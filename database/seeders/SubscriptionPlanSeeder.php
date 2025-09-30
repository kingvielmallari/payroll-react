<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter Plan',
                'slug' => 'starter',
                'description' => 'Perfect for small businesses with up to 10 employees',
                'max_employees' => 10,
                'features' => ['basic_payroll', 'time_tracking', 'employee_management', 'basic_reports'],
                'price' => 99.00,
                'duration_months' => 12,
                'is_active' => true
            ],
            [
                'name' => 'Professional Plan',
                'slug' => 'professional',
                'description' => 'For growing companies with up to 50 employees',
                'max_employees' => 50,
                'features' => ['basic_payroll', 'time_tracking', 'employee_management', 'basic_reports', 'advanced_reports', 'government_forms'],
                'price' => 299.00,
                'duration_months' => 12,
                'is_active' => true
            ],
            [
                'name' => 'Enterprise Plan',
                'slug' => 'enterprise',
                'description' => 'Unlimited employees with all features',
                'max_employees' => -1,
                'features' => ['basic_payroll', 'time_tracking', 'employee_management', 'basic_reports', 'advanced_reports', 'government_forms', 'api_access'],
                'price' => 599.00,
                'duration_months' => 12,
                'is_active' => true
            ]
        ];

        foreach ($plans as $planData) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }
    }
}
