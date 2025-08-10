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
        Schema::create('allowance_bonus_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // transportation, meal, clothing, performance_bonus, etc.
            $table->text('description')->nullable();
            
            // Type Classification
            $table->enum('type', ['allowance', 'bonus', 'benefit'])->default('allowance');
            $table->enum('category', ['regular', 'conditional', 'one_time'])->default('regular');
            
            // Calculation Method
            $table->enum('calculation_type', ['percentage', 'fixed_amount', 'daily_rate_multiplier']);
            $table->decimal('rate_percentage', 8, 4)->nullable(); // For percentage of basic salary
            $table->decimal('fixed_amount', 12, 2)->nullable(); // For fixed amount
            $table->decimal('multiplier', 8, 4)->nullable(); // For daily rate multiplier
            
            // Application Rules
            $table->boolean('is_taxable')->default(true);
            $table->boolean('apply_to_regular_days')->default(true);
            $table->boolean('apply_to_overtime')->default(false);
            $table->boolean('apply_to_holidays')->default(true);
            $table->boolean('apply_to_rest_days')->default(true);
            
            // Frequency and Conditions
            $table->enum('frequency', ['daily', 'per_payroll', 'monthly', 'quarterly', 'annually'])->default('per_payroll');
            $table->json('conditions')->nullable(); // Store conditions for conditional allowances/bonuses
            
            // Limits
            $table->decimal('minimum_amount', 12, 2)->nullable();
            $table->decimal('maximum_amount', 12, 2)->nullable();
            $table->integer('max_days_per_period')->nullable(); // For daily allowances
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system_default')->default(false);
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allowance_bonus_settings');
    }
};
