<?php

require_once 'vendor/autoload.php';

use Carbon\Carbon;

// Simulate the calculation logic for testing
function calculateSemiMonthlyPeriodForOffset($baseDate, $offset, $monthlyTiming = null)
{
    // Default semi-monthly cutoffs
    $cutoffPeriods = [
        ['start_day' => 1, 'end_day' => 15],
        ['start_day' => 16, 'end_day' => 31]
    ];

    $currentDay = $baseDate->day;
    $currentMonth = $baseDate->copy();

    // Determine which cutoff period to use based on timing preference
    if ($monthlyTiming) {
        // If timing is specified, use it to determine which period to show
        if ($monthlyTiming === 'first_payroll') {
            // Show first cutoff periods (1-15)
            $preferredPeriodIndex = 0;
        } else {
            // Show second cutoff periods (16-31)  
            $preferredPeriodIndex = 1;
        }

        $targetPeriodIndex = $preferredPeriodIndex;
        $targetMonth = $currentMonth->copy();

        // Apply offset by adding months
        $targetMonth->addMonths($offset);
    } else {
        // Default behavior - determine current period and calculate offset
        $isFirstHalf = $currentDay <= 15;
        $targetPeriodIndex = $isFirstHalf ? 0 : 1;
        $targetMonth = $currentMonth->copy();

        // Apply offset
        for ($i = 0; $i < $offset; $i++) {
            $targetPeriodIndex++;
            if ($targetPeriodIndex >= 2) {
                $targetPeriodIndex = 0;
                $targetMonth->addMonth();
            }
        }
    }

    $cutoff = $cutoffPeriods[$targetPeriodIndex];
    $startDay = $cutoff['start_day'];
    $endDay = $cutoff['end_day'];

    $startDate = $targetMonth->copy()->day($startDay);
    $endDate = $endDay == 31 ? $targetMonth->copy()->endOfMonth() : $targetMonth->copy()->day($endDay);

    return [
        'start' => $startDate,
        'end' => $endDate,
        'display' => $startDate->format('M d') . ' - ' . $endDate->format('d, Y')
    ];
}

// Test with current date (August 28, 2025)
$currentDate = Carbon::create(2025, 8, 28);

echo "Testing Semi-Monthly Period Calculation\n";
echo "Current Date: " . $currentDate->format('M d, Y') . "\n\n";

echo "1. Without timing preference (default behavior):\n";
for ($i = 0; $i < 3; $i++) {
    $period = calculateSemiMonthlyPeriodForOffset($currentDate, $i);
    $label = $i === 0 ? 'Current' : ($i === 1 ? '2nd' : '3rd');
    echo "   {$period['display']} ({$label})\n";
}

echo "\n2. With 'first_payroll' timing (1st cutoff preference):\n";
for ($i = 0; $i < 3; $i++) {
    $period = calculateSemiMonthlyPeriodForOffset($currentDate, $i, 'first_payroll');
    $label = $i === 0 ? 'Current' : ($i === 1 ? '2nd' : '3rd');
    echo "   {$period['display']} ({$label})\n";
}

echo "\n3. With 'last_payroll' timing (2nd cutoff preference):\n";
for ($i = 0; $i < 3; $i++) {
    $period = calculateSemiMonthlyPeriodForOffset($currentDate, $i, 'last_payroll');
    $label = $i === 0 ? 'Current' : ($i === 1 ? '2nd' : '3rd');
    echo "   {$period['display']} ({$label})\n";
}

echo "\nTest completed!\n";
