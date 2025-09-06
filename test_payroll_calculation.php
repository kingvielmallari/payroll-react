<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Employee;
use App\Http\Controllers\PayrollController;

echo "\n=== Testing PayrollController calculateHourlyRate Method ===\n\n";

// Get first active employee to test
$employee = Employee::where('employment_status', 'active')->first();

if (!$employee) {
    echo "No active employees found to test.\n";
    exit;
}

echo "Testing with Employee: {$employee->first_name} {$employee->last_name} (#{$employee->employee_number})\n";
echo "Current hourly_rate: ₱" . number_format($employee->hourly_rate ?? 0, 2) . "\n";
echo "Current basic_salary: ₱" . number_format($employee->basic_salary ?? 0, 2) . "\n";
echo "Fixed rate: ₱" . number_format($employee->fixed_rate ?? 0, 2) . "\n";
echo "Rate type: " . ($employee->rate_type ?? 'not set') . "\n";

// Get time schedule details
$timeSchedule = $employee->timeSchedule;
if ($timeSchedule) {
    echo "Time Schedule: {$timeSchedule->schedule_name}\n";
    echo "Total Hours: {$timeSchedule->total_hours}\n";
} else {
    echo "Time Schedule: Not assigned\n";
}

echo "\n";

// Create PayrollController instance to test the method
$payrollController = new PayrollController();

try {
    // Use reflection to call the private method
    $reflection = new ReflectionClass($payrollController);
    $method = $reflection->getMethod('calculateHourlyRate');
    $method->setAccessible(true);

    $calculatedHourlyRate = $method->invoke($payrollController, $employee, $employee->basic_salary ?? 0);

    echo "=== CALCULATION RESULT ===\n";
    echo "New Calculated Hourly Rate: ₱" . number_format($calculatedHourlyRate, 10) . "\n";

    // Show the calculation breakdown if using fixed_rate and rate_type
    if ($employee->fixed_rate && $employee->rate_type) {
        echo "\n=== CALCULATION BREAKDOWN ===\n";
        $dailyHours = $timeSchedule ? $timeSchedule->total_hours : 8;
        echo "Using Fixed Rate System:\n";
        echo "Fixed Rate: ₱" . number_format($employee->fixed_rate, 2) . "\n";
        echo "Rate Type: {$employee->rate_type}\n";
        echo "Daily Hours: {$dailyHours}\n";

        switch ($employee->rate_type) {
            case 'hourly':
                echo "Calculation: Fixed Rate = ₱" . number_format($employee->fixed_rate, 10) . "\n";
                break;
            case 'daily':
                echo "Calculation: ₱" . number_format($employee->fixed_rate, 10) . " / {$dailyHours} hours = ₱" . number_format($employee->fixed_rate / $dailyHours, 10) . "\n";
                break;
            case 'monthly':
                $dailyRate = $employee->fixed_rate / 22;
                echo "Daily Rate: ₱" . number_format($employee->fixed_rate, 2) . " / 22 days = ₱" . number_format($dailyRate, 10) . "\n";
                echo "Hourly Rate: ₱" . number_format($dailyRate, 10) . " / {$dailyHours} hours = ₱" . number_format($dailyRate / $dailyHours, 10) . "\n";
                break;
        }
    } else {
        echo "\nUsing Legacy Calculation (no fixed_rate/rate_type set)\n";
        if ($employee->hourly_rate) {
            echo "Using existing hourly_rate: ₱" . number_format($employee->hourly_rate, 2) . "\n";
        } else {
            echo "Calculating from basic_salary based on pay_schedule: {$employee->pay_schedule}\n";
        }
    }

    // Test per-minute calculation
    $perMinuteRate = $calculatedHourlyRate / 60;
    echo "\nPer-Minute Rate: ₱" . number_format($perMinuteRate, 10) . "\n";

    // Test for full month (10,560 minutes)
    $fullMonthMinutes = 10560;
    $fullMonthAmount = $perMinuteRate * $fullMonthMinutes;
    echo "Full Month Amount ({$fullMonthMinutes} minutes): ₱" . number_format($fullMonthAmount, 2) . "\n";
} catch (Exception $e) {
    echo "Error testing calculation: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
