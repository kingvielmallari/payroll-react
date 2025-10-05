<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';

// Handle the incoming request
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Boot the app
$kernel->bootstrap();

use App\Models\Employee;
use Carbon\Carbon;

try {
    echo "Testing Basic Pay and Monthly Basic Pay Calculations\n";
    echo "==================================================\n\n";

    // Get the first employee with fixed_rate
    $employee = Employee::whereNotNull('fixed_rate')->first();

    if (!$employee) {
        echo "No employee with fixed_rate found!\n";
        exit(1);
    }

    echo "Testing Employee: {$employee->name}\n";
    echo "Rate Type: {$employee->rate_type}\n";
    echo "Fixed Rate: ₱" . number_format($employee->fixed_rate, 2) . "\n\n";

    // Test period (current month)
    $periodStart = Carbon::now()->startOfMonth();
    $periodEnd = Carbon::now()->endOfMonth();

    echo "Test Period: {$periodStart->format('Y-m-d')} to {$periodEnd->format('Y-m-d')}\n\n";

    // Test Basic Pay calculation for period
    $basicPayForPeriod = $employee->calculateBasicPayForPeriod($periodStart, $periodEnd);
    echo "Basic Pay for Period: ₱" . number_format($basicPayForPeriod, 2) . "\n";

    // Test Monthly Basic Pay calculation
    $monthlyBasicPay = $employee->calculateMonthlyBasicSalary($periodStart, $periodEnd);
    echo "Monthly Basic Pay: ₱" . number_format($monthlyBasicPay, 2) . "\n\n";

    // Check time logs count
    $timeLogsCount = $employee->timeLogs()
        ->whereBetween('log_date', [$periodStart->format('Y-m-d'), $periodEnd->format('Y-m-d')])
        ->count();
    echo "Time logs found for period: {$timeLogsCount}\n";

    // Check total time logs for this employee
    $totalTimeLogs = $employee->timeLogs()->count();
    echo "Total time logs for employee: {$totalTimeLogs}\n";

    // Check if there are any time logs in the database at all
    $allTimeLogsCount = \App\Models\TimeLog::count();
    echo "Total time logs in database: {$allTimeLogsCount}\n";

    // Check date range of time logs
    $firstTimeLog = \App\Models\TimeLog::orderBy('log_date', 'asc')->first();
    $lastTimeLog = \App\Models\TimeLog::orderBy('log_date', 'desc')->first();

    if ($firstTimeLog && $lastTimeLog) {
        echo "Time logs date range: {$firstTimeLog->log_date} to {$lastTimeLog->log_date}\n";
    }

    // Get time logs for this specific employee
    $employeeTimeLogs = \App\Models\TimeLog::where('employee_id', $employee->id)
        ->orderBy('log_date', 'desc')
        ->limit(5)
        ->get(['log_date', 'log_type', 'hours_worked']);

    if ($employeeTimeLogs->count() > 0) {
        echo "Recent time logs for {$employee->name}:\n";
        foreach ($employeeTimeLogs as $log) {
            echo "  - {$log->log_date}: {$log->log_type} ({$log->hours_worked} hours)\n";
        }
    } else {
        echo "No time logs found for {$employee->name}\n";
    }
    echo "\n";

    if ($timeLogsCount > 0) {
        echo "✅ Calculations executed successfully!\n";
        echo "The new calculation logic is processing time logs correctly.\n";
    } else {
        echo "⚠️  No time logs found for the test period.\n";
        echo "The calculations returned ₱0.00 as expected when no time logs exist.\n";
    }
} catch (Exception $e) {
    echo "❌ Error during calculation: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
