<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PayrollSetting;
use App\Models\User;

class PayrollSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first admin user or create a default user
        $adminUser = User::first();

        // Create default payroll setting if it doesn't exist
        if (!PayrollSetting::exists()) {
            PayrollSetting::create([
                'payroll_frequency' => 'semi_monthly',
                'payroll_periods' => json_encode([]), // Will be calculated automatically
                'pay_delay_days' => 0,
                'adjust_for_weekends' => true,
                'adjust_for_holidays' => true,
                'weekend_adjustment' => 'before',
                'notes' => 'Default semi-monthly payroll setting',
                'is_active' => true,
                'created_by' => $adminUser?->id,
                'updated_by' => $adminUser?->id,
            ]);
        }
    }
}
