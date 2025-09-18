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
        // Update allowance_bonus_settings - first update existing data, then change enum
        // Update existing per_payroll records to monthly
        DB::table('allowance_bonus_settings')
            ->where('frequency', 'per_payroll')
            ->update(['frequency' => 'monthly']);

        // Update distribution_method for existing records that have 'all_payrolls'
        DB::table('allowance_bonus_settings')
            ->where('distribution_method', 'all_payrolls')
            ->update(['distribution_method' => 'first_payroll']);

        Schema::table('allowance_bonus_settings', function (Blueprint $table) {
            // Update frequency enum to only allow monthly, quarterly, annually
            $table->enum('frequency', ['monthly', 'quarterly', 'annually'])->default('monthly')->change();

            // Update distribution_method options (remove all_payrolls)
            $table->enum('distribution_method', ['first_payroll', 'last_payroll', 'equally_distributed'])->default('first_payroll')->change();
        });

        // Update deduction_tax_settings to standardize frequency and distribution
        // Update existing data first if the column exists
        if (Schema::hasColumn('deduction_tax_settings', 'distribution_method')) {
            DB::table('deduction_tax_settings')
                ->where('distribution_method', 'distribute_equally')
                ->update(['distribution_method' => 'equally_distributed']);
        }

        Schema::table('deduction_tax_settings', function (Blueprint $table) {
            // Remove old fields first to avoid conflicts
            if (Schema::hasColumn('deduction_tax_settings', 'pay_frequency')) {
                $table->dropColumn('pay_frequency');
            }
            if (Schema::hasColumn('deduction_tax_settings', 'deduction_frequency')) {
                $table->dropColumn('deduction_frequency');
            }
            if (Schema::hasColumn('deduction_tax_settings', 'semi_monthly_period')) {
                $table->dropColumn('semi_monthly_period');
            }
            if (Schema::hasColumn('deduction_tax_settings', 'frequency_notes')) {
                $table->dropColumn('frequency_notes');
            }
            if (Schema::hasColumn('deduction_tax_settings', 'deduct_on_monthly_payroll')) {
                $table->dropColumn('deduct_on_monthly_payroll');
            }

            // Add frequency field with standardized options
            if (!Schema::hasColumn('deduction_tax_settings', 'frequency')) {
                $table->enum('frequency', ['monthly', 'quarterly', 'annually'])->default('monthly')->after('tax_table_type');
            } else {
                $table->enum('frequency', ['monthly', 'quarterly', 'annually'])->default('monthly')->change();
            }

            // Update distribution_method to match standard options
            if (Schema::hasColumn('deduction_tax_settings', 'distribution_method')) {
                $table->enum('distribution_method', ['first_payroll', 'last_payroll', 'equally_distributed'])->default('first_payroll')->change();
            } else {
                $table->enum('distribution_method', ['first_payroll', 'last_payroll', 'equally_distributed'])->default('first_payroll')->after('frequency');
            }
        });

        // Update deduction_settings table if it exists
        if (Schema::hasTable('deduction_settings')) {
            Schema::table('deduction_settings', function (Blueprint $table) {
                // Add frequency field
                if (!Schema::hasColumn('deduction_settings', 'frequency')) {
                    $table->enum('frequency', ['monthly', 'quarterly', 'annually'])->default('monthly')->after('calculation_type');
                }

                // Add distribution_method field
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
            $table->enum('distribution_method', ['all_payrolls', 'first_payroll', 'last_payroll', 'equally_distributed'])->default('all_payrolls')->change();
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
