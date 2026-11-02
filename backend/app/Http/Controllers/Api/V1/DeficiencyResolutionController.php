<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\InvalidWorkflowTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveDeficiencyRequest;
use App\Models\Deficiency;
use App\Services\OfficeTaskWorkflowService;
use Illuminate\Http\JsonResponse;

class DeficiencyResolutionController extends Controller
{
    public function store(
        ResolveDeficiencyRequest $request,
        Deficiency $deficiency,
        OfficeTaskWorkflowService $service,
    ): JsonResponse {
        $deficiency->loadMissing('officeTask.clearance', 'officeTask.office');

        if ((int) $request->user()->id !== (int) $deficiency->officeTask->clearance->student_id) {
            return $this->error(
                'DEFICIENCY_FORBIDDEN',
                'This deficiency belongs to another student clearance.',
                $request->attributes->get('correlation_id'),
                403,
            );
        }

        try {
            $deficiency = $service->resolveDeficiency(
                $deficiency,
                $request->user(),
                $request->validated('resolution_note'),
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
                'id' => $deficiency->id,
                'status' => $deficiency->status,
                'resolution_note' => $deficiency->resolution_note,
                'resolved_at' => $deficiency->resolved_at->toIso8601String(),
                'office_task' => [
                    'id' => $deficiency->officeTask->id,
                    'status' => $deficiency->officeTask->status,
                    'office_code' => $deficiency->officeTask->office->code,
                ],
            ],
            'meta' => ['correlation_id' => $request->attributes->get('correlation_id')],
        ]);
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
