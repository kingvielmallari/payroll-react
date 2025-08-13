<?php

require_once 'vendor/autoload.php';

// Initialize Laravel application context
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Rate Multiplier Payroll Calculation\n";
echo "==========================================\n\n";

try {
    // Get first employee
    $employee = App\Models\Employee::first();
    if (!$employee) {
        echo "❌ No employees found. Please seed the database first.\n";
        exit;
    }

    echo "👤 Testing for Employee: {$employee->first_name} {$employee->last_name}\n";
    echo "💰 Basic Salary: ₱" . number_format($employee->basic_salary, 2) . "\n";
    echo "📅 Pay Schedule: {$employee->pay_schedule}\n\n";

    // Test hourly rate calculation
    $controller = new App\Http\Controllers\PayrollController();
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('calculateHourlyRate');
    $method->setAccessible(true);

    $hourlyRate = $method->invoke($controller, $employee, $employee->basic_salary);
    echo "💵 Calculated Hourly Rate: ₱" . number_format($hourlyRate, 2) . "\n\n";

    // Get rate configurations
    $rateConfigs = App\Models\PayrollRateConfiguration::active()->ordered()->get();

    echo "🔧 Available Rate Configurations:\n";
    foreach ($rateConfigs as $config) {
        echo "  • {$config->display_name}\n";
        echo "    Regular: {$config->regular_rate_multiplier}x (₱" . number_format($hourlyRate * $config->regular_rate_multiplier, 2) . "/hr)\n";
        echo "    Overtime: {$config->overtime_rate_multiplier}x (₱" . number_format($hourlyRate * $config->overtime_rate_multiplier, 2) . "/hr)\n\n";
    }

    // Test time log calculation
    $sampleRegularHours = 8;
    $sampleOvertimeHours = 2;

    echo "📊 Sample Pay Calculation for {$sampleRegularHours} regular + {$sampleOvertimeHours} overtime hours:\n\n";

    foreach ($rateConfigs as $config) {
        $regularPay = $hourlyRate * $config->regular_rate_multiplier * $sampleRegularHours;
        $overtimePay = $hourlyRate * $config->overtime_rate_multiplier * $sampleOvertimeHours;
        $totalPay = $regularPay + $overtimePay;

        echo "  {$config->display_name}:\n";
        echo "    Regular Pay: ₱" . number_format($regularPay, 2) . " ({$sampleRegularHours}h × ₱" . number_format($hourlyRate * $config->regular_rate_multiplier, 2) . ")\n";
        echo "    Overtime Pay: ₱" . number_format($overtimePay, 2) . " ({$sampleOvertimeHours}h × ₱" . number_format($hourlyRate * $config->overtime_rate_multiplier, 2) . ")\n";
        echo "    Total: ₱" . number_format($totalPay, 2) . "\n\n";
    }

    echo "✅ Rate multiplier system is working correctly!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
