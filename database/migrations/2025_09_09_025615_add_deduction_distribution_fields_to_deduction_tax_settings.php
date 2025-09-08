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
        Schema::table('deduction_tax_settings', function (Blueprint $table) {
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('deduction_tax_settings', 'deduct_on_monthly_payroll')) {
                $table->boolean('deduct_on_monthly_payroll')->default(true)->after('deduction_frequency');
            }
            if (!Schema::hasColumn('deduction_tax_settings', 'distribution_method')) {
                $table->enum('distribution_method', ['first_payroll', 'last_payroll', 'distribute_equally'])->nullable()->after('deduct_on_monthly_payroll');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deduction_tax_settings', function (Blueprint $table) {
            if (Schema::hasColumn('deduction_tax_settings', 'deduct_on_monthly_payroll')) {
                $table->dropColumn('deduct_on_monthly_payroll');
            }
            if (Schema::hasColumn('deduction_tax_settings', 'distribution_method')) {
                $table->dropColumn('distribution_method');
            }
        });
    }
};
