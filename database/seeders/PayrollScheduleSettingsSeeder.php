<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PayrollScheduleSetting;

class PayrollScheduleSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing settings
        PayrollScheduleSetting::truncate();

        // Weekly Schedule
        PayrollScheduleSetting::create([
            'pay_type' => 'weekly',
            'cutoff_description' => 'Monday to Sunday',
            'cutoff_start_day' => 1, // Monday (1-7 where 1=Monday)
            'cutoff_end_day' => 7,   // Sunday
            'payday_description' => 'Next Friday',
            'payday_offset_days' => 5, // 5 days after Sunday (Friday)
            'notes' => 'Weekly pay schedule - employees paid every Friday for the previous Monday-Sunday period',
            'cutoff_rules' => [
                'week_start' => 'monday',
                'payday' => 'friday'
            ],
            'is_active' => true
        ]);

        // Semi-Monthly Schedule - First Half
        PayrollScheduleSetting::create([
            'pay_type' => 'semi_monthly',
            'cutoff_description' => '1st to 15th of the month',
            'cutoff_start_day' => 1,
            'cutoff_end_day' => 15,
            'payday_description' => '15th of the month',
            'payday_offset_days' => 0, // Same day as cutoff end
            'notes' => 'First semi-monthly period - employees paid on the 15th for work from 1st to 15th',
            'cutoff_rules' => [
                'period' => 'first_half',
                'fixed_payday' => true,
                'payday_day' => 15
            ],
            'is_active' => true
        ]);

        // Monthly Schedule
        PayrollScheduleSetting::create([
            'pay_type' => 'monthly',
            'cutoff_description' => '1st to last day of the month',
            'cutoff_start_day' => 1,
            'cutoff_end_day' => 31, // Will be adjusted for different month lengths
            'payday_description' => 'Last day of the month',
            'payday_offset_days' => 0,
            'notes' => 'Monthly pay schedule - employees paid on the last day of each month for the entire month',
            'cutoff_rules' => [
                'period' => 'monthly',
                'fixed_payday' => true,
                'adjust_for_month_end' => true
            ],
            'is_active' => true
        ]);
    }
}
