<?php

require_once 'vendor/autoload.php';

use Carbon\Carbon;

// Simple test for HTML time input processing
function processHtmlTimeInput($timeValue)
{
    if (empty($timeValue) || trim($timeValue) === '') {
        return null;
    }

    // Clean the input
    $timeValue = trim($timeValue);

    // HTML time inputs send "HH:MM" format (e.g., "14:30")
    // Validate it's in the expected format
    if (preg_match('/^([01][0-9]|2[0-3]):([0-5][0-9])$/', $timeValue)) {
        return $timeValue; // Already in correct format for database
    }

    // Handle edge case where seconds might be included "HH:MM:SS"
    if (preg_match('/^([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/', $timeValue)) {
        return substr($timeValue, 0, 5); // Return just "HH:MM" part
    }

    // If it doesn't match, return null (invalid time)
    echo "Invalid time format received: '{$timeValue}'\n";
    return null;
}

// Test cases
$testValues = [
    '08:00',
    '17:00',
    '12:30',
    '00:00',
    '23:59',
    '08:00:00',
    '17:30:45',
    '',
    null,
    '25:00', // Invalid
    '8:00',  // Invalid (should be 08:00)
    '08:60', // Invalid
];

echo "Testing HTML time input processing:\n";
echo "=====================================\n";

foreach ($testValues as $test) {
    $result = processHtmlTimeInput($test);
    echo "Input: '" . ($test ?? 'null') . "' => Output: '" . ($result ?? 'null') . "'\n";
}

echo "\nTesting Carbon parsing:\n";
echo "=======================\n";

$validTimes = ['08:00', '17:00', '12:30'];
foreach ($validTimes as $time) {
    try {
        $carbon = Carbon::createFromFormat('H:i', $time);
        echo "Time: {$time} => Carbon: {$carbon->format('H:i')} (Success)\n";
    } catch (\Exception $e) {
        echo "Time: {$time} => Error: {$e->getMessage()}\n";
    }
}
