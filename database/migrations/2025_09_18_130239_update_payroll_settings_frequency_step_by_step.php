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
        // Step 1: Add distribution method and update frequency for allowance_bonus_settings
        Schema::table('allowance_bonus_settings', function (Blueprint $table) {
            // Add distribution method first
            if (!Schema::hasColumn('allowance_bonus_settings', 'distribution_method')) {
                $table->enum('distribution_method', ['first_payroll', 'last_payroll', 'equally_distributed'])->default('first_payroll')->after('frequency');
            }
        });

        // Step 2: Update frequency for allowance_bonus_settings (remove per_payroll)
        Schema::table('allowance_bonus_settings', function (Blueprint $table) {
            $table->enum('frequency', ['monthly', 'quarterly', 'annually'])->default('monthly')->change();
        });

        // Step 3: Add frequency and distribution method to deduction_tax_settings
        Schema::table('deduction_tax_settings', function (Blueprint $table) {
            // Add frequency field
            if (!Schema::hasColumn('deduction_tax_settings', 'frequency')) {
                $table->enum('frequency', ['monthly', 'quarterly', 'annually'])->default('monthly')->after('tax_table_type');
            }

            // Add distribution_method field
            if (!Schema::hasColumn('deduction_tax_settings', 'distribution_method')) {
                $table->enum('distribution_method', ['first_payroll', 'last_payroll', 'equally_distributed'])->default('first_payroll')->after('frequency');
            }
        });

        // Step 4: Clean up old deduction fields if they exist
        Schema::table('deduction_tax_settings', function (Blueprint $table) {
            $columnsToRemove = ['pay_frequency', 'deduction_frequency', 'semi_monthly_period', 'frequency_notes', 'deduct_on_monthly_payroll'];

            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('deduction_tax_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Step 5: Add frequency and distribution method to deduction_settings if it exists
        if (Schema::hasTable('deduction_settings')) {
            Schema::table('deduction_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('deduction_settings', 'frequency')) {
                    $table->enum('frequency', ['monthly', 'quarterly', 'annually'])->default('monthly')->after('calculation_type');
                }

                if (!Schema::hasColumn('deduction_settings', 'distribution_method')) {
                    $table->enum('distribution_method', ['first_payroll', 'last_payroll', 'equally_distributed'])->default('first_payroll')->after('frequency');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert allowance_bonus_settings
        Schema::table('allowance_bonus_settings', function (Blueprint $table) {
            $table->enum('frequency', ['per_payroll', 'monthly', 'quarterly', 'annually'])->default('per_payroll')->change();

            if (Schema::hasColumn('allowance_bonus_settings', 'distribution_method')) {
                $table->dropColumn('distribution_method');
            }
        });

        // Revert deduction_tax_settings
        Schema::table('deduction_tax_settings', function (Blueprint $table) {
            if (Schema::hasColumn('deduction_tax_settings', 'frequency')) {
                $table->dropColumn('frequency');
            }
            if (Schema::hasColumn('deduction_tax_settings', 'distribution_method')) {
                $table->dropColumn('distribution_method');
            }
        });

        // Revert deduction_settings
        if (Schema::hasTable('deduction_settings')) {
            Schema::table('deduction_settings', function (Blueprint $table) {
                if (Schema::hasColumn('deduction_settings', 'frequency')) {
                    $table->dropColumn('frequency');
                }
                if (Schema::hasColumn('deduction_settings', 'distribution_method')) {
                    $table->dropColumn('distribution_method');
                }
            });
        }
    }
};
