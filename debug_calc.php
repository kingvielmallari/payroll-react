<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Payroll;
use App\Models\Employee;
use App\Http\Controllers\PayrollController;

echo "Debug Payroll Calculation\n";
echo "========================\n\n";

$payrollId = 93;
$employeeId = 12;

$payroll = Payroll::find($payrollId);
$employee = Employee::find($employeeId);

if (!$payroll || !$employee) {
    echo "Payroll or Employee not found!\n";
    exit;
}

echo "Payroll: {$payroll->id} (Status: {$payroll->status})\n";
echo "Employee: {$employee->first_name} {$employee->last_name} (ID: {$employee->id})\n\n";

// Test calling the calculateEmployeePayrollDynamic method directly
$controller = new PayrollController();
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('calculateEmployeePayrollDynamic');
$method->setAccessible(true);

try {
    echo "Calling calculateEmployeePayrollDynamic...\n";
    $result = $method->invoke($controller, $employee, $payroll);

    echo "Result type: " . get_class($result) . "\n";
    echo "Regular Pay: ₱" . number_format($result->regular_pay ?? 0, 2) . "\n";
    echo "Regular Hours: " . ($result->regular_hours ?? 0) . "\n";
    echo "Overtime Pay: ₱" . number_format($result->overtime_pay ?? 0, 2) . "\n";
    echo "Overtime Hours: " . ($result->overtime_hours ?? 0) . "\n";
    echo "Gross Pay: ₱" . number_format($result->gross_pay ?? 0, 2) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
