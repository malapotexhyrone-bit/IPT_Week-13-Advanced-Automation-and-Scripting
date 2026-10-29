<?php

namespace App\Services;

use App\Exceptions\ClearanceAlreadyExistsException;
use App\Exceptions\NoActiveOfficesException;
use App\Models\AcademicTerm;
use App\Models\Clearance;
use App\Models\Office;
use App\Models\OfficeTask;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClearanceInitiationService
{
    public function initiate(
        User $student,
        string $termCode,
        string $correlationId,
        ?string $idempotencyKey,
    ): Clearance {
        $term = AcademicTerm::query()
            ->where('code', $termCode)
            ->where('is_active', true)
            ->first();

        if (! $term) {
            throw ValidationException::withMessages([
                'term_code' => ['The selected academic term is not active.'],
            ]);
        }

        $offices = Office::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($offices->isEmpty()) {
            throw new NoActiveOfficesException('No active clearing offices are configured.');
        }

        return DB::transaction(function () use (
            $student,
            $term,
            $offices,
            $correlationId,
            $idempotencyKey,
        ): Clearance {
            $existing = Clearance::query()
                ->where('student_id', $student->id)
                ->where('academic_term_id', $term->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw new ClearanceAlreadyExistsException($existing);
            }

            $clearance = Clearance::query()->create([
                'student_id' => $student->id,
                'academic_term_id' => $term->id,
                'status' => Clearance::STATUS_IN_PROGRESS,
                'correlation_id' => $correlationId,
                'idempotency_key_hash' => $idempotencyKey
                    ? hash('sha256', $idempotencyKey)
                    : null,
                'initiated_at' => now(),
            ]);

            foreach ($offices as $office) {
                $clearance->officeTasks()->create([
                    'office_id' => $office->id,
                    'status' => OfficeTask::STATUS_PENDING,
                    'due_at' => now()->addDays(3),
                ]);
            }

            $clearance->events()->create([
                'actor_user_id' => $student->id,
                'event_type' => 'clearance_initiated',
                'correlation_id' => $correlationId,
                'metadata' => ['office_task_count' => $offices->count()],
                'occurred_at' => now(),
            ]);

            return $clearance->load(['academicTerm', 'officeTasks.office']);
        });
    }
}
