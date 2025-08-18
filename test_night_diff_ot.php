<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\NightDifferentialSetting;
use App\Models\GracePeriodSetting;
use App\Http\Controllers\TimeLogController;
use App\Models\TimeLog;
use App\Models\Employee;
use Carbon\Carbon;

echo "=== Night Differential OT Calculation Test ===\n\n";

// Get current settings
$nightDiff = NightDifferentialSetting::current();
$gracePeriod = GracePeriodSetting::current();

echo "Night Differential Settings:\n";
echo "- Start: {$nightDiff->start_time}\n";
echo "- End: {$nightDiff->end_time}\n";
echo "- Rate: " . ($nightDiff->rate_multiplier * 100) . "%\n";
echo "- Active: " . ($nightDiff->is_active ? 'Yes' : 'No') . "\n\n";

echo "Grace Period Settings:\n";
echo "- Overtime Threshold: {$gracePeriod->overtime_threshold_minutes} minutes\n\n";

// Test scenario: Aug 17, 7:00 AM - 4:00 AM next day (employee worked 21 hours)
// Regular hours: 7:00 AM - 4:00 PM (8 hours after break)
// OT: 4:00 PM - 10:00 PM (6 hours)
// OT+ND: 10:00 PM - 4:00 AM (6 hours)

// Create a test employee (use existing or create mock)
try {
    $employee = Employee::first();
    if (!$employee) {
        echo "No employees found. Please create an employee first.\n";
        exit;
    }

    echo "Testing with employee: {$employee->full_name}\n\n";

    // Create test time log
    $testDate = Carbon::parse('2025-08-17');
    $timeLog = new TimeLog([
        'employee_id' => $employee->id,
        'log_date' => $testDate,
        'time_in' => '07:00:00',
        'time_out' => '04:00:00', // Next day 4 AM
        'log_type' => 'rest_day'
    ]);
    $timeLog->setRelation('employee', $employee);

    // Test the calculation method via reflection
    $controller = new TimeLogController();
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('calculateDynamicWorkingHours');
    $method->setAccessible(true);

    $result = $method->invoke($controller, $timeLog);

    echo "Calculation Results:\n";
    echo "- Total Hours: {$result['total_hours']}\n";
    echo "- Regular Hours: {$result['regular_hours']}\n";
    echo "- Total Overtime Hours: {$result['overtime_hours']}\n";
    echo "- Regular OT Hours: {$result['regular_overtime_hours']}\n";
    echo "- Night Diff OT Hours: {$result['night_diff_overtime_hours']}\n";
    echo "- Late Hours: {$result['late_hours']}\n";
    echo "- Undertime Hours: {$result['undertime_hours']}\n\n";

    // Expected results for 7 AM - 4 AM scenario:
    // - Total: ~21 hours (minus break)
    // - Regular: 8 hours (threshold)
    // - Total OT: 13 hours
    // - Regular OT: 7 hours (4 PM - 10 PM + 1 hour from 7 AM extension if needed)
    // - Night Diff OT: 6 hours (10 PM - 4 AM)

    echo "Expected breakdown for 7 AM - 4 AM shift:\n";
    echo "- 7:00 AM - 4:00 PM: Regular hours (8h after break)\n";
    echo "- 4:00 PM - 10:00 PM: Regular OT (6h)\n";
    echo "- 10:00 PM - 4:00 AM: OT + Night Diff (6h)\n\n";

    if ($result['regular_overtime_hours'] > 0 || $result['night_diff_overtime_hours'] > 0) {
        echo "✅ Night differential overtime breakdown is working!\n";
    } else {
        echo "❌ Night differential overtime breakdown not calculated\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
