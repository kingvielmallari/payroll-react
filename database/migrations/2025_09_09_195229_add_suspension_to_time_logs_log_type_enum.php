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
        // Drop the existing enum column
        Schema::table('time_logs', function (Blueprint $table) {
            $table->dropColumn('log_type');
        });

        // Add the column back with updated enum values including suspension
        Schema::table('time_logs', function (Blueprint $table) {
            $table->enum('log_type', [
                'regular_workday',
                'rest_day',
                'regular_holiday',
                'special_holiday',
                'rest_day_regular_holiday',
                'rest_day_special_holiday',
                'suspension'  // Add suspension to enum
            ])->default('regular_workday')->after('undertime_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to the previous enum values without suspension
        Schema::table('time_logs', function (Blueprint $table) {
            $table->dropColumn('log_type');
        });

        Schema::table('time_logs', function (Blueprint $table) {
            $table->enum('log_type', [
                'regular_workday',
                'rest_day',
                'regular_holiday',
                'special_holiday',
                'rest_day_regular_holiday',
                'rest_day_special_holiday'
            ])->default('regular_workday')->after('undertime_hours');
        });
    }
};
