<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\Office;
use App\Models\User;
use App\Services\ClearanceInitiationService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FlutterApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_current_clearance_returns_only_authenticated_students_record(): void
    {
        $this->seed(DatabaseSeeder::class);
        $student = User::query()->where('role', User::ROLE_STUDENT)->firstOrFail();
        $otherStudent = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $term = AcademicTerm::query()->where('is_active', true)->firstOrFail();
        app(ClearanceInitiationService::class)->initiate(
            $student,
            $term->code,
            (string) Str::ulid(),
            null,
        );
        Sanctum::actingAs($otherStudent);
        $this->getJson('/api/v1/clearances/current')->assertOk()->assertJsonPath('data', null);

        Sanctum::actingAs($student);
        $this->getJson('/api/v1/clearances/current')
            ->assertOk()
            ->assertJsonPath('data.term.code', $term->code)
            ->assertJsonCount(4, 'data.office_tasks');
    }

    public function test_office_queue_returns_only_tasks_for_authenticated_office(): void
    {
        $this->seed(DatabaseSeeder::class);
        $student = User::query()->where('role', User::ROLE_STUDENT)->firstOrFail();
        $term = AcademicTerm::query()->where('is_active', true)->firstOrFail();
        app(ClearanceInitiationService::class)->initiate(
            $student,
            $term->code,
            (string) Str::ulid(),
            null,
        );
        $office = Office::query()->where('code', 'LIBRARY')->firstOrFail();
        $reviewer = User::query()
            ->where('role', User::ROLE_OFFICE)
            ->where('office_id', $office->id)
            ->firstOrFail();
        Sanctum::actingAs($reviewer);

        $this->getJson('/api/v1/office-tasks')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.office.code', 'LIBRARY')
            ->assertJsonPath('data.0.student.name', 'Ari Santos');
    }
}
