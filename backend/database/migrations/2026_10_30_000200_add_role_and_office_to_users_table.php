<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('student')->after('email');
            $table->foreignId('office_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->index(['role', 'office_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'office_id']);
            $table->dropConstrainedForeignId('office_id');
            $table->dropColumn('role');
        });
    }
};
