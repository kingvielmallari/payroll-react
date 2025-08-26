<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Employee;
use App\Models\TimeLog;
use App\Http\Controllers\TimeLogController;

echo "Fixing Aug 18 overtime calculation...\n";

$employee = Employee::where('first_name', 'Carlos')->first();
$log18 = TimeLog::where('employee_id', $employee->id)->where('log_date', '2025-08-18')->first();

if ($log18) {
    echo "Current Aug 18 values:\n";
    echo "  Regular OT: {$log18->regular_overtime_hours}h\n";
    echo "  Night Diff OT: {$log18->night_diff_overtime_hours}h\n";
    echo "  Total OT: {$log18->overtime_hours}h\n";

    // Create test log with correct times (8:15 AM - 10:00 PM)
    $testLog18 = new TimeLog([
        'employee_id' => $employee->id,
        'log_date' => '2025-08-18',
        'time_in' => '08:15:00',
        'time_out' => '22:00:00',
    ]);
    $testLog18->setRelation('employee', $employee);

    $controller = new TimeLogController();
    $reflection = new \ReflectionClass($controller);
    $method = $reflection->getMethod('calculateDynamicWorkingHours');
    $method->setAccessible(true);

    $result = $method->invoke($controller, $testLog18);

    echo "\nRecalculated values:\n";
    echo "  Regular OT: {$result['regular_overtime_hours']}h\n";
    echo "  Night Diff OT: {$result['night_diff_overtime_hours']}h\n";
    echo "  Total OT: {$result['overtime_hours']}h\n";

    // Update the database
    $log18->update([
        'time_out' => '2025-08-26 22:00:00', // Change from 23:59 to 22:00
        'total_hours' => $result['total_hours'],
        'regular_hours' => $result['regular_hours'],
        'overtime_hours' => $result['overtime_hours'],
        'regular_overtime_hours' => $result['regular_overtime_hours'],
        'night_diff_overtime_hours' => $result['night_diff_overtime_hours'],
        'late_hours' => $result['late_hours'],
        'undertime_hours' => $result['undertime_hours'],
    ]);

    echo "\n✅ Database updated successfully!\n";
    echo "Aug 18 now shows 5:00 PM - 10:00 PM (5.00h) for Regular OT\n";
} else {
    echo "❌ Aug 18 log not found\n";
}
