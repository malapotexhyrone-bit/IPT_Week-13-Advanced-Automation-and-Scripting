<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\InvalidWorkflowTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeficiencyRequest;
use App\Models\Deficiency;
use App\Models\OfficeTask;
use App\Services\OfficeTaskWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfficeTaskReviewController extends Controller
{
    public function storeDeficiency(
        StoreDeficiencyRequest $request,
        OfficeTask $officeTask,
        OfficeTaskWorkflowService $service,
    ): JsonResponse {
        if ((int) $request->user()->office_id !== (int) $officeTask->office_id) {
            return $this->error(
                'OFFICE_TASK_FORBIDDEN',
                'This task is assigned to another clearing office.',
                $request->attributes->get('correlation_id'),
                403,
            );
        }

        try {
            $deficiency = $service->reportDeficiency(
                $officeTask,
                $request->user(),
                $request->validated('description'),
                $request->attributes->get('correlation_id'),
            );
        } catch (InvalidWorkflowTransitionException $exception) {
            return $this->error(
                $exception->errorCode,
                $exception->getMessage(),
                $request->attributes->get('correlation_id'),
                409,
            );
        }

        return response()->json([
            'data' => $this->deficiencyData($deficiency),
            'meta' => ['correlation_id' => $request->attributes->get('correlation_id')],
        ], 201);
    }

    public function approve(
        Request $request,
        OfficeTask $officeTask,
        OfficeTaskWorkflowService $service,
    ): JsonResponse {
        if ((int) $request->user()->office_id !== (int) $officeTask->office_id) {
            return $this->error(
                'OFFICE_TASK_FORBIDDEN',
                'This task is assigned to another clearing office.',
                $request->attributes->get('correlation_id'),
                403,
            );
        }

        try {
            $task = $service->approveTask(
                $officeTask,
                $request->user(),
                $request->attributes->get('correlation_id'),
            );
        } catch (InvalidWorkflowTransitionException $exception) {
            return $this->error(
                $exception->errorCode,
                $exception->getMessage(),
                $request->attributes->get('correlation_id'),
                409,
            );
        }

        return response()->json([
            'data' => [
                'id' => $task->id,
                'status' => $task->status,
                'approved_at' => $task->approved_at->toIso8601String(),
                'office' => [
                    'code' => $task->office->code,
                    'name' => $task->office->name,
                ],
                'clearance_status' => $task->clearance->status,
            ],
            'meta' => ['correlation_id' => $request->attributes->get('correlation_id')],
        ]);
    }

    private function deficiencyData(Deficiency $deficiency): array
    {
        return [
            'id' => $deficiency->id,
            'status' => $deficiency->status,
            'description' => $deficiency->description,
            'office_task' => [
                'id' => $deficiency->officeTask->id,
                'status' => $deficiency->officeTask->status,
                'office_code' => $deficiency->officeTask->office->code,
            ],
        ];
    }

    private function error(string $code, string $message, string $correlationId, int $status): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'correlation_id' => $correlationId,
                'fields' => (object) [],
            ],
        ], $status);
    }
}
