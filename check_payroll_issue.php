<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Checking Payroll Data Issue ===\n\n";

$payroll = App\Models\Payroll::with('payrollDetails.employee.user')->first();
$detail = $payroll->payrollDetails->first();

echo "Employee: " . $detail->employee->user->name . "\n";
echo "Regular Pay: ₱" . number_format($detail->regular_pay, 2) . "\n";
echo "Regular Hours (from payroll detail): " . $detail->regular_hours . "\n";
echo "Overtime Hours: " . $detail->overtime_hours . "\n";
echo "Total Hours: " . $detail->total_hours . "\n\n";

// Check actual time logs for this employee in this period
echo "=== Time Logs for this Employee in Payroll Period ===\n";
$timeLogs = App\Models\TimeLog::where('employee_id', $detail->employee_id)
    ->whereBetween('log_date', [$payroll->period_start, $payroll->period_end])
    ->get();

echo "Time logs found: " . $timeLogs->count() . "\n";
$totalHours = 0;
foreach ($timeLogs as $log) {
    echo "Date: " . $log->log_date . " - Regular Hours: " . $log->regular_hours . "\n";
    $totalHours += $log->regular_hours;
}
echo "Total Regular Hours from Time Logs: " . $totalHours . "\n\n";

// Check if the issue is in recalculation
echo "=== Testing PayrollController calculateGrossPay ===\n";
$controller = new App\Http\Controllers\PayrollController();
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('calculateGrossPay');
$method->setAccessible(true);

$grossPay = $method->invoke($controller, $detail->employee, $payroll->period_start, $payroll->period_end);
echo "Calculated Gross Pay: ₱" . number_format($grossPay, 2) . "\n";

// Check if the payroll needs recalculation
echo "\n=== Recommendation ===\n";
if ($totalHours == 0 && $detail->regular_pay > 0) {
    echo "⚠️  ISSUE FOUND: Employee has 0 working hours but ₱" . number_format($detail->regular_pay, 2) . " regular pay\n";
    echo "💡 Solution: Recalculate the payroll to fix the incorrect payment\n";
} else if ($totalHours > 0 && $detail->regular_pay == 0) {
    echo "⚠️  ISSUE FOUND: Employee has " . $totalHours . " working hours but ₱0 regular pay\n";
    echo "💡 Solution: Recalculate the payroll to add the missing payment\n";
} else {
    echo "✅ Data looks consistent\n";
}
