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
        // Add 'per_payroll' back to allowance_bonus_settings frequency enum
        Schema::table('allowance_bonus_settings', function (Blueprint $table) {
            $table->enum('frequency', ['per_payroll', 'monthly', 'quarterly', 'annually'])->default('per_payroll')->change();
        });

        // Add 'per_payroll' to deduction_tax_settings frequency enum
        Schema::table('deduction_tax_settings', function (Blueprint $table) {
            $table->enum('frequency', ['per_payroll', 'monthly', 'quarterly', 'annually'])->default('per_payroll')->change();
        });

        // Update deduction_settings table if it exists
        if (Schema::hasTable('deduction_settings')) {
            Schema::table('deduction_settings', function (Blueprint $table) {
                if (Schema::hasColumn('deduction_settings', 'frequency')) {
                    $table->enum('frequency', ['per_payroll', 'monthly', 'quarterly', 'annually'])->default('per_payroll')->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'per_payroll' from allowance_bonus_settings frequency enum
        Schema::table('allowance_bonus_settings', function (Blueprint $table) {
            $table->enum('frequency', ['monthly', 'quarterly', 'annually'])->default('monthly')->change();
        });

        // Remove 'per_payroll' from deduction_tax_settings frequency enum
        Schema::table('deduction_tax_settings', function (Blueprint $table) {
            $table->enum('frequency', ['monthly', 'quarterly', 'annually'])->default('monthly')->change();
        });

        // Update deduction_settings table if it exists
        if (Schema::hasTable('deduction_settings')) {
            Schema::table('deduction_settings', function (Blueprint $table) {
                if (Schema::hasColumn('deduction_settings', 'frequency')) {
                    $table->enum('frequency', ['monthly', 'quarterly', 'annually'])->default('monthly')->change();
                }
            });
        }
    }
};
