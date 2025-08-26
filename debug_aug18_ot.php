<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Employee;
use App\Models\TimeLog;
use App\Http\Controllers\TimeLogController;

$employee = Employee::where('first_name', 'Carlos')->first();

echo "=== DEBUGGING AUG 18 OT CALCULATION ===\n";

// Get Aug 18 log
$log18 = TimeLog::where('employee_id', $employee->id)->where('log_date', '2025-08-18')->first();
$log19 = TimeLog::where('employee_id', $employee->id)->where('log_date', '2025-08-19')->first();

if ($log18) {
    echo "Aug 18 stored values:\n";
    echo "  Time In: {$log18->time_in}\n";
    echo "  Time Out: {$log18->time_out}\n";
    echo "  Total Hours: {$log18->total_hours}h\n";
    echo "  Regular Hours: {$log18->regular_hours}h\n";
    echo "  Total OT Hours: {$log18->overtime_hours}h\n";
    echo "  Regular OT Hours: {$log18->regular_overtime_hours}h\n";
    echo "  Night Diff OT Hours: {$log18->night_diff_overtime_hours}h\n";

    $breakdown18 = $log18->getTimePeriodBreakdown();
    echo "\n  Time Period Breakdown:\n";
    foreach ($breakdown18 as $period) {
        echo "    {$period['type']}: {$period['start_time']} - {$period['end_time']} ({$period['hours']}h)\n";
    }

    // Manual calculation
    echo "\n  Manual calculation:\n";
    echo "  Regular OT + Night Diff OT = {$log18->regular_overtime_hours} + {$log18->night_diff_overtime_hours} = " . ($log18->regular_overtime_hours + $log18->night_diff_overtime_hours) . "h\n";
    echo "  Should equal Total OT: {$log18->overtime_hours}h\n";

    if (abs($log18->overtime_hours - ($log18->regular_overtime_hours + $log18->night_diff_overtime_hours)) > 0.01) {
        echo "  ❌ MISMATCH! Total OT doesn't equal Regular OT + Night Diff OT\n";
    } else {
        echo "  ✅ Match! Total OT equals Regular OT + Night Diff OT\n";
    }
}

if ($log19) {
    echo "\n=== AUG 19 COMPARISON ===\n";
    echo "Aug 19 stored values:\n";
    echo "  Time In: {$log19->time_in}\n";
    echo "  Time Out: {$log19->time_out}\n";
    echo "  Total Hours: {$log19->total_hours}h\n";
    echo "  Regular Hours: {$log19->regular_hours}h\n";
    echo "  Total OT Hours: {$log19->overtime_hours}h\n";
    echo "  Regular OT Hours: {$log19->regular_overtime_hours}h\n";
    echo "  Night Diff OT Hours: {$log19->night_diff_overtime_hours}h\n";

    echo "\n  Manual calculation:\n";
    echo "  Regular OT + Night Diff OT = {$log19->regular_overtime_hours} + {$log19->night_diff_overtime_hours} = " . ($log19->regular_overtime_hours + $log19->night_diff_overtime_hours) . "h\n";
    echo "  Should equal Total OT: {$log19->overtime_hours}h\n";

    if (abs($log19->overtime_hours - ($log19->regular_overtime_hours + $log19->night_diff_overtime_hours)) > 0.01) {
        echo "  ❌ MISMATCH! Total OT doesn't equal Regular OT + Night Diff OT\n";
    } else {
        echo "  ✅ Match! Total OT equals Regular OT + Night Diff OT\n";
    }
}

// Test recalculation
echo "\n=== RECALCULATION TEST ===\n";
$controller = new TimeLogController();
$reflection = new \ReflectionClass($controller);
$method = $reflection->getMethod('calculateDynamicWorkingHours');
$method->setAccessible(true);

if ($log18) {
    // Create a test log with correct times
    $testLog18 = new TimeLog([
        'employee_id' => $employee->id,
        'log_date' => '2025-08-18',
        'time_in' => '08:15:00',
        'time_out' => '22:00:00', // End at 10 PM instead of 11:59 PM
    ]);
    $testLog18->setRelation('employee', $employee);

    $result18 = $method->invoke($controller, $testLog18);

    echo "Aug 18 recalculated (8:15 AM - 10:00 PM):\n";
    echo "  Total OT Hours: {$result18['overtime_hours']}h\n";
    echo "  Regular OT Hours: {$result18['regular_overtime_hours']}h\n";
    echo "  Night Diff OT Hours: {$result18['night_diff_overtime_hours']}h\n";
    echo "  Sum: " . ($result18['regular_overtime_hours'] + $result18['night_diff_overtime_hours']) . "h\n";

    if (abs($result18['overtime_hours'] - ($result18['regular_overtime_hours'] + $result18['night_diff_overtime_hours'])) > 0.01) {
        echo "  ❌ MISMATCH! Total OT doesn't equal Regular OT + Night Diff OT\n";
    } else {
        echo "  ✅ Match! Total OT equals Regular OT + Night Diff OT\n";
    }
}
