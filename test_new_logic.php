<?php

require_once 'vendor/autoload.php';

use Carbon\Carbon;

// Test the new logic
function calculateSemiMonthlyPeriodForOffset($baseDate, $offset, $monthlyTiming = null, $deductionFrequency = null)
{
    $cutoffPeriods = [
        ['start_day' => 1, 'end_day' => 15],
        ['start_day' => 16, 'end_day' => 31]
    ];

    $currentDay = $baseDate->day;
    $currentMonth = $baseDate->copy();

    // Determine the ACTUAL current period based on today's date
    $isFirstHalf = $currentDay <= 15;

    if ($deductionFrequency === 'monthly' && $monthlyTiming) {
        // For monthly frequency with timing preference
        if ($monthlyTiming === 'first_payroll') {
            // Show only 1st cutoff periods across months
            $preferredPeriodIndex = 0;
            $targetMonth = $currentMonth->copy();

            // If we're currently in 2nd half and user wants 1st cutoff, start from next month
            if (!$isFirstHalf) {
                $targetMonth->addMonth();
            }

            // Apply offset by adding months (stay on same cutoff type)
            $targetMonth->addMonths($offset);
            $targetPeriodIndex = $preferredPeriodIndex;
        } else {
            // Show only 2nd cutoff periods across months
            $preferredPeriodIndex = 1;
            $targetMonth = $currentMonth->copy();

            // If we're currently in 1st half and user wants 2nd cutoff, use current month
            if ($isFirstHalf) {
                // Stay in current month for 2nd cutoff
            } else {
                // If in 2nd half, start from next month's 2nd cutoff
                $targetMonth->addMonth();
            }

            // Apply offset by adding months (stay on same cutoff type)
            $targetMonth->addMonths($offset);
            $targetPeriodIndex = $preferredPeriodIndex;
        }
    } else {
        // For per-payroll frequency, show both cutoffs alternating
        // Start from the CURRENT active period
        $targetPeriodIndex = $isFirstHalf ? 0 : 1;
        $targetMonth = $currentMonth->copy();

        // Apply offset with alternating cutoffs
        for ($i = 0; $i < $offset; $i++) {
            $targetPeriodIndex++;
            if ($targetPeriodIndex >= 2) {
                $targetPeriodIndex = 0;
                $targetMonth->addMonth();
            }
        }
    }

    $cutoff = $cutoffPeriods[$targetPeriodIndex];
    $startDay = (int) $cutoff['start_day'];
    $endDay = (int) $cutoff['end_day'];

    $startDate = $targetMonth->copy()->day($startDay);
    $endDate = $endDay == 31 ? $targetMonth->copy()->endOfMonth() : $targetMonth->copy()->day($endDay);

    return [
        'start' => $startDate,
        'end' => $endDate,
        'display' => $startDate->format('M d') . ' - ' . $endDate->format('d, Y')
    ];
}

// Test with current date (August 28, 2025 - we're in 2nd half)
$currentDate = Carbon::create(2025, 8, 28);

echo "Testing Semi-Monthly Period Calculation\n";
echo "Current Date: " . $currentDate->format('M d, Y') . " (2nd half of month)\n\n";

echo "1. Per-payroll frequency (should show alternating periods starting from current):\n";
for ($i = 0; $i < 3; $i++) {
    $period = calculateSemiMonthlyPeriodForOffset($currentDate, $i, null, 'per_payroll');
    $label = $i === 0 ? 'Current' : ($i === 1 ? '2nd' : '3rd');
    echo "   {$period['display']} ({$label})\n";
}

echo "\n2. Monthly frequency + 'first_payroll' timing (only 1st cutoffs):\n";
for ($i = 0; $i < 3; $i++) {
    $period = calculateSemiMonthlyPeriodForOffset($currentDate, $i, 'first_payroll', 'monthly');
    $label = $i === 0 ? 'Current' : ($i === 1 ? '2nd' : '3rd');
    echo "   {$period['display']} ({$label})\n";
}

echo "\n3. Monthly frequency + 'last_payroll' timing (only 2nd cutoffs):\n";
for ($i = 0; $i < 3; $i++) {
    $period = calculateSemiMonthlyPeriodForOffset($currentDate, $i, 'last_payroll', 'monthly');
    $label = $i === 0 ? 'Current' : ($i === 1 ? '2nd' : '3rd');
    echo "   {$period['display']} ({$label})\n";
}

echo "\nTest completed!\n";
