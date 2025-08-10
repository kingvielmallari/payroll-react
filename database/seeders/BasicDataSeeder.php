<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Position;
use App\Models\WorkSchedule;
use App\Models\Holiday;

class BasicDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Departments
        $departments = [
            [
                'name' => 'Human Resources',
                'code' => 'HR',
                'description' => 'Manages employee relations, recruitment, and company policies',
                'is_active' => true,
            ],
            [
                'name' => 'Information Technology',
                'code' => 'IT',
                'description' => 'Manages technology infrastructure and software development',
                'is_active' => true,
            ],
            [
                'name' => 'Finance',
                'code' => 'FIN',
                'description' => 'Handles financial planning, accounting, and budgeting',
                'is_active' => true,
            ],
            [
                'name' => 'Marketing',
                'code' => 'MKT',
                'description' => 'Responsible for marketing strategies and campaigns',
                'is_active' => true,
            ],
            [
                'name' => 'Operations',
                'code' => 'OPS',
                'description' => 'Manages daily operations and processes',
                'is_active' => true,
            ],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(['code' => $dept['code']], $dept);
        }

        // Create Positions
        $positions = [
            [
                'title' => 'Chief Executive Officer',
                'description' => 'Senior executive responsible for making major corporate decisions',
                'base_salary' => 100000.00,
                'salary_type' => 'monthly',
                'is_active' => true,
            ],
            [
                'title' => 'HR Manager',
                'description' => 'Manages human resources department and policies',
                'base_salary' => 60000.00,
                'salary_type' => 'monthly',
                'is_active' => true,
            ],
            [
                'title' => 'HR Assistant',
                'description' => 'Assists with HR administrative tasks',
                'base_salary' => 25000.00,
                'salary_type' => 'monthly',
                'is_active' => true,
            ],
            [
                'title' => 'IT Manager',
                'description' => 'Manages IT department and technology infrastructure',
                'base_salary' => 65000.00,
                'salary_type' => 'monthly',
                'is_active' => true,
            ],
            [
                'title' => 'Software Developer',
                'description' => 'Develops and maintains software applications',
                'base_salary' => 45000.00,
                'salary_type' => 'monthly',
                'is_active' => true,
            ],
            [
                'title' => 'Finance Manager',
                'description' => 'Manages financial operations and reporting',
                'base_salary' => 55000.00,
                'salary_type' => 'monthly',
                'is_active' => true,
            ],
            [
                'title' => 'Accountant',
                'description' => 'Handles accounting and bookkeeping tasks',
                'base_salary' => 30000.00,
                'salary_type' => 'monthly',
                'is_active' => true,
            ],
            [
                'title' => 'Marketing Manager',
                'description' => 'Manages marketing strategies and campaigns',
                'base_salary' => 50000.00,
                'salary_type' => 'monthly',
                'is_active' => true,
            ],
            [
                'title' => 'Marketing Assistant',
                'description' => 'Assists with marketing activities and campaigns',
                'base_salary' => 22000.00,
                'salary_type' => 'monthly',
                'is_active' => true,
            ],
            [
                'title' => 'Operations Manager',
                'description' => 'Manages daily operations and processes',
                'base_salary' => 50000.00,
                'salary_type' => 'monthly',
                'is_active' => true,
            ],
            [
                'title' => 'Administrative Assistant',
                'description' => 'Provides administrative support',
                'base_salary' => 20000.00,
                'salary_type' => 'monthly',
                'is_active' => true,
            ],
            [
                'title' => 'Part-time Staff',
                'description' => 'Part-time employee with hourly compensation',
                'base_salary' => 200.00,
                'salary_type' => 'hourly',
                'is_active' => true,
            ],
        ];

        foreach ($positions as $pos) {
            Position::firstOrCreate(['title' => $pos['title']], $pos);
        }

        // Create Work Schedules
        $workSchedules = [
            [
                'name' => 'Regular Schedule (8-5)',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'break_start' => '12:00:00',
                'break_end' => '13:00:00',
                'work_hours_per_day' => 8,
                'work_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
                'is_flexible' => false,
                'grace_period_minutes' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Early Schedule (7-4)',
                'start_time' => '07:00:00',
                'end_time' => '16:00:00',
                'break_start' => '12:00:00',
                'break_end' => '13:00:00',
                'work_hours_per_day' => 8,
                'work_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
                'is_flexible' => false,
                'grace_period_minutes' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Late Schedule (9-6)',
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'break_start' => '12:00:00',
                'break_end' => '13:00:00',
                'work_hours_per_day' => 8,
                'work_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
                'is_flexible' => false,
                'grace_period_minutes' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Flexible Schedule',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'break_start' => '12:00:00',
                'break_end' => '13:00:00',
                'work_hours_per_day' => 8,
                'work_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
                'is_flexible' => true,
                'grace_period_minutes' => 30,
                'is_active' => true,
            ],
            [
                'name' => 'Part-time Schedule (4 hours)',
                'start_time' => '08:00:00',
                'end_time' => '12:00:00',
                'break_start' => '10:00:00',
                'break_end' => '10:15:00',
                'work_hours_per_day' => 4,
                'work_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
                'is_flexible' => false,
                'grace_period_minutes' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($workSchedules as $schedule) {
            WorkSchedule::firstOrCreate(['name' => $schedule['name']], $schedule);
        }

        // Create Holidays for 2025 (Philippines)
        $holidays2025 = [
            [
                'name' => 'New Year\'s Day',
                'date' => '2025-01-01',
                'type' => 'regular',
                'rate_multiplier' => 2.00,
                'description' => 'Regular Holiday',
                'is_recurring' => true,
                'is_active' => true,
                'year' => 2025,
            ],
            [
                'name' => 'People Power Anniversary',
                'date' => '2025-02-25',
                'type' => 'special_non_working',
                'rate_multiplier' => 1.30,
                'description' => 'Special Non-Working Holiday',
                'is_recurring' => true,
                'is_active' => true,
                'year' => 2025,
            ],
            [
                'name' => 'Maundy Thursday',
                'date' => '2025-04-17',
                'type' => 'regular',
                'rate_multiplier' => 2.00,
                'description' => 'Regular Holiday',
                'is_recurring' => false,
                'is_active' => true,
                'year' => 2025,
            ],
            [
                'name' => 'Good Friday',
                'date' => '2025-04-18',
                'type' => 'regular',
                'rate_multiplier' => 2.00,
                'description' => 'Regular Holiday',
                'is_recurring' => false,
                'is_active' => true,
                'year' => 2025,
            ],
            [
                'name' => 'Araw ng Kagitingan',
                'date' => '2025-04-09',
                'type' => 'regular',
                'rate_multiplier' => 2.00,
                'description' => 'Regular Holiday',
                'is_recurring' => true,
                'is_active' => true,
                'year' => 2025,
            ],
            [
                'name' => 'Labor Day',
                'date' => '2025-05-01',
                'type' => 'regular',
                'rate_multiplier' => 2.00,
                'description' => 'Regular Holiday',
                'is_recurring' => true,
                'is_active' => true,
                'year' => 2025,
            ],
            [
                'name' => 'Independence Day',
                'date' => '2025-06-12',
                'type' => 'regular',
                'rate_multiplier' => 2.00,
                'description' => 'Regular Holiday',
                'is_recurring' => true,
                'is_active' => true,
                'year' => 2025,
            ],
            [
                'name' => 'National Heroes Day',
                'date' => '2025-08-25',
                'type' => 'regular',
                'rate_multiplier' => 2.00,
                'description' => 'Regular Holiday',
                'is_recurring' => true,
                'is_active' => true,
                'year' => 2025,
            ],
            [
                'name' => 'All Saints\' Day',
                'date' => '2025-11-01',
                'type' => 'special_non_working',
                'rate_multiplier' => 1.30,
                'description' => 'Special Non-Working Holiday',
                'is_recurring' => true,
                'is_active' => true,
                'year' => 2025,
            ],
            [
                'name' => 'Bonifacio Day',
                'date' => '2025-11-30',
                'type' => 'regular',
                'rate_multiplier' => 2.00,
                'description' => 'Regular Holiday',
                'is_recurring' => true,
                'is_active' => true,
                'year' => 2025,
            ],
            [
                'name' => 'Christmas Day',
                'date' => '2025-12-25',
                'type' => 'regular',
                'rate_multiplier' => 2.00,
                'description' => 'Regular Holiday',
                'is_recurring' => true,
                'is_active' => true,
                'year' => 2025,
            ],
            [
                'name' => 'Rizal Day',
                'date' => '2025-12-30',
                'type' => 'regular',
                'rate_multiplier' => 2.00,
                'description' => 'Regular Holiday',
                'is_recurring' => true,
                'is_active' => true,
                'year' => 2025,
            ],
        ];

        foreach ($holidays2025 as $holiday) {
            Holiday::firstOrCreate(
                ['name' => $holiday['name'], 'year' => $holiday['year']], 
                $holiday
            );
        }

        $this->command->info('Basic data seeded successfully!');
        $this->command->info('Created: ' . count($departments) . ' departments');
        $this->command->info('Created: ' . count($positions) . ' positions');
        $this->command->info('Created: ' . count($workSchedules) . ' work schedules');
        $this->command->info('Created: ' . count($holidays2025) . ' holidays for 2025');
    }
}
