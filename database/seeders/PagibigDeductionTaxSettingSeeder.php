<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DeductionTaxSetting;

class PagibigDeductionTaxSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or update Pag-IBIG deduction tax setting
        DeductionTaxSetting::updateOrCreate(
            [
                'code' => 'pagibig',
                'type' => 'government'
            ],
            [
                'name' => 'Pag-IBIG Fund',
                'description' => 'Home Development Mutual Fund (Pag-IBIG) contribution using tax table',
                'category' => 'mandatory',
                'calculation_type' => 'bracket',
                'tax_table_type' => 'pagibig',
                'rate_percentage' => 0, // Not used for tax table calculations
                'minimum_amount' => 0,
                'maximum_amount' => 200,
                'share_with_employer' => true,
                'apply_to_basic_pay' => false,
                'apply_to_gross_pay' => false,
                'apply_to_taxable_income' => false,
                'apply_to_monthly_basic_salary' => true, // Use monthly basic salary
                'apply_to_net_pay' => false,
                'employee_share_percentage' => 50.00, // 50% employee (2% of salary)
                'employer_share_percentage' => 50.00, // 50% employer (2% of salary)
                'sort_order' => 3, // After SSS and PhilHealth
                'is_active' => true,
                'benefit_eligibility' => 'both', // Both regular and probationary
                'deduction_frequency' => 'monthly',
            ]
        );

        $this->command->info('Pag-IBIG Deduction Tax Setting created/updated successfully!');
    }
}
