<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\Department;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get department IDs
        $departments = Department::all()->keyBy('code');

        $positions = [
            // Human Resources
            [
                'department_id' => $departments['HR']->id,
                'title' => 'HR Manager',
                'description' => 'Oversees HR operations and strategic initiatives',
                'base_salary' => 45000.00,
                'salary_type' => 'monthly',
                'is_active' => true,
            ],
            [
                'department_id' => $departments['HR']->id,
                'title' => 'HR Specialist',
                'description' => 'Handles recruitment, employee relations, and HR processes',
                'base_salary' => 25000.00,
                'salary_type' => 'monthly',
                'is_active' => true,
            ],
            
            // Information Technology
            [
                'department_id' => $departments['IT']->id,
                'title' => 'IT Manager',
                'description' => 'Leads IT strategy and technology initiatives',
                'base_salary' => 55000.00,
                'salary_type' => 'monthly',
                'is_active' => true,
            ],
            [
                'department_id' => $departments['IT']->id,
                'title' => 'Software Developer',
                'description' => 'Develops and maintains software applications',
                'base_salary' => 35000.00,
                'salary_type' => 'monthly',
                'is_active' => true,
            ],
            
            // Finance and Accounting
            [
                'department_id' => $departments['FIN']->id,
                'title' => 'Finance Manager',
                'description' => 'Oversees financial operations and strategic planning',
                'base_salary' => 50000.00,
                'salary_type' => 'monthly',
                'is_active' => true,
            ],
            
            // Operations
            [
                'department_id' => $departments['OPS']->id,
                'title' => 'Operations Manager',
                'description' => 'Oversees daily operations and process optimization',
                'base_salary' => 45000.00,
                'salary_type' => 'monthly',
                'is_active' => true,
            ],
        ];

        foreach ($positions as $position) {
            Position::create($position);
        }

        $this->command->info('✅ Positions seeded successfully!');
    }
}
