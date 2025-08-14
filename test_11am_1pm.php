<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Break Time Calculation Logic ===" . PHP_EOL;

// Simulate the 11:00 AM - 1:00 PM scenario
$logDate = '2025-08-05';
$timeIn = '11:00:00';
$timeOut = '13:00:00';

echo "Testing: " . $timeIn . " to " . $timeOut . " on " . $logDate . PHP_EOL;

// Parse the times
$timeInCarbon = \Carbon\Carbon::parse($logDate . ' ' . $timeIn);
$timeOutCarbon = \Carbon\Carbon::parse($logDate . ' ' . $timeOut);

echo "Parsed Time In: " . $timeInCarbon->format('Y-m-d H:i:s') . PHP_EOL;
echo "Parsed Time Out: " . $timeOutCarbon->format('Y-m-d H:i:s') . PHP_EOL;

// Calculate total minutes (corrected order)
$totalMinutes = $timeOutCarbon->diffInMinutes($timeInCarbon);
echo "diffInMinutes result (timeOut->diffInMinutes(timeIn)): " . $totalMinutes . PHP_EOL;

// Let's try the other way
$totalMinutes2 = $timeInCarbon->diffInMinutes($timeOutCarbon);
echo "diffInMinutes result (timeIn->diffInMinutes(timeOut)): " . $totalMinutes2 . PHP_EOL;

// Use the positive result
$totalMinutes = abs($totalMinutes);
echo "Total work minutes (before break): " . $totalMinutes . PHP_EOL;

// Check break deduction (no recorded break times, so use standard break logic)
$breakMinutesDeducted = 0;

// Standard break time: 12:00 PM - 1:00 PM
$standardBreakStart = \Carbon\Carbon::parse($logDate . ' 12:00:00');
$standardBreakEnd = \Carbon\Carbon::parse($logDate . ' 13:00:00');

echo "Standard break period: " . $standardBreakStart->format('H:i') . " - " . $standardBreakEnd->format('H:i') . PHP_EOL;
echo "Work period: " . $timeInCarbon->format('H:i') . " - " . $timeOutCarbon->format('H:i') . PHP_EOL;

// Check if work period overlaps with standard break period
if ($timeInCarbon->lt($standardBreakEnd) && $timeOutCarbon->gt($standardBreakStart)) {
    echo "Overlap detected!" . PHP_EOL;

    // Calculate overlap between work period and break period
    $overlapStart = max($timeInCarbon->timestamp, $standardBreakStart->timestamp);
    $overlapEnd = min($timeOutCarbon->timestamp, $standardBreakEnd->timestamp);

    echo "Overlap start timestamp: " . $overlapStart . " (" . date('H:i', $overlapStart) . ")" . PHP_EOL;
    echo "Overlap end timestamp: " . $overlapEnd . " (" . date('H:i', $overlapEnd) . ")" . PHP_EOL;

    if ($overlapEnd > $overlapStart) {
        $breakMinutesDeducted = ($overlapEnd - $overlapStart) / 60;
        echo "Break minutes to deduct: " . $breakMinutesDeducted . PHP_EOL;
    }
} else {
    echo "No overlap with standard break period" . PHP_EOL;
}

// Calculate final hours
$finalMinutes = $totalMinutes - $breakMinutesDeducted;
$finalHours = $finalMinutes / 60;

echo "Final work minutes: " . $finalMinutes . PHP_EOL;
echo "Final work hours: " . $finalHours . PHP_EOL;
echo "Should be: 1.0 hours" . PHP_EOL;
