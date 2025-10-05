<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - {{ $payrollDetail->employee->first_name }} {{ $payrollDetail->employee->last_name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 15px;
            background: white;
        }
        
        .payslip-container {
            max-width: 8in;
            margin: 0 auto;
            background: white;
        }
        
        .header-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        
        .header-left, .header-right {
            display: table-cell;
            vertical-align: top;
            width: 50%;
        }
        
        .header-right {
            text-align: right;
        }
        
        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .company-address {
            font-size: 9px;
            color: #666;
            line-height: 1.3;
        }
        
        .payslip-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .period-info {
            font-size: 10px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .info-left, .info-right {
            display: table-cell;
            vertical-align: top;
            width: 50%;
            padding-right: 10px;
        }
        
        .info-section {
            margin-bottom: 15px;
        }
        
        .info-section h3 {
            font-size: 12px;
            font-weight: bold;
            margin: 0 0 8px 0;
            padding-bottom: 3px;
            border-bottom: 1px solid #ccc;
        }
        
        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 3px;
            font-size: 10px;
        }
        
        .info-label, .info-value {
            display: table-cell;
        }
        
        .info-label {
            font-weight: bold;
            width: 40%;
        }
        
        .earnings-deductions-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .earnings-col, .deductions-col {
            display: table-cell;
            vertical-align: top;
            width: 50%;
            padding-right: 10px;
        }
        
        .section-header {
            font-size: 12px;
            font-weight: bold;
            margin: 0 0 8px 0;
            padding-bottom: 3px;
        }
        
        .earnings-header {
            border-bottom: 1px solid #2e7d32;
        }
        
        .deductions-header {
            border-bottom: 1px solid #d32f2f;
        }
        
        .amount-row {
            display: table;
            width: 100%;
            margin-bottom: 2px;
            font-size: 10px;
            padding: 2px 0;
            border-bottom: 1px solid #eee;
        }
        
        .amount-label, .amount-value {
            display: table-cell;
        }
        
        .amount-label {
            width: 60%;
        }
        
        .amount-value {
            text-align: right;
            font-weight: bold;
        }
        
        .summary-box {
            border: 2px solid;
            border-radius: 5px;
            padding: 10px;
            text-align: center;
            margin: 10px 0;
            font-weight: bold;
        }
        
        .gross-pay {
            border-color: #2e7d32;
            background-color: #f8fff8;
            color: #2e7d32;
        }
        
        .total-deductions {
            border-color: #d32f2f;
            background-color: #fff8f8;
            color: #d32f2f;
        }
        
        .net-pay {
            border-color: #7b1fa2;
            background-color: #f3e5f5;
            color: #7b1fa2;
            font-size: 14px;
        }
        
        .signatures {
            display: table;
            width: 100%;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        
        .sig-left, .sig-right {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: bottom;
        }
        
        .sig-line {
            border-bottom: 1px solid #333;
            margin-bottom: 5px;
            height: 40px;
        }
        
        .sig-label {
            font-size: 9px;
            color: #666;
        }
        
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 8px;
            color: #999;
        }
    </style>
</head>
<body>
    @php
        // Get the snapshot data for this employee to get correct calculated values
        $employeeSnapshot = $snapshot ?? \App\Models\PayrollSnapshot::where('payroll_id', $payrollDetail->payroll->id)
            ->where('employee_id', $payrollDetail->employee_id)
            ->first();
            
        // Use the EXACT same calculation logic as the web payslip view
        // Calculate basic pay from basic_breakdown JSON to match draft calculation
        $actualBasicPay = 0;
        if ($employeeSnapshot && $employeeSnapshot->basic_breakdown) {
            $basicBreakdown = is_string($employeeSnapshot->basic_breakdown) ? 
                json_decode($employeeSnapshot->basic_breakdown, true) : 
                $employeeSnapshot->basic_breakdown;
            if (is_array($basicBreakdown)) {
                foreach ($basicBreakdown as $type => $data) {
                    $actualBasicPay += $data['amount'] ?? 0;
                }
            }
        } else {
            // Fallback to raw value if no breakdown available
            $actualBasicPay = $employeeSnapshot ? $employeeSnapshot->regular_pay : $payrollDetail->regular_pay;
        }
        
        // Calculate holiday pay from holiday_breakdown JSON (same logic as summary)
        $actualHolidayPay = 0;
        if ($employeeSnapshot && $employeeSnapshot->holiday_breakdown) {
            $holidayBreakdown = is_string($employeeSnapshot->holiday_breakdown) ? 
                json_decode($employeeSnapshot->holiday_breakdown, true) : 
                $employeeSnapshot->holiday_breakdown;
            if (is_array($holidayBreakdown)) {
                foreach ($holidayBreakdown as $type => $data) {
                    $actualHolidayPay += $data['amount'] ?? 0;
                }
            }
        } else {
            // Fallback to raw value if no breakdown available
            $actualHolidayPay = $employeeSnapshot ? $employeeSnapshot->holiday_pay : ($payrollDetail->holiday_pay ?? 0);
        }
        
        // Calculate rest pay from rest_breakdown JSON
        $actualRestPay = 0;
        if ($employeeSnapshot && $employeeSnapshot->rest_breakdown) {
            $restBreakdown = is_string($employeeSnapshot->rest_breakdown) ? 
                json_decode($employeeSnapshot->rest_breakdown, true) : 
                $employeeSnapshot->rest_breakdown;
            if (is_array($restBreakdown)) {
                foreach ($restBreakdown as $restData) {
                    $actualRestPay += $restData['amount'] ?? 0;
                }
            }
        }
        
        // Calculate overtime pay from overtime_breakdown JSON (same logic as summary)
        $actualOvertimePay = 0;
        if ($employeeSnapshot && $employeeSnapshot->overtime_breakdown) {
            $overtimeBreakdown = is_string($employeeSnapshot->overtime_breakdown) ? 
                json_decode($employeeSnapshot->overtime_breakdown, true) : 
                $employeeSnapshot->overtime_breakdown;
            if (is_array($overtimeBreakdown)) {
                foreach ($overtimeBreakdown as $type => $data) {
                    $actualOvertimePay += $data['amount'] ?? 0;
                }
            }
        } else {
            // Fallback to raw value if no breakdown available
            $actualOvertimePay = $employeeSnapshot ? $employeeSnapshot->overtime_pay : ($payrollDetail->overtime_pay ?? 0);
        }
        
        // Get allowances, bonuses, and incentives - use snapshot values for locked payrolls
        if ($employeeSnapshot && ($payrollDetail->payroll->status === 'locked' || $payrollDetail->payroll->status === 'processing')) {
            // Use snapshot totals for locked/processing payrolls (already calculated with distribution methods)
            $actualAllowances = $employeeSnapshot->allowances_total ?? 0;
            $actualBonuses = $employeeSnapshot->bonuses_total ?? 0;
            $actualIncentives = $employeeSnapshot->incentives_total ?? 0;
        } else {
            // Use detail values for draft payrolls
            $actualAllowances = $payrollDetail->allowances ?? 0;
            $actualBonuses = $payrollDetail->bonuses ?? 0;
            $actualIncentives = $payrollDetail->incentives ?? 0;
        }
        
        // Separate 13th month pay from other bonuses (same as web view)
        $thirteenthMonthPay = 0;
        $otherBonuses = 0;
        
        if (isset($payrollDetail->bonuses_breakdown) && is_array($payrollDetail->bonuses_breakdown)) {
            foreach ($payrollDetail->bonuses_breakdown as $bonus) {
                if (isset($bonus['name']) && isset($bonus['amount'])) {
                    // Check if this is 13th month pay
                    if (stripos($bonus['name'], '13th') !== false || stripos($bonus['name'], 'thirteenth') !== false) {
                        $thirteenthMonthPay += $bonus['amount'];
                    } else {
                        $otherBonuses += $bonus['amount'];
                    }
                }
            }
        } else {
            // Fallback: if no breakdown, show all bonuses as "Other Bonuses"
            $otherBonuses = $payrollDetail->bonuses ?? 0;
        }
        
        // Calculate total deductions (same logic as payroll show view)
        $calculatedDeductionTotal = 0;
        $hasBreakdown = false;
        
        // Use the same conditional logic as the payslip view to avoid double-counting
        if(isset($payrollDetail->deduction_breakdown) && is_array($payrollDetail->deduction_breakdown) && !empty($payrollDetail->deduction_breakdown)) {
            // Use detail breakdown if available
            $hasBreakdown = true;
            foreach($payrollDetail->deduction_breakdown as $code => $deductionData) {
                $amount = $deductionData['amount'] ?? $deductionData;
                $calculatedDeductionTotal += $amount;
            }
        } elseif($employeeSnapshot && isset($employeeSnapshot->deductions_breakdown) && $employeeSnapshot->deductions_breakdown) {
            // Use employee snapshot breakdown if available (handle both array and JSON string)
            $hasBreakdown = true;
            $deductionsBreakdown = is_string($employeeSnapshot->deductions_breakdown) 
                ? json_decode($employeeSnapshot->deductions_breakdown, true) 
                : $employeeSnapshot->deductions_breakdown;
            
            if (is_array($deductionsBreakdown)) {
                foreach ($deductionsBreakdown as $code => $deductionData) {
                    $amount = $deductionData['amount'] ?? $deductionData;
                    $calculatedDeductionTotal += $amount;
                }
            }
        } else {
            // Fallback to individual fields
            $calculatedDeductionTotal += $payrollDetail->sss_contribution ?? 0;
            $calculatedDeductionTotal += $payrollDetail->philhealth_contribution ?? 0;
            $calculatedDeductionTotal += $payrollDetail->pagibig_contribution ?? 0;
            $calculatedDeductionTotal += $payrollDetail->withholding_tax ?? 0;
            $calculatedDeductionTotal += $payrollDetail->cash_advance_deductions ?? 0;
            $calculatedDeductionTotal += $payrollDetail->other_deductions ?? 0;
        }
        
        // Use fallback to stored total if calculated is 0
        $actualTotalDeductions = $calculatedDeductionTotal > 0 ? $calculatedDeductionTotal : ($payrollDetail->total_deductions ?? 0);
        
        // Calculate gross pay by adding all components to ensure correct total
        $actualGrossPay = $actualBasicPay + $actualHolidayPay + $actualRestPay + $actualOvertimePay + $actualAllowances + $thirteenthMonthPay + $otherBonuses + $actualIncentives;
        
        // Calculate net pay dynamically: Gross Pay - Total Deductions
        $actualNetPay = $actualGrossPay - $actualTotalDeductions;
    @endphp

    <div class="payslip-container">
        <!-- Header -->
        <div class="header-grid">
            <div class="header-left">
                <div class="company-name">Pateros Technological College</div>
                <div class="company-address">Calsada Tipas Taguig<br>09511465141 | ptc@gmail.com</div>
            </div>
            <div class="header-right">
                <div class="payslip-title">PAYSLIP</div>
                <div class="period-info">
                    <strong>Pay Period:</strong> {{ $payrollDetail->payroll->period_start->format('M d') }} - {{ $payrollDetail->payroll->period_end->format('M d, Y') }}<br>
                    <strong>Status:</strong> {{ ucfirst($payrollDetail->payroll->status) }}
                </div>
            </div>
        </div>

        <!-- Employee Information and Pay Information -->
        <div class="info-grid">
            <div class="info-left">
                <div class="info-section">
                    <h3>Employee Information</h3>
                    <div class="info-row">
                        <div class="info-label">Name:</div>
                        <div class="info-value">{{ $payrollDetail->employee->first_name }} {{ $payrollDetail->employee->last_name }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Employee #:</div>
                        <div class="info-value">{{ $payrollDetail->employee->employee_number }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Position:</div>
                        <div class="info-value">{{ $payrollDetail->employee->position->title ?? 'N/A' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Department:</div>
                        <div class="info-value">{{ $payrollDetail->employee->department->name ?? 'N/A' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Schedule:</div>
                        <div class="info-value">{{ $payrollDetail->employee->schedule_display ?? 'Monday to Friday | 8:00 AM - 5:00 PM' }}</div>
                    </div>
                </div>
            </div>
            
            <div class="info-right">
                <div class="info-section">
                    <h3>Pay Information</h3>
                    <div class="info-row">
                        <div class="info-label">Pay Type:</div>
                        <div class="info-value">{{ ucwords(str_replace('_', ' ', $payrollDetail->employee->pay_schedule)) }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Basic Pay:</div>
                        <div class="info-value">₱{{ number_format($payrollDetail->regular_pay ?? 0, 2) }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Regular Hours:</div>
                        <div class="info-value">{{ number_format($payrollDetail->regular_hours ?? 0, 1) }} hrs</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Overtime Hours:</div>
                        <div class="info-value">{{ number_format($payrollDetail->overtime_hours ?? 0, 1) }} hrs</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Total Hours:</div>
                        <div class="info-value">{{ number_format(($payrollDetail->regular_hours ?? 0) + ($payrollDetail->overtime_hours ?? 0), 1) }} hrs</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Earnings and Deductions -->
        <div class="earnings-deductions-grid">
            <div class="earnings-col">
                <h3 class="section-header earnings-header">Earnings</h3>
                
                <div class="amount-row">
                    <div class="amount-label">Regular Pay</div>
                    <div class="amount-value">₱{{ number_format($actualBasicPay, 2) }}</div>
                </div>
                
                <div class="amount-row">
                    <div class="amount-label">Holiday Pay</div>
                    <div class="amount-value">₱{{ number_format($actualHolidayPay, 2) }}</div>
                </div>
                
                <div class="amount-row">
                    <div class="amount-label">Rest Pay</div>
                    <div class="amount-value">₱{{ number_format($actualRestPay, 2) }}</div>
                </div>
                
                <div class="amount-row">
                    <div class="amount-label">Overtime Pay</div>
                    <div class="amount-value">₱{{ number_format($actualOvertimePay, 2) }}</div>
                </div>
                
                <div class="amount-row">
                    <div class="amount-label">Allowances</div>
                    <div class="amount-value">₱{{ number_format($actualAllowances, 2) }}</div>
                </div>
                
                @if($thirteenthMonthPay > 0)
                <div class="amount-row">
                    <div class="amount-label">13th Month Pay</div>
                    <div class="amount-value">₱{{ number_format($thirteenthMonthPay, 2) }}</div>
                </div>
                @endif
                
                @if($otherBonuses > 0)
                <div class="amount-row">
                    <div class="amount-label">Bonuses</div>
                    <div class="amount-value">₱{{ number_format($otherBonuses, 2) }}</div>
                </div>
                @endif
                
                <div class="amount-row">
                    <div class="amount-label">Incentives</div>
                    <div class="amount-value">₱{{ number_format($actualIncentives, 2) }}</div>
                </div>
            </div>

            <div class="deductions-col">
                <h3 class="section-header deductions-header">Deductions</h3>
                
                <div class="amount-row">
                    <div class="amount-label">SSS</div>
                    <div class="amount-value">₱{{ number_format($payrollDetail->sss_contribution ?? 0, 2) }}</div>
                </div>
                
                <div class="amount-row">
                    <div class="amount-label">PhilHealth</div>
                    <div class="amount-value">₱{{ number_format($payrollDetail->philhealth_contribution ?? 0, 2) }}</div>
                </div>
                
                <div class="amount-row">
                    <div class="amount-label">Pag-IBIG</div>
                    <div class="amount-value">₱{{ number_format($payrollDetail->pagibig_contribution ?? 0, 2) }}</div>
                </div>
                
                <div class="amount-row">
                    <div class="amount-label">Withholding Tax</div>
                    <div class="amount-value">₱{{ number_format($payrollDetail->withholding_tax ?? 0, 2) }}</div>
                </div>
                
                @if(($payrollDetail->cash_advance_deductions ?? 0) > 0)
                <div class="amount-row">
                    <div class="amount-label">Cash Advance</div>
                    <div class="amount-value">₱{{ number_format($payrollDetail->cash_advance_deductions, 2) }}</div>
                </div>
                @endif
                
                @if(($payrollDetail->other_deductions ?? 0) > 0)
                <div class="amount-row">
                    <div class="amount-label">Other Deductions</div>
                    <div class="amount-value">₱{{ number_format($payrollDetail->other_deductions, 2) }}</div>
                </div>
                @endif
            </div>
        </div>

        <!-- Summary Totals -->
        <div class="summary-box gross-pay">
            <div>Gross Pay</div>
            <div style="font-size: 16px;">₱{{ number_format($actualGrossPay, 2) }}</div>
        </div>

        <div class="summary-box total-deductions">
            <div>Total Deductions</div>
            <div style="font-size: 16px;">₱{{ number_format($actualTotalDeductions, 2) }}</div>
        </div>

        <div class="summary-box net-pay">
            <div style="margin-bottom: 5px;">NET PAY</div>
            <div style="font-size: 20px; font-weight: bold;">₱{{ number_format($actualNetPay, 2) }}</div>
            <div style="font-size: 9px; margin-top: 5px;">Amount to be paid to employee</div>
        </div>

        <!-- Signatures -->
        <div class="signatures">
            <div class="sig-left">
                <div class="sig-line"></div>
                <div class="sig-label">Employee Signature</div>
                <div class="sig-label">{{ $payrollDetail->employee->first_name }} {{ $payrollDetail->employee->last_name }}</div>
                <div class="sig-label">({{ $payrollDetail->employee->position->title ?? 'Employee' }})</div>
            </div>
            <div class="sig-right">
                <div class="sig-line"></div>
                <div class="sig-label">Authorized Signature</div>
                <div class="sig-label">{{ $payrollDetail->payroll->creator->name ?? 'King Mallari' }}</div>
                <div class="sig-label">(HR Head)</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This payslip is generated electronically and serves as an official record of payment.</p>
            <p>Generated on {{ now()->format('M d, Y g:i A') }}</p>
        </div>
    </div>
</body>
</html>