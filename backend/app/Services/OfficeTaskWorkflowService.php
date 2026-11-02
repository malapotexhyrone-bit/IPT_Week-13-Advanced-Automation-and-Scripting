<?php

namespace App\Services;

use App\Exceptions\InvalidWorkflowTransitionException;
use App\Models\Clearance;
use App\Models\Deficiency;
use App\Models\OfficeTask;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OfficeTaskWorkflowService
{
    public function reportDeficiency(
        OfficeTask $task,
        User $reviewer,
        string $description,
        string $correlationId,
    ): Deficiency {
        return DB::transaction(function () use ($task, $reviewer, $description, $correlationId): Deficiency {
            $task = OfficeTask::query()->lockForUpdate()->findOrFail($task->id);

            if ($task->status === OfficeTask::STATUS_APPROVED) {
                throw new InvalidWorkflowTransitionException(
                    'TASK_ALREADY_APPROVED',
                    'An approved task cannot receive a deficiency.',
                );
            }

            if ($task->deficiencies()->where('status', Deficiency::STATUS_OPEN)->exists()) {
                throw new InvalidWorkflowTransitionException(
                    'DEFICIENCY_ALREADY_OPEN',
                    'Resolve the existing deficiency before recording another one.',
                );
            }

            $deficiency = $task->deficiencies()->create([
                'reported_by' => $reviewer->id,
                'description' => $description,
                'status' => Deficiency::STATUS_OPEN,
            ]);

            $task->update([
                'status' => OfficeTask::STATUS_DEFICIENCY,
                'reviewed_by' => $reviewer->id,
            ]);

            $task->clearance->events()->create([
                'office_task_id' => $task->id,
                'actor_user_id' => $reviewer->id,
                'event_type' => 'deficiency_reported',
                'correlation_id' => $correlationId,
                'metadata' => ['deficiency_id' => $deficiency->id],
                'occurred_at' => now(),
            ]);

            return $deficiency->fresh(['officeTask.office']);
        });
    }

    public function resolveDeficiency(
        Deficiency $deficiency,
        User $student,
        string $resolutionNote,
        string $correlationId,
    ): Deficiency {
        return DB::transaction(function () use ($deficiency, $student, $resolutionNote, $correlationId): Deficiency {
            $deficiency = Deficiency::query()->lockForUpdate()->findOrFail($deficiency->id);
            $task = OfficeTask::query()->lockForUpdate()->findOrFail($deficiency->office_task_id);

            if ($deficiency->status !== Deficiency::STATUS_OPEN || $task->status !== OfficeTask::STATUS_DEFICIENCY) {
                throw new InvalidWorkflowTransitionException(
                    'DEFICIENCY_NOT_OPEN',
                    'Only an open deficiency can be resolved.',
                );
            }

            $deficiency->update([
                'status' => Deficiency::STATUS_RESOLVED,
                'resolution_note' => $resolutionNote,
                'resolved_at' => now(),
            ]);

            $task->update(['status' => OfficeTask::STATUS_AWAITING_RE_REVIEW]);

            $task->clearance->events()->create([
                'office_task_id' => $task->id,
                'actor_user_id' => $student->id,
                'event_type' => 'deficiency_resolved',
                'correlation_id' => $correlationId,
                'metadata' => ['deficiency_id' => $deficiency->id],
                'occurred_at' => now(),
            ]);

            return $deficiency->fresh(['officeTask.office']);
        });
    }

    public function approveTask(
        OfficeTask $task,
        User $reviewer,
        string $correlationId,
    ): OfficeTask {
        return DB::transaction(function () use ($task, $reviewer, $correlationId): OfficeTask {
            $task = OfficeTask::query()->lockForUpdate()->findOrFail($task->id);

            if (! in_array($task->status, [
                OfficeTask::STATUS_PENDING,
                OfficeTask::STATUS_AWAITING_RE_REVIEW,
            ], true)) {
                throw new InvalidWorkflowTransitionException(
                    'TASK_NOT_APPROVABLE',
                    'The office task is not eligible for approval.',
                );
            }

            if ($task->deficiencies()->where('status', Deficiency::STATUS_OPEN)->exists()) {
                throw new InvalidWorkflowTransitionException(
                    'OPEN_DEFICIENCY_EXISTS',
                    'Resolve the open deficiency before approval.',
                );
            }

            $task->update([
                'status' => OfficeTask::STATUS_APPROVED,
                'reviewed_by' => $reviewer->id,
                'approved_at' => now(),
            ]);

            $clearance = Clearance::query()->lockForUpdate()->findOrFail($task->clearance_id);
            $clearance->events()->create([
                'office_task_id' => $task->id,
                'actor_user_id' => $reviewer->id,
                'event_type' => 'office_task_approved',
                'correlation_id' => $correlationId,
                'metadata' => ['office_id' => $task->office_id],
                'occurred_at' => now(),
            ]);

            $hasIncompleteTask = $clearance->officeTasks()
                ->where('status', '!=', OfficeTask::STATUS_APPROVED)
                ->exists();

            if (! $hasIncompleteTask && $clearance->status !== Clearance::STATUS_COMPLETED) {
                $clearance->update([
                    'status' => Clearance::STATUS_COMPLETED,
                    'completed_at' => now(),
                ]);

                $clearance->events()->create([
                    'actor_user_id' => $reviewer->id,
                    'event_type' => 'clearance_completed',
                    'correlation_id' => $correlationId,
                    'metadata' => ['approved_task_count' => $clearance->officeTasks()->count()],
                    'occurred_at' => now(),
                ]);

                app(ClearanceAutomationService::class)->completionDelivery($clearance);
            }

            return $task->fresh(['office', 'clearance']);
        });
    }
}
