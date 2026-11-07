<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secure_exchange_receipts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('message_id')->unique();
            $table->char('envelope_hash', 64);
            $table->string('sender', 80);
            $table->string('recipient', 80);
            $table->string('clearance_reference', 40);
            $table->string('overall_status', 40);
            $table->dateTime('issued_at');
            $table->dateTime('expires_at');
            $table->dateTime('received_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secure_exchange_receipts');
    }
};
