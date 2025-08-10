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
        // Add benefit_eligibility to deduction_tax_settings table
        Schema::table('deduction_tax_settings', function (Blueprint $table) {
            $table->enum('benefit_eligibility', ['both', 'with_benefits', 'without_benefits'])
                  ->default('both')
                  ->after('is_system_default')
                  ->comment('Which employees (by benefit status) this setting applies to');
        });

        // Add benefit_eligibility to allowance_bonus_settings table
        Schema::table('allowance_bonus_settings', function (Blueprint $table) {
            $table->enum('benefit_eligibility', ['both', 'with_benefits', 'without_benefits'])
                  ->default('both')
                  ->after('is_system_default')
                  ->comment('Which employees (by benefit status) this setting applies to');
        });

        // Add benefit_eligibility to paid_leave_settings table
        Schema::table('paid_leave_settings', function (Blueprint $table) {
            $table->enum('benefit_eligibility', ['both', 'with_benefits', 'without_benefits'])
                  ->default('both')
                  ->after('is_system_default')
                  ->comment('Which employees (by benefit status) this setting applies to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deduction_tax_settings', function (Blueprint $table) {
            $table->dropColumn('benefit_eligibility');
        });

        Schema::table('allowance_bonus_settings', function (Blueprint $table) {
            $table->dropColumn('benefit_eligibility');
        });

        Schema::table('paid_leave_settings', function (Blueprint $table) {
            $table->dropColumn('benefit_eligibility');
        });
    }
};
