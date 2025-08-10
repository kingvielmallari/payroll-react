<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DeductionTaxSetting;

class DeductionTaxSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $deductions = [
            // Government Contributions
            [
                'name' => 'SSS Contribution',
                'code' => 'sss',
                'description' => 'Social Security System contribution',
                'type' => 'government',
                'category' => 'mandatory',
                'calculation_type' => 'bracket',
                'bracket_rates' => [
                    ['min' => 0, 'max' => 3250, 'employee_rate' => 135, 'employer_rate' => 270, 'type' => 'fixed'],
                    ['min' => 3250, 'max' => 3750, 'employee_rate' => 157.50, 'employer_rate' => 315, 'type' => 'fixed'],
                    ['min' => 3750, 'max' => 4250, 'employee_rate' => 180, 'employer_rate' => 360, 'type' => 'fixed'],
                    // Add more brackets as per current SSS table
                ],
                'salary_cap' => 30000,
                'employer_share_rate' => 8.5,
                'apply_to_regular' => true,
                'is_active' => true,
                'is_system_default' => true,
            ],
            [
                'name' => 'PhilHealth Contribution',
                'code' => 'philhealth',
                'description' => 'Philippine Health Insurance Corporation contribution',
                'type' => 'government',
                'category' => 'mandatory',
                'calculation_type' => 'percentage',
                'rate_percentage' => 4.5, // 4.5% total (2.25% employee, 2.25% employer)
                'salary_cap' => 100000,
                'minimum_amount' => 450,
                'maximum_amount' => 4500,
                'employer_share_rate' => 2.25,
                'apply_to_regular' => true,
                'is_active' => true,
                'is_system_default' => true,
            ],
            [
                'name' => 'Pag-IBIG Contribution',
                'code' => 'pagibig',
                'description' => 'Home Development Mutual Fund contribution',
                'type' => 'government',
                'category' => 'mandatory',
                'calculation_type' => 'percentage',
                'rate_percentage' => 2.0, // 2% total (1% employee, 1% employer)
                'salary_cap' => 5000,
                'minimum_amount' => 100,
                'maximum_amount' => 100,
                'employer_share_rate' => 1.0,
                'apply_to_regular' => true,
                'is_active' => true,
                'is_system_default' => true,
            ],
            
            // Withholding Tax
            [
                'name' => 'Withholding Tax',
                'code' => 'withholding_tax',
                'description' => 'Income tax withheld from salary (TRAIN Law)',
                'type' => 'government',
                'category' => 'mandatory',
                'calculation_type' => 'bracket',
                'bracket_rates' => [
                    ['min' => 0, 'max' => 250000, 'rate' => 0], // Tax exempt
                    ['min' => 250000, 'max' => 400000, 'rate' => 20],
                    ['min' => 400000, 'max' => 800000, 'rate' => 25], 
                    ['min' => 800000, 'max' => 2000000, 'rate' => 30],
                    ['min' => 2000000, 'max' => 8000000, 'rate' => 32],
                    ['min' => 8000000, 'max' => PHP_INT_MAX, 'rate' => 35],
                ],
                'apply_to_regular' => true,
                'apply_to_overtime' => true,
                'apply_to_bonus' => true,
                'is_active' => true,
                'is_system_default' => true,
            ],
        ];
        
        foreach ($deductions as $deduction) {
            DeductionTaxSetting::firstOrCreate(
                ['code' => $deduction['code']],
                $deduction
            );
        }
        
        $this->command->info('Deduction/Tax settings seeded successfully!');
    }
}
