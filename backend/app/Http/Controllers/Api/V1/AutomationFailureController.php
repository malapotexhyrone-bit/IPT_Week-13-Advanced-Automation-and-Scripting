<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAutomationDelivery;
use App\Models\AutomationDelivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AutomationFailureController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = AutomationDelivery::query()
            ->with('humanActionItem')
            ->where('status', AutomationDelivery::STATUS_HUMAN_ACTION)
            ->latest('failed_at')
            ->get()
            ->map(fn (AutomationDelivery $delivery): array => $this->failureData($delivery));

        return response()->json([
            'data' => $items,
            'meta' => ['correlation_id' => $request->attributes->get('correlation_id')],
        ]);
    }

    public function retry(Request $request, AutomationDelivery $automationDelivery): JsonResponse
    {
        if ($automationDelivery->status !== AutomationDelivery::STATUS_HUMAN_ACTION
            || ! $automationDelivery->retry_allowed) {
            return response()->json([
                'error' => [
                    'code' => 'AUTOMATION_RETRY_NOT_ALLOWED',
                    'message' => 'This failure is not eligible for an automated retry.',
                    'correlation_id' => $request->attributes->get('correlation_id'),
                    'fields' => (object) [],
                ],
            ], 409);
        }

        $previousAttempts = $automationDelivery->attempt_count;
        $newAttemptLimit = $automationDelivery->max_attempts + 3;

        DB::transaction(function () use ($automationDelivery, $newAttemptLimit): void {
            $automationDelivery->update([
                'status' => AutomationDelivery::STATUS_RETRY_SCHEDULED,
                'max_attempts' => $newAttemptLimit,
                'next_attempt_at' => now(),
                'retry_allowed' => false,
                'failed_at' => null,
            ]);
            $automationDelivery->humanActionItem()->update(['status' => 'retry_requested']);
            ProcessAutomationDelivery::dispatch($automationDelivery->id)->afterCommit();
        });

        return response()->json([
            'data' => [
                'id' => $automationDelivery->id,
                'status' => AutomationDelivery::STATUS_RETRY_SCHEDULED,
                'previous_attempts_preserved' => $previousAttempts,
                'new_attempt_limit' => $newAttemptLimit,
            ],
            'meta' => ['correlation_id' => $request->attributes->get('correlation_id')],
        ], 202);
    }

    private function failureData(AutomationDelivery $delivery): array
    {
        return [
            'id' => $delivery->id,
            'automation_type' => $delivery->automation_type,
            'status' => $delivery->status,
            'attempt_count' => $delivery->attempt_count,
            'error_code' => $delivery->last_error_code,
            'correlation_id' => $delivery->correlation_id,
            'retry_allowed' => $delivery->retry_allowed,
            'owner_role' => $delivery->humanActionItem?->owner_role,
            'next_step' => $delivery->humanActionItem?->next_step,
        ];
    }
}
