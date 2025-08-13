<?php

require_once __DIR__ . '/vendor/autoload.php';

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\TimeLog;
use App\Models\PayrollRateConfiguration;

echo "=== Testing Rate Multiplier Calculations ===\n\n";

// Test the rate configurations
$configs = PayrollRateConfiguration::where('is_active', true)->get();
echo "Available Rate Configurations:\n";
foreach ($configs as $config) {
    $regularPercent = $config->regular_rate_multiplier * 100;
    $overtimePercent = $config->overtime_rate_multiplier * 100;
    echo "- {$config->type_name} => {$config->display_name}\n";
    echo "  Regular: {$config->regular_rate_multiplier} ({$regularPercent}%)\n";
    echo "  Overtime: {$config->overtime_rate_multiplier} ({$overtimePercent}%)\n\n";
}

// Create test TimeLog instances for different types
$hourlyRate = 200; // ₱200/hour as shown in your image

echo "=== Testing Calculations (Hourly Rate: ₱{$hourlyRate}) ===\n\n";

$testCases = [
    ['type' => 'regular_workday', 'regular_hours' => 8, 'overtime_hours' => 0],
    ['type' => 'rest_day', 'regular_hours' => 8, 'overtime_hours' => 0],
    ['type' => 'regular_holiday', 'regular_hours' => 8, 'overtime_hours' => 2],
    ['type' => 'special_holiday', 'regular_hours' => 8, 'overtime_hours' => 0],
];

foreach ($testCases as $case) {
    $timeLog = new TimeLog();
    $timeLog->log_type = $case['type'];
    $timeLog->regular_hours = $case['regular_hours'];
    $timeLog->overtime_hours = $case['overtime_hours'];

    $rateConfig = $timeLog->getRateConfiguration();
    if ($rateConfig) {
        $payAmounts = $timeLog->calculatePayAmount($hourlyRate);

        echo "Type: {$rateConfig->display_name}\n";
        echo "Hours: {$case['regular_hours']}h regular + {$case['overtime_hours']}h overtime\n";
        echo "Regular Rate: {$rateConfig->regular_rate_multiplier} x ₱{$hourlyRate} = ₱" . number_format($rateConfig->regular_rate_multiplier * $hourlyRate, 2) . "/hr\n";
        echo "Overtime Rate: {$rateConfig->overtime_rate_multiplier} x ₱{$hourlyRate} = ₱" . number_format($rateConfig->overtime_rate_multiplier * $hourlyRate, 2) . "/hr\n";
        echo "Regular Pay: {$case['regular_hours']}h x ₱" . number_format($rateConfig->regular_rate_multiplier * $hourlyRate, 2) . " = ₱" . number_format($payAmounts['regular_amount'], 2) . "\n";
        if ($case['overtime_hours'] > 0) {
            echo "Overtime Pay: {$case['overtime_hours']}h x ₱" . number_format($rateConfig->overtime_rate_multiplier * $hourlyRate, 2) . " = ₱" . number_format($payAmounts['overtime_amount'], 2) . "\n";
        }
        echo "Total Pay: ₱" . number_format($payAmounts['total_amount'], 2) . "\n";
        echo "---\n\n";
    } else {
        echo "No rate config found for {$case['type']}\n\n";
    }
}

echo "Test completed!\n";
