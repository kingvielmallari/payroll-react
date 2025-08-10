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
        // Create payroll_snapshots table to store locked calculation data
        Schema::create('payroll_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            
            // Employee info snapshot
            $table->string('employee_number');
            $table->string('employee_name');
            $table->string('department');
            $table->string('position');
            
            // Basic info
            $table->decimal('basic_salary', 12, 2);
            $table->decimal('daily_rate', 12, 2);
            $table->decimal('hourly_rate', 12, 2);
            
            // Time tracking
            $table->decimal('days_worked', 8, 2)->default(0);
            $table->decimal('regular_hours', 8, 2)->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('holiday_hours', 8, 2)->default(0);
            $table->decimal('night_differential_hours', 8, 2)->default(0);
            
            // Earnings calculations
            $table->decimal('regular_pay', 12, 2)->default(0);
            $table->decimal('overtime_pay', 12, 2)->default(0);
            $table->decimal('holiday_pay', 12, 2)->default(0);
            $table->decimal('night_differential_pay', 12, 2)->default(0);
            
            // Allowances and bonuses (detailed JSON)
            $table->json('allowances_breakdown')->nullable(); // Detailed allowance calculations
            $table->decimal('allowances_total', 12, 2)->default(0);
            $table->json('bonuses_breakdown')->nullable(); // Detailed bonus calculations  
            $table->decimal('bonuses_total', 12, 2)->default(0);
            $table->decimal('other_earnings', 12, 2)->default(0);
            
            // Gross pay
            $table->decimal('gross_pay', 12, 2)->default(0);
            
            // Deductions (detailed JSON)
            $table->json('deductions_breakdown')->nullable(); // Detailed deduction calculations
            $table->decimal('sss_contribution', 12, 2)->default(0);
            $table->decimal('philhealth_contribution', 12, 2)->default(0);
            $table->decimal('pagibig_contribution', 12, 2)->default(0);
            $table->decimal('withholding_tax', 12, 2)->default(0);
            $table->decimal('late_deductions', 12, 2)->default(0);
            $table->decimal('undertime_deductions', 12, 2)->default(0);
            $table->decimal('cash_advance_deductions', 12, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            
            // Net pay
            $table->decimal('net_pay', 12, 2)->default(0);
            
            // Settings snapshot (to track what settings were used)
            $table->json('settings_snapshot')->nullable(); // Settings used for this calculation
            
            $table->text('remarks')->nullable();
            $table->timestamps();
            
            // Ensure one snapshot per employee per payroll
            $table->unique(['payroll_id', 'employee_id']);
        });
        
        // Add dynamic calculation fields to payroll_details
        Schema::table('payroll_details', function (Blueprint $table) {
            // Keep only core time tracking data that doesn't change based on settings
            $table->json('time_logs_reference')->nullable()->after('remarks'); // Reference to time logs used
            $table->boolean('is_finalized')->default(false)->after('time_logs_reference'); // Flag to indicate if calculations are locked
        });
        
        // Update payrolls table for better status tracking
        Schema::table('payrolls', function (Blueprint $table) {
            // Add more detailed status tracking
            $table->timestamp('submitted_at')->nullable()->after('approved_at');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->onDelete('set null')->after('submitted_at');
            $table->timestamp('voided_at')->nullable()->after('submitted_by');
            $table->foreignId('voided_by')->nullable()->constrained('users')->onDelete('set null')->after('voided_at');
            $table->text('void_reason')->nullable()->after('voided_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove added columns from payrolls
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropForeign(['submitted_by']);
            $table->dropForeign(['voided_by']);
            $table->dropColumn([
                'submitted_at',
                'submitted_by', 
                'voided_at',
                'voided_by',
                'void_reason'
            ]);
        });
        
        // Remove added columns from payroll_details
        Schema::table('payroll_details', function (Blueprint $table) {
            $table->dropColumn([
                'time_logs_reference',
                'is_finalized'
            ]);
        });
        
        // Drop payroll_snapshots table
        Schema::dropIfExists('payroll_snapshots');
    }
};
