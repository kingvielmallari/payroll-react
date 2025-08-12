<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Payroll;
use App\Models\TimeLog;
use App\Models\Employee;

echo "Testing Payroll Calculation with Time Logs\n";
echo "==========================================\n\n";

$payrollId = 93;
$employeeId = 12;

$payroll = Payroll::find($payrollId);
$employee = Employee::find($employeeId);

if (!$payroll || !$employee) {
    echo "Payroll or Employee not found!\n";
    exit;
}

echo "Payroll: {$payroll->id} (Status: {$payroll->status})\n";
echo "Employee: {$employee->first_name} {$employee->last_name} (ID: {$employee->id})\n";
echo "Employee hourly rate: ₱" . number_format($employee->hourly_rate ?? 0, 2) . "\n\n";

// Test the query used in the calculation
$query = TimeLog::where('employee_id', $employee->id)
    ->where('payroll_id', $payroll->id);

if ($payroll->status === 'draft') {
    $query->whereIn('status', ['pending', 'approved']);
} else {
    $query->where('status', 'approved');
}

$timeLogs = $query->get();

echo "Time logs found: " . $timeLogs->count() . "\n";

if ($timeLogs->count() > 0) {
    $totalRegularHours = $timeLogs->sum('regular_hours');
    $totalOvertimeHours = $timeLogs->sum('overtime_hours');
    $totalTotalHours = $timeLogs->sum('total_hours');

    echo "Total regular hours: " . $totalRegularHours . "\n";
    echo "Total overtime hours: " . $totalOvertimeHours . "\n";
    echo "Total total hours: " . $totalTotalHours . "\n\n";

    // Show each time log
    echo "Time log details:\n";
    foreach ($timeLogs as $timeLog) {
        echo "  Date: {$timeLog->log_date}, Regular: {$timeLog->regular_hours}, Overtime: {$timeLog->overtime_hours}, Total: {$timeLog->total_hours}, Status: {$timeLog->status}\n";
    }

    // Calculate basic pay
    $hourlyRate = $employee->hourly_rate ?? 0;
    $basicPay = $totalRegularHours * $hourlyRate;
    $overtimePay = $totalOvertimeHours * $hourlyRate * 1.25;

    echo "\nCalculated Pay:\n";
    echo "Basic Pay (Regular Hours × Hourly Rate): ₱" . number_format($basicPay, 2) . " ({$totalRegularHours} hrs × ₱{$hourlyRate})\n";
    echo "Overtime Pay (OT Hours × Hourly Rate × 1.25): ₱" . number_format($overtimePay, 2) . " ({$totalOvertimeHours} hrs × ₱{$hourlyRate} × 1.25)\n";
} else {
    echo "No time logs found matching the criteria.\n";
    echo "Checking all time logs for this employee and payroll:\n";

    $allTimeLogs = TimeLog::where('employee_id', $employee->id)
        ->where('payroll_id', $payroll->id)
        ->get();

    echo "All time logs (any status): " . $allTimeLogs->count() . "\n";
    foreach ($allTimeLogs as $timeLog) {
        echo "  Date: {$timeLog->log_date}, Status: {$timeLog->status}\n";
    }
}
