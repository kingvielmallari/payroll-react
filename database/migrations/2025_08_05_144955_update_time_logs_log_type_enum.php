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
        Schema::table('time_logs', function (Blueprint $table) {
            // Drop the existing enum column
            $table->dropColumn('log_type');
        });

        Schema::table('time_logs', function (Blueprint $table) {
            // Add the column back with updated enum values
            $table->enum('log_type', ['regular', 'overtime', 'holiday', 'rest_day', 'manual', 'biometric', 'imported'])->default('regular')->after('undertime_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_logs', function (Blueprint $table) {
            $table->dropColumn('log_type');
        });

        Schema::table('time_logs', function (Blueprint $table) {
            $table->enum('log_type', ['manual', 'biometric', 'imported'])->default('manual')->after('undertime_hours');
        });
    }
};
