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
        // Since there are no existing records, we can directly update the column
        // Drop the existing enum column
        Schema::table('time_logs', function (Blueprint $table) {
            $table->dropColumn('log_type');
        });

        // Add the column back with new enum values that match PayrollRateConfiguration type_names
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to old enum values
        Schema::table('time_logs', function (Blueprint $table) {
            $table->dropColumn('log_type');
        });

        Schema::table('time_logs', function (Blueprint $table) {
            $table->enum('log_type', ['regular', 'overtime', 'holiday', 'rest_day', 'manual', 'biometric', 'imported'])->default('regular')->after('undertime_hours');
        });
    }
};
