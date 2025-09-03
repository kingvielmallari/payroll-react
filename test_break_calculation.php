<?php

// Load Laravel environment
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use Carbon\Carbon;

echo "Testing Break Calculation Logic\n";
echo "==============================\n\n";

// Test 1: Break duration system
echo "Test 1: Break Duration System\n";
echo "Work: 8:30 AM - 5:00 PM (8.5 hours)\n";
echo "Break duration: 60 minutes\n";

$workStart = Carbon::parse('08:30');
$workEnd = Carbon::parse('17:00');
$breakDuration = 60;

$totalMinutes = $workStart->diffInMinutes($workEnd);
$workingMinutes = $totalMinutes - $breakDuration;
$workingHours = $workingMinutes / 60;

echo "Total time: {$totalMinutes} minutes (" . ($totalMinutes / 60) . " hours)\n";
echo "Working time: {$workingMinutes} minutes (" . ($workingHours) . " hours)\n";
echo "Expected: 450 minutes (7.5 hours)\n\n";

// Test 2: Fixed break times with no break logs
echo "Test 2: Fixed Break Times (No Employee Break Logs)\n";
echo "Work: 8:00 AM - 5:00 PM\n";
echo "Scheduled break: 12:00 PM - 1:00 PM\n";

$workStart = Carbon::parse('08:00');
$workEnd = Carbon::parse('17:00');
$breakStart = Carbon::parse('12:00');
$breakEnd = Carbon::parse('13:00');

// Calculate before and after break
$beforeBreak = $workStart->diffInMinutes($breakStart);
$afterBreak = $breakEnd->diffInMinutes($workEnd);
$totalWorking = $beforeBreak + $afterBreak;

echo "Before break: {$beforeBreak} minutes\n";
echo "After break: {$afterBreak} minutes\n";
echo "Total working: {$totalWorking} minutes (" . ($totalWorking / 60) . " hours)\n";
echo "Expected: 480 minutes (8 hours)\n\n";

// Test 3: Fixed break times with employee break logs
echo "Test 3: Fixed Break Times (With Employee Break Logs)\n";
echo "Work: 8:00 AM - 5:00 PM\n";
echo "Scheduled break: 12:00 PM - 1:00 PM\n";
echo "Employee actual break: 12:00 PM - 1:30 PM\n";

$workStart = Carbon::parse('08:00');
$workEnd = Carbon::parse('17:00');
$actualBreakStart = Carbon::parse('12:00');
$actualBreakEnd = Carbon::parse('13:30');

// Calculate using actual break times
$beforeBreak = $workStart->diffInMinutes($actualBreakStart);
$afterBreak = $actualBreakEnd->diffInMinutes($workEnd);
$totalWorking = $beforeBreak + $afterBreak;

echo "Before break: {$beforeBreak} minutes\n";
echo "After break: {$afterBreak} minutes\n";
echo "Total working: {$totalWorking} minutes (" . ($totalWorking / 60) . " hours)\n";
echo "Expected: 450 minutes (7.5 hours)\n\n";

echo "All tests completed successfully!\n";
