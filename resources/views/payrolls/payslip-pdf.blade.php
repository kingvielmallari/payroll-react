<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Payslip - {{ $payroll->payroll_number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9px;
            line-height: 1.2;
            color: #333;
            margin: 0;
            padding: 15px;
            background: white;
        }
        
        .payslip-container {
            max-width: 100%;
            margin: 0;
            background: white;
            page-break-after: avoid;
        }
        
        .header-section {
            border-bottom: 1px solid #333;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        
        .header-grid {
            display: table;
            width: 100%;
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
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 4px;
            color: #1f2937;
        }
        
        .company-address {
            font-size: 8px;
            line-height: 1.2;
            color: #666;
        }
        
        .payslip-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 4px;
            color: #1f2937;
        }
        
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .info-left, .info-right {
            display: table-cell;
            vertical-align: top;
            width: 50%;
            padding-right: 10px;
        }
        
        .info-right {
            padding-right: 0;
            padding-left: 10px;
        }
        
        .section-title {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 4px;
            padding-bottom: 2px;
            border-bottom: 1px solid #ccc;
            color: #1f2937;
        }
        
        .info-item {
            display: table;
            width: 100%;
            margin-bottom: 2px;
        }
        
        .info-label, .info-value {
            display: table-cell;
            vertical-align: top;
            font-size: 8px;
        }
        
        .info-label {
            font-weight: bold;
            width: 45%;
            color: #555;
        }
        
        .info-value {
            text-align: right;
        }
        
        .earnings-section, .deductions-section {
            margin-bottom: 10px;
        }
        
        .earnings-table, .deductions-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        
        .earnings-table th, .earnings-table td,
        .deductions-table th, .deductions-table td {
            border: 1px solid #ddd;
            padding: 3px 5px;
            text-align: left;
        }
        
        .earnings-table th, .deductions-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            font-size: 9px;
        }
        
        .amount-column {
            text-align: right !important;
            width: 30%;
        }
        
        .net-pay-section {
            background-color: #f0f8ff;
            border: 2px solid #333;
            padding: 15px;
            text-align: center;
            margin: 20px 0;
        }
        
        .net-pay-title {
            font-size: 12px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .net-pay-amount {
            font-size: 20px;
            font-weight: bold;
            color: #2563eb;
        }
        
        .signature-section {
            margin-top: 15px;
            display: table;
            width: 100%;
        }
        
        .signature-left, .signature-right {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: top;
        }
        
        .signature-line {
            border-top: 1px solid #333;
            width: 150px;
            margin: 0 auto 3px auto;
            padding-top: 3px;
        }
        
        .signature-label {
            font-size: 8px;
            font-weight: bold;
        }
        
        .footer-note {
            margin-top: 10px;
            text-align: center;
            font-size: 7px;
            color: #666;
        }
        
        .status-badge {
            
        }
        
        .status-approved {  color: #166534; }
        .status-processing { color: #dde62b; }
        .status-draft { color: #374151; }
        
        @page {
            margin: 0.3in;
            size: letter;
            color: #666;
            line-height: 1.3;
        
        }
         
        
        .payroll-info {
            font-size: 10px;
            color: #374151;
        }
        
        .payroll-info strong {
            color: #1f2937;
        }
        
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1f2937;
            margin: 15px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 1px solid #d1d5db;
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
            padding-right: 15px;
        }
        
        .info-item {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }
        
        .info-label {
            display: table-cell;
            font-weight: bold;
            color: #374151;
            width: 45%;
            padding-right: 10px;
        }
        
        .info-value {
            display: table-cell;
            color: #1f2937;
            text-align: right;
        }
        
        .earnings-deductions-grid {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .earnings-col, .deductions-col {
            display: table-cell;
            vertical-align: top;
            width: 50%;
            padding-right: 8px;
        }
        
        .deductions-col {
            padding-right: 0;
            padding-left: 8px;
        }
        
        .line-item {
            display: table;
            width: 100%;
            padding: 2px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .line-item-label {
            display: table-cell;
            color: #374151;
            width: 65%;
            font-size: 8px;
        }
        
        .line-item-value {
            display: table-cell;
            font-weight: bold;
            color: #1f2937;
            text-align: right;
            font-size: 8px;
        }
        
        .total-row {
            background-color: #f8f9fa;
            font-weight: bold;
            border-top: 1px solid #374151;
            padding: 4px 0;
            margin-top: 5px;
        }
        
        .total-row .line-item-label,
        .total-row .line-item-value {
            font-size: 9px;
        }
        
        .net-pay-section {
            margin-top: 15px;
            padding: 12px;
            background-color: #f8f9fa;
            border: 2px solid #374151;
            text-align: center;
        }
        
        .net-pay-amount {
            font-size: 18px;
            font-weight: bold;
            color: #1f2937;
        }
        
        .page-break { page-break-before: always; }
        
        /* Utility classes */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .mb-2 { margin-bottom: 8px; }
        .mb-4 { margin-bottom: 16px; }
        .font-bold { font-weight: bold; }
    </style>
</head>
<body>
    @foreach($payroll->payrollDetails as $index => $detail)
        @php
            // Use EXACT same calculation logic as the web payslip view
            $employeeSnapshot = \App\Models\PayrollSnapshot::where('payroll_id', $payroll->id)
                ->where('employee_id', $detail->employee_id)
                ->first();
                
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
                $actualBasicPay = $employeeSnapshot ? $employeeSnapshot->regular_pay : $detail->regular_pay;
            }
            
            // Calculate holiday pay from holiday_breakdown JSON
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
                $actualHolidayPay = $employeeSnapshot ? $employeeSnapshot->holiday_pay : $detail->holiday_pay;
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
            } else {
                $actualRestPay = 0; // No rest pay for older records
            }
            
            // Calculate overtime pay from overtime_breakdown JSON
            $actualOvertimePay = 0;
            if ($employeeSnapshot && $employeeSnapshot->overtime_breakdown) {
                $overtimeBreakdown = is_string($employeeSnapshot->overtime_breakdown) ? 
                    json_decode($employeeSnapshot->overtime_breakdown, true) : 
                    $employeeSnapshot->overtime_breakdown;
                if (is_array($overtimeBreakdown)) {
                    foreach ($overtimeBreakdown as $overtimeData) {
                        $actualOvertimePay += $overtimeData['amount'] ?? 0;
                    }
                }
            } else {
                $actualOvertimePay = $employeeSnapshot ? $employeeSnapshot->overtime_pay : $detail->overtime_pay;
            }
            
            // Calculate allowances/bonuses/incentives (same as web view)
            $actualAllowances = $employeeSnapshot ? $employeeSnapshot->allowances_total : $detail->allowances;
            $actualBonuses = $employeeSnapshot ? $employeeSnapshot->bonuses_total : $detail->bonuses;
            $actualIncentives = $employeeSnapshot ? $employeeSnapshot->incentives_total : $detail->incentives;
            
            // Calculate deductions from deductions_breakdown JSON (same as web view)
            $calculatedDeductionTotal = 0;
            if ($employeeSnapshot && $employeeSnapshot->deductions_breakdown) {
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
                $calculatedDeductionTotal += $detail->sss_contribution ?? 0;
                $calculatedDeductionTotal += $detail->philhealth_contribution ?? 0;
                $calculatedDeductionTotal += $detail->pagibig_contribution ?? 0;
                $calculatedDeductionTotal += $detail->withholding_tax ?? 0;
                $calculatedDeductionTotal += $detail->cash_advance_deductions ?? 0;
                $calculatedDeductionTotal += $detail->other_deductions ?? 0;
            }
            
            // Use fallback to stored total if calculated is 0
            $actualTotalDeductions = $calculatedDeductionTotal > 0 ? $calculatedDeductionTotal : ($detail->total_deductions ?? 0);
            
            // Calculate gross pay by adding all components to ensure correct total
            $actualGrossPay = $actualBasicPay + $actualHolidayPay + $actualRestPay + $actualOvertimePay + $actualAllowances + $actualBonuses + $actualIncentives;
            
            // Calculate total earnings (same as gross pay for display purposes)
            $totalEarnings = $actualGrossPay;
            
            // Add aliases for backward compatibility
            $totalDeductions = $actualTotalDeductions;
            $netPay = $actualGrossPay - $actualTotalDeductions;
            
            // Calculate net pay dynamically: Gross Pay - Total Deductions
            $actualNetPay = $actualGrossPay - $actualTotalDeductions;
        @endphp

        @if($index > 0)
            <div class="page-break"></div>
        @endif

        <div class="payslip-container">
            <!-- Header -->
            <div class="header-section">
                <div class="header-grid">
                    <div class="header-left">
                        <div class="company-name">{{ $company->name }}</div>
                        <div class="company-address">
                            {{ $company->address }}<br>
                            {{ $company->phone }} | {{ $company->email }}
                        </div>
                    </div>
                    <div class="header-right">
                        <div class="payslip-title">PAYSLIP</div>
                        <div style="font-size: 9px; color: #666;">
                            <div><strong>Pay Period:</strong> {{ $payroll->period_start->format('M d') }} - {{ $payroll->period_end->format('M d, Y') }}</div>
                            <div><strong>Status:</strong> 
                                <span class="status-badge status-{{ $payroll->status }}">{{ ucfirst($payroll->status) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Employee Information -->
            <div class="info-grid">
                <div class="info-left">
                    <div class="section-title">Employee Information</div>
                    <div class="info-item">
                        <div class="info-label">Name:</div>
                        <div class="info-value">{{ $detail->employee->first_name }} {{ $detail->employee->last_name }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Employee #:</div>
                        <div class="info-value">{{ $detail->employee->employee_number }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Position:</div>
                        <div class="info-value">{{ $detail->employee->position->title ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Department:</div>
                        <div class="info-value">{{ $detail->employee->department->name ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Schedule:</div>
                        <div class="info-value">{{ $detail->employee->schedule_display ?? 'N/A' }}</div>
                    </div>
                </div>
                
                <div class="info-right">
                    <div class="section-title">Pay Information</div>
                    <div class="info-item">
                        <div class="info-label">Pay Type:</div>
                        <div class="info-value">{{ ucwords(str_replace('_', ' ', $detail->employee->pay_schedule)) }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Basic Pay:</div>
                        <div class="info-value">₱{{ number_format($detail->regular_pay ?? 0, 2) }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Regular Hours:</div>
                        <div class="info-value">{{ number_format($detail->regular_hours ?? 0, 1) }} hrs</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Overtime Hours:</div>
                        <div class="info-value">{{ number_format($detail->overtime_hours ?? 0, 1) }} hrs</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Total Hours:</div>
                        <div class="info-value">{{ number_format(($detail->regular_hours ?? 0) + ($detail->overtime_hours ?? 0), 1) }} hrs</div>
                    </div>
                </div>
            </div>
                </div>
            </div>
        </div>

        <!-- Earnings and Deductions -->
        <div class="earnings-deductions-grid">
            <!-- Earnings -->
            <div class="earnings-col">
                <div class="section-title">Earnings</div>
                <div class="line-item">
                    <div class="line-item-label">Regular Pay</div>
                    <div class="line-item-value">₱{{ number_format($actualBasicPay, 2) }}</div>
                </div>
                <div class="line-item">
                    <div class="line-item-label">Holiday Pay</div>
                    <div class="line-item-value">₱{{ number_format($actualHolidayPay, 2) }}</div>
                </div>
                <div class="line-item">
                    <div class="line-item-label">Rest Pay</div>
                    <div class="line-item-value">₱{{ number_format($actualRestPay, 2) }}</div>
                </div>
                <div class="line-item">
                    <div class="line-item-label">Overtime Pay</div>
                    <div class="line-item-value">₱{{ number_format($actualOvertimePay, 2) }}</div>
                </div>
                <div class="line-item">
                    <div class="line-item-label">Allowances</div>
                    <div class="line-item-value">₱{{ number_format($detail->allowances ?? 0, 2) }}</div>
                </div>
                <div class="line-item">
                    <div class="line-item-label">Bonuses</div>
                    <div class="line-item-value">₱{{ number_format($detail->bonuses ?? 0, 2) }}</div>
                </div>
                @if (isset($detail->incentives) && $detail->incentives > 0)
                <div class="line-item">
                    <div class="line-item-label">Incentives</div>
                    <div class="line-item-value">₱{{ number_format($detail->incentives, 2) }}</div>
                </div>
                @endif
                <div class="line-item total-row">
                    <div class="line-item-label">Total Earnings</div>
                    <div class="line-item-value">₱{{ number_format($totalEarnings, 2) }}</div>
                </div>
            </div>

            <!-- Deductions -->
            <div class="deductions-col">
                <div class="section-title">Deductions</div>
                
                @if ($isDynamic && isset($activeDeductions) && $activeDeductions->isNotEmpty())
                    {{-- Dynamic deductions calculation for draft payrolls --}}
                    @foreach ($activeDeductions as $setting)
                        @php
                            $basicPay = $actualBasicPay + $actualHolidayPay + $actualRestPay;
                            $overtimePay = $actualOvertimePay;
                            $bonuses = $detail->bonuses ?? 0;
                            $allowances = $detail->allowances ?? 0;
                            $grossPay = $totalEarnings;
                            $taxableIncome = $basicPay + $bonuses + $allowances;
                            
                            $payFrequency = \App\Models\PayScheduleSetting::detectPayFrequencyFromPeriod(
                                $payroll->period_start,
                                $payroll->period_end
                            );
                            
                            $calculatedAmount = $setting->calculateDeduction(
                                $basicPay,
                                $overtimePay,
                                $bonuses,
                                $allowances,
                                $grossPay,
                                $taxableIncome,
                                null,
                                $detail->employee->calculateMonthlyBasicSalary($payroll->period_start, $payroll->period_end),
                                $payFrequency
                            );
                            
                            if ($calculatedAmount > 0) {
                                $calculatedAmount = $setting->calculateDistributedAmount(
                                    $calculatedAmount,
                                    $payroll->period_start,
                                    $payroll->period_end,
                                    $detail->employee->pay_schedule ?? $payFrequency
                                );
                            }
                        @endphp
                        @if ($calculatedAmount > 0)
                        <div class="line-item">
                            <div class="line-item-label">{{ $setting->name }}</div>
                            <div class="line-item-value">₱{{ number_format($calculatedAmount, 2) }}</div>
                        </div>
                        @endif
                    @endforeach
                @else
                    {{-- Static deductions for approved/processing payrolls - read from snapshot breakdown --}}
                    @if ($employeeSnapshot && $employeeSnapshot->deductions_breakdown)
                        @php
                            $snapshotDeductions = is_string($employeeSnapshot->deductions_breakdown) 
                                ? json_decode($employeeSnapshot->deductions_breakdown, true) 
                                : $employeeSnapshot->deductions_breakdown;
                        @endphp
                        
                        @if (is_array($snapshotDeductions))
                            @foreach ($snapshotDeductions as $deductionData)
                                @php
                                    $amount = $deductionData['amount'] ?? 0;
                                    $name = $deductionData['name'] ?? $deductionData['code'] ?? 'Unknown';
                                @endphp
                                @if ($amount > 0)
                                <div class="line-item">
                                    <div class="line-item-label">{{ $name }}</div>
                                    <div class="line-item-value">₱{{ number_format($amount, 2) }}</div>
                                </div>
                                @endif
                            @endforeach
                        @endif
                    @else
                        {{-- Fallback to individual columns if no snapshot breakdown --}}
                        @if ($detail->sss_contribution > 0)
                        <div class="line-item">
                            <div class="line-item-label">SSS</div>
                            <div class="line-item-value">₱{{ number_format($detail->sss_contribution, 2) }}</div>
                        </div>
                        @endif
                        @if ($detail->philhealth_contribution > 0)
                        <div class="line-item">
                            <div class="line-item-label">PhilHealth</div>
                            <div class="line-item-value">₱{{ number_format($detail->philhealth_contribution, 2) }}</div>
                        </div>
                        @endif
                        @if ($detail->pagibig_contribution > 0)
                        <div class="line-item">
                            <div class="line-item-label">Pag-IBIG</div>
                            <div class="line-item-value">₱{{ number_format($detail->pagibig_contribution, 2) }}</div>
                        </div>
                        @endif
                        @if ($detail->withholding_tax > 0)
                        <div class="line-item">
                            <div class="line-item-label">Withholding Tax</div>
                            <div class="line-item-value">₱{{ number_format($detail->withholding_tax, 2) }}</div>
                        </div>
                        @endif
                    @endif
                @endif
                
                {{-- Other deductions (always shown) --}}
                @if (($detail->cash_advance_deductions ?? 0) > 0)
                <div class="line-item">
                    <div class="line-item-label">Cash Advance</div>
                    <div class="line-item-value">₱{{ number_format($detail->cash_advance_deductions, 2) }}</div>
                </div>
                @endif
                @if (($detail->other_deductions ?? 0) > 0)
                <div class="line-item">
                    <div class="line-item-label">Other Deductions</div>
                    <div class="line-item-value">₱{{ number_format($detail->other_deductions, 2) }}</div>
                </div>
                @endif
                
                <div class="line-item total-row">
                    <div class="line-item-label">Total Deductions</div>
                    <div class="line-item-value">₱{{ number_format($actualTotalDeductions, 2) }}</div>
                </div>
            </div>
        </div>

        <!-- Net Pay -->
        <div class="net-pay-section">
            <div class="net-pay-title">NET PAY</div>
            <div class="net-pay-amount">₱{{ number_format($actualNetPay, 2) }}</div>
        </div>


        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-left">
                <div class="signature-line">{{ $detail->employee->first_name }} {{ $detail->employee->last_name }}</div>
                <div class="signature-label">Employee Signature<br>({{ $detail->employee->position->title ?? 'Software Developer' }})</div>
            </div>
            <div class="signature-right">
                <div class="signature-line">King Mallari</div>
                <div class="signature-label">Authorized Signature<br>(HR Head)</div>
            </div>
        </div>

        <!-- Footer Note -->
        <div class="footer-note">
            This payslip is generated electronically and serves as an official record of payment.<br>
            Generated on {{ \Carbon\Carbon::now()->format('M d, Y g:i A') }}
        </div>
        </div>
    @endforeach
</body>
</html>