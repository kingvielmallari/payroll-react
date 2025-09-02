<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\DeductionTaxSetting;

class UpdatePhilHealthDeductionSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // First, try to find existing PhilHealth deduction setting
        $philHealthSetting = DeductionTaxSetting::where('code', 'philhealth')
            ->orWhere('name', 'like', '%PhilHealth%')
            ->first();

        if ($philHealthSetting) {
            // Update existing PhilHealth setting to use new tax table and Monthly Basic Salary
            $philHealthSetting->update([
                'name' => 'PhilHealth Contribution',
                'code' => 'philhealth',
                'description' => 'PhilHealth contribution based on Monthly Basic Salary using percentage-based tax table',
                'type' => 'government',
                'category' => 'mandatory',
                'calculation_type' => 'bracket',
                'tax_table_type' => 'philhealth',
                'rate_percentage' => null,
                'fixed_amount' => null,
                'bracket_rates' => null,
                'minimum_amount' => 500.00,
                'maximum_amount' => 5000.00,
                'salary_cap' => null,
                'apply_to_regular' => false,
                'apply_to_overtime' => false,
                'apply_to_bonus' => false,
                'apply_to_allowances' => false,
                'apply_to_basic_pay' => false,
                'apply_to_gross_pay' => false,
                'apply_to_taxable_income' => false,
                'apply_to_net_pay' => false,
                'apply_to_monthly_basic_salary' => true,
                'employer_share_rate' => 2.5,
                'employer_share_fixed' => null,
                'share_with_employer' => true,
                'is_active' => true,
                'is_system_default' => true,
                'sort_order' => 2,
                'benefit_eligibility' => 'both',
            ]);
        } else {
            // Create new PhilHealth deduction setting
            DeductionTaxSetting::create([
                'name' => 'PhilHealth Contribution',
                'code' => 'philhealth',
                'description' => 'PhilHealth contribution based on Monthly Basic Salary using percentage-based tax table',
                'type' => 'government',
                'category' => 'mandatory',
                'calculation_type' => 'bracket',
                'tax_table_type' => 'philhealth',
                'rate_percentage' => null,
                'fixed_amount' => null,
                'bracket_rates' => null,
                'minimum_amount' => 500.00,
                'maximum_amount' => 5000.00,
                'salary_cap' => null,
                'apply_to_regular' => false,
                'apply_to_overtime' => false,
                'apply_to_bonus' => false,
                'apply_to_allowances' => false,
                'apply_to_basic_pay' => false,
                'apply_to_gross_pay' => false,
                'apply_to_taxable_income' => false,
                'apply_to_net_pay' => false,
                'apply_to_monthly_basic_salary' => true,
                'employer_share_rate' => 2.5,
                'employer_share_fixed' => null,
                'share_with_employer' => true,
                'is_active' => true,
                'is_system_default' => true,
                'sort_order' => 2,
                'benefit_eligibility' => 'both',
            ]);
        }

        $this->command->info('PhilHealth deduction setting updated to use new tax table with Monthly Basic Salary pay basis');
    }
}
