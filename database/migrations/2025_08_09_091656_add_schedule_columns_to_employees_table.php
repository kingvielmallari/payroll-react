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
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('time_schedule_id')->nullable()->after('pay_schedule');
            $table->unsignedBigInteger('day_schedule_id')->nullable()->after('time_schedule_id');
            
            $table->foreign('time_schedule_id')->references('id')->on('time_schedules')->onDelete('set null');
            $table->foreign('day_schedule_id')->references('id')->on('day_schedules')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['time_schedule_id']);
            $table->dropForeign(['day_schedule_id']);
            $table->dropColumn(['time_schedule_id', 'day_schedule_id']);
        });
    }
};
