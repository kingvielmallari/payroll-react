<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Break Calculation with Break Times Present ===" . PHP_EOL;

// Test the 8:00 AM - 5:00 PM with 12:00 PM - 1:00 PM break scenario
$logDate = '2025-08-05';
$timeIn = '08:00:00';
$timeOut = '17:00:00';
$breakIn = '12:00:00';
$breakOut = '13:00:00';

echo "Testing: " . $timeIn . " to " . $timeOut . " with break " . $breakIn . " to " . $breakOut . PHP_EOL;

// Parse the times
$timeInCarbon = \Carbon\Carbon::parse($logDate . ' ' . $timeIn);
$timeOutCarbon = \Carbon\Carbon::parse($logDate . ' ' . $timeOut);

echo "Work period: " . $timeInCarbon->format('H:i') . " - " . $timeOutCarbon->format('H:i') . PHP_EOL;

// Calculate total work minutes
$totalMinutes = $timeInCarbon->diffInMinutes($timeOutCarbon);
echo "Total work minutes (before break): " . $totalMinutes . PHP_EOL;

// Calculate break deduction
$breakMinutesDeducted = 0;

if ($breakIn && $breakOut) {
    echo "Break times are present, using recorded break times" . PHP_EOL;
    $breakInCarbon = \Carbon\Carbon::parse($logDate . ' ' . $breakIn);
    $breakOutCarbon = \Carbon\Carbon::parse($logDate . ' ' . $breakOut);

    echo "Break period: " . $breakInCarbon->format('H:i') . " - " . $breakOutCarbon->format('H:i') . PHP_EOL;

    if ($breakOutCarbon->gt($breakInCarbon)) {
        $breakMinutesDeducted = $breakInCarbon->diffInMinutes($breakOutCarbon);
        echo "Break minutes deducted: " . $breakMinutesDeducted . PHP_EOL;
    }
}

// Calculate final hours
$finalMinutes = $totalMinutes - $breakMinutesDeducted;
$finalHours = $finalMinutes / 60;

echo "Final work minutes: " . $finalMinutes . PHP_EOL;
echo "Final work hours: " . $finalHours . PHP_EOL;
echo "Should be: 8.0 hours" . PHP_EOL;

echo PHP_EOL . "=== Checking actual database record ===" . PHP_EOL;

// Check if there's an actual time log for Aug 5
$timeLog = App\Models\TimeLog::where('employee_id', 1)
    ->whereDate('log_date', '2025-08-05')
    ->first();

if ($timeLog) {
    echo "Found time log for Aug 5:" . PHP_EOL;
    echo "Time In: " . $timeLog->time_in . PHP_EOL;
    echo "Time Out: " . $timeLog->time_out . PHP_EOL;
    echo "Break In: " . ($timeLog->break_in ?? 'NULL') . PHP_EOL;
    echo "Break Out: " . ($timeLog->break_out ?? 'NULL') . PHP_EOL;
    echo "Current Regular Hours: " . $timeLog->regular_hours . PHP_EOL;

    // Test recalculation
    echo PHP_EOL . "Recalculating..." . PHP_EOL;
    $controller = new App\Http\Controllers\TimeLogController();
    $controller->calculateHours($timeLog);

    $timeLog->refresh();
    echo "After recalculation: " . $timeLog->regular_hours . "h" . PHP_EOL;
} else {
    echo "No time log found for Aug 5, 2025" . PHP_EOL;
}
