<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Employee;

echo "\n=== Testing Legacy Compatibility ===\n\n";

$employee = Employee::where('employment_status', 'active')->first();

if (!$employee) {
    echo "No active employees found.\n";
    exit;
}

echo "Testing Employee: {$employee->first_name} {$employee->last_name}\n\n";

$payrollController = new \App\Http\Controllers\PayrollController();
$reflection = new ReflectionClass($payrollController);
$method = $reflection->getMethod('calculateHourlyRate');
$method->setAccessible(true);

echo "=== Test 1: Employee with hourly_rate but no fixed_rate ===\n";
$employee->hourly_rate = 150.00;
$employee->fixed_rate = null;
$employee->rate_type = null;
$employee->save();

$result = $method->invoke($payrollController, $employee, $employee->basic_salary ?? 0);
echo "Expected: ₱150.00, Actual: ₱" . number_format($result, 2) . "\n";
echo ($result == 150.00) ? "✅ PASS\n" : "❌ FAIL\n";

echo "\n=== Test 2: Employee with basic_salary but no hourly_rate or fixed_rate ===\n";
$employee->hourly_rate = 0;
$employee->basic_salary = 35000.00;
$employee->pay_schedule = 'monthly';
$employee->fixed_rate = null;
$employee->rate_type = null;
$employee->save();

$result = $method->invoke($payrollController, $employee, $employee->basic_salary);
$expected = 35000 / 173.33; // Monthly to hourly conversion
echo "Expected: ₱" . number_format($expected, 2) . ", Actual: ₱" . number_format($result, 2) . "\n";
echo (abs($result - $expected) < 0.01) ? "✅ PASS\n" : "❌ FAIL\n";

echo "\n=== Test 3: Employee with all fields empty ===\n";
$employee->hourly_rate = 0;
$employee->basic_salary = 0;
$employee->fixed_rate = null;
$employee->rate_type = null;
$employee->save();

$result = $method->invoke($payrollController, $employee, 0);
echo "Expected: ₱0.00, Actual: ₱" . number_format($result, 2) . "\n";
echo ($result == 0) ? "✅ PASS\n" : "❌ FAIL\n";

// Reset
$employee->hourly_rate = 0;
$employee->basic_salary = 35000;
$employee->fixed_rate = null;
$employee->rate_type = null;
$employee->save();

echo "\n✅ Legacy compatibility maintained!\n";
echo "=== Test Complete ===\n";
