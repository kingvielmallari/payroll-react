<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PayScheduleSetting;

class PayScheduleSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schedules = [
            [
                'name' => 'Weekly',
                'code' => 'weekly',
                'description' => 'Weekly pay schedule with configurable days',
                'cutoff_periods' => [
                    [
                        'start_day' => 'monday',
                        'end_day' => 'friday', 
                        'pay_day' => 'friday'
                    ]
                ],
                'move_if_holiday' => true,
                'move_if_weekend' => true,
                'move_direction' => 'before',
                'is_active' => true,
                'is_system_default' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Semi Monthly',
                'code' => 'semi_monthly', 
                'description' => 'Twice monthly pay schedule with 2 cut-off periods',
                'cutoff_periods' => [
                    [
                        'start_day' => 1,
                        'end_day' => 15,
                        'pay_date' => 16
                    ],
                    [
                        'start_day' => 16,
                        'end_day' => 31,
                        'pay_date' => 5 // 5th of next month
                    ]
                ],
                'move_if_holiday' => true,
                'move_if_weekend' => true,
                'move_direction' => 'before',
                'is_active' => true,
                'is_system_default' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Monthly',
                'code' => 'monthly',
                'description' => 'Monthly pay schedule with single cut-off period',
                'cutoff_periods' => [
                    [
                        'start_day' => 1,
                        'end_day' => 31,
                        'pay_date' => 5 // 5th of next month
                    ]
                ],
                'move_if_holiday' => true,
                'move_if_weekend' => true,
                'move_direction' => 'before',
                'is_active' => true,
                'is_system_default' => true,
                'sort_order' => 3,
            ],
        ];
        
        foreach ($schedules as $schedule) {
            PayScheduleSetting::updateOrCreate(
                ['code' => $schedule['code']],
                $schedule
            );
        }
        
        $this->command->info('Pay schedule settings seeded successfully!');
    }
}
