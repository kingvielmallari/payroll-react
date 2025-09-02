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
        Schema::table('philhealth_tax_table', function (Blueprint $table) {
            // Drop existing columns that we don't need
            $table->dropColumn(['min_salary', 'max_salary', 'ee_percentage', 'er_percentage', 'effective_date', 'description']);

            // Add new columns according to requirements
            $table->decimal('range_start', 15, 2)->after('id');
            $table->decimal('range_end', 15, 2)->nullable()->after('range_start');
            $table->decimal('employee_share', 5, 2)->after('range_end'); // Employee percentage
            $table->decimal('employer_share', 5, 2)->after('employee_share'); // Employer percentage
            $table->decimal('total_contribution', 5, 2)->after('employer_share'); // Total percentage
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('philhealth_tax_table', function (Blueprint $table) {
            // Restore original columns
            $table->dropColumn(['range_start', 'range_end', 'employee_share', 'employer_share', 'total_contribution']);

            $table->decimal('min_salary', 15, 2);
            $table->decimal('max_salary', 15, 2)->nullable();
            $table->decimal('ee_percentage', 5, 2);
            $table->decimal('er_percentage', 5, 2);
            $table->date('effective_date');
            $table->text('description')->nullable();
        });
    }
};
