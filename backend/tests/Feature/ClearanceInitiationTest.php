<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClearanceInitiationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_initiate_clearance_and_create_one_task_per_active_office(): void
    {
        $term = $this->activeTerm();
        $offices = $this->activeOffices();
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $correlationId = (string) Str::ulid();
        $idempotencyKey = 'clearance-request-001';

        Sanctum::actingAs($student);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'X-Correlation-ID' => $correlationId,
            'Idempotency-Key' => $idempotencyKey,
        ])->postJson('/api/v1/clearances', [
            'term_code' => $term->code,
        ]);

        $response
            ->assertCreated()
            ->assertHeader('X-Correlation-ID', $correlationId)
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.term.code', $term->code)
            ->assertJsonCount($offices->count(), 'data.office_tasks')
            ->assertJsonPath('meta.correlation_id', $correlationId);

        $this->assertDatabaseHas('clearances', [
            'student_id' => $student->id,
            'academic_term_id' => $term->id,
            'status' => 'in_progress',
            'correlation_id' => $correlationId,
            'idempotency_key_hash' => hash('sha256', $idempotencyKey),
        ]);
        $this->assertDatabaseCount('office_tasks', $offices->count());
        $this->assertDatabaseHas('clearance_events', [
            'event_type' => 'clearance_initiated',
            'actor_user_id' => $student->id,
            'correlation_id' => $correlationId,
        ]);
    }

    public function test_request_requires_authentication(): void
    {
        $this->activeTerm();
        $this->activeOffices();

        $response = $this->postJson('/api/v1/clearances', [
            'term_code' => '2026-2027-1',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
        $this->assertDatabaseCount('clearances', 0);
    }

    public function test_office_user_cannot_initiate_student_clearance(): void
    {
        $this->activeTerm();
        $offices = $this->activeOffices();
        $officeUser = User::factory()->create([
            'role' => User::ROLE_OFFICE,
            'office_id' => $offices->first()->id,
        ]);

        Sanctum::actingAs($officeUser);

        $response = $this->postJson('/api/v1/clearances', [
            'term_code' => '2026-2027-1',
        ]);

        $response
            ->assertForbidden()
            ->assertJsonPath('error.code', 'ROLE_FORBIDDEN');
        $this->assertDatabaseCount('clearances', 0);
    }

    public function test_invalid_request_creates_no_partial_records(): void
    {
        $this->activeTerm();
        $this->activeOffices();
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_STUDENT]));

        $response = $this->postJson('/api/v1/clearances', []);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonValidationErrors('term_code', 'error.fields');
        $this->assertDatabaseCount('clearances', 0);
        $this->assertDatabaseCount('office_tasks', 0);
    }

    public function test_inactive_term_is_rejected(): void
    {
        AcademicTerm::query()->create([
            'code' => '2025-2026-2',
            'name' => 'Second Semester 2025-2026',
            'is_active' => false,
        ]);
        $this->activeOffices();
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_STUDENT]));

        $response = $this->postJson('/api/v1/clearances', [
            'term_code' => '2025-2026-2',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
        $this->assertDatabaseCount('clearances', 0);
    }

    public function test_duplicate_term_is_rejected_without_duplicate_tasks(): void
    {
        $term = $this->activeTerm();
        $offices = $this->activeOffices();
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        Sanctum::actingAs($student);

        $this->postJson('/api/v1/clearances', ['term_code' => $term->code])
            ->assertCreated();

        $response = $this->postJson('/api/v1/clearances', [
            'term_code' => $term->code,
        ]);

        $response
            ->assertConflict()
            ->assertJsonPath('error.code', 'CLEARANCE_ALREADY_EXISTS');
        $this->assertDatabaseCount('clearances', 1);
        $this->assertDatabaseCount('office_tasks', $offices->count());
        $this->assertDatabaseCount('clearance_events', 1);
    }

    public function test_missing_active_offices_returns_safe_unavailable_response(): void
    {
        $term = $this->activeTerm();
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_STUDENT]));

        $response = $this->postJson('/api/v1/clearances', [
            'term_code' => $term->code,
        ]);

        $response
            ->assertStatus(503)
            ->assertJsonPath('error.code', 'CLEARING_OFFICES_UNAVAILABLE');
        $this->assertDatabaseCount('clearances', 0);
    }

    private function activeTerm(): AcademicTerm
    {
        return AcademicTerm::query()->create([
            'code' => '2026-2027-1',
            'name' => 'First Semester 2026-2027',
            'is_active' => true,
        ]);
    }

    private function activeOffices()
    {
        return collect([
            ['code' => 'REGISTRAR', 'name' => 'Registrar', 'sort_order' => 10],
            ['code' => 'LIBRARY', 'name' => 'Library', 'sort_order' => 20],
            ['code' => 'ACCOUNTING', 'name' => 'Accounting', 'sort_order' => 30],
        ])->map(fn (array $office) => Office::query()->create([
            ...$office,
            'is_active' => true,
        ]));
    }
}
