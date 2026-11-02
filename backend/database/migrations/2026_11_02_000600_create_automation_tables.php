<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clearance_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('office_task_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('automation_type', 40);
            $table->string('recipient_role', 30);
            $table->string('status', 30)->default('pending');
            $table->string('idempotency_key', 160)->unique();
            $table->string('correlation_id', 26)->index();
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->boolean('retry_allowed')->default(false);
            $table->timestamp('next_attempt_at')->nullable();
            $table->string('last_error_code', 60)->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'next_attempt_at']);
        });

        Schema::create('automation_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_delivery_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('attempt_number');
            $table->string('outcome', 30);
            $table->string('error_code', 60)->nullable();
            $table->string('correlation_id', 26)->index();
            $table->timestamp('attempted_at');
            $table->timestamps();

            $table->unique(['automation_delivery_id', 'attempt_number']);
        });

        Schema::create('human_action_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_delivery_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('owner_role', 30);
            $table->string('reason_code', 60);
            $table->string('status', 20)->default('open');
            $table->string('next_step', 160);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('human_action_items');
        Schema::dropIfExists('automation_attempts');
        Schema::dropIfExists('automation_deliveries');
    }
};
