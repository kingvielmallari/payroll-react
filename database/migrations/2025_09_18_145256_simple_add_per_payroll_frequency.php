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
        // Add 'per_payroll' to allowance_bonus_settings frequency enum
        DB::statement("ALTER TABLE allowance_bonus_settings MODIFY frequency ENUM('per_payroll', 'monthly', 'quarterly', 'annually') DEFAULT 'per_payroll'");

        // Add 'per_payroll' to deduction_tax_settings frequency enum if the table has frequency column
        if (Schema::hasColumn('deduction_tax_settings', 'frequency')) {
            DB::statement("ALTER TABLE deduction_tax_settings MODIFY frequency ENUM('per_payroll', 'monthly', 'quarterly', 'annually') DEFAULT 'per_payroll'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'per_payroll' from allowance_bonus_settings frequency enum
        DB::statement("ALTER TABLE allowance_bonus_settings MODIFY frequency ENUM('monthly', 'quarterly', 'annually') DEFAULT 'monthly'");

        // Remove 'per_payroll' from deduction_tax_settings frequency enum if column exists
        if (Schema::hasColumn('deduction_tax_settings', 'frequency')) {
            DB::statement("ALTER TABLE deduction_tax_settings MODIFY frequency ENUM('monthly', 'quarterly', 'annually') DEFAULT 'monthly'");
        }
    }
};
