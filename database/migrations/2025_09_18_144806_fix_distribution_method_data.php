<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix any invalid distribution_method values in deduction_tax_settings
        // Replace any invalid values with 'first_payroll'
        DB::statement("UPDATE deduction_tax_settings SET distribution_method = 'first_payroll' WHERE distribution_method NOT IN ('first_payroll', 'last_payroll', 'equally_distributed')");

        // Fix any invalid distribution_method values in allowance_bonus_settings if they exist
        if (Schema::hasTable('allowance_bonus_settings') && Schema::hasColumn('allowance_bonus_settings', 'distribution_method')) {
            DB::statement("UPDATE allowance_bonus_settings SET distribution_method = 'first_payroll' WHERE distribution_method NOT IN ('first_payroll', 'last_payroll', 'equally_distributed')");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse data cleanup
    }
};
