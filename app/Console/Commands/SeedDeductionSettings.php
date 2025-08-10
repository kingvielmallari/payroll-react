<?php

namespace App\Console\Commands;

use App\Models\DeductionSetting;
use Illuminate\Console\Command;

class SeedDeductionSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deduction:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed deduction settings with default government and custom deduction configurations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Seeding deduction settings...');

        try {
            // Check if records already exist
            if (DeductionSetting::count() > 0) {
                if (!$this->confirm('Deduction settings already exist. Do you want to clear and reseed?')) {
                    $this->info('Seeding cancelled.');
                    return;
                }
                DeductionSetting::truncate();
            }

            // SSS (Social Security System)
            DeductionSetting::create([
                'name' => 'SSS Contribution',
                'code' => 'sss',
                'type' => 'government',
                'calculation_type' => 'percentage',
                'rate' => 4.5,
                'minimum_amount' => 135.00,
                'maximum_amount' => 1800.00,
                'salary_threshold' => 3250.00,
                'is_mandatory' => true,
                'is_active' => true,
                'description' => 'Social Security System contribution for employees',
                'formula_notes' => 'Employee contributes 4.5% of monthly salary credit.',
            ]);
            $this->info('✓ SSS Contribution setting created');

            // PhilHealth
            DeductionSetting::create([
                'name' => 'PhilHealth Premium',
                'code' => 'philhealth',
                'type' => 'government',
                'calculation_type' => 'percentage',
                'rate' => 1.5,
                'minimum_amount' => 450.00,
                'maximum_amount' => 2000.00,
                'salary_threshold' => 10000.00,
                'is_mandatory' => true,
                'is_active' => true,
                'description' => 'Philippine Health Insurance Corporation premium',
                'formula_notes' => 'Employee contributes 1.5% of monthly salary (min: ₱450, max: ₱2,000).',
            ]);
            $this->info('✓ PhilHealth Premium setting created');

            // Pag-IBIG
            DeductionSetting::create([
                'name' => 'Pag-IBIG Fund',
                'code' => 'pagibig',
                'type' => 'government',
                'calculation_type' => 'percentage',
                'rate' => 2.0,
                'minimum_amount' => 15.00,
                'maximum_amount' => 200.00,
                'is_mandatory' => true,
                'is_active' => true,
                'description' => 'Home Development Mutual Fund (Pag-IBIG) contribution',
                'formula_notes' => 'For salaries ≤₱1,500: 1% of salary. For salaries >₱1,500: 2% of salary (max: ₱200).',
            ]);
            $this->info('✓ Pag-IBIG Fund setting created');

            // BIR Withholding Tax
            DeductionSetting::create([
                'name' => 'Withholding Tax',
                'code' => 'bir_withholding',
                'type' => 'government',
                'calculation_type' => 'table_based',
                'rate_table' => [
                    ['min' => 0, 'max' => 20833, 'amount' => 0], // ₱250,000 annual / 12
                    ['min' => 20834, 'max' => 33333, 'rate' => 20, 'base' => 0, 'excess_over' => 20833],
                    ['min' => 33334, 'max' => 66667, 'rate' => 25, 'base' => 2500, 'excess_over' => 33333],
                    ['min' => 66668, 'max' => 166667, 'rate' => 30, 'base' => 10833, 'excess_over' => 66667],
                    ['min' => 166668, 'max' => 666667, 'rate' => 32, 'base' => 40833, 'excess_over' => 166667],
                    ['min' => 666668, 'max' => 999999999, 'rate' => 35, 'base' => 200833, 'excess_over' => 666667],
                ],
                'is_mandatory' => true,
                'is_active' => true,
                'description' => 'Bureau of Internal Revenue withholding tax on compensation',
                'formula_notes' => 'Progressive tax rates based on monthly compensation.',
            ]);
            $this->info('✓ Withholding Tax setting created');

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
            $this->info('✓ Uniform Allowance setting created');

            // Late/Absence Penalty
            DeductionSetting::create([
                'name' => 'Late/Absence Penalty',
                'code' => 'penalty',
                'type' => 'custom',
                'calculation_type' => 'percentage',
                'rate' => 0.1,
                'is_mandatory' => false,
                'is_active' => true,
                'description' => 'Penalty for tardiness or unexcused absences',
                'formula_notes' => 'Calculated based on daily rate and number of infractions.',
            ]);
            $this->info('✓ Late/Absence Penalty setting created');

            $this->info('');
            $this->info('🎉 Deduction settings seeded successfully!');
            $this->info('Created ' . DeductionSetting::count() . ' deduction settings.');

        } catch (\Exception $e) {
            $this->error('Error seeding deduction settings: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
