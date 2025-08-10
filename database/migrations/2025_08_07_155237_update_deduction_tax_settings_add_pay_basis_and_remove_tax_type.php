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
        Schema::table('deduction_tax_settings', function (Blueprint $table) {
            // Add pay basis fields
            $table->boolean('apply_to_basic_pay')->default(true)->after('apply_to_allowances');
            $table->boolean('apply_to_gross_pay')->default(false)->after('apply_to_basic_pay');
            $table->boolean('apply_to_taxable_income')->default(false)->after('apply_to_gross_pay');
            $table->boolean('apply_to_net_pay')->default(false)->after('apply_to_taxable_income');
            
            // Add tax table type for government deductions (SSS, PhilHealth, Pag-IBIG, Withholding Tax)
            $table->enum('tax_table_type', ['sss', 'philhealth', 'pagibig', 'withholding_tax'])->nullable()->after('calculation_type');
        });

        // Update existing 'tax' type records to 'government' type
        DB::table('deduction_tax_settings')
            ->where('type', 'tax')
            ->update(['type' => 'government']);

        // Update the enum to remove 'tax' type
        Schema::table('deduction_tax_settings', function (Blueprint $table) {
            $table->enum('type', ['government', 'loan', 'custom'])->default('custom')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deduction_tax_settings', function (Blueprint $table) {
            // Restore the original enum with 'tax' type
            $table->enum('type', ['government', 'tax', 'loan', 'custom'])->default('custom')->change();
        });

        // Restore 'government' records that were originally 'tax' (this is approximate)
        DB::table('deduction_tax_settings')
            ->where('type', 'government')
            ->where('code', 'like', 'withholding%')
            ->update(['type' => 'tax']);

        Schema::table('deduction_tax_settings', function (Blueprint $table) {
            // Remove the new fields
            $table->dropColumn([
                'apply_to_basic_pay',
                'apply_to_gross_pay', 
                'apply_to_taxable_income',
                'apply_to_net_pay',
                'tax_table_type'
            ]);
        });
    }
};
