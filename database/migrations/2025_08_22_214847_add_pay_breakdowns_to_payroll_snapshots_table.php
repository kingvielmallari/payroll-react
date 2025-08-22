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
        Schema::table('payroll_snapshots', function (Blueprint $table) {
            $table->json('basic_breakdown')->nullable()->after('night_differential_pay');
            $table->json('holiday_breakdown')->nullable()->after('basic_breakdown');
            $table->json('rest_breakdown')->nullable()->after('holiday_breakdown');
            $table->json('overtime_breakdown')->nullable()->after('rest_breakdown');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_snapshots', function (Blueprint $table) {
            $table->dropColumn(['basic_breakdown', 'holiday_breakdown', 'rest_breakdown', 'overtime_breakdown']);
        });
    }
};
