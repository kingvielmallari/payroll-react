<?php

// Test undertime logic manually
echo "Testing Undertime Grace Period Logic\n";
echo "=====================================\n\n";

// Assume grace period is 15 minutes (from your screenshot)
$undertimeGracePeriodMinutes = 15;

echo "Undertime Grace Period: {$undertimeGracePeriodMinutes} minutes\n\n";

// Test scenarios based on your data
$testCases = [
    [
        'description' => 'Aug 18 - Left 30 min early (4:30 PM vs 5:00 PM)',
        'early_minutes' => 30,
        'expected_result' => 'Should charge 15 min undertime (30-15=15)'
    ],
    [
        'description' => 'Aug 19 - Left 31 min early (4:29 PM vs 5:00 PM)',
        'early_minutes' => 31,
        'expected_result' => 'Should charge 16 min undertime (31-15=16)'
    ]
];

foreach ($testCases as $case) {
    echo "Test Case: {$case['description']}\n";
    echo "Expected: {$case['expected_result']}\n";

    $earlyMinutes = $case['early_minutes'];
    $undertimeHours = 0;

    if ($earlyMinutes > $undertimeGracePeriodMinutes) {
        $undertimeMinutesToCharge = $earlyMinutes - $undertimeGracePeriodMinutes;
        $undertimeHours = $undertimeMinutesToCharge / 60;
        echo "Actual: Charging {$undertimeMinutesToCharge} min undertime\n";
    } else {
        echo "Actual: Within grace period - no undertime charged\n";
    }

    echo "Undertime Hours: " . round($undertimeHours, 3) . "\n";
    echo "Net Working Hours: " . round(8 - $undertimeHours, 3) . " (assuming 8-hour standard)\n\n";
}

// Show the current vs expected results
echo "COMPARISON WITH YOUR DATA:\n";
echo "==========================\n";
echo "Aug 18: Currently showing 7.5h, should show ~7.75h (8.0 - 0.25)\n";
echo "Aug 19: Currently showing 7.5h, should show ~7.733h (8.0 - 0.267)\n";
echo "\nThe issue is likely that existing time logs are not being recalculated\n";
echo "with the new undertime grace period logic.\n";
