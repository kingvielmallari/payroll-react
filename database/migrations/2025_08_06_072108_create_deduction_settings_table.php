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
        Schema::create('deduction_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "SSS", "PhilHealth", "Pag-IBIG", "BIR Withholding Tax"
            $table->string('code')->unique(); // e.g., "sss", "philhealth", "pagibig", "bir"
            $table->enum('type', ['government', 'custom']);
            $table->enum('calculation_type', ['percentage', 'fixed', 'tiered', 'table_based']);
            $table->decimal('rate', 8, 4)->nullable(); // For percentage-based deductions
            $table->decimal('fixed_amount', 10, 2)->nullable(); // For fixed amount deductions
            $table->decimal('minimum_amount', 10, 2)->nullable();
            $table->decimal('maximum_amount', 10, 2)->nullable();
            $table->decimal('salary_threshold', 10, 2)->nullable(); // Minimum salary for deduction
            $table->json('rate_table')->nullable(); // For tiered or table-based calculations
            $table->boolean('is_mandatory')->default(false); // Required for all employees
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->text('formula_notes')->nullable(); // Documentation of how to calculate
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deduction_settings');
    }
};
