<?php

namespace Database\Seeders;

use App\Models\TimeSchedule;
use Illuminate\Database\Seeder;

class TimeScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timeSchedules = [
            [
                'name' => 'Regular Day Shift',
                'time_in' => '08:00:00',
                'time_out' => '17:00:00',
                'break_start' => '12:00:00',
                'break_end' => '13:00:00',
                'is_active' => true,
                'created_by' => 1,
            ],
            [
                'name' => 'Morning Shift',
                'time_in' => '07:00:00',
                'time_out' => '16:00:00',
                'break_start' => '11:00:00',
                'break_end' => '12:00:00',
                'is_active' => true,
                'created_by' => 1,
            ],
            [
                'name' => 'Afternoon Shift',
                'time_in' => '13:00:00',
                'time_out' => '22:00:00',
                'break_start' => '17:00:00',
                'break_end' => '18:00:00',
                'is_active' => true,
                'created_by' => 1,
            ],
            [
                'name' => 'Flexible Hours',
                'time_in' => '09:00:00',
                'time_out' => '18:00:00',
                'break_start' => '12:30:00',
                'break_end' => '13:30:00',
                'is_active' => true,
                'created_by' => 1,
            ],
        ];

        foreach ($timeSchedules as $schedule) {
            TimeSchedule::create($schedule);
        }

        $this->command->info('✅ Time schedules seeded successfully!');
    }
}
