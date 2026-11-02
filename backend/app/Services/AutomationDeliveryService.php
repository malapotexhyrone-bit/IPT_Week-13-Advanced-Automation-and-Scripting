<?php

namespace App\Services;

use App\Contracts\NotificationGateway;
use App\Exceptions\NotificationDeliveryException;
use App\Models\AutomationDelivery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutomationDeliveryService
{
    public function __construct(private readonly NotificationGateway $gateway) {}

    public function process(AutomationDelivery $delivery): string
    {
        return DB::transaction(function () use ($delivery): string {
            $delivery = AutomationDelivery::query()->lockForUpdate()->findOrFail($delivery->id);

            if (in_array($delivery->status, [
                AutomationDelivery::STATUS_DELIVERED,
                AutomationDelivery::STATUS_HUMAN_ACTION,
            ], true)) {
                return $delivery->status;
            }

            $attemptNumber = $delivery->attempt_count + 1;

            try {
                $this->gateway->send($delivery);
                $delivery->attempts()->create([
                    'attempt_number' => $attemptNumber,
                    'outcome' => 'delivered',
                    'correlation_id' => $delivery->correlation_id,
                    'attempted_at' => now(),
                ]);
                $delivery->update([
                    'status' => AutomationDelivery::STATUS_DELIVERED,
                    'attempt_count' => $attemptNumber,
                    'next_attempt_at' => null,
                    'last_error_code' => null,
                    'delivered_at' => now(),
                ]);
                $this->applyDeliveredEffect($delivery);
                $this->logOutcome($delivery, $attemptNumber, 'delivered');

                return AutomationDelivery::STATUS_DELIVERED;
            } catch (NotificationDeliveryException $exception) {
                $delivery->attempts()->create([
                    'attempt_number' => $attemptNumber,
                    'outcome' => $exception->retryable ? 'transient_failure' : 'permanent_failure',
                    'error_code' => $exception->safeCode,
                    'correlation_id' => $delivery->correlation_id,
                    'attempted_at' => now(),
                ]);

                $exhausted = ! $exception->retryable || $attemptNumber >= $delivery->max_attempts;

                if ($exhausted) {
                    $delivery->update([
                        'status' => AutomationDelivery::STATUS_HUMAN_ACTION,
                        'attempt_count' => $attemptNumber,
                        'next_attempt_at' => null,
                        'last_error_code' => $exception->safeCode,
                        'retry_allowed' => $exception->retryable,
                        'failed_at' => now(),
                    ]);
                    $delivery->humanActionItem()->firstOrCreate([], [
                        'owner_role' => 'administrator',
                        'reason_code' => $exception->safeCode,
                        'status' => 'open',
                        'next_step' => 'Inspect the dependency, then use the authorized recovery procedure.',
                    ]);
                    $this->logOutcome($delivery, $attemptNumber, 'human_action_required', $exception->safeCode);

                    return AutomationDelivery::STATUS_HUMAN_ACTION;
                }

                $delivery->update([
                    'status' => AutomationDelivery::STATUS_RETRY_SCHEDULED,
                    'attempt_count' => $attemptNumber,
                    'next_attempt_at' => now()->addSeconds(2 ** ($attemptNumber - 1)),
                    'last_error_code' => $exception->safeCode,
                    'retry_allowed' => true,
                ]);
                $this->logOutcome($delivery, $attemptNumber, 'retry_scheduled', $exception->safeCode);

                return AutomationDelivery::STATUS_RETRY_SCHEDULED;
            }
        });
    }

    private function applyDeliveredEffect(AutomationDelivery $delivery): void
    {
        if ($delivery->automation_type === AutomationDelivery::TYPE_REMINDER) {
            $delivery->officeTask?->update(['last_reminded_at' => now()]);
        }

        if ($delivery->automation_type === AutomationDelivery::TYPE_ESCALATION) {
            $delivery->officeTask?->update(['escalated_at' => now()]);
        }
    }

    private function logOutcome(
        AutomationDelivery $delivery,
        int $attempt,
        string $outcome,
        ?string $errorCode = null,
    ): void {
        Log::info('automation.delivery_outcome', array_filter([
            'delivery_id' => $delivery->id,
            'automation_type' => $delivery->automation_type,
            'attempt' => $attempt,
            'outcome' => $outcome,
            'error_code' => $errorCode,
            'correlation_id' => $delivery->correlation_id,
        ], fn ($value) => $value !== null));
    }
}
