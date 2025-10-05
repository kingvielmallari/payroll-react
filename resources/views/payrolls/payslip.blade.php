<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - {{ $payroll->payroll_number }}</title>
    
    <!-- Include CSS and JS assets using Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Fallback CDN Tailwind CSS for payslip styling -->
    {{-- <script src="https://cdn.tailwindcss.com"></script> --}}
    
    <!-- Local assets instead of external CDNs -->
    <script src="/assets/js/jspdf.min.js"></script>
    <script src="/assets/js/html2canvas.min.js"></script>
    <style>
        @media print {
            * { 
                print-color-adjust: exact; 
                -webkit-print-color-adjust: exact; 
                box-sizing: border-box;
            }
            body { 
                margin: 0; 
                padding: 0; 
                background: white !important;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            }
            .print\:hidden { display: none !important; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
            .payslip-container { 
                width: 8.5in !important; 
                height: auto !important;
                max-height: 10.5in !important;
                margin: 0 !important;
                padding: 0.4in 0.5in !important;
                box-shadow: none !important;
                page-break-inside: avoid !important;
                overflow: visible !important;
                border-radius: 0 !important;
                background: white !important;
            }
            
            /* Optimized typography for print */
            .compact-spacing h1 { font-size: 1.1rem !important; line-height: 1.3 !important; font-weight: 700 !important; }
            .compact-spacing h2 { font-size: 1rem !important; line-height: 1.3 !important; font-weight: 600 !important; }
            .compact-spacing h3 { font-size: 0.9rem !important; line-height: 1.3 !important; font-weight: 600 !important; }
            .compact-spacing .text-3xl { font-size: 1.6rem !important; line-height: 1.2 !important; font-weight: 700 !important; }
            .compact-spacing .text-xl { font-size: 1.1rem !important; line-height: 1.3 !important; }
            .compact-spacing .text-lg { font-size: 0.95rem !important; line-height: 1.3 !important; }
            .compact-spacing .text-base { font-size: 0.85rem !important; line-height: 1.4 !important; }
            .compact-spacing .text-sm { font-size: 0.75rem !important; line-height: 1.4 !important; }
            .compact-spacing .text-xs { font-size: 0.7rem !important; line-height: 1.3 !important; }
            
            /* Optimized spacing for single page */
            .compact-spacing .space-y-3 > * + * { margin-top: 0.3rem !important; }
            .compact-spacing .space-y-1 > * + * { margin-top: 0.15rem !important; }
            .compact-spacing .mb-8 { margin-bottom: 0.4rem !important; }
            .compact-spacing .mb-6 { margin-bottom: 0.3rem !important; }
            .compact-spacing .mb-4 { margin-bottom: 0.25rem !important; }
            .compact-spacing .mb-3 { margin-bottom: 0.2rem !important; }
            .compact-spacing .mb-2 { margin-bottom: 0.15rem !important; }
            .compact-spacing .py-3 { padding-top: 0.25rem !important; padding-bottom: 0.25rem !important; }
            .compact-spacing .py-2 { padding-top: 0.2rem !important; padding-bottom: 0.2rem !important; }
            .compact-spacing .pt-8 { padding-top: 0.3rem !important; }
            .compact-spacing .pt-6 { padding-top: 0.25rem !important; }
            .compact-spacing .p-6 { padding: 0.3rem !important; }
            .compact-spacing .gap-8 { gap: 0.3rem !important; }
            .compact-spacing .gap-6 { gap: 0.25rem !important; }
            .compact-spacing .mt-12 { margin-top: 0.4rem !important; }
            .compact-spacing .mt-8 { margin-top: 0.2rem !important; }
            .compact-spacing .mt-4 { margin-top: 0.15rem !important; }
            .compact-spacing .pb-4 { padding-bottom: 0.25rem !important; }
        }
        
        @page {
            size: 8.5in 11in;
            margin: 0.4in;
        }
        
        /* Enhanced Payslip Container */
        .payslip-container {
            width: 8.5in;
            max-width: 8.5in;
            min-height: 10in;
            margin: 20px auto;
            background: white;
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
            padding: 0.5in;
            border-radius: 6px;
            position: relative;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.5;
            color: #1f2937;
        }
        
        /* Enhanced spacing and typography for readability */
        .compact-spacing .space-y-3 > * + * { margin-top: 0.5rem; }
        .compact-spacing .space-y-1 > * + * { margin-top: 0.25rem; }
        .compact-spacing .mb-8 { margin-bottom: 1rem; }
        .compact-spacing .mb-6 { margin-bottom: 0.75rem; }
        .compact-spacing .mb-4 { margin-bottom: 0.5rem; }
        .compact-spacing .mb-3 { margin-bottom: 0.4rem; }
        .compact-spacing .mb-2 { margin-bottom: 0.3rem; }
        .compact-spacing .py-3 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .compact-spacing .py-2 { padding-top: 0.4rem; padding-bottom: 0.4rem; }
        .compact-spacing .pt-8 { padding-top: 0.75rem; }
        .compact-spacing .pt-6 { padding-top: 0.6rem; }
        .compact-spacing .p-6 { padding: 0.75rem; }
        .compact-spacing .gap-8 { gap: 0.75rem; }
        .compact-spacing .gap-6 { gap: 0.6rem; }
        .compact-spacing .mt-12 { margin-top: 1rem; }
        .compact-spacing .mt-8 { margin-top: 0.5rem; }
        .compact-spacing .mt-4 { margin-top: 0.3rem; }
        
        /* Improved typography with better readability */
        .compact-spacing .text-3xl { font-size: 1.875rem; line-height: 1.3; font-weight: 700; }
        .compact-spacing .text-xl { font-size: 1.25rem; line-height: 1.4; font-weight: 600; }
        .compact-spacing .text-lg { font-size: 1.125rem; line-height: 1.4; font-weight: 500; }
        .compact-spacing .text-base { font-size: 1rem; line-height: 1.5; }
        .compact-spacing .text-sm { font-size: 0.875rem; line-height: 1.5; }
        .compact-spacing .text-xs { font-size: 0.75rem; line-height: 1.4; }
        .compact-spacing h1 { font-size: 1.25rem; line-height: 1.3; font-weight: 700; }
        .compact-spacing h2 { font-size: 1.125rem; line-height: 1.3; font-weight: 600; }
        .compact-spacing h3 { font-size: 1rem; line-height: 1.3; font-weight: 600; }
        
        /* Enhanced borders and visual elements */
        .compact-spacing .border-b-2 { border-bottom-width: 2px; border-color: #374151; }
        .compact-spacing .border-b { border-bottom-width: 1px; border-color: #d1d5db; }
        .compact-spacing .border-t-2 { border-top-width: 2px; border-color: #374151; }
        
        /* Loading overlay styles */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 99999;
            backdrop-filter: blur(5px);
            opacity: 0;
            transition: opacity 0.2s ease-in-out;
        }
        
        .loading-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            max-width: 400px;
            margin: 1rem;
        }
        
        .loading-spinner {
            width: 48px;
            height: 48px;
            border: 4px solid #e5e7eb;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-text {
            font-size: 1.1rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .loading-subtext {
            font-size: 0.9rem;
            color: #6b7280;
        }
        
        /* Button loading state */
        .btn-loading {
            opacity: 0.7;
            pointer-events: none;
            cursor: not-allowed;
        }
    </style>
</head>
<body class="bg-gray-100 p-2">
    
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <div class="loading-text" id="loadingText">Sending Email...</div>
            <div class="loading-subtext" id="loadingSubtext">Please wait while we process your request.</div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="flex justify-center space-x-2 mb-4 no-print print:hidden">
                <a href="{{ url()->previous() }}" 
                       class="inline-flex items-center p-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors duration-200 shadow-lg hover:shadow-xl"
                       title="Back">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                    <button onclick="window.print()" 
                            class="inline-flex items-center p-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200 shadow-lg hover:shadow-xl"
                            title="Print Payslip">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                    </button>
                    
                    <button onclick="downloadPDF()" 
                            class="inline-flex items-center p-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors duration-200 shadow-lg hover:shadow-xl"
                            title="Download PDF">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </button>
                    
                    {{-- Individual email buttons for each employee --}}
                    @canany(['email payslip'], [auth()->user()])
                        @if(auth()->user()->hasAnyRole(['System Administrator', 'HR Head', 'HR Staff']))
                            @foreach($payroll->payrollDetails as $emailDetail)
                                <button onclick="emailIndividualPayslip({{ $emailDetail->employee_id }})" 
                                        class="inline-flex items-center p-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors duration-200 shadow-lg hover:shadow-xl"
                                        title="Email Payslip to {{ $emailDetail->employee->first_name }} {{ $emailDetail->employee->last_name }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                            @endforeach
                        @endif
                    @endcanany
                    
            
                </div>

    <!-- Payslips Container -->
    <div id="payslips-container">
        @foreach($payroll->payrollDetails as $index => $detail)
            @php
                // Get the snapshot data for this employee to get correct calculated values
                $employeeSnapshot = \App\Models\PayrollSnapshot::where('payroll_id', $payroll->id)
                    ->where('employee_id', $detail->employee_id)
                    ->first();
                    
                // Use snapshot values if available, otherwise fall back to detail values
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
                    $actualBasicPay = $employeeSnapshot ? $employeeSnapshot->regular_pay : $detail->regular_pay;
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
                    $actualOvertimePay = $employeeSnapshot ? $employeeSnapshot->overtime_pay : $detail->overtime_pay;
                }
                
                // Get allowances, bonuses, and incentives - use snapshot values for locked payrolls
                if ($employeeSnapshot && ($payroll->status === 'locked' || $payroll->status === 'processing')) {
                    // Use snapshot totals for locked/processing payrolls (already calculated with distribution methods)
                    $actualAllowances = $employeeSnapshot->allowances_total ?? 0;
                    $actualBonuses = $employeeSnapshot->bonuses_total ?? 0;
                    $actualIncentives = $employeeSnapshot->incentives_total ?? 0;
                } else {
                    // Use detail values for draft payrolls
                    $actualAllowances = $detail->allowances ?? 0;
                    $actualBonuses = $detail->bonuses ?? 0;
                    $actualIncentives = $detail->incentives ?? 0;
                }
                
                // Calculate total deductions (same logic as payroll show view)
                $calculatedDeductionTotal = 0;
                $hasBreakdown = false;
                
                // Use the same conditional logic as the payroll view to avoid double-counting
                if(isset($detail->deduction_breakdown) && is_array($detail->deduction_breakdown) && !empty($detail->deduction_breakdown)) {
                    // Use detail breakdown if available
                    $hasBreakdown = true;
                    foreach($detail->deduction_breakdown as $code => $deductionData) {
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
                
                // Calculate net pay dynamically: Gross Pay - Total Deductions
                $actualNetPay = $actualGrossPay - $actualTotalDeductions;
            @endphp
            
            @if($index > 0)
                <div class="page-break"></div>
            @endif
            
            <div class="payslip-container compact-spacing">
                <!-- Header -->
                <div class="border-b-2 border-gray-800 pb-3 mb-4">
                    <div class="grid grid-cols-2 gap-6">
                        <!-- Company Info -->
                        <div>
                            <h1 class="text-xl font-bold text-gray-800 mb-2">{{ $company->name }}</h1>
                            <div class="text-sm text-gray-600 space-y-1">
                                <p class="leading-relaxed">{{ $company->address }}</p>
                                <p class="text-gray-500">{{ $company->phone }} | {{ $company->email }}</p>
                            </div>
                        </div>
                        
                        <!-- Payslip Info -->
                        <div class="text-right">
                            <h2 class="text-xl font-bold text-gray-800 mb-2">PAYSLIP</h2>
                            <div class="text-sm text-gray-600 space-y-1">
                                <p><strong class="text-gray-700">Pay Period:</strong> {{ $payroll->period_start->format('M d') }} - {{ $payroll->period_end->format('M d, Y') }}</p>
                                <p><strong class="text-gray-700">Status:</strong> <span class="px-2 py-1 rounded text-xs font-medium
                                    @if($payroll->status == 'approved') bg-green-100 text-green-800
                                    @elseif($payroll->status == 'processing') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">{{ ucfirst($payroll->status) }}</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employee Information -->
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div>
                                                <div class="mb-3 border-b border-gray-300 pb-2">
                            <h3 class="text-base font-semibold text-gray-800">Employee Information</h3>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-700">Name:</span>
                                <span data-employee-id="{{ $detail->employee_id }}" data-employee-email="{{ $detail->employee->user->email ?? 'No email' }}">{{ $detail->employee->first_name }} {{ $detail->employee->last_name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-700">Employee #:</span>
                                <span>{{ $detail->employee->employee_number }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-700">Position:</span>
                                <span>{{ $detail->employee->position->title ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-700">Department:</span>
                                <span>{{ $detail->employee->department->name ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-700">Schedule:</span>
                                <span>{{ $detail->employee->schedule_display ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 mb-3 border-b border-gray-300 pb-2">Pay Information</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-700">Pay Type:</span>
                                <span>{{ ucwords(str_replace('_', ' ', $detail->employee->pay_schedule)) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-700">Basic Pay:</span>
                                <span>₱{{ number_format($detail->regular_pay ?? 0, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-700">Regular Hours:</span>
                                <span>{{ number_format($detail->regular_hours ?? 0, 1) }} hrs</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-700">Overtime Hours:</span>
                                <span>{{ number_format($detail->overtime_hours ?? 0, 1) }} hrs</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-medium text-gray-700">Total Hours:</span>
                                <span class="font-semibold">{{ number_format(($detail->regular_hours ?? 0) + ($detail->overtime_hours ?? 0), 1) }} hrs</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Earnings and Deductions -->
                <div class="grid grid-cols-2 gap-6 mb-6">
                    <!-- Earnings -->
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 mb-3 border-b border-gray-300 pb-2">Earnings</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm text-gray-700">Regular Pay</span>
                                <span class="font-semibold text-gray-800">₱{{ number_format($actualBasicPay, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm text-gray-700">Holiday Pay</span>
                                <span class="font-semibold text-gray-800">₱{{ number_format($actualHolidayPay, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm text-gray-700">Rest Pay</span>
                                <span class="font-semibold text-gray-800">₱{{ number_format($actualRestPay, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm text-gray-700">Overtime Pay</span>
                                <span class="font-semibold text-gray-800">₱{{ number_format($actualOvertimePay, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm text-gray-700">Allowances</span>
                                <span class="font-semibold text-gray-800">₱{{ number_format($detail->allowances, 2) }}</span>
                            </div>

                            @php
                                // Separate 13th month pay from other bonuses
                                $thirteenthMonthPay = 0;
                                $otherBonuses = 0;
                                
                                if (isset($detail->bonuses_breakdown) && is_array($detail->bonuses_breakdown)) {
                                    foreach ($detail->bonuses_breakdown as $bonus) {
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
                                    $otherBonuses = $detail->bonuses ?? 0;
                                }
                            @endphp
                            
                            @if($thirteenthMonthPay > 0)
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm text-gray-700">13th Month Pay</span>
                                <span class="font-semibold text-gray-800">₱{{ number_format($thirteenthMonthPay, 2) }}</span>
                            </div>
                            @endif
                            
                            @if($otherBonuses > 0)
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm text-gray-700">Bonuses</span>
                                <span class="font-semibold text-gray-800">₱{{ number_format($otherBonuses, 2) }}</span>
                            </div>
                            @endif
                            
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm text-gray-700">Incentives</span>
                                <span class="font-semibold text-gray-800">₱{{ number_format($detail->incentives, 2) }}</span>
                            </div>
                            @if($detail->other_earnings > 0)
                            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                <span class="text-sm text-gray-700">Other Earnings</span>
                                <span class="font-semibold text-gray-800">₱{{ number_format($detail->other_earnings, 2) }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between items-center py-2 border-t-2 border-gray-300 bg-green-50 mt-3">
                                <span class="font-semibold text-gray-800">Gross Pay</span>
                                <span class="font-bold text-lg text-green-600">₱{{ number_format($actualGrossPay, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Deductions -->
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 mb-3 border-b border-gray-300 pb-2">Deductions</h3>
                        <div class="space-y-2">
                            <!-- Show deduction breakdown if available from snapshot -->
                            @if(isset($detail->deduction_breakdown) && is_array($detail->deduction_breakdown) && !empty($detail->deduction_breakdown))
                                @php
                                    // Get all active deduction codes for comprehensive display
                                    $activeDeductionCodes = $activeDeductions->pluck('name', 'code')->toArray();
                                    $allDeductions = collect($detail->deduction_breakdown);
                                    
                                    // Ensure all active deductions are represented, even with 0 amount
                                    foreach($activeDeductions as $deduction) {
                                        if (!$allDeductions->has($deduction->code)) {
                                            $allDeductions->put($deduction->code, ['name' => $deduction->name, 'amount' => 0]);
                                        }
                                    }
                                    
                                    // // Ensure cash advance is always shown
                                    // if (!$allDeductions->has('CASH_ADVANCE')) {
                                    //     $allDeductions->put('CASH_ADVANCE', ['name' => 'Cash Advance', 'amount' => $detail->cash_advance_deductions ?? 0]);
                                    // }
                                @endphp
                                
                                @foreach($allDeductions as $code => $deductionData)
                                    @php
                                        $amount = $deductionData['amount'] ?? $deductionData;
                                    @endphp
                                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                        <span class="text-sm text-gray-700">{{ $deductionData['name'] ?? $code }}</span>
                                        <span class="font-semibold text-gray-800">₱{{ number_format($amount, 2) }}</span>
                                    </div>
                                @endforeach
                            @else
                                <!-- Show all active deductions including cash advances -->
                                @php
                                    // Create a mapping of deduction codes to actual amounts
                                    $deductionAmounts = [
                                        'SSS' => $detail->sss_contribution ?? 0,
                                        'PHILHEALTH' => $detail->philhealth_contribution ?? 0,
                                        'PAGIBIG' => $detail->pagibig_contribution ?? 0,
                                        'WITHHOLDING_TAX' => $detail->withholding_tax ?? 0,
                                        'OTHER_DEDUCTIONS' => $detail->other_deductions ?? 0,
                                    ];
                                @endphp
                                
                                @php
                                    $cashAdvanceShown = false;
                                @endphp
                                
                                {{-- Show all active deduction settings --}}
                                @foreach($activeDeductions as $deduction)
                                    @php
                                        $amount = $deductionAmounts[$deduction->code] ?? 0;
                                        // Check if this is a cash advance related deduction
                                        if (in_array(strtolower($deduction->code), ['cash_advance', 'ca']) || 
                                            in_array(strtolower($deduction->name), ['cash advance', 'ca'])) {
                                            $amount = $detail->cash_advance_deductions ?? 0;
                                            $cashAdvanceShown = true;
                                        }
                                    @endphp
                                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                        <span class="text-sm text-gray-700">{{ $deduction->name }}</span>
                                        <span class="font-semibold text-gray-800">₱{{ number_format($amount, 2) }}</span>
                                    </div>
                                @endforeach
                                
                                {{-- Show Cash Advance only if not already shown in active deductions --}}
                                @if(!$cashAdvanceShown)
                                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                                        <span class="text-sm text-gray-700">Cash Advance</span>
                                        <span class="font-semibold text-gray-800">₱{{ number_format($detail->cash_advance_deductions ?? 0, 2) }}</span>
                                    </div>
                                @endif
                            @endif
                            
                            <div class="flex justify-between items-center py-2 border-t-2 border-gray-300 bg-red-50 mt-3">
                                <span class="font-semibold text-gray-800">Total Deductions</span>
                                <span class="font-bold text-lg text-red-600">₱{{ number_format($actualTotalDeductions, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Net Pay -->
                <div class="bg-purple-50 border-2 border-purple-200 rounded-lg p-4 mb-6">
                    <div class="flex justify-between items-center">
                        <h3 class="text-xl font-bold text-gray-800">NET PAY</h3>
                        <span class="text-3xl font-bold text-purple-600">₱{{ number_format($actualNetPay, 2) }}</span>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">Amount to be paid to employee</p>
                </div>

                <!-- Footer -->
                <div class="pt-4 text-center mt-6">
                    <div class="grid grid-cols-2 gap-8 mb-4">
                        <div>
                            <div class="border-t border-gray-400 pt-3 mt-8">
                                <p class="text-sm font-semibold">Employee Signature</p>
                                <p class="text-xs text-gray-700 font-medium">{{ $detail->employee->first_name }} {{ $detail->employee->last_name }}</p>
                                <p class="text-xs text-gray-500">({{ $detail->employee->position->title ?? 'Employee' }})</p>
                            </div>
                        </div>
                        <div>
                            <div class="border-t border-gray-400 pt-3 mt-8">
                                <p class="text-sm font-semibold">Authorized Signature</p>
                                <p class="text-xs text-gray-700 font-medium">{{ $employerSettings->signatory_name ?? 'HR Head' }}</p>
                                <p class="text-xs text-gray-500">({{ $employerSettings->signatory_designation ?? 'HR Department' }})</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p class="text-xs text-gray-500 mb-1">
                            This payslip is generated electronically and serves as an official record of payment.
                        </p>
                        <p class="text-xs text-gray-400">
                            Generated on {{ now()->format('M d, Y g:i A') }}
                        </p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <script>
        // Auto-configure print settings
        window.addEventListener('beforeprint', function() {
            // Try to set print options programmatically
            try {
                // This works in some browsers for setting basic print preferences
                const printSettings = {
                    headerFooter: false,
                    backgroundGraphics: true,
                    marginType: 1, // Minimum margins
                    scalingType: 0 // Fit to page width
                };
            } catch (e) {
                console.log('Print settings auto-configuration not supported in this browser');
            }
        });
        
        function downloadPDF() {
            // Simple implementation - you can enhance this with jsPDF
            window.print();
        }
        
        function emailPayslips() {
            if (confirm('Send payslips to ALL employees in this payroll via email?')) {
                showLoading('Sending Payslips...', `Sending payslips to all {{ count($payroll->payrollDetails) }} employees. This may take a few moments.`);
                
                // Disable all action buttons
                disableActionButtons();
                
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
                    hideLoading();
                    enableActionButtons();
                    
                    // Small delay to ensure loading overlay is fully hidden before alert
                    setTimeout(() => {
                        if (data.success) {
                            alert('Payslips sent successfully!');
                        } else {
                            alert('Error sending payslips: ' + data.message);
                        }
                    }, 100);
                })
                .catch(error => {
                    hideLoading();
                    enableActionButtons();
                    
                    // Small delay to ensure loading overlay is fully hidden before alert
                    setTimeout(() => {
                        alert('Error sending payslips');
                    }, 100);
                    console.error('Error:', error);
                });
            }
        }
        
        function emailIndividualPayslip(employeeId) {
            const employeeElement = document.querySelector(`[data-employee-id="${employeeId}"]`);
            const name = employeeElement ? employeeElement.textContent.trim() : 'this employee';
            const email = employeeElement ? employeeElement.getAttribute('data-employee-email') : 'unknown email';
            
            if (confirm(`Send payslip to ${name} via email?\nEmail address: ${email}`)) {
                // Always show loading with consistent message format
                showLoading('Sending Payslip...', `Sending payslip to ${name}. Please wait while we generate and send the PDF.`);
                
                // Disable all action buttons
                disableActionButtons();
                
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('employee_id', employeeId);
                
                fetch('{{ route("payslips.email-individual", $payroll) }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        hideLoading();
                        enableActionButtons();
                        if (response.status === 403) {
                            throw new Error('This action is unauthorized. Please check your permissions.');
                        }
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    hideLoading();
                    enableActionButtons();
                    
                    // Small delay to ensure loading overlay is fully hidden before alert
                    setTimeout(() => {
                        if (data.success) {
                            alert('Individual payslip sent successfully!');
                        } else {
                            alert('Error sending payslip: ' + data.message);
                        }
                    }, 100);
                })
                .catch(error => {
                    hideLoading();
                    enableActionButtons();
                    
                    // Small delay to ensure loading overlay is fully hidden before alert
                    setTimeout(() => {
                        alert('Error sending payslip: ' + error.message);
                    }, 100);
                    console.error('Error:', error);
                });
            }
        }
        
        // Loading helper functions
        function showLoading(title = 'Processing...', message = 'Please wait while we process your request.') {
            const loadingText = document.getElementById('loadingText');
            const loadingSubtext = document.getElementById('loadingSubtext');
            const loadingOverlay = document.getElementById('loadingOverlay');
            
            if (loadingText) loadingText.textContent = title;
            if (loadingSubtext) loadingSubtext.textContent = message;
            if (loadingOverlay) {
                loadingOverlay.style.display = 'flex';
                loadingOverlay.style.opacity = '1';
            }
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }
        
        function hideLoading() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.style.display = 'none';
                overlay.style.opacity = '0';
            }
            document.body.style.overflow = 'auto'; // Restore scrolling
        }
        
        function disableActionButtons() {
            const buttons = document.querySelectorAll('.no-print button, .no-print a');
            buttons.forEach(button => {
                button.classList.add('btn-loading');
                button.setAttribute('disabled', 'disabled');
                button.style.pointerEvents = 'none';
                button.style.opacity = '0.6';
            });
        }
        
        function enableActionButtons() {
            const buttons = document.querySelectorAll('.no-print button, .no-print a');
            buttons.forEach(button => {
                button.classList.remove('btn-loading');
                button.removeAttribute('disabled');
                button.style.pointerEvents = 'auto';
                button.style.opacity = '1';
            });
        }
    </script>
</body>
</html>
