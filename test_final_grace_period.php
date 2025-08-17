<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\TimeLog;
use App\Models\Employee;
use App\Http\Controllers\TimeLogController;
use Carbon\Carbon;

echo "Testing Fixed Grace Period Logic...\n\n";

// Get Maria Santos (Employee ID 2) 
$employee = Employee::find(2);

if (!$employee) {
    echo "Employee ID 2 not found!\n";
    exit;
}

echo "Employee: " . $employee->user->name . " (ID: " . $employee->id . ")\n";
echo "Schedule: " . ($employee->timeSchedule ? $employee->timeSchedule->time_range_display : 'No schedule') . "\n\n";

$timeLogController = app(TimeLogController::class);
$reflection = new ReflectionClass($timeLogController);
$method = $reflection->getMethod('calculateDynamicWorkingHours');
$method->setAccessible(true);

echo "=== TESTING LATE GRACE PERIOD ===\n\n";

// Test Case 1: 7:29 AM - 4:00 PM (within 30-minute grace)
echo "Test 1: 7:29 AM - 4:00 PM (within grace period)\n";
$testTimeLog1 = new TimeLog();
$testTimeLog1->employee_id = $employee->id;
$testTimeLog1->log_date = '2025-08-18';
$testTimeLog1->time_in = '2025-08-18 07:29:00';
$testTimeLog1->time_out = '2025-08-18 16:00:00';
$testTimeLog1->setRelation('employee', $employee);

try {
    $result1 = $method->invoke($timeLogController, $testTimeLog1);
    
    echo "Result: " . $result1['total_hours'] . "h total, " . $result1['late_hours'] . "h late\n";
    echo "Expected: ~8h total, 0h late (within grace period)\n";
    
    if ($result1['late_hours'] == 0) {
        echo "✅ Late grace period working correctly\n";
    } else {
        echo "❌ Late grace period NOT working - should be 0 late hours\n";
    }
    echo "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// Test Case 2: 7:31 AM - 4:00 PM (beyond 30-minute grace)
echo "Test 2: 7:31 AM - 4:00 PM (beyond grace period)\n";
$testTimeLog2 = new TimeLog();
$testTimeLog2->employee_id = $employee->id;
$testTimeLog2->log_date = '2025-08-18';
$testTimeLog2->time_in = '2025-08-18 07:31:00';
$testTimeLog2->time_out = '2025-08-18 16:00:00';
$testTimeLog2->setRelation('employee', $employee);

try {
    $result2 = $method->invoke($timeLogController, $testTimeLog2);
    
    echo "Result: " . $result2['total_hours'] . "h total, " . $result2['late_hours'] . "h late\n";
    echo "Expected: ~7.5h total, ~0.5h late (beyond grace period)\n";
    
    if ($result2['late_hours'] > 0) {
        echo "✅ Late deduction working correctly for time beyond grace period\n";
    } else {
        echo "❌ Should deduct late hours for 31 minutes (beyond grace period)\n";
    }
    echo "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

echo "=== TESTING OVERTIME THRESHOLD ===\n\n";

// Test Case 3: 7:00 AM - 9:00 PM (14 hours - should trigger overtime)
echo "Test 3: 7:00 AM - 9:00 PM (14 hours - should trigger overtime)\n";
$testTimeLog3 = new TimeLog();
$testTimeLog3->employee_id = $employee->id;
$testTimeLog3->log_date = '2025-08-18';
$testTimeLog3->time_in = '2025-08-18 07:00:00';
$testTimeLog3->time_out = '2025-08-18 21:00:00';
$testTimeLog3->setRelation('employee', $employee);

try {
    $result3 = $method->invoke($timeLogController, $testTimeLog3);
    
    echo "Result: " . $result3['total_hours'] . "h total, " . $result3['regular_hours'] . "h regular, " . $result3['overtime_hours'] . "h OT\n";
    echo "Expected: 13h total (minus 1h break), 8h regular, 5h OT (based on 480-minute threshold)\n";
    
    if ($result3['overtime_hours'] > 0) {
        echo "✅ Overtime calculation working\n";
    } else {
        echo "❌ Should have overtime hours\n";
    }
    echo "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

echo "=== TESTING UNDERTIME (should always be 0) ===\n\n";

// Test Case 4: 7:00 AM - 12:00 PM (5 hours - early out)
echo "Test 4: 7:00 AM - 12:00 PM (early time out)\n";
$testTimeLog4 = new TimeLog();
$testTimeLog4->employee_id = $employee->id;
$testTimeLog4->log_date = '2025-08-18';
$testTimeLog4->time_in = '2025-08-18 07:00:00';
$testTimeLog4->time_out = '2025-08-18 12:00:00';
$testTimeLog4->setRelation('employee', $employee);

try {
    $result4 = $method->invoke($timeLogController, $testTimeLog4);
    
    echo "Result: " . $result4['total_hours'] . "h total, " . $result4['undertime_hours'] . "h undertime\n";
    echo "Expected: 5h total, 0h undertime (we only count actual work time)\n";
    
    if ($result4['undertime_hours'] == 0) {
        echo "✅ Undertime correctly removed - only counting actual work time\n";
    } else {
        echo "❌ Should have 0 undertime hours\n";
    }
    echo "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

echo "All tests completed!\n";
