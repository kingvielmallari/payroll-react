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
        Schema::create('deduction_tax_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // sss, philhealth, pagibig, tax, custom_code
            $table->text('description')->nullable();
            
            // Type Classification
            $table->enum('type', ['government', 'tax', 'loan', 'custom'])->default('custom');
            $table->enum('category', ['mandatory', 'voluntary'])->default('mandatory');
            
            // Calculation Method
            $table->enum('calculation_type', ['percentage', 'fixed_amount', 'bracket'])->nullable();
            $table->decimal('rate_percentage', 8, 4)->nullable(); // For percentage type (e.g., 12.5000 for 12.5%)
            $table->decimal('fixed_amount', 12, 2)->nullable(); // For fixed amount type
            $table->json('bracket_rates')->nullable(); // For bracket calculations (tax brackets)
            
            // Limits and Caps
            $table->decimal('minimum_amount', 12, 2)->nullable();
            $table->decimal('maximum_amount', 12, 2)->nullable();
            $table->decimal('salary_cap', 12, 2)->nullable(); // Maximum salary subject to deduction
            
            // Application Rules
            $table->boolean('apply_to_regular')->default(true);
            $table->boolean('apply_to_overtime')->default(false);
            $table->boolean('apply_to_bonus')->default(false);
            $table->boolean('apply_to_allowances')->default(false);
            
            // Employer Share (for government contributions)
            $table->decimal('employer_share_rate', 8, 4)->nullable();
            $table->decimal('employer_share_fixed', 12, 2)->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system_default')->default(false); // Cannot be deleted
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deduction_tax_settings');
    }
};
