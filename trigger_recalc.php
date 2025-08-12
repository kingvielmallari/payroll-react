<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Payroll;
use App\Http\Controllers\PayrollController;

echo "Triggering Payroll Recalculation\n";
echo "================================\n\n";

$payrollId = 93;
$payroll = Payroll::find($payrollId);

if (!$payroll) {
    echo "Payroll not found!\n";
    exit;
}

echo "Before recalculation:\n";
echo "Total Basic: ₱" . number_format($payroll->total_gross ?? 0, 2) . "\n";
echo "Total Deductions: ₱" . number_format($payroll->total_deductions ?? 0, 2) . "\n";
echo "Total Net: ₱" . number_format($payroll->total_net ?? 0, 2) . "\n\n";

// Force recalculation by calling the controller method
$controller = new PayrollController();

// Use reflection to call the private method
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('autoRecalculateIfNeeded');
$method->setAccessible(true);

try {
    $method->invoke($controller, $payroll);
    echo "Recalculation completed successfully!\n\n";
} catch (Exception $e) {
    echo "Error during recalculation: " . $e->getMessage() . "\n\n";
}

// Refresh the payroll data
$payroll->refresh();

echo "After recalculation:\n";
echo "Total Basic: ₱" . number_format($payroll->total_gross ?? 0, 2) . "\n";
echo "Total Deductions: ₱" . number_format($payroll->total_deductions ?? 0, 2) . "\n";
echo "Total Net: ₱" . number_format($payroll->total_net ?? 0, 2) . "\n";

// Also check the payroll detail
$payrollDetail = $payroll->payrollDetails->first();
if ($payrollDetail) {
    echo "\nEmployee Payroll Detail:\n";
    echo "Regular Pay (Basic Pay): ₱" . number_format($payrollDetail->regular_pay ?? 0, 2) . "\n";
    echo "Regular Hours: " . ($payrollDetail->regular_hours ?? 0) . "\n";
    echo "Overtime Pay: ₱" . number_format($payrollDetail->overtime_pay ?? 0, 2) . "\n";
    echo "Overtime Hours: " . ($payrollDetail->overtime_hours ?? 0) . "\n";
    echo "Gross Pay: ₱" . number_format($payrollDetail->gross_pay ?? 0, 2) . "\n";
    echo "Total Deductions: ₱" . number_format($payrollDetail->total_deductions ?? 0, 2) . "\n";
    echo "Net Pay: ₱" . number_format($payrollDetail->net_pay ?? 0, 2) . "\n";
}
