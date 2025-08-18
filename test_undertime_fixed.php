<?php

// Test the fixed undertime logic - simulating like late grace period
echo "Testing FIXED Undertime Grace Period Logic\n";
echo "==========================================\n\n";

// Test settings: 30-minute undertime grace period
$undertimeGracePeriodMinutes = 30;
$schedEndTime = '17:00'; // 5:00 PM

echo "Undertime Grace Period: {$undertimeGracePeriodMinutes} minutes\n";
echo "Scheduled End Time: {$schedEndTime}\n\n";

$testCases = [
    [
        'description' => 'Aug 18 - Left 30 min early (4:30 PM vs 5:00 PM)',
        'actual_time_out' => '16:30', // 4:30 PM
        'early_minutes' => 30,
        'expected' => 'WITHIN grace period = Full 8 hours (like late grace period)'
    ],
    [
        'description' => 'Aug 19 - Left 31 min early (4:29 PM vs 5:00 PM)',
        'actual_time_out' => '16:29', // 4:29 PM
        'early_minutes' => 31,
        'expected' => 'BEYOND grace period = 7.98 hours (31-30=1 min deducted)'
    ]
];

foreach ($testCases as $case) {
    echo "Test Case: {$case['description']}\n";
    echo "Expected: {$case['expected']}\n";

    $earlyMinutes = $case['early_minutes'];

    // NEW LOGIC: Adjust work end time (like late grace period does for start time)
    if ($earlyMinutes <= $undertimeGracePeriodMinutes) {
        // Within grace period - use scheduled end time for calculation
        $effectiveEndTime = $schedEndTime;
        $workingHours = 8.0; // Full hours
        $undertimeHours = 0;
        echo "Actual: Within grace period - Full working hours credited\n";
    } else {
        // Beyond grace period - use actual time out
        $effectiveEndTime = $case['actual_time_out'];
        $undertimeMinutesToCharge = $earlyMinutes - $undertimeGracePeriodMinutes;
        $undertimeHours = $undertimeMinutesToCharge / 60;
        $workingHours = 8.0 - $undertimeHours;
        echo "Actual: Beyond grace period - {$undertimeMinutesToCharge} min deducted\n";
    }

    echo "Effective End Time: {$effectiveEndTime}\n";
    echo "Working Hours: " . round($workingHours, 3) . "\n";
    echo "Undertime Hours: " . round($undertimeHours, 3) . "\n\n";
}

echo "KEY INSIGHT:\n";
echo "=============\n";
echo "Just like late grace period adjusts WORK START TIME,\n";
echo "undertime grace period should adjust WORK END TIME!\n";
echo "\nThis way the total hours worked reflects the grace period benefit.\n";
