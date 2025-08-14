<?php

require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

use App\Models\PayrollDetail;
use App\Models\Payroll;

// Get the payroll and its details
$payroll = Payroll::with('payrollDetails')->find(13);

if ($payroll) {
    echo "Payroll #13 Details:\n";
    echo "===================\n";

    foreach ($payroll->payrollDetails as $detail) {
        echo "Employee: {$detail->employee_id}\n";
        echo "SSS: {$detail->sss_contribution}\n";
        echo "PhilHealth: {$detail->philhealth_contribution}\n";
        echo "PagIBIG: {$detail->pagibig_contribution}\n";
        echo "Withholding Tax: {$detail->withholding_tax}\n";
        echo "Late: {$detail->late_deductions}\n";
        echo "Undertime: {$detail->undertime_deductions}\n";
        echo "Cash Advance: {$detail->cash_advance_deductions}\n";
        echo "Other: {$detail->other_deductions}\n";
        echo "Stored Total Deductions: {$detail->total_deductions}\n";
        echo "Gross Pay: {$detail->gross_pay}\n";
        echo "Stored Net Pay: {$detail->net_pay}\n";

        // Calculate actual total
        $actualTotal = $detail->sss_contribution + $detail->philhealth_contribution +
            $detail->pagibig_contribution + $detail->withholding_tax +
            $detail->late_deductions + $detail->undertime_deductions +
            $detail->cash_advance_deductions + $detail->other_deductions;

        echo "Calculated Total Deductions: {$actualTotal}\n";
        echo "Correct Net Pay: " . ($detail->gross_pay - $actualTotal) . "\n";
        echo "---\n";
    }
} else {
    echo "Payroll #13 not found\n";
}
