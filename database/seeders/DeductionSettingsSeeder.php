<?php

namespace Database\Seeders;

use App\Models\DeductionSetting;
use Illuminate\Database\Seeder;

class DeductionSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // SSS (Social Security System)
        DeductionSetting::create([
            'name' => 'SSS Contribution',
            'code' => 'sss',
            'type' => 'government',
            'calculation_type' => 'tiered',
            'rate_table' => [
                ['min' => 0, 'max' => 3249.99, 'rate' => 0],
                ['min' => 3250, 'max' => 3749.99, 'rate' => 4.5],
                ['min' => 3750, 'max' => 4249.99, 'rate' => 4.5],
                ['min' => 4250, 'max' => 4749.99, 'rate' => 4.5],
                ['min' => 4750, 'max' => 5249.99, 'rate' => 4.5],
                ['min' => 5250, 'max' => 5749.99, 'rate' => 4.5],
                ['min' => 5750, 'max' => 6249.99, 'rate' => 4.5],
                ['min' => 6250, 'max' => 6749.99, 'rate' => 4.5],
                ['min' => 6750, 'max' => 7249.99, 'rate' => 4.5],
                ['min' => 7250, 'max' => 7749.99, 'rate' => 4.5],
                ['min' => 7750, 'max' => 8249.99, 'rate' => 4.5],
                ['min' => 8250, 'max' => 8749.99, 'rate' => 4.5],
                ['min' => 8750, 'max' => 9249.99, 'rate' => 4.5],
                ['min' => 9250, 'max' => 9749.99, 'rate' => 4.5],
                ['min' => 9750, 'max' => 10249.99, 'rate' => 4.5],
                ['min' => 10250, 'max' => 999999, 'rate' => 4.5],
            ],
            'minimum_amount' => 135.00,
            'maximum_amount' => 1800.00,
            'salary_threshold' => 3250.00,
            'is_mandatory' => true,
            'is_active' => true,
            'description' => 'Social Security System contribution for employees',
            'formula_notes' => 'Employee contributes 4.5% of monthly salary credit. Employer contributes 9%, EC contributes 0.5%.',
        ]);

        // PhilHealth
        DeductionSetting::create([
            'name' => 'PhilHealth Premium',
            'code' => 'philhealth',
            'type' => 'government',
            'calculation_type' => 'percentage',
            'rate' => 1.5, // Employee's share is 1.5%, employer's is also 1.5%
            'minimum_amount' => 450.00,
            'maximum_amount' => 2000.00,
            'salary_threshold' => 10000.00,
            'is_mandatory' => true,
            'is_active' => true,
            'description' => 'Philippine Health Insurance Corporation premium',
            'formula_notes' => 'Employee contributes 1.5% of monthly salary (min: ₱450, max: ₱2,000). For salaries below ₱10,000, contribution is ₱450.',
        ]);

        // Pag-IBIG
        DeductionSetting::create([
            'name' => 'Pag-IBIG Fund',
            'code' => 'pagibig',
            'type' => 'government',
            'calculation_type' => 'tiered',
            'rate_table' => [
                ['min' => 0, 'max' => 1500, 'rate' => 1],
                ['min' => 1500.01, 'max' => 999999, 'rate' => 2],
            ],
            'minimum_amount' => 15.00,
            'maximum_amount' => 200.00,
            'is_mandatory' => true,
            'is_active' => true,
            'description' => 'Home Development Mutual Fund (Pag-IBIG) contribution',
            'formula_notes' => 'For salaries ≤₱1,500: 1% of salary (min: ₱15). For salaries >₱1,500: 2% of salary (max: ₱200).',
        ]);

        // BIR Withholding Tax
        DeductionSetting::create([
            'name' => 'Withholding Tax',
            'code' => 'bir_withholding',
            'type' => 'government',
            'calculation_type' => 'table_based',
            'rate_table' => [
                // Annual tax table - will need to convert to monthly
                ['min' => 0, 'max' => 250000, 'amount' => 0],
                ['min' => 250001, 'max' => 400000, 'rate' => 20, 'base' => 0, 'excess_over' => 250000],
                ['min' => 400001, 'max' => 800000, 'rate' => 25, 'base' => 30000, 'excess_over' => 400000],
                ['min' => 800001, 'max' => 2000000, 'rate' => 30, 'base' => 130000, 'excess_over' => 800000],
                ['min' => 2000001, 'max' => 8000000, 'rate' => 32, 'base' => 490000, 'excess_over' => 2000000],
                ['min' => 8000001, 'max' => 999999999, 'rate' => 35, 'base' => 2410000, 'excess_over' => 8000000],
            ],
            'is_mandatory' => true,
            'is_active' => true,
            'description' => 'Bureau of Internal Revenue withholding tax on compensation',
            'formula_notes' => 'Progressive tax rates based on annual compensation. Calculated monthly using annualized method.',
        ]);

        // Example custom deduction
        DeductionSetting::create([
            'name' => 'Uniform Allowance',
            'code' => 'uniform',
            'type' => 'custom',
            'calculation_type' => 'fixed',
            'fixed_amount' => 500.00,
            'is_mandatory' => false,
            'is_active' => true,
            'description' => 'Monthly uniform allowance deduction',
            'formula_notes' => 'Fixed amount of ₱500 per month for uniform expenses.',
        ]);

        // Late/Absence Penalty
        DeductionSetting::create([
            'name' => 'Late/Absence Penalty',
            'code' => 'penalty',
            'type' => 'custom',
            'calculation_type' => 'percentage',
            'rate' => 0.1, // 0.1% per occurrence
            'is_mandatory' => false,
            'is_active' => true,
            'description' => 'Penalty for tardiness or unexcused absences',
            'formula_notes' => 'Calculated based on daily rate and number of infractions.',
        ]);
    }
}
