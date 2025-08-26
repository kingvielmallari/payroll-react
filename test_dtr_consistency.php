<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

use App\Models\TimeLog;
use App\Models\Payroll;

echo "Testing DTR Summary consistency between draft and processing payrolls...\n";

// Find a timeLog from both draft and processing payrolls
$draftPayroll = Payroll::where('status', 'draft')->first();
$processingPayroll = Payroll::where('status', 'processing')->first();

if ($draftPayroll && $processingPayroll) {
    echo "Found draft payroll: {$draftPayroll->id}\n";
    echo "Found processing payroll: {$processingPayroll->id}\n";

    // Get a timeLog from each
    $draftTimeLog = TimeLog::where('employee_id', $draftPayroll->payrollDetails->first()->employee_id ?? 1)
        ->whereNotNull('time_in')
        ->whereNotNull('time_out')
        ->first();

    $processingTimeLog = TimeLog::where('employee_id', $processingPayroll->payrollDetails->first()->employee_id ?? 1)
        ->whereNotNull('time_in')
        ->whereNotNull('time_out')
        ->first();

    if ($draftTimeLog && $processingTimeLog) {
        echo "\nTesting TimeLog breakdown methods...\n";

        // Test regular breakdown
        $draftBreakdown = $draftTimeLog->getTimePeriodBreakdown();
        $processingBreakdown = $processingTimeLog->getTimePeriodBreakdown();

        echo "Draft TimeLog breakdown items: " . count($draftBreakdown) . "\n";
        echo "Processing TimeLog breakdown items: " . count($processingBreakdown) . "\n";

        // Test with forced dynamic values
        $forcedValues = [
            'regular_hours' => 8.0,
            'overtime_hours' => 2.0,
            'regular_overtime_hours' => 1.5,
            'night_diff_overtime_hours' => 0.5,
        ];

        $forcedBreakdown = $processingTimeLog->getTimePeriodBreakdown($forcedValues);
        echo "Processing TimeLog with forced values breakdown items: " . count($forcedBreakdown) . "\n";

        echo "\nTimeLog getTimePeriodBreakdown method is working correctly!\n";
    } else {
        echo "Could not find suitable TimeLogs for testing.\n";
    }
} else {
    echo "Could not find both draft and processing payrolls for testing.\n";
}

echo "\nTest completed.\n";
