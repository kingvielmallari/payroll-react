<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PayrollScheduleSetting;

class PayrollScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing records
        PayrollScheduleSetting::truncate();

        $settings = [
            [
                'pay_type' => 'weekly',
                'cutoff_description' => 'Monday to Friday, Pay on Friday',
                'cutoff_start_day' => 1, // Monday
                'cutoff_end_day' => 5,   // Friday
                'payday_offset_days' => 0, // Same day as end
                'payday_description' => 'Every Friday',
                'notes' => 'Weekly payroll runs Monday to Friday with payment on Friday',
                'is_active' => true,
                
                // New flexible configuration
                'weekly_start_day' => 'monday',
                'weekly_end_day' => 'friday',
                'weekly_pay_day' => 'friday',
                'holiday_handling' => 'before',
                'skip_weekends' => true,
                'skip_holidays' => true,
                'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'special_rules' => 'If Friday is a holiday, pay on Thursday',
                
                'cutoff_rules' => [
                    'start_day' => 'monday',
                    'end_day' => 'friday',
                    'pay_day' => 'friday',
                    'description' => 'Monday to Friday weekly cycle'
                ]
            ],
            [
                'pay_type' => 'semi_monthly',
                'cutoff_description' => '1st-15th (Pay 20th) and 16th-31st (Pay last day)',
                'cutoff_start_day' => 1,
                'cutoff_end_day' => 15,
                'payday_offset_days' => 5, // 5 days after 15th = 20th
                'payday_description' => '20th and last day of month',
                'notes' => 'Two pay periods per month: 1st-15th paid on 20th, 16th-31st paid on last day',
                'is_active' => true,
                
                // New flexible configuration
                'semi_monthly_config' => [
                    'first_period' => [
                        'start_day' => 1,
                        'end_day' => 15,
                        'pay_day' => 20,
                        'description' => '1st to 15th, pay on 20th'
                    ],
                    'second_period' => [
                        'start_day' => 16,
                        'end_day' => -1, // Last day of month
                        'pay_day' => -1, // Last day of month
                        'description' => '16th to last day, pay on last day'
                    ]
                ],
                'holiday_handling' => 'before',
                'skip_weekends' => true,
                'skip_holidays' => true,
                'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'special_rules' => 'If pay day falls on weekend or holiday, move to previous working day',
                
                'cutoff_rules' => [
                    'first_cutoff' => [
                        'start' => 1,
                        'end' => 15,
                        'pay_date' => 20
                    ],
                    'second_cutoff' => [
                        'start' => 16,
                        'end' => 'last_day',
                        'pay_date' => 'last_day'
                    ]
                ]
            ],
            [
                'pay_type' => 'monthly',
                'cutoff_description' => '1st to last day of month, Pay on last day',
                'cutoff_start_day' => 1,
                'cutoff_end_day' => 31,
                'payday_offset_days' => 0,
                'payday_description' => 'Last day of month',
                'notes' => 'Full month payroll from 1st to last day, payment on last day of month',
                'is_active' => true,
                
                // New flexible configuration
                'monthly_start_day' => 1,
                'monthly_end_day' => -1, // Last day of month
                'monthly_pay_day' => -1, // Last day of month
                'holiday_handling' => 'before',
                'skip_weekends' => true,
                'skip_holidays' => true,
                'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'special_rules' => 'If last day is weekend or holiday, pay on previous working day',
                
                'cutoff_rules' => [
                    'start_day' => 1,
                    'end_day' => 'last_day',
                    'pay_day' => 'last_day',
                    'description' => 'Full month from 1st to last day'
                ]
            ],
        ];

        foreach ($settings as $setting) {
            PayrollScheduleSetting::create($setting);
        }

        $this->command->info('PayrollScheduleSeeder completed successfully!');
        $this->command->info('Created 3 payroll schedule configurations:');
        $this->command->info('- Weekly: Monday-Friday, Pay on Friday');
        $this->command->info('- Semi-Monthly: 1st-15th (Pay 20th), 16th-31st (Pay last day)');
        $this->command->info('- Monthly: 1st-31st, Pay on last day');
    }
}
