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
        Schema::create('machine_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uid')->unique();
            $table->unsignedBigInteger('attendance_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->enum('type', ['checkin', 'checkout'])->comment('Type of attendance: checkin or checkout');
            $table->timestamp('datetime')->comment('Date and time of attendance');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machine_attendances');
    }
};
