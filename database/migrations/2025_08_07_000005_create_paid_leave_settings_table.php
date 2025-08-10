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
        Schema::create('paid_leave_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Vacation Leave, Sick Leave, Maternity Leave, etc.
            $table->string('code')->unique(); // VL, SL, ML, PL, etc.
            $table->text('description')->nullable();
            
            // Leave Entitlement
            $table->integer('days_per_year')->default(0); // Annual entitlement
            $table->enum('accrual_method', ['yearly', 'monthly', 'per_payroll'])->default('yearly');
            $table->decimal('accrual_rate', 8, 4)->default(0); // Rate per period for accrual
            
            // Earning Requirements
            $table->integer('minimum_service_months')->default(0); // Months before eligible
            $table->boolean('prorated_first_year')->default(true); // Prorated in first year of employment
            
            // Usage Rules
            $table->integer('minimum_days_usage')->default(1); // Minimum days that can be taken
            $table->integer('maximum_days_usage')->default(0); // Maximum days that can be taken at once (0 = no limit)
            $table->integer('notice_days_required')->default(0); // Days advance notice required
            
            // Carry Over and Expiry
            $table->boolean('can_carry_over')->default(false);
            $table->integer('max_carry_over_days')->default(0);
            $table->boolean('expires_annually')->default(true);
            $table->integer('expiry_month')->default(12); // Month when leave expires (1-12)
            
            // Conversion and Cash Out
            $table->boolean('can_convert_to_cash')->default(false);
            $table->decimal('cash_conversion_rate', 8, 4)->default(1.0000); // Multiplier for cash conversion
            $table->integer('max_convertible_days')->default(0);
            
            // Gender and Status Restrictions
            $table->json('applicable_gender')->nullable(); // ['male', 'female'] or null for all
            $table->json('applicable_employment_types')->nullable(); // Employment types this applies to
            $table->json('applicable_employment_status')->nullable(); // Employment status this applies to
            
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
        Schema::dropIfExists('paid_leave_settings');
    }
};
