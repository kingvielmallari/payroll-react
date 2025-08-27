<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== GRACE PERIOD SETTINGS ===\n";
$gracePeriod = DB::table('grace_period_settings')->where('is_active', true)->first();
if ($gracePeriod) {
    echo "Overtime threshold: {$gracePeriod->overtime_threshold_minutes} minutes (" . ($gracePeriod->overtime_threshold_minutes / 60) . " hours)\n";
    echo "Late grace: {$gracePeriod->late_grace_minutes} minutes\n";
} else {
    echo "No active grace period settings found\n";
}

echo "\n=== CARLOS TIME LOG CALCULATION TEST ===\n";
// Get Carlos's time log for Aug 18
$timeLog = DB::table('time_logs')
    ->join('employees', 'time_logs.employee_id', '=', 'employees.id')
    ->where('employees.first_name', 'Carlos')
    ->where('employees.last_name', 'Mendoza')
    ->where('log_date', '2025-08-18')
    ->select('time_logs.*')
    ->first();

if ($timeLog) {
    echo "Time In: {$timeLog->time_in}\n";
    echo "Time Out: {$timeLog->time_out}\n";
    echo "Stored Regular Hours: {$timeLog->regular_hours}\n";
    echo "Stored Total Hours: {$timeLog->total_hours}\n";

    // Test dynamic calculation with debugging
    $timeLogObj = \App\Models\TimeLog::find($timeLog->id);

    // Manually trace through the calculation logic
    echo "\n=== MANUAL CALCULATION TRACE ===\n";
    $employee = $timeLogObj->employee;
    $timeSchedule = $employee->timeSchedule;

    echo "Employee: {$employee->first_name} {$employee->last_name}\n";
    if ($timeSchedule) {
        echo "Time Schedule: {$timeSchedule->time_in} - {$timeSchedule->time_out}\n";
        echo "Break Period: {$timeSchedule->break_start} - {$timeSchedule->break_end}\n";
    } else {
        echo "No time schedule assigned\n";
    }

    $controller = app(\App\Http\Controllers\TimeLogController::class);
    $reflection = new \ReflectionClass($controller);
    $method = $reflection->getMethod('calculateDynamicWorkingHours');
    $method->setAccessible(true);
    $dynamicResult = $method->invoke($controller, $timeLogObj);

    echo "\nDYNAMIC CALCULATION RESULT:\n";
    echo "Total Hours: " . ($dynamicResult['total_hours'] ?? 'N/A') . "\n";
    echo "Regular Hours: " . ($dynamicResult['regular_hours'] ?? 'N/A') . "\n";
    echo "Overtime Hours: " . ($dynamicResult['overtime_hours'] ?? 'N/A') . "\n";
} else {
    echo "No time log found for Carlos on Aug 18\n";
}
