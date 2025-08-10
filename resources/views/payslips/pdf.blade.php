<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #4F81BD;
            padding-bottom: 20px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #4F81BD;
            margin-bottom: 5px;
        }
        .company-address {
            font-size: 10px;
            color: #666;
            margin-bottom: 10px;
        }
        .payslip-title {
            font-size: 18px;
            font-weight: bold;
            margin-top: 15px;
        }
        .period {
            font-size: 14px;
            color: #666;
        }
        .employee-info {
            width: 100%;
            margin-bottom: 20px;
        }
        .employee-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .employee-info td {
            padding: 5px 10px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        .employee-info .label {
            background-color: #f0f0f0;
            font-weight: bold;
            width: 150px;
        }
        .payroll-details {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .payroll-details th,
        .payroll-details td {
            padding: 8px 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .payroll-details th {
            background-color: #4F81BD;
            color: white;
            font-weight: bold;
        }
        .section-header {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .amount {
            text-align: right;
            font-weight: bold;
        }
        .gross-total {
            background-color: #e8f5e8;
            font-weight: bold;
        }
        .deduction-total {
            background-color: #ffe8e8;
            font-weight: bold;
        }
        .net-total {
            background-color: #e3f2fd;
            font-weight: bold;
            font-size: 14px;
        }
        .signatures {
            margin-top: 40px;
            width: 100%;
        }
        .signatures table {
            width: 100%;
            border-collapse: collapse;
        }
        .signatures td {
            padding: 40px 20px 10px 20px;
            text-align: center;
            vertical-align: bottom;
            border-top: 1px solid #333;
            width: 33.33%;
        }
        .signature-label {
            font-size: 10px;
            color: #666;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ config('app.name', 'Company Name') }}</div>
        <div class="company-address">
            Company Address Line 1<br>
            Company Address Line 2<br>
            Phone: (02) 123-4567 | Email: hr@company.com
        </div>
        <div class="payslip-title">PAYSLIP</div>
        <div class="period">{{ $payrollDetail->payroll->period_start->format('F d') }} - {{ $payrollDetail->payroll->period_end->format('F d, Y') }}</div>
    </div>

    <!-- Employee Information -->
    <div class="employee-info">
        <table>
            <tr>
                <td class="label">Employee Number:</td>
                <td>{{ $payrollDetail->employee->employee_number }}</td>
                <td class="label">Pay Date:</td>
                <td>{{ $payrollDetail->payroll->pay_date->format('F d, Y') }}</td>
            </tr>
            <tr>
                <td class="label">Employee Name:</td>
                <td>{{ $payrollDetail->employee->first_name }} {{ $payrollDetail->employee->last_name }}</td>
                <td class="label">Payroll Number:</td>
                <td>{{ $payrollDetail->payroll->payroll_number }}</td>
            </tr>
            <tr>
                <td class="label">Department:</td>
                <td>{{ $payrollDetail->employee->department->name ?? 'N/A' }}</td>
                <td class="label">Position:</td>
                <td>{{ $payrollDetail->employee->position->title ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Basic Salary:</td>
                <td>₱{{ number_format($payrollDetail->basic_salary, 2) }}</td>
                <td class="label">Days Worked:</td>
                <td>{{ $payrollDetail->days_worked }}</td>
            </tr>
        </table>
    </div>

    <!-- Payroll Details -->
    <table class="payroll-details">
        <thead>
            <tr>
                <th style="width: 60%;">Description</th>
                <th style="width: 20%;">Hours/Days</th>
                <th style="width: 20%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <!-- Earnings -->
            <tr class="section-header">
                <td colspan="3">EARNINGS</td>
            </tr>
            <tr>
                <td>Regular Pay</td>
                <td>{{ number_format($payrollDetail->regular_hours, 1) }} hrs</td>
                <td class="amount">₱{{ number_format($payrollDetail->regular_pay, 2) }}</td>
            </tr>
            @if($payrollDetail->overtime_pay > 0)
            <tr>
                <td>Overtime Pay</td>
                <td>{{ number_format($payrollDetail->overtime_hours, 1) }} hrs</td>
                <td class="amount">₱{{ number_format($payrollDetail->overtime_pay, 2) }}</td>
            </tr>
            @endif
            @if($payrollDetail->holiday_pay > 0)
            <tr>
                <td>Holiday Pay</td>
                <td>-</td>
                <td class="amount">₱{{ number_format($payrollDetail->holiday_pay, 2) }}</td>
            </tr>
            @endif
            @if($payrollDetail->night_differential_pay > 0)
            <tr>
                <td>Night Differential Pay</td>
                <td>-</td>
                <td class="amount">₱{{ number_format($payrollDetail->night_differential_pay, 2) }}</td>
            </tr>
            @endif
            @if($payrollDetail->allowances > 0)
            <tr>
                <td>Allowances</td>
                <td>-</td>
                <td class="amount">₱{{ number_format($payrollDetail->allowances, 2) }}</td>
            </tr>
            @endif
            @if($payrollDetail->bonuses > 0)
            <tr>
                <td>Bonuses</td>
                <td>-</td>
                <td class="amount">₱{{ number_format($payrollDetail->bonuses, 2) }}</td>
            </tr>
            @endif
            @if($payrollDetail->other_earnings > 0)
            <tr>
                <td>Other Earnings</td>
                <td>-</td>
                <td class="amount">₱{{ number_format($payrollDetail->other_earnings, 2) }}</td>
            </tr>
            @endif
            <tr class="gross-total">
                <td colspan="2"><strong>TOTAL GROSS PAY</strong></td>
                <td class="amount">₱{{ number_format($payrollDetail->gross_pay, 2) }}</td>
            </tr>

            <!-- Deductions -->
            <tr class="section-header">
                <td colspan="3">DEDUCTIONS</td>
            </tr>
            <tr>
                <td>SSS Contribution</td>
                <td>-</td>
                <td class="amount">₱{{ number_format($payrollDetail->sss_contribution, 2) }}</td>
            </tr>
            <tr>
                <td>PhilHealth Contribution</td>
                <td>-</td>
                <td class="amount">₱{{ number_format($payrollDetail->philhealth_contribution, 2) }}</td>
            </tr>
            <tr>
                <td>Pag-IBIG Contribution</td>
                <td>-</td>
                <td class="amount">₱{{ number_format($payrollDetail->pagibig_contribution, 2) }}</td>
            </tr>
            <tr>
                <td>Withholding Tax</td>
                <td>-</td>
                <td class="amount">₱{{ number_format($payrollDetail->withholding_tax, 2) }}</td>
            </tr>
            @if($payrollDetail->late_deductions > 0)
            <tr>
                <td>Late Deductions</td>
                <td>{{ number_format($payrollDetail->late_hours, 1) }} hrs</td>
                <td class="amount">₱{{ number_format($payrollDetail->late_deductions, 2) }}</td>
            </tr>
            @endif
            @if($payrollDetail->undertime_deductions > 0)
            <tr>
                <td>Undertime Deductions</td>
                <td>{{ number_format($payrollDetail->undertime_hours, 1) }} hrs</td>
                <td class="amount">₱{{ number_format($payrollDetail->undertime_deductions, 2) }}</td>
            </tr>
            @endif
            @if($payrollDetail->other_deductions > 0)
            <tr>
                <td>Other Deductions</td>
                <td>-</td>
                <td class="amount">₱{{ number_format($payrollDetail->other_deductions, 2) }}</td>
            </tr>
            @endif
            <tr class="deduction-total">
                <td colspan="2"><strong>TOTAL DEDUCTIONS</strong></td>
                <td class="amount">₱{{ number_format($payrollDetail->total_deductions, 2) }}</td>
            </tr>

            <!-- Net Pay -->
            <tr class="net-total">
                <td colspan="2"><strong>NET PAY</strong></td>
                <td class="amount">₱{{ number_format($payrollDetail->net_pay, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Government Contributions Summary -->
    <div style="margin-top: 20px; border: 1px solid #ddd; padding: 10px; background-color: #f9f9f9;">
        <h4 style="margin: 0 0 10px 0;">Government Contributions Summary</h4>
        <table style="width: 100%; font-size: 10px;">
            <tr>
                <td>SSS Number: {{ $payrollDetail->employee->sss_number ?? 'N/A' }}</td>
                <td>PhilHealth Number: {{ $payrollDetail->employee->philhealth_number ?? 'N/A' }}</td>
                <td>Pag-IBIG Number: {{ $payrollDetail->employee->pagibig_number ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>TIN: {{ $payrollDetail->employee->tin_number ?? 'N/A' }}</td>
                <td colspan="2">Year-to-Date Tax: ₱{{ number_format($payrollDetail->withholding_tax, 2) }}</td>
            </tr>
        </table>
    </div>

    <!-- Signatures -->
    <div class="signatures">
        <table>
            <tr>
                <td>
                    <div class="signature-label">Prepared by</div>
                </td>
                <td>
                    <div class="signature-label">Checked by</div>
                </td>
                <td>
                    <div class="signature-label">Employee Signature</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>This payslip is computer-generated and does not require a signature.</p>
        <p>Generated on {{ now()->format('F d, Y g:i A') }} | Page 1 of 1</p>
        <p><strong>Important:</strong> Keep this payslip for your records. Contact HR for any discrepancies.</p>
    </div>
</body>
</html>
