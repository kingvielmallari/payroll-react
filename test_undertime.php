<?php

require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

use Carbon\Carbon;
use App\Models\GracePeriodSetting;

// Test the undertime logic directly
echo "Testing Undertime Grace Period Logic\n";
echo "=====================================\n\n";

// Get current grace period settings
$gracePeriodSettings = GracePeriodSetting::current();
$undertimeGracePeriodMinutes = $gracePeriodSettings->undertime_grace_minutes;

echo "Current Undertime Grace Period: {$undertimeGracePeriodMinutes} minutes\n\n";

// Test scenarios
$testCases = [
    [
        'date' => '2025-08-18',
        'time_out' => '16:30', // 4:30 PM
        'scheduled_end' => '17:00', // 5:00 PM
        'description' => 'Aug 18 - Left 30 min early'
    ],
    [
        'date' => '2025-08-19',
        'time_out' => '16:29', // 4:29 PM
        'scheduled_end' => '17:00', // 5:00 PM
        'description' => 'Aug 19 - Left 31 min early'
    ]
];

foreach ($testCases as $case) {
    echo "Test Case: {$case['description']}\n";
    echo "Time Out: {$case['time_out']}, Scheduled End: {$case['scheduled_end']}\n";

    $actualTimeOut = Carbon::parse($case['date'] . ' ' . $case['time_out']);
    $schedEnd = Carbon::parse($case['date'] . ' ' . $case['scheduled_end']);

    $undertimeHours = 0;

    // Apply the current logic
    if ($actualTimeOut->lt($schedEnd)) {
        $earlyMinutes = $actualTimeOut->diffInMinutes($schedEnd);
        echo "Early Minutes: {$earlyMinutes}\n";

        if ($earlyMinutes > $undertimeGracePeriodMinutes) {
            $undertimeMinutesToCharge = $earlyMinutes - $undertimeGracePeriodMinutes;
            $undertimeHours = $undertimeMinutesToCharge / 60;
            echo "Undertime Minutes to Charge: {$undertimeMinutesToCharge}\n";
        } else {
            echo "Within grace period - no undertime charged\n";
        }
    }

    echo "Calculated Undertime Hours: " . round($undertimeHours, 2) . "\n";
    echo "Expected Working Hours: " . round(8 - $undertimeHours, 2) . " (8 hours - undertime)\n\n";
}
