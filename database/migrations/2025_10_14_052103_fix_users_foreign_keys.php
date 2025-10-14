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
            // Drop old columns
            $table->dropColumn('supervisor_id');
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });

        Schema::table('users', function (Blueprint $table) {
            // Add corrected columns
            $table->foreignId('department_id')->nullable()->after('employee_id')->constrained('departments')->onDelete('set null');
            $table->foreignId('supervisor_id')->nullable()->after('email')->constrained('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
