<?php

namespace Tests\Feature;

use App\Contracts\NotificationGateway;
use App\Exceptions\NotificationDeliveryException;
use App\Jobs\ProcessAutomationDelivery;
use App\Models\AcademicTerm;
use App\Models\AutomationDelivery;
use App\Models\Clearance;
use App\Models\Office;
use App\Models\OfficeTask;
use App\Models\User;
use App\Services\AutomationDeliveryService;
use App\Services\ClearanceAutomationService;
use App\Services\ClearanceInitiationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClearanceAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_scan_creates_one_reminder_and_does_not_duplicate_it(): void
    {
        $this->travelTo(now()->startOfMinute());
        Queue::fake();
        $workflow = $this->workflow(now()->addHours(12));
        $automation = app(ClearanceAutomationService::class);

        $this->assertSame(1, $automation->scanAndDispatch());
        $this->assertSame(1, $automation->scanAndDispatch());

        $this->assertDatabaseCount('automation_deliveries', 1);
        $this->assertDatabaseHas('automation_deliveries', [
            'office_task_id' => $workflow['task']->id,
            'automation_type' => AutomationDelivery::TYPE_REMINDER,
        ]);
        Queue::assertPushed(ProcessAutomationDelivery::class, 2);
    }

    public function test_overdue_task_creates_reminder_and_stalled_escalation(): void
    {
        Queue::fake();
        $workflow = $this->workflow(now()->subHour());

        $this->assertSame(2, app(ClearanceAutomationService::class)->scanAndDispatch());

        $this->assertDatabaseHas('automation_deliveries', [
            'office_task_id' => $workflow['task']->id,
            'automation_type' => AutomationDelivery::TYPE_ESCALATION,
            'recipient_role' => 'administrator',
        ]);
    }

    public function test_successful_delivery_records_one_attempt_and_applies_reminder_effect(): void
    {
        $workflow = $this->workflow(now()->addHours(12));
        $delivery = $this->delivery($workflow, AutomationDelivery::TYPE_REMINDER);

        $result = app(AutomationDeliveryService::class)->process($delivery);

        $this->assertSame(AutomationDelivery::STATUS_DELIVERED, $result);
        $this->assertDatabaseHas('automation_attempts', [
            'automation_delivery_id' => $delivery->id,
            'attempt_number' => 1,
            'outcome' => 'delivered',
        ]);
        $this->assertNotNull($workflow['task']->fresh()->last_reminded_at);

        app(AutomationDeliveryService::class)->process($delivery);
        $this->assertDatabaseCount('automation_attempts', 1);
    }

    public function test_transient_failure_recovers_within_bounded_retry_without_duplicate_effect(): void
    {
        $workflow = $this->workflow(now()->addHours(12));
        $delivery = $this->delivery($workflow, AutomationDelivery::TYPE_REMINDER);
        $this->app->instance(NotificationGateway::class, new ControlledGateway(1));
        $service = app(AutomationDeliveryService::class);

        $this->assertSame(AutomationDelivery::STATUS_RETRY_SCHEDULED, $service->process($delivery));
        $this->travel(2)->seconds();
        $this->assertSame(AutomationDelivery::STATUS_DELIVERED, $service->process($delivery));

        $delivery->refresh();
        $this->assertSame(2, $delivery->attempt_count);
        $this->assertSame(AutomationDelivery::STATUS_DELIVERED, $delivery->status);
        $this->assertDatabaseHas('automation_attempts', [
            'automation_delivery_id' => $delivery->id,
            'attempt_number' => 1,
            'outcome' => 'transient_failure',
            'error_code' => 'ADAPTER_TEMPORARILY_UNAVAILABLE',
        ]);
        $this->assertDatabaseCount('human_action_items', 0);
    }

    public function test_repeated_transient_failure_stops_after_three_attempts_and_routes_human_action(): void
    {
        $workflow = $this->workflow(now()->subHour());
        $delivery = $this->delivery($workflow, AutomationDelivery::TYPE_ESCALATION);
        $this->app->instance(NotificationGateway::class, new ControlledGateway(99));
        $service = app(AutomationDeliveryService::class);

        $this->assertSame(AutomationDelivery::STATUS_RETRY_SCHEDULED, $service->process($delivery));
        $this->travel(2)->seconds();
        $this->assertSame(AutomationDelivery::STATUS_RETRY_SCHEDULED, $service->process($delivery));
        $this->travel(3)->seconds();
        $this->assertSame(AutomationDelivery::STATUS_HUMAN_ACTION, $service->process($delivery));

        $delivery->refresh();
        $this->assertSame(3, $delivery->attempt_count);
        $this->assertSame(AutomationDelivery::STATUS_HUMAN_ACTION, $delivery->status);
        $this->assertDatabaseHas('human_action_items', [
            'automation_delivery_id' => $delivery->id,
            'owner_role' => 'administrator',
            'reason_code' => 'ADAPTER_TEMPORARILY_UNAVAILABLE',
            'status' => 'open',
        ]);
    }

    public function test_non_retryable_failure_routes_human_action_immediately(): void
    {
        $workflow = $this->workflow(now()->addHours(12));
        $delivery = $this->delivery($workflow, AutomationDelivery::TYPE_REMINDER);
        $this->app->instance(NotificationGateway::class, new ControlledGateway(1, false));

        $result = app(AutomationDeliveryService::class)->process($delivery);

        $this->assertSame(AutomationDelivery::STATUS_HUMAN_ACTION, $result);
        $this->assertSame(1, $delivery->fresh()->attempt_count);
        $this->assertDatabaseCount('human_action_items', 1);
    }

    public function test_completed_clearance_has_one_completion_notification_record(): void
    {
        $workflow = $this->workflow(now()->addHours(12));
        $workflow['task']->update(['status' => OfficeTask::STATUS_APPROVED, 'approved_at' => now()]);
        $workflow['clearance']->update(['status' => Clearance::STATUS_COMPLETED, 'completed_at' => now()]);
        Queue::fake();

        app(ClearanceAutomationService::class)->scanAndDispatch();
        app(ClearanceAutomationService::class)->scanAndDispatch();

        $this->assertDatabaseCount('automation_deliveries', 1);
        $this->assertDatabaseHas('automation_deliveries', [
            'clearance_id' => $workflow['clearance']->id,
            'automation_type' => AutomationDelivery::TYPE_COMPLETION,
            'recipient_role' => 'student',
        ]);
    }

    public function test_only_admin_can_inspect_failures(): void
    {
        $workflow = $this->workflow(now()->subHour());
        $delivery = $this->delivery($workflow, AutomationDelivery::TYPE_ESCALATION);
        $delivery->update([
            'status' => AutomationDelivery::STATUS_HUMAN_ACTION,
            'attempt_count' => 3,
            'last_error_code' => 'ADAPTER_TEMPORARILY_UNAVAILABLE',
            'retry_allowed' => true,
            'failed_at' => now(),
        ]);
        $delivery->humanActionItem()->create([
            'owner_role' => 'administrator',
            'reason_code' => 'ADAPTER_TEMPORARILY_UNAVAILABLE',
            'status' => 'open',
            'next_step' => 'Inspect the dependency, then use the authorized recovery procedure.',
        ]);

        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_OFFICE]));
        $this->getJson('/api/v1/automation/failures')->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $this->getJson('/api/v1/automation/failures')
            ->assertOk()
            ->assertJsonPath('data.0.id', $delivery->id)
            ->assertJsonPath('data.0.retry_allowed', true)
            ->assertJsonMissingPath('data.0.office_task.student');
    }

    public function test_admin_retry_preserves_history_and_grants_one_new_bounded_cycle(): void
    {
        Queue::fake();
        $workflow = $this->workflow(now()->subHour());
        $delivery = $this->delivery($workflow, AutomationDelivery::TYPE_ESCALATION);
        $delivery->update([
            'status' => AutomationDelivery::STATUS_HUMAN_ACTION,
            'attempt_count' => 3,
            'max_attempts' => 3,
            'last_error_code' => 'ADAPTER_TEMPORARILY_UNAVAILABLE',
            'retry_allowed' => true,
            'failed_at' => now(),
        ]);
        $delivery->humanActionItem()->create([
            'owner_role' => 'administrator',
            'reason_code' => 'ADAPTER_TEMPORARILY_UNAVAILABLE',
            'status' => 'open',
            'next_step' => 'Inspect the dependency, then use the authorized recovery procedure.',
        ]);
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $this->postJson("/api/v1/automation/deliveries/{$delivery->id}/retry")
            ->assertAccepted()
            ->assertJsonPath('data.previous_attempts_preserved', 3)
            ->assertJsonPath('data.new_attempt_limit', 6);

        $delivery->refresh();
        $this->assertSame(AutomationDelivery::STATUS_RETRY_SCHEDULED, $delivery->status);
        $this->assertSame(3, $delivery->attempt_count);
        $this->assertSame(6, $delivery->max_attempts);
        $this->assertDatabaseHas('human_action_items', [
            'automation_delivery_id' => $delivery->id,
            'status' => 'retry_requested',
        ]);
        Queue::assertPushed(ProcessAutomationDelivery::class);
    }

    public function test_admin_cannot_retry_a_non_retryable_failure(): void
    {
        $workflow = $this->workflow(now()->subHour());
        $delivery = $this->delivery($workflow, AutomationDelivery::TYPE_ESCALATION);
        $delivery->update([
            'status' => AutomationDelivery::STATUS_HUMAN_ACTION,
            'attempt_count' => 1,
            'retry_allowed' => false,
            'failed_at' => now(),
        ]);
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $this->postJson("/api/v1/automation/deliveries/{$delivery->id}/retry")
            ->assertConflict()
            ->assertJsonPath('error.code', 'AUTOMATION_RETRY_NOT_ALLOWED');
    }

    private function workflow($dueAt): array
    {
        $term = AcademicTerm::query()->create([
            'code' => '2026-2027-1',
            'name' => 'First Semester 2026-2027',
            'is_active' => true,
        ]);
        $office = Office::query()->create([
            'code' => 'LIBRARY',
            'name' => 'Library',
            'sort_order' => 10,
            'is_active' => true,
        ]);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT]);
        $clearance = app(ClearanceInitiationService::class)->initiate(
            $student,
            $term->code,
            (string) Str::ulid(),
            null,
        );
        $task = $clearance->officeTasks()->firstOrFail();
        $task->update(['due_at' => $dueAt]);

        return compact('student', 'office', 'clearance', 'task');
    }

    private function delivery(array $workflow, string $type): AutomationDelivery
    {
        return AutomationDelivery::query()->create([
            'clearance_id' => $workflow['clearance']->id,
            'office_task_id' => $workflow['task']->id,
            'automation_type' => $type,
            'recipient_role' => $type === AutomationDelivery::TYPE_ESCALATION
                ? 'administrator'
                : 'office_personnel',
            'status' => AutomationDelivery::STATUS_PENDING,
            'idempotency_key' => "test:{$type}:{$workflow['task']->id}",
            'correlation_id' => (string) Str::ulid(),
            'max_attempts' => 3,
        ]);
    }
}

class ControlledGateway implements NotificationGateway
{
    private int $calls = 0;

    public function __construct(
        private readonly int $failuresBeforeSuccess,
        private readonly bool $retryable = true,
    ) {}

    public function send(AutomationDelivery $delivery): void
    {
        $this->calls++;

        if ($this->calls <= $this->failuresBeforeSuccess) {
            throw new NotificationDeliveryException(
                'ADAPTER_TEMPORARILY_UNAVAILABLE',
                $this->retryable,
            );
        }
    }
}
