<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update existing 'suspension' records to 'full_day_suspension'
        DB::statement("UPDATE time_logs SET log_type = 'full_day_suspension' WHERE log_type = 'suspension'");

        // Then alter the enum to include the new suspension types
        DB::statement("ALTER TABLE time_logs MODIFY COLUMN log_type ENUM('regular_workday','rest_day','regular_holiday','special_holiday','rest_day_regular_holiday','rest_day_special_holiday','full_day_suspension','partial_suspension') NOT NULL DEFAULT 'regular_workday'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert new suspension types back to old 'suspension' type
        DB::statement("UPDATE time_logs SET log_type = 'suspension' WHERE log_type IN ('full_day_suspension', 'partial_suspension')");

        // Revert the enum back to original values
        DB::statement("ALTER TABLE time_logs MODIFY COLUMN log_type ENUM('regular_workday','rest_day','regular_holiday','special_holiday','rest_day_regular_holiday','rest_day_special_holiday','suspension') NOT NULL DEFAULT 'regular_workday'");
    }
};
