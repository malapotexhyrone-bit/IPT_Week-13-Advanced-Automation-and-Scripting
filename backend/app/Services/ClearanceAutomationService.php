<?php

namespace App\Services;

use App\Jobs\ProcessAutomationDelivery;
use App\Models\AutomationDelivery;
use App\Models\Clearance;
use App\Models\OfficeTask;
use Illuminate\Support\Str;

class ClearanceAutomationService
{
    public function scanAndDispatch(): int
    {
        $deliveries = collect();

        OfficeTask::query()
            ->where('status', '!=', OfficeTask::STATUS_APPROVED)
            ->where('due_at', '<=', now()->addDay())
            ->where(function ($query): void {
                $query->whereNull('last_reminded_at')
                    ->orWhere('last_reminded_at', '<=', now()->subDay());
            })
            ->get()
            ->each(function (OfficeTask $task) use ($deliveries): void {
                $deliveries->push($this->firstOrCreate(
                    AutomationDelivery::TYPE_REMINDER,
                    "reminder:task:{$task->id}:".now()->toDateString(),
                    'office_personnel',
                    $task->clearance_id,
                    $task->id,
                ));
            });

        OfficeTask::query()
            ->where('status', '!=', OfficeTask::STATUS_APPROVED)
            ->where('due_at', '<', now())
            ->whereNull('escalated_at')
            ->get()
            ->each(function (OfficeTask $task) use ($deliveries): void {
                $deliveries->push($this->firstOrCreate(
                    AutomationDelivery::TYPE_ESCALATION,
                    "escalation:task:{$task->id}",
                    'administrator',
                    $task->clearance_id,
                    $task->id,
                ));
            });

        Clearance::query()
            ->where('status', Clearance::STATUS_COMPLETED)
            ->get()
            ->each(function (Clearance $clearance) use ($deliveries): void {
                $deliveries->push($this->completionDelivery($clearance));
            });

        AutomationDelivery::query()
            ->where('status', AutomationDelivery::STATUS_RETRY_SCHEDULED)
            ->where('next_attempt_at', '<=', now())
            ->get()
            ->each(fn (AutomationDelivery $delivery) => $deliveries->push($delivery));

        $deliveries->filter()->unique('id')->each(function (AutomationDelivery $delivery): void {
            if ($delivery->status === AutomationDelivery::STATUS_PENDING
                || ($delivery->status === AutomationDelivery::STATUS_RETRY_SCHEDULED
                    && $delivery->next_attempt_at?->isPast())) {
                ProcessAutomationDelivery::dispatch($delivery->id);
            }
        });

        return $deliveries->filter()->unique('id')->count();
    }

    public function completionDelivery(Clearance $clearance): AutomationDelivery
    {
        return $this->firstOrCreate(
            AutomationDelivery::TYPE_COMPLETION,
            "completion:clearance:{$clearance->id}",
            'student',
            $clearance->id,
        );
    }

    private function firstOrCreate(
        string $type,
        string $idempotencyKey,
        string $recipientRole,
        int $clearanceId,
        ?int $officeTaskId = null,
    ): AutomationDelivery {
        return AutomationDelivery::query()->firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'clearance_id' => $clearanceId,
                'office_task_id' => $officeTaskId,
                'automation_type' => $type,
                'recipient_role' => $recipientRole,
                'status' => AutomationDelivery::STATUS_PENDING,
                'correlation_id' => (string) Str::ulid(),
                'max_attempts' => 3,
            ],
        );
    }
}
