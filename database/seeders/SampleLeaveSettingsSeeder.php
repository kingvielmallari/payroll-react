<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SampleLeaveSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $leaveSettings = [
            [
                'name' => 'Vacation Leave',
                'code' => 'VL',
                'total_days' => 1,
                'limit_quantity' => 3,
                'limit_period' => 'monthly',
                'applicable_to' => 'with_benefits',
                'pay_percentage' => 100.00,
                'is_active' => true,
            ],
            [
                'name' => 'Sick Leave',
                'code' => 'SL',
                'total_days' => 1,
                'limit_quantity' => 2,
                'limit_period' => 'monthly',
                'applicable_to' => 'both',
                'pay_percentage' => 100.00,
                'is_active' => true,
            ],
            [
                'name' => 'Emergency Leave',
                'code' => 'EL',
                'total_days' => 1,
                'limit_quantity' => 1,
                'limit_period' => 'quarterly',
                'applicable_to' => 'both',
                'pay_percentage' => 50.00,
                'is_active' => true,
            ],
        ];

        foreach ($leaveSettings as $setting) {
            \App\Models\PaidLeaveSetting::updateOrCreate(
                ['code' => $setting['code']],
                $setting
            );
        }
    }
}
