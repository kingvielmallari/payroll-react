<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Employee;
use App\Models\TimeSchedule;

echo "\n=== Testing Fixed Rate Calculation System ===\n\n";

// Test calculation with different scenarios
$testCases = [
    [
        'name' => 'Monthly Rate Employee (30K)',
        'fixed_rate' => 30000.00,
        'rate_type' => 'monthly',
        'daily_hours' => 8
    ],
    [
        'name' => 'Daily Rate Employee (1,363.64)',
        'fixed_rate' => 1363.64,
        'rate_type' => 'daily',
        'daily_hours' => 8
    ],
    [
        'name' => 'Hourly Rate Employee (170.45)',
        'fixed_rate' => 170.45,
        'rate_type' => 'hourly',
        'daily_hours' => 8
    ],
];

foreach ($testCases as $testCase) {
    echo "--- {$testCase['name']} ---\n";
    echo "Fixed Rate: ₱" . number_format($testCase['fixed_rate'], 2) . "\n";
    echo "Rate Type: {$testCase['rate_type']}\n";
    echo "Daily Hours: {$testCase['daily_hours']}\n";

    // Calculate using the new formula (same as in PayrollController)
    switch ($testCase['rate_type']) {
        case 'hourly':
            $hourlyRate = $testCase['fixed_rate'];
            break;
        case 'daily':
            $hourlyRate = $testCase['fixed_rate'] / $testCase['daily_hours'];
            break;
        case 'weekly':
            $hourlyRate = $testCase['fixed_rate'] / ($testCase['daily_hours'] * 5);
            break;
        case 'semi_monthly':
            $hourlyRate = $testCase['fixed_rate'] / ($testCase['daily_hours'] * 11);
            break;
        case 'monthly':
            $hourlyRate = $testCase['fixed_rate'] / ($testCase['daily_hours'] * 22);
            break;
    }

    echo "Calculated Hourly Rate: ₱" . number_format($hourlyRate, 10) . "\n";

    // Calculate daily rate from hourly
    $dailyRate = $hourlyRate * $testCase['daily_hours'];
    echo "Daily Rate: ₱" . number_format($dailyRate, 2) . "\n";

    // Calculate per minute rate
    $perMinuteRate = $hourlyRate / 60;
    echo "Per Minute Rate: ₱" . number_format($perMinuteRate, 10) . "\n";

    // Test working 10,560 minutes (8 hours * 22 days * 60 minutes)
    $workingMinutes = 10560;
    $totalAmount = $perMinuteRate * $workingMinutes;
    echo "Total for {$workingMinutes} minutes: ₱" . number_format($totalAmount, 2) . "\n";

    echo "\n";
}

echo "=== Example as requested ===\n";
echo "Monthly Rate: ₱30,000.00\n";
echo "Days: 22\n";
echo "Hours: 8\n";
echo "Total working minutes: 10,560\n\n";

$monthlyRate = 30000.00;
$days = 22;
$hours = 8;
$totalMinutes = 10560;

echo "1. Daily Rate: ₱30,000.00 / 22 = ₱" . number_format($monthlyRate / $days, 10) . "\n";

$dailyRate = $monthlyRate / $days;
echo "2. Hourly Rate: ₱{$dailyRate} / 8 = ₱" . number_format($dailyRate / $hours, 10) . "\n";

$hourlyRate = $dailyRate / $hours;
echo "3. Per Minute Rate: ₱{$hourlyRate} / 60 = ₱" . number_format($hourlyRate / 60, 10) . "\n";

$perMinuteRate = $hourlyRate / 60;
echo "4. Total Amount: ₱{$perMinuteRate} * {$totalMinutes} = ₱" . number_format($perMinuteRate * $totalMinutes, 2) . "\n";

echo "\n=== Test Complete ===\n";
