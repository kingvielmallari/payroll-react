<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Automatic Time Log Recalculation ===" . PHP_EOL;

// Find a draft payroll to test with
$payroll = App\Models\Payroll::where('status', 'draft')->first();

if (!$payroll) {
    echo "No draft payroll found. Creating a test scenario..." . PHP_EOL;

    // Check if we have any time logs that need recalculation
    $timeLogs = App\Models\TimeLog::whereTime('time_in', '11:00:00')
        ->whereTime('time_out', '13:00:00')
        ->get();

    foreach ($timeLogs as $log) {
        echo "Before auto-recalc: " . $log->log_date . " = " . $log->regular_hours . "h" . PHP_EOL;

        // Test the calculateHours method directly
        $controller = new App\Http\Controllers\TimeLogController();
        $controller->calculateHours($log);

        $log->refresh();
        echo "After auto-recalc: " . $log->log_date . " = " . $log->regular_hours . "h" . PHP_EOL;
    }
} else {
    echo "Found draft payroll ID: " . $payroll->id . PHP_EOL;
    echo "Period: " . $payroll->period_start . " to " . $payroll->period_end . PHP_EOL;

    // Test the time log recalculation for this payroll
    $employeeIds = $payroll->payrollDetails->pluck('employee_id');
    $timeLogs = App\Models\TimeLog::whereIn('employee_id', $employeeIds)
        ->whereBetween('log_date', [$payroll->period_start, $payroll->period_end])
        ->get();

    echo "Found " . $timeLogs->count() . " time logs to recalculate" . PHP_EOL;

    foreach ($timeLogs as $log) {
        if ($log->time_in && $log->time_out) {
            echo "Before: " . $log->log_date . " (" . $log->time_in . "-" . $log->time_out . ") = " . $log->regular_hours . "h" . PHP_EOL;

            $controller = new App\Http\Controllers\TimeLogController();
            $controller->calculateHours($log);

            $log->refresh();
            echo "After: " . $log->log_date . " (" . $log->time_in . "-" . $log->time_out . ") = " . $log->regular_hours . "h" . PHP_EOL;
            echo "---" . PHP_EOL;
        }
    }
}

echo "Test completed!" . PHP_EOL;
