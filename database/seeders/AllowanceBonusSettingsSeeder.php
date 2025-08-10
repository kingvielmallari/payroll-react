<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AllowanceBonusSetting;

class AllowanceBonusSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Common Allowances
            [
                'name' => 'Rice Allowance',
                'code' => 'rice_allowance',
                'description' => 'Monthly rice subsidy allowance',
                'type' => 'allowance',
                'category' => 'regular',
                'calculation_type' => 'fixed_amount',
                'fixed_amount' => 1500,
                'is_taxable' => false, // Usually non-taxable up to certain amount
                'frequency' => 'per_payroll',
                'apply_to_regular_days' => true,
                'is_active' => true,
                'is_system_default' => true,
            ],
            [
                'name' => 'Transportation Allowance',
                'code' => 'transportation',
                'description' => 'Daily transportation allowance',
                'type' => 'allowance',
                'category' => 'regular',
                'calculation_type' => 'fixed_amount',
                'fixed_amount' => 200,
                'is_taxable' => false,
                'frequency' => 'daily',
                'max_days_per_period' => 22, // Working days per month
                'apply_to_regular_days' => true,
                'is_active' => true,
                'is_system_default' => false,
            ],
            [
                'name' => 'Meal Allowance',
                'code' => 'meal_allowance',
                'description' => 'Daily meal allowance',
                'type' => 'allowance',
                'category' => 'regular',
                'calculation_type' => 'fixed_amount',
                'fixed_amount' => 150,
                'is_taxable' => false,
                'frequency' => 'daily',
                'max_days_per_period' => 22,
                'apply_to_regular_days' => true,
                'is_active' => true,
                'is_system_default' => false,
            ],
            [
                'name' => 'Communication Allowance',
                'code' => 'communication',
                'description' => 'Monthly communication allowance',
                'type' => 'allowance',
                'category' => 'regular',
                'calculation_type' => 'fixed_amount',
                'fixed_amount' => 800,
                'is_taxable' => true,
                'frequency' => 'per_payroll',
                'apply_to_regular_days' => true,
                'is_active' => true,
                'is_system_default' => false,
            ],
            
            // Bonuses
            [
                'name' => '13th Month Pay',
                'code' => '13th_month',
                'description' => 'Mandatory 13th month pay (1/12 of total basic salary)',
                'type' => 'bonus',
                'category' => 'regular',
                'calculation_type' => 'percentage',
                'rate_percentage' => 8.33, // 1/12 = 8.33%
                'is_taxable' => false, // Non-taxable up to ₱90,000
                'frequency' => 'annually',
                'apply_to_regular_days' => true,
                'is_active' => true,
                'is_system_default' => true,
            ],
            [
                'name' => 'Performance Bonus',
                'code' => 'performance_bonus',
                'description' => 'Quarterly performance-based bonus',
                'type' => 'bonus',
                'category' => 'conditional',
                'calculation_type' => 'percentage',
                'rate_percentage' => 10,
                'is_taxable' => true,
                'frequency' => 'quarterly',
                'conditions' => [
                    [
                        'field' => 'performance_rating',
                        'operator' => 'greater_than',
                        'value' => 4,
                        'action' => 'multiply',
                        'action_value' => 1.5
                    ]
                ],
                'apply_to_regular_days' => true,
                'is_active' => true,
                'is_system_default' => false,
            ],
            [
                'name' => 'Overtime Meal Allowance',
                'code' => 'overtime_meal',
                'description' => 'Meal allowance for overtime work',
                'type' => 'allowance',
                'category' => 'conditional',
                'calculation_type' => 'fixed_amount',
                'fixed_amount' => 100,
                'is_taxable' => false,
                'frequency' => 'daily',
                'conditions' => [
                    [
                        'field' => 'overtime_hours',
                        'operator' => 'greater_than',
                        'value' => 2,
                        'action' => 'set',
                        'action_value' => 100
                    ]
                ],
                'apply_to_overtime' => true,
                'is_active' => true,
                'is_system_default' => false,
            ],
        ];
        
        foreach ($settings as $setting) {
            AllowanceBonusSetting::firstOrCreate(
                ['code' => $setting['code']],
                $setting
            );
        }
        
        $this->command->info('Allowance/Bonus settings seeded successfully!');
    }
}
