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
        Schema::table('cash_advances', function (Blueprint $table) {
            // Remove the old deduction_period field since we now use more specific starting payroll ID
            $table->dropColumn('deduction_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_advances', function (Blueprint $table) {
            // Add back the deduction_period field if needed to rollback
            $table->enum('deduction_period', ['current', 'next'])
                ->default('current')
                ->after('first_deduction_date')
                ->comment('When to start deductions: current or next payroll period');
        });
    }
};
