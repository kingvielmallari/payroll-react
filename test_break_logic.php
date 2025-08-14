<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Testing Break Time Calculation Logic ===\n\n";

// Test scenarios
$testCases = [
    [
        'description' => 'Employee 8:00 AM - 5:00 PM (spans 12-1pm break)',
        'time_in' => '08:00:00',
        'time_out' => '17:00:00',
        'expected_break_deduction' => 60, // 1 hour
        'expected_total_hours' => 8.0,
    ],
    [
        'description' => 'Employee 1:00 PM - 5:00 PM (after 12-1pm break)',
        'time_in' => '13:00:00',
        'time_out' => '17:00:00',
        'expected_break_deduction' => 0, // No break deduction
        'expected_total_hours' => 4.0,
    ],
    [
        'description' => 'Employee 11:00 AM - 12:30 PM (partial break overlap)',
        'time_in' => '11:00:00',
        'time_out' => '12:30:00',
        'expected_break_deduction' => 30, // 30 minutes (12:00-12:30)
        'expected_total_hours' => 1.0, // 1.5 - 0.5 break
    ],
    [
        'description' => 'Employee 10:00 AM - 11:00 AM (no break overlap)',
        'time_in' => '10:00:00',
        'time_out' => '11:00:00',
        'expected_break_deduction' => 0, // No break deduction
        'expected_total_hours' => 1.0,
    ],
];

foreach ($testCases as $index => $testCase) {
    echo "Test " . ($index + 1) . ": " . $testCase['description'] . "\n";

    // Simulate the break calculation logic
    $logDate = '2025-08-01';
    $timeIn = \Carbon\Carbon::parse($logDate . ' ' . $testCase['time_in']);
    $timeOut = \Carbon\Carbon::parse($logDate . ' ' . $testCase['time_out']);

    $totalMinutes = $timeIn->diffInMinutes($timeOut);

    // Standard break time logic
    $standardBreakStart = \Carbon\Carbon::parse($logDate . ' 12:00:00');
    $standardBreakEnd = \Carbon\Carbon::parse($logDate . ' 13:00:00');

    $breakMinutesDeducted = 0;
    if ($timeIn->lt($standardBreakEnd) && $timeOut->gt($standardBreakStart)) {
        $overlapStart = max($timeIn->timestamp, $standardBreakStart->timestamp);
        $overlapEnd = min($timeOut->timestamp, $standardBreakEnd->timestamp);

        if ($overlapEnd > $overlapStart) {
            $breakMinutesDeducted = ($overlapEnd - $overlapStart) / 60;
        }
    }

    $totalMinutesAfterBreak = $totalMinutes - $breakMinutesDeducted;
    $totalHoursAfterBreak = $totalMinutesAfterBreak / 60;

    echo "  Result: Total minutes = $totalMinutes, Break deducted = $breakMinutesDeducted min, Final hours = $totalHoursAfterBreak\n";
    echo "  Expected: Break = {$testCase['expected_break_deduction']} min, Hours = {$testCase['expected_total_hours']}\n";

    $passBreak = ($breakMinutesDeducted == $testCase['expected_break_deduction']);
    $passHours = (abs($totalHoursAfterBreak - $testCase['expected_total_hours']) < 0.01);

    echo "  Status: " . (($passBreak && $passHours) ? "✅ PASS" : "❌ FAIL") . "\n\n";
}
