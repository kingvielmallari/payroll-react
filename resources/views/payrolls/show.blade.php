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
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
               
            <!-- Payroll Summary -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-row gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg flex-1 h-20 flex flex-col justify-center text-center">
                            @php
                                $totalBasicPay = 0;
                                
                                if ($payroll->status === 'draft') {
                                    // DRAFT: Use dynamic calculation from payBreakdownByEmployee
                                    foreach($payroll->payrollDetails as $detail) {
                                        $basicPay = $payBreakdownByEmployee[$detail->employee_id]['basic_pay'] ?? 0;
                                        $totalBasicPay += $basicPay;
                                    }
                                } else {
                                    // PROCESSING/APPROVED: Use stored static data from database
                                    $totalBasicPay = $payroll->payrollDetails->sum('regular_pay');
                                }
                            @endphp
                            <div class="text-2xl font-bold text-blue-600">₱{{ number_format($totalBasicPay, 2) }}</div>
                            <div class="text-sm text-blue-800">Total Basic</div>
                        </div>
                        <div class="bg-yellow-50 p-4 rounded-lg flex-1 h-20 flex flex-col justify-center text-center">
                            @php
                                $totalHolidayPay = 0;
                                
                                if ($payroll->status === 'draft') {
                                    // DRAFT: Use dynamic calculation from payBreakdownByEmployee
                                    foreach($payroll->payrollDetails as $detail) {
                                        $holidayPay = $payBreakdownByEmployee[$detail->employee_id]['holiday_pay'] ?? 0;
                                        $totalHolidayPay += $holidayPay;
                                    }
                                } else {
                                    // PROCESSING/APPROVED: Use stored static data from database
                                    $totalHolidayPay = $payroll->payrollDetails->sum('holiday_pay');
                                }
                            @endphp
                            <div class="text-2xl font-bold text-yellow-600">₱{{ number_format($totalHolidayPay, 2) }}</div>
                            <div class="text-sm text-yellow-800">Total Holiday</div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg flex-1 h-20 flex flex-col justify-center text-center">
                            @php
                                $totalRestDayPay = 0;
                                
                                if ($payroll->status === 'draft') {
                                    // DRAFT: Use dynamic calculation from payBreakdownByEmployee
                                    foreach($payroll->payrollDetails as $detail) {
                                        $restDayPay = $payBreakdownByEmployee[$detail->employee_id]['rest_day_pay'] ?? 0;
                                        $totalRestDayPay += $restDayPay;
                                    }
                                } else {
                                    // PROCESSING/APPROVED: Use stored static data from database
                                    $totalRestDayPay = $payroll->payrollDetails->sum('rest_day_pay');
                                }
                            @endphp
                            <div class="text-2xl font-bold text-cyan-600">₱{{ number_format($totalRestDayPay, 2) }}</div>
                            <div class="text-sm text-gray-800">Total Rest</div>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg flex-1 h-20 flex flex-col justify-center text-center">
                            @php
                                $totalGrossPay = 0;
                                
                                if ($payroll->status === 'draft') {
                                    // DRAFT: Use dynamic calculation from payBreakdownByEmployee
                                    foreach($payroll->payrollDetails as $detail) {
                                        $basicPay = $payBreakdownByEmployee[$detail->employee_id]['basic_pay'] ?? 0;
                                        $holidayPay = $payBreakdownByEmployee[$detail->employee_id]['holiday_pay'] ?? 0;
                                        $restDayPay = $payBreakdownByEmployee[$detail->employee_id]['rest_day_pay'] ?? 0;
                                        $overtimePay = $payBreakdownByEmployee[$detail->employee_id]['overtime_pay'] ?? 0;
                                        $allowances = $detail->allowances ?? 0;
                                        $bonuses = $detail->bonuses ?? 0;
                                        
                                        $detailGross = $basicPay + $holidayPay + $restDayPay + $overtimePay + $allowances + $bonuses;
                                        $totalGrossPay += $detailGross;
                                    }
                                } else {
                                    // PROCESSING/APPROVED: Use stored static data from database
                                    $totalGrossPay = $payroll->payrollDetails->sum('gross_pay');
                                }
                            @endphp
                            <div class="text-2xl font-bold text-green-600">₱{{ number_format($totalGrossPay, 2) }}</div>
                            <div class="text-sm text-green-800">Total Gross</div>
                        </div>
                        <div class="bg-red-50 p-4 rounded-lg flex-1 h-20 flex flex-col justify-center text-center">
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
                                        // Use stored values for non-dynamic payrolls (excluding late/undertime as they're already accounted for in hours)
                                        $detailDeductionTotal = $detail->sss_contribution + $detail->philhealth_contribution + $detail->pagibig_contribution + $detail->withholding_tax + $detail->cash_advance_deductions + $detail->other_deductions;
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
                        <div class="bg-purple-50 p-4 rounded-lg flex-1 h-20 flex flex-col justify-center text-center">
                            @php
                                // Calculate correct net pay: Correct Gross - Actual Deductions
                                $correctNetPay = $totalGrossPay - $actualTotalDeductions;
                            @endphp
                            <div class="text-2xl font-bold text-purple-600">₱{{ number_format($correctNetPay, 2) }}</div>
                            <div class="text-sm text-purple-800">Total Net</div>
                        </div>
                    </div>

                    <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-6">
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
                            <h4 class="text-sm font-medium text-gray-900">Payroll Period</h4>
                            <p class="mt-1 text-sm text-gray-600">{{ $payroll->period_start->format('M d') }} - {{ $payroll->period_end->format('M d, Y') }}</p>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Pay Date</h4>
                            <p class="mt-1 text-sm text-gray-600">{{ $payroll->pay_date->format('M d, Y') }}</p>
                        </div>
                    </div>

                   
{{-- 
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
                    </div> --}}

                    <!-- Action Buttons -->
                    <div class="mt-6 flex space-x-3">
                        @can('process payrolls')
                        @if($payroll->status == 'draft')
                        @if(isset($schedule) && isset($employee))
                        {{-- Automation payroll - use unified process route --}}
                        <form method="POST" action="{{ route('payrolls.automation.process', ['schedule' => $schedule, 'employee' => $employee]) }}" class="inline">
                            @csrf
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    onclick="return confirm('Submit this payroll for processing? This will save it to the database with locked data snapshots.')">
                                Submit for Processing
                            </button>
                        </form>
                        @else
                        {{-- Regular payroll - use standard process route --}}
                        <form method="POST" action="{{ route('payrolls.process', $payroll) }}" class="inline">
                            @csrf
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    onclick="return confirm('Submit this payroll for processing?')">
                                Submit for Processing
                            </button>
                        </form>
                        @endif
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
                        @if(isset($schedule) && isset($employee))
                        {{-- Automation payroll - use unified approve route --}}
                        <form method="POST" action="{{ route('payrolls.automation.approve', ['schedule' => $schedule, 'employee' => $employee]) }}" class="inline">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 focus:bg-purple-700 active:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    onclick="return confirm('Approve this payroll?')">
                                Approve Payroll
                            </button>
                        </form>
                        @else
                        {{-- Regular payroll - use standard approve route --}}
                        <form method="POST" action="{{ route('payrolls.approve', $payroll) }}" class="inline">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 focus:bg-purple-700 active:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    onclick="return confirm('Approve this payroll?')">
                                Approve Payroll
                            </button>
                        </form>
                        @endif
                        @endif
                        @endcan

                        @can('edit payrolls')
                        @if($payroll->status == 'processing')
                        @if(isset($schedule) && isset($employee))
                        {{-- Automation payroll - use unified back-to-draft route --}}
                        <form method="POST" action="{{ route('payrolls.automation.back-to-draft', ['schedule' => $schedule, 'employee' => $employee]) }}" class="inline">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 focus:bg-yellow-700 active:bg-yellow-900 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    onclick="return confirm('Move this payroll back to draft? This will delete the saved payroll and return to dynamic calculations.')">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
                                </svg>
                                Back to Draft
                            </button>
                        </form>
                        @else
                        {{-- Regular payroll - use standard back-to-draft route --}}
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
                        @if(!($payroll->payroll_type === 'automated' && in_array($payroll->status, ['draft', 'processing'])) && ($payroll->canBeEdited() || ($payroll->status === 'approved' && auth()->user()->can('delete approved payrolls'))))
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
                                        Bonuses
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
                                            if ($payroll->status === 'draft') {
                                                // DRAFT: Use dynamic calculation from payBreakdownByEmployee
                                                $payBreakdown = $payBreakdownByEmployee[$detail->employee_id] ?? [
                                                    'basic_pay' => 0, 
                                                    'holiday_pay' => 0,
                                                    'rest_day_pay' => 0,
                                                    'overtime_pay' => 0
                                                ];
                                            } else {
                                                // PROCESSING/APPROVED: Use stored static data from database
                                                $payBreakdown = [
                                                    'basic_pay' => $detail->regular_pay ?? 0, 
                                                    'holiday_pay' => $detail->holiday_pay ?? 0,
                                                    'rest_day_pay' => $detail->rest_day_pay ?? 0,
                                                    'overtime_pay' => $detail->overtime_pay ?? 0
                                                ];
                                            }
                                            
                                            $basicPay = $payBreakdown['basic_pay'];
                                            
                                            // For hours display, always use timeBreakdowns (for both draft and processing)
                                            $regularWorkdayBreakdown = ($timeBreakdowns[$detail->employee_id] ?? [])['regular_workday'] ?? ['regular_hours' => 0, 'overtime_hours' => 0];
                                            
                                            if ($payroll->status === 'draft') {
                                                // DRAFT: Use dynamic hours from timeBreakdowns  
                                                $basicRegularHours = $regularWorkdayBreakdown['regular_hours'];
                                            } else {
                                                // PROCESSING/APPROVED: Can use stored regular_hours or timeBreakdowns (both should match)
                                                $basicRegularHours = $regularWorkdayBreakdown['regular_hours']; // Keep using timeBreakdowns for transparency
                                            }
                                            $basicOvertimeHours = $regularWorkdayBreakdown['overtime_hours'];
                                        @endphp
                                        <div class="text-xs text-gray-500">{{ number_format($basicRegularHours, 1) }} hrs</div>
                                        <div class="font-bold text-blue-600">₱{{ number_format($basicPay, 2) }}</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                                        @php 
                                            // Use backend calculation instead of frontend calculation
                                            $holidayPay = $payBreakdown['holiday_pay'] ?? 0;
                                            // Calculate holiday breakdown by type - SHOW ONLY REGULAR HOURS
                                            $holidayTypes = ['special_holiday', 'regular_holiday', 'rest_day_regular_holiday', 'rest_day_special_holiday'];
                                            $holidayBreakdown = [];
                                            $totalHolidayRegularHours = 0;
                                            $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
                                            
                                            foreach ($holidayTypes as $type) {
                                                if (isset($employeeBreakdown[$type])) {
                                                    $breakdown = $employeeBreakdown[$type];
                                                    $rateConfig = $breakdown['rate_config'];
                                                    $displayName = $rateConfig ? $rateConfig->display_name : 'Holiday';
                                                    $regularHours = $breakdown['regular_hours']; // ONLY regular hours
                                                    
                                                    if ($regularHours > 0) {
                                                        $hourlyRate = $detail->employee->hourly_rate ?? 0;
                                                        $regularMultiplier = $rateConfig ? $rateConfig->regular_rate_multiplier : 2.0;
                                                        $regularAmount = $regularHours * $hourlyRate * $regularMultiplier;
                                                        
                                                        // Calculate percentage for display - show actual multiplier percentage
                                                        $percentageDisplay = number_format($regularMultiplier * 100, 0) . '%';
                                                        
                                                        if (isset($holidayBreakdown[$displayName])) {
                                                            $holidayBreakdown[$displayName]['hours'] += $regularHours;
                                                            $holidayBreakdown[$displayName]['amount'] += $regularAmount;
                                                        } else {
                                                            $holidayBreakdown[$displayName] = [
                                                                'hours' => $regularHours,
                                                                'amount' => $regularAmount,
                                                                'rate' => $hourlyRate * $regularMultiplier,
                                                                'percentage' => $percentageDisplay
                                                            ];
                                                        }
                                                        $totalHolidayRegularHours += $regularHours;
                                                    }
                                                }
                                            }
                                        @endphp
                                        
                                        <div>
                                            @if(!empty($holidayBreakdown))
                                                <!-- Show individual holiday type breakdowns -->
                                                @foreach($holidayBreakdown as $type => $data)
                                                    <div class="text-xs text-gray-500 mb-1">
                                                        
                                                            <span>{{ $type }}: {{ number_format($data['hours'], 1) }}h</span>
                                                    
                                                        <div class="text-xs text-gray-600">
                                                            {{ $data['percentage'] }} = ₱{{ number_format($data['amount'], 2) }}
                                                        </div>
                                                    </div>
                                                @endforeach
                                                
                                                <div class="text-xs border-t pt-1">
                                                    <div class="text-gray-500">Total: {{ number_format($totalHolidayRegularHours, 1) }} hrs</div>
                                                    <div class="font-bold text-purple-600">₱{{ number_format($holidayPay, 2) }}</div>
                                                </div>
                                            @else
                                                <div class="text-gray-400">₱0.00</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                                        @php 
                                            // Use backend calculation for rest day pay
                                            $restDayPay = $payBreakdown['rest_day_pay'] ?? 0;
                                            
                                            // Calculate rest day breakdown by type - SHOW ONLY REGULAR HOURS
                                            $restDayTypes = ['rest_day'];
                                            $restDayBreakdown = [];
                                            $totalRestRegularHours = 0;
                                            $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
                                            
                                            foreach ($restDayTypes as $type) {
                                                if (isset($employeeBreakdown[$type])) {
                                                    $breakdown = $employeeBreakdown[$type];
                                                    $rateConfig = $breakdown['rate_config'];
                                                    $displayName = $rateConfig ? $rateConfig->display_name : 'Rest Day';
                                                    $regularHours = $breakdown['regular_hours']; // ONLY regular hours
                                                    
                                                    if ($regularHours > 0) {
                                                        $hourlyRate = $detail->employee->hourly_rate ?? 0;
                                                        $regularMultiplier = $rateConfig ? $rateConfig->regular_rate_multiplier : 1.3;
                                                        $regularAmount = $regularHours * $hourlyRate * $regularMultiplier;
                                                        
                                                        // Calculate percentage for display - show actual multiplier percentage
                                                        $percentageDisplay = number_format($regularMultiplier * 100, 0) . '%';
                                                        
                                                        if (isset($restDayBreakdown[$displayName])) {
                                                            $restDayBreakdown[$displayName]['hours'] += $regularHours;
                                                            $restDayBreakdown[$displayName]['amount'] += $regularAmount;
                                                        } else {
                                                            $restDayBreakdown[$displayName] = [
                                                                'hours' => $regularHours,
                                                                'amount' => $regularAmount,
                                                                'rate' => $hourlyRate * $regularMultiplier,
                                                                'percentage' => $percentageDisplay
                                                            ];
                                                        }
                                                        $totalRestRegularHours += $regularHours;
                                                    }
                                                }
                                            }
                                            
                                            // For both draft and processing, prioritize PayrollDetail stored hours if available
                                            if (empty($restDayBreakdown) && isset($detail->rest_day_hours) && $detail->rest_day_hours > 0) {
                                                $totalRestRegularHours = $detail->rest_day_hours;
                                            }
                                        @endphp
                                        
                                        <div>
                                            @if(!empty($restDayBreakdown))
                                                <!-- Show individual rest day type breakdowns -->
                                                @foreach($restDayBreakdown as $type => $data)
                                                    <div class="text-xs text-gray-500 mb-1">
                                                       
                                                            <span>{{ $type }}: {{ number_format($data['hours'], 1) }}h</span>
                                                     
                                                        <div class="text-xs text-gray-600">
                                                            {{ $data['percentage'] }} = ₱{{ number_format($data['amount'], 2) }}
                                                        </div>
                                                    </div>
                                                @endforeach
                                                
                                                <div class="text-xs border-t pt-1">
                                                    <div class="text-gray-500">Total: {{ number_format($totalRestRegularHours, 1) }} hrs</div>
                                                    <div class="font-bold text-cyan-600">₱{{ number_format($restDayPay, 2) }}</div>
                                                </div>
                                            @else
                                                @if($totalRestRegularHours > 0)
                                                    <div class="text-xs text-gray-500">{{ number_format($totalRestRegularHours, 1) }} hrs</div>
                                                    <div class="font-bold text-cyan-600">₱{{ number_format($restDayPay, 2) }}</div>
                                                @else
                                                    <div class="text-gray-400">₱0.00</div>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                                        @php 
                                            // Use backend calculation for overtime pay
                                            $overtimePay = $payBreakdown['overtime_pay'] ?? 0;
                                            $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
                                            $totalOvertimeHours = 0;
                                            $overtimeBreakdown = [];
                                            
                                            foreach ($employeeBreakdown as $logType => $breakdown) {
                                                if ($breakdown['overtime_hours'] > 0) {
                                                    $rateConfig = $breakdown['rate_config'];
                                                    $displayName = $rateConfig ? $rateConfig->display_name : 'Regular Day';
                                                    $overtimeHours = $breakdown['overtime_hours'];
                                                    $regularOvertimeHours = $breakdown['regular_overtime_hours'] ?? 0;
                                                    $nightDiffOvertimeHours = $breakdown['night_diff_overtime_hours'] ?? 0;
                                                    $totalOvertimeHours += $overtimeHours;
                                                    
                                                    $hourlyRate = $detail->employee->hourly_rate ?? 0;
                                                    
                                                    // Regular overtime breakdown
                                                    if ($regularOvertimeHours > 0) {
                                                        $overtimeMultiplier = $rateConfig ? $rateConfig->overtime_rate_multiplier : 1.25;
                                                        $overtimeAmount = $regularOvertimeHours * $hourlyRate * $overtimeMultiplier;
                                                        $percentageDisplay = number_format($overtimeMultiplier * 100, 0) . '%';
                                                        
                                                        $overtimeBreakdown[] = [
                                                            'name' => $displayName . ' OT',
                                                            'hours' => $regularOvertimeHours,
                                                            'amount' => $overtimeAmount,
                                                            'percentage' => $percentageDisplay
                                                        ];
                                                    }
                                                    
                                                    // Night differential overtime breakdown
                                                    if ($nightDiffOvertimeHours > 0) {
                                                        // Get night differential rate
                                                        $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
                                                        $nightDiffMultiplier = $nightDiffSetting ? $nightDiffSetting->rate_multiplier : 1.10;
                                                        
                                                        // Base overtime rate + night differential
                                                        $baseOvertimeMultiplier = $rateConfig ? $rateConfig->overtime_rate_multiplier : 1.25;
                                                        $combinedMultiplier = $baseOvertimeMultiplier + ($nightDiffMultiplier - 1); // e.g., 1.25 + 0.10 = 1.35
                                                        
                                                        $nightDiffOvertimeAmount = $nightDiffOvertimeHours * $hourlyRate * $combinedMultiplier;
                                                        $percentageDisplay = number_format($combinedMultiplier * 100, 0) . '%';
                                                        
                                                        $overtimeBreakdown[] = [
                                                            'name' => $displayName . ' OT+ND',
                                                            'hours' => $nightDiffOvertimeHours,
                                                            'amount' => $nightDiffOvertimeAmount,
                                                            'percentage' => $percentageDisplay
                                                        ];
                                                    }
                                                    
                                                    // Fallback: if no breakdown available, show total overtime
                                                    if ($regularOvertimeHours == 0 && $nightDiffOvertimeHours == 0 && $overtimeHours > 0) {
                                                        $overtimeMultiplier = $rateConfig ? $rateConfig->overtime_rate_multiplier : 1.25;
                                                        $overtimeAmount = $overtimeHours * $hourlyRate * $overtimeMultiplier;
                                                        $percentageDisplay = number_format($overtimeMultiplier * 100, 0) . '%';
                                                        
                                                        $overtimeBreakdown[] = [
                                                            'name' => $displayName . ' OT',
                                                            'hours' => $overtimeHours,
                                                            'amount' => $overtimeAmount,
                                                            'percentage' => $percentageDisplay
                                                        ];
                                                    }
                                                }
                                            }
                                        @endphp
                                        
                                        <div>
                                            @if(!empty($overtimeBreakdown))
                                                @foreach($overtimeBreakdown as $ot)
                                                    <div class="text-xs text-gray-500 mb-1">
                                                     
                                                            <span>{{ $ot['name'] }}: {{ number_format($ot['hours'], 1) }}h</span>
                                                  
                                                        <div class="text-xs text-gray-600">
                                                            {{ $ot['percentage'] }} = ₱{{ number_format($ot['amount'], 2) }}
                                                        </div>
                                                    </div>
                                                @endforeach
                                                
                                                <div class="text-xs border-t pt-1">
                                                    <div class="text-gray-500">Total: {{ number_format($totalOvertimeHours, 1) }} hrs</div>
                                                    <div class="font-bold text-orange-600">₱{{ number_format($overtimePay, 2) }}</div>
                                                </div>
                                            @else
                                                <div class="text-gray-400">₱0.00</div>
                                            @endif
                                        </div>
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
                                                       
                                                            @foreach($allowanceDetails as $code => $allowanceData)
                                                                <div class="text-xs text-gray-500">
                                                                    <span>{{ $allowanceData['name'] ?? $code }}:</span>
                                                                    <span>₱{{ number_format($allowanceData['amount'] ?? $allowanceData, 2) }}</span>
                                                                </div>
                                                            @endforeach
                                                       
                                                    @endif
                                                @elseif(isset($isDynamic) && $isDynamic && $allowanceSettings->isNotEmpty())
                                                    <!-- Fallback: Show Active Settings if no breakdown available -->
                                                    
                                                        {{-- <div class="text-xs font-medium text-green-800 mb-1">Active Settings:</div> --}}
                                                        @foreach($allowanceSettings as $setting)
                                                            <div class="text-xs text-gray-500">
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
                                                 
                                                @endif
                                                
                                                <div class="font-bold text-green-600">
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
                                        </div>
                                    </td>
                                    <!-- Bonuses Column -->
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                                        <div class="space-y-1">
                                            @if($detail->bonuses > 0)
                                                <!-- Show Calculated Bonus Breakdown -->
                                                @if($detail->earnings_breakdown)
                                                    @php
                                                        $earningsBreakdown = json_decode($detail->earnings_breakdown, true);
                                                        $bonusDetails = $earningsBreakdown['bonuses'] ?? [];
                                                    @endphp
                                                    @if(!empty($bonusDetails))
                                                        @foreach($bonusDetails as $code => $bonusData)
                                                            <div class="text-xs text-gray-500">
                                                                <span>{{ $bonusData['name'] ?? $code }}:</span>
                                                                <span>₱{{ number_format($bonusData['amount'] ?? $bonusData, 2) }}</span>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                @endif
                                                
                                                <div class="font-bold text-blue-600">
                                                    ₱{{ number_format($detail->bonuses, 2) }}
                                                </div>
                                                @if(isset($isDynamic) && $isDynamic)
                                                    <div class="text-xs text-blue-500">
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
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                                        @php
                                            // Calculate correct gross pay: Basic + Holiday + Rest + Overtime + Allowances + Bonuses
                                            $basicPay = $payBreakdownByEmployee[$detail->employee_id]['basic_pay'] ?? $detail->regular_pay ?? 0;
                                            $holidayPay = $detail->holiday_pay ?? 0;
                                            $overtimePay = $detail->overtime_pay ?? 0;
                                            $allowances = $detail->allowances ?? 0;
                                            $bonuses = $detail->bonuses ?? 0;
                                            
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
                                            
                                            $calculatedGrossPay = $basicPay + $holidayPay + $restPay + $overtimePay + $allowances + $bonuses;
                                        @endphp
                                        
                                        <!-- Show Gross Pay Breakdown -->
                                        <div class="space-y-1">
                                            @if($calculatedGrossPay > 0)
                                               
                                                    @if($basicPay > 0)
                                                        <div class="text-xs text-gray-500">
                                                            <span>Basic:</span>
                                                            <span>₱{{ number_format($basicPay, 2) }}</span>
                                                        </div>
                                                    @endif
                                                    @if($holidayPay > 0)
                                                        <div class="text-xs text-gray-500">
                                                            <span>Holiday:</span>
                                                            <span>₱{{ number_format($holidayPay, 2) }}</span>
                                                        </div>
                                                    @endif
                                                    @if($restPay > 0)
                                                        <div class="text-xs text-gray-500">
                                                            <span>Rest:</span>
                                                            <span>₱{{ number_format($restPay, 2) }}</span>
                                                        </div>
                                                    @endif
                                                    @if($overtimePay > 0)
                                                        <div class="text-xs text-gray-500">
                                                            <span>Overtime:</span>
                                                            <span>₱{{ number_format($overtimePay, 2) }}</span>
                                                        </div>
                                                    @endif
                                                    @if($allowances > 0)
                                                        <div class="text-xs text-gray-500">
                                                            <span>Allow.:</span>
                                                            <span>₱{{ number_format($allowances, 2) }}</span>
                                                        </div>
                                                    @endif
                                                    @if($bonuses > 0)
                                                        <div class="text-xs text-gray-500">
                                                            <span>Bonus:</span>
                                                            <span>₱{{ number_format($bonuses, 2) }}</span>
                                                        </div>
                                                    @endif
                                             
                                            @endif
                                            <div class="font-bold text-green-600">₱{{ number_format($calculatedGrossPay, 2) }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                        <div class="space-y-1">
                                            @if($detail->total_deductions > 0)
                                                @php
                                                    $calculatedDeductionTotal = 0;
                                                    $hasBreakdown = false;
                                                @endphp
                                                
                                                <!-- Show Deduction Breakdown if available (from snapshot or dynamic calculation) -->
                                                @if(isset($detail->deduction_breakdown) && is_array($detail->deduction_breakdown) && !empty($detail->deduction_breakdown))
                                                    @php $hasBreakdown = true; @endphp
                                                    @foreach($detail->deduction_breakdown as $code => $deductionData)
                                                        @php
                                                            $amount = $deductionData['amount'] ?? $deductionData;
                                                            $calculatedDeductionTotal += $amount;
                                                        @endphp
                                                        <div class="text-xs text-gray-500">
                                                            <span>{{ $deductionData['name'] ?? $code }}:</span>
                                                            <span>₱{{ number_format($amount, 2) }}</span>
                                                        </div>
                                                    @endforeach
                                                @elseif(isset($isDynamic) && $isDynamic && $deductionSettings->isNotEmpty())
                                                    @php $hasBreakdown = true; @endphp
                                                    <!-- Show Active Deduction Settings with Calculated Amounts -->
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
                                                        <div class="text-xs text-gray-500">
                                                            <span>{{ $setting->name }}:</span>
                                                            <span>₱{{ number_format($calculatedAmount, 2) }}</span>
                                                        </div>
                                                    @endforeach
                                                @elseif(!isset($isDynamic) || !$isDynamic || $deductionSettings->isEmpty())
                                                    @php $hasBreakdown = true; @endphp
                                                    <!-- Show Traditional Breakdown for snapshot/non-dynamic payrolls -->
                                                    @if($detail->sss_contribution > 0)
                                                        @php $calculatedDeductionTotal += $detail->sss_contribution; @endphp
                                                        <div class="text-xs text-gray-500">
                                                            <span>SSS:</span>
                                                            <span>₱{{ number_format($detail->sss_contribution, 2) }}</span>
                                                        </div>
                                                    @endif
                                                    
                                                    @if($detail->philhealth_contribution > 0)
                                                        @php $calculatedDeductionTotal += $detail->philhealth_contribution; @endphp
                                                        <div class="text-xs text-gray-500">
                                                            <span>PhilHealth:</span>
                                                            <span>₱{{ number_format($detail->philhealth_contribution, 2) }}</span>
                                                        </div>
                                                    @endif
                                                    
                                                    @if($detail->pagibig_contribution > 0)
                                                        @php $calculatedDeductionTotal += $detail->pagibig_contribution; @endphp
                                                        <div class="text-xs text-gray-500">
                                                            <span>PagIBIG:</span>
                                                            <span>₱{{ number_format($detail->pagibig_contribution, 2) }}</span>
                                                        </div>
                                                    @endif
                                                    
                                                    @if($detail->withholding_tax > 0)
                                                        @php $calculatedDeductionTotal += $detail->withholding_tax; @endphp
                                                        <div class="text-xs text-gray-500">
                                                            <span>BIR:</span>
                                                            <span>₱{{ number_format($detail->withholding_tax, 2) }}</span>
                                                        </div>
                                                    @endif
                                                    
                                                    @if($detail->cash_advance_deductions > 0)
                                                        @php $calculatedDeductionTotal += $detail->cash_advance_deductions; @endphp
                                                        <div class="text-xs text-gray-500">
                                                            <span>CA:</span>
                                                            <span>₱{{ number_format($detail->cash_advance_deductions, 2) }}</span>
                                                        </div>
                                                    @endif
                                                    
                                                    @if($detail->other_deductions > 0)
                                                        @php $calculatedDeductionTotal += $detail->other_deductions; @endphp
                                                        <div class="text-xs text-gray-500">
                                                            <span>Other:</span>
                                                            <span>₱{{ number_format($detail->other_deductions, 2) }}</span>
                                                        </div>
                                                    @endif
                                                @endif
                                                
                                                <!-- Total deductions -->
                                                <div class="font-medium text-red-600">
                                                    ₱{{ number_format($calculatedDeductionTotal > 0 ? $calculatedDeductionTotal : $detail->total_deductions, 2) }}
                                                </div>
                                            @else
                                                @if(isset($isDynamic) && $isDynamic && $deductionSettings->isNotEmpty())
                                                    <!-- Show Available Deduction Settings when no deductions applied -->
                                                    @foreach($deductionSettings as $setting)
                                                        <div class="text-xs text-gray-400">
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
                                                @endif
                                                <div class="font-medium text-gray-400">₱0.00</div>
                                            @endif
                                            
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
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                                        @php
                                            // Calculate net pay - use snapshot data for processing/approved payrolls
                                            $detailDeductionTotal = 0;
                                            
                                            // First calculate the correct gross pay for this detail
                                            $basicPay = $payBreakdownByEmployee[$detail->employee_id]['basic_pay'] ?? $detail->regular_pay ?? 0;
                                            $holidayPay = $detail->holiday_pay ?? 0;
                                            $overtimePay = $detail->overtime_pay ?? 0;
                                            $allowances = $detail->allowances ?? 0;
                                            $bonuses = $detail->bonuses ?? 0;
                                            
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
                                            
                                            $calculatedGrossPay = $basicPay + $holidayPay + $restPay + $overtimePay + $allowances + $bonuses;
                                            
                                            // For processing/approved payrolls with snapshots, use the snapshot deduction total
                                            if (!isset($isDynamic) || !$isDynamic) {
                                                // Use snapshot data - same logic as deduction column
                                                if (isset($detail->deduction_breakdown) && is_array($detail->deduction_breakdown)) {
                                                    // Sum up snapshot breakdown amounts
                                                    foreach ($detail->deduction_breakdown as $deduction) {
                                                        $detailDeductionTotal += $deduction['amount'] ?? 0;
                                                    }
                                                } else {
                                                    // Fallback to stored values
                                                    $detailDeductionTotal = $detail->total_deductions ?? 0;
                                                }
                                            } elseif(isset($isDynamic) && $isDynamic && isset($deductionSettings) && $deductionSettings->isNotEmpty()) {
                                                // Use dynamic calculation with SAME variables as deduction column
                                                foreach($deductionSettings as $setting) {
                                                    // Use same variable mapping as deduction column calculation
                                                    $basicPayForDeduction = $payBreakdownByEmployee[$detail->employee_id]['basic_pay'] ?? $detail->basic_pay ?? 0;
                                                    $grossPayForDeduction = $detail->gross_pay ?? 0;
                                                    $overtimePayForDeduction = $detail->overtime_pay ?? 0;
                                                    $allowancesForDeduction = $detail->allowances ?? 0;
                                                    $bonuses = $detail->bonuses ?? 0;
                                                    
                                                    $calculatedAmount = $setting->calculateDeduction(
                                                        $basicPayForDeduction, 
                                                        $overtimePayForDeduction, 
                                                        $bonuses, 
                                                        $allowancesForDeduction, 
                                                        $grossPayForDeduction
                                                    );
                                                    $detailDeductionTotal += $calculatedAmount;
                                                }
                                            } else {
                                                // Use stored values for non-dynamic payrolls (excluding late/undertime as they're already accounted for in hours)
                                                $detailDeductionTotal = $detail->sss_contribution + $detail->philhealth_contribution + $detail->pagibig_contribution + $detail->withholding_tax + $detail->cash_advance_deductions + $detail->other_deductions;
                                            }
                                            
                                            $calculatedNetPay = $calculatedGrossPay - $detailDeductionTotal;
                                        @endphp
                                        
                                        <!-- Show Net Pay Breakdown -->
                                        <div class="space-y-1">
                                            @if($calculatedNetPay > 0)
                                               
                                                    <div class="text-xs text-gray-500">
                                                        <span>Gross:</span>
                                                        <span>₱{{ number_format($calculatedGrossPay, 2) }}</span>
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        <span>Deduct:</span>
                                                        <span>₱{{ number_format($detailDeductionTotal, 2) }}</span>
                                                    </div>
                                              
                                            @endif
                                            <div class="font-bold text-purple-600">₱{{ number_format($calculatedNetPay, 2) }}</div>
                                        </div>
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
                                @if($payroll->payrollDetails->isNotEmpty() && $payroll->status === 'draft')
                                    <a href="{{ route('time-logs.create-bulk-employee', array_merge([
                                        'employee_id' => $payroll->payrollDetails->first()->employee_id,
                                        'period_start' => $payroll->period_start->format('Y-m-d'),
                                        'period_end' => $payroll->period_end->format('Y-m-d'),
                                        'payroll_id' => $payroll->id
                                    ], isset($schedule) ? ['schedule' => $schedule] : [])) }}" 
                                       class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        Manage DTR
                                    </a>
                                @elseif($payroll->payrollDetails->isNotEmpty() && $payroll->status !== 'draft')
                                    <span class="bg-gray-400 text-white font-bold py-2 px-4 rounded text-sm flex items-center cursor-not-allowed opacity-50">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                        DTR Locked
                                    </span>
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
                                        $timeLogData = $dtrData[$detail->employee_id][$date] ?? null;
                                        
                                        // Ensure we have a proper object
                                        if ($timeLogData) {
                                            if (is_array($timeLogData)) {
                                                // Convert array to object
                                                $timeLog = (object) $timeLogData;
                                            } elseif (is_object($timeLogData)) {
                                                $timeLog = $timeLogData;
                                            } else {
                                                $timeLog = null;
                                            }
                                        } else {
                                            $timeLog = null;
                                        }
                                        
                                        // Exclude incomplete records from hour calculation
                                        $isIncompleteRecord = $timeLog && (
                                            (isset($timeLog->remarks) && $timeLog->remarks === 'Incomplete Time Record') || 
                                            (!isset($timeLog->time_in) || !isset($timeLog->time_out) || !$timeLog->time_in || !$timeLog->time_out)
                                        );
                                        
                                        // For draft payrolls, use dynamic calculation; for approved payrolls, use stored values
                                        if (!$isIncompleteRecord && $timeLog && $payroll->status === 'draft') {
                                            // Use dynamic calculation for draft payrolls
                                            $regularHours = $timeLog->dynamic_regular_hours ?? ($timeLog->regular_hours ?? 0);
                                            $overtimeHours = $timeLog->dynamic_overtime_hours ?? ($timeLog->overtime_hours ?? 0);
                                        } else {
                                            // Use stored values for approved payrolls or incomplete records
                                            $regularHours = (!$isIncompleteRecord && $timeLog) ? ($timeLog->regular_hours ?? 0) : 0;
                                            $overtimeHours = (!$isIncompleteRecord && $timeLog) ? ($timeLog->overtime_hours ?? 0) : 0;
                                        }
                                        $totalEmployeeHours += $regularHours;
                                        $totalEmployeeOvertimeHours += $overtimeHours;
                                        $isWeekend = \Carbon\Carbon::parse($date)->isWeekend();
                                        
                                        // Get day type for indicator
                                        $dayType = 'Regular Day';
                                        $dayTypeColor = 'bg-green-100 text-green-800';
                                        
                                        if ($timeLog) {
                                            // Get the log_type to determine day type
                                            $logType = is_array($timeLog) ? ($timeLog['log_type'] ?? null) : ($timeLog->log_type ?? null);
                                            
                                            if ($logType) {
                                                // Map log_type to display names
                                                switch ($logType) {
                                                    case 'special_holiday':
                                                        $dayType = 'Special Holiday';
                                                        $dayTypeColor = 'bg-red-100 text-red-800';
                                                        break;
                                                    case 'regular_holiday':
                                                        $dayType = 'Regular Holiday';
                                                        $dayTypeColor = 'bg-red-100 text-red-800';
                                                        break;
                                                    case 'rest_day_regular_holiday':
                                                        $dayType = 'Rest + REG Holiday';
                                                        $dayTypeColor = 'bg-red-100 text-red-800';
                                                        break;
                                                    case 'rest_day_special_holiday':
                                                        $dayType = 'Rest + SPE Holiday';
                                                        $dayTypeColor = 'bg-red-100 text-red-800';
                                                        break;
                                                    case 'rest_day':
                                                        $dayType = 'Rest Day';
                                                        $dayTypeColor = 'bg-blue-100 text-blue-800';
                                                        break;
                                                    case 'regular_workday':
                                                    default:
                                                        $dayType = 'Regular Day';
                                                        $dayTypeColor = 'bg-green-100 text-green-800';
                                                        break;
                                                }
                                            } else {
                                                // Fallback: try to get rate configuration if log_type is null
                                                if (is_object($timeLog) && method_exists($timeLog, 'getRateConfiguration')) {
                                                    $rateConfig = $timeLog->getRateConfiguration();
                                                    if ($rateConfig) {
                                                        $dayType = $rateConfig->display_name;
                                                        // Set color based on type
                                                        if (str_contains($dayType, 'Holiday')) {
                                                            $dayTypeColor = 'bg-red-100 text-red-800';
                                                        } elseif (str_contains($dayType, 'Rest')) {
                                                            $dayTypeColor = 'bg-blue-100 text-blue-800';
                                                        }
                                                    }
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
                                            @php
                                                // Check different time log conditions
                                                $hasTimeIn = isset($timeLog->time_in) && $timeLog->time_in;
                                                $hasTimeOut = isset($timeLog->time_out) && $timeLog->time_out;
                                                $isMarkedIncomplete = isset($timeLog->remarks) && $timeLog->remarks === 'Incomplete Time Record';
                                                
                                                // Determine display logic:
                                                // N/A: Both time_in and time_out are missing/null
                                                // INC: Either time_in OR time_out is missing (but not both) OR explicitly marked as incomplete
                                                $showNA = !$hasTimeIn && !$hasTimeOut;
                                                $showINC = $isMarkedIncomplete || ($hasTimeIn && !$hasTimeOut) || (!$hasTimeIn && $hasTimeOut);
                                            @endphp
                                            
                                            @if($showNA)
                                                {{-- Display N/A when both time_in and time_out are missing --}}
                                                <div class="text-gray-600 font-bold">N/A</div>
                                            @elseif($showINC)
                                                {{-- Display INC for incomplete records (only one time missing or explicitly marked incomplete) --}}
                                                <div class="text-red-600 font-bold">INC</div>
                                            @else
                                                <div class="space-y-1">
                                                    {{-- Always show hours, even if 0 --}}
                                                    @if((isset($timeLog->time_in) && $timeLog->time_in) || (isset($timeLog->time_out) && $timeLog->time_out))
                                                    
                                                    {{-- Main work schedule with regular hours --}}
                                                    <div class="text-green-600 font-medium">
                                                        {{ $timeLog->time_in ? \Carbon\Carbon::parse($timeLog->time_in)->format('g:i A') : 'N/A' }} - {{ $timeLog->time_out ? \Carbon\Carbon::parse($timeLog->time_out)->format('g:i A') : 'N/A' }}
                                                        @if($regularHours > 0)
                                                    
                                                        @endif
                                                        ({{ number_format($regularHours, 1) }}h)
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
                                                
                                                    @if($overtimeHours > 0)
                                                    @php
                                                        // Get detailed time period breakdown
                                                        $timePeriodBreakdown = $timeLog->getTimePeriodBreakdown();
                                                        
                                                        // Get night differential breakdown for DTR display
                                                        $regularOvertimeHours = 0;
                                                        $nightDiffOvertimeHours = 0;
                                                        
                                                        if ($payroll->status === 'draft') {
                                                            $regularOvertimeHours = $timeLog->dynamic_regular_overtime_hours ?? 0;
                                                            $nightDiffOvertimeHours = $timeLog->dynamic_night_diff_overtime_hours ?? 0;
                                                        } else {
                                                            $regularOvertimeHours = $timeLog->regular_overtime_hours ?? 0;
                                                            $nightDiffOvertimeHours = $timeLog->night_diff_overtime_hours ?? 0;
                                                        }
                                                        
                                                        // If breakdown not available, show total
                                                        if ($regularOvertimeHours == 0 && $nightDiffOvertimeHours == 0) {
                                                            $regularOvertimeHours = $overtimeHours;
                                                        }
                                                    @endphp
                                                    
                                                    {{-- Display detailed time periods --}}
                                                    @foreach($timePeriodBreakdown as $period)
                                                        @if($period['type'] === 'regular_overtime' || $period['type'] === 'night_diff_overtime')
                                                        <div class="{{ $period['color_class'] }} text-xs">
                                                            {{ $period['start_time'] }} - {{ $period['end_time'] }}
                                                            @if($period['type'] === 'regular_overtime')
                                                                (regular ot period)
                                                            @elseif($period['type'] === 'night_diff_overtime')
                                                                (ot + nd period)
                                                            @endif
                                                        </div>
                                                        <div class="{{ $period['color_class'] }} text-xs">
                                                            @if($period['type'] === 'regular_overtime')
                                                                OT: {{ number_format($period['hours'], 1) }}h
                                                            @elseif($period['type'] === 'night_diff_overtime')
                                                                OT+ND: {{ number_format($period['hours'], 1) }}h
                                                            @endif
                                                        </div>
                                                        @endif
                                                    @endforeach
                                                    
                                                    {{-- Fallback to old display if no breakdown available --}}
                                                    @if(empty($timePeriodBreakdown) || count($timePeriodBreakdown) <= 1)
                                                        {{-- Always show the breakdown if we have the data --}}
                                                        @if($regularOvertimeHours > 0)
                                                        @php
                                                            // Calculate regular overtime period
                                                            $regularOTStart = '';
                                                            $regularOTEnd = '';
                                                            if ($timeLog->time_out && $timeLog->time_in) {
                                                                $workStart = \Carbon\Carbon::parse($timeLog->time_in);
                                                                $workEnd = \Carbon\Carbon::parse($timeLog->time_out);
                                                                // Show regular overtime as extending from regular hours
                                                                $regularOTStart = $workStart->copy()->addHours(8)->format('g:i A'); // Start after 8 regular hours
                                                                $regularOTEnd = $workStart->copy()->addHours(8)->addHours($regularOvertimeHours)->format('g:i A');
                                                            }
                                                        @endphp
                                                        @if($regularOTStart && $regularOTEnd)
                                                        <div class="text-orange-600 text-xs">
                                                            {{ $regularOTStart }} - {{ $regularOTEnd }} ({{ number_format($regularOvertimeHours, 1) }}h)
                                                        </div>
                                                        @endif
                                                     
                                                        @endif
                                                        
                                                        @if($nightDiffOvertimeHours > 0)
                                                        @php
                                                            // Calculate night diff overtime period
                                                            $nightOTStart = '';
                                                            $nightOTEnd = '';
                                                            if ($timeLog->time_out) {
                                                                $workEnd = \Carbon\Carbon::parse($timeLog->time_out);
                                                                // Night differential usually starts at 11 PM
                                                                $nightOTStart = $workEnd->copy()->subHours($nightDiffOvertimeHours)->format('g:i A');
                                                                $nightOTEnd = $workEnd->format('g:i A');
                                                            }
                                                        @endphp
                                                        @if($nightOTStart && $nightOTEnd)
                                                        <div class="text-purple-600 text-xs">
                                                            {{ $nightOTStart }} - {{ $nightOTEnd }} ({{ number_format($nightDiffOvertimeHours, 1) }}h)
                                                        </div>
                                                        @endif
                                                     
                                                        @endif
                                                        
                                                        {{-- If we have total overtime but no breakdown, show total --}}
                                                        @if($regularOvertimeHours == 0 && $nightDiffOvertimeHours == 0 && $overtimeHours > 0)
                                                        @php
                                                            // Calculate overtime period (simplified display logic)
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
                                                            {{ $overtimeStart }} - {{ $overtimeEnd }} (regular ot period)
                                                        </div>
                                                        @endif
                                                        <div class="text-orange-600 text-xs">
                                                            OT: {{ number_format($overtimeHours, 1) }}h
                                                        </div>
                                                        @endif
                                                    @endif
                                                    @endif
                                                </div>
                                            @endif
                                        @else
                                            {{-- Display N/A when there's no record from database --}}
                                            <div class="text-gray-600 font-bold">N/A</div>
                                        @endif
                                    </td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
