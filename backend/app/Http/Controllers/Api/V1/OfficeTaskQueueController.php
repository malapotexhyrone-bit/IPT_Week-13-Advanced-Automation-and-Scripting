<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\OfficeTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OfficeTaskQueueController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tasks = OfficeTask::query()
            ->with(['office', 'clearance.student', 'deficiencies'])
            ->where('office_id', $request->user()->office_id)
            ->latest()
            ->get()
            ->map(fn (OfficeTask $task): array => [
                'id' => $task->id,
                'status' => $task->status,
                'due_at' => $task->due_at->toIso8601String(),
                'office' => [
                    'code' => $task->office->code,
                    'name' => $task->office->name,
                ],
                'student' => [
                    'id' => $task->clearance->student->id,
                    'name' => $task->clearance->student->name,
                ],
                'deficiencies' => $task->deficiencies->map(fn ($deficiency): array => [
                    'id' => $deficiency->id,
                    'description' => $deficiency->description,
                    'status' => $deficiency->status,
                    'resolution_note' => $deficiency->resolution_note,
                ])->values(),
            ]);

        return response()->json([
            'data' => $tasks,
            'meta' => ['correlation_id' => $request->attributes->get('correlation_id')],
        ]);
    }
}
