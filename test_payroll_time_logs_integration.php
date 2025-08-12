<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Payroll;
use App\Models\TimeLog;
use App\Models\Employee;
use Carbon\Carbon;

echo "Testing Payroll-TimeLog Integration\n";
echo "===================================\n\n";

// Test 1: Check if payroll_id column exists in time_logs table
echo "1. Checking if payroll_id column exists in time_logs table...\n";
try {
    $timeLogSchema = \Illuminate\Support\Facades\Schema::getColumnListing('time_logs');
    if (in_array('payroll_id', $timeLogSchema)) {
        echo "✓ payroll_id column exists in time_logs table\n";
    } else {
        echo "✗ payroll_id column not found in time_logs table\n";
    }
} catch (Exception $e) {
    echo "✗ Error checking schema: " . $e->getMessage() . "\n";
}

// Test 2: Check if TimeLog model has payroll relationship
echo "\n2. Checking TimeLog model payroll relationship...\n";
try {
    $timeLog = new TimeLog();
    if (method_exists($timeLog, 'payroll')) {
        echo "✓ TimeLog model has payroll() method\n";
    } else {
        echo "✗ TimeLog model missing payroll() method\n";
    }
} catch (Exception $e) {
    echo "✗ Error checking TimeLog model: " . $e->getMessage() . "\n";
}

// Test 3: Check if Payroll model has timeLogs relationship
echo "\n3. Checking Payroll model timeLogs relationship...\n";
try {
    $payroll = new Payroll();
    if (method_exists($payroll, 'timeLogs')) {
        echo "✓ Payroll model has timeLogs() method\n";
    } else {
        echo "✗ Payroll model missing timeLogs() method\n";
    }
} catch (Exception $e) {
    echo "✗ Error checking Payroll model: " . $e->getMessage() . "\n";
}

// Test 4: Check if there are any existing payrolls
echo "\n4. Checking existing payrolls...\n";
try {
    $payrollCount = Payroll::count();
    echo "Found {$payrollCount} payrolls in the system\n";

    if ($payrollCount > 0) {
        $recentPayroll = Payroll::latest()->first();
        echo "Most recent payroll: ID {$recentPayroll->id}, Period: {$recentPayroll->period_start} to {$recentPayroll->period_end}\n";

        // Check if this payroll has linked time logs
        $linkedTimeLogs = TimeLog::where('payroll_id', $recentPayroll->id)->count();
        echo "Time logs linked to this payroll: {$linkedTimeLogs}\n";

        // Check time logs in the period without payroll_id
        $unlinkedTimeLogs = TimeLog::whereBetween('log_date', [$recentPayroll->period_start, $recentPayroll->period_end])
            ->whereNull('payroll_id')
            ->count();
        echo "Time logs in period without payroll_id: {$unlinkedTimeLogs}\n";
    }
} catch (Exception $e) {
    echo "✗ Error checking payrolls: " . $e->getMessage() . "\n";
}

// Test 5: Check existing time logs
echo "\n5. Checking existing time logs...\n";
try {
    $timeLogCount = TimeLog::count();
    echo "Found {$timeLogCount} time logs in the system\n";

    $timeLogsWithPayroll = TimeLog::whereNotNull('payroll_id')->count();
    $timeLogsWithoutPayroll = TimeLog::whereNull('payroll_id')->count();

    echo "Time logs with payroll_id: {$timeLogsWithPayroll}\n";
    echo "Time logs without payroll_id: {$timeLogsWithoutPayroll}\n";
} catch (Exception $e) {
    echo "✗ Error checking time logs: " . $e->getMessage() . "\n";
}

echo "\nTest completed!\n";
echo "\nNOTES:\n";
echo "- When System Admin updates time logs from the payroll page, they will be linked to that payroll\n";
echo "- When payroll is deleted, associated time logs will also be deleted (cascade)\n";
echo "- Payroll calculations now only use time logs with matching payroll_id\n";
echo "- Existing time logs will be auto-linked to payrolls when payroll is viewed\n";
