<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DeductionTaxSetting;

class GovernmentDeductionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // SSS Contribution
        DeductionTaxSetting::updateOrCreate(
            ['code' => 'sss'],
            [
                'name' => 'SSS Contribution',
                'description' => 'Social Security System contribution based on salary brackets',
                'type' => 'government',
                'category' => 'mandatory',
                'calculation_type' => 'bracket',
                'tax_table_type' => 'sss',
                'apply_to_basic_pay' => true,
                'apply_to_gross_pay' => false,
                'apply_to_taxable_income' => false,
                'apply_to_net_pay' => false,
                'apply_to_regular' => true,
                'apply_to_overtime' => false,
                'apply_to_bonus' => false,
                'apply_to_allowances' => false,
                'employer_share_rate' => null,
                'employer_share_fixed' => null,
                'is_active' => true,
                'is_system_default' => true,
                'sort_order' => 1,
            ]
        );

        // PhilHealth Contribution  
        DeductionTaxSetting::updateOrCreate(
            ['code' => 'philhealth'],
            [
                'name' => 'PhilHealth Contribution',
                'description' => 'Philippine Health Insurance Corporation premium based on salary',
                'type' => 'government',
                'category' => 'mandatory',
                'calculation_type' => 'bracket',
                'tax_table_type' => 'philhealth',
                'apply_to_basic_pay' => true,
                'apply_to_gross_pay' => false,
                'apply_to_taxable_income' => false,
                'apply_to_net_pay' => false,
                'apply_to_regular' => true,
                'apply_to_overtime' => false,
                'apply_to_bonus' => false,
                'apply_to_allowances' => false,
                'employer_share_rate' => null,
                'employer_share_fixed' => null,
                'is_active' => true,
                'is_system_default' => true,
                'sort_order' => 2,
            ]
        );

        // Pag-IBIG Contribution
        DeductionTaxSetting::updateOrCreate(
            ['code' => 'pagibig'],
            [
                'name' => 'Pag-IBIG Contribution',
                'description' => 'Home Development Mutual Fund contribution',
                'type' => 'government',
                'category' => 'mandatory',
                'calculation_type' => 'bracket',
                'tax_table_type' => 'pagibig',
                'apply_to_basic_pay' => true,
                'apply_to_gross_pay' => false,
                'apply_to_taxable_income' => false,
                'apply_to_net_pay' => false,
                'apply_to_regular' => true,
                'apply_to_overtime' => false,
                'apply_to_bonus' => false,
                'apply_to_allowances' => false,
                'employer_share_rate' => 2.00, // 2% employer share
                'employer_share_fixed' => null,
                'is_active' => true,
                'is_system_default' => true,
                'sort_order' => 3,
            ]
        );

        // BIR Withholding Tax
        DeductionTaxSetting::updateOrCreate(
            ['code' => 'withholding_tax'],
            [
                'name' => 'Withholding Tax',
                'description' => 'BIR withholding tax based on taxable income brackets',
                'type' => 'government',
                'category' => 'mandatory',
                'calculation_type' => 'bracket',
                'tax_table_type' => 'withholding_tax',
                'apply_to_basic_pay' => false,
                'apply_to_gross_pay' => false,
                'apply_to_taxable_income' => true,
                'apply_to_net_pay' => false,
                'apply_to_regular' => false,
                'apply_to_overtime' => false,
                'apply_to_bonus' => false,
                'apply_to_allowances' => false,
                'employer_share_rate' => null,
                'employer_share_fixed' => null,
                'is_active' => true,
                'is_system_default' => true,
                'sort_order' => 4,
            ]
        );

        $this->command->info('Government deduction settings have been seeded successfully.');
    }
}
