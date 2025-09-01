<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Checking existing processing payroll for ND comparison...\n";

// Find a processing payroll with snapshots
$payroll = App\Models\Payroll::where('status', 'processing')->first();
if ($payroll) {
    echo "Found processing payroll: {$payroll->payroll_number}\n";
    
    $snapshot = $payroll->snapshots()->first();
    if ($snapshot) {
        echo "Employee: {$snapshot->employee_name}\n";
        echo "Snapshot Taxable Income: " . number_format($snapshot->taxable_income, 2) . "\n";
        echo "Regular Pay: " . number_format($snapshot->regular_pay, 2) . "\n";
        echo "Holiday Pay: " . number_format($snapshot->holiday_pay, 2) . "\n";
        echo "Rest Day Pay: " . number_format($snapshot->rest_day_pay, 2) . "\n";
        echo "Overtime Pay: " . number_format($snapshot->overtime_pay, 2) . "\n";
        
        $baseTaxable = $snapshot->regular_pay + $snapshot->holiday_pay + $snapshot->rest_day_pay + $snapshot->overtime_pay;
        echo "Base Components Total: " . number_format($baseTaxable, 2) . "\n";
        echo "Difference: " . number_format($snapshot->taxable_income - $baseTaxable, 2) . "\n";
        
        // Check for breakdown data
        if ($snapshot->holiday_breakdown) {
            $holidayBreakdown = is_string($snapshot->holiday_breakdown) 
                ? json_decode($snapshot->holiday_breakdown, true) 
                : $snapshot->holiday_breakdown;
            echo "\nHoliday breakdown:\n";
            echo json_encode($holidayBreakdown, JSON_PRETTY_PRINT) . "\n";
        }
        
        if ($snapshot->overtime_breakdown) {
            $overtimeBreakdown = is_string($snapshot->overtime_breakdown) 
                ? json_decode($snapshot->overtime_breakdown, true) 
                : $snapshot->overtime_breakdown;
            echo "\nOvertime breakdown:\n";
            echo json_encode($overtimeBreakdown, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "No snapshots found\n";
    }
} else {
    echo "No processing payroll found\n";
}

echo "Done!\n";
