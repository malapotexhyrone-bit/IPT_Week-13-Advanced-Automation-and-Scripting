<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ClearanceAlreadyExistsException;
use App\Exceptions\NoActiveOfficesException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClearanceRequest;
use App\Models\Clearance;
use App\Services\ClearanceInitiationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ClearanceController extends Controller
{
    public function store(
        StoreClearanceRequest $request,
        ClearanceInitiationService $service,
    ): JsonResponse {
        $correlationId = $request->attributes->get('correlation_id');

        try {
            $clearance = $service->initiate(
                $request->user(),
                $request->validated('term_code'),
                $correlationId,
                $request->validated('idempotency_key'),
            );
        } catch (ClearanceAlreadyExistsException $exception) {
            return $this->error(
                'CLEARANCE_ALREADY_EXISTS',
                $exception->getMessage(),
                $correlationId,
                409,
            );
        } catch (NoActiveOfficesException) {
            return $this->error(
                'CLEARING_OFFICES_UNAVAILABLE',
                'Clearance initiation is temporarily unavailable for human review.',
                $correlationId,
                503,
            );
        } catch (ValidationException $exception) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_FAILED',
                    'message' => 'The request contains invalid data.',
                    'correlation_id' => $correlationId,
                    'fields' => $exception->errors(),
                ],
            ], 422);
        }

        return response()->json([
            'data' => $this->clearanceData($clearance),
            'meta' => ['correlation_id' => $correlationId],
        ], 201);
    }

    private function clearanceData(Clearance $clearance): array
    {
        return [
            'id' => $clearance->id,
            'status' => $clearance->status,
            'initiated_at' => $clearance->initiated_at->toIso8601String(),
            'term' => [
                'code' => $clearance->academicTerm->code,
                'name' => $clearance->academicTerm->name,
            ],
            'office_tasks' => $clearance->officeTasks->map(fn ($task): array => [
                'id' => $task->id,
                'status' => $task->status,
                'due_at' => $task->due_at->toIso8601String(),
                'office' => [
                    'code' => $task->office->code,
                    'name' => $task->office->name,
                ],
            ])->values(),
        ];
    }

    private function error(
        string $code,
        string $message,
        string $correlationId,
        int $status,
    ): JsonResponse {
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
