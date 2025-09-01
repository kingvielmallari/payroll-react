<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing night differential calculation fix...\n";

// Find an employee with time logs that have night differential
$employee = App\Models\Employee::first();
if (!$employee) {
    echo "No employee found\n";
    exit;
}

echo "Testing with Employee: {$employee->full_name} (ID: {$employee->id})\n";

// Use the PayrollController to calculate a draft payroll
$controller = new App\Http\Controllers\PayrollController();

// Use reflection to access the private method
$reflection = new \ReflectionClass($controller);
$method = $reflection->getMethod('calculateEmployeePayrollForPeriod');
$method->setAccessible(true);

// Calculate for current period (draft mode - should include ND correctly)
$periodStart = '2025-09-01';
$periodEnd = '2025-09-15';

try {
    echo "\n=== DRAFT MODE CALCULATION (with ND fix) ===\n";
    $draftCalculation = $method->invoke($controller, $employee, $periodStart, $periodEnd, null);
    
    echo "Regular Pay: " . number_format($draftCalculation['regular_pay'] ?? 0, 2) . "\n";
    echo "Holiday Pay: " . number_format($draftCalculation['holiday_pay'] ?? 0, 2) . "\n";
    echo "Rest Day Pay: " . number_format($draftCalculation['rest_day_pay'] ?? 0, 2) . "\n";
    echo "Overtime Pay: " . number_format($draftCalculation['overtime_pay'] ?? 0, 2) . "\n";
    echo "Gross Pay: " . number_format($draftCalculation['gross_pay'] ?? 0, 2) . "\n";
    
    // Calculate base taxable income (basic + holiday + rest + overtime)
    $baseTaxable = ($draftCalculation['regular_pay'] ?? 0) + 
                   ($draftCalculation['holiday_pay'] ?? 0) + 
                   ($draftCalculation['rest_day_pay'] ?? 0) + 
                   ($draftCalculation['overtime_pay'] ?? 0);
    echo "Base Taxable Income: " . number_format($baseTaxable, 2) . "\n";
    
    // Check for time logs with night differential
    $timeLogs = App\Models\TimeLog::where('employee_id', $employee->id)
        ->whereBetween('log_date', [$periodStart, $periodEnd])
        ->where(function($query) {
            $query->where('night_diff_regular_hours', '>', 0)
                  ->orWhere('night_diff_overtime_hours', '>', 0);
        })
        ->get();
    
    echo "\nTime logs with Night Differential found: " . $timeLogs->count() . "\n";
    foreach ($timeLogs as $log) {
        echo "- Date: {$log->log_date}, Type: {$log->log_type}, ND Reg: {$log->night_diff_regular_hours}h, ND OT: {$log->night_diff_overtime_hours}h\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nDone!\n";
