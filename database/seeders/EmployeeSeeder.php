<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use App\Models\Department;
use App\Models\Position;
use App\Models\TimeSchedule;
use App\Models\DaySchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get seeded data
        $departments = Department::all();
        $positions = Position::all();
        $timeSchedules = TimeSchedule::all();
        $daySchedules = DaySchedule::all();

        $employees = [
            [
                'user_data' => [
                    'name' => 'Juan Dela Cruz',
                    'email' => 'juan.delacruz@company.com',
                    'password' => Hash::make('password'),
                ],
                'employee_data' => [
                    'employee_number' => 'EMP-2025-0001',
                    'first_name' => 'Juan',
                    'middle_name' => 'Santos',
                    'last_name' => 'Dela Cruz',
                    'suffix' => null,
                    'birth_date' => '1990-05-15',
                    'gender' => 'male',
                    'civil_status' => 'married',
                    'phone' => '+639171234567',
                    'address' => '123 Rizal Street, Quezon City, Metro Manila',
                    'hire_date' => '2023-01-15',
                    'employment_type' => 'regular',
                    'employment_status' => 'active',
                    'pay_schedule' => 'monthly',
                    'basic_salary' => 35000.00,
                    'sss_number' => '123456789',
                    'philhealth_number' => '123456789012',
                    'pagibig_number' => '123456789012',
                    'tin_number' => '123-456-789-000',
                    'emergency_contact_name' => 'Maria Dela Cruz',
                    'emergency_contact_relationship' => 'Spouse',
                    'emergency_contact_phone' => '+639181234567',
                    'bank_name' => 'BDO',
                    'bank_account_number' => '1234567890',
                    'bank_account_name' => 'Juan Santos Dela Cruz',
                ],
            ],
            [
                'user_data' => [
                    'name' => 'Maria Santos',
                    'email' => 'maria.santos@company.com',
                    'password' => Hash::make('password'),
                ],
                'employee_data' => [
                    'employee_number' => 'EMP-2025-0002',
                    'first_name' => 'Maria',
                    'middle_name' => 'Garcia',
                    'last_name' => 'Santos',
                    'suffix' => null,
                    'birth_date' => '1992-08-22',
                    'gender' => 'female',
                    'civil_status' => 'single',
                    'phone' => '+639181234568',
                    'address' => '456 Bonifacio Avenue, Makati City, Metro Manila',
                    'hire_date' => '2023-03-01',
                    'employment_type' => 'regular',
                    'employment_status' => 'active',
                    'pay_schedule' => 'semi_monthly',
                    'basic_salary' => 28000.00,
                    'sss_number' => '234567890',
                    'philhealth_number' => '234567890123',
                    'pagibig_number' => '234567890123',
                    'tin_number' => '234-567-890-000',
                    'emergency_contact_name' => 'Rosa Santos',
                    'emergency_contact_relationship' => 'Mother',
                    'emergency_contact_phone' => '+639191234568',
                    'bank_name' => 'BPI',
                    'bank_account_number' => '2345678901',
                    'bank_account_name' => 'Maria Garcia Santos',
                ],
            ],
            [
                'user_data' => [
                    'name' => 'Roberto Garcia',
                    'email' => 'roberto.garcia@company.com',
                    'password' => Hash::make('password'),
                ],
                'employee_data' => [
                    'employee_number' => 'EMP-2025-0003',
                    'first_name' => 'Roberto',
                    'middle_name' => 'Cruz',
                    'last_name' => 'Garcia',
                    'suffix' => 'Jr.',
                    'birth_date' => '1988-12-10',
                    'gender' => 'male',
                    'civil_status' => 'married',
                    'phone' => '+639201234569',
                    'address' => '789 Taft Avenue, Manila City, Metro Manila',
                    'hire_date' => '2022-06-15',
                    'employment_type' => 'regular',
                    'employment_status' => 'active',
                    'pay_schedule' => 'monthly',
                    'basic_salary' => 55000.00,
                    'sss_number' => '345678901',
                    'philhealth_number' => '345678901234',
                    'pagibig_number' => '345678901234',
                    'tin_number' => '345-678-901-000',
                    'emergency_contact_name' => 'Carmen Garcia',
                    'emergency_contact_relationship' => 'Wife',
                    'emergency_contact_phone' => '+639211234569',
                    'bank_name' => 'Metrobank',
                    'bank_account_number' => '3456789012',
                    'bank_account_name' => 'Roberto Cruz Garcia Jr.',
                ],
            ],
            [
                'user_data' => [
                    'name' => 'Ana Reyes',
                    'email' => 'ana.reyes@company.com',
                    'password' => Hash::make('password'),
                ],
                'employee_data' => [
                    'employee_number' => 'EMP-2025-0004',
                    'first_name' => 'Ana',
                    'middle_name' => 'Mendoza',
                    'last_name' => 'Reyes',
                    'suffix' => null,
                    'birth_date' => '1995-03-18',
                    'gender' => 'female',
                    'civil_status' => 'single',
                    'phone' => '+639221234570',
                    'address' => '321 EDSA, Pasig City, Metro Manila',
                    'hire_date' => '2023-08-01',
                    'employment_type' => 'regular',
                    'employment_status' => 'active',
                    'pay_schedule' => 'weekly',
                    'basic_salary' => 18000.00,
                    'sss_number' => '456789012',
                    'philhealth_number' => '456789012345',
                    'pagibig_number' => '456789012345',
                    'tin_number' => '456-789-012-000',
                    'emergency_contact_name' => 'Pedro Reyes',
                    'emergency_contact_relationship' => 'Father',
                    'emergency_contact_phone' => '+639231234570',
                    'bank_name' => 'Unionbank',
                    'bank_account_number' => '4567890123',
                    'bank_account_name' => 'Ana Mendoza Reyes',
                ],
            ],
            [
                'user_data' => [
                    'name' => 'Michael Torres',
                    'email' => 'michael.torres@company.com',
                    'password' => Hash::make('password'),
                ],
                'employee_data' => [
                    'employee_number' => 'EMP-2025-0005',
                    'first_name' => 'Michael',
                    'middle_name' => 'Rodriguez',
                    'last_name' => 'Torres',
                    'suffix' => null,
                    'birth_date' => '1991-07-05',
                    'gender' => 'male',
                    'civil_status' => 'divorced',
                    'phone' => '+639241234571',
                    'address' => '654 Ortigas Avenue, Mandaluyong City, Metro Manila',
                    'hire_date' => '2023-02-20',
                    'employment_type' => 'regular',
                    'employment_status' => 'active',
                    'pay_schedule' => 'semi_monthly',
                    'basic_salary' => 30000.00,
                    'sss_number' => '567890123',
                    'philhealth_number' => '567890123456',
                    'pagibig_number' => '567890123456',
                    'tin_number' => '567-890-123-000',
                    'emergency_contact_name' => 'Elena Torres',
                    'emergency_contact_relationship' => 'Mother',
                    'emergency_contact_phone' => '+639251234571',
                    'bank_name' => 'Security Bank',
                    'bank_account_number' => '5678901234',
                    'bank_account_name' => 'Michael Rodriguez Torres',
                ],
            ],
            [
                'user_data' => [
                    'name' => 'Catherine Flores',
                    'email' => 'catherine.flores@company.com',
                    'password' => Hash::make('password'),
                ],
                'employee_data' => [
                    'employee_number' => 'EMP-2025-0006',
                    'first_name' => 'Catherine',
                    'middle_name' => 'Luna',
                    'last_name' => 'Flores',
                    'suffix' => null,
                    'birth_date' => '1993-11-12',
                    'gender' => 'female',
                    'civil_status' => 'married',
                    'phone' => '+639261234572',
                    'address' => '987 Shaw Boulevard, Pasig City, Metro Manila',
                    'hire_date' => '2022-11-10',
                    'employment_type' => 'regular',
                    'employment_status' => 'active',
                    'pay_schedule' => 'monthly',
                    'basic_salary' => 40000.00,
                    'sss_number' => '678901234',
                    'philhealth_number' => '678901234567',
                    'pagibig_number' => '678901234567',
                    'tin_number' => '678-901-234-000',
                    'emergency_contact_name' => 'James Flores',
                    'emergency_contact_relationship' => 'Husband',
                    'emergency_contact_phone' => '+639271234572',
                    'bank_name' => 'PNB',
                    'bank_account_number' => '6789012345',
                    'bank_account_name' => 'Catherine Luna Flores',
                ],
            ],
            [
                'user_data' => [
                    'name' => 'David Martinez',
                    'email' => 'david.martinez@company.com',
                    'password' => Hash::make('password'),
                ],
                'employee_data' => [
                    'employee_number' => 'EMP-2025-0007',
                    'first_name' => 'David',
                    'middle_name' => 'Gonzales',
                    'last_name' => 'Martinez',
                    'suffix' => null,
                    'birth_date' => '1989-09-28',
                    'gender' => 'male',
                    'civil_status' => 'single',
                    'phone' => '+639281234573',
                    'address' => '147 C5 Road, Taguig City, Metro Manila',
                    'hire_date' => '2023-05-15',
                    'employment_type' => 'probationary',
                    'employment_status' => 'active',
                    'pay_schedule' => 'monthly',
                    'basic_salary' => 25000.00,
                    'sss_number' => '789012345',
                    'philhealth_number' => '789012345678',
                    'pagibig_number' => '789012345678',
                    'tin_number' => '789-012-345-000',
                    'emergency_contact_name' => 'Isabel Martinez',
                    'emergency_contact_relationship' => 'Sister',
                    'emergency_contact_phone' => '+639291234573',
                    'bank_name' => 'BDO',
                    'bank_account_number' => '7890123456',
                    'bank_account_name' => 'David Gonzales Martinez',
                ],
            ],
            [
                'user_data' => [
                    'name' => 'Jennifer Villanueva',
                    'email' => 'jennifer.villanueva@company.com',
                    'password' => Hash::make('password'),
                ],
                'employee_data' => [
                    'employee_number' => 'EMP-2025-0008',
                    'first_name' => 'Jennifer',
                    'middle_name' => 'Pascual',
                    'last_name' => 'Villanueva',
                    'suffix' => null,
                    'birth_date' => '1994-01-20',
                    'gender' => 'female',
                    'civil_status' => 'single',
                    'phone' => '+639301234574',
                    'address' => '852 Katipunan Avenue, Quezon City, Metro Manila',
                    'hire_date' => '2023-07-01',
                    'employment_type' => 'contractual',
                    'employment_status' => 'active',
                    'pay_schedule' => 'weekly',
                    'basic_salary' => 22000.00,
                    'sss_number' => '890123456',
                    'philhealth_number' => '890123456789',
                    'pagibig_number' => '890123456789',
                    'tin_number' => '890-123-456-000',
                    'emergency_contact_name' => 'Rosario Villanueva',
                    'emergency_contact_relationship' => 'Mother',
                    'emergency_contact_phone' => '+639311234574',
                    'bank_name' => 'BPI',
                    'bank_account_number' => '8901234567',
                    'bank_account_name' => 'Jennifer Pascual Villanueva',
                ],
            ],
            [
                'user_data' => [
                    'name' => 'Carlos Mendoza',
                    'email' => 'carlos.mendoza@company.com',
                    'password' => Hash::make('password'),
                ],
                'employee_data' => [
                    'employee_number' => 'EMP-2025-0009',
                    'first_name' => 'Carlos',
                    'middle_name' => 'Herrera',
                    'last_name' => 'Mendoza',
                    'suffix' => 'III',
                    'birth_date' => '1987-04-14',
                    'gender' => 'male',
                    'civil_status' => 'married',
                    'phone' => '+639321234575',
                    'address' => '963 Alabang-Zapote Road, Las Piñas City, Metro Manila',
                    'hire_date' => '2022-09-05',
                    'employment_type' => 'regular',
                    'employment_status' => 'active',
                    'pay_schedule' => 'semi_monthly',
                    'basic_salary' => 50000.00,
                    'sss_number' => '901234567',
                    'philhealth_number' => '901234567890',
                    'pagibig_number' => '901234567890',
                    'tin_number' => '901-234-567-000',
                    'emergency_contact_name' => 'Patricia Mendoza',
                    'emergency_contact_relationship' => 'Wife',
                    'emergency_contact_phone' => '+639331234575',
                    'bank_name' => 'Metrobank',
                    'bank_account_number' => '9012345678',
                    'bank_account_name' => 'Carlos Herrera Mendoza III',
                ],
            ],
            [
                'user_data' => [
                    'name' => 'Stephanie Cruz',
                    'email' => 'stephanie.cruz@company.com',
                    'password' => Hash::make('password'),
                ],
                'employee_data' => [
                    'employee_number' => 'EMP-2025-0010',
                    'first_name' => 'Stephanie',
                    'middle_name' => 'Ramos',
                    'last_name' => 'Cruz',
                    'suffix' => null,
                    'birth_date' => '1996-06-30',
                    'gender' => 'female',
                    'civil_status' => 'single',
                    'phone' => '+639341234576',
                    'address' => '741 Roxas Boulevard, Parañaque City, Metro Manila',
                    'hire_date' => '2023-10-01',
                    'employment_type' => 'part_time',
                    'employment_status' => 'active',
                    'pay_schedule' => 'weekly',
                    'basic_salary' => 15000.00,
                    'sss_number' => '012345678',
                    'philhealth_number' => '012345678901',
                    'pagibig_number' => '012345678901',
                    'tin_number' => '012-345-678-000',
                    'emergency_contact_name' => 'Antonio Cruz',
                    'emergency_contact_relationship' => 'Father',
                    'emergency_contact_phone' => '+639351234576',
                    'bank_name' => 'Unionbank',
                    'bank_account_number' => '0123456789',
                    'bank_account_name' => 'Stephanie Ramos Cruz',
                ],
            ],
        ];

        foreach ($employees as $index => $employeeInfo) {
            // Create user first
            $user = User::create($employeeInfo['user_data']);

            // Assign departments and positions in round-robin fashion
            $departmentIndex = $index % $departments->count();
            $department = $departments[$departmentIndex];
            
            // Get positions for this department
            $departmentPositions = $positions->where('department_id', $department->id);
            if ($departmentPositions->count() > 0) {
                $positionIndex = $index % $departmentPositions->count();
                $position = $departmentPositions->values()[$positionIndex];
            } else {
                // Fallback to any position if no positions for this department
                $position = $positions->first();
            }

            // Assign time and day schedules in round-robin fashion
            $timeSchedule = $timeSchedules[($index % $timeSchedules->count())];
            $daySchedule = $daySchedules[($index % $daySchedules->count())];

            // Calculate hourly rate and daily rate based on basic salary
            $basicSalary = $employeeInfo['employee_data']['basic_salary'];
            $workingDaysPerMonth = 22; // Average working days per month
            $workingHoursPerDay = 8; // Standard 8-hour work day
            
            // Calculate rates
            $dailyRate = round($basicSalary / $workingDaysPerMonth, 2);
            $hourlyRate = round($dailyRate / $workingHoursPerDay, 2);
            
            // Add some randomness to hourly rate (±10%)
            $randomFactor = mt_rand(90, 110) / 100; // Random factor between 0.9 and 1.1
            $hourlyRate = round($hourlyRate * $randomFactor, 2);

            // Create employee
            $employeeData = array_merge($employeeInfo['employee_data'], [
                'user_id' => $user->id,
                'department_id' => $department->id,
                'position_id' => $position->id,
                'time_schedule_id' => $timeSchedule->id,
                'day_schedule_id' => $daySchedule->id,
                'hourly_rate' => $hourlyRate,
                'daily_rate' => $dailyRate,
            ]);

            Employee::create($employeeData);
        }

        $this->command->info('✅ 10 Employees seeded successfully!');
    }
}
