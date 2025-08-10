<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$payroll = App\Models\Payroll::with([
    'payrollDetails.employee.user',
    'payrollDetails.employee.daySchedule',
    'payrollDetails.employee.timeSchedule'
])->first();

if ($payroll) {
    echo "=== Testing Payroll Display Updates ===\n";
    echo "Payroll ID: " . $payroll->id . "\n";
    
    foreach ($payroll->payrollDetails as $detail) {
        echo "\nEmployee: " . $detail->employee->user->name . "\n";
        echo "Schedule: " . ($detail->employee->schedule_display ?? 'No schedule') . "\n";
        echo "Regular Pay: ₱" . number_format($detail->regular_pay, 2) . "\n";
        echo "Overtime Pay: ₱" . number_format($detail->overtime_pay, 2) . "\n";
        echo "Gross Pay: ₱" . number_format($detail->gross_pay, 2) . "\n";
        echo "Deductions: ₱" . number_format($detail->total_deductions, 2) . "\n";
        echo "Net Pay: ₱" . number_format($detail->net_pay, 2) . "\n";
        
        // Calculate working hours from time logs
        $timeLogs = App\Models\TimeLog::where('employee_id', $detail->employee_id)
            ->whereBetween('log_date', [$payroll->period_start, $payroll->period_end])
            ->get();
            
        $totalHours = $timeLogs->sum('regular_hours');
        echo "Total Working Hours: " . number_format($totalHours, 1) . " hrs\n";
    }
} else {
    echo "No payroll found\n";
}
