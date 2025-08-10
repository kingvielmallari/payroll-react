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

class AdditionalUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get departments and positions  
        $hrDept = Department::where('code', 'HR')->first();
        $itDept = Department::where('code', 'IT')->first();
        $adminDept = Department::where('code', 'ADMIN')->first();
        
        $hrManagerPos = Position::where('title', 'HR Manager')->first();
        $hrSpecialistPos = Position::where('title', 'HR Specialist')->first();
        $salesExecPos = Position::where('title', 'Sales Executive')->first();
        $accountantPos = Position::where('title', 'Accountant')->first();
        $adminAssistantPos = Position::where('title', 'Administrative Assistant')->first();
        
        $regularSchedule = WorkSchedule::where('name', 'Regular Schedule (8-5)')->first();

        // Check if HR Head user already exists
        if (!User::where('email', 'hr@payroll.com')->exists()) {
            // Create HR Head
            $hrHeadUser = User::create([
                'name' => 'Maria Santos',
                'email' => 'hr@payroll.com',
                'password' => Hash::make('password'),
                'employee_id' => 'EMP-2025-0002',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
            $hrHeadUser->assignRole('HR Head');

            $hrHeadEmployee = Employee::create([
                'user_id' => $hrHeadUser->id,
                'department_id' => $hrDept->id,
                'position_id' => $hrManagerPos->id,
                'employee_number' => 'EMP-2025-0002',
                'first_name' => 'Maria',
                'last_name' => 'Santos',
                'birth_date' => '1985-03-15',
                'gender' => 'female',
                'civil_status' => 'married',
                'phone' => '09234567890',
                'address' => '123 HR Street, Manila City',
                'hire_date' => '2020-03-01',
                'employment_type' => 'regular',
                'employment_status' => 'active',
                'basic_salary' => 65000.00,
                'sss_number' => '2345678901',
                'philhealth_number' => '2345678901234',
                'pagibig_number' => '2345678901234',
                'tin_number' => '234-567-890-001',
            ]);

            $hrHeadEmployee->workSchedules()->attach($regularSchedule->id, [
                'effective_date' => '2020-03-01',
                'is_active' => true,
            ]);

            $this->command->info('HR Head user created: hr@payroll.com');
        }

        // Check if HR Staff user already exists
        if (!User::where('email', 'hrstaff@payroll.com')->exists()) {
            // Create HR Staff
            $hrStaffUser = User::create([
                'name' => 'Juan Dela Cruz',
                'email' => 'hrstaff@payroll.com',
                'password' => Hash::make('password'),
                'employee_id' => 'EMP-2025-0003',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
            $hrStaffUser->assignRole('HR Staff');

            $hrStaffEmployee = Employee::create([
                'user_id' => $hrStaffUser->id,
                'department_id' => $hrDept->id,
                'position_id' => $hrSpecialistPos->id,
                'employee_number' => 'EMP-2025-0003',
                'first_name' => 'Juan',
                'last_name' => 'Dela Cruz',
                'birth_date' => '1992-07-20',
                'gender' => 'male',
                'civil_status' => 'single',
                'phone' => '09345678901',
                'address' => '456 Staff Avenue, Quezon City',
                'hire_date' => '2022-05-15',
                'employment_type' => 'regular',
                'employment_status' => 'active',
                'basic_salary' => 45000.00,
                'sss_number' => '3456789012',
                'philhealth_number' => '3456789012345',
                'pagibig_number' => '3456789012345',
                'tin_number' => '345-678-901-002',
            ]);

            $hrStaffEmployee->workSchedules()->attach($regularSchedule->id, [
                'effective_date' => '2022-05-15',
                'is_active' => true,
            ]);

            $this->command->info('HR Staff user created: hrstaff@payroll.com');
        }

        // Check if Employee 1 already exists
        if (!User::where('email', 'ana@payroll.com')->exists()) {
            // Create Employee 1 - Sales
            $employee1User = User::create([
                'name' => 'Ana Rodriguez',
                'email' => 'ana@payroll.com',
                'password' => Hash::make('password'),
                'employee_id' => 'EMP-2025-0004',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
            $employee1User->assignRole('Employee');

            $employee1 = Employee::create([
                'user_id' => $employee1User->id,
                'department_id' => $adminDept->id,
                'position_id' => $salesExecPos->id,
                'employee_number' => 'EMP-2025-0004',
                'first_name' => 'Ana',
                'last_name' => 'Rodriguez',
                'birth_date' => '1995-11-08',
                'gender' => 'female',
                'civil_status' => 'single',
                'phone' => '09456789012',
                'address' => '789 Sales Street, Makati City',
                'hire_date' => '2023-01-10',
                'employment_type' => 'regular',
                'employment_status' => 'active',
                'basic_salary' => 35000.00,
                'sss_number' => '4567890123',
                'philhealth_number' => '4567890123456',
                'pagibig_number' => '4567890123456',
                'tin_number' => '456-789-012-003',
            ]);

            $employee1->workSchedules()->attach($regularSchedule->id, [
                'effective_date' => '2023-01-10',
                'is_active' => true,
            ]);

            $this->command->info('Employee user created: ana@payroll.com');
        }

        // Check if Employee 2 already exists
        if (!User::where('email', 'carlos@payroll.com')->exists()) {
            // Create Employee 2 - Finance  
            $employee2User = User::create([
                'name' => 'Carlos Mendoza',
                'email' => 'carlos@payroll.com',
                'password' => Hash::make('password'),
                'employee_id' => 'EMP-2025-0005',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
            $employee2User->assignRole('Employee');

            $employee2 = Employee::create([
                'user_id' => $employee2User->id,
                'department_id' => $adminDept->id,
                'position_id' => $accountantPos->id,
                'employee_number' => 'EMP-2025-0005',
                'first_name' => 'Carlos',
                'last_name' => 'Mendoza',
                'birth_date' => '1988-09-12',
                'gender' => 'male',
                'civil_status' => 'married',
                'phone' => '09567890123',
                'address' => '321 Finance Road, Pasig City',
                'hire_date' => '2021-08-01',
                'employment_type' => 'regular',
                'employment_status' => 'active',
                'basic_salary' => 40000.00,
                'sss_number' => '5678901234',
                'philhealth_number' => '5678901234567',
                'pagibig_number' => '5678901234567',
                'tin_number' => '567-890-123-004',
            ]);

            $employee2->workSchedules()->attach($regularSchedule->id, [
                'effective_date' => '2021-08-01',
                'is_active' => true,
            ]);

            $this->command->info('Employee user created: carlos@payroll.com');
        }

        // Check if Employee 3 already exists
        if (!User::where('email', 'lisa@payroll.com')->exists()) {
            // Create Employee 3 - HR Department
            $employee3User = User::create([
                'name' => 'Lisa Garcia',
                'email' => 'lisa@payroll.com',
                'password' => Hash::make('password'),
                'employee_id' => 'EMP-2025-0006',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
            $employee3User->assignRole('Employee');

            $employee3 = Employee::create([
                'user_id' => $employee3User->id,
                'department_id' => $hrDept->id,
                'position_id' => $hrSpecialistPos->id,
                'employee_number' => 'EMP-2025-0006',
                'first_name' => 'Lisa',
                'last_name' => 'Garcia',
                'birth_date' => '1993-04-25',
                'gender' => 'female',
                'civil_status' => 'single',
                'phone' => '09678901234',
                'address' => '654 Employee Lane, Mandaluyong City',
                'hire_date' => '2024-02-14',
                'employment_type' => 'regular',
                'employment_status' => 'active',
                'basic_salary' => 32000.00,
                'sss_number' => '6789012345',
                'philhealth_number' => '6789012345678',
                'pagibig_number' => '6789012345678',
                'tin_number' => '678-901-234-005',
            ]);

            $employee3->workSchedules()->attach($regularSchedule->id, [
                'effective_date' => '2024-02-14',
                'is_active' => true,
            ]);

            $this->command->info('Employee user created: lisa@payroll.com');
        }

        $this->command->info('=== LOGIN CREDENTIALS ===');
        $this->command->info('System Admin: admin@payroll.com / password');
        $this->command->info('HR Head: hr@payroll.com / password');
        $this->command->info('HR Staff: hrstaff@payroll.com / password');
        $this->command->info('Employee 1: ana@payroll.com / password');
        $this->command->info('Employee 2: carlos@payroll.com / password');
        $this->command->info('Employee 3: lisa@payroll.com / password');
    }
}
