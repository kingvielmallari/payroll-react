<?php
require_once 'vendor/autoload.php';

try {
    $app = require_once 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    echo "Bootstrap successful\n";
    
    // Test basic model access
    $payroll = App\Models\Payroll::find(40);
    echo "Payroll found: " . ($payroll ? 'Yes' : 'No') . "\n";
    
    if ($payroll) {
        echo "Status: " . $payroll->status . "\n";
        echo "Can be edited: " . ($payroll->canBeEdited() ? 'Yes' : 'No') . "\n";
        
        // Test PayScheduleSetting query
        $setting = App\Models\PayScheduleSetting::where('code', $payroll->pay_schedule)
            ->where('is_active', true)
            ->first();
        echo "PayScheduleSetting found: " . ($setting ? 'Yes' : 'No') . "\n";
        
        // Test Employee query
        $employees = App\Models\Employee::with(['department', 'position', 'user'])
            ->whereHas('user', function($query) {
                $query->where('status', 'active');
            })
            ->get();
        echo "Active employees count: " . $employees->count() . "\n";
        
        // Test payroll details access
        $details = $payroll->payrollDetails;
        echo "Payroll details count: " . $details->count() . "\n";
        
        if ($details->count() > 0) {
            $detail = $details->first();
            echo "First detail employee_id: " . $detail->employee_id . "\n";
            
            // Test time logs query
            $timeLogs = App\Models\TimeLog::where('employee_id', $detail->employee_id)
                ->whereBetween('log_date', [$payroll->period_start, $payroll->period_end])
                ->get();
            echo "Time logs count: " . $timeLogs->count() . "\n";
        }
    }
    
    echo "All basic tests passed\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
