<?php

namespace Database\Seeders;

use App\Models\DaySchedule;
use Illuminate\Database\Seeder;

class DayScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
                'created_by' => 1,
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
                'created_by' => 1,
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
                'created_by' => 1,
            ],
            [
                'name' => 'Flexible Schedule',
                'monday' => true,
                'tuesday' => true,
                'wednesday' => true,
                'thursday' => true,
                'friday' => false,
                'saturday' => false,
                'sunday' => true,
                'is_active' => true,
                'created_by' => 1,
            ],
        ];

        foreach ($daySchedules as $schedule) {
            DaySchedule::create($schedule);
        }

        $this->command->info('✅ Day schedules seeded successfully!');
    }
}
