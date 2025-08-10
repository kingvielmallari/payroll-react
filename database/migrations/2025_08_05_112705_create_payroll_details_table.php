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
        Schema::create('payroll_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            
            // Basic Salary Computations
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('daily_rate', 8, 2)->default(0);
            $table->decimal('hourly_rate', 8, 2)->default(0);
            $table->integer('days_worked')->default(0);
            $table->decimal('regular_hours', 8, 2)->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('holiday_hours', 8, 2)->default(0);
            $table->decimal('night_differential_hours', 8, 2)->default(0);
            
            // Earnings
            $table->decimal('regular_pay', 12, 2)->default(0);
            $table->decimal('overtime_pay', 12, 2)->default(0);
            $table->decimal('holiday_pay', 12, 2)->default(0);
            $table->decimal('night_differential_pay', 12, 2)->default(0);
            $table->decimal('allowances', 12, 2)->default(0);
            $table->decimal('bonuses', 12, 2)->default(0);
            $table->decimal('other_earnings', 12, 2)->default(0);
            $table->decimal('gross_pay', 12, 2)->default(0);
            
            // Deductions
            $table->decimal('sss_contribution', 8, 2)->default(0);
            $table->decimal('philhealth_contribution', 8, 2)->default(0);
            $table->decimal('pagibig_contribution', 8, 2)->default(0);
            $table->decimal('withholding_tax', 8, 2)->default(0);
            $table->decimal('late_deductions', 8, 2)->default(0);
            $table->decimal('undertime_deductions', 8, 2)->default(0);
            $table->decimal('loan_deductions', 8, 2)->default(0);
            $table->decimal('other_deductions', 8, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            
            // Net Pay
            $table->decimal('net_pay', 12, 2)->default(0);
            
            // Additional Info
            $table->text('remarks')->nullable();
            $table->json('deduction_breakdown')->nullable(); // For detailed deduction info
            $table->json('earnings_breakdown')->nullable(); // For detailed earnings info
            
            $table->timestamps();

            $table->unique(['payroll_id', 'employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_details');
    }
};
