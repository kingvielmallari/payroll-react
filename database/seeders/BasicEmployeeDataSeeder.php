<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Position;
use App\Models\WorkSchedule;

class BasicEmployeeDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample departments
        $departments = [
            [
                'name' => 'Human Resources',
                'code' => 'HR',
                'description' => 'Human Resources Department',
                'is_active' => true,
            ],
            [
                'name' => 'Information Technology',
                'code' => 'IT',
                'description' => 'Information Technology Department',
                'is_active' => true,
            ],
            [
                'name' => 'Finance',
                'code' => 'FIN',
                'description' => 'Finance Department',
                'is_active' => true,
            ],
            [
                'name' => 'Operations',
                'code' => 'OPS',
                'description' => 'Operations Department',
                'is_active' => true,
            ],
            [
                'name' => 'Marketing',
                'code' => 'MKT',
                'description' => 'Marketing Department',
                'is_active' => true,
            ],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(
                ['code' => $department['code']],
                $department
            );
        }

        // Get created departments for assigning to positions
        $hrDept = Department::where('code', 'HR')->first();
        $itDept = Department::where('code', 'IT')->first();
        $finDept = Department::where('code', 'FIN')->first();
        $opsDept = Department::where('code', 'OPS')->first();
        $mktDept = Department::where('code', 'MKT')->first();

        // Create sample positions with department assignments
        $positions = [
            [
                'title' => 'HR Manager',
                'description' => 'Human Resources Manager',
                'base_salary' => 50000.00,
                'salary_type' => 'monthly',
                'department_id' => $hrDept->id,
                'is_active' => true,
            ],
            [
                'title' => 'IT Manager',
                'description' => 'Information Technology Manager',
                'base_salary' => 55000.00,
                'salary_type' => 'monthly',
                'department_id' => $itDept->id,
                'is_active' => true,
            ],
            [
                'title' => 'Finance Manager',
                'description' => 'Finance Department Manager',
                'base_salary' => 52000.00,
                'salary_type' => 'monthly',
                'department_id' => $finDept->id,
                'is_active' => true,
            ],
            [
                'title' => 'Operations Manager',
                'description' => 'Operations Department Manager',
                'base_salary' => 48000.00,
                'salary_type' => 'monthly',
                'department_id' => $opsDept->id,
                'is_active' => true,
            ],
            [
                'title' => 'Marketing Manager',
                'description' => 'Marketing Department Manager',
                'base_salary' => 47000.00,
                'salary_type' => 'monthly',
                'department_id' => $mktDept->id,
                'is_active' => true,
            ],
            [
                'title' => 'Software Developer',
                'description' => 'Senior Software Developer',
                'base_salary' => 35000.00,
                'salary_type' => 'monthly',
                'department_id' => $itDept->id,
                'is_active' => true,
            ],
            [
                'title' => 'HR Officer',
                'description' => 'Human Resources Officer',
                'base_salary' => 25000.00,
                'salary_type' => 'monthly',
                'department_id' => $hrDept->id,
                'is_active' => true,
            ],
            [
                'title' => 'Accountant',
                'description' => 'Staff Accountant',
                'base_salary' => 28000.00,
                'salary_type' => 'monthly',
                'department_id' => $finDept->id,
                'is_active' => true,
            ],
            [
                'title' => 'Marketing Associate',
                'description' => 'Marketing Associate',
                'base_salary' => 22000.00,
                'salary_type' => 'monthly',
                'department_id' => $mktDept->id,
                'is_active' => true,
            ],
            [
                'title' => 'Operations Clerk',
                'description' => 'Operations Administrative Clerk',
                'base_salary' => 18000.00,
                'salary_type' => 'monthly',
                'department_id' => $opsDept->id,
                'is_active' => true,
            ],
        ];

        foreach ($positions as $position) {
            Position::updateOrCreate(
                ['title' => $position['title'], 'department_id' => $position['department_id']],
                $position
            );
        }

        // Create sample work schedules
        $workSchedules = [
            [
                'name' => 'Regular Office Hours',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'break_start' => '12:00:00',
                'break_end' => '13:00:00',
                'work_hours_per_day' => 8,
                'work_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
                'is_flexible' => false,
                'grace_period_minutes' => 15,
                'is_active' => true,
            ],
            [
                'name' => 'Morning Shift',
                'start_time' => '06:00:00',
                'end_time' => '14:00:00',
                'break_start' => '10:00:00',
                'break_end' => '10:30:00',
                'work_hours_per_day' => 7.5,
                'work_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
                'is_flexible' => false,
                'grace_period_minutes' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Evening Shift',
                'start_time' => '14:00:00',
                'end_time' => '22:00:00',
                'break_start' => '18:00:00',
                'break_end' => '19:00:00',
                'work_hours_per_day' => 7,
                'work_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
                'is_flexible' => false,
                'grace_period_minutes' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Flexible Hours',
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'break_start' => '12:00:00',
                'break_end' => '13:00:00',
                'work_hours_per_day' => 8,
                'work_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
                'is_flexible' => true,
                'grace_period_minutes' => 30,
                'is_active' => true,
            ],
            [
                'name' => '6-Day Schedule',
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'break_start' => '12:00:00',
                'break_end' => '13:00:00',
                'work_hours_per_day' => 8,
                'work_days' => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']),
                'is_flexible' => false,
                'grace_period_minutes' => 15,
                'is_active' => true,
            ],
        ];

        foreach ($workSchedules as $schedule) {
            WorkSchedule::updateOrCreate(
                ['name' => $schedule['name']],
                $schedule
            );
        }
    }
}
