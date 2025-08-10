<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;
use App\Models\WorkSchedule;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Skip if admin user already exists
        if (User::where('email', 'admin@payroll.com')->exists()) {
            $this->command->info('System Administrator already exists, skipping user seeder...');
            return;
        }

        // Get departments and positions  
        $itDept = Department::where('code', 'IT')->first();
        $itManagerPos = Position::where('title', 'IT Manager')->first();
        $regularSchedule = WorkSchedule::where('name', 'Regular Schedule (8-5)')->first();

        // Create only System Administrator
        $adminUser = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@payroll.com',
            'password' => Hash::make('password'),
            'employee_id' => 'EMP-2025-0001',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $adminUser->assignRole('System Admin');

        $adminEmployee = Employee::create([
            'user_id' => $adminUser->id,
            'department_id' => $itDept->id,
            'position_id' => $itManagerPos->id,
            'employee_number' => 'EMP-2025-0001',
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'birth_date' => '1990-01-01',
            'gender' => 'male',
            'civil_status' => 'single',
            'phone' => '09123456789',
            'address' => 'Admin Office, Company Building',
            'hire_date' => '2025-01-01',
            'employment_type' => 'regular',
            'employment_status' => 'active',
            'pay_schedule' => 'monthly',
            'basic_salary' => 50000.00,
            'hourly_rate' => 284.09,
            'daily_rate' => 2272.73,
            'weekly_rate' => 11363.64,
            'semi_monthly_rate' => 25000.00,
            'sss_number' => '1234567890',
            'philhealth_number' => '1234567890123',
            'pagibig_number' => '1234567890123',
            'tin_number' => '123-456-789-000',
        ]);

        $adminEmployee->workSchedules()->attach($regularSchedule->id, [
            'effective_date' => '2025-01-01',
            'is_active' => true,
        ]);

        $this->command->info('System Administrator seeded successfully!');
        $this->command->info('=== LOGIN CREDENTIALS ===');
        $this->command->info('System Admin: admin@payroll.com / password');
        $this->command->info('Note: This is the only user account that cannot be deleted.');
    }
}
