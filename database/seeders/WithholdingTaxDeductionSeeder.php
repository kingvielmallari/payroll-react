<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DeductionTaxSetting;

class WithholdingTaxDeductionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or update the Withholding Tax deduction setting
        DeductionTaxSetting::updateOrCreate(
            [
                'code' => 'withholding_tax',
                'name' => 'Withholding Tax'
            ],
            [
                'description' => 'Income tax based on Train Law tax brackets',
                'type' => 'government',
                'category' => 'mandatory',
                'calculation_type' => 'bracket',
                'tax_table_type' => 'withholding_tax',
                'pay_frequency' => 'semi_monthly', // Default to semi-monthly
                'apply_to_taxable_income' => true,
                'apply_to_basic_pay' => false,
                'apply_to_gross_pay' => false,
                'apply_to_net_pay' => false,
                'apply_to_monthly_basic_salary' => false,
                'apply_to_regular' => false,
                'apply_to_overtime' => false,
                'apply_to_bonus' => false,
                'apply_to_allowances' => false,
                'share_with_employer' => false, // Employee pays 100%
                'is_active' => true,
                'is_system_default' => true,
                'sort_order' => 4,
                'benefit_eligibility' => 'both',
            ]
        );

        $this->command->info('Withholding Tax deduction setting created/updated successfully!');
    }
}
