<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deficiencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reported_by')->constrained('users')->restrictOnDelete();
            $table->text('description');
            $table->string('status', 30)->default('open');
            $table->text('resolution_note')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['office_task_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deficiencies');
    }
};
