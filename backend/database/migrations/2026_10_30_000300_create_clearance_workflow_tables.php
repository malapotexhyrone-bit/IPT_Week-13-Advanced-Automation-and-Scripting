<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clearances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('academic_term_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('in_progress');
            $table->string('correlation_id', 26)->unique();
            $table->char('idempotency_key_hash', 64)->nullable();
            $table->timestamp('initiated_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'academic_term_id']);
            $table->unique(['student_id', 'idempotency_key_hash']);
        });

        Schema::create('office_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clearance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('office_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('pending');
            $table->timestamp('due_at');
            $table->timestamp('last_reminded_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['clearance_id', 'office_id']);
            $table->index(['status', 'due_at']);
        });

        Schema::create('clearance_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clearance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('office_task_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 60);
            $table->string('correlation_id', 26)->index();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clearance_events');
        Schema::dropIfExists('office_tasks');
        Schema::dropIfExists('clearances');
    }
};
