<?php
// Quick debug script to test MBS calculation
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Employee;
use Carbon\Carbon;

// Find an employee with hourly rate (like Carlos Mendoza)
$employee = Employee::where('employee_number', 'EMP-2025-0003')->first();

if ($employee) {
    echo "Employee: {$employee->first_name} {$employee->last_name}\n";
    echo "Rate Type: {$employee->rate_type}\n";
    echo "Fixed Rate: ₱" . number_format($employee->fixed_rate, 2) . "\n";

    if ($employee->timeSchedule) {
        echo "Time Schedule: {$employee->timeSchedule->name}\n";
        echo "Total Hours: {$employee->timeSchedule->total_hours}\n";
    }

    $periodStart = Carbon::parse('2025-09-01');
    $periodEnd = Carbon::parse('2025-09-15');

    echo "\nCalculating MBS for period: {$periodStart->format('Y-m-d')} to {$periodEnd->format('Y-m-d')}\n";

    // Get working days in September 2025
    $monthStart = $periodStart->copy()->startOfMonth();
    $monthEnd = $periodStart->copy()->endOfMonth();
    $workingDays = $employee->getWorkingDaysForPeriod($monthStart, $monthEnd);

    echo "Working days in September 2025: {$workingDays}\n";

    if ($employee->timeSchedule && $employee->timeSchedule->total_hours) {
        $hoursPerDay = $employee->timeSchedule->total_hours;
        echo "Hours per day: {$hoursPerDay}\n";
        echo "Expected MBS: ₱" . number_format($employee->fixed_rate * $hoursPerDay * $workingDays, 2) . "\n";
    }

    $calculatedMBS = $employee->calculateMonthlyBasicSalary($periodStart, $periodEnd);
    echo "Calculated MBS: ₱" . number_format($calculatedMBS, 2) . "\n";
} else {
    echo "Employee not found\n";
}
