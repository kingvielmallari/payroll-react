<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\PayrollSnapshot;
use Carbon\Carbon;

echo "Testing Dynamic Payroll System\n";
echo "==============================\n\n";

try {
    // Get first active employee
    $employee = Employee::where('employment_status', 'active')->first();
    
    if (!$employee) {
        echo "❌ No active employees found.\n";
        exit(1);
    }
    
    echo "📋 Testing with employee: {$employee->employee_number} - {$employee->first_name} {$employee->last_name}\n";
    echo "📋 Benefits status: {$employee->benefits_status}\n";
    echo "📋 Pay schedule: {$employee->pay_schedule}\n\n";
    
    // Create a test payroll in draft status
    $payroll = Payroll::create([
        'payroll_number' => 'TEST-' . now()->format('YmdHis'),
        'period_start' => Carbon::now()->startOfMonth(),
        'period_end' => Carbon::now()->endOfMonth(),
        'pay_date' => Carbon::now()->addDays(5),
        'payroll_type' => 'regular',
        'pay_schedule' => $employee->pay_schedule,
        'status' => 'draft',
        'description' => 'Dynamic Payroll System Test',
        'created_by' => 1,
    ]);
    
    echo "✅ Created test payroll: {$payroll->payroll_number}\n";
    echo "📋 Status: {$payroll->status}\n";
    echo "📋 Is Dynamic: " . ($payroll->isDynamic() ? 'Yes' : 'No') . "\n";
    echo "📋 Uses Snapshot: " . ($payroll->usesSnapshot() ? 'Yes' : 'No') . "\n\n";
    
    // Test dynamic calculation (this will use current settings)
    $controller = new App\Http\Controllers\PayrollController();
    $calculateMethod = new ReflectionMethod($controller, 'calculateEmployeePayroll');
    $calculateMethod->setAccessible(true);
    
    echo "🔄 Calculating payroll dynamically...\n";
    $payrollDetail = $calculateMethod->invoke($controller, $employee, $payroll);
    
    echo "✅ Dynamic calculation completed\n";
    echo "📋 Gross Pay: ₱" . number_format($payrollDetail->gross_pay, 2) . "\n";
    echo "📋 Net Pay: ₱" . number_format($payrollDetail->net_pay, 2) . "\n";
    echo "📋 Allowances: ₱" . number_format($payrollDetail->allowances, 2) . "\n";
    echo "📋 Total Deductions: ₱" . number_format($payrollDetail->total_deductions, 2) . "\n\n";
    
    // Test moving to processing (creates snapshots)
    echo "🔄 Moving payroll to processing status...\n";
    
    $processMethod = new ReflectionMethod($controller, 'createPayrollSnapshots');
    $processMethod->setAccessible(true);
    $processMethod->invoke($controller, $payroll);
    
    $payroll->update([
        'status' => 'processing',
        'processing_started_at' => now(),
        'processing_by' => 1,
    ]);
    
    echo "✅ Payroll moved to processing\n";
    echo "📋 Status: {$payroll->status}\n";
    echo "📋 Is Dynamic: " . ($payroll->isDynamic() ? 'Yes' : 'No') . "\n";
    echo "📋 Uses Snapshot: " . ($payroll->usesSnapshot() ? 'Yes' : 'No') . "\n\n";
    
    // Check if snapshot was created
    $snapshot = PayrollSnapshot::where('payroll_id', $payroll->id)
                              ->where('employee_id', $employee->id)
                              ->first();
    
    if ($snapshot) {
        echo "✅ Snapshot created successfully\n";
        echo "📋 Employee: {$snapshot->employee_name}\n";
        echo "📋 Gross Pay: ₱" . number_format($snapshot->gross_pay, 2) . "\n";
        echo "📋 Net Pay: ₱" . number_format($snapshot->net_pay, 2) . "\n";
        echo "📋 Settings captured: " . (is_array($snapshot->settings_snapshot) ? 'Yes' : 'No') . "\n\n";
    } else {
        echo "❌ No snapshot found\n\n";
    }
    
    // Test calculation from snapshot
    echo "🔄 Testing calculation from snapshot...\n";
    $payrollDetail2 = $calculateMethod->invoke($controller, $employee, $payroll);
    
    echo "✅ Snapshot-based calculation completed\n";
    echo "📋 Gross Pay: ₱" . number_format($payrollDetail2->gross_pay, 2) . "\n";
    echo "📋 Net Pay: ₱" . number_format($payrollDetail2->net_pay, 2) . "\n\n";
    
    // Verify values match
    if (abs($payrollDetail->gross_pay - $payrollDetail2->gross_pay) < 0.01) {
        echo "✅ Snapshot values match dynamic calculation\n";
    } else {
        echo "❌ Snapshot values don't match dynamic calculation\n";
        echo "   Dynamic: ₱" . number_format($payrollDetail->gross_pay, 2) . "\n";
        echo "   Snapshot: ₱" . number_format($payrollDetail2->gross_pay, 2) . "\n";
    }
    
    echo "\n🧹 Cleaning up test data...\n";
    
    // Clean up
    PayrollDetail::where('payroll_id', $payroll->id)->delete();
    PayrollSnapshot::where('payroll_id', $payroll->id)->delete();
    $payroll->delete();
    
    echo "✅ Test completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
