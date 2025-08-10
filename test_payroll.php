<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Testing payroll functionality...\n";

// Check if payroll exists
$payroll = App\Models\Payroll::find(40);
if ($payroll) {
    echo "Payroll ID: " . $payroll->id . "\n";
    echo "Status: " . $payroll->status . "\n";
    echo "Pay Schedule: " . $payroll->pay_schedule . "\n";
    echo "Details Count: " . $payroll->payrollDetails()->count() . "\n";
    echo "Can be edited: " . ($payroll->canBeEdited() ? 'Yes' : 'No') . "\n";
    
    // Check PayScheduleSetting
    $setting = App\Models\PayScheduleSetting::where('code', $payroll->pay_schedule)
        ->where('is_active', true)
        ->first();
    
    if ($setting) {
        echo "PayScheduleSetting found: " . $setting->name . "\n";
    } else {
        echo "PayScheduleSetting NOT found for code: " . $payroll->pay_schedule . "\n";
    }
} else {
    echo "Payroll not found\n";
}
