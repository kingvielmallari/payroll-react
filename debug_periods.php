<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Employee;
use App\Models\PayScheduleSetting;
use App\Http\Controllers\CashAdvanceController;
use Illuminate\Http\Request;

// Test the getEmployeePayrollPeriods method
echo "Testing CashAdvanceController::getEmployeePayrollPeriods\n";
echo "=======================================================\n\n";

// Find a semi-monthly employee (Bernadette)
$employee = Employee::where('employee_number', 'EMP-2025-0011')->first();

if (!$employee) {
    echo "Employee EMP-2025-0011 not found\n";
    exit;
}

echo "Employee found: {$employee->full_name}\n";
echo "Pay Schedule: {$employee->pay_schedule}\n\n";

// Check if PayScheduleSetting exists
$scheduleSetting = PayScheduleSetting::where('code', $employee->pay_schedule)
    ->where('is_active', true)
    ->first();

if (!$scheduleSetting) {
    echo "ERROR: PayScheduleSetting not found for '{$employee->pay_schedule}'\n";
    echo "Available schedule settings:\n";
    $settings = PayScheduleSetting::where('is_active', true)->get();
    foreach ($settings as $setting) {
        echo "  - {$setting->code} ({$setting->name})\n";
    }
    exit;
}

echo "Schedule Setting found: {$scheduleSetting->name}\n";
echo "Cutoff Periods: " . json_encode($scheduleSetting->cutoff_periods) . "\n\n";

// Create a mock request
$request = new Request([
    'employee_id' => $employee->id,
    'monthly_deduction_timing' => 'first_payroll'
]);

// Test the controller method
try {
    $controller = new CashAdvanceController();

    // Use reflection to call the private method
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('getEmployeePayrollPeriods');
    $method->setAccessible(true);

    $response = $method->invoke($controller, $request);
    $data = $response->getData(true);

    echo "Controller Response:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\nTest completed!\n";
