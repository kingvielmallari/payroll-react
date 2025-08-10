<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$payroll = App\Models\Payroll::first();
$employee = $payroll->payrollDetails->first()->employee;

echo "Testing employee: " . $employee->user->name . "\n";
echo "Employee ID: " . $employee->id . "\n";
echo "Payroll period: " . $payroll->period_start . " to " . $payroll->period_end . "\n\n";

$timeLogs = App\Models\TimeLog::where('employee_id', $employee->id)
    ->whereBetween('log_date', [$payroll->period_start, $payroll->period_end])
    ->get();

echo "Time logs found: " . $timeLogs->count() . "\n\n";

foreach ($timeLogs as $log) {
    echo "Date: " . $log->log_date . "\n";
    echo "  Time In: " . ($log->time_in ? \Carbon\Carbon::parse($log->time_in)->format('H:i') : 'null') . "\n";
    echo "  Time Out: " . ($log->time_out ? \Carbon\Carbon::parse($log->time_out)->format('H:i') : 'null') . "\n";
    echo "  Break In: " . ($log->break_in ? \Carbon\Carbon::parse($log->break_in)->format('H:i') : 'null') . "\n";
    echo "  Break Out: " . ($log->break_out ? \Carbon\Carbon::parse($log->break_out)->format('H:i') : 'null') . "\n";
    echo "  Regular Hours: " . $log->regular_hours . "\n";
    echo "  Status: " . $log->status . "\n\n";
}

// Test the DTR data generation method
echo "=== Testing Enhanced DTR Data Generation ===\n";
$controller = new App\Http\Controllers\TimeLogController();
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('generateDTRDataForPeriod');
$method->setAccessible(true);

$dtrData = $method->invoke($controller, $employee, $payroll->period_start->format('Y-m-d'), $payroll->period_end->format('Y-m-d'));

echo "DTR Data generated: " . count($dtrData) . " days\n\n";

foreach ($dtrData as $day) {
    echo "Date: " . $day['date']->format('Y-m-d') . " (" . $day['day_name'] . ")\n";
    if ($day['time_log']) {
        echo "  HAS EXISTING TIME LOG\n";
        echo "  Time In: " . ($day['time_in'] ? $day['time_in']->format('H:i') : 'null') . "\n";
        echo "  Time Out: " . ($day['time_out'] ? $day['time_out']->format('H:i') : 'null') . "\n";
        echo "  Break In: " . ($day['break_in'] ? $day['break_in']->format('H:i') : 'null') . "\n";
        echo "  Break Out: " . ($day['break_out'] ? $day['break_out']->format('H:i') : 'null') . "\n";
        echo "  Regular Hours: " . $day['regular_hours'] . "\n";
    } else {
        echo "  No time log (will show empty fields)\n";
    }
    echo "\n";
}
