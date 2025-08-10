<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DefaultSchedulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default time schedules
        $timeSchedules = [
            [
                'name' => 'Regular Office Hours (8AM-5PM)',
                'time_in' => '08:00:00',
                'time_out' => '17:00:00',
                'break_start' => '12:00:00',
                'break_end' => '13:00:00',
                'is_active' => true,
            ],
            [
                'name' => 'Early Shift (6AM-3PM)',
                'time_in' => '06:00:00',
                'time_out' => '15:00:00',
                'break_start' => '10:00:00',
                'break_end' => '11:00:00',
                'is_active' => true,
            ],
            [
                'name' => 'Late Shift (2PM-11PM)',
                'time_in' => '14:00:00',
                'time_out' => '23:00:00',
                'break_start' => '18:00:00',
                'break_end' => '19:00:00',
                'is_active' => true,
            ],
            [
                'name' => 'Night Shift (10PM-7AM)',
                'time_in' => '22:00:00',
                'time_out' => '07:00:00',
                'break_start' => '02:00:00',
                'break_end' => '03:00:00',
                'is_active' => true,
            ],
            [
                'name' => 'Flexible Hours (9AM-6PM)',
                'time_in' => '09:00:00',
                'time_out' => '18:00:00',
                'break_start' => '12:30:00',
                'break_end' => '13:30:00',
                'is_active' => true,
            ],
        ];

        foreach ($timeSchedules as $schedule) {
            \App\Models\TimeSchedule::firstOrCreate(
                ['name' => $schedule['name']],
                $schedule
            );
        }

        // Create default day schedules
        $daySchedules = [
            [
                'name' => 'Monday to Friday',
                'monday' => true,
                'tuesday' => true,
                'wednesday' => true,
                'thursday' => true,
                'friday' => true,
                'saturday' => false,
                'sunday' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Monday to Saturday',
                'monday' => true,
                'tuesday' => true,
                'wednesday' => true,
                'thursday' => true,
                'friday' => true,
                'saturday' => true,
                'sunday' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Tuesday to Saturday',
                'monday' => false,
                'tuesday' => true,
                'wednesday' => true,
                'thursday' => true,
                'friday' => true,
                'saturday' => true,
                'sunday' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Sunday to Thursday',
                'monday' => true,
                'tuesday' => true,
                'wednesday' => true,
                'thursday' => true,
                'friday' => false,
                'saturday' => false,
                'sunday' => true,
                'is_active' => true,
            ],
            [
                'name' => 'All Days (7 Days)',
                'monday' => true,
                'tuesday' => true,
                'wednesday' => true,
                'thursday' => true,
                'friday' => true,
                'saturday' => true,
                'sunday' => true,
                'is_active' => true,
            ],
        ];

        foreach ($daySchedules as $schedule) {
            \App\Models\DaySchedule::firstOrCreate(
                ['name' => $schedule['name']],
                $schedule
            );
        }
    }
}
