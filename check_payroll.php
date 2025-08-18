<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$payroll = App\Models\Payroll::where('status', 'processing')->latest()->first();
if ($payroll) {
    echo 'Payroll Status: ' . $payroll->status . "\n";
    echo 'Payroll Details:' . "\n";
    foreach ($payroll->payrollDetails as $detail) {
        echo '  Employee: ' . $detail->employee->first_name . ' ' . $detail->employee->last_name . "\n";
        echo '  Rest Day Pay: ' . $detail->rest_day_pay . "\n";
        echo '  Rest Day Hours: ' . $detail->rest_day_hours . "\n";
        echo '  Regular Pay: ' . $detail->regular_pay . "\n";
        echo '  Holiday Pay: ' . $detail->holiday_pay . "\n";
        echo "\n";
    }
} else {
    echo 'No processing payroll found';
}
