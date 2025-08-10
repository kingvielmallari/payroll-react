<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing payroll calculation fixes...\n\n";

// Test the calculateGrossPay method
$employee = App\Models\Employee::first();
if ($employee) {
    echo "Testing employee: {$employee->first_name} {$employee->last_name}\n";
    echo "Basic salary: " . ($employee->basic_salary ?? 'Not set') . "\n";
    echo "Pay schedule: {$employee->pay_schedule}\n\n";
    
    // Create a dummy controller instance to test the method
    $controller = new App\Http\Controllers\PayrollController();
    
    // Use reflection to access the private method
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('calculateGrossPay');
    $method->setAccessible(true);
    
    // Test with no hours worked
    echo "Test 1: No hours worked (should return 0)\n";
    $result = $method->invoke($controller, $employee, $employee->basic_salary ?? 12000, 0, 0, '2025-08-06', '2025-08-12');
    echo "Result: {$result}\n\n";
    
    // Test with some hours worked
    echo "Test 2: 40 hours worked\n";
    $result = $method->invoke($controller, $employee, $employee->basic_salary ?? 12000, 40, 5, '2025-08-06', '2025-08-12');
    echo "Result: {$result}\n\n";
    
    // Check current time logs for this employee
    $timeLogs = App\Models\TimeLog::where('employee_id', $employee->id)->count();
    echo "Current time logs for this employee: {$timeLogs}\n";
    
} else {
    echo "No employees found in database\n";
}
