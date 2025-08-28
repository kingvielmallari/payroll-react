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
            // Add deduction frequency field
            $table->enum('deduction_frequency', ['per_payroll', 'monthly'])
                ->default('per_payroll')
                ->after('deduction_period')
                ->comment('How often deductions occur: per_payroll (every pay period) or monthly');

            // Add monthly deduction timing for weekly/semi-monthly employees
            $table->enum('monthly_deduction_timing', ['first_payroll', 'last_payroll'])
                ->nullable()
                ->after('deduction_frequency')
                ->comment('For monthly deductions: when to deduct (first or last payroll of month)');

            // Add number of months for monthly installments
            $table->integer('monthly_installments')->nullable()->after('installments')
                ->comment('Number of months for monthly deductions (when deduction_frequency is monthly)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_advances', function (Blueprint $table) {
            $table->dropColumn(['deduction_frequency', 'monthly_deduction_timing', 'monthly_installments']);
        });
    }
};
