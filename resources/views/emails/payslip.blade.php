<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4F81BD;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 0 0 5px 5px;
        }
        .employee-info {
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .summary-table th,
        .summary-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .summary-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .amount {
            font-weight: bold;
        }
        .gross-amount {
            color: #2e7d32;
        }
        .deduction-amount {
            color: #d32f2f;
        }
        .net-amount {
            color: #1976d2;
            font-size: 1.1em;
        }
        .footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Payslip</h1>
        <p>{{ $payroll->period_start->format('F d') }} - {{ $payroll->period_end->format('F d, Y') }}</p>
    </div>

    <div class="content">
        <p>Dear {{ $employee->first_name }} {{ $employee->last_name }},</p>
        
        <p>Please find attached your payslip for the period {{ $payroll->period_start->format('F d') }} to {{ $payroll->period_end->format('F d, Y') }}.</p>

        <div class="employee-info">
            <h3>Employee Information</h3>
            <p><strong>Employee Number:</strong> {{ $employee->employee_number }}</p>
            <p><strong>Name:</strong> {{ $employee->first_name }} {{ $employee->last_name }}</p>
            <p><strong>Department:</strong> {{ $employee->department->name ?? 'N/A' }}</p>
            <p><strong>Position:</strong> {{ $employee->position->title ?? 'N/A' }}</p>
            <p><strong>Pay Date:</strong> {{ $payroll->pay_date->format('F d, Y') }}</p>
        </div>

        <h3>Payroll Summary</h3>
        <table class="summary-table">
            <tr>
                <th>Description</th>
                <th>Amount</th>
            </tr>
            <tr>
                <td>Basic Salary</td>
                <td class="amount">₱{{ number_format($payrollDetail->basic_salary, 2) }}</td>
            </tr>
            <tr>
                <td>Regular Pay</td>
                <td class="amount">₱{{ number_format($payrollDetail->regular_pay, 2) }}</td>
            </tr>
            @if($payrollDetail->overtime_pay > 0)
            <tr>
                <td>Overtime Pay ({{ number_format($payrollDetail->overtime_hours, 1) }} hrs)</td>
                <td class="amount">₱{{ number_format($payrollDetail->overtime_pay, 2) }}</td>
            </tr>
            @endif
            @if($payrollDetail->allowances > 0)
            <tr>
                <td>Allowances</td>
                <td class="amount">₱{{ number_format($payrollDetail->allowances, 2) }}</td>
            </tr>
            @endif
            @if($payrollDetail->bonuses > 0)
            <tr>
                <td>Bonuses</td>
                <td class="amount">₱{{ number_format($payrollDetail->bonuses, 2) }}</td>
            </tr>
            @endif
            <tr style="background-color: #e8f5e8;">
                <td><strong>Gross Pay</strong></td>
                <td class="amount gross-amount">₱{{ number_format($payrollDetail->gross_pay, 2) }}</td>
            </tr>
            <tr>
                <td colspan="2"><strong>Deductions</strong></td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;SSS Contribution</td>
                <td class="amount deduction-amount">₱{{ number_format($payrollDetail->sss_contribution, 2) }}</td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;PhilHealth Contribution</td>
                <td class="amount deduction-amount">₱{{ number_format($payrollDetail->philhealth_contribution, 2) }}</td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;Pag-IBIG Contribution</td>
                <td class="amount deduction-amount">₱{{ number_format($payrollDetail->pagibig_contribution, 2) }}</td>
            </tr>
            <tr>
                <td>&nbsp;&nbsp;Withholding Tax</td>
                <td class="amount deduction-amount">₱{{ number_format($payrollDetail->withholding_tax, 2) }}</td>
            </tr>
            @if($payrollDetail->other_deductions > 0)
            <tr>
                <td>&nbsp;&nbsp;Other Deductions</td>
                <td class="amount deduction-amount">₱{{ number_format($payrollDetail->other_deductions, 2) }}</td>
            </tr>
            @endif
            <tr style="background-color: #ffe8e8;">
                <td><strong>Total Deductions</strong></td>
                <td class="amount deduction-amount">₱{{ number_format($payrollDetail->total_deductions, 2) }}</td>
            </tr>
            <tr style="background-color: #e3f2fd; font-size: 1.1em;">
                <td><strong>Net Pay</strong></td>
                <td class="amount net-amount">₱{{ number_format($payrollDetail->net_pay, 2) }}</td>
            </tr>
        </table>

        <p style="margin-top: 20px;">If you have any questions regarding your payslip, please contact the HR department.</p>

        <div class="footer">
            <p>This is an automated email. Please do not reply to this message.</p>
            <p>Generated on {{ now()->format('F d, Y g:i A') }}</p>
        </div>
    </div>
</body>
</html>
