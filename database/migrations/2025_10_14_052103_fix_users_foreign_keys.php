<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Check if the column exists and drop it safely
            $table->dropColumn(['supervisor_id', 'department_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            // Add properly defined foreign keys
            $table->foreignId('department_id')
                ->nullable()
                ->after('employee_id')
                ->constrained('departments')
                ->onDelete('set null');
            
            $table->foreignId('supervisor_id')
                ->nullable()
                ->after('email')
                ->constrained('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id', 'supervisor_id']);
            $table->dropColumn(['department_id', 'supervisor_id']);
        });
    }
};