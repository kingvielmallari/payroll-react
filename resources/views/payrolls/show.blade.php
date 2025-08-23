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
        <div class="max-w-9xl mx-auto sm:px-6 lg:px-8 space-y-6">
               
            <!-- Payroll Summary -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-row gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg flex-1 h-20 flex flex-col justify-center text-center">
                            @php
                                $totalBasicPay = 0; // This will be updated by JavaScript to match Basic column exactly
                            @endphp
                            <div class="text-2xl font-bold text-blue-600" id="totalBasicDisplay">₱{{ number_format($totalBasicPay, 2) }}</div>
                            <div class="text-sm text-blue-800">Total Basic</div>
                        </div>
                        <div class="bg-yellow-50 p-4 rounded-lg flex-1 h-20 flex flex-col justify-center text-center">
                            @php
                                $totalHolidayPay = 0; // This will be updated by JavaScript to match Holiday column exactly
                            @endphp
                            <div class="text-2xl font-bold text-yellow-600" id="totalHolidayDisplay">₱{{ number_format($totalHolidayPay, 2) }}</div>
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
                                $totalGrossPay = 0; // This will be updated by JavaScript to match Gross Pay column exactly
                            @endphp
                            <div class="text-2xl font-bold text-green-600" id="totalGrossDisplay">₱{{ number_format($totalGrossPay, 2) }}</div>
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
                                            
                                            // Calculate DYNAMIC allowances (same logic as Allowances column)
                                            $allowances = 0;
                                            if (isset($allowanceSettings) && $allowanceSettings->isNotEmpty()) {
                                                foreach($allowanceSettings as $allowanceSetting) {
                                                    $calculatedAllowanceAmount = 0;
                                                    if($allowanceSetting->calculation_type === 'percentage') {
                                                        $calculatedAllowanceAmount = ($basicPay * $allowanceSetting->rate_percentage) / 100;
                                                    } elseif($allowanceSetting->calculation_type === 'fixed_amount') {
                                                        $calculatedAllowanceAmount = $allowanceSetting->fixed_amount;
                                                        
                                                        // Apply frequency-based calculation for daily allowances
                                                        if ($allowanceSetting->frequency === 'daily') {
                                                            // Calculate actual working days for this employee
                                                            $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
                                                            $workingDays = 0;
                                                            
                                                            // Count working days from DTR data
                                                            if (isset($employeeBreakdown['regular_workday'])) {
                                                                $regularBreakdown = $employeeBreakdown['regular_workday'];
                                                                $workingDays += ($regularBreakdown['regular_hours'] ?? 0) > 0 ? 1 : 0;
                                                            }
                                                            if (isset($employeeBreakdown['special_holiday'])) {
                                                                $specialBreakdown = $employeeBreakdown['special_holiday'];
                                                                $workingDays += ($specialBreakdown['regular_hours'] ?? 0) > 0 ? 1 : 0;
                                                            }
                                                            if (isset($employeeBreakdown['regular_holiday'])) {
                                                                $regularHolidayBreakdown = $employeeBreakdown['regular_holiday'];
                                                                $workingDays += ($regularHolidayBreakdown['regular_hours'] ?? 0) > 0 ? 1 : 0;
                                                            }
                                                            if (isset($employeeBreakdown['rest_day'])) {
                                                                $restBreakdown = $employeeBreakdown['rest_day'];
                                                                $workingDays += ($restBreakdown['regular_hours'] ?? 0) > 0 ? 1 : 0;
                                                            }
                                                            
                                                            // Apply max days limit if set
                                                            $maxDays = $allowanceSetting->max_days_per_period ?? $workingDays;
                                                            $applicableDays = min($workingDays, $maxDays);
                                                            
                                                            $calculatedAllowanceAmount = $allowanceSetting->fixed_amount * $applicableDays;
                                                        }
                                                    } elseif($allowanceSetting->calculation_type === 'daily_rate_multiplier') {
                                                        $dailyRate = $detail->employee->daily_rate ?? 0;
                                                        $multiplier = $allowanceSetting->multiplier ?? 1;
                                                        $calculatedAllowanceAmount = $dailyRate * $multiplier;
                                                    }
                                                    
                                                    // Apply minimum and maximum limits
                                                    if ($allowanceSetting->minimum_amount && $calculatedAllowanceAmount < $allowanceSetting->minimum_amount) {
                                                        $calculatedAllowanceAmount = $allowanceSetting->minimum_amount;
                                                    }
                                                    if ($allowanceSetting->maximum_amount && $calculatedAllowanceAmount > $allowanceSetting->maximum_amount) {
                                                        $calculatedAllowanceAmount = $allowanceSetting->maximum_amount;
                                                    }
                                                    
                                                    $allowances += $calculatedAllowanceAmount;
                                                }
                                            } else {
                                                // Fallback to stored value if no active settings
                                                $allowances = $detail->allowances ?? 0;
                                            }
                                            
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
                                $correctNetPay = 0; // This will be updated by JavaScript to match Net Pay column exactly
                            @endphp
                            <div class="text-2xl font-bold text-purple-600" id="totalNetDisplay">₱{{ number_format($correctNetPay, 2) }}</div>
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
                                @php
                                    // Fetch snapshot data once for this employee (for processing/locked payrolls)
                                    $employeeSnapshot = null;
                                    if ($payroll->status !== 'draft') {
                                        $employeeSnapshot = \App\Models\PayrollSnapshot::where('payroll_id', $payroll->id)
                                                                                      ->where('employee_id', $detail->employee_id)
                                                                                      ->first();
                                    }
                                @endphp
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
                                            
                                            // For draft mode, calculate basic pay from actual DTR data
                                            if ($payroll->status === 'draft') {
                                                $basicPay = 0; // Calculate from actual DTR data
                                            } else {
                                                $basicPay = $payBreakdown['basic_pay'];
                                            }
                                            
                                            // Get breakdown data
                                            $basicBreakdownData = [];
                                            $basicRegularHours = 0;
                                            
                                            if ($payroll->status === 'draft') {
                                                // DRAFT: Use ACTUAL DTR hours instead of timeBreakdowns aggregated data
                                                $actualRegularWorkdayHours = 0;
                                                
                                                // Calculate actual regular workday hours from DTR data
                                                if (isset($dtrData[$detail->employee_id])) {
                                                    foreach ($dtrData[$detail->employee_id] as $date => $timeLogData) {
                                                        if ($timeLogData) {
                                                            $timeLog = is_array($timeLogData) ? (object) $timeLogData : $timeLogData;
                                                            
                                                            // Check if this is a regular workday and has valid hours
                                                            $logType = $timeLog->log_type ?? null;
                                                            if ($logType === 'regular_workday' && isset($timeLog->regular_hours) && $timeLog->regular_hours > 0) {
                                                                // Use dynamic hours for draft mode, stored hours for processing mode
                                                                $actualHours = isset($timeLog->dynamic_regular_hours) ? $timeLog->dynamic_regular_hours : $timeLog->regular_hours;
                                                                $actualRegularWorkdayHours += $actualHours;
                                                            }
                                                        }
                                                    }
                                                }
                                                
                                                // Use actual DTR hours for calculation
                                                if ($actualRegularWorkdayHours > 0) {
                                                    $hourlyRate = $detail->employee->hourly_rate ?? 0;
                                                    
                                                    // Convert hours to minutes for precise calculation
                                                    $actualMinutes = $actualRegularWorkdayHours * 60;
                                                    
                                                    // Round to nearest minute for payroll accuracy
                                                    $roundedMinutes = round($actualMinutes);
                                                    
                                                    $ratePerMinute = $hourlyRate / 60;
                                                    $regularAmount = $roundedMinutes * $ratePerMinute;
                                                    
                                                    // // Debug: Show exact calculation
                                                    // $debugInfo = "Debug: {$actualRegularWorkdayHours}h = {$actualMinutes}min → {$roundedMinutes}min × ₱{$ratePerMinute}/min = ₱{$regularAmount}";
                                                    
                                                    $basicBreakdownData['Regular Workday'] = [
                                                        'hours' => $actualRegularWorkdayHours,
                                                        'rate' => $hourlyRate,
                                                        'amount' => $regularAmount,
                                                        // 'debug' => $debugInfo
                                                    ];
                                                    $basicRegularHours = $actualRegularWorkdayHours;
                                                    $basicPay = $regularAmount; // Use calculated amount
                                                }
                                            } else {
                                                // PROCESSING/APPROVED: Use breakdown data from snapshot
                                                $basicBreakdownData = [];
                                                if ($employeeSnapshot && $employeeSnapshot->basic_breakdown) {
                                                    $basicBreakdownData = is_string($employeeSnapshot->basic_breakdown) 
                                                        ? json_decode($employeeSnapshot->basic_breakdown, true) 
                                                        : $employeeSnapshot->basic_breakdown;
                                                }
                                                $basicRegularHours = array_sum(array_column($basicBreakdownData, 'hours'));
                                                $basicPay = $detail->regular_pay ?? 0; // Use snapshot basic pay amount
                                            }
                                        @endphp
                                        
                                        <div>
                                            @if(!empty($basicBreakdownData))
                                                <!-- Show Basic Pay breakdown -->
                                                @foreach($basicBreakdownData as $type => $data)
                                                    <div class="text-xs text-gray-500 mb-1">
                                                        <span>{{ $type }}: {{ number_format($data['hours'], 2) }}h</span>
                                                        <div class="text-xs text-gray-600">
                                                            ₱{{ number_format($data['rate'] ?? 0, 2) }}/hr = ₱{{ number_format($data['amount'] ?? 0, 2) }}
                                                        </div>
                                                        @if(isset($data['debug']))
                                                            <div class="text-xs text-red-500">{{ $data['debug'] }}</div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                                <div class="text-xs border-t pt-1">
                                                    <div class="text-gray-500">Total: {{ number_format($basicRegularHours, 2) }} hrs</div>
                                                </div>
                                            @else
                                                <div class="text-xs text-gray-500">
                                                    <div class="text-gray-500">Total: {{ number_format($basicRegularHours, 2) }} hrs</div>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="font-bold text-blue-600 basic-pay-amount" data-basic-amount="{{ $basicPay }}">₱{{ number_format($basicPay, 2) }}</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                                        @php 
                                            $holidayBreakdown = [];
                                            $totalHolidayRegularHours = 0;
                                            $holidayPay = 0; // Calculate this properly
                                            
                                            if ($payroll->status === 'draft') {
                                                // DRAFT: Calculate holiday breakdown using ACTUAL DTR hours, not aggregated breakdown
                                                $actualHolidayHours = 0;
                                                
                                                // Calculate actual holiday hours from DTR data
                                                if (isset($dtrData[$detail->employee_id])) {
                                                    foreach ($dtrData[$detail->employee_id] as $date => $timeLogData) {
                                                        if ($timeLogData) {
                                                            $timeLog = is_array($timeLogData) ? (object) $timeLogData : $timeLogData;
                                                            
                                                            // Check if this is a holiday and has valid hours
                                                            $logType = $timeLog->log_type ?? null;
                                                            if (in_array($logType, ['special_holiday', 'regular_holiday', 'rest_day_regular_holiday', 'rest_day_special_holiday']) && isset($timeLog->regular_hours) && $timeLog->regular_hours > 0) {
                                                                // Get rate config for this specific holiday type
                                                                $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
                                                                if (isset($employeeBreakdown[$logType])) {
                                                                    $breakdown = $employeeBreakdown[$logType];
                                                                    $rateConfig = $breakdown['rate_config'];
                                                                    $displayName = $rateConfig ? $rateConfig->display_name : 'Holiday';
                                                                    $hourlyRate = $detail->employee->hourly_rate ?? 0;
                                                                    $regularMultiplier = $rateConfig ? $rateConfig->regular_rate_multiplier : 1.3;
                                                                    
                                                                    // Convert hours to minutes for precise calculation
                                                                    // Use dynamic hours for draft mode, stored hours for processing mode
                                                                    $actualHours = isset($timeLog->dynamic_regular_hours) ? $timeLog->dynamic_regular_hours : $timeLog->regular_hours;
                                                                    $actualMinutes = $actualHours * 60;
                                                                    
                                                                    // Round to nearest minute for payroll accuracy
                                                                    $roundedMinutes = round($actualMinutes);
                                                                    
                                                                    $ratePerMinute = ($hourlyRate * $regularMultiplier) / 60;
                                                                    $regularAmount = $roundedMinutes * $ratePerMinute;
                                                                    
                                                                    $percentageDisplay = number_format($regularMultiplier * 100, 0) . '%';
                                                                    
                                                                    if (isset($holidayBreakdown[$displayName])) {
                                                                        $holidayBreakdown[$displayName]['hours'] += $actualHours;
                                                                        $holidayBreakdown[$displayName]['amount'] += $regularAmount;
                                                                    } else {
                                                                        $holidayBreakdown[$displayName] = [
                                                                            'hours' => $actualHours,
                                                                            'amount' => $regularAmount,
                                                                            'rate' => $hourlyRate * $regularMultiplier,
                                                                            'percentage' => $percentageDisplay
                                                                        ];
                                                                    }
                                                                    $totalHolidayRegularHours += $actualHours;
                                                                    $holidayPay += $regularAmount; // Sum up all amounts
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            } else {
                                                // PROCESSING/APPROVED: Use breakdown data from snapshot
                                                $holidayBreakdownData = [];
                                                if ($employeeSnapshot && $employeeSnapshot->holiday_breakdown) {
                                                    $holidayBreakdownData = is_string($employeeSnapshot->holiday_breakdown) 
                                                        ? json_decode($employeeSnapshot->holiday_breakdown, true) 
                                                        : $employeeSnapshot->holiday_breakdown;
                                                    
                                                    foreach ($holidayBreakdownData as $type => $data) {
                                                        $holidayBreakdown[$type] = [
                                                            'hours' => $data['hours'],
                                                            'amount' => $data['amount'],
                                                            'rate' => $data['rate'],
                                                            'percentage' => number_format($data['multiplier'] * 100, 0) . '%'
                                                        ];
                                                        $totalHolidayRegularHours += $data['hours'];
                                                        $holidayPay += $data['amount']; // Sum up amounts from snapshot
                                                    }
                                                }
                                            }
                                        @endphp
                                        
                                        <div>
                                            @if(!empty($holidayBreakdown))
                                                <!-- Show individual holiday type breakdowns -->
                                                @foreach($holidayBreakdown as $type => $data)
                                                    <div class="text-xs text-gray-500 mb-1">
                                                        <span>{{ $type }}: {{ number_format($data['hours'], 2) }}h</span>
                                                        <div class="text-xs text-gray-600">
                                                            {{ $data['percentage'] }} = ₱{{ number_format($data['amount'], 2) }}
                                                        </div>
                                                    </div>
                                                @endforeach
                                                
                                                <div class="text-xs border-t pt-1">
                                                    <div class="text-gray-500">Total: {{ number_format($totalHolidayRegularHours, 2) }} hrs</div>
                                                </div>
                                            @else
                                                <div class="text-gray-400">0 hrs</div>
                                            @endif
                                        </div>
                                        <div class="font-bold text-yellow-600 holiday-pay-amount" data-holiday-amount="{{ $holidayPay }}">₱{{ number_format($holidayPay, 2) }}</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                                        @php 
                                            $restDayBreakdown = [];
                                            $totalRestRegularHours = 0;
                                            $restDayPay = 0; // Calculate this properly
                                            
                                            if ($payroll->status === 'draft') {
                                                // DRAFT: Calculate rest day breakdown using ACTUAL DTR hours, not aggregated breakdown
                                                $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
                                                $actualRestDayHours = 0;
                                                
                                                // Calculate actual rest day hours from DTR data
                                                if (isset($dtrData[$detail->employee_id])) {
                                                    foreach ($dtrData[$detail->employee_id] as $date => $timeLogData) {
                                                        if ($timeLogData) {
                                                            $timeLog = is_array($timeLogData) ? (object) $timeLogData : $timeLogData;
                                                            
                                                            // Check if this is a rest day and has valid hours
                                                            $logType = $timeLog->log_type ?? null;
                                                            if ($logType === 'rest_day' && isset($timeLog->regular_hours) && $timeLog->regular_hours > 0) {
                                                                // Use dynamic hours for draft mode, stored hours for processing mode
                                                                $actualHours = isset($timeLog->dynamic_regular_hours) ? $timeLog->dynamic_regular_hours : $timeLog->regular_hours;
                                                                $actualRestDayHours += $actualHours;
                                                            }
                                                        }
                                                    }
                                                }
                                                
                                                // If we found actual DTR hours, use them
                                                if ($actualRestDayHours > 0) {
                                                    $hourlyRate = $detail->employee->hourly_rate ?? 0;
                                                    
                                                    // Get the rest day rate config for multiplier
                                                    $rateConfig = null;
                                                    if (isset($employeeBreakdown['rest_day']['rate_config'])) {
                                                        $rateConfig = $employeeBreakdown['rest_day']['rate_config'];
                                                    }
                                                    
                                                    $regularMultiplier = $rateConfig ? $rateConfig->regular_rate_multiplier : 1.2; // Default to 120%
                                                    
                                                    // Convert hours to minutes for precise calculation
                                                    $actualMinutes = $actualRestDayHours * 60;
                                                    
                                                    // Round to nearest minute for payroll accuracy
                                                    $roundedMinutes = round($actualMinutes);
                                                    
                                                    $ratePerMinute = ($hourlyRate * $regularMultiplier) / 60;
                                                    $regularAmount = $roundedMinutes * $ratePerMinute;
                                                    
                                                    $percentageDisplay = number_format($regularMultiplier * 100, 0) . '%';
                                                    
                                                    $restDayBreakdown['Rest Day'] = [
                                                        'hours' => $actualRestDayHours,
                                                        'amount' => $regularAmount,
                                                        'rate' => $hourlyRate * $regularMultiplier,
                                                        'percentage' => $percentageDisplay
                                                    ];
                                                    $totalRestRegularHours = $actualRestDayHours;
                                                    $restDayPay = $regularAmount; // Use calculated amount
                                                } else {
                                                    // Fallback to breakdown calculation if no DTR data
                                                    $restDayTypes = ['rest_day'];
                                                    foreach ($restDayTypes as $type) {
                                                        if (isset($employeeBreakdown[$type])) {
                                                            $breakdown = $employeeBreakdown[$type];
                                                            $rateConfig = $breakdown['rate_config'];
                                                            $displayName = $rateConfig ? $rateConfig->display_name : 'Rest Day';
                                                            $regularHours = $breakdown['regular_hours']; // ONLY regular hours
                                                            
                                                            if ($regularHours > 0) {
                                                                $hourlyRate = $detail->employee->hourly_rate ?? 0;
                                                                $regularMultiplier = $rateConfig ? $rateConfig->regular_rate_multiplier : 1.2;
                                                                
                                                                // Convert hours to minutes for precise calculation
                                                                $actualMinutes = $regularHours * 60;
                                                                
                                                                // Round to nearest minute for payroll accuracy
                                                                $roundedMinutes = round($actualMinutes);
                                                                
                                                                $ratePerMinute = ($hourlyRate * $regularMultiplier) / 60;
                                                                $regularAmount = $roundedMinutes * $ratePerMinute;
                                                                
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
                                                                $restDayPay += $regularAmount; // Sum up all amounts
                                                            }
                                                        }
                                                    }
                                                }
                                                
                                                // For both draft and processing, prioritize PayrollDetail stored hours if available
                                                if (empty($restDayBreakdown) && isset($detail->rest_day_hours) && $detail->rest_day_hours > 0) {
                                                    $totalRestRegularHours = $detail->rest_day_hours;
                                                }
                                            } else {
                                                // PROCESSING/APPROVED: Use breakdown data from snapshot
                                                $restBreakdownData = [];
                                                if ($employeeSnapshot && $employeeSnapshot->rest_breakdown) {
                                                    $restBreakdownData = is_string($employeeSnapshot->rest_breakdown) 
                                                        ? json_decode($employeeSnapshot->rest_breakdown, true) 
                                                        : $employeeSnapshot->rest_breakdown;
                                                    
                                                    foreach ($restBreakdownData as $type => $data) {
                                                        $restDayBreakdown[$type] = [
                                                            'hours' => $data['hours'],
                                                            'amount' => $data['amount'],
                                                            'rate' => $data['rate'],
                                                            'percentage' => number_format($data['multiplier'] * 100, 0) . '%'
                                                        ];
                                                        $totalRestRegularHours += $data['hours'];
                                                        $restDayPay += $data['amount']; // Sum up amounts from snapshot
                                                    }
                                                }
                                            }
                                        @endphp
                                        
                                        <div>
                                            @if(!empty($restDayBreakdown))
                                                <!-- Show individual rest day type breakdowns -->
                                                @foreach($restDayBreakdown as $type => $data)
                                                    <div class="text-xs text-gray-500 mb-1">
                                                       
                                                            <span>{{ $type }}: {{ number_format($data['hours'], 2) }}h</span>
                                                     
                                                        <div class="text-xs text-gray-600">
                                                            {{ $data['percentage'] }} = ₱{{ number_format($data['amount'], 2) }}
                                                        </div>
                                                    </div>
                                                @endforeach
                                                
                                                <div class="text-xs border-t pt-1">
                                                    <div class="text-gray-500">Total: {{ number_format($totalRestRegularHours, 2) }} hrs</div>
                                                    <div class="font-bold text-cyan-600">₱{{ number_format($restDayPay, 2) }}</div>
                                                </div>
                                            @else
                                                @if($totalRestRegularHours > 0)
                                                    <div class="text-xs text-gray-500">{{ number_format($totalRestRegularHours, 2) }} hrs</div>
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
                                            $totalOvertimeHours = 0;
                                            $overtimeBreakdown = [];
                                            
                                            if ($payroll->status === 'draft') {
                                                // DRAFT: Calculate overtime breakdown dynamically with CORRECT amounts
                                                $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
                                                $hourlyRate = $detail->employee->hourly_rate ?? 0;
                                                $calculatedOvertimeTotal = 0;
                                                
                                                // Regular workday overtime
                                                if (isset($employeeBreakdown['regular_workday']) && $employeeBreakdown['regular_workday']['overtime_hours'] > 0) {
                                                    $hours = $employeeBreakdown['regular_workday']['overtime_hours'];
                                                    $rateConfig = $employeeBreakdown['regular_workday']['rate_config'];
                                                    $multiplier = $rateConfig ? ($rateConfig->overtime_rate_multiplier ?? 1.25) : 1.25;
                                                    
                                                    // Apply per-minute calculation for overtime (same as Basic/Holiday/Rest)
                                                    $overtimeHourlyRate = $hourlyRate * $multiplier; // 300 * 1.4 = 420/hr
                                                    $overtimeMinutes = $hours * 60; // Convert to minutes
                                                    $roundedOvertimeMinutes = round($overtimeMinutes); // Round to nearest minute
                                                    $overtimeRatePerMinute = $overtimeHourlyRate / 60; // 420/hr = 7/min
                                                    $amount = $roundedOvertimeMinutes * $overtimeRatePerMinute; // 5min * 7/min = 35
                                                    
                                                    $overtimeBreakdown[] = [
                                                        'name' => 'Regular Workday OT',
                                                        'hours' => $hours,
                                                        'amount' => $amount,
                                                        'percentage' => number_format($multiplier * 100, 0) . '%'
                                                    ];
                                                    $totalOvertimeHours += $hours;
                                                    $calculatedOvertimeTotal += $amount;
                                                }
                                                
                                                // Special holiday overtime
                                                if (isset($employeeBreakdown['special_holiday']) && $employeeBreakdown['special_holiday']['overtime_hours'] > 0) {
                                                    $hours = $employeeBreakdown['special_holiday']['overtime_hours'];
                                                    $rateConfig = $employeeBreakdown['special_holiday']['rate_config'];
                                                    $multiplier = $rateConfig ? ($rateConfig->overtime_rate_multiplier ?? 1.69) : 1.69;
                                                    
                                                    // Apply per-minute calculation for overtime (same as Basic/Holiday/Rest)
                                                    $overtimeHourlyRate = $hourlyRate * $multiplier;
                                                    $overtimeMinutes = $hours * 60; // Convert to minutes
                                                    $roundedOvertimeMinutes = round($overtimeMinutes); // Round to nearest minute
                                                    $overtimeRatePerMinute = $overtimeHourlyRate / 60;
                                                    $amount = $roundedOvertimeMinutes * $overtimeRatePerMinute;
                                                    
                                                    $overtimeBreakdown[] = [
                                                        'name' => 'Special Holiday OT',
                                                        'hours' => $hours,
                                                        'amount' => $amount,
                                                        'percentage' => number_format($multiplier * 100, 0) . '%'
                                                    ];
                                                    $totalOvertimeHours += $hours;
                                                    $calculatedOvertimeTotal += $amount;
                                                }
                                                
                                                // Regular holiday overtime  
                                                if (isset($employeeBreakdown['regular_holiday']) && $employeeBreakdown['regular_holiday']['overtime_hours'] > 0) {
                                                    $hours = $employeeBreakdown['regular_holiday']['overtime_hours'];
                                                    $rateConfig = $employeeBreakdown['regular_holiday']['rate_config'];
                                                    $multiplier = $rateConfig ? ($rateConfig->overtime_rate_multiplier ?? 2.6) : 2.6;
                                                    
                                                    // Apply per-minute calculation for overtime (same as Basic/Holiday/Rest)
                                                    $overtimeHourlyRate = $hourlyRate * $multiplier;
                                                    $overtimeMinutes = $hours * 60; // Convert to minutes
                                                    $roundedOvertimeMinutes = round($overtimeMinutes); // Round to nearest minute
                                                    $overtimeRatePerMinute = $overtimeHourlyRate / 60;
                                                    $amount = $roundedOvertimeMinutes * $overtimeRatePerMinute;
                                                    
                                                    $overtimeBreakdown[] = [
                                                        'name' => 'Regular Holiday OT',
                                                        'hours' => $hours,
                                                        'amount' => $amount,
                                                        'percentage' => number_format($multiplier * 100, 0) . '%'
                                                    ];
                                                    $totalOvertimeHours += $hours;
                                                    $calculatedOvertimeTotal += $amount;
                                                }
                                                
                                                // Rest day overtime
                                                if (isset($employeeBreakdown['rest_day']) && $employeeBreakdown['rest_day']['overtime_hours'] > 0) {
                                                    $hours = $employeeBreakdown['rest_day']['overtime_hours'];
                                                    $rateConfig = $employeeBreakdown['rest_day']['rate_config'];
                                                    $multiplier = $rateConfig ? ($rateConfig->overtime_rate_multiplier ?? 1.69) : 1.69;
                                                    
                                                    // Apply per-minute calculation for overtime (same as Basic/Holiday/Rest)
                                                    $overtimeHourlyRate = $hourlyRate * $multiplier;
                                                    $overtimeMinutes = $hours * 60; // Convert to minutes
                                                    $roundedOvertimeMinutes = round($overtimeMinutes); // Round to nearest minute
                                                    $overtimeRatePerMinute = $overtimeHourlyRate / 60;
                                                    $amount = $roundedOvertimeMinutes * $overtimeRatePerMinute;
                                                    
                                                    $overtimeBreakdown[] = [
                                                        'name' => 'Rest Day OT',
                                                        'hours' => $hours,
                                                        'amount' => $amount,
                                                        'percentage' => number_format($multiplier * 100, 0) . '%'
                                                    ];
                                                    $totalOvertimeHours += $hours;
                                                    $calculatedOvertimeTotal += $amount;
                                                }
                                                
                                                // Override the backend overtime pay with our correct calculation for display
                                                $overtimePay = $calculatedOvertimeTotal;
                                            } else {
                                                // PROCESSING/APPROVED: Use breakdown data from snapshot - NO CALCULATION!
                                                $overtimeBreakdownData = [];
                                                if ($employeeSnapshot && $employeeSnapshot->overtime_breakdown) {
                                                    $overtimeBreakdownData = is_string($employeeSnapshot->overtime_breakdown) 
                                                        ? json_decode($employeeSnapshot->overtime_breakdown, true) 
                                                        : $employeeSnapshot->overtime_breakdown;
                                                    
                                                    foreach ($overtimeBreakdownData as $type => $data) {
                                                        $overtimeBreakdown[] = [
                                                            'name' => $type,
                                                            'hours' => $data['hours'],
                                                            'amount' => $data['amount'],
                                                            'percentage' => number_format($data['multiplier'] * 100, 0) . '%'
                                                        ];
                                                        $totalOvertimeHours += $data['hours'];
                                                    }
                                                    // Use the total from snapshots
                                                    $overtimePay = array_sum(array_column($overtimeBreakdown, 'amount'));
                                                } else {
                                                    // Fallback to the simple overtime pay from snapshot
                                                    $overtimePay = $detail->overtime_pay ?? 0;
                                                }
                                            }
                                        @endphp
                                        
                                        <div>
                                            @if(!empty($overtimeBreakdown))
                                                @foreach($overtimeBreakdown as $ot)
                                                    <div class="text-xs text-gray-500 mb-1">
                                                        <span>{{ $ot['name'] }}: {{ number_format($ot['hours'], 2) }}h</span>
                                                        <div class="text-xs text-gray-600">
                                                            {{ $ot['percentage'] }} = ₱{{ number_format($ot['amount'], 2) }}
                                                        </div>
                                                    </div>
                                                @endforeach
                                                
                                                <div class="text-xs border-t pt-1">
                                                    <div class="text-gray-500">Total: {{ number_format($totalOvertimeHours, 2) }} hrs</div>
                                                </div>
                                            @else
                                                <div class="text-gray-400">0 hrs</div>
                                            @endif
                                        </div>
                                        <div class="font-bold text-orange-600">₱{{ number_format($overtimePay, 2) }}</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                                        <div class="space-y-1">
                                            @php
                                                // Always calculate allowances dynamically in DRAFT mode
                                                $dynamicAllowancesTotal = 0;
                                                $allowanceBreakdownDisplay = [];
                                            @endphp
                                            
                                            @if(isset($isDynamic) && $isDynamic && $allowanceSettings->isNotEmpty())
                                                <!-- DRAFT MODE: Show Dynamic Calculations -->
                                                @foreach($allowanceSettings as $setting)
                                                    @php
                                                        // Calculate actual amount for display based on setting configuration
                                                        $displayAmount = 0;
                                                        if($setting->calculation_type === 'percentage') {
                                                            $basicPay = $payBreakdownByEmployee[$detail->employee_id]['basic_pay'] ?? $detail->regular_pay ?? 0;
                                                            $displayAmount = ($basicPay * $setting->rate_percentage) / 100;
                                                        } elseif($setting->calculation_type === 'fixed_amount') {
                                                            $displayAmount = $setting->fixed_amount;
                                                            
                                                            // Apply frequency-based calculation for daily allowances
                                                            if ($setting->frequency === 'daily') {
                                                                // Calculate actual working days for this employee in this period
                                                                $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
                                                                $workingDays = 0;
                                                                
                                                                // Count working days from DTR data
                                                                if (isset($employeeBreakdown['regular_workday'])) {
                                                                    $regularBreakdown = $employeeBreakdown['regular_workday'];
                                                                    $workingDays += ($regularBreakdown['regular_hours'] ?? 0) > 0 ? 1 : 0;
                                                                }
                                                                if (isset($employeeBreakdown['special_holiday'])) {
                                                                    $specialBreakdown = $employeeBreakdown['special_holiday'];
                                                                    $workingDays += ($specialBreakdown['regular_hours'] ?? 0) > 0 ? 1 : 0;
                                                                }
                                                                if (isset($employeeBreakdown['regular_holiday'])) {
                                                                    $regularHolidayBreakdown = $employeeBreakdown['regular_holiday'];
                                                                    $workingDays += ($regularHolidayBreakdown['regular_hours'] ?? 0) > 0 ? 1 : 0;
                                                                }
                                                                if (isset($employeeBreakdown['rest_day'])) {
                                                                    $restBreakdown = $employeeBreakdown['rest_day'];
                                                                    $workingDays += ($restBreakdown['regular_hours'] ?? 0) > 0 ? 1 : 0;
                                                                }
                                                                
                                                                // Apply max days limit if set
                                                                $maxDays = $setting->max_days_per_period ?? $workingDays;
                                                                $applicableDays = min($workingDays, $maxDays);
                                                                
                                                                $displayAmount = $setting->fixed_amount * $applicableDays;
                                                            }
                                                        } elseif($setting->calculation_type === 'daily_rate_multiplier') {
                                                            $dailyRate = $detail->employee->daily_rate ?? 0;
                                                            $multiplier = $setting->multiplier ?? 1;
                                                            $displayAmount = $dailyRate * $multiplier;
                                                        }
                                                        
                                                        // Apply minimum and maximum limits
                                                        if ($setting->minimum_amount && $displayAmount < $setting->minimum_amount) {
                                                            $displayAmount = $setting->minimum_amount;
                                                        }
                                                        if ($setting->maximum_amount && $displayAmount > $setting->maximum_amount) {
                                                            $displayAmount = $setting->maximum_amount;
                                                        }
                                                        
                                                        // Add to breakdown and total
                                                        if ($displayAmount > 0) {
                                                            $allowanceBreakdownDisplay[] = [
                                                                'name' => $setting->name,
                                                                'amount' => $displayAmount
                                                            ];
                                                            $dynamicAllowancesTotal += $displayAmount;
                                                        }
                                                    @endphp
                                                @endforeach
                                                
                                                <!-- Display breakdown -->
                                                @foreach($allowanceBreakdownDisplay as $item)
                                                    <div class="text-xs text-gray-500">
                                                        <span>{{ $item['name'] }}:</span>
                                                        <span>₱{{ number_format($item['amount'], 2) }}</span>
                                                    </div>
                                                @endforeach
                                                
                                                <!-- Display dynamic total -->
                                                <div class="font-bold text-green-600">
                                                    ₱{{ number_format($dynamicAllowancesTotal, 2) }}
                                                </div>
                                                <div class="text-xs text-green-500">
                                                    <span class="inline-flex items-center">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                        </svg>
                                                        Current settings
                                                    </span>
                                                </div>
                                            @elseif($detail->allowances > 0)
                                                <!-- PROCESSING/APPROVED: Show Breakdown from Snapshot -->
                                                @php
                                                    // Get allowances breakdown from the fetched snapshot
                                                    $allowancesBreakdown = [];
                                                    if ($employeeSnapshot && $employeeSnapshot->allowances_breakdown) {
                                                        $allowancesBreakdown = is_string($employeeSnapshot->allowances_breakdown) 
                                                            ? json_decode($employeeSnapshot->allowances_breakdown, true) 
                                                            : $employeeSnapshot->allowances_breakdown;
                                                    }
                                                @endphp
                                                
                                                @if(!empty($allowancesBreakdown))
                                                    @foreach($allowancesBreakdown as $allowance)
                                                        @if(isset($allowance['amount']) && $allowance['amount'] > 0)
                                                            <div class="text-xs text-gray-500">
                                                                <span>{{ $allowance['name'] ?? $allowance['code'] }}:</span>
                                                                <span>₱{{ number_format($allowance['amount'], 2) }}</span>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                @endif
                                                
                                                <div class="font-bold text-green-600">
                                                    ₱{{ number_format($detail->allowances, 2) }}
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    <span class="inline-flex items-center">
                                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                                        </svg>
                                                        Locked snapshot
                                                    </span>
                                                </div>
                                            @else
                                                <!-- No allowances -->
                                                <div class="text-gray-400">₱0.00</div>
                                            @endif
                                        </div>
                                    </td>
                                    <!-- Bonuses Column -->
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                                        <div class="space-y-1">
                                            @if($detail->bonuses > 0)
                                                <!-- PROCESSING/APPROVED: Show Bonus Breakdown from Snapshot -->
                                                @php
                                                    // Get bonuses breakdown from the fetched snapshot
                                                    $bonusesBreakdown = [];
                                                    if ($employeeSnapshot && $employeeSnapshot->bonuses_breakdown) {
                                                        $bonusesBreakdown = is_string($employeeSnapshot->bonuses_breakdown) 
                                                            ? json_decode($employeeSnapshot->bonuses_breakdown, true) 
                                                            : $employeeSnapshot->bonuses_breakdown;
                                                    }
                                                @endphp
                                                
                                                @if(!empty($bonusesBreakdown))
                                                    @foreach($bonusesBreakdown as $bonus)
                                                        @if(isset($bonus['amount']) && $bonus['amount'] > 0)
                                                            <div class="text-xs text-gray-500">
                                                                <span>{{ $bonus['name'] ?? $bonus['code'] }}:</span>
                                                                <span>₱{{ number_format($bonus['amount'], 2) }}</span>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                @elseif(isset($isDynamic) && $isDynamic && isset($bonusSettings) && $bonusSettings->isNotEmpty())
                                                    <!-- Fallback: Show Active Bonus Settings if no breakdown available -->
                                                    @foreach($bonusSettings as $setting)
                                                        @php
                                                            // Calculate actual amount for display
                                                            $displayAmount = 0;
                                                            if($setting->calculation_type === 'percentage') {
                                                                $basicPay = $payBreakdownByEmployee[$detail->employee_id]['basic_pay'] ?? $detail->regular_pay ?? 0;
                                                                $displayAmount = ($basicPay * $setting->rate_percentage) / 100;
                                                            } elseif($setting->calculation_type === 'fixed_amount') {
                                                                $displayAmount = $setting->fixed_amount;
                                                            }
                                                        @endphp
                                                        @if($displayAmount > 0)
                                                            <div class="text-xs text-gray-500">
                                                                <span>{{ $setting->name }}:</span>
                                                                <span>₱{{ number_format($displayAmount, 2) }}</span>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                @else
                                                    <!-- Debug: Show why breakdown is not displaying -->
                                                    <div class="text-xs text-red-500">
                                                        @if(!isset($isDynamic))
                                                            No isDynamic
                                                        @elseif(!$isDynamic)
                                                            Not dynamic
                                                        @elseif(!isset($bonusSettings))
                                                            No bonusSettings
                                                        @elseif($bonusSettings->isEmpty())
                                                            Empty bonusSettings
                                                        @else
                                                            Unknown issue
                                                        @endif
                                                    </div>
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
                                            $allowances = $detail->allowances ?? 0;
                                            $bonuses = $detail->bonuses ?? 0;
                                            
                                            // Handle basic pay - use the same calculation as the Basic column for consistency
                                            $basicPayForGross = $basicPay; // Use the same calculation from basic column
                                            
                                            // Handle holiday pay - use the same calculation as the Holiday column for consistency
                                            $holidayPayForGross = $holidayPay; // Use the same calculation from holiday column
                                            
                                            // Handle rest pay - use the same calculation as the Rest column for consistency
                                            $restPayForGross = $restDayPay; // Use the same calculation from rest column
                                            
                                            // Handle overtime pay - use snapshot in processing mode, calculate in draft mode  
                                            if (!isset($isDynamic) || !$isDynamic) {
                                                // PROCESSING/APPROVED: Use overtime breakdown from snapshot
                                                if (isset($detail->overtime_breakdown) && is_array($detail->overtime_breakdown)) {
                                                    $overtimePay = 0;
                                                    foreach ($detail->overtime_breakdown as $overtimeData) {
                                                        $overtimePay += $overtimeData['amount'] ?? 0;
                                                    }
                                                } else {
                                                    $overtimePay = $detail->overtime_pay ?? 0;
                                                }
                                            } else {
                                                // DRAFT: Calculate overtime pay correctly using SAME per-minute logic as Employee Payroll Details
                                                $overtimePay = 0;
                                                $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
                                                $hourlyRate = $detail->employee->hourly_rate ?? 0;
                                                
                                                // Calculate overtime for regular workdays
                                                if (isset($employeeBreakdown['regular_workday'])) {
                                                    $regularBreakdown = $employeeBreakdown['regular_workday'];
                                                    $overtimeHours = $regularBreakdown['overtime_hours'] ?? 0;
                                                    $rateConfig = $regularBreakdown['rate_config'];
                                                    if ($rateConfig && $overtimeHours > 0) {
                                                        $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 1.25;
                                                        
                                                        // Apply per-minute calculation for overtime (same as Employee Payroll Details)
                                                        $overtimeHourlyRate = $hourlyRate * $overtimeMultiplier;
                                                        $overtimeMinutes = $overtimeHours * 60; // Convert to minutes
                                                        $roundedOvertimeMinutes = round($overtimeMinutes); // Round to nearest minute
                                                        $overtimeRatePerMinute = $overtimeHourlyRate / 60;
                                                        $overtimePay += $roundedOvertimeMinutes * $overtimeRatePerMinute;
                                                    }
                                                }
                                                
                                                // Calculate overtime for special holidays
                                                if (isset($employeeBreakdown['special_holiday'])) {
                                                    $specialBreakdown = $employeeBreakdown['special_holiday'];
                                                    $overtimeHours = $specialBreakdown['overtime_hours'] ?? 0;
                                                    $rateConfig = $specialBreakdown['rate_config'];
                                                    if ($rateConfig && $overtimeHours > 0) {
                                                        $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 1.69;
                                                        
                                                        // Apply per-minute calculation for overtime (same as Employee Payroll Details)
                                                        $overtimeHourlyRate = $hourlyRate * $overtimeMultiplier;
                                                        $overtimeMinutes = $overtimeHours * 60; // Convert to minutes
                                                        $roundedOvertimeMinutes = round($overtimeMinutes); // Round to nearest minute
                                                        $overtimeRatePerMinute = $overtimeHourlyRate / 60;
                                                        $overtimePay += $roundedOvertimeMinutes * $overtimeRatePerMinute;
                                                    }
                                                }
                                                
                                                // Calculate overtime for regular holidays  
                                                if (isset($employeeBreakdown['regular_holiday'])) {
                                                    $regularHolidayBreakdown = $employeeBreakdown['regular_holiday'];
                                                    $overtimeHours = $regularHolidayBreakdown['overtime_hours'] ?? 0;
                                                    $rateConfig = $regularHolidayBreakdown['rate_config'];
                                                    if ($rateConfig && $overtimeHours > 0) {
                                                        $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 2.6;
                                                        
                                                        // Apply per-minute calculation for overtime (same as Employee Payroll Details)
                                                        $overtimeHourlyRate = $hourlyRate * $overtimeMultiplier;
                                                        $overtimeMinutes = $overtimeHours * 60; // Convert to minutes
                                                        $roundedOvertimeMinutes = round($overtimeMinutes); // Round to nearest minute
                                                        $overtimeRatePerMinute = $overtimeHourlyRate / 60;
                                                        $overtimePay += $roundedOvertimeMinutes * $overtimeRatePerMinute;
                                                    }
                                                }
                                                
                                                // Calculate overtime for rest days
                                                if (isset($employeeBreakdown['rest_day'])) {
                                                    $restDayBreakdown = $employeeBreakdown['rest_day'];
                                                    $overtimeHours = $restDayBreakdown['overtime_hours'] ?? 0;
                                                    $rateConfig = $restDayBreakdown['rate_config'];
                                                    if ($rateConfig && $overtimeHours > 0) {
                                                        $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 1.69;
                                                        
                                                        // Apply per-minute calculation for overtime (same as Employee Payroll Details)
                                                        $overtimeHourlyRate = $hourlyRate * $overtimeMultiplier;
                                                        $overtimeMinutes = $overtimeHours * 60; // Convert to minutes
                                                        $roundedOvertimeMinutes = round($overtimeMinutes); // Round to nearest minute
                                                        $overtimeRatePerMinute = $overtimeHourlyRate / 60;
                                                        $overtimePay += $roundedOvertimeMinutes * $overtimeRatePerMinute;
                                                    }
                                                }
                                            }
                                            
                                            $calculatedGrossPay = $basicPayForGross + $holidayPayForGross + $restPayForGross + $overtimePay + $allowances + $bonuses;
                                        @endphp
                                        
                                        <!-- Show Gross Pay Breakdown -->
                                        <div class="space-y-1">
                                            @if($calculatedGrossPay > 0)
                                               
                                                    @if($basicPayForGross > 0)
                                                        <div class="text-xs text-gray-500">
                                                            <span>Basic:</span>
                                                            <span>₱{{ number_format($basicPayForGross, 2) }}</span>
                                                        </div>
                                                    @endif
                                                    @if($holidayPayForGross > 0)
                                                        <div class="text-xs text-gray-500">
                                                            <span>Holiday:</span>
                                                            <span>₱{{ number_format($holidayPayForGross, 2) }}</span>
                                                        </div>
                                                    @endif
                                                    @if($restPayForGross > 0)
                                                        <div class="text-xs text-gray-500">
                                                            <span>Rest:</span>
                                                            <span>₱{{ number_format($restPayForGross, 2) }}</span>
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
                                            <div class="font-bold text-green-600 gross-pay-amount" data-gross-amount="{{ $calculatedGrossPay }}">₱{{ number_format($calculatedGrossPay, 2) }}</div>
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
                                                @elseif($employeeSnapshot && $employeeSnapshot->deductions_breakdown)
                                                    @php 
                                                        $hasBreakdown = true; 
                                                        $deductionsBreakdown = is_string($employeeSnapshot->deductions_breakdown) 
                                                            ? json_decode($employeeSnapshot->deductions_breakdown, true) 
                                                            : $employeeSnapshot->deductions_breakdown;
                                                    @endphp
                                                    @foreach($deductionsBreakdown as $code => $deductionData)
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
                                            
                                            // Use the SAME calculation logic as the Gross Pay column
                                            $allowances = $detail->allowances ?? 0;
                                            $bonuses = $detail->bonuses ?? 0;
                                            
                                            // Handle basic pay - use the same calculation as the Basic column for consistency
                                            $basicPay = $payBreakdownByEmployee[$detail->employee_id]['basic_pay'] ?? $detail->regular_pay ?? 0;
                                            
                                            // Handle holiday pay - use the same calculation as the Holiday column for consistency  
                                            $holidayPay = $payBreakdownByEmployee[$detail->employee_id]['holiday_pay'] ?? $detail->holiday_pay ?? 0;
                                            
                                            // Handle rest pay - use the same calculation as the Rest column for consistency
                                            $restPayForNet = 0;
                                            $restDayBreakdown = [];
                                            $totalRestRegularHours = 0;
                                            
                                            if ($payroll->status === 'draft') {
                                                // DRAFT: Calculate rest pay dynamically using DTR data
                                                $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
                                                $hourlyRate = $detail->employee->hourly_rate ?? 0;
                                                
                                                if (isset($employeeBreakdown['rest_day'])) {
                                                    $restBreakdown = $employeeBreakdown['rest_day'];
                                                    $rateConfig = $restBreakdown['rate_config'];
                                                    if ($rateConfig) {
                                                        $regularMultiplier = $rateConfig->regular_rate_multiplier ?? 1.3;
                                                        $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 1.69;
                                                        
                                                        // Apply per-minute calculation for rest day pay (same as Rest column)
                                                        $regularRestHourlyRate = $hourlyRate * $regularMultiplier;
                                                        $regularMinutes = ($restBreakdown['regular_hours'] ?? 0) * 60;
                                                        $roundedRegularMinutes = round($regularMinutes);
                                                        $regularRatePerMinute = $regularRestHourlyRate / 60;
                                                        $regularRestPay = $roundedRegularMinutes * $regularRatePerMinute;
                                                        
                                                        $overtimeRestHourlyRate = $hourlyRate * $overtimeMultiplier;
                                                        $overtimeMinutes = ($restBreakdown['overtime_hours'] ?? 0) * 60;
                                                        $roundedOvertimeMinutes = round($overtimeMinutes);
                                                        $overtimeRatePerMinute = $overtimeRestHourlyRate / 60;
                                                        $overtimeRestPay = $roundedOvertimeMinutes * $overtimeRatePerMinute;
                                                        
                                                        $restPayForNet = $regularRestPay + $overtimeRestPay;
                                                    }
                                                }
                                            } else {
                                                // PROCESSING/APPROVED: Use breakdown data from snapshot
                                                if ($detail->earnings_breakdown) {
                                                    $earningsBreakdown = json_decode($detail->earnings_breakdown, true);
                                                    $restDetails = $earningsBreakdown['rest'] ?? [];
                                                    $restPayForNet = 0;
                                                    foreach ($restDetails as $restData) {
                                                        $restPayForNet += is_array($restData) ? ($restData['amount'] ?? $restData) : $restData;
                                                    }
                                                }
                                            }
                                            
                                            // Handle overtime pay - use the SAME calculation as the Overtime column for consistency
                                            $overtimePayForNet = 0;
                                            if ($payroll->status === 'draft') {
                                                // DRAFT: Calculate overtime pay using per-minute logic (same as Overtime column)
                                                $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
                                                $hourlyRate = $detail->employee->hourly_rate ?? 0;
                                                
                                                // Calculate overtime for regular workdays
                                                if (isset($employeeBreakdown['regular_workday'])) {
                                                    $regularBreakdown = $employeeBreakdown['regular_workday'];
                                                    $overtimeHours = $regularBreakdown['overtime_hours'] ?? 0;
                                                    $rateConfig = $regularBreakdown['rate_config'];
                                                    if ($rateConfig && $overtimeHours > 0) {
                                                        $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 1.25;
                                                        
                                                        // Apply per-minute calculation for overtime (same as Overtime column)
                                                        $overtimeHourlyRate = $hourlyRate * $overtimeMultiplier;
                                                        $overtimeMinutes = $overtimeHours * 60;
                                                        $roundedOvertimeMinutes = round($overtimeMinutes);
                                                        $overtimeRatePerMinute = $overtimeHourlyRate / 60;
                                                        $overtimePayForNet += $roundedOvertimeMinutes * $overtimeRatePerMinute;
                                                    }
                                                }
                                                
                                                // Calculate overtime for special holidays
                                                if (isset($employeeBreakdown['special_holiday'])) {
                                                    $specialBreakdown = $employeeBreakdown['special_holiday'];
                                                    $overtimeHours = $specialBreakdown['overtime_hours'] ?? 0;
                                                    $rateConfig = $specialBreakdown['rate_config'];
                                                    if ($rateConfig && $overtimeHours > 0) {
                                                        $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 1.69;
                                                        
                                                        // Apply per-minute calculation for overtime (same as Overtime column)
                                                        $overtimeHourlyRate = $hourlyRate * $overtimeMultiplier;
                                                        $overtimeMinutes = $overtimeHours * 60;
                                                        $roundedOvertimeMinutes = round($overtimeMinutes);
                                                        $overtimeRatePerMinute = $overtimeHourlyRate / 60;
                                                        $overtimePayForNet += $roundedOvertimeMinutes * $overtimeRatePerMinute;
                                                    }
                                                }
                                                
                                                // Calculate overtime for regular holidays  
                                                if (isset($employeeBreakdown['regular_holiday'])) {
                                                    $regularHolidayBreakdown = $employeeBreakdown['regular_holiday'];
                                                    $overtimeHours = $regularHolidayBreakdown['overtime_hours'] ?? 0;
                                                    $rateConfig = $regularHolidayBreakdown['rate_config'];
                                                    if ($rateConfig && $overtimeHours > 0) {
                                                        $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 2.6;
                                                        
                                                        // Apply per-minute calculation for overtime (same as Overtime column)
                                                        $overtimeHourlyRate = $hourlyRate * $overtimeMultiplier;
                                                        $overtimeMinutes = $overtimeHours * 60;
                                                        $roundedOvertimeMinutes = round($overtimeMinutes);
                                                        $overtimeRatePerMinute = $overtimeHourlyRate / 60;
                                                        $overtimePayForNet += $roundedOvertimeMinutes * $overtimeRatePerMinute;
                                                    }
                                                }
                                                
                                                // Calculate overtime for rest days
                                                if (isset($employeeBreakdown['rest_day'])) {
                                                    $restDayBreakdown = $employeeBreakdown['rest_day'];
                                                    $overtimeHours = $restDayBreakdown['overtime_hours'] ?? 0;
                                                    $rateConfig = $restDayBreakdown['rate_config'];
                                                    if ($rateConfig && $overtimeHours > 0) {
                                                        $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 1.69;
                                                        
                                                        // Apply per-minute calculation for overtime (same as Overtime column)
                                                        $overtimeHourlyRate = $hourlyRate * $overtimeMultiplier;
                                                        $overtimeMinutes = $overtimeHours * 60;
                                                        $roundedOvertimeMinutes = round($overtimeMinutes);
                                                        $overtimeRatePerMinute = $overtimeHourlyRate / 60;
                                                        $overtimePayForNet += $roundedOvertimeMinutes * $overtimeRatePerMinute;
                                                    }
                                                }
                                            } else {
                                                // PROCESSING/APPROVED: Use breakdown data from snapshot 
                                                if ($detail->earnings_breakdown) {
                                                    $earningsBreakdown = json_decode($detail->earnings_breakdown, true);
                                                    $overtimeDetails = $earningsBreakdown['overtime'] ?? [];
                                                    foreach ($overtimeDetails as $overtimeData) {
                                                        $overtimePayForNet += is_array($overtimeData) ? ($overtimeData['amount'] ?? $overtimeData) : $overtimeData;
                                                    }
                                                }
                                            }
                                            
                                            // Calculate gross pay using EXACT SAME logic and variables as Gross Pay column
                                            // This ensures Net Pay breakdown shows SAME gross amount as Gross Pay column
                                            $calculatedGrossPayForNet = $calculatedGrossPay; // Use the SAME gross calculation from Gross Pay column
                                            
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
                                            
                                            $calculatedNetPay = $calculatedGrossPayForNet - $detailDeductionTotal;
                                        @endphp
                                        
                                        <!-- Show Net Pay Breakdown -->
                                        <div class="space-y-1">
                                            @if($calculatedNetPay > 0)
                                               
                                                    <div class="text-xs text-gray-500">
                                                        <span>Gross:</span>
                                                        <span>₱{{ number_format($calculatedGrossPayForNet, 2) }}</span>
                                                    </div>
                                                    <div class="text-xs text-gray-500">
                                                        <span>Deduct:</span>
                                                        <span>₱{{ number_format($detailDeductionTotal, 2) }}</span>
                                                    </div>
                                              
                                            @endif
                                            <div class="font-bold text-purple-600 net-pay-amount" data-net-amount="{{ $calculatedNetPay }}">₱{{ number_format($calculatedNetPay, 2) }}</div>
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

            <!-- JavaScript to update all summary boxes to match their respective column totals -->
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Helper function to format currency
                    function formatCurrency(amount) {
                        return '₱' + amount.toLocaleString('en-PH', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    }
                    
                    // Calculate and update Total Basic (matches Basic column)
                    const basicPayElements = document.querySelectorAll('.basic-pay-amount');
                    let totalBasic = 0;
                    basicPayElements.forEach(function(element) {
                        const basicAmount = parseFloat(element.getAttribute('data-basic-amount')) || 0;
                        totalBasic += basicAmount;
                    });
                    const totalBasicDisplay = document.getElementById('totalBasicDisplay');
                    if (totalBasicDisplay) {
                        totalBasicDisplay.textContent = formatCurrency(totalBasic);
                    }
                    
                    // Calculate and update Total Holiday (matches Holiday column)
                    const holidayPayElements = document.querySelectorAll('.holiday-pay-amount');
                    let totalHoliday = 0;
                    holidayPayElements.forEach(function(element) {
                        const holidayAmount = parseFloat(element.getAttribute('data-holiday-amount')) || 0;
                        totalHoliday += holidayAmount;
                    });
                    const totalHolidayDisplay = document.getElementById('totalHolidayDisplay');
                    if (totalHolidayDisplay) {
                        totalHolidayDisplay.textContent = formatCurrency(totalHoliday);
                    }
                    
                    // Calculate and update Total Gross (matches Gross Pay column)
                    const grossPayElements = document.querySelectorAll('.gross-pay-amount');
                    let totalGross = 0;
                    grossPayElements.forEach(function(element) {
                        const grossAmount = parseFloat(element.getAttribute('data-gross-amount')) || 0;
                        totalGross += grossAmount;
                    });
                    const totalGrossDisplay = document.getElementById('totalGrossDisplay');
                    if (totalGrossDisplay) {
                        totalGrossDisplay.textContent = formatCurrency(totalGross);
                    }
                    
                    // Calculate and update Total Net (matches Net Pay column)
                    const netPayElements = document.querySelectorAll('.net-pay-amount');
                    let totalNet = 0;
                    netPayElements.forEach(function(element) {
                        const netAmount = parseFloat(element.getAttribute('data-net-amount')) || 0;
                        totalNet += netAmount;
                    });
                    const totalNetDisplay = document.getElementById('totalNetDisplay');
                    if (totalNetDisplay) {
                        totalNetDisplay.textContent = formatCurrency(totalNet);
                    }
                });
            </script>

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
                                        
                                        // For draft payrolls, FORCE use live calculation from timeBreakdowns (same as Employee Payroll Details)
                                        if (!$isIncompleteRecord && $timeLog && $payroll->status === 'draft') {
                                            // UNIFIED CALCULATION - Use SAME approach as Employee Payroll Details OVERTIME column
                                            $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
                                            
                                            // Find breakdown for this specific day using the EXACT same logic as Employee Payroll Details
                                            $dayBreakdown = null;
                                            
                                            // First try to find in daily logs (if they exist)
                                            foreach ($employeeBreakdown as $dayType => $breakdown) {
                                                if (isset($breakdown['logs']) && is_array($breakdown['logs'])) {
                                                    foreach ($breakdown['logs'] as $log) {
                                                        if ($log['date'] === $date) {
                                                            $dayBreakdown = $log;
                                                            break 2;
                                                        }
                                                    }
                                                }
                                            }
                                            
                                            // If no daily logs found, check if we can determine day type and use aggregate
                                            if (!$dayBreakdown && $timeLog) {
                                                // Determine day type for this date
                                                $isWeekend = \Carbon\Carbon::parse($date)->isWeekend();
                                                $logType = $timeLog->log_type ?? ($isWeekend ? 'rest_day' : 'regular_workday');
                                                
                                                // For DTR Summary, only show overtime if there's ACTUAL overtime for this specific day
                                                // Don't use aggregate data as it shows total overtime across all days of this type
                                                $regularHours = $timeLog->regular_hours ?? 0;
                                                $overtimeHours = $timeLog->overtime_hours ?? 0;
                                            } else if ($dayBreakdown) {
                                                // Use the daily breakdown
                                                $regularHours = $dayBreakdown['regular_hours'] ?? 0;
                                                $overtimeHours = $dayBreakdown['overtime_hours'] ?? 0;
                                            } else {
                                                // If no breakdown found, use 0 (don't fall back to stored values)
                                                $regularHours = 0;
                                                $overtimeHours = 0;
                                            }
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
                                                        $dayTypeColor = 'bg-orange-100 text-orange-800';
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
                                                        $dayTypeColor = 'bg-orange-100 text-orange-800';
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
                                                    @php
                                                        // Calculate regular hours period end time
                                                        $regularPeriodEnd = $timeLog->time_out;
                                                        $regularPeriodStart = $timeLog->time_in;
                                                        
                                                        // If there's overtime, regular period should end when overtime starts
                                                        if($overtimeHours > 0 && $timeLog->time_in && $timeLog->time_out) {
                                                            $employee = $detail->employee;
                                                            $timeSchedule = $employee->timeSchedule;
                                                            // Fix: Combine the log date with the time_in to get the correct datetime
                                                            $actualTimeIn = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', 
                                                                $timeLog->log_date->format('Y-m-d') . ' ' . \Carbon\Carbon::parse($timeLog->time_in)->format('H:i:s'));
                                                            
                                                            // Calculate overtime threshold dynamically from grace period settings
                                                            $gracePeriodSettings = \App\Models\GracePeriodSetting::current();
                                                            $overtimeThresholdMinutes = $gracePeriodSettings ? $gracePeriodSettings->overtime_threshold_minutes : 480; // default 8 hours = 480 minutes
                                                            $baseWorkingHours = $overtimeThresholdMinutes / 60; // Convert to hours (e.g., 500 minutes = 8.33 hours)
                                                            
                                                            $clockHoursForRegular = $baseWorkingHours; // Use dynamic overtime threshold
                                                            
                                                            if ($timeSchedule && $timeSchedule->break_start && $timeSchedule->break_end) {
                                                                // Add break duration to working hours to get total clock hours
                                                                $breakDuration = $timeSchedule->break_start->diffInHours($timeSchedule->break_end);
                                                                $clockHoursForRegular = $baseWorkingHours + $breakDuration; // dynamic working hours + break time
                                                            }
                                                            
                                                            // Check if employee is within grace period by comparing actual time_in with scheduled time
                                                            $scheduledStart = $timeSchedule ? 
                                                                \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', 
                                                                    $timeLog->log_date->format('Y-m-d') . ' ' . $timeSchedule->time_in->format('H:i') . ':00') :
                                                                \Carbon\Carbon::parse($timeLog->log_date->format('Y-m-d') . ' 08:00');
                                                            
                                                            // Check if employee is within 15-minute grace period
                                                            // If actualTimeIn is after scheduledStart, calculate minutes late
                                                            $minutesLate = 0;
                                                            if ($actualTimeIn > $scheduledStart) {
                                                                $minutesLate = $scheduledStart->diffInMinutes($actualTimeIn);
                                                            }
                                                            
                                                            // Get grace period setting from database instead of hardcoding
                                                            $gracePeriodSettings = \App\Models\GracePeriodSetting::current();
                                                            $lateGracePeriodMinutes = $gracePeriodSettings ? $gracePeriodSettings->late_grace_minutes : 15;
                                                            
                                                            // Check if employee is within the configured grace period
                                                            $isFullWorkingDay = $timeLog->time_in && $timeLog->time_out;
                                                            $isWithinGracePeriod = ($minutesLate <= $lateGracePeriodMinutes) && $isFullWorkingDay;
                                                            
                                                            if ($isWithinGracePeriod) {
                                                                // Grace period applied - use scheduled end time (e.g., 5:00 PM)
                                                                $regularPeriodEnd = $scheduledStart->copy()->addHours($clockHoursForRegular);
                                                            } else {
                                                                // Employee was truly late or beyond grace - extend period to compensate for late minutes
                                                                // For 8:16 AM (16 min late), they work until 5:16 PM to complete 8 hours
                                                                $regularPeriodEnd = $actualTimeIn->copy()->addHours($clockHoursForRegular);
                                                            }
                                                        } else {
                                                            $regularPeriodEnd = $timeLog->time_out ? \Carbon\Carbon::parse($timeLog->time_out)->format('g:i A') : 'N/A';
                                                        }
                                                        
                                                        // Format the regularPeriodEnd if it's a Carbon instance
                                                        if ($regularPeriodEnd instanceof \Carbon\Carbon) {
                                                            $regularPeriodEnd = $regularPeriodEnd->format('g:i A');
                                                        }
                                                        
                                                        // Calculate ACTUAL regular hours based on our display periods to ensure consistency
                                                        // This ensures the hour value matches the time period exactly
                                                        if($overtimeHours > 0 && $timeLog->time_in && $timeLog->time_out) {
                                                            // When there's overtime, regular hours should use the dynamic overtime threshold
                                                            // regardless of grace period (the grace period affects the TIME PERIOD, not the HOURS)
                                                            $gracePeriodSettings = \App\Models\GracePeriodSetting::current();
                                                            $overtimeThresholdMinutes = $gracePeriodSettings ? $gracePeriodSettings->overtime_threshold_minutes : 480;
                                                            $displayRegularHours = $overtimeThresholdMinutes / 60; // Convert to hours (e.g., 500 minutes = 8.33 hours)
                                                        } else {
                                                            // When there's no overtime, use the stored regular hours value
                                                            $displayRegularHours = $regularHours;
                                                        }
                                                    @endphp
                                                    
                                                    <div class="text-green-600 font-medium">
                                                        {{ $timeLog->time_in ? \Carbon\Carbon::parse($timeLog->time_in)->format('g:i A') : 'N/A' }} - {{ $regularPeriodEnd }}
                                                        @if($displayRegularHours > 0)
                                                            {{-- (regular hours period) --}}
                                                        @endif
                                                        ({{ number_format($displayRegularHours, 2) }}h)
                                                    </div>
                                               
                                                    @endif
                                                    
                                                    {{-- Break schedule with break hours --}}
                                                    @php
                                                        // Calculate break duration - use logged break times OR employee's time schedule default
                                                        $breakHours = 0;
                                                        $showBreakTime = false;
                                                        $breakDisplayStart = '';
                                                        $breakDisplayEnd = '';
                                                        
                                                        if ($timeLog->break_in && $timeLog->break_out && $timeLog->time_in && $timeLog->time_out) {
                                                            // Use logged break times
                                                            $breakStart = \Carbon\Carbon::parse($timeLog->break_in);
                                                            $breakEnd = \Carbon\Carbon::parse($timeLog->break_out);
                                                            $workStart = \Carbon\Carbon::parse($timeLog->time_in);
                                                            $workEnd = \Carbon\Carbon::parse($timeLog->time_out);
                                                            
                                                            // Only show break time if employee was present during the break period
                                                            if ($breakStart >= $workStart && $breakEnd <= $workEnd) {
                                                                $breakHours = $breakEnd->diffInMinutes($breakStart) / 60;
                                                                $showBreakTime = true;
                                                                $breakDisplayStart = $breakStart->format('g:i A');
                                                                $breakDisplayEnd = $breakEnd->format('g:i A');
                                                            }
                                                            // Special case: if employee came in during break time (e.g., 1pm when break is 12pm-1pm)
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
                                                                    $breakDisplayStart = $actualBreakStart->format('g:i A');
                                                                    $breakDisplayEnd = $actualBreakEnd->format('g:i A');
                                                                }
                                                            }
                                                        } elseif ($timeLog->time_in && $timeLog->time_out) {
                                                            // Use employee's time schedule default break times when break in/out is missing
                                                            $employee = $detail->employee;
                                                            $timeSchedule = $employee->timeSchedule ?? null;
                                                            
                                                            if ($timeSchedule && $timeSchedule->break_start && $timeSchedule->break_end) {
                                                                $defaultBreakStart = \Carbon\Carbon::parse($timeLog->log_date . ' ' . $timeSchedule->break_start->format('H:i'));
                                                                $defaultBreakEnd = \Carbon\Carbon::parse($timeLog->log_date . ' ' . $timeSchedule->break_end->format('H:i'));
                                                                $workStart = \Carbon\Carbon::parse($timeLog->time_in);
                                                                $workEnd = \Carbon\Carbon::parse($timeLog->time_out);
                                                                
                                                                // Only show default break time if employee was present during the scheduled break period
                                                                if ($defaultBreakStart >= $workStart && $defaultBreakEnd <= $workEnd) {
                                                                    $breakHours = $defaultBreakEnd->diffInMinutes($defaultBreakStart) / 60;
                                                                    $showBreakTime = true;
                                                                    $breakDisplayStart = $defaultBreakStart->format('g:i A');
                                                                    $breakDisplayEnd = $defaultBreakEnd->format('g:i A');
                                                                }
                                                            }
                                                        }
                                                    @endphp
                                                    
                                                    {{-- Display break time if applicable --}}
                                                    @if($showBreakTime && $breakHours > 0)
                                                        <div class="text-red-600 text-xs">
                                                            {{ $breakDisplayStart }} - {{ $breakDisplayEnd }} ({{ number_format($breakHours, 2) }}h)
                                                        </div>
                                                    @endif
                                                
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
                                                                {{-- (regular ot period) --}}
                                                            @elseif($period['type'] === 'night_diff_overtime')
                                                                (ot + nd period)
                                                            @endif
                                                        </div>
                                                        <div class="{{ $period['color_class'] }} text-xs">
                                                            @if($period['type'] === 'regular_overtime')
                                                                OT: {{ number_format($period['hours'], 2) }}h
                                                            @elseif($period['type'] === 'night_diff_overtime')
                                                                OT+ND: {{ number_format($period['hours'], 2) }}h
                                                            @endif
                                                        </div>
                                                        @endif
                                                    @endforeach
                                                    
                                                    {{-- Fallback to old display if no breakdown available --}}
                                                    @if(empty($timePeriodBreakdown) || count($timePeriodBreakdown) <= 1)
                                                        {{-- FORCE USE THE UNIFIED CALCULATION - Use overtimeHours from our unified calculation above --}}
                                                        @if($overtimeHours > 0)
                                                        @php
                                                            // Calculate regular overtime period - should start where regular hours end
                                                            $regularOTStart = '';
                                                            $regularOTEnd = '';
                                                            if ($timeLog->time_out && $timeLog->time_in) {
                                                                $workEnd = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', 
                                                                    $timeLog->log_date->format('Y-m-d') . ' ' . \Carbon\Carbon::parse($timeLog->time_out)->format('H:i:s'));
                                                                $actualTimeIn = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', 
                                                                    $timeLog->log_date->format('Y-m-d') . ' ' . \Carbon\Carbon::parse($timeLog->time_in)->format('H:i:s'));
                                                                
                                                                $employee = $detail->employee;
                                                                $timeSchedule = $employee->timeSchedule;
                                                                
                                                                // Calculate clock hours for regular work using dynamic overtime threshold
                                                                $gracePeriodSettings = \App\Models\GracePeriodSetting::current();
                                                                $overtimeThresholdMinutes = $gracePeriodSettings ? $gracePeriodSettings->overtime_threshold_minutes : 480;
                                                                $baseWorkingHours = $overtimeThresholdMinutes / 60;
                                                                
                                                                $clockHoursForRegular = $baseWorkingHours;
                                                                if ($timeSchedule && $timeSchedule->break_start && $timeSchedule->break_end) {
                                                                    $breakDuration = $timeSchedule->break_start->diffInHours($timeSchedule->break_end);
                                                                    $clockHoursForRegular = $baseWorkingHours + $breakDuration;
                                                                }
                                                                
                                                                // Use same logic as regular hours period calculation
                                                                $scheduledStart = $timeSchedule ? 
                                                                    \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', 
                                                                        $timeLog->log_date->format('Y-m-d') . ' ' . $timeSchedule->time_in->format('H:i') . ':00') :
                                                                    \Carbon\Carbon::parse($timeLog->log_date->format('Y-m-d') . ' 08:00');
                                                                
                                                                $minutesLate = 0;
                                                                if ($actualTimeIn > $scheduledStart) {
                                                                    $minutesLate = $scheduledStart->diffInMinutes($actualTimeIn);
                                                                }
                                                                $isFullWorkingDay = $timeLog->time_in && $timeLog->time_out;
                                                                
                                                                // Get grace period setting from database
                                                                $gracePeriodSettings = \App\Models\GracePeriodSetting::current();
                                                                $lateGracePeriodMinutes = $gracePeriodSettings ? $gracePeriodSettings->late_grace_minutes : 15;
                                                                $isWithinGracePeriod = ($minutesLate <= $lateGracePeriodMinutes) && $isFullWorkingDay;
                                                                
                                                                if ($isWithinGracePeriod) {
                                                                    // Grace period applied - OT starts at scheduled end time
                                                                    $regularOTStart = $scheduledStart->copy()->addHours($clockHoursForRegular)->format('g:i A');
                                                                } else {
                                                                    // Employee was truly late - OT starts after their actual work hours
                                                                    $regularOTStart = $actualTimeIn->copy()->addHours($clockHoursForRegular)->format('g:i A');
                                                                }
                                                                
                                                                // Regular OT ends at actual time_out
                                                                $regularOTEnd = $workEnd->format('g:i A');
                                                            }
                                                        @endphp
                                                        @if($regularOTStart && $regularOTEnd)
                                                        @php
                                                            // Calculate the ACTUAL overtime hours based on our display periods
                                                            // This ensures display time periods match the hour values exactly
                                                            try {
                                                                $displayOTStart = \Carbon\Carbon::createFromFormat('Y-m-d g:i A', 
                                                                    $timeLog->log_date->format('Y-m-d') . ' ' . $regularOTStart);
                                                                $displayOTEnd = \Carbon\Carbon::createFromFormat('Y-m-d g:i A', 
                                                                    $timeLog->log_date->format('Y-m-d') . ' ' . $regularOTEnd);
                                                                
                                                                // Handle overnight shifts (if end time is before start time, add a day)
                                                                if ($displayOTEnd < $displayOTStart) {
                                                                    $displayOTEnd->addDay();
                                                                }
                                                                
                                                                $calculatedRegularOT = $displayOTStart->diffInMinutes($displayOTEnd) / 60;
                                                                
                                                                // Ensure positive hours
                                                                $calculatedRegularOT = abs($calculatedRegularOT);
                                                            } catch (\Exception $e) {
                                                                // Fallback to database value if parsing fails
                                                                $calculatedRegularOT = $overtimeHours;
                                                            }
                                                            
                                                            // Use the CALCULATED hours based on time period, not stored DB values
                                                            $displayRegularOTHours = $calculatedRegularOT; // Use calculated time period, not DB values
                                                        @endphp
                                                        <div class="text-orange-600 text-xs">
                                                            {{ $regularOTStart }} - {{ $regularOTEnd }} ({{ number_format($displayRegularOTHours, 2) }}h)
                                                        </div>
                                                   
                                                        @endif
                                                     
                                                        @endif
                                                        
                                                        @if($nightDiffOvertimeHours > 0)
                                                        @php
                                                            // Calculate night diff overtime period - should start after regular OT
                                                            $nightOTStart = '';
                                                            $nightOTEnd = '';
                                                            if ($timeLog->time_out && $timeLog->time_in) {
                                                                $workStart = \Carbon\Carbon::parse($timeLog->time_in);
                                                                $workEnd = \Carbon\Carbon::parse($timeLog->time_out);
                                                                
                                                                // Calculate where regular hours + regular OT end using dynamic threshold
                                                                $gracePeriodSettings = \App\Models\GracePeriodSetting::current();
                                                                $overtimeThresholdMinutes = $gracePeriodSettings ? $gracePeriodSettings->overtime_threshold_minutes : 480;
                                                                $baseWorkingHours = $overtimeThresholdMinutes / 60;
                                                                
                                                                $clockHoursForRegular = $baseWorkingHours;
                                                                $employee = $detail->employee;
                                                                $timeSchedule = $employee->timeSchedule;
                                                                
                                                                if ($timeSchedule && $timeSchedule->break_start && $timeSchedule->break_end) {
                                                                    $breakDuration = $timeSchedule->break_start->diffInHours($timeSchedule->break_end);
                                                                    $clockHoursForRegular = $baseWorkingHours + $breakDuration;
                                                                }
                                                                
                                                                // Night diff OT starts after regular hours + regular OT
                                                                $nightOTStart = $workStart->copy()->addHours($clockHoursForRegular)->addHours($regularOvertimeHours)->format('g:i A');
                                                                $nightOTEnd = $workEnd->format('g:i A');
                                                            }
                                                        @endphp
                                                        @if($nightOTStart && $nightOTEnd)
                                                        <div class="text-purple-600 text-xs">
                                                            {{ $nightOTStart }} - {{ $nightOTEnd }} (ot + nd period)
                                                        </div>
                                                        <div class="text-purple-600 text-xs">
                                                            OT+ND: {{ number_format($nightDiffOvertimeHours, 2) }}h
                                                        </div>
                                                        @endif
                                                     
                                                        @endif
                                                        
                                                        {{-- If we have total overtime but no breakdown, show total --}}
                                                        @if($regularOvertimeHours == 0 && $nightDiffOvertimeHours == 0 && $overtimeHours > 0)
                                                        @php
                                                            // Calculate overtime period starting from where regular hours end
                                                            $overtimeStart = '';
                                                            $overtimeEnd = '';
                                                            if ($timeLog->time_out && $timeLog->time_in) {
                                                                $workEnd = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', 
                                                                    $timeLog->log_date->format('Y-m-d') . ' ' . \Carbon\Carbon::parse($timeLog->time_out)->format('H:i:s'));
                                                                $actualTimeIn = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', 
                                                                    $timeLog->log_date->format('Y-m-d') . ' ' . \Carbon\Carbon::parse($timeLog->time_in)->format('H:i:s'));
                                                                
                                                                $employee = $detail->employee;
                                                                $timeSchedule = $employee->timeSchedule;
                                                                
                                                                // Calculate where regular hours end using dynamic overtime threshold
                                                                $gracePeriodSettings = \App\Models\GracePeriodSetting::current();
                                                                $overtimeThresholdMinutes = $gracePeriodSettings ? $gracePeriodSettings->overtime_threshold_minutes : 480;
                                                                $baseWorkingHours = $overtimeThresholdMinutes / 60;
                                                                
                                                                $clockHoursForRegular = $baseWorkingHours;
                                                                if ($timeSchedule && $timeSchedule->break_start && $timeSchedule->break_end) {
                                                                    $breakDuration = $timeSchedule->break_start->diffInHours($timeSchedule->break_end);
                                                                    $clockHoursForRegular = $baseWorkingHours + $breakDuration;
                                                                }
                                                                
                                                                // Use same logic as regular hours period calculation
                                                                $scheduledStart = $timeSchedule ? 
                                                                    \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', 
                                                                        $timeLog->log_date->format('Y-m-d') . ' ' . $timeSchedule->time_in->format('H:i') . ':00') :
                                                                    \Carbon\Carbon::parse($timeLog->log_date->format('Y-m-d') . ' 08:00');
                                                                
                                                                $minutesLate = 0;
                                                                if ($actualTimeIn > $scheduledStart) {
                                                                    $minutesLate = $scheduledStart->diffInMinutes($actualTimeIn);
                                                                }
                                                                $isFullWorkingDay = $timeLog->time_in && $timeLog->time_out;
                                                                
                                                                // Get grace period setting from database
                                                                $gracePeriodSettings = \App\Models\GracePeriodSetting::current();
                                                                $lateGracePeriodMinutes = $gracePeriodSettings ? $gracePeriodSettings->late_grace_minutes : 15;
                                                                $isWithinGracePeriod = ($minutesLate <= $lateGracePeriodMinutes) && $isFullWorkingDay;
                                                                
                                                                if ($isWithinGracePeriod) {
                                                                    // Grace period applied - OT starts at scheduled end time
                                                                    $overtimeStart = $scheduledStart->copy()->addHours($clockHoursForRegular)->format('g:i A');
                                                                } else {
                                                                    // Employee was truly late - OT starts after their actual work hours
                                                                    $overtimeStart = $actualTimeIn->copy()->addHours($clockHoursForRegular)->format('g:i A');
                                                                }
                                                                
                                                                $overtimeEnd = $workEnd->format('g:i A');
                                                            }
                                                        @endphp
                                                        @if($overtimeStart && $overtimeEnd)
                                                        @php
                                                            // Calculate actual overtime hours from the time period
                                                            try {
                                                                $displayOTStart = \Carbon\Carbon::createFromFormat('Y-m-d g:i A', 
                                                                    $timeLog->log_date->format('Y-m-d') . ' ' . $overtimeStart);
                                                                $displayOTEnd = \Carbon\Carbon::createFromFormat('Y-m-d g:i A', 
                                                                    $timeLog->log_date->format('Y-m-d') . ' ' . $overtimeEnd);
                                                                
                                                                // Handle overnight shifts
                                                                if ($displayOTEnd < $displayOTStart) {
                                                                    $displayOTEnd->addDay();
                                                                }
                                                                
                                                                $calculatedOTHours = $displayOTStart->diffInMinutes($displayOTEnd) / 60;
                                                                $calculatedOTHours = abs($calculatedOTHours);
                                                            } catch (\Exception $e) {
                                                                $calculatedOTHours = $overtimeHours;
                                                            }
                                                        @endphp
                                                        <div class="text-orange-600 text-xs">
                                                            {{ $overtimeStart }} - {{ $overtimeEnd }} 
                                                        </div>
                                                        <div class="text-orange-600 text-xs">
                                                            OT: {{ number_format($calculatedOTHours, 2) }}h
                                                        </div>
                                                        @else
                                                        <div class="text-orange-600 text-xs">
                                                            OT: {{ number_format($overtimeHours, 2) }}h
                                                        </div>
                                                        @endif
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
