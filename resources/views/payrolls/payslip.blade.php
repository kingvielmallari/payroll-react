<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - {{ $payroll->payroll_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        @media print {
            body { margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
            .payslip-container { 
                width: 8.5in !important; 
                min-height: 11in !important;
                margin: 0 !important;
                padding: 0.5in !important;
                box-shadow: none !important;
            }
        }
        
        @page {
            size: letter;
            margin: 0.5in;
        }
        
        .payslip-container {
            width: 8.5in;
            min-height: 11in;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gray-100 p-4">
    
    <!-- Action Buttons -->
    <div class="no-print mb-6 text-center space-x-4">
        <button onclick="window.print()" 
                class="inline-flex items-center px-6 py-3 bg-blue-600 border border-transparent rounded-md font-semibold text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
            </svg>
            Print Payslips
        </button>
        
        <button onclick="downloadPDF()" 
                class="inline-flex items-center px-6 py-3 bg-green-600 border border-transparent rounded-md font-semibold text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Download PDF
        </button>
        
        <button onclick="emailPayslips()" 
                class="inline-flex items-center px-6 py-3 bg-purple-600 border border-transparent rounded-md font-semibold text-white uppercase tracking-widest hover:bg-purple-700 focus:bg-purple-700 active:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition ease-in-out duration-150">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
            Email to Employees
        </button>
        
        <a href="{{ route('payrolls.show', $payroll) }}" 
           class="inline-flex items-center px-6 py-3 bg-gray-600 border border-transparent rounded-md font-semibold text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Payroll
        </a>
    </div>

    <!-- Payslips Container -->
    <div id="payslips-container">
        @foreach($payroll->payrollDetails as $index => $detail)
            @if($index > 0)
                <div class="page-break"></div>
            @endif
            
            <div class="payslip-container p-8 mb-8">
                <!-- Header -->
                <div class="border-b-2 border-gray-800 pb-6 mb-6">
                    <div class="grid grid-cols-2 gap-8">
                        <!-- Company Info -->
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800 mb-2">{{ $company->name }}</h1>
                            <div class="text-sm text-gray-600 space-y-1">
                                <p>{{ $company->address }}</p>
                                <p>Phone: {{ $company->phone }}</p>
                                <p>Email: {{ $company->email }}</p>
                            </div>
                        </div>
                        
                        <!-- Payslip Info -->
                        <div class="text-right">
                            <h2 class="text-xl font-bold text-gray-800 mb-2">PAYSLIP</h2>
                            <div class="text-sm text-gray-600 space-y-1">
                                <p><strong>Payroll #:</strong> {{ $payroll->payroll_number }}</p>
                                <p><strong>Pay Period:</strong> {{ $payroll->period_start->format('M d') }} - {{ $payroll->period_end->format('M d, Y') }}</p>
                                <p><strong>Pay Date:</strong> {{ $payroll->period_end->format('M d, Y') }}</p>
                                <p><strong>Status:</strong> <span class="px-2 py-1 rounded text-xs 
                                    @if($payroll->status == 'approved') bg-green-100 text-green-800
                                    @elseif($payroll->status == 'processing') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">{{ ucfirst($payroll->status) }}</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employee Information -->
                <div class="grid grid-cols-2 gap-8 mb-8">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 border-b border-gray-300 pb-1">Employee Information</h3>
                        <div class="space-y-2 text-sm">
                            <div class="grid grid-cols-3 gap-2">
                                <span class="font-medium text-gray-700">Name:</span>
                                <span class="col-span-2">{{ $detail->employee->first_name }} {{ $detail->employee->last_name }}</span>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <span class="font-medium text-gray-700">Employee #:</span>
                                <span class="col-span-2">{{ $detail->employee->employee_number }}</span>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <span class="font-medium text-gray-700">Position:</span>
                                <span class="col-span-2">{{ $detail->employee->position->title ?? 'N/A' }}</span>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <span class="font-medium text-gray-700">Department:</span>
                                <span class="col-span-2">{{ $detail->employee->department->name ?? 'N/A' }}</span>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <span class="font-medium text-gray-700">Schedule:</span>
                                <span class="col-span-2">{{ $detail->employee->schedule_display ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 border-b border-gray-300 pb-1">Pay Information</h3>
                        <div class="space-y-2 text-sm">
                            <div class="grid grid-cols-3 gap-2">
                                <span class="font-medium text-gray-700">Pay Type:</span>
                                <span class="col-span-2">{{ ucfirst($detail->employee->pay_schedule) }}</span>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <span class="font-medium text-gray-700">Hourly Rate:</span>
                                <span class="col-span-2">₱{{ number_format($detail->employee->hourly_rate ?? 0, 2) }}/hr</span>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <span class="font-medium text-gray-700">Regular Hours:</span>
                                <span class="col-span-2">{{ number_format($detail->regular_hours ?? 0, 1) }} hrs</span>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <span class="font-medium text-gray-700">Overtime Hours:</span>
                                <span class="col-span-2">{{ number_format($detail->overtime_hours ?? 0, 1) }} hrs</span>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <span class="font-medium text-gray-700">Total Hours:</span>
                                <span class="col-span-2 font-semibold">{{ number_format(($detail->regular_hours ?? 0) + ($detail->overtime_hours ?? 0), 1) }} hrs</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Earnings and Deductions -->
                <div class="grid grid-cols-2 gap-8 mb-8">
                    <!-- Earnings -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 border-b border-gray-300 pb-1">Earnings</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm text-gray-700">Regular Pay</span>
                                <span class="font-semibold text-blue-600">₱{{ number_format($detail->regular_pay, 2) }}</span>
                            </div>
                            @if($detail->overtime_pay > 0)
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm text-gray-700">Overtime Pay</span>
                                <span class="font-semibold text-orange-600">₱{{ number_format($detail->overtime_pay, 2) }}</span>
                            </div>
                            @endif
                            @if($detail->allowances > 0)
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm text-gray-700">Allowances</span>
                                <span class="font-semibold text-yellow-600">₱{{ number_format($detail->allowances, 2) }}</span>
                            </div>
                            @endif
                            @if($detail->bonuses > 0)
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm text-gray-700">Bonuses</span>
                                <span class="font-semibold text-yellow-600">₱{{ number_format($detail->bonuses, 2) }}</span>
                            </div>
                            @endif
                            @if($detail->other_earnings > 0)
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm text-gray-700">Other Earnings</span>
                                <span class="font-semibold text-yellow-600">₱{{ number_format($detail->other_earnings, 2) }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between items-center py-3 border-t-2 border-gray-300 bg-green-50">
                                <span class="font-semibold text-gray-800">Gross Pay</span>
                                <span class="font-bold text-lg text-green-600">₱{{ number_format($detail->gross_pay, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Deductions -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-3 border-b border-gray-300 pb-1">Deductions</h3>
                        <div class="space-y-3">
                            @php
                                $calculatedDeductionTotal = 0;
                                $hasDeductionBreakdown = false;
                            @endphp
                            
                            <!-- Show deduction breakdown if available from snapshot -->
                            @if(isset($detail->deduction_breakdown) && is_array($detail->deduction_breakdown) && !empty($detail->deduction_breakdown))
                                @php $hasDeductionBreakdown = true; @endphp
                                @foreach($detail->deduction_breakdown as $code => $deductionData)
                                    @php
                                        $amount = $deductionData['amount'] ?? $deductionData;
                                        $calculatedDeductionTotal += $amount;
                                    @endphp
                                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                        <span class="text-sm text-gray-700">{{ $deductionData['name'] ?? $code }}</span>
                                        <span class="font-semibold text-red-600">₱{{ number_format($amount, 2) }}</span>
                                    </div>
                                @endforeach
                            @else
                                <!-- Traditional breakdown display -->
                                @if($detail->sss_contribution > 0)
                                    @php $calculatedDeductionTotal += $detail->sss_contribution; @endphp
                                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                        <span class="text-sm text-gray-700">SSS Contribution</span>
                                        <span class="font-semibold text-red-600">₱{{ number_format($detail->sss_contribution, 2) }}</span>
                                    </div>
                                @endif
                                @if($detail->philhealth_contribution > 0)
                                    @php $calculatedDeductionTotal += $detail->philhealth_contribution; @endphp
                                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                        <span class="text-sm text-gray-700">PhilHealth Contribution</span>
                                        <span class="font-semibold text-red-600">₱{{ number_format($detail->philhealth_contribution, 2) }}</span>
                                    </div>
                                @endif
                                @if($detail->pagibig_contribution > 0)
                                    @php $calculatedDeductionTotal += $detail->pagibig_contribution; @endphp
                                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                        <span class="text-sm text-gray-700">Pag-IBIG Contribution</span>
                                        <span class="font-semibold text-red-600">₱{{ number_format($detail->pagibig_contribution, 2) }}</span>
                                    </div>
                                @endif
                                @if($detail->withholding_tax > 0)
                                    @php $calculatedDeductionTotal += $detail->withholding_tax; @endphp
                                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                        <span class="text-sm text-gray-700">Withholding Tax</span>
                                        <span class="font-semibold text-red-600">₱{{ number_format($detail->withholding_tax, 2) }}</span>
                                    </div>
                                @endif
                                @if($detail->cash_advance_deductions > 0)
                                    @php $calculatedDeductionTotal += $detail->cash_advance_deductions; @endphp
                                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                        <span class="text-sm text-gray-700">Cash Advance</span>
                                        <span class="font-semibold text-red-600">₱{{ number_format($detail->cash_advance_deductions, 2) }}</span>
                                    </div>
                                @endif
                                @if($detail->other_deductions > 0)
                                    @php $calculatedDeductionTotal += $detail->other_deductions; @endphp
                                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                        <span class="text-sm text-gray-700">Other Deductions</span>
                                        <span class="font-semibold text-red-600">₱{{ number_format($detail->other_deductions, 2) }}</span>
                                    </div>
                                @endif
                            @endif
                            
                            @if($calculatedDeductionTotal == 0)
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm text-gray-400">No deductions</span>
                                <span class="font-semibold text-gray-400">₱0.00</span>
                            </div>
                            @endif
                            
                            <div class="flex justify-between items-center py-3 border-t-2 border-gray-300 bg-red-50">
                                <span class="font-semibold text-gray-800">Total Deductions</span>
                                <span class="font-bold text-lg text-red-600">₱{{ number_format($calculatedDeductionTotal > 0 ? $calculatedDeductionTotal : $detail->total_deductions, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Net Pay -->
                <div class="bg-purple-50 border-2 border-purple-200 rounded-lg p-6 mb-8">
                    <div class="flex justify-between items-center">
                        <h3 class="text-xl font-bold text-gray-800">NET PAY</h3>
                        <span class="text-3xl font-bold text-purple-600">₱{{ number_format($detail->net_pay, 2) }}</span>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">Amount to be paid to employee</p>
                </div>

                <!-- Footer -->
                <div class="border-t border-gray-300 pt-6 text-center">
                    <div class="grid grid-cols-2 gap-8 mb-6">
                        <div>
                            <div class="border-t border-gray-400 pt-2 mt-16">
                                <p class="text-sm font-semibold">Employee Signature</p>
                                <p class="text-xs text-gray-500">{{ $detail->employee->first_name }} {{ $detail->employee->last_name }}</p>
                            </div>
                        </div>
                        <div>
                            <div class="border-t border-gray-400 pt-2 mt-16">
                                <p class="text-sm font-semibold">Authorized Signature</p>
                                <p class="text-xs text-gray-500">HR Department</p>
                            </div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">
                        This payslip is generated electronically and serves as an official record of payment. 
                        For questions, please contact HR Department.
                    </p>
                    <p class="text-xs text-gray-400 mt-2">
                        Generated on {{ now()->format('M d, Y g:i A') }}
                    </p>
                </div>
            </div>
        @endforeach
    </div>

    <script>
        function downloadPDF() {
            // Simple implementation - you can enhance this with jsPDF
            window.print();
        }
        
        function emailPayslips() {
            if (confirm('Send payslips to all employees via email?')) {
                // Make AJAX call to email endpoint
                fetch('{{ route("payslips.email-all", $payroll) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Payslips sent successfully!');
                    } else {
                        alert('Error sending payslips: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error sending payslips');
                    console.error('Error:', error);
                });
            }
        }
    </script>
</body>
</html>
