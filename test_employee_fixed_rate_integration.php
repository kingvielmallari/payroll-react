<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Employee;

echo "\n=== Testing Employee Fixed Rate System Integration ===\n\n";

// Get the first employee
$employee = Employee::where('employment_status', 'active')->first();

if (!$employee) {
    echo "No active employees found.\n";
    exit;
}

echo "Original Employee Data:\n";
echo "- Name: {$employee->first_name} {$employee->last_name}\n";
echo "- Employee Number: {$employee->employee_number}\n";
echo "- Current hourly_rate: ₱" . number_format($employee->hourly_rate ?? 0, 2) . "\n";
echo "- Current basic_salary: ₱" . number_format($employee->basic_salary ?? 0, 2) . "\n";
echo "- Current fixed_rate: ₱" . number_format($employee->fixed_rate ?? 0, 2) . "\n";
echo "- Current rate_type: " . ($employee->rate_type ?? 'not set') . "\n\n";

// Test Case 1: Set a monthly fixed rate
echo "=== Test Case 1: Setting Monthly Fixed Rate ===\n";
$employee->fixed_rate = 30000.00;
$employee->rate_type = 'monthly';
$employee->save();

echo "Set fixed_rate to ₱30,000.00 (monthly)\n";
echo "Employee should now calculate hourly rate from fixed_rate instead of hourly_rate field\n\n";

// Test Case 2: Create a draft payroll to see the calculation
echo "=== Test Case 2: Testing Draft Payroll Calculation ===\n";

// Simulate what PayrollController does
$payrollController = new \App\Http\Controllers\PayrollController();
$reflection = new ReflectionClass($payrollController);
$method = $reflection->getMethod('calculateHourlyRate');
$method->setAccessible(true);

$calculatedHourlyRate = $method->invoke($payrollController, $employee, $employee->basic_salary ?? 0);

echo "Calculated Hourly Rate: ₱" . number_format($calculatedHourlyRate, 10) . "\n";

// Expected calculation: 30000 / 22 days / 8 hours = 170.454545...
$expectedHourlyRate = 30000 / 22 / 8;
echo "Expected Hourly Rate: ₱" . number_format($expectedHourlyRate, 10) . "\n";

if (abs($calculatedHourlyRate - $expectedHourlyRate) < 0.0001) {
    echo "✅ Calculation CORRECT!\n";
} else {
    echo "❌ Calculation INCORRECT!\n";
}

// Test per-minute rate
$perMinuteRate = $calculatedHourlyRate / 60;
echo "Per-minute rate: ₱" . number_format($perMinuteRate, 10) . "\n";

// Test full month calculation (10,560 minutes)
$fullMonthAmount = $perMinuteRate * 10560;
echo "Full month amount (10,560 minutes): ₱" . number_format($fullMonthAmount, 2) . "\n";

if (abs($fullMonthAmount - 30000.00) < 0.01) {
    echo "✅ Full month calculation CORRECT!\n";
} else {
    echo "❌ Full month calculation INCORRECT!\n";
}

echo "\n=== Test Case 3: Different Rate Types ===\n";

$testRates = [
    ['rate_type' => 'daily', 'fixed_rate' => 1363.64],
    ['rate_type' => 'hourly', 'fixed_rate' => 170.45],
    ['rate_type' => 'weekly', 'fixed_rate' => 6818.18],
    ['rate_type' => 'semi_monthly', 'fixed_rate' => 15000.00],
];

foreach ($testRates as $testRate) {
    $employee->rate_type = $testRate['rate_type'];
    $employee->fixed_rate = $testRate['fixed_rate'];
    $employee->save();

    $calculatedHourlyRate = $method->invoke($payrollController, $employee, $employee->basic_salary ?? 0);

    echo "Rate Type: {$testRate['rate_type']}, Fixed Rate: ₱" . number_format($testRate['fixed_rate'], 2) . "\n";
    echo "Calculated Hourly: ₱" . number_format($calculatedHourlyRate, 6) . "\n";

    // They should all result in approximately the same hourly rate (~170.45)
    if ($calculatedHourlyRate > 170.40 && $calculatedHourlyRate < 170.50) {
        echo "✅ Rate conversion CORRECT!\n";
    } else {
        echo "❌ Rate conversion INCORRECT!\n";
    }
    echo "\n";
}

// Reset to original state
echo "=== Resetting Employee to Original State ===\n";
$employee->fixed_rate = null;
$employee->rate_type = null;
$employee->save();
echo "Employee reset to original state.\n";

echo "\n=== Test Complete ===\n";
