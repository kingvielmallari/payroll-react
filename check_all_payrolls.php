<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$payrolls = App\Models\Payroll::orderBy('created_at', 'desc')->take(5)->get();
echo "Recent Payrolls:\n";
foreach ($payrolls as $payroll) {
    echo "ID: {$payroll->id}, Status: {$payroll->status}, Number: {$payroll->payroll_number}, Created: {$payroll->created_at}\n";
    if ($payroll->payrollDetails->count() > 0) {
        $detail = $payroll->payrollDetails->first();
        echo "  Employee: {$detail->employee->first_name} {$detail->employee->last_name}\n";
        echo "  Rest Day Pay: {$detail->rest_day_pay}\n";
        echo "  Rest Day Hours: {$detail->rest_day_hours}\n";
    }
    echo "\n";
}
