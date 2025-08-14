<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Payroll Details: {{ $payroll->payroll_number }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $payroll->period_start->format('M d') }} - {{ $payroll->period_end->format('M d, Y') }}
                </p>
            </div>
            <div class="flex space-x-2">
                @can('edit payrolls')
                @if($payroll->canBeEdited())
                <a href="{{ route('payrolls.edit', $payroll) }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Edit Payroll
                </a>
                @endif
                @endcan
                <a href="{{ route('payrolls.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Back to Payrolls
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
               
            <!-- Payroll Summary -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-row gap-2 justify-between">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            @php
                                // Calculate total basic pay - use the same logic as employee details
                                $totalBasicPay = 0;
                                foreach($payroll->payrollDetails as $detail) {
                                    // Use basic pay from payBreakdown if available, otherwise use stored value
                                    $basicPay = $payBreakdownByEmployee[$detail->employee_id]['basic_pay'] ?? $detail->regular_pay ?? 0;
                                    $totalBasicPay += $basicPay;
                                }
                            @endphp
                            <div class="text-2xl font-bold text-blue-600">₱{{ number_format($totalBasicPay, 2) }}</div>
                            <div class="text-sm text-blue-800">Total Basic</div>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <div class="text-2xl font-bold text-purple-600">₱{{ number_format($payroll->payrollDetails->sum('holiday_pay'), 2) }}</div>
                            <div class="text-sm text-purple-800">Total Holiday</div>
                        </div>
                        <div class="bg-cyan-50 p-4 rounded-lg">
                            @php
                                // Calculate total rest pay
                                $totalRestPay = 0;
                                foreach($payroll->payrollDetails as $detail) {
                                    $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
                                    $hourlyRate = $detail->employee->hourly_rate ?? 0;
                                    
                                    if (isset($employeeBreakdown['rest_day'])) {
                                        $restBreakdown = $employeeBreakdown['rest_day'];
                                        $rateConfig = $restBreakdown['rate_config'];
                                        if ($rateConfig) {
                                            $regularMultiplier = $rateConfig->regular_rate_multiplier ?? 1.3;
                                            $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 1.69;
                                            
                                            $regularRestPay = $restBreakdown['regular_hours'] * $hourlyRate * $regularMultiplier;
                                            $overtimeRestPay = $restBreakdown['overtime_hours'] * $hourlyRate * $overtimeMultiplier;
                                            
                                            $totalRestPay += $regularRestPay + $overtimeRestPay;
                                        }
                                    }
                                }
                            @endphp
                            <div class="text-2xl font-bold text-cyan-600">₱{{ number_format($totalRestPay, 2) }}</div>
                            <div class="text-sm text-cyan-800">Total Rest</div>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            @php
                                // Calculate correct gross pay: Basic + Holiday + Rest + Overtime + Allowances
                                $totalGrossPay = 0;
                                foreach($payroll->payrollDetails as $detail) {
                                    $basicPay = $payBreakdownByEmployee[$detail->employee_id]['basic_pay'] ?? $detail->regular_pay ?? 0;
                                    $holidayPay = $detail->holiday_pay ?? 0;
                                    $overtimePay = $detail->overtime_pay ?? 0;
                                    $allowances = $detail->allowances ?? 0;
                                    
                                    // Calculate rest pay for this employee
                                    $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
                                    $hourlyRate = $detail->employee->hourly_rate ?? 0;
                                    $restPay = 0;
                                    
                                    if (isset($employeeBreakdown['rest_day'])) {
                                        $restBreakdown = $employeeBreakdown['rest_day'];
                                        $rateConfig = $restBreakdown['rate_config'];
                                        if ($rateConfig) {
                                            $regularMultiplier = $rateConfig->regular_rate_multiplier ?? 1.3;
                                            $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 1.69;
                                            
                                            $regularRestPay = $restBreakdown['regular_hours'] * $hourlyRate * $regularMultiplier;
                                            $overtimeRestPay = $restBreakdown['overtime_hours'] * $hourlyRate * $overtimeMultiplier;
                                            
                                            $restPay = $regularRestPay + $overtimeRestPay;
                                        }
                                    }
                                    
                                    $detailGross = $basicPay + $holidayPay + $restPay + $overtimePay + $allowances;
                                    $totalGrossPay += $detailGross;
                                }
                            @endphp
                            <div class="text-2xl font-bold text-green-600">₱{{ number_format($totalGrossPay, 2) }}</div>
                            <div class="text-sm text-green-800">Total Gross</div>
                        </div>
                        <div class="bg-red-50 p-4 rounded-lg">
                            @php
                                // Calculate actual total deductions using the same logic as employee details
                                $actualTotalDeductions = 0;
                                
                                foreach($payroll->payrollDetails as $detail) {
                                    $detailDeductionTotal = 0;
                                    
                                    // Check if this payroll uses dynamic calculations
                                    if(isset($isDynamic) && $isDynamic && isset($deductionSettings) && $deductionSettings->isNotEmpty()) {
                                        // Use dynamic calculation like in employee details section
                                        foreach($deductionSettings as $setting) {
                                            $basicPay = $payBreakdownByEmployee[$detail->employee_id]['basic_pay'] ?? $detail->basic_pay ?? 0;
                                            $grossPay = $detail->gross_pay ?? 0;
                                            $overtimePay = $detail->overtime_pay ?? 0;
                                            $allowances = $detail->allowances ?? 0;
                                            $bonuses = $detail->bonuses ?? 0;
                                            
                                            $calculatedAmount = $setting->calculateDeduction(
                                                $basicPay, 
                                                $overtimePay, 
                                                $bonuses, 
                                                $allowances, 
                                                $grossPay
                                            );
                                            $detailDeductionTotal += $calculatedAmount;
                                        }
                                    } else {
                                        // Use stored values for non-dynamic payrolls
                                        $detailDeductionTotal = $detail->sss_contribution + $detail->philhealth_contribution + $detail->pagibig_contribution + $detail->withholding_tax + $detail->late_deductions + $detail->undertime_deductions + $detail->cash_advance_deductions + $detail->other_deductions;
                                    }
                                    
                                    $actualTotalDeductions += $detailDeductionTotal;
                                }
                                
                                // Debug output (remove this after fixing)
                                $firstDetail = $payroll->payrollDetails->first();
                                if ($firstDetail) {
                                    $debugComponents = [
                                        'SSS' => $firstDetail->sss_contribution,
                                        'PhilHealth' => $firstDetail->philhealth_contribution,
                                        'PagIBIG' => $firstDetail->pagibig_contribution,
                                        'isDynamic' => isset($isDynamic) ? ($isDynamic ? 'Y' : 'N') : 'NULL',
                                        'hasSettings' => isset($deductionSettings) ? $deductionSettings->count() : 'NULL',
                                    ];
                                }
                            @endphp
                            <div class="text-2xl font-bold text-red-600">₱{{ number_format($actualTotalDeductions, 2) }}</div>
                            <div class="text-sm text-red-800">Total Deductions</div>
                            
                           
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            @php
                                // Calculate correct net pay: Correct Gross - Actual Deductions
                                $correctNetPay = $totalGrossPay - $actualTotalDeductions;
                            @endphp
                            <div class="text-2xl font-bold text-gray-600">₱{{ number_format($correctNetPay, 2) }}</div>
                            <div class="text-sm text-gray-800">Total Net</div>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Status</h4>
                            <div class="mt-1 flex items-center space-x-2">
                                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full 
                                    {{ $payroll->status == 'paid' ? 'bg-green-100 text-green-800' : 
                                       ($payroll->status == 'approved' ? 'bg-blue-100 text-blue-800' : 
                                        ($payroll->status == 'processing' ? 'bg-yellow-100 text-yellow-800' : 
                                         ($payroll->status == 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800'))) }}">
                                    {{ ucfirst($payroll->status) }}
                                </span>
                                @if(isset($isDynamic))
                                    @if($isDynamic)
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-blue-50 text-blue-700">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                            Dynamic
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-gray-50 text-gray-700">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                            </svg>
                                            Locked
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Type</h4>
                            <p class="mt-1 text-sm text-gray-600">{{ ucfirst(str_replace('_', ' ', $payroll->payroll_type)) }}</p>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Pay Date</h4>
                            <p class="mt-1 text-sm text-gray-600">{{ $payroll->pay_date->format('M d, Y') }}</p>
                        </div>
                    </div>

                   

                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Created By</h4>
                            <p class="mt-1 text-sm text-gray-600">{{ $payroll->creator->name }} on {{ $payroll->created_at->format('M d, Y g:i A') }}</p>
                        </div>
                        @if($payroll->approver)
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Approved By</h4>
                            <p class="mt-1 text-sm text-gray-600">{{ $payroll->approver->name }} on {{ $payroll->approved_at->format('M d, Y g:i A') }}</p>
                        </div>
                        @endif
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-6 flex space-x-3">
                        @can('process payrolls')
                        @if($payroll->status == 'draft')
                        <form method="POST" action="{{ route('payrolls.process', $payroll) }}" class="inline">
                            @csrf
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    onclick="return confirm('Submit this payroll for processing?')">
                                Submit for Processing
                            </button>
                        </form>
                        @endif
                        @endcan

                        <!-- View Payslip Button - Show only if not draft and not processing -->
                        @if($payroll->status != 'draft' && $payroll->status != 'processing')
                        <a href="{{ route('payrolls.payslip', $payroll) }}" 
                           class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            View Payslip
                        </a>
                        @endif

                        @can('approve payrolls')
                        @if($payroll->status == 'processing')
                        <form method="POST" action="{{ route('payrolls.approve', $payroll) }}" class="inline">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 focus:bg-purple-700 active:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    onclick="return confirm('Approve this payroll?')">
                                Approve Payroll
                            </button>
                        </form>
                        @endif
                        @endcan

                        @can('edit payrolls')
                        @if($payroll->status == 'processing')
                        <form method="POST" action="{{ route('payrolls.back-to-draft', $payroll) }}" class="inline">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 focus:bg-yellow-700 active:bg-yellow-900 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    onclick="return confirm('Move this payroll back to draft? This will clear all snapshots and make it dynamic again.')">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
                                </svg>
                                Back to Draft
                            </button>
                        </form>
                        @endif
                        @endcan

                        @can('email all payslips')
                        @if($payroll->status == 'approved')
                        <form method="POST" action="{{ route('payslips.email-all', $payroll) }}" class="inline">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    onclick="return confirm('Send payslips to all employees?')">
                                Email All Payslips
                            </button>
                        </form>
                        @endif
                        @endcan

                        @can('download all payslips')
                        @if($payroll->status == 'approved')
                        <a href="{{ route('payslips.download-all', $payroll) }}"
                           class="inline-flex items-center px-4 py-2 bg-orange-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-700 focus:bg-orange-700 active:bg-orange-900 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Download All Payslips
                        </a>
                        @endif
                        @endcan

                        @can('delete payrolls')
                        @if($payroll->canBeEdited() || ($payroll->status === 'approved' && auth()->user()->can('delete approved payrolls')))
                        <form method="POST" action="{{ route('payrolls.destroy', $payroll) }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    onclick="return confirm('Are you sure you want to delete this {{ $payroll->status }} payroll? This action cannot be undone.')">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                Delete Payroll
                            </button>
                        </form>
                        @endif
                        @endcan
                    </div>
                </div>
            </div>

            <!-- Employee Payroll Details -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Employee Payroll Details</h3>
                    
                    <div class="overflow-x-auto">   
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Employee
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Basic
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Holiday
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Rest
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Overtime
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Allowances
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Gross Pay
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Deductions
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Net Pay
                                    </th>
                                    {{-- @if($payroll->status == 'approved')
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Payslip
                                    </th>
                                    @endif --}}
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($payroll->payrollDetails as $detail)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $detail->employee->first_name }} {{ $detail->employee->last_name }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ $detail->employee->employee_number }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ $detail->employee->position->title ?? 'No Position' }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                                        @php 
                                            $payBreakdown = $payBreakdownByEmployee[$detail->employee_id] ?? ['basic_pay' => 0, 'holiday_pay' => 0];
                                            $basicPay = $payBreakdown['basic_pay'];
                                            $regularWorkdayBreakdown = ($timeBreakdowns[$detail->employee_id] ?? [])['regular_workday'] ?? ['regular_hours' => 0, 'overtime_hours' => 0];
                                            $basicRegularHours = $regularWorkdayBreakdown['regular_hours'];
                                            $basicOvertimeHours = $regularWorkdayBreakdown['overtime_hours'];
                                        @endphp
                                        <div class="font-bold text-blue-600">₱{{ number_format($basicPay, 2) }}</div>
                                        <div class="text-xs text-gray-500">{{ number_format($basicRegularHours, 1) }} hrs</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                                        @php 
                                            $holidayPay = $payBreakdown['holiday_pay'];
                                            // Calculate total holiday hours from all holiday types
                                            $holidayTypes = ['special_holiday', 'regular_holiday', 'rest_day_regular_holiday', 'rest_day_special_holiday'];
                                            $totalHolidayHours = 0;
                                            $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
                                            foreach ($holidayTypes as $type) {
                                                if (isset($employeeBreakdown[$type])) {
                                                    $totalHolidayHours += $employeeBreakdown[$type]['regular_hours'] + $employeeBreakdown[$type]['overtime_hours'];
                                                }
                                            }
                                        @endphp
                                        <div class="font-bold text-purple-600">₱{{ number_format($holidayPay, 2) }}</div>
                                        <div class="text-xs text-gray-500">{{ number_format($totalHolidayHours, 1) }} hrs</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                                        @php 
                                            // Calculate rest day pay (excluding holidays on rest days)
                                            $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
                                            $hourlyRate = $detail->employee->hourly_rate ?? 0;
                                            $totalRestPay = 0;
                                            $totalRestHours = 0;
                                            
                                            // Only count rest_day type, not rest_day_regular_holiday or rest_day_special_holiday
                                            if (isset($employeeBreakdown['rest_day'])) {
                                                $restBreakdown = $employeeBreakdown['rest_day'];
                                                $rateConfig = $restBreakdown['rate_config'];
                                                if ($rateConfig) {
                                                    $regularMultiplier = $rateConfig->regular_rate_multiplier ?? 1.3;
                                                    $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 1.69;
                                                    
                                                    $regularRestPay = $restBreakdown['regular_hours'] * $hourlyRate * $regularMultiplier;
                                                    $overtimeRestPay = $restBreakdown['overtime_hours'] * $hourlyRate * $overtimeMultiplier;
                                                    
                                                    $totalRestPay = $regularRestPay + $overtimeRestPay;
                                                    $totalRestHours = $restBreakdown['regular_hours'] + $restBreakdown['overtime_hours'];
                                                }
                                            }
                                        @endphp
                                        <div class="font-bold text-cyan-600">₱{{ number_format($totalRestPay, 2) }}</div>
                                        <div class="text-xs text-gray-500">{{ number_format($totalRestHours, 1) }} hrs</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                                        @php 
                                            // Calculate total overtime pay from all types using rate multipliers
                                            $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
                                            $hourlyRate = $detail->employee->hourly_rate ?? 0;
                                            $totalOvertimePay = 0;
                                            $totalOvertimeHours = 0;
                                            
                                            foreach ($employeeBreakdown as $logType => $breakdown) {
                                                $rateConfig = $breakdown['rate_config'];
                                                if ($rateConfig && $breakdown['overtime_hours'] > 0) {
                                                    $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 1.25;
                                                    $overtimePay = $breakdown['overtime_hours'] * $hourlyRate * $overtimeMultiplier;
                                                    $totalOvertimePay += $overtimePay;
                                                    $totalOvertimeHours += $breakdown['overtime_hours'];
                                                }
                                            }
                                        @endphp
                                        <div class="font-bold text-orange-600">₱{{ number_format($totalOvertimePay, 2) }}</div>
                                        <div class="text-xs text-gray-500">{{ number_format($totalOvertimeHours, 1) }} hrs</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                                        <div class="space-y-1">
                                            @if($detail->allowances > 0)
                                                <!-- Show Calculated Allowance Breakdown -->
                                                @if($detail->earnings_breakdown)
                                                    @php
                                                        $earningsBreakdown = json_decode($detail->earnings_breakdown, true);
                                                        $allowanceDetails = $earningsBreakdown['allowances'] ?? [];
                                                    @endphp
                                                    @if(!empty($allowanceDetails))
                                                        <div class="mb-2 p-2 bg-green-50 rounded border border-green-200">
                                                            <div class="text-xs font-medium text-green-800 mb-1">Allowance Details:</div>
                                                            @foreach($allowanceDetails as $code => $allowanceData)
                                                                <div class="text-xs text-green-700 flex justify-between">
                                                                    <span>{{ $allowanceData['name'] ?? $code }}:</span>
                                                                    <span>₱{{ number_format($allowanceData['amount'] ?? $allowanceData, 2) }}</span>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                @elseif(isset($isDynamic) && $isDynamic && $allowanceSettings->isNotEmpty())
                                                    <!-- Fallback: Show Active Settings if no breakdown available -->
                                                    <div class="mb-2 p-2 bg-green-50 rounded border border-green-200">
                                                        <div class="text-xs font-medium text-green-800 mb-1">Active Settings:</div>
                                                        @foreach($allowanceSettings as $setting)
                                                            <div class="text-xs text-green-700 flex justify-between">
                                                                <span>{{ $setting->name }}:</span>
                                                                <span>
                                                                    @if($setting->calculation_type === 'fixed_amount')
                                                                        ₱{{ number_format($setting->fixed_amount, 2) }}
                                                                        @if($setting->frequency === 'daily')
                                                                            <span class="text-green-600">/day</span>
                                                                        @endif
                                                                    @elseif($setting->calculation_type === 'percentage')
                                                                        {{ $setting->rate_percentage }}%
                                                                    @else
                                                                        {{ ucfirst(str_replace('_', ' ', $setting->calculation_type)) }}
                                                                    @endif
                                                                </span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                                
                                                <div class="font-medium text-green-600">
                                                    ₱{{ number_format($detail->allowances, 2) }}
                                                </div>
                                                @if(isset($isDynamic) && $isDynamic)
                                                    <div class="text-xs text-green-500">
                                                        <span class="inline-flex items-center">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                            </svg>
                                                            Current settings
                                                        </span>
                                                    </div>
                                                @else
                                                    <div class="text-xs text-gray-500">
                                                        <span class="inline-flex items-center">
                                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                                            </svg>
                                                            Locked snapshot
                                                        </span>
                                                    </div>
                                                @endif
                                            @else
                                                <div class="text-gray-400">₱0.00</div>
                                            @endif
                                            
                                            @if($detail->bonuses > 0)
                                                <div class="text-xs border-t pt-1">
                                                    <span class="text-blue-600">Bonus: ₱{{ number_format($detail->bonuses, 2) }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                                        @php
                                            // Calculate correct gross pay: Basic + Holiday + Rest + Overtime + Allowances
                                            $basicPay = $payBreakdownByEmployee[$detail->employee_id]['basic_pay'] ?? $detail->regular_pay ?? 0;
                                            $holidayPay = $detail->holiday_pay ?? 0;
                                            $overtimePay = $detail->overtime_pay ?? 0;
                                            $allowances = $detail->allowances ?? 0;
                                            
                                            // Calculate rest pay for gross pay calculation
                                            $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
                                            $hourlyRate = $detail->employee->hourly_rate ?? 0;
                                            $restPay = 0;
                                            
                                            if (isset($employeeBreakdown['rest_day'])) {
                                                $restBreakdown = $employeeBreakdown['rest_day'];
                                                $rateConfig = $restBreakdown['rate_config'];
                                                if ($rateConfig) {
                                                    $regularMultiplier = $rateConfig->regular_rate_multiplier ?? 1.3;
                                                    $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 1.69;
                                                    
                                                    $regularRestPay = $restBreakdown['regular_hours'] * $hourlyRate * $regularMultiplier;
                                                    $overtimeRestPay = $restBreakdown['overtime_hours'] * $hourlyRate * $overtimeMultiplier;
                                                    
                                                    $restPay = $regularRestPay + $overtimeRestPay;
                                                }
                                            }
                                            
                                            $calculatedGrossPay = $basicPay + $holidayPay + $restPay + $overtimePay + $allowances;
                                        @endphp
                                        <div class="font-bold text-green-600">₱{{ number_format($calculatedGrossPay, 2) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                        <div class="space-y-1">
                                            @if($detail->total_deductions > 0)
                                                <!-- Show Deduction Breakdown if available -->
                                                @if(isset($detail->deduction_breakdown) && is_array($detail->deduction_breakdown) && !empty($detail->deduction_breakdown))
                                                    <div class="mb-2 p-2 bg-red-50 rounded border border-red-200">
                                                        <div class="text-xs font-medium text-red-800 mb-1">Deductions Breakdown:</div>
                                                        @foreach($detail->deduction_breakdown as $code => $deductionData)
                                                            <div class="text-xs text-red-700 flex justify-between">
                                                                <span>{{ $deductionData['name'] ?? $code }}:</span>
                                                                <span>₱{{ number_format($deductionData['amount'] ?? $deductionData, 2) }}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @elseif(isset($isDynamic) && $isDynamic && $deductionSettings->isNotEmpty())
                                                    <!-- Show Active Deduction Settings with Calculated Amounts -->
                                                    <div class="mb-2 p-2 bg-red-50 rounded border border-red-200">
                                                        <div class="text-xs font-medium text-red-800 mb-1">Deductions (EE Share):</div>
                                                        @php
                                                            $calculatedDeductionTotal = 0;
                                                        @endphp
                                                        @foreach($deductionSettings as $setting)
                                                            @php
                                                                // Calculate actual deduction amount for this employee
                                                                $basicPay = $payBreakdownByEmployee[$detail->employee_id]['basic_pay'] ?? $detail->basic_pay ?? 0;
                                                                $grossPay = $detail->gross_pay ?? 0;
                                                                $overtimePay = $detail->overtime_pay ?? 0;
                                                                $allowances = $detail->allowances ?? 0;
                                                                $bonuses = $detail->bonuses ?? 0;
                                                                
                                                                $calculatedAmount = $setting->calculateDeduction(
                                                                    $basicPay, 
                                                                    $overtimePay, 
                                                                    $bonuses, 
                                                                    $allowances, 
                                                                    $grossPay
                                                                );
                                                                $calculatedDeductionTotal += $calculatedAmount;
                                                            @endphp
                                                            <div class="text-xs text-red-700 flex justify-between">
                                                                <span>{{ $setting->name }}:</span>
                                                                <span>₱{{ number_format($calculatedAmount, 2) }}</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <!-- Fallback: Show Traditional Breakdown -->
                                                    <div class="mb-2 p-2 bg-red-50 rounded border border-red-200">
                                                        <div class="text-xs font-medium text-red-800 mb-1">Deductions (EE Share):</div>
                                                        
                                                        @if($detail->sss_contribution > 0)
                                                            <div class="text-xs text-red-700 flex justify-between">
                                                                <span>SSS ({{ number_format($detail->sss_contribution, 2) }}):</span>
                                                                <span>₱{{ number_format($detail->sss_contribution, 2) }}</span>
                                                            </div>
                                                        @endif
                                                        
                                                        @if($detail->philhealth_contribution > 0)
                                                            <div class="text-xs text-red-700 flex justify-between">
                                                                <span>PHIC ({{ number_format($detail->philhealth_contribution, 2) }}):</span>
                                                                <span>₱{{ number_format($detail->philhealth_contribution, 2) }}</span>
                                                            </div>
                                                        @endif
                                                        
                                                        @if($detail->pagibig_contribution > 0)
                                                            <div class="text-xs text-red-700 flex justify-between">
                                                                <span>HDMF ({{ number_format($detail->pagibig_contribution, 2) }}):</span>
                                                                <span>₱{{ number_format($detail->pagibig_contribution, 2) }}</span>
                                                            </div>
                                                        @endif
                                                        
                                                        @if($detail->withholding_tax > 0)
                                                            <div class="text-xs text-red-700 flex justify-between">
                                                                <span>TAX ({{ number_format($detail->withholding_tax, 2) }}):</span>
                                                                <span>₱{{ number_format($detail->withholding_tax, 2) }}</span>
                                                            </div>
                                                        @endif
                                                        
                                                        @if($detail->late_deductions > 0)
                                                            <div class="text-xs text-red-700 flex justify-between">
                                                                <span>LATE ({{ number_format($detail->late_deductions, 2) }}):</span>
                                                                <span>₱{{ number_format($detail->late_deductions, 2) }}</span>
                                                            </div>
                                                        @endif
                                                        
                                                        @if($detail->undertime_deductions > 0)
                                                            <div class="text-xs text-red-700 flex justify-between">
                                                                <span>UT ({{ number_format($detail->undertime_deductions, 2) }}):</span>
                                                                <span>₱{{ number_format($detail->undertime_deductions, 2) }}</span>
                                                            </div>
                                                        @endif
                                                        
                                                        @if($detail->cash_advance_deductions > 0)
                                                            <div class="text-xs text-red-700 flex justify-between">
                                                                <span>CA:</span>
                                                                <span>₱{{ number_format($detail->cash_advance_deductions, 2) }}</span>
                                                            </div>
                                                        @endif
                                                        
                                                        @if($detail->other_deductions > 0)
                                                            <div class="text-xs text-red-700 flex justify-between">
                                                                <span>OTHER ({{ number_format($detail->other_deductions, 2) }}):</span>
                                                                <span>₱{{ number_format($detail->other_deductions, 2) }}</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                                
                                                @if(isset($isDynamic) && $isDynamic && $deductionSettings->isNotEmpty())
                                                    <div class="font-medium text-red-600">
                                                        ₱{{ number_format($calculatedDeductionTotal ?? $detail->total_deductions, 2) }}
                                                    </div>
                                                @else
                                                    <div class="font-medium text-red-600">
                                                        ₱{{ number_format($detail->total_deductions, 2) }}
                                                    </div>
                                                @endif
                                            @else
                                                @if(isset($isDynamic) && $isDynamic && $deductionSettings->isNotEmpty())
                                                    <!-- Show Available Deduction Settings when no deductions applied -->
                                                    <div class="mb-2 p-2 bg-gray-50 rounded border border-gray-200">
                                                        <div class="text-xs font-medium text-gray-600 mb-1">Available Deductions:</div>
                                                        @foreach($deductionSettings as $setting)
                                                            <div class="text-xs text-gray-500 flex justify-between">
                                                                <span>{{ $setting->name }}:</span>
                                                                <span>
                                                                    @if($setting->calculation_type === 'fixed_amount')
                                                                        ₱{{ number_format($setting->fixed_amount, 2) }}
                                                                    @elseif($setting->calculation_type === 'percentage')
                                                                        {{ $setting->rate_percentage }}%
                                                                    @else
                                                                        {{ ucfirst(str_replace('_', ' ', $setting->calculation_type)) }}
                                                                    @endif
                                                                </span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                                <div class="text-gray-400">₱0.00</div>
                                            @endif
                                            
                                            @if(isset($isDynamic) && $isDynamic)
                                                <div class="text-xs text-red-500">
                                                    <span class="inline-flex items-center">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                        </svg>
                                                        Current rates
                                                    </span>
                                                </div>
                                            @else
                                                <div class="text-xs text-gray-500">
                                                    <span class="inline-flex items-center">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                                        </svg>
                                                        Locked snapshot
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                                        @php
                                            // Calculate net pay dynamically using same logic as top summary
                                            $detailDeductionTotal = 0;
                                            
                                            // First calculate the correct gross pay for this detail
                                            $basicPay = $payBreakdownByEmployee[$detail->employee_id]['basic_pay'] ?? $detail->regular_pay ?? 0;
                                            $holidayPay = $detail->holiday_pay ?? 0;
                                            $overtimePay = $detail->overtime_pay ?? 0;
                                            $allowances = $detail->allowances ?? 0;
                                            
                                            // Calculate rest pay for this employee
                                            $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
                                            $hourlyRate = $detail->employee->hourly_rate ?? 0;
                                            $restPay = 0;
                                            
                                            if (isset($employeeBreakdown['rest_day'])) {
                                                $restBreakdown = $employeeBreakdown['rest_day'];
                                                $rateConfig = $restBreakdown['rate_config'];
                                                if ($rateConfig) {
                                                    $regularMultiplier = $rateConfig->regular_rate_multiplier ?? 1.3;
                                                    $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 1.69;
                                                    
                                                    $regularRestPay = $restBreakdown['regular_hours'] * $hourlyRate * $regularMultiplier;
                                                    $overtimeRestPay = $restBreakdown['overtime_hours'] * $hourlyRate * $overtimeMultiplier;
                                                    
                                                    $restPay = $regularRestPay + $overtimeRestPay;
                                                }
                                            }
                                            
                                            $calculatedGrossPay = $basicPay + $holidayPay + $restPay + $overtimePay + $allowances;
                                            
                                            // Check if this payroll uses dynamic calculations
                                            if(isset($isDynamic) && $isDynamic && isset($deductionSettings) && $deductionSettings->isNotEmpty()) {
                                                // Use dynamic calculation 
                                                foreach($deductionSettings as $setting) {
                                                    $bonuses = $detail->bonuses ?? 0;
                                                    
                                                    $calculatedAmount = $setting->calculateDeduction(
                                                        $basicPay, 
                                                        $overtimePay, 
                                                        $bonuses, 
                                                        $allowances, 
                                                        $calculatedGrossPay
                                                    );
                                                    $detailDeductionTotal += $calculatedAmount;
                                                }
                                            } else {
                                                // Use stored values for non-dynamic payrolls
                                                $detailDeductionTotal = $detail->sss_contribution + $detail->philhealth_contribution + $detail->pagibig_contribution + $detail->withholding_tax + $detail->late_deductions + $detail->undertime_deductions + $detail->cash_advance_deductions + $detail->other_deductions;
                                            }
                                            
                                            $calculatedNetPay = $calculatedGrossPay - $detailDeductionTotal;
                                        @endphp
                                        <div class="font-bold text-purple-600">₱{{ number_format($calculatedNetPay, 2) }}</div>
                                    </td>
                                    {{-- @if($payroll->status == 'approved')
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            @can('view payslips')
                                            <a href="{{ route('payslips.show', $detail) }}" 
                                               class="text-indigo-600 hover:text-indigo-900 text-xs">View</a>
                                            @endcan
                                            @can('download payslips')
                                            <a href="{{ route('payslips.download', $detail) }}" 
                                               class="text-blue-600 hover:text-blue-900 text-xs">PDF</a>
                                            @endcan
                                            @can('email payslips')
                                            <form method="POST" action="{{ route('payslips.email', $detail) }}" class="inline">
                                                @csrf
                                                <button type="submit" 
                                                        class="text-green-600 hover:text-green-900 text-xs"
                                                        onclick="return confirm('Send payslip to {{ $detail->employee->user->email ?? 'employee' }}?')">
                                                    Email
                                                </button>
                                            </form>
                                            @endcan
                                        </div>
                                    </td>
                                    @endif --}}
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- DTR Summary for Period -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            DTR Summary: {{ \Carbon\Carbon::parse($payroll->period_start)->format('M d') }} - {{ \Carbon\Carbon::parse($payroll->period_end)->format('M d, Y') }}
                        </h3>
                        <div class="flex space-x-2">
                            @can('create time logs')
                                @if($payroll->payrollDetails->isNotEmpty())
                                    <a href="{{ route('time-logs.create-bulk-employee', [
                                        'employee_id' => $payroll->payrollDetails->first()->employee_id,
                                        'period_start' => $payroll->period_start->format('Y-m-d'),
                                        'period_end' => $payroll->period_end->format('Y-m-d'),
                                        'payroll_id' => $payroll->id
                                    ]) }}" 
                                       class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        Manage DTR
                                    </a>
                                @endif
                            @endcan
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <style>
                            .dtr-table {
                                font-size: 0.75rem;
                            }
                            .dtr-table td, .dtr-table th {
                                white-space: nowrap;
                            }
                            .employee-column {
                                min-width: 150px;
                                max-width: 200px;
                            }
                            .date-column {
                                min-width: 90px;
                                max-width: 120px;
                            }
                        </style>
                        <table class="min-w-full divide-y divide-gray-200 dtr-table">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 z-10 employee-column">
                                        Employee
                                    </th>
                                    @foreach($periodDates as $date)
                                    <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider date-column">
                                        {{ \Carbon\Carbon::parse($date)->format('M d') }}
                                        <br>
                                        <span class="text-gray-400 normal-case">{{ \Carbon\Carbon::parse($date)->format('D') }}</span>
                                    </th>
                                    @endforeach
                                    {{-- <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Total<br>Hours
                                    </th>
                                    <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Overtime<br>Hours
                                    </th> --}}
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($payroll->payrollDetails as $detail)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-3 py-4 text-sm font-medium text-gray-900 sticky left-0 bg-white z-10 border-r">
                                        <div>
                                            {{ $detail->employee->user->name }}
                                            @php
                                                $daySchedule = $detail->employee->daySchedule;
                                                $timeSchedule = $detail->employee->timeSchedule;
                                            @endphp
                                            @if($daySchedule && $timeSchedule)
                                                <div class="text-xs text-gray-600">{{ $daySchedule->days_display }}</div>
                                                <div class="text-xs text-gray-600">{{ $timeSchedule->time_range_display }}</div>
                                            @else
                                                <div class="text-xs text-gray-600">No schedule assigned</div>
                                            @endif
                                            <div class="text-xs text-blue-600">₱{{ number_format($detail->employee->hourly_rate ?? 0, 2) }}/hr</div>
                                        </div>
                                    </td>
                                    @php 
                                        $totalEmployeeHours = 0; 
                                        $totalEmployeeOvertimeHours = 0;
                                    @endphp
                                    @foreach($periodDates as $date)
                                    @php 
                                        $timeLog = $dtrData[$detail->employee_id][$date] ?? null;
                                        
                                        // Exclude incomplete records from hour calculation
                                        $isIncompleteRecord = $timeLog && ($timeLog->remarks === 'Incomplete Time Record' || (!$timeLog->time_in || !$timeLog->time_out));
                                        
                                        $regularHours = (!$isIncompleteRecord && $timeLog) ? ($timeLog->regular_hours ?? 0) : 0;
                                        $overtimeHours = (!$isIncompleteRecord && $timeLog) ? ($timeLog->overtime_hours ?? 0) : 0;
                                        $totalEmployeeHours += $regularHours;
                                        $totalEmployeeOvertimeHours += $overtimeHours;
                                        $isWeekend = \Carbon\Carbon::parse($date)->isWeekend();
                                        
                                        // Get day type for indicator
                                        $dayType = 'Regular Day';
                                        $dayTypeColor = 'bg-green-100 text-green-800';
                                        
                                        if ($timeLog && $timeLog->log_type) {
                                            $rateConfig = $timeLog->getRateConfiguration();
                                            if ($rateConfig) {
                                                $dayType = $rateConfig->display_name;
                                                // Set color based on type
                                                if (str_contains($dayType, 'Holiday')) {
                                                    $dayTypeColor = 'bg-red-100 text-red-800';
                                                } elseif (str_contains($dayType, 'Rest')) {
                                                    $dayTypeColor = 'bg-blue-100 text-blue-800';
                                                } else {
                                                    $dayTypeColor = 'bg-green-100 text-green-800';
                                                }
                                            }
                                        } elseif ($isWeekend) {
                                            $dayType = 'Rest Day';
                                            $dayTypeColor = 'bg-blue-100 text-blue-800';
                                        }
                                    @endphp
                                    <td class="px-2 py-4 text-xs text-center {{ $isWeekend ? 'bg-gray-100' : '' }}">
                                        <!-- Day Type Indicator -->
                                        <div class="mb-2">
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full {{ $dayTypeColor }}">
                                                {{ $dayType }}
                                            </span>
                                        </div>
                                        
                                        @if($timeLog)
                                            @if($timeLog->remarks === 'Incomplete Time Record' || (!$timeLog->time_in || !$timeLog->time_out))
                                                {{-- Display INC for incomplete records --}}
                                                <div class="text-red-600 font-bold">
                                                    INC
                                                </div>
                                            @else
                                                <div class="space-y-1">
                                                    {{-- Main work schedule with regular hours --}}
                                                    @if($timeLog->time_in || $timeLog->time_out)
                                                    <div class="text-green-600 font-medium">
                                                        {{ $timeLog->time_in ? \Carbon\Carbon::parse($timeLog->time_in)->format('g:i A') : 'N/A' }} - {{ $timeLog->time_out ? \Carbon\Carbon::parse($timeLog->time_out)->format('g:i A') : 'N/A' }}
                                                    </div>
                                                    {{-- Always show hours, even if 0 --}}
                                                    <div class="text-blue-600">
                                                        {{ number_format($regularHours, 1) }}h
                                                    </div>
                                                    @endif
                                                    
                                                    {{-- Break schedule with break hours --}}
                                                    @php
                                                        // Calculate break duration - only if BOTH break_in and break_out exist
                                                        $breakHours = 0;
                                                        $showBreakTime = false;
                                                        
                                                        if ($timeLog->break_in && $timeLog->break_out && $timeLog->time_in && $timeLog->time_out) {
                                                            $breakStart = \Carbon\Carbon::parse($timeLog->break_in);
                                                            $breakEnd = \Carbon\Carbon::parse($timeLog->break_out);
                                                            $workStart = \Carbon\Carbon::parse($timeLog->time_in);
                                                            $workEnd = \Carbon\Carbon::parse($timeLog->time_out);
                                                            
                                                            // Only show break time if employee was present during the break period
                                                            // Check if break period overlaps with work period
                                                            if ($breakStart >= $workStart && $breakEnd <= $workEnd) {
                                                                $breakHours = $breakEnd->diffInMinutes($breakStart) / 60;
                                                                $showBreakTime = true;
                                                            }
                                                            // Special case: if employee came in during break time (e.g., 1pm when break is 12pm-1pm)
                                                            // Don't show break since they weren't present for it
                                                            elseif ($workStart >= $breakStart && $workStart < $breakEnd) {
                                                                // Employee came in during break time, so no break deduction applies
                                                                $showBreakTime = false;
                                                            }
                                                            // Special case: if employee left during break time
                                                            elseif ($workEnd > $breakStart && $workEnd <= $breakEnd) {
                                                                // Employee left during break time, only count partial break
                                                                $actualBreakStart = max($breakStart, $workStart);
                                                                $actualBreakEnd = min($breakEnd, $workEnd);
                                                                if ($actualBreakEnd > $actualBreakStart) {
                                                                    $breakHours = $actualBreakEnd->diffInMinutes($actualBreakStart) / 60;
                                                                    $showBreakTime = true;
                                                                }
                                                            }
                                                        }
                                                    @endphp
                                                    {{-- Commented out break time display as requested --}}
                                                    {{-- @if($showBreakTime)
                                                    <div class="text-gray-600 text-xs">
                                                        {{ $timeLog->break_in ? \Carbon\Carbon::parse($timeLog->break_in)->format('g:i A') : 'N/A' }} - {{ $timeLog->break_out ? \Carbon\Carbon::parse($timeLog->break_out)->format('g:i A') : 'N/A' }}
                                                    </div>
                                                    @if($breakHours > 0)
                                                    <div class="text-gray-600 text-xs">
                                                        {{ number_format($breakHours, 1) }}h
                                                    </div>
                                                    @endif
                                                    @endif --}}
                                                    
                                                    {{-- Additional overtime schedule if needed --}}
                                                    @if($overtimeHours > 0)
                                                    @php
                                                        // Calculate overtime period (this is simplified - you may need more logic based on your system)
                                                        $overtimeStart = '';
                                                        $overtimeEnd = '';
                                                        if ($timeLog->time_out) {
                                                            $workEnd = \Carbon\Carbon::parse($timeLog->time_out);
                                                            $workStart = \Carbon\Carbon::parse($timeLog->time_in);
                                                            
                                                            // If overtime exists, show a period extending beyond normal hours
                                                            $overtimeStart = $workEnd->copy()->subHours($overtimeHours)->format('g:i A');
                                                            $overtimeEnd = $workEnd->format('g:i A');
                                                        }
                                                    @endphp
                                                    @if($overtimeStart && $overtimeEnd)
                                                    <div class="text-orange-600 text-xs">
                                                        {{ $overtimeStart }} - {{ $overtimeEnd }}
                                                    </div>
                                                    @endif
                                                    <div class="text-orange-600 text-xs">
                                                        OT: {{ number_format($overtimeHours, 1) }}h
                                                    </div>
                                                    @endif
                                                </div>
                                            @endif
                                        @else
                                            {{-- Display N/A when there's no record from database --}}
                                            <div class="text-gray-600 font-bold">N/A</div>
                                        @endif
                                    </td>
                                    @endforeach
                                    {{-- <td class="px-3 py-4 text-sm font-medium text-center border-l">
                                        <div class="text-blue-600">{{ number_format($totalEmployeeHours, 1) }} hrs</div>
                                        @php
                                            // Calculate total regular pay using rate multipliers
                                            $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
                                            $hourlyRate = $detail->employee->hourly_rate ?? 0;
                                            $totalRegularPay = 0;
                                            
                                            foreach ($employeeBreakdown as $logType => $breakdown) {
                                                $rateConfig = $breakdown['rate_config'];
                                                if ($rateConfig && $breakdown['regular_hours'] > 0) {
                                                    $regularMultiplier = $rateConfig->regular_rate_multiplier ?? 1.0;
                                                    $regularPay = $breakdown['regular_hours'] * $hourlyRate * $regularMultiplier;
                                                    $totalRegularPay += $regularPay;
                                                }
                                            }
                                        @endphp
                                        <div class="text-xs text-gray-500">
                                            ₱{{ number_format($totalRegularPay, 2) }}
                                        </div>
                                    </td> --}}
                                    {{-- <td class="px-3 py-4 text-sm font-medium text-center border-l">
                                        <div class="text-orange-600">{{ number_format($totalEmployeeOvertimeHours, 1) }} hrs</div>
                                        @if($totalEmployeeOvertimeHours > 0)
                                        @php
                                            // Calculate total overtime pay using rate multipliers
                                            $totalOvertimePayCalc = 0;
                                            
                                            foreach ($employeeBreakdown as $logType => $breakdown) {
                                                $rateConfig = $breakdown['rate_config'];
                                                if ($rateConfig && $breakdown['overtime_hours'] > 0) {
                                                    $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 1.25;
                                                    $overtimePay = $breakdown['overtime_hours'] * $hourlyRate * $overtimeMultiplier;
                                                    $totalOvertimePayCalc += $overtimePay;
                                                }
                                            }
                                        @endphp
                                        <div class="text-xs text-gray-500">
                                            ₱{{ number_format($totalOvertimePayCalc, 2) }}
                                        </div>
                                        @endif
                                    </td> --}}
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                   
                    
                    {{-- <div class="mt-4 text-sm text-gray-600">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <strong>Legend:</strong>
                                <ul class="mt-2 space-y-1">
                                    <li><span class="text-green-600">●</span> Time In/Out</li>
                                    <li><span class="text-blue-600">●</span> Regular Hours</li>
                                </ul>
                            </div>
                            <div>
                                <ul class="mt-6 space-y-1">
                                    <li><span class="text-orange-600">●</span> Overtime Hours</li>
                                    <li><span class="text-red-600">●</span> Late Hours</li>
                                </ul>
                            </div>
                            <div class="bg-gray-100 p-3 rounded">
                                <strong>Weekend days</strong> are highlighted in gray background
                            </div>
                        </div>
                    </div> --}}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
