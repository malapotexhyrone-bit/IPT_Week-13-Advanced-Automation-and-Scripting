<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\Clearance;
use App\Models\Deficiency;
use App\Models\Office;
use App\Models\OfficeTask;
use App\Models\User;
use App\Services\ClearanceInitiationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OfficeTaskWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_office_can_report_a_deficiency(): void
    {
        $workflow = $this->workflow();
        $task = $workflow['clearance']->officeTasks->first();
        $reviewer = $workflow['reviewers'][$task->office_id];
        Sanctum::actingAs($reviewer);

        $response = $this->postJson("/api/v1/office-tasks/{$task->id}/deficiencies", [
            'description' => 'Return the borrowed laboratory equipment.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.status', Deficiency::STATUS_OPEN)
            ->assertJsonPath('data.office_task.status', OfficeTask::STATUS_DEFICIENCY);
        $this->assertDatabaseHas('deficiencies', [
            'office_task_id' => $task->id,
            'reported_by' => $reviewer->id,
            'status' => Deficiency::STATUS_OPEN,
        ]);
        $this->assertDatabaseHas('clearance_events', [
            'office_task_id' => $task->id,
            'event_type' => 'deficiency_reported',
        ]);
    }

    public function test_office_cannot_review_another_offices_task(): void
    {
        $workflow = $this->workflow();
        $task = $workflow['clearance']->officeTasks->first();
        $wrongReviewer = $workflow['reviewers']->first(
            fn (User $reviewer) => $reviewer->office_id !== $task->office_id,
        );
        Sanctum::actingAs($wrongReviewer);

        $this->postJson("/api/v1/office-tasks/{$task->id}/deficiencies", [
            'description' => 'This must not be recorded.',
        ])->assertForbidden()->assertJsonPath('error.code', 'OFFICE_TASK_FORBIDDEN');

        $this->assertDatabaseCount('deficiencies', 0);
    }

    public function test_deficiency_description_is_validated(): void
    {
        $workflow = $this->workflow();
        $task = $workflow['clearance']->officeTasks->first();
        Sanctum::actingAs($workflow['reviewers'][$task->office_id]);

        $this->postJson("/api/v1/office-tasks/{$task->id}/deficiencies", [
            'description' => 'bad',
        ])->assertUnprocessable()->assertJsonPath('error.code', 'VALIDATION_FAILED');

        $this->assertDatabaseCount('deficiencies', 0);
    }

    public function test_owning_student_can_resolve_an_open_deficiency(): void
    {
        [$workflow, $deficiency] = $this->workflowWithDeficiency();
        Sanctum::actingAs($workflow['student']);

        $response = $this->postJson("/api/v1/deficiencies/{$deficiency->id}/resolution", [
            'resolution_note' => 'Equipment was returned to the laboratory custodian.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.status', Deficiency::STATUS_RESOLVED)
            ->assertJsonPath('data.office_task.status', OfficeTask::STATUS_AWAITING_RE_REVIEW);
        $this->assertDatabaseHas('clearance_events', [
            'event_type' => 'deficiency_resolved',
            'actor_user_id' => $workflow['student']->id,
        ]);
    }

    public function test_another_student_cannot_resolve_the_deficiency(): void
    {
        [, $deficiency] = $this->workflowWithDeficiency();
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_STUDENT]));

        $this->postJson("/api/v1/deficiencies/{$deficiency->id}/resolution", [
            'resolution_note' => 'Attempted unauthorized resolution.',
        ])->assertForbidden()->assertJsonPath('error.code', 'DEFICIENCY_FORBIDDEN');

        $this->assertDatabaseHas('deficiencies', [
            'id' => $deficiency->id,
            'status' => Deficiency::STATUS_OPEN,
        ]);
    }

    public function test_resolved_deficiency_cannot_be_resolved_twice(): void
    {
        [$workflow, $deficiency] = $this->workflowWithDeficiency();
        Sanctum::actingAs($workflow['student']);

        $this->postJson("/api/v1/deficiencies/{$deficiency->id}/resolution", [
            'resolution_note' => 'First valid resolution note.',
        ])->assertOk();

        $this->postJson("/api/v1/deficiencies/{$deficiency->id}/resolution", [
            'resolution_note' => 'Second resolution attempt.',
        ])->assertConflict()->assertJsonPath('error.code', 'DEFICIENCY_NOT_OPEN');
    }

    public function test_task_with_open_deficiency_cannot_be_approved(): void
    {
        [$workflow, $deficiency] = $this->workflowWithDeficiency();
        $task = $deficiency->officeTask;
        Sanctum::actingAs($workflow['reviewers'][$task->office_id]);

        $this->postJson("/api/v1/office-tasks/{$task->id}/approve")
            ->assertConflict()
            ->assertJsonPath('error.code', 'TASK_NOT_APPROVABLE');

        $this->assertDatabaseHas('office_tasks', [
            'id' => $task->id,
            'status' => OfficeTask::STATUS_DEFICIENCY,
        ]);
    }

    public function test_approving_every_task_completes_clearance_once(): void
    {
        $workflow = $this->workflow();

        foreach ($workflow['clearance']->officeTasks as $index => $task) {
            Sanctum::actingAs($workflow['reviewers'][$task->office_id]);
            $response = $this->postJson("/api/v1/office-tasks/{$task->id}/approve")->assertOk();
            $response->assertJsonPath(
                'data.clearance_status',
                $index === $workflow['clearance']->officeTasks->count() - 1
                    ? Clearance::STATUS_COMPLETED
                    : Clearance::STATUS_IN_PROGRESS,
            );
        }

        $this->assertDatabaseHas('clearances', [
            'id' => $workflow['clearance']->id,
            'status' => Clearance::STATUS_COMPLETED,
        ]);
        $this->assertDatabaseCount('office_tasks', 2);
        $this->assertDatabaseCount('clearance_events', 4);
        $this->assertDatabaseHas('clearance_events', ['event_type' => 'clearance_completed']);
    }

    public function test_student_role_cannot_approve_an_office_task(): void
    {
        $workflow = $this->workflow();
        Sanctum::actingAs($workflow['student']);

        $this->postJson("/api/v1/office-tasks/{$workflow['clearance']->officeTasks->first()->id}/approve")
            ->assertForbidden()
            ->assertJsonPath('error.code', 'ROLE_FORBIDDEN');
    }

    private function workflow(): array
    {
        $term = AcademicTerm::query()->create([
            'code' => '2026-2027-1',
            'name' => 'First Semester 2026-2027',
            'is_active' => true,
        ]);
        $offices = collect([
            ['code' => 'LIBRARY', 'name' => 'Library', 'sort_order' => 10],
            ['code' => 'ACCOUNTING', 'name' => 'Accounting', 'sort_order' => 20],
        ])->map(fn (array $data) => Office::query()->create([...$data, 'is_active' => true]));
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $reviewers = $offices->mapWithKeys(function (Office $office): array {
            $reviewer = User::factory()->create([
                'role' => User::ROLE_OFFICE,
                'office_id' => $office->id,
            ]);

            return [$office->id => $reviewer];
        });
        $clearance = app(ClearanceInitiationService::class)->initiate(
            $student,
            $term->code,
            (string) Str::ulid(),
            null,
        );

        return compact('student', 'reviewers', 'clearance');
    }

    private function workflowWithDeficiency(): array
    {
        $workflow = $this->workflow();
        $task = $workflow['clearance']->officeTasks->first();
        Sanctum::actingAs($workflow['reviewers'][$task->office_id]);
        $response = $this->postJson("/api/v1/office-tasks/{$task->id}/deficiencies", [
            'description' => 'Return the borrowed laboratory equipment.',
        ])->assertCreated();

        return [$workflow, Deficiency::query()->findOrFail($response->json('data.id'))];
    }
}
