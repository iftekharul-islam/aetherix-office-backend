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
        Schema::table('departments', function (Blueprint $table) {
         
            $table->decimal('expected_duty_hours', 4, 2)->default(9.00)->after('office_start_time');
            $table->integer('on_time_threshold_minutes')->default(1)->after('expected_duty_hours')->comment('Minutes after office start time to be considered on time');
            $table->integer('delay_threshold_minutes')->default(5)->after('on_time_threshold_minutes')->comment('Minutes after office start time to be considered delayed');
            $table->integer('extreme_delay_threshold_minutes')->default(15)->after('delay_threshold_minutes')->comment('Minutes after office start time to be considered extremely delayed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn([
               
                'expected_duty_hours',
                'on_time_threshold_minutes',
                'delay_threshold_minutes',
                'extreme_delay_threshold_minutes'
            ]);
        });
    }
};