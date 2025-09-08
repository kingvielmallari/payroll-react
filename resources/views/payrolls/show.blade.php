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
                            <div class="text-sm text-blue-800">Total Regular</div>
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
                            <div class="text-2xl font-bold text-cyan-600" id="totalRestDisplay">₱{{ number_format($totalRestDayPay, 2) }}</div>
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
                                            
                                            // Calculate the SAME gross pay as in the Gross Pay column for this employee
                                            $basicPayForGross = $payBreakdownByEmployee[$detail->employee_id]['basic_pay'] ?? $detail->basic_pay ?? 0;
                                            $holidayPayForGross = $payBreakdownByEmployee[$detail->employee_id]['holiday_pay'] ?? $detail->holiday_pay ?? 0;
                                            $restPayForGross = 0;
                                            $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
                                            if (isset($employeeBreakdown['rest_day'])) {
                                                $restBreakdown = $employeeBreakdown['rest_day'];
                                                $restPayForGross = ($restBreakdown['regular_hours'] ?? 0) * ($detail->hourly_rate ?? 0) * 1.3; // Use calculated hourly rate
                                                $restPayForGross += ($restBreakdown['overtime_hours'] ?? 0) * ($detail->hourly_rate ?? 0) * 1.69; // Use calculated hourly rate
                                            }
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
                                            
                                            // Calculate total gross pay like in the Gross Pay column
                                            $calculatedGrossPayForSummary = $basicPayForGross + $holidayPayForGross + $restPayForGross + $overtimePay + $allowances + $bonuses;
                                            
                                            // Calculate taxable income for this detail (same logic as in detail columns)
                                            $taxableIncomeForSummary = $basicPayForGross + $holidayPayForGross + $restPayForGross + $overtimePay;
                                            if (isset($allowanceBonusSettings) && $allowanceBonusSettings->isNotEmpty()) {
                                                foreach($allowanceBonusSettings as $abSetting) {
                                                    if ($abSetting->is_taxable) {
                                                        $calculatedAllowanceAmount = $abSetting->calculateAmount(
                                                            $basicPay, // Use calculated basic pay for the period
                                                            $detail->employee->daily_rate ?? 0, // dailyRate
                                                            null, // workingDays (will be calculated inside if needed)
                                                            $detail->employee // employee object
                                                        );
                                                        $taxableIncomeForSummary += $calculatedAllowanceAmount;
                                                    }
                                                }
                                            }
                                            
                                            // Auto-detect pay frequency from payroll period
                                            $payFrequency = 'semi_monthly'; // default
                                            $periodDays = $payroll->period_start->diffInDays($payroll->period_end) + 1;
                                            if ($periodDays <= 1) {
                                                $payFrequency = 'daily';
                                            } elseif ($periodDays <= 7) {
                                                $payFrequency = 'weekly';
                                            } elseif ($periodDays <= 16) {
                                                $payFrequency = 'semi_monthly';
                                            } else {
                                                $payFrequency = 'monthly';
                                            }
                                            
                                            $calculatedAmount = $setting->calculateDeduction(
                                                $basicPay, 
                                                $overtimePay, 
                                                $bonuses, 
                                                $allowances, 
                                                $calculatedGrossPayForSummary, // grossPay
                                                $taxableIncomeForSummary, // taxableIncome
                                                null, // netPay
                                                $detail->employee->calculateMonthlyBasicSalary($payroll->period_start, $payroll->period_end), // monthlyBasicSalary - DYNAMIC
                                                $payFrequency // payFrequency
                                            );
                                            
                                            // Apply deduction distribution logic to match backend calculations
                                            if ($calculatedAmount > 0) {
                                                $calculatedAmount = $setting->calculateDistributedAmount(
                                                    $calculatedAmount,
                                                    $payroll->period_start,
                                                    $payroll->period_end,
                                                    $detail->employee->pay_schedule ?? $payFrequency
                                                );
                                            }
                                            
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
                            <div class="text-2xl font-bold text-red-600" id="totalDeductionsDisplay">₱{{ number_format($actualTotalDeductions, 2) }}</div>
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

                    <div class="mt-6 grid grid-cols-1 md:grid-cols-5 gap-6">
                        <div>
                            <h4 class="text-sm font-medium text-gray-900">Status</h4>
                            <div class="mt-1 flex items-center space-x-2">
                                @if($payroll->is_paid)
                                    {{-- Show both Approved and Paid when payroll is paid --}}
                                    <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 text-blue-800">
                                        Approved
                                    </span>
                                    <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                                        Paid
                                    </span>
                                @else
                                    {{-- Show only the current status when not paid --}}
                                    <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full 
                                        {{ $payroll->status == 'approved' ? 'bg-blue-100 text-blue-800' : 
                                            ($payroll->status == 'processing' ? 'bg-yellow-100 text-yellow-800' : 
                                             ($payroll->status == 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) }}">
                                        {{ ucfirst($payroll->status) }}
                                    </span>
                                @endif
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
                            <h4 class="text-sm font-medium text-gray-900">Payroll Frequency</h4>
                            @php
                                // Get the first employee's pay schedule to determine the frequency display
                                $firstEmployee = $payroll->payrollDetails->first()?->employee;
                                $paySchedule = $firstEmployee?->pay_schedule ?? 'weekly';
                                
                                // Pay frequency display logic
                                $payFrequencyDisplay = '';
                                $periodStart = \Carbon\Carbon::parse($payroll->period_start);
                                $periodEnd = \Carbon\Carbon::parse($payroll->period_end);
                                
                                switch ($paySchedule) {
                                    case 'semi_monthly':
                                    case 'semi-monthly':
                                        // Determine if it's 1st or 2nd cutoff based on the day
                                        $cutoff = $periodStart->day <= 15 ? '1st' : '2nd';
                                        $payFrequencyDisplay = "Semi-Monthly - {$cutoff} Cutoff";
                                        break;
                                        
                                    case 'monthly':
                                        $monthName = $periodStart->format('F');
                                        $payFrequencyDisplay = "Monthly - {$monthName}";
                                        break;
                                        
                                    case 'weekly':
                                        // Calculate which week of the month this is based on Sunday-Saturday weeks
                                        // Find the Saturday that ends this week period
                                        $saturdayEnd = $periodEnd->copy();
                                        while ($saturdayEnd->dayOfWeek !== 6) { // 6 = Saturday
                                            $saturdayEnd->addDay();
                                        }
                                        
                                        // Find all Saturdays in this month to determine week number
                                        $monthStart = $saturdayEnd->copy()->startOfMonth();
                                        $weekNumber = 0;
                                        $currentSaturday = $monthStart->copy();
                                        
                                        // Find first Saturday of the month
                                        while ($currentSaturday->dayOfWeek !== 6) {
                                            $currentSaturday->addDay();
                                        }
                                        
                                        // Count Saturdays until we reach our target Saturday
                                        while ($currentSaturday->lte($saturdayEnd)) {
                                            $weekNumber++;
                                            if ($currentSaturday->format('Y-m-d') === $saturdayEnd->format('Y-m-d')) {
                                                break;
                                            }
                                            $currentSaturday->addWeek();
                                        }
                                        
                                        $weekOrdinal = match($weekNumber) {
                                            1 => '1st',
                                            2 => '2nd', 
                                            3 => '3rd',
                                            4 => '4th',
                                            default => '5th'
                                        };
                                        $payFrequencyDisplay = "Weekly - {$weekOrdinal}";
                                        break;
                                        
                                    case 'daily':
                                        $dayOfMonth = $periodStart->day;
                                        $dayOrdinal = match($dayOfMonth % 10) {
                                            1 => $dayOfMonth . 'st',
                                            2 => $dayOfMonth . 'nd', 
                                            3 => $dayOfMonth . 'rd',
                                            default => $dayOfMonth . 'th'
                                        };
                                        // Handle special cases for 11th, 12th, 13th
                                        if (in_array($dayOfMonth, [11, 12, 13])) {
                                            $dayOrdinal = $dayOfMonth . 'th';
                                        }
                                        $payFrequencyDisplay = "Daily - {$dayOrdinal}";
                                        break;
                                        
                                    default:
                                        $payFrequencyDisplay = ucfirst(str_replace('_', '-', $paySchedule));
                                        break;
                                }
                            @endphp
                            <p class="mt-1 text-sm text-gray-600">{{ $payFrequencyDisplay }}</p>
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

                    {{-- Payment Information Section --}}
                    @if($payroll->is_paid)
                    <div class="mt-6 bg-green-50 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-green-900 mb-3 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Payment Information
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                {{-- <p class="text-sm text-green-700">
                                    <span class="font-medium">Marked as paid on:</span><br>
                                    {{ $payroll->marked_paid_at->format('M d, Y g:i A') }}
                                </p> --}}
                                @if($payroll->markedPaidBy)
                                <p class="text-sm text-green-700 mt-1 ">
                                    <span class="font-medium">Marked by:</span>
                                    {{ $payroll->markedPaidBy->name }} on {{ $payroll->marked_paid_at->format('M d, Y g:i A') }}
                                </p>
                                @endif
                            </div>
                              @if($payroll->payment_proof_files && count($payroll->payment_proof_files) > 0)
                        <div >
                            <p class="text-sm font-medium text-green-900 mb-2">Payment Proof Files:</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                @foreach($payroll->payment_proof_files as $file)
                                <a href="{{ asset('storage/' . $file['file_path']) }}" 
                                   target="_blank"
                                   class="flex items-center p-2 text-sm text-green-700 bg-white rounded border border-green-200 hover:bg-green-50">
                                    @if(in_array(strtolower(pathinfo($file['original_name'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png']))
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 002 2z"></path>
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                    @endif
                                    <span class="truncate">{{ $file['original_name'] }}</span>
                                </a>
                                @endforeach
                            </div>
                        </div>
                        @endif
                            @if($payroll->payment_notes)
                            <div>
                                <p class="text-sm text-green-700">
                                    <span class="font-medium">Payment Notes:</span><br>
                                    {{ $payroll->payment_notes }}
                                </p>
                            </div>
                            @endif
                        </div>

                      
                    </div>
                    @endif

                   
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

                        {{-- Mark as Paid/Unpaid buttons --}}
                        @can('mark payrolls as paid')
                        @if($payroll->canBeMarkedAsPaid())
                        <button type="button"
                                onclick="openMarkAsPaidModal()"
                                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Mark as Paid
                        </button>
                        @elseif($payroll->canBeUnmarkedAsPaid())
                        <form method="POST" action="{{ route('payrolls.unmark-as-paid', $payroll) }}" class="inline">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-orange-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-700 focus:bg-orange-700 active:bg-orange-900 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    onclick="return confirm('Are you sure you want to unmark this payroll as paid? This will reverse all deduction calculations.')">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.866-.833-2.598 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                Unmark as Paid
                            </button>
                        </form>
                        @endif
                        @endcan

                        {{-- @can('delete payrolls')
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
                        @endcan --}}
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
                                        Regular
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
                                                @if($detail->employee->fixed_rate && $detail->employee->rate_type)
                                                <div class="flex items-center gap-1 ">
                                                    <span class="text-sm text-blue-700">
                                                        ₱{{ number_format($detail->employee->fixed_rate, 2) }}/{{ ucfirst(str_replace('_', ' ', $detail->employee->rate_type)) }}
                                                    </span>
                                                    <span class="inline-flex items-center p-1 text-xs font-medium rounded-full bg-blue-50 text-blue-700">
                                                        Fixed Rate
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    <span class="text-sm font-small text-yellow-700">
                                                        ₱{{ number_format($detail->employee->calculateMonthlyBasicSalary($payroll->period_start, $payroll->period_end), 2) }}
                                                    </span>
                                                    <span class="inline-flex items-center p-1 text-xs font-small rounded-full bg-yellow-50 text-yellow-700">
                                                        MBS
                                                    </span>
                                                </div>
                                                @endif
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
                                                // PROCESSING/APPROVED: Use stored data from snapshot first, fallback to database
                                                if ($employeeSnapshot) {
                                                    $payBreakdown = [
                                                        'basic_pay' => $employeeSnapshot->regular_pay ?? 0, 
                                                        'holiday_pay' => $employeeSnapshot->holiday_pay ?? 0,
                                                        'rest_day_pay' => $employeeSnapshot->rest_day_pay ?? 0,
                                                        'overtime_pay' => $employeeSnapshot->overtime_pay ?? 0
                                                    ];
                                                } else {
                                                    // Fallback to database values
                                                    $payBreakdown = [
                                                        'basic_pay' => $detail->regular_pay ?? 0, 
                                                        'holiday_pay' => $detail->holiday_pay ?? 0,
                                                        'rest_day_pay' => $detail->rest_day_pay ?? 0,
                                                        'overtime_pay' => $detail->overtime_pay ?? 0
                                                    ];
                                                }
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
                                                // DRAFT: Use timeBreakdowns data like Overtime column
                                                $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
                                                $hourlyRate = $detail->hourly_rate ?? 0; // Use calculated hourly rate from detail
                                                $basicPay = 0;
                                                
                                                // Get night differential settings for dynamic rate
                                                $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
                                                $nightDiffMultiplier = $nightDiffSetting ? $nightDiffSetting->rate_multiplier : 1.10;
                                                
                                                // Regular workday hours - split into regular and regular+ND
                                                if (isset($employeeBreakdown['regular_workday'])) {
                                                    $regularHours = $employeeBreakdown['regular_workday']['regular_hours'] ?? 0;
                                                    $nightDiffRegularHours = $employeeBreakdown['regular_workday']['night_diff_regular_hours'] ?? 0;
                                                    
                                                    // Get rate config for regular workday
                                                    $rateConfig = $employeeBreakdown['regular_workday']['rate_config'] ?? null;
                                                    $regularMultiplier = $rateConfig ? $rateConfig->regular_rate_multiplier : 1.01;
                                                    
                                                    // Regular Workday (without ND)
                                                    if ($regularHours > 0) {
                                                        // Use consistent calculation: hourly rate * multiplier, truncate to 4 decimals, then multiply by minutes
                                                        $actualMinutes = $regularHours * 60;
                                                        $roundedMinutes = round($actualMinutes);
                                                        $adjustedHourlyRate = $hourlyRate * $regularMultiplier;
                                                        $ratePerMinute = $adjustedHourlyRate / 60; // Truncate to 4 decimals
                                                        $amount = round($ratePerMinute * $roundedMinutes, 2); // Round final amount to 2 decimals
                                                        
                                                        $percentageDisplay = number_format($regularMultiplier * 100, 0) . '%';
                                                        
                                                        $basicBreakdownData['Regular Workday'] = [
                                                            'hours' => $regularHours,
                                                            'rate' => $hourlyRate,
                                                            'multiplier' => $regularMultiplier,
                                                            'percentage' => $percentageDisplay,
                                                            'amount' => $amount,
                                                        ];
                                                        $basicRegularHours += $regularHours;
                                                        $basicPay += $amount;
                                                    }
                                                    
                                                    // Regular Workday + ND
                                                    if ($nightDiffRegularHours > 0) {
                                                        // Combined rate: regular rate + night differential bonus
                                                        $combinedMultiplier = $regularMultiplier + ($nightDiffMultiplier - 1);
                                                        
                                                        // Use consistent calculation: hourly rate * multiplier, truncate to 4 decimals, then multiply by minutes
                                                        $actualMinutes = $nightDiffRegularHours * 60;
                                                        $roundedMinutes = round($actualMinutes);
                                                        $adjustedHourlyRate = $hourlyRate * $combinedMultiplier;
                                                        $ratePerMinute = $adjustedHourlyRate / 60; // Truncate to 4 decimals
                                                        $ndAmount = round($ratePerMinute * $roundedMinutes, 2); // Round final amount to 2 decimals
                                                        
                                                        $ndPercentageDisplay = number_format($combinedMultiplier * 100, 0) . '%';
                                                        
                                                        $basicBreakdownData['Regular Workday+ND'] = [
                                                            'hours' => $nightDiffRegularHours,
                                                            'rate' => $hourlyRate,
                                                            'multiplier' => $combinedMultiplier,
                                                            'percentage' => $ndPercentageDisplay,
                                                            'amount' => $ndAmount,
                                                        ];
                                                        $basicRegularHours += $nightDiffRegularHours;
                                                        $basicPay += $ndAmount;
                                                    }
                                                }
                                            } else {
                                                // PROCESSING/APPROVED: Use breakdown data from snapshot
                                                $basicBreakdownData = [];
                                                if ($employeeSnapshot && $employeeSnapshot->basic_breakdown) {
                                                    $rawBreakdownData = is_string($employeeSnapshot->basic_breakdown) 
                                                        ? json_decode($employeeSnapshot->basic_breakdown, true) 
                                                        : $employeeSnapshot->basic_breakdown;
                                                    
                                                    // Ensure percentage is added to snapshot data for consistent display
                                                    foreach ($rawBreakdownData as $type => $data) {
                                                        $basicBreakdownData[$type] = [
                                                            'hours' => $data['hours'],
                                                            'amount' => $data['amount'],
                                                            'rate' => $data['rate'] ?? 0,
                                                            'multiplier' => $data['multiplier'] ?? 1.0,
                                                            'percentage' => isset($data['multiplier']) ? number_format($data['multiplier'] * 100, 0) . '%' : (isset($data['percentage']) ? $data['percentage'] : '100%')
                                                        ];
                                                    }
                                                }
                                                $basicRegularHours = array_sum(array_column($basicBreakdownData, 'hours'));
                                                // Use calculated total from breakdown instead of stored regular_pay
                                                $basicPay = array_sum(array_column($basicBreakdownData, 'amount'));
                                            }
                                        @endphp
                                        
                                        <div>
                                            @if(!empty($basicBreakdownData))
                                                <!-- Show Basic Pay breakdown -->
                                                @foreach($basicBreakdownData as $type => $data)
                                                    <div class="text-xs text-gray-500 mb-1">
                                                        <span>{{ $type }}: {{ isset($data['minutes']) ? number_format($data['minutes'], 0) . 'm' : number_format($data['hours'] * 60, 0) . 'm' }}</span>
                                                        <div class="text-xs text-gray-600">
                                                            @if(isset($data['percentage']))
                                                                {{ $data['percentage'] }} = ₱{{ number_format($data['amount'] ?? 0, 2) }}
                                                            @elseif(str_contains($type, '+ND') && isset($data['multiplier']))
                                                                {{ number_format($data['multiplier'] * 100, 0) }}% = ₱{{ number_format($data['amount'] ?? 0, 2) }}
                                                            @elseif(str_contains($type, '+ND'))
                                                                110% = ₱{{ number_format($data['amount'] ?? 0, 2) }}
                                                            @else
                                                                ₱{{ number_format($data['rate'] ?? 0, 2) }}/hr 
                                                                @if(isset($data['rate_per_minute']))
                                                                    <br>(₱{{ number_format($data['rate_per_minute'], 4) }}/min)
                                                                @endif
                                                                = ₱{{ number_format($data['amount'] ?? 0, 2) }}
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                                <div class="text-xs border-t pt-1">
                                                    <?php 
                                                        // Round total minutes properly without adding extra 0.5
                                                        $totalMinutes = round($basicRegularHours * 60);
                                                        $hours = intval($totalMinutes / 60);
                                                        $minutes = $totalMinutes % 60;
                                                    ?>
                                                    <div class="text-gray-500">Total: {{ $hours }}h {{ $minutes }}m</div>
                                                </div>
                                            @else
                                                <div class="text-xs text-gray-500">
                                                    <?php 
                                                        // Round total minutes properly without adding extra 0.5
                                                        $totalMinutes = round($basicRegularHours * 60);
                                                        $hours = intval($totalMinutes / 60);
                                                        $minutes = $totalMinutes % 60;
                                                    ?>
                                                    <div class="text-gray-500">Total: {{ $hours }}h {{ $minutes }}m</div>
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
                                                // DRAFT: Use timeBreakdowns data like Overtime column
                                                $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
                                                $hourlyRate = $detail->hourly_rate ?? 0; // Use calculated hourly rate from detail
                                                $holidayPay = 0;
                                                
                                                // Get night differential settings for dynamic rate
                                                $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
                                                $nightDiffMultiplier = $nightDiffSetting ? $nightDiffSetting->rate_multiplier : 1.10;
                                                
                                                // Process each holiday type
                                                $holidayTypes = ['special_holiday', 'regular_holiday', 'rest_day_special_holiday', 'rest_day_regular_holiday'];
                                                foreach ($holidayTypes as $logType) {
                                                    if (isset($employeeBreakdown[$logType])) {
                                                        $regularHours = $employeeBreakdown[$logType]['regular_hours'] ?? 0;
                                                        $nightDiffRegularHours = $employeeBreakdown[$logType]['night_diff_regular_hours'] ?? 0;
                                                        
                                                        $rateConfig = $employeeBreakdown[$logType]['rate_config'];
                                                        $displayName = $rateConfig ? $rateConfig->display_name : 'Holiday';
                                                        $regularMultiplier = $rateConfig ? $rateConfig->regular_rate_multiplier : 1.3;
                                                        
                                                        // Holiday (without ND)
                                                        if ($regularHours > 0) {
                                                            // Use consistent calculation: hourly rate * multiplier, truncate to 4 decimals, then multiply by minutes
                                                            $actualMinutes = $regularHours * 60;
                                                            $roundedMinutes = round($actualMinutes);
                                                            $adjustedHourlyRate = $hourlyRate * $regularMultiplier;
                                                            $ratePerMinute = $adjustedHourlyRate / 60; // Truncate to 4 decimals
                                                            $amount = round($ratePerMinute * $roundedMinutes, 2); // Round final amount to 2 decimals
                                                            
                                                            $percentageDisplay = number_format($regularMultiplier * 100, 0) . '%';
                                                            
                                                            if (isset($holidayBreakdown[$displayName])) {
                                                                $holidayBreakdown[$displayName]['hours'] += $regularHours;
                                                                $holidayBreakdown[$displayName]['amount'] += $amount;
                                                            } else {
                                                                $holidayBreakdown[$displayName] = [
                                                                    'hours' => $regularHours,
                                                                    'amount' => $amount,
                                                                    'rate' => $hourlyRate,
                                                                    'multiplier' => $regularMultiplier,
                                                                    'percentage' => $percentageDisplay
                                                                ];
                                                            }
                                                            $totalHolidayRegularHours += $regularHours;
                                                            $holidayPay += $amount;
                                                        }
                                                        
                                                        // Holiday + ND
                                                        if ($nightDiffRegularHours > 0) {
                                                            // Combined rate: holiday rate + night differential bonus
                                                            $combinedMultiplier = $regularMultiplier + ($nightDiffMultiplier - 1);
                                                            $ndDisplayName = $displayName . '+ND';
                                                            
                                                            // Use consistent calculation: hourly rate * multiplier, truncate to 4 decimals, then multiply by minutes
                                                            $actualMinutes = $nightDiffRegularHours * 60;
                                                            $roundedMinutes = round($actualMinutes);
                                                            $adjustedHourlyRate = $hourlyRate * $combinedMultiplier;
                                                            $ratePerMinute = $adjustedHourlyRate / 60; // Truncate to 4 decimals
                                                            $ndAmount = round($ratePerMinute * $roundedMinutes, 2); // Round final amount to 2 decimals
                                                            
                                                            $ndPercentageDisplay = number_format($combinedMultiplier * 100, 0) . '%';
                                                            
                                                            if (isset($holidayBreakdown[$ndDisplayName])) {
                                                                $holidayBreakdown[$ndDisplayName]['hours'] += $nightDiffRegularHours;
                                                                $holidayBreakdown[$ndDisplayName]['amount'] += $ndAmount;
                                                            } else {
                                                                $holidayBreakdown[$ndDisplayName] = [
                                                                    'hours' => $nightDiffRegularHours,
                                                                    'amount' => $ndAmount,
                                                                    'rate' => $hourlyRate,
                                                                    'multiplier' => $combinedMultiplier,
                                                                    'percentage' => $ndPercentageDisplay
                                                                ];
                                                            }
                                                            $totalHolidayRegularHours += $nightDiffRegularHours;
                                                            $holidayPay += $ndAmount;
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
                                                <!-- Show individual holiday type breakdowns in consistent order -->
                                                @php
                                                    // Define consistent display order to match draft mode: Special Holiday first, then Regular Holiday
                                                    $orderedHolidayTypes = [
                                                        'Special Holiday',
                                                        'Special Holiday+ND', 
                                                        'Regular Holiday',
                                                        'Regular Holiday+ND'
                                                    ];
                                                    
                                                    // Sort breakdown by the defined order
                                                    $sortedHolidayBreakdown = [];
                                                    foreach ($orderedHolidayTypes as $type) {
                                                        if (isset($holidayBreakdown[$type])) {
                                                            $sortedHolidayBreakdown[$type] = $holidayBreakdown[$type];
                                                        }
                                                    }
                                                    // Add any remaining types not in the ordered list
                                                    foreach ($holidayBreakdown as $type => $data) {
                                                        if (!isset($sortedHolidayBreakdown[$type])) {
                                                            $sortedHolidayBreakdown[$type] = $data;
                                                        }
                                                    }
                                                @endphp
                                                @foreach($sortedHolidayBreakdown as $type => $data)
                                                    <div class="text-xs text-gray-500 mb-1">
                                                        <span>{{ $type }}: {{ isset($data['minutes']) ? number_format($data['minutes'], 0) . 'm' : number_format($data['hours'] * 60, 0) . 'm' }}</span>
                                                        <div class="text-xs text-gray-600">
                                                            {{ $data['percentage'] }} = ₱{{ number_format($data['amount'], 2) }}
                                                        </div>
                                                    </div>
                                                @endforeach
                                                
                                                <div class="text-xs border-t pt-1">
                                                    <?php 
                                                        // Round total minutes properly without adding extra 0.5
                                                        $totalMinutes = round($totalHolidayRegularHours * 60);
                                                        $hours = intval($totalMinutes / 60);
                                                        $minutes = $totalMinutes % 60;
                                                    ?>
                                                    <div class="text-gray-500">Total: {{ $hours }}h {{ $minutes }}m</div>
                                                </div>
                                            @else
                                                <div class="text-gray-400">0h 0m</div>
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
                                                // DRAFT: Use timeBreakdowns data like Overtime column
                                                $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
                                                $hourlyRate = $detail->hourly_rate ?? 0; // Use calculated hourly rate from detail
                                                $restDayPay = 0;
                                                
                                                // Get night differential settings for dynamic rate
                                                $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
                                                $nightDiffMultiplier = $nightDiffSetting ? $nightDiffSetting->rate_multiplier : 1.10;
                                                
                                                // Rest day hours - split into regular and regular+ND
                                                if (isset($employeeBreakdown['rest_day'])) {
                                                    $regularHours = $employeeBreakdown['rest_day']['regular_hours'] ?? 0;
                                                    $nightDiffRegularHours = $employeeBreakdown['rest_day']['night_diff_regular_hours'] ?? 0;
                                                    
                                                    $rateConfig = $employeeBreakdown['rest_day']['rate_config'];
                                                    $displayName = $rateConfig ? $rateConfig->display_name : 'Rest Day';
                                                    $regularMultiplier = $rateConfig ? $rateConfig->regular_rate_multiplier : 1.2;
                                                    
                                                    // Rest Day (without ND)
                                                    if ($regularHours > 0) {
                                                        // Use consistent calculation: hourly rate * multiplier, truncate to 4 decimals, then multiply by minutes
                                                        $actualMinutes = $regularHours * 60;
                                                        $roundedMinutes = round($actualMinutes);
                                                        $adjustedHourlyRate = $hourlyRate * $regularMultiplier;
                                                        $ratePerMinute = $adjustedHourlyRate / 60; // Truncate to 4 decimals
                                                        $amount = round($ratePerMinute * $roundedMinutes, 2); // Round final amount to 2 decimals
                                                        
                                                        $percentageDisplay = number_format($regularMultiplier * 100, 0) . '%';
                                                        
                                                        $restDayBreakdown[$displayName] = [
                                                            'hours' => $regularHours,
                                                            'amount' => $amount,
                                                            'rate' => $hourlyRate,
                                                            'multiplier' => $regularMultiplier,
                                                            'percentage' => $percentageDisplay
                                                        ];
                                                        $totalRestRegularHours += $regularHours;
                                                        $restDayPay += $amount;
                                                    }
                                                    
                                                    // Rest Day + ND
                                                    if ($nightDiffRegularHours > 0) {
                                                        // Combined rate: rest day rate + night differential bonus
                                                        $combinedMultiplier = $regularMultiplier + ($nightDiffMultiplier - 1);
                                                        
                                                        // Use consistent calculation: hourly rate * multiplier, truncate to 4 decimals, then multiply by minutes
                                                        $actualMinutes = $nightDiffRegularHours * 60;
                                                        $roundedMinutes = round($actualMinutes);
                                                        $adjustedHourlyRate = $hourlyRate * $combinedMultiplier;
                                                        $ratePerMinute = $adjustedHourlyRate / 60; // Truncate to 4 decimals
                                                        $ndAmount = round($ratePerMinute * $roundedMinutes, 2); // Round final amount to 2 decimals
                                                        
                                                        $ndPercentageDisplay = number_format($combinedMultiplier * 100, 0) . '%';
                                                        
                                                        $restDayBreakdown['Rest Day+ND'] = [
                                                            'hours' => $nightDiffRegularHours,
                                                            'amount' => $ndAmount,
                                                            'rate' => $hourlyRate,
                                                            'multiplier' => $combinedMultiplier,
                                                            'percentage' => $ndPercentageDisplay
                                                        ];
                                                        $totalRestRegularHours += $nightDiffRegularHours;
                                                        $restDayPay += $ndAmount;
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
                                                       
                                                            <span>{{ $type }}: {{ isset($data['minutes']) ? number_format($data['minutes'], 0) . 'm' : number_format($data['hours'] * 60, 0) . 'm' }}</span>
                                                     
                                                        <div class="text-xs text-gray-600">
                                                            {{ $data['percentage'] }} = ₱{{ number_format($data['amount'], 2) }}
                                                        </div>
                                                    </div>
                                                @endforeach
                                                
                                                <div class="text-xs border-t pt-1">
                                                    <?php 
                                                        // Round total minutes properly without adding extra 0.5
                                                        $totalMinutes = round($totalRestRegularHours * 60);
                                                        $hours = intval($totalMinutes / 60);
                                                        $minutes = $totalMinutes % 60;
                                                    ?>
                                                    <div class="text-gray-500">Total: {{ $hours }}h {{ $minutes }}m</div>
                                                   
                                                </div>
                                            @else
                                                @if($totalRestRegularHours > 0)
                                                    <?php 
                                                        // Round total minutes properly without adding extra 0.5
                                                        $totalMinutes = round($totalRestRegularHours * 60);
                                                        $hours = intval($totalMinutes / 60);
                                                        $minutes = $totalMinutes % 60;
                                                    ?>
                                                    <div class="text-xs text-gray-500">{{ $hours }}h {{ $minutes }}m</div>
                                                @else
                                                    <div class="text-gray-400">0h 0m</div>
                                                @endif
                                            @endif
                                        </div>
                                        <div class="font-bold text-cyan-600 rest-pay-amount" data-rest-amount="{{ $restDayPay }}">₱{{ number_format($restDayPay, 2) }}</div>
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
                                                $hourlyRate = $detail->hourly_rate ?? 0; // Use calculated hourly rate from detail
                                                $calculatedOvertimeTotal = 0;
                                                
                                                // Regular workday overtime - split into regular OT and OT+ND
                                                if (isset($employeeBreakdown['regular_workday'])) {
                                                    $regularOTHours = $employeeBreakdown['regular_workday']['regular_overtime_hours'] ?? 0;
                                                    $nightDiffOTHours = $employeeBreakdown['regular_workday']['night_diff_overtime_hours'] ?? 0;
                                                    $rateConfig = $employeeBreakdown['regular_workday']['rate_config'];
                                                    $overtimeMultiplier = $rateConfig ? ($rateConfig->overtime_rate_multiplier ?? 1.25) : 1.25;
                                                    
                                                    // Get night differential settings for dynamic rate
                                                    $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
                                                    $nightDiffMultiplier = $nightDiffSetting ? $nightDiffSetting->rate_multiplier : 1.10;
                                                    
                                                    // Regular Workday OT (without ND)
                                                    if ($regularOTHours > 0) {
                                                        // Use consistent calculation: hourly rate * multiplier, truncate to 4 decimals, then multiply by minutes
                                                        $actualMinutes = $regularOTHours * 60;
                                                        $roundedMinutes = round($actualMinutes);
                                                        $adjustedHourlyRate = $hourlyRate * $overtimeMultiplier;
                                                        $ratePerMinute = $adjustedHourlyRate / 60; // Truncate to 4 decimals
                                                        $amount = round($ratePerMinute * $roundedMinutes, 2); // Round final amount to 2 decimals
                                                        
                                                        $overtimeBreakdown[] = [
                                                            'name' => 'Regular Workday OT',
                                                            'hours' => $regularOTHours,
                                                            'amount' => $amount,
                                                            'percentage' => number_format($overtimeMultiplier * 100, 0) . '%'
                                                        ];
                                                        $totalOvertimeHours += $regularOTHours;
                                                        $calculatedOvertimeTotal += $amount;
                                                    }
                                                    
                                                    // Regular Workday OT + ND
                                                    if ($nightDiffOTHours > 0) {
                                                        // Combined rate: overtime rate + night differential bonus
                                                        $combinedMultiplier = $overtimeMultiplier + ($nightDiffMultiplier - 1);
                                                        
                                                        // Use consistent calculation: hourly rate * multiplier, truncate to 4 decimals, then multiply by minutes
                                                        $actualMinutes = $nightDiffOTHours * 60;
                                                        $roundedMinutes = round($actualMinutes);
                                                        $adjustedHourlyRate = $hourlyRate * $combinedMultiplier;
                                                        $ratePerMinute = $adjustedHourlyRate / 60; // Truncate to 4 decimals
                                                        $amount = round($ratePerMinute * $roundedMinutes, 2); // Round final amount to 2 decimals
                                                        
                                                        $overtimeBreakdown[] = [
                                                            'name' => 'Regular Workday OT+ND',
                                                            'hours' => $nightDiffOTHours,
                                                            'amount' => $amount,
                                                            'percentage' => number_format($combinedMultiplier * 100, 0) . '%'
                                                        ];
                                                        $totalOvertimeHours += $nightDiffOTHours;
                                                        $calculatedOvertimeTotal += $amount;
                                                    }
                                                }
                                                
                                                // Special holiday overtime - split into regular OT and OT+ND
                                                if (isset($employeeBreakdown['special_holiday'])) {
                                                    $regularOTHours = $employeeBreakdown['special_holiday']['regular_overtime_hours'] ?? 0;
                                                    $nightDiffOTHours = $employeeBreakdown['special_holiday']['night_diff_overtime_hours'] ?? 0;
                                                    $rateConfig = $employeeBreakdown['special_holiday']['rate_config'];
                                                    $overtimeMultiplier = $rateConfig ? ($rateConfig->overtime_rate_multiplier ?? 1.69) : 1.69;
                                                    
                                                    // Get night differential settings for dynamic rate
                                                    $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
                                                    $nightDiffMultiplier = $nightDiffSetting ? $nightDiffSetting->rate_multiplier : 1.10;
                                                    
                                                    // Special Holiday OT (without ND)
                                                    if ($regularOTHours > 0) {
                                                        // Use consistent calculation: hourly rate * multiplier, truncate to 4 decimals, then multiply by minutes
                                                        $actualMinutes = $regularOTHours * 60;
                                                        $roundedMinutes = round($actualMinutes);
                                                        $adjustedHourlyRate = $hourlyRate * $overtimeMultiplier;
                                                        $ratePerMinute = $adjustedHourlyRate / 60; // Truncate to 4 decimals
                                                        $amount = round($ratePerMinute * $roundedMinutes, 2); // Round final amount to 2 decimals
                                                        
                                                        $overtimeBreakdown[] = [
                                                            'name' => 'Special Holiday OT',
                                                            'hours' => $regularOTHours,
                                                            'amount' => $amount,
                                                            'percentage' => number_format($overtimeMultiplier * 100, 0) . '%'
                                                        ];
                                                        $totalOvertimeHours += $regularOTHours;
                                                        $calculatedOvertimeTotal += $amount;
                                                    }
                                                    
                                                    // Special Holiday OT + ND
                                                    if ($nightDiffOTHours > 0) {
                                                        // Combined rate: overtime rate + night differential bonus
                                                        $combinedMultiplier = $overtimeMultiplier + ($nightDiffMultiplier - 1);
                                                        
                                                        // Use consistent calculation: hourly rate * multiplier, truncate to 4 decimals, then multiply by minutes
                                                        $actualMinutes = $nightDiffOTHours * 60;
                                                        $roundedMinutes = round($actualMinutes);
                                                        $adjustedHourlyRate = $hourlyRate * $combinedMultiplier;
                                                        $ratePerMinute = $adjustedHourlyRate / 60; // Truncate to 4 decimals
                                                        $amount = round($ratePerMinute * $roundedMinutes, 2); // Round final amount to 2 decimals
                                                        
                                                        $overtimeBreakdown[] = [
                                                            'name' => 'Special Holiday OT+ND',
                                                            'hours' => $nightDiffOTHours,
                                                            'amount' => $amount,
                                                            'percentage' => number_format($combinedMultiplier * 100, 0) . '%'
                                                        ];
                                                        $totalOvertimeHours += $nightDiffOTHours;
                                                        $calculatedOvertimeTotal += $amount;
                                                    }
                                                }
                                                
                                                // Regular holiday overtime - split into regular OT and OT+ND
                                                if (isset($employeeBreakdown['regular_holiday'])) {
                                                    $regularOTHours = $employeeBreakdown['regular_holiday']['regular_overtime_hours'] ?? 0;
                                                    $nightDiffOTHours = $employeeBreakdown['regular_holiday']['night_diff_overtime_hours'] ?? 0;
                                                    $rateConfig = $employeeBreakdown['regular_holiday']['rate_config'];
                                                    $overtimeMultiplier = $rateConfig ? ($rateConfig->overtime_rate_multiplier ?? 2.6) : 2.6;
                                                    
                                                    // Get night differential settings for dynamic rate
                                                    $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
                                                    $nightDiffMultiplier = $nightDiffSetting ? $nightDiffSetting->rate_multiplier : 1.10;
                                                    
                                                    // Regular Holiday OT (without ND)
                                                    if ($regularOTHours > 0) {
                                                        // Use consistent calculation: hourly rate * multiplier, truncate to 4 decimals, then multiply by minutes
                                                        $actualMinutes = $regularOTHours * 60;
                                                        $roundedMinutes = round($actualMinutes);
                                                        $adjustedHourlyRate = $hourlyRate * $overtimeMultiplier;
                                                        $ratePerMinute = $adjustedHourlyRate / 60; // Truncate to 4 decimals
                                                        $amount = round($ratePerMinute * $roundedMinutes, 2); // Round final amount to 2 decimals
                                                        
                                                        $overtimeBreakdown[] = [
                                                            'name' => 'Regular Holiday OT',
                                                            'hours' => $regularOTHours,
                                                            'amount' => $amount,
                                                            'percentage' => number_format($overtimeMultiplier * 100, 0) . '%'
                                                        ];
                                                        $totalOvertimeHours += $regularOTHours;
                                                        $calculatedOvertimeTotal += $amount;
                                                    }
                                                    
                                                    // Regular Holiday OT + ND
                                                    if ($nightDiffOTHours > 0) {
                                                        // Combined rate: overtime rate + night differential bonus
                                                        $combinedMultiplier = $overtimeMultiplier + ($nightDiffMultiplier - 1);
                                                        
                                                        // Use consistent calculation: hourly rate * multiplier, truncate to 4 decimals, then multiply by minutes
                                                        $actualMinutes = $nightDiffOTHours * 60;
                                                        $roundedMinutes = round($actualMinutes);
                                                        $adjustedHourlyRate = $hourlyRate * $combinedMultiplier;
                                                        $ratePerMinute = $adjustedHourlyRate / 60; // Truncate to 4 decimals
                                                        $amount = round($ratePerMinute * $roundedMinutes, 2); // Round final amount to 2 decimals
                                                        
                                                        $overtimeBreakdown[] = [
                                                            'name' => 'Regular Holiday OT+ND',
                                                            'hours' => $nightDiffOTHours,
                                                            'amount' => $amount,
                                                            'percentage' => number_format($combinedMultiplier * 100, 0) . '%'
                                                        ];
                                                        $totalOvertimeHours += $nightDiffOTHours;
                                                        $calculatedOvertimeTotal += $amount;
                                                    }
                                                }
                                                
                                                // Rest day overtime - split into regular OT and OT+ND
                                                if (isset($employeeBreakdown['rest_day'])) {
                                                    $regularOTHours = $employeeBreakdown['rest_day']['regular_overtime_hours'] ?? 0;
                                                    $nightDiffOTHours = $employeeBreakdown['rest_day']['night_diff_overtime_hours'] ?? 0;
                                                    $rateConfig = $employeeBreakdown['rest_day']['rate_config'];
                                                    $overtimeMultiplier = $rateConfig ? ($rateConfig->overtime_rate_multiplier ?? 1.69) : 1.69;
                                                    
                                                    // Get night differential settings for dynamic rate
                                                    $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
                                                    $nightDiffMultiplier = $nightDiffSetting ? $nightDiffSetting->rate_multiplier : 1.10;
                                                    
                                                    // Rest Day OT (without ND)
                                                    if ($regularOTHours > 0) {
                                                        // Use consistent calculation: hourly rate * multiplier, truncate to 4 decimals, then multiply by minutes
                                                        $actualMinutes = $regularOTHours * 60;
                                                        $roundedMinutes = round($actualMinutes);
                                                        $adjustedHourlyRate = $hourlyRate * $overtimeMultiplier;
                                                        $ratePerMinute = $adjustedHourlyRate / 60; // Truncate to 4 decimals
                                                        $amount = round($ratePerMinute * $roundedMinutes, 2); // Round final amount to 2 decimals
                                                        
                                                        $overtimeBreakdown[] = [
                                                            'name' => 'Rest Day OT',
                                                            'hours' => $regularOTHours,
                                                            'amount' => $amount,
                                                            'percentage' => number_format($overtimeMultiplier * 100, 0) . '%'
                                                        ];
                                                        $totalOvertimeHours += $regularOTHours;
                                                        $calculatedOvertimeTotal += $amount;
                                                    }
                                                    
                                                    // Rest Day OT + ND
                                                    if ($nightDiffOTHours > 0) {
                                                        // Combined rate: overtime rate + night differential bonus
                                                        $combinedMultiplier = $overtimeMultiplier + ($nightDiffMultiplier - 1);
                                                        
                                                        // Use consistent calculation: hourly rate * multiplier, truncate to 4 decimals, then multiply by minutes
                                                        $actualMinutes = $nightDiffOTHours * 60;
                                                        $roundedMinutes = round($actualMinutes);
                                                        $adjustedHourlyRate = $hourlyRate * $combinedMultiplier;
                                                        $ratePerMinute = $adjustedHourlyRate / 60; // Truncate to 4 decimals
                                                        $amount = round($ratePerMinute * $roundedMinutes, 2); // Round final amount to 2 decimals
                                                        
                                                        $overtimeBreakdown[] = [
                                                            'name' => 'Rest Day OT+ND',
                                                            'hours' => $nightDiffOTHours,
                                                            'amount' => $amount,
                                                            'percentage' => number_format($combinedMultiplier * 100, 0) . '%'
                                                        ];
                                                        $totalOvertimeHours += $nightDiffOTHours;
                                                        $calculatedOvertimeTotal += $amount;
                                                    }
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
                                                        <span>{{ $ot['name'] }}: {{ isset($ot['minutes']) ? number_format($ot['minutes'], 0) . 'm' : number_format($ot['hours'] * 60, 0) . 'm' }}</span>
                                                        <div class="text-xs text-gray-600">
                                                            {{ $ot['percentage'] }} = ₱{{ number_format($ot['amount'], 2) }}
                                                        </div>
                                                    </div>
                                                @endforeach
                                                
                                                <div class="text-xs border-t pt-1">
                                                    <?php 
                                                        // Round total minutes properly without adding extra 0.5
                                                        $totalMinutes = round($totalOvertimeHours * 60);
                                                        $hours = intval($totalMinutes / 60);
                                                        $minutes = $totalMinutes % 60;
                                                    ?>
                                                    <div class="text-gray-500">Total: {{ $hours }}h {{ $minutes }}m</div>
                                                </div>
                                            @else
                                                <div class="text-gray-400">0h 0m</div>
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
                                            $basicPayForGross = round($basicPay, 2); // Use rounded value to match display
                                            
                                            // Handle holiday pay - use the same calculation as the Holiday column for consistency
                                            $holidayPayForGross = round($holidayPay, 2); // Use rounded value to match display
                                            
                                            // Handle rest pay - use the same calculation as the Rest column for consistency
                                            $restPayForGross = round($restDayPay, 2); // Use rounded value to match display
                                            
                            // Handle overtime pay - use the SAME variable that was calculated in the Overtime column
                                            // DO NOT recalculate, just use the $overtimePay that was already computed above
                                            // This ensures 100% consistency between Overtime column and Gross Pay breakdown
                                            
                                            // Round overtime pay to match display precision
                                            $overtimePay = round($overtimePay, 2);
                                            
                                            // Calculate gross pay using stored snapshot value or dynamic calculation
                                            if ($payroll->status !== 'draft' && $employeeSnapshot && isset($employeeSnapshot->gross_pay)) {
                                                // For processing/approved payrolls, ALWAYS use stored gross pay from snapshot
                                                // This ensures gross pay is locked and doesn't change when settings are modified
                                                $calculatedGrossPay = $employeeSnapshot->gross_pay;
                                            } else {
                                                // For draft payrolls or if no valid snapshot data, calculate gross pay dynamically
                                                $calculatedGrossPay = $basicPayForGross + $holidayPayForGross + $restPayForGross + $overtimePay + $allowances + $bonuses;
                                            }
                                        @endphp
                                        
                                        <!-- Show Gross Pay Breakdown -->
                                        <div class="space-y-1">
                                            @if($calculatedGrossPay > 0)
                                               
                                                    @if($basicPayForGross > 0)
                                                        <div class="text-xs text-gray-500">
                                                            <span>Regular:</span>
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
                                            @php
                                                // Calculate taxable income using stored snapshot value or dynamic calculation
                                                if ($payroll->status !== 'draft' && $employeeSnapshot && isset($employeeSnapshot->taxable_income)) {
                                                    // For processing/approved payrolls, ALWAYS use stored taxable income from snapshot
                                                    // This ensures taxable income is locked and doesn't change when settings are modified
                                                    $taxableIncome = $employeeSnapshot->taxable_income;
                                                } else {
                                                    // For draft payrolls or if no valid snapshot data, calculate taxable income dynamically
                                                    // Same calculation as PayrollDetail.getTaxableIncomeAttribute()
                                                    // NOTE: Night differential amounts are already embedded in basic, holiday, and rest pay
                                                    // through the breakdown calculations (Regular Workday+ND, Holiday+ND, etc.)
                                                    $taxableIncome = $basicPayForGross + $holidayPayForGross + $restPayForGross + $overtimePay;
                                                    
                                                    // Add taxable allowances/bonuses from settings
                                                    $allSettings = collect();
                                                    if (isset($allowanceSettings) && $allowanceSettings->isNotEmpty()) {
                                                        $allSettings = $allSettings->merge($allowanceSettings);
                                                    }
                                                    if (isset($bonusSettings) && $bonusSettings->isNotEmpty()) {
                                                        $allSettings = $allSettings->merge($bonusSettings);
                                                    }
                                                    
                                                    // Add only taxable allowances/bonuses
                                                    if ($allSettings->isNotEmpty()) {
                                                        foreach($allSettings as $setting) {
                                                            // Only add if this setting is taxable
                                                            if (!$setting->is_taxable) {
                                                                continue;
                                                            }
                                                            
                                                            $calculatedAmount = 0;
                                                            
                                                            // Calculate the amount based on the setting type
                                                            if($setting->calculation_type === 'percentage') {
                                                                $calculatedAmount = ($basicPayForGross * $setting->rate_percentage) / 100;
                                                            } elseif($setting->calculation_type === 'fixed_amount') {
                                                                $calculatedAmount = $setting->fixed_amount;
                                                                
                                                                // Apply frequency-based calculation for daily allowances
                                                                if ($setting->frequency === 'daily') {
                                                                    // Use same working days calculation as in allowances column
                                                                    $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
                                                                    $workingDays = 0;
                                                                    
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
                                                                    
                                                                    $maxDays = $setting->max_days_per_period ?? $workingDays;
                                                                    $applicableDays = min($workingDays, $maxDays);
                                                                    
                                                                    $calculatedAmount = $setting->fixed_amount * $applicableDays;
                                                                }
                                                            } elseif($setting->calculation_type === 'daily_rate_multiplier') {
                                                                $dailyRate = $detail->employee->daily_rate ?? 0;
                                                                $multiplier = $setting->multiplier ?? 1;
                                                                $calculatedAmount = $dailyRate * $multiplier;
                                                            }
                                                            
                                                            // Apply limits
                                                            if ($setting->minimum_amount && $calculatedAmount < $setting->minimum_amount) {
                                                                $calculatedAmount = $setting->minimum_amount;
                                                            }
                                                            if ($setting->maximum_amount && $calculatedAmount > $setting->maximum_amount) {
                                                                $calculatedAmount = $setting->maximum_amount;
                                                            }
                                                            
                                                            // Add taxable allowance/bonus to taxable income
                                                            $taxableIncome += $calculatedAmount;
                                                        }
                                                    }
                                                    
                                                    $taxableIncome = max(0, $taxableIncome);
                                                }
                                            @endphp
                                          
                                            <div class="font-medium text-green-600 gross-pay-amount" data-gross-amount="{{ $calculatedGrossPay }}">₱{{ number_format($calculatedGrossPay, 2) }}</div>
                                              <div class="text-xs text-gray-500 taxable-income-amount" data-taxable-amount="{{ $taxableIncome }}">Taxable: ₱{{ number_format($taxableIncome, 2) }}</div>
                                          
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
                                                        @if($amount > 0)
                                                            <div class="text-xs text-gray-500">
                                                                <span>{{ $deductionData['name'] ?? $code }}:</span>
                                                                <span>₱{{ number_format($amount, 2) }}</span>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                @elseif(isset($isDynamic) && $isDynamic && $deductionSettings->isNotEmpty())
                                                    @php $hasBreakdown = true; @endphp
                                                    <!-- Show Active Deduction Settings with Calculated Amounts -->
                                                    @foreach($deductionSettings as $setting)
                                                        @php
                                                            // Calculate actual deduction amount for this employee
                                                            $basicPay = $payBreakdownByEmployee[$detail->employee_id]['basic_pay'] ?? $detail->basic_pay ?? 0;
                                                            // Use the CALCULATED gross pay from the Gross Pay column instead of stored value
                                                            $grossPayForDeduction = $calculatedGrossPay;
                                                            $overtimePay = $detail->overtime_pay ?? 0;
                                                            $allowances = $detail->allowances ?? 0;
                                                            $bonuses = $detail->bonuses ?? 0;
                                                            
                                                            // Auto-detect pay frequency from payroll period
                                                            $payFrequency = 'semi_monthly'; // default
                                                            $periodDays = $payroll->period_start->diffInDays($payroll->period_end) + 1;
                                                            if ($periodDays <= 1) {
                                                                $payFrequency = 'daily';
                                                            } elseif ($periodDays <= 7) {
                                                                $payFrequency = 'weekly';
                                                            } elseif ($periodDays <= 16) {
                                                                $payFrequency = 'semi_monthly';
                                                            } else {
                                                                $payFrequency = 'monthly';
                                                            }
                                                            
                                            // Use the calculated taxable income from the previous column
                                            $calculatedAmount = $setting->calculateDeduction(
                                                $basicPay, 
                                                $overtimePay, 
                                                $bonuses, 
                                                $allowances, 
                                                $grossPayForDeduction,
                                                $taxableIncome,  // Pass calculated taxable income
                                                null, // netPay (not used for now)
                                                $detail->employee->calculateMonthlyBasicSalary($payroll->period_start, $payroll->period_end), // monthlyBasicSalary - DYNAMIC
                                                $payFrequency // Pass auto-detected pay frequency
                                            );
                                            
                                            // Apply deduction distribution logic to match backend calculations
                                            if ($calculatedAmount > 0) {
                                                $calculatedAmount = $setting->calculateDistributedAmount(
                                                    $calculatedAmount,
                                                    $payroll->period_start,
                                                    $payroll->period_end,
                                                    $detail->employee->pay_schedule ?? $payFrequency
                                                );
                                            }
                                            
                                            $calculatedDeductionTotal += $calculatedAmount;                                                            // Debug info for PhilHealth only
                                                            $debugInfo = '';
                                                            if (strtolower($setting->name) === 'philhealth' || strtolower($setting->code) === 'philhealth') {
                                                                // Get the pay basis being used by this setting
                                                                $payBasisDebug = '';
                                                                if ($setting->apply_to_basic_pay) $payBasisDebug .= 'Basic Pay ';
                                                                if ($setting->apply_to_gross_pay) $payBasisDebug .= 'Gross Pay ';
                                                                if ($setting->apply_to_taxable_income) $payBasisDebug .= 'Taxable Income ';
                                                                if ($setting->apply_to_monthly_basic_salary) $payBasisDebug .= 'Monthly Basic ';
                                                                if ($setting->apply_to_net_pay) $payBasisDebug .= 'Net Pay ';
                                                                
                                                                $salaryUsed = 0;
                                                                if ($setting->apply_to_basic_pay) $salaryUsed = $basicPay;
                                                                elseif ($setting->apply_to_gross_pay) $salaryUsed = $grossPayForDeduction;
                                                                elseif ($setting->apply_to_taxable_income) $salaryUsed = $taxableIncome;
                                                                elseif ($setting->apply_to_monthly_basic_salary) $salaryUsed = $detail->employee->calculateMonthlyBasicSalary($payroll->period_start, $payroll->period_end);
                                                                elseif ($setting->apply_to_net_pay) $salaryUsed = 0; // calculated later
                                                                
                                                                // Find matching tax table using correct column names
                                                                $taxTable = null;
                                                                if ($setting->tax_table_type === 'philhealth') {
                                                                    $taxTable = \App\Models\PhilHealthTaxTable::where('range_start', '<=', $salaryUsed)
                                                                        ->where('range_end', '>=', $salaryUsed)
                                                                        ->first();
                                                                }
                                                            
                                                            }
                                                        @endphp
                                                        @if($calculatedAmount > 0)
                                                            <div class="text-xs text-gray-500">
                                                                <span>{{ $setting->name }}:</span>
                                                                <span>₱{{ number_format($calculatedAmount, 2) }}</span>
                                                                {!! $debugInfo !!}
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                    
                                                    <!-- Show Cash Advance Deductions for Dynamic Payroll -->
                                                    @if($detail->cash_advance_deductions > 0)
                                                        @php $calculatedDeductionTotal += $detail->cash_advance_deductions; @endphp
                                                        <div class="text-xs text-gray-500">
                                                            <span>CA:</span>
                                                            <span>₱{{ number_format($detail->cash_advance_deductions, 2) }}</span>
                                                        </div>
                                                    @endif
                                                @elseif(!isset($isDynamic) || !$isDynamic || $deductionSettings->isEmpty())
                                                    @php $hasBreakdown = true; @endphp
                                                    <!-- Show Traditional Breakdown for snapshot/non-dynamic payrolls or when no deduction settings -->
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
                                                <div class="font-medium text-red-600 deduction-amount" data-deduction-amount="{{ $calculatedDeductionTotal > 0 ? $calculatedDeductionTotal : $detail->total_deductions }}">
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
                                                $hourlyRate = $detail->hourly_rate ?? 0; // Use calculated hourly rate from detail
                                                
                                                if (isset($employeeBreakdown['rest_day'])) {
                                                    $restBreakdown = $employeeBreakdown['rest_day'];
                                                    $rateConfig = $restBreakdown['rate_config'];
                                                    if ($rateConfig) {
                                                        $regularMultiplier = $rateConfig->regular_rate_multiplier ?? 1.3;
                                                        $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 1.69;
                                                        
                                                        // Apply per-minute calculation for rest day pay (same as Rest column)
                                                        $timeLogInstance = new \App\Models\TimeLog();
                                                        $regularRestPay = $timeLogInstance->calculatePerMinuteAmount($hourlyRate, $regularMultiplier, ($restBreakdown['regular_hours'] ?? 0));
                                                        $overtimeRestPay = $timeLogInstance->calculatePerMinuteAmount($hourlyRate, $overtimeMultiplier, ($restBreakdown['overtime_hours'] ?? 0));
                                                        
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
                                                $hourlyRate = $detail->hourly_rate ?? 0; // Use calculated hourly rate from detail
                                                
                                                // Calculate overtime for regular workdays
                                                if (isset($employeeBreakdown['regular_workday'])) {
                                                    $regularBreakdown = $employeeBreakdown['regular_workday'];
                                                    $overtimeHours = $regularBreakdown['overtime_hours'] ?? 0;
                                                    $rateConfig = $regularBreakdown['rate_config'];
                                                    if ($rateConfig && $overtimeHours > 0) {
                                                        $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 1.25;
                                                        
                                                        // Apply per-minute calculation for overtime (same as Overtime column)
                                                        $timeLogInstance = new \App\Models\TimeLog();
                                                        $overtimePayForNet += $timeLogInstance->calculatePerMinuteAmount($hourlyRate, $overtimeMultiplier, $overtimeHours);
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
                                                        $timeLogInstance = new \App\Models\TimeLog();
                                                        $overtimePayForNet += $timeLogInstance->calculatePerMinuteAmount($hourlyRate, $overtimeMultiplier, $overtimeHours);
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
                                                        $timeLogInstance = new \App\Models\TimeLog();
                                                            $overtimePayForNet += $timeLogInstance->calculatePerMinuteAmount($hourlyRate, $overtimeMultiplier, $overtimeHours);
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
                                                        $timeLogInstance = new \App\Models\TimeLog();
                                                        $overtimePayForNet += $timeLogInstance->calculatePerMinuteAmount($hourlyRate, $overtimeMultiplier, $overtimeHours);
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
                                            
                                            // For processing/approved payrolls with snapshots, use the EXACT SAME logic as deduction column
                                            if (!isset($isDynamic) || !$isDynamic) {
                                                // Use snapshot data - EXACT SAME logic as deduction column
                                                if (isset($detail->deduction_breakdown) && is_array($detail->deduction_breakdown) && !empty($detail->deduction_breakdown)) {
                                                    // Sum up snapshot breakdown amounts
                                                    foreach ($detail->deduction_breakdown as $deduction) {
                                                        $detailDeductionTotal += $deduction['amount'] ?? 0;
                                                    }
                                                } elseif($employeeSnapshot && $employeeSnapshot->deductions_breakdown) {
                                                    // Use employee snapshot breakdown
                                                    $deductionsBreakdown = is_string($employeeSnapshot->deductions_breakdown) 
                                                        ? json_decode($employeeSnapshot->deductions_breakdown, true) 
                                                        : $employeeSnapshot->deductions_breakdown;
                                                    if (is_array($deductionsBreakdown)) {
                                                        foreach($deductionsBreakdown as $code => $deductionData) {
                                                            $amount = $deductionData['amount'] ?? $deductionData;
                                                            $detailDeductionTotal += $amount;
                                                        }
                                                    }
                                                } else {
                                                    // Fallback to individual fields - SAME as deduction column
                                                    $detailDeductionTotal += $detail->sss_contribution ?? 0;
                                                    $detailDeductionTotal += $detail->philhealth_contribution ?? 0;
                                                    $detailDeductionTotal += $detail->pagibig_contribution ?? 0;
                                                    $detailDeductionTotal += $detail->withholding_tax ?? 0;
                                                    $detailDeductionTotal += $detail->cash_advance_deductions ?? 0;
                                                    $detailDeductionTotal += $detail->other_deductions ?? 0;
                                                }
                                            } elseif(isset($isDynamic) && $isDynamic && isset($deductionSettings) && $deductionSettings->isNotEmpty()) {
                                                // Use dynamic calculation with SAME variables as deduction column
                                                foreach($deductionSettings as $setting) {
                                                    // Use same variable mapping as deduction column calculation
                                                    $basicPayForDeduction = $payBreakdownByEmployee[$detail->employee_id]['basic_pay'] ?? $detail->basic_pay ?? 0;
                                                    // Use the CALCULATED gross pay from the Gross Pay column instead of stored value
                                                    $grossPayForDeduction = $calculatedGrossPay;
                                                    $overtimePayForDeduction = $detail->overtime_pay ?? 0;
                                                    $allowancesForDeduction = $detail->allowances ?? 0;
                                                    $bonuses = $detail->bonuses ?? 0;
                                                    
                                                    // Auto-detect pay frequency from payroll period (same logic as deduction column)
                                                    $payFrequency = 'semi_monthly'; // default
                                                    $periodDays = $payroll->period_start->diffInDays($payroll->period_end) + 1;
                                                    if ($periodDays <= 1) {
                                                        $payFrequency = 'daily';
                                                    } elseif ($periodDays <= 7) {
                                                        $payFrequency = 'weekly';
                                                    } elseif ($periodDays <= 16) {
                                                        $payFrequency = 'semi_monthly';
                                                    } else {
                                                        $payFrequency = 'monthly';
                                                    }
                                                    
                                                    $calculatedAmount = $setting->calculateDeduction(
                                                        $basicPayForDeduction, 
                                                        $overtimePayForDeduction, 
                                                        $bonuses, 
                                                        $allowancesForDeduction, 
                                                        $grossPayForDeduction,
                                                        $taxableIncome,  // Pass calculated taxable income
                                                        null, // netPay (not used for now)
                                                        $detail->employee->calculateMonthlyBasicSalary($payroll->period_start, $payroll->period_end), // monthlyBasicSalary - DYNAMIC
                                                        $payFrequency // Pass auto-detected pay frequency
                                                    );
                                                    
                                                    // Apply deduction distribution logic to match backend calculations
                                                    if ($calculatedAmount > 0) {
                                                        $calculatedAmount = $setting->calculateDistributedAmount(
                                                            $calculatedAmount,
                                                            $payroll->period_start,
                                                            $payroll->period_end,
                                                            $detail->employee->pay_schedule ?? $payFrequency
                                                        );
                                                    }
                                                    
                                                    $detailDeductionTotal += $calculatedAmount;
                                                }
                                                // Add cash advance deductions for dynamic payrolls
                                                $detailDeductionTotal += $detail->cash_advance_deductions ?? 0;
                                            } else {
                                                // Use stored values for non-dynamic payrolls - EXACT SAME logic as deduction column
                                                $detailDeductionTotal += $detail->sss_contribution ?? 0;
                                                $detailDeductionTotal += $detail->philhealth_contribution ?? 0;
                                                $detailDeductionTotal += $detail->pagibig_contribution ?? 0;
                                                $detailDeductionTotal += $detail->withholding_tax ?? 0;
                                                $detailDeductionTotal += $detail->cash_advance_deductions ?? 0;
                                                $detailDeductionTotal += $detail->other_deductions ?? 0;
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
                    
                    // Calculate and update Total Rest (matches Rest column)
                    const restPayElements = document.querySelectorAll('.rest-pay-amount');
                    let totalRest = 0;
                    restPayElements.forEach(function(element) {
                        const restAmount = parseFloat(element.getAttribute('data-rest-amount')) || 0;
                        totalRest += restAmount;
                    });
                    const totalRestDisplay = document.getElementById('totalRestDisplay');
                    if (totalRestDisplay) {
                        totalRestDisplay.textContent = formatCurrency(totalRest);
                    }
                    
                    // Calculate and update Total Deductions (matches Deductions column)
                    const deductionElements = document.querySelectorAll('.deduction-amount');
                    let totalDeductions = 0;
                    deductionElements.forEach(function(element) {
                        const deductionAmount = parseFloat(element.getAttribute('data-deduction-amount')) || 0;
                        totalDeductions += deductionAmount;
                    });
                    const totalDeductionsDisplay = document.getElementById('totalDeductionsDisplay');
                    if (totalDeductionsDisplay) {
                        totalDeductionsDisplay.textContent = formatCurrency(totalDeductions);
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
                                @php
                                    $firstEmployee = $payroll->payrollDetails->first();
                                    $hasSchedule = $firstEmployee && $firstEmployee->employee && $firstEmployee->employee->timeSchedule;
                                @endphp
                                
                                @if(!$hasSchedule)
                                    {{-- No schedule assigned - lock the button --}}
                                    <span class="bg-gray-400 text-white font-bold py-2 px-4 rounded text-sm flex items-center cursor-not-allowed opacity-50" 
                                          title="Employee has no time schedule assigned">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                        No Schedule Assigned
                                    </span>
                                @elseif($payroll->payrollDetails->isNotEmpty() && $payroll->status === 'draft')
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
                    
                    <!-- DTR Summary Legends -->
                    <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Time Period Legend:</h4>
                        <div class="flex flex-wrap gap-4 text-xs">
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full bg-green-600 mr-2"></div>
                                <span class="text-green-600 font-medium">Regular Hours</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full bg-orange-600 mr-2"></div>
                                <span class="text-orange-600 font-medium">Regular OT Hours</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full bg-purple-600 mr-2"></div>
                                <span class="text-purple-600 font-medium">OT + ND Hours</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full bg-blue-600 mr-2"></div>
                                <span class="text-blue-600 font-medium">Regular + ND Hours</span>
                            </div>

                             @php
                            // Get break time description for the first employee (assuming all employees have similar break configuration)
                            $breakTimeDescription = 'Not Set';
                            $firstEmployee = $payroll->payrollDetails->first();
                            
                            if ($firstEmployee && $firstEmployee->employee && $firstEmployee->employee->timeSchedule) {
                                $timeSchedule = $firstEmployee->employee->timeSchedule;
                                
                                // Check if employee has flexible break (break_duration_minutes without fixed times)
                                if ($timeSchedule->break_duration_minutes && $timeSchedule->break_duration_minutes > 0 && !($timeSchedule->break_start && $timeSchedule->break_end)) {
                                    // Flexible break
                                    $breakMinutes = $timeSchedule->break_duration_minutes;
                                    $breakHours = floor($breakMinutes / 60);
                                    $breakMins = $breakMinutes % 60;
                                    
                                    if ($breakHours > 0 && $breakMins > 0) {
                                        $breakTimeDescription = "Flexible {$breakHours}h {$breakMins}m";
                                    } elseif ($breakHours > 0) {
                                        $breakTimeDescription = "Flexible {$breakHours}h";
                                    } else {
                                        $breakTimeDescription = "Flexible {$breakMins}m";
                                    }
                                } elseif ($timeSchedule->break_start && $timeSchedule->break_end) {
                                    // Fixed break - check if any employee has actual break logs
                                    $hasActualBreakLogs = false;
                                    $actualBreakStart = '';
                                    $actualBreakEnd = '';
                                    
                                    // Check the first few time logs to see if there are actual break logs
                                    foreach ($payroll->payrollDetails->take(5) as $detail) {
                                        foreach ($periodDates as $date) {
                                            $timeLogData = $dtrData[$detail->employee_id][$date] ?? null;
                                            if ($timeLogData) {
                                                $timeLog = is_array($timeLogData) ? (object) $timeLogData : $timeLogData;
                                                if (isset($timeLog->break_in) && isset($timeLog->break_out) && $timeLog->break_in && $timeLog->break_out) {
                                                    $hasActualBreakLogs = true;
                                                    $actualBreakStart = \Carbon\Carbon::parse($timeLog->break_in)->format('g:i A');
                                                    $actualBreakEnd = \Carbon\Carbon::parse($timeLog->break_out)->format('g:i A');
                                                    break 2; // Break out of both loops
                                                }
                                            }
                                        }
                                    }
                                    
                                    if ($hasActualBreakLogs) {
                                        // Show actual break logs
                                        $breakTimeDescription = "Fixed {$actualBreakStart} - {$actualBreakEnd}";
                                    } else {
                                        // Show default schedule break times
                                        $defaultBreakStart = $timeSchedule->break_start->format('g:i A');
                                        $defaultBreakEnd = $timeSchedule->break_end->format('g:i A');
                                        $breakTimeDescription = "Fixed {$defaultBreakStart} - {$defaultBreakEnd}";
                                    }
                                } else {
                                    $breakTimeDescription = 'No Break';
                                }
                            }
                        @endphp
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full bg-red-600 mr-2"></div>
                                <span class="text-red-600 font-medium">Break Time: {{$breakTimeDescription}} </span>
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
                                                @php
                                                    // Calculate working days for the payroll period
                                                    $workingDays = $detail->employee->getWorkingDaysForPeriod($payroll->period_start, $payroll->period_end);
                                                    $totalHours = $timeSchedule->total_hours ?? 8; // Get from time schedule or default to 8
                                                @endphp
                                                <div class="text-xs text-gray-600">{{ $daySchedule->days_display }} ({{ $workingDays }}days)</div>
                                                <div class="text-xs text-gray-600">{{ $timeSchedule->time_range_display }} ({{ $totalHours }}h)</div>
                                            @else
                                                <div class="text-xs text-gray-600">No schedule assigned</div>
                                            @endif
                                            <div class="text-xs text-blue-600">
                                                @php
                                                    $employee = $detail->employee;
                                                    $rateDisplay = '';
                                                    
                                                    if($employee->fixed_rate && $employee->rate_type) {
                                                        switch($employee->rate_type) {
                                                            case 'monthly':
                                                                // Monthly rate - show per day (assuming 22 working days)
                                                                $dailyRate = $employee->fixed_rate / 22;
                                                                $rateDisplay = '₱' . number_format($dailyRate, 2) . '/day';
                                                                break;
                                                            case 'semi_monthly':
                                                            case 'semi-monthly':
                                                                // Semi-monthly rate - show per day (assuming 11 working days per semi-month)
                                                                $dailyRate = $employee->fixed_rate / 11;
                                                                $rateDisplay = '₱' . number_format($dailyRate, 2) . '/day';
                                                                break;
                                                            case 'weekly':
                                                                // Weekly rate - show per day (assuming 5 working days)
                                                                $dailyRate = $employee->fixed_rate / 5;
                                                                $rateDisplay = '₱' . number_format($dailyRate, 2) . '/day';
                                                                break;
                                                            case 'daily':
                                                                // Daily rate - show per hour (assuming 8 working hours)
                                                                $hourlyRate = $employee->fixed_rate / 8;
                                                                $rateDisplay = '₱' . number_format($hourlyRate, 2) . '/hr';
                                                                break;
                                                            case 'hourly':
                                                                // Hourly rate - show per minute
                                                                $minuteRate = $employee->fixed_rate / 60;
                                                                $rateDisplay = '₱' . number_format($minuteRate, 4) . '/min';
                                                                break;
                                                            default:
                                                                // Fallback to hourly rate from database if available
                                                                if($detail->hourly_rate) {
                                                                    $rateDisplay = '₱' . number_format($detail->hourly_rate, 2) . '/hr';
                                                                }
                                                                break;
                                                        }
                                                    } elseif($detail->hourly_rate) {
                                                        // Fallback to hourly rate from database
                                                        $rateDisplay = '₱' . number_format($detail->hourly_rate, 2) . '/hr';
                                                    }
                                                @endphp
                                                {{ $rateDisplay }}
                                            </div>
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
                                                
                                                // For DTR Summary, ALWAYS calculate dynamic values with grace periods
                                                // This ensures consistent display between draft and processing payrolls
                                                if ($timeLog->time_in && $timeLog->time_out && $timeLog->remarks !== 'Incomplete Time Record') {
                                                    // Calculate dynamic values on-the-fly for DTR display
                                                    $controller = app(App\Http\Controllers\PayrollController::class);
                                                    $reflection = new ReflectionClass($controller);
                                                    $method = $reflection->getMethod('calculateTimeLogHoursDynamically');
                                                    $method->setAccessible(true);
                                                    $dynamicCalc = $method->invoke($controller, $timeLog);
                                                    
                                                    $regularHours = $dynamicCalc['regular_hours'] ?? 0;
                                                    $overtimeHours = $dynamicCalc['overtime_hours'] ?? 0;
                                                } else {
                                                    // Fallback for incomplete records
                                                    $regularHours = $timeLog->regular_hours ?? 0;
                                                    $overtimeHours = $timeLog->overtime_hours ?? 0;
                                                }
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
                                            // Always use dynamic calculation if available, otherwise stored values
                                            $regularHours = (!$isIncompleteRecord && $timeLog) ? 
                                                (isset($timeLog->dynamic_regular_hours) ? $timeLog->dynamic_regular_hours : ($timeLog->regular_hours ?? 0)) : 0;
                                            $overtimeHours = (!$isIncompleteRecord && $timeLog) ? 
                                                (isset($timeLog->dynamic_overtime_hours) ? $timeLog->dynamic_overtime_hours : ($timeLog->overtime_hours ?? 0)) : 0;
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
                                                        
                                                        // FOR DTR SUMMARY: ALWAYS use the dynamic calculation results to ensure consistency
                                                        // Force dynamic calculation for DTR display regardless of payroll status
                                                        if ($timeLog->time_in && $timeLog->time_out && $timeLog->remarks !== 'Incomplete Time Record') {
                                                            // Calculate dynamic values on-the-fly for DTR display
                                                            $controller = app(App\Http\Controllers\PayrollController::class);
                                                            $reflection = new ReflectionClass($controller);
                                                            $method = $reflection->getMethod('calculateTimeLogHoursDynamically');
                                                            $method->setAccessible(true);
                                                            $dynamicCalc = $method->invoke($controller, $timeLog);
                                                            
                                                            // Always use the dynamic calculation for display hours
                                                            $displayRegularHours = $dynamicCalc['regular_hours'] ?? 0;
                                                            $displayOvertimeHours = $dynamicCalc['overtime_hours'] ?? 0;
                                                            
                                                            // FOR DTR SUMMARY: Always use dynamic calculation for night differential regular hours
                                                            $nightDiffRegularHours = $dynamicCalc['night_diff_regular_hours'] ?? 0;
                                                            
                                                            // Get time period breakdown early for consistent display
                                                            $forceDynamicValues = [
                                                                'regular_hours' => $dynamicCalc['regular_hours'] ?? 0,
                                                                'overtime_hours' => $dynamicCalc['overtime_hours'] ?? 0,
                                                                'regular_overtime_hours' => $dynamicCalc['regular_overtime_hours'] ?? 0,
                                                                'night_diff_overtime_hours' => $dynamicCalc['night_diff_overtime_hours'] ?? 0,
                                                                'night_diff_regular_hours' => $dynamicCalc['night_diff_regular_hours'] ?? 0,
                                                                'overtime_start_time' => $dynamicCalc['overtime_start_time'] ?? null,
                                                            ];
                                                            $timePeriodBreakdown = $timeLog->getTimePeriodBreakdown($forceDynamicValues);
                                                            
                                                            // FOR DTR SUMMARY: Use the accurate overtime start time from dynamic calculation
                                                            if ($displayOvertimeHours > 0 && isset($dynamicCalc['overtime_start_time']) && $dynamicCalc['overtime_start_time']) {
                                                                $regularPeriodEnd = $dynamicCalc['overtime_start_time']->format('g:i A');
                                                            } else {
                                                                // No overtime, regular period ends at employee time out
                                                                $regularPeriodEnd = $timeLog->time_out ? \Carbon\Carbon::parse($timeLog->time_out)->format('g:i A') : 'N/A';
                                                            }
                                                        } else {
                                                            // Fallback for incomplete records
                                                            $displayRegularHours = $regularHours;
                                                            $displayOvertimeHours = $overtimeHours;
                                                            $timePeriodBreakdown = [];
                                                            
                                                            // Fallback for night differential regular hours
                                                            if ($payroll->status === 'draft') {
                                                                $nightDiffRegularHours = $timeLog->dynamic_night_diff_regular_hours ?? 0;
                                                            } else {
                                                                $nightDiffRegularHours = $timeLog->night_diff_regular_hours ?? 0;
                                                            }
                                                        }
                                                    @endphp
                                                    
                                                    <div class="text-green-600 font-medium">
                                                        @php
                                                            // FOR DTR SUMMARY: Display actual time periods based on employee's time in/out and ND boundaries
                                                            $regularStart = $timeLog->time_in ? \Carbon\Carbon::parse($timeLog->time_in)->format('g:i A') : 'N/A';
                                                            $regularEnd = '';
                                                            
                                                            if ($timeLog->time_in && $timeLog->time_out) {
                                                                // Get night differential settings to determine where regular hours end
                                                                $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
                                                                
                                                                if ($nightDiffSetting && $nightDiffSetting->is_active && $nightDiffRegularHours > 0) {
                                                                    // If employee works into ND period, regular hours end at ND start
                                                                    $nightStart = \Carbon\Carbon::parse($timeLog->log_date->format('Y-m-d') . ' ' . $nightDiffSetting->start_time);
                                                                    $regularEnd = $nightStart->format('g:i A');
                                                                } else {
                                                                    // If no ND hours, regular period ends at calculated regular period end (not employee's actual time out)
                                                                    // Use the regularPeriodEnd calculated earlier which considers grace periods and scheduled hours
                                                                    if (is_string($regularPeriodEnd)) {
                                                                        $regularEnd = $regularPeriodEnd;
                                                                    } else {
                                                                        $regularEnd = $regularPeriodEnd->format('g:i A');
                                                                    }
                                                                }
                                                            } else {
                                                                $regularEnd = $regularPeriodEnd;
                                                            }
                                                        @endphp
                                                        {{ $regularStart }} - {{ $regularEnd }}
                                                        @if($displayRegularHours > 0)
                                                            {{-- (regular hours period) --}}
                                                        @endif
                                                        ({{ number_format($displayRegularHours * 60, 0) }}m) {{ floor($displayRegularHours) }}h {{ round(($displayRegularHours - floor($displayRegularHours)) * 60) }}m
                                                    </div>
                                                    
                                                    {{-- Display Night Differential Regular Hours --}}
                                                    @if($nightDiffRegularHours > 0)
                                                    @php
                                                        // Use the time period breakdown data for consistent display
                                                        $nightDiffRegularPeriod = null;
                                                        foreach($timePeriodBreakdown as $period) {
                                                            if ($period['type'] === 'night_diff_regular') {
                                                                $nightDiffRegularPeriod = $period;
                                                                break;
                                                            }
                                                        }
                                                    @endphp
                                                    
                                                    @if($nightDiffRegularPeriod)
                                                        {{-- Display from breakdown data --}}
                                                        <div class="{{ $nightDiffRegularPeriod['color_class'] }} text-xs">
                                                            {{ $nightDiffRegularPeriod['start_time'] }} - {{ $nightDiffRegularPeriod['end_time'] }} ({{ number_format($nightDiffRegularPeriod['hours'] * 60, 0) }}m) {{ floor($nightDiffRegularPeriod['hours']) }}h {{ round(($nightDiffRegularPeriod['hours'] - floor($nightDiffRegularPeriod['hours'])) * 60) }}m
                                                        </div>
                                                    @else
                                                        {{-- Fallback to old calculation if breakdown not available --}}
                                                        @php
                                                            // Calculate night differential regular hours period
                                                            $nightRegularStart = '';
                                                            $nightRegularEnd = '';
                                                            if ($timeLog->time_out && $timeLog->time_in) {
                                                                $workStart = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', 
                                                                    $timeLog->log_date->format('Y-m-d') . ' ' . \Carbon\Carbon::parse($timeLog->time_in)->format('H:i:s'));
                                                                $workEnd = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', 
                                                                    $timeLog->log_date->format('Y-m-d') . ' ' . \Carbon\Carbon::parse($timeLog->time_out)->format('H:i:s'));
                                                                
                                                                // Get night differential settings
                                                                $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
                                                                if ($nightDiffSetting && $nightDiffSetting->is_active) {
                                                                    $nightStart = \Carbon\Carbon::parse($timeLog->log_date->format('Y-m-d') . ' ' . $nightDiffSetting->start_time);
                                                                    $nightEnd = \Carbon\Carbon::parse($timeLog->log_date->format('Y-m-d') . ' ' . $nightDiffSetting->end_time);
                                                                    
                                                                    // Handle next day end time (e.g., 10 PM to 5 AM next day)
                                                                    if ($nightEnd->lte($nightStart)) {
                                                                        $nightEnd->addDay();
                                                                    }
                                                                    
                                                                    // Handle next day night start (if before current day start)
                                                                    if ($nightStart->lt($workStart)) {
                                                                        $nightStart->addDay();
                                                                    }
                                                                    
                                                                    // FOR DTR SUMMARY: Display actual ND period from ND start to employee time out
                                                                    $actualNDStart = $nightStart->format('g:i A');
                                                                    $actualNDEnd = \Carbon\Carbon::parse($timeLog->time_out)->format('g:i A');
                                                                    $nightRegularStart = $actualNDStart;
                                                                    $nightRegularEnd = $actualNDEnd;
                                                                }
                                                            }
                                                        @endphp
                                                        @if($nightRegularStart && $nightRegularEnd)
                                                        <div class="text-blue-600 text-xs">
                                                            {{ $nightRegularStart }} - {{ $nightRegularEnd }} ({{ number_format($nightDiffRegularHours * 60, 0) }}m) {{ floor($nightDiffRegularHours) }}h {{ round(($nightDiffRegularHours - floor($nightDiffRegularHours)) * 60) }}m
                                                        </div>
                                                        @endif
                                                    @endif
                                                    @endif
                                               
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
                                                                $defaultBreakStart = \Carbon\Carbon::parse($timeLog->log_date->format('Y-m-d') . ' ' . $timeSchedule->break_start->format('H:i:s'));
                                                                $defaultBreakEnd = \Carbon\Carbon::parse($timeLog->log_date->format('Y-m-d') . ' ' . $timeSchedule->break_end->format('H:i:s'));
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
                                                            {{ $breakDisplayStart }} - {{ $breakDisplayEnd }} ({{ number_format($breakHours * 60, 0) }}m) {{ floor($breakHours) }}h {{ round(($breakHours - floor($breakHours)) * 60) }}m
                                                        </div>
                                                    @endif
                                                
                                                    @if($displayOvertimeHours > 0)
                                                    @php
                                                        // For DTR Summary, we need to calculate overtime breakdown consistently
                                                        // Use the already calculated values from above
                                                        if ($timeLog->time_in && $timeLog->time_out && $timeLog->remarks !== 'Incomplete Time Record') {
                                                            // Use dynamic calculation results already calculated above
                                                            $regularOvertimeHours = $dynamicCalc['regular_overtime_hours'] ?? 0;
                                                            $nightDiffOvertimeHours = $dynamicCalc['night_diff_overtime_hours'] ?? 0;
                                                        } else {
                                                            // Fallback for incomplete records
                                                            if ($payroll->status === 'draft') {
                                                                $regularOvertimeHours = $timeLog->dynamic_regular_overtime_hours ?? 0;
                                                                $nightDiffOvertimeHours = $timeLog->dynamic_night_diff_overtime_hours ?? 0;
                                                            } else {
                                                                $regularOvertimeHours = $timeLog->regular_overtime_hours ?? 0;
                                                                $nightDiffOvertimeHours = $timeLog->night_diff_overtime_hours ?? 0;
                                                            }
                                                        }
                                                        
                                                        // If breakdown not available, show total
                                                        if ($regularOvertimeHours == 0 && $nightDiffOvertimeHours == 0) {
                                                            $regularOvertimeHours = $displayOvertimeHours;
                                                        }
                                                    @endphp
                                                    
                                                    {{-- Display detailed time periods --}}
                                                    @foreach($timePeriodBreakdown as $period)
                                                        @if($period['type'] === 'regular_overtime' || $period['type'] === 'night_diff_overtime')
                                                        <div class="{{ $period['color_class'] }} text-xs">
                                                            {{ $period['start_time'] }} - {{ $period['end_time'] }} ({{ number_format($period['hours'] * 60, 0) }}ms) {{ floor($period['hours']) }}h {{ round(($period['hours'] - floor($period['hours'])) * 60) }}ms
                                                            @if($period['type'] === 'regular_overtime')
                                                             
                                                            @elseif($period['type'] === 'night_diff_overtime')
                                                          
                                                            @endif
                                                        </div>
                                                        @endif
                                                    @endforeach
                                                    
                                                    {{-- Fallback to old display if no breakdown available --}}
                                                    @if(empty($timePeriodBreakdown) || count($timePeriodBreakdown) <= 1)
                                                        {{-- Display Regular OT (before night differential period) --}}
                                                        @if($regularOvertimeHours > 0)
                                                        @php
                                                            // Use the accurate overtime start time from dynamic calculation
                                                            $regularOTStart = '';
                                                            $regularOTEnd = '';
                                                            
                                                            if ($timeLog->time_out && $timeLog->time_in && isset($dynamicCalc['overtime_start_time']) && $dynamicCalc['overtime_start_time']) {
                                                                // Use the accurate overtime start time from dynamic calculation
                                                                $regularOTStart = $dynamicCalc['overtime_start_time']->format('g:i A');
                                                                
                                                                $workEnd = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', 
                                                                    $timeLog->log_date->format('Y-m-d') . ' ' . \Carbon\Carbon::parse($timeLog->time_out)->format('H:i:s'));
                                                                
                                                                // Use the Carbon instance directly for comparison
                                                                $overtimeStartTime = $dynamicCalc['overtime_start_time'];
                                                                
                                                                // Get night differential settings to determine where regular OT ends
                                                                $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
                                                                if ($nightDiffSetting && $nightDiffSetting->is_active) {
                                                                    $nightStart = \Carbon\Carbon::parse($timeLog->log_date->format('Y-m-d') . ' ' . $nightDiffSetting->start_time);
                                                                    
                                                                    // Handle next day night start (if before current day start)
                                                                    if ($nightStart->lt($overtimeStartTime)) {
                                                                        $nightStart->addDay();
                                                                    }
                                                                    
                                                                    // Regular OT ends at night differential start OR actual time out, whichever is earlier
                                                                    $regularOTEndTime = $nightStart->lessThan($workEnd) ? $nightStart : $workEnd;
                                                                    
                                                                    // Only show regular OT if it starts before the night differential period
                                                                    if ($overtimeStartTime->lessThan($nightStart)) {
                                                                        $regularOTEnd = $regularOTEndTime->format('g:i A');
                                                                    } else {
                                                                        // If overtime starts during ND period, no regular OT to show
                                                                        $regularOTStart = '';
                                                                        $regularOTEnd = '';
                                                                    }
                                                                } else {
                                                                    // No night differential - regular OT goes to actual time_out
                                                                    $regularOTEnd = $workEnd->format('g:i A');
                                                                }
                                                            }
                                                        @endphp
                                                        @if($regularOTStart && $regularOTEnd)
                                                        <div class="text-orange-600 text-xs">
                                                            {{ $regularOTStart }} - {{ $regularOTEnd }} ({{ number_format($regularOvertimeHours * 60, 0) }}m) {{ floor($regularOvertimeHours) }}h {{ round(($regularOvertimeHours - floor($regularOvertimeHours)) * 60) }}m
                                                        </div>
                                                    
                                                        @endif
                                                        @endif
                                                        
                                                        {{-- Display Night Differential OT (during ND period) --}}
                                                        @if($nightDiffOvertimeHours > 0)
                                                        @php
                                                            // Use the accurate overtime start time from dynamic calculation
                                                            $nightOTStart = '';
                                                            $nightOTEnd = '';
                                                            
                                                            if ($timeLog->time_out && $timeLog->time_in && isset($dynamicCalc['overtime_start_time']) && $dynamicCalc['overtime_start_time']) {
                                                                $workEnd = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', 
                                                                    $timeLog->log_date->format('Y-m-d') . ' ' . \Carbon\Carbon::parse($timeLog->time_out)->format('H:i:s'));
                                                                
                                                                // Use the Carbon instance directly from dynamic calculation
                                                                $overtimeStartTime = $dynamicCalc['overtime_start_time'];
                                                                
                                                                // Get night differential settings to determine ND period
                                                                $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
                                                                if ($nightDiffSetting && $nightDiffSetting->is_active) {
                                                                    $nightStart = \Carbon\Carbon::parse($timeLog->log_date->format('Y-m-d') . ' ' . $nightDiffSetting->start_time);
                                                                    $nightEnd = \Carbon\Carbon::parse($timeLog->log_date->format('Y-m-d') . ' ' . $nightDiffSetting->end_time);
                                                                    
                                                                    // Handle next day end time (e.g., 10 PM to 5 AM next day)
                                                                    if ($nightEnd->lte($nightStart)) {
                                                                        $nightEnd->addDay();
                                                                    }
                                                                    
                                                                    // Handle next day night start (if before current day start)
                                                                    if ($nightStart->lt($overtimeStartTime)) {
                                                                        $nightStart->addDay();
                                                                    }
                                                                    
                                                                    // ND OT starts at the later of: overtime start OR night differential start
                                                                    $ndOTStartTime = $overtimeStartTime->greaterThan($nightStart) ? $overtimeStartTime : $nightStart;
                                                                    
                                                                    // ND OT ends at the earlier of: work end OR night differential end
                                                                    $ndOTEndTime = $workEnd->lessThan($nightEnd) ? $workEnd : $nightEnd;
                                                                    
                                                                    // Only show if there's actual ND OT period
                                                                    if ($ndOTStartTime->lessThan($ndOTEndTime)) {
                                                                        $nightOTStart = $ndOTStartTime->format('g:i A');
                                                                        $nightOTEnd = $ndOTEndTime->format('g:i A');
                                                                    }
                                                                }
                                                            }
                                                        @endphp
                                                        @if($nightOTStart && $nightOTEnd)
                                                        <div class="text-purple-600 text-xs">
                                                            {{ $nightOTStart }} - {{ $nightOTEnd }} ({{ number_format($nightDiffOvertimeHours * 60, 0) }}m) {{ floor($nightDiffOvertimeHours) }}h {{ round(($nightDiffOvertimeHours - floor($nightDiffOvertimeHours)) * 60) }}m
                                                        </div>
                                                        @endif
                                                        @endif 
                                                        
                                                        {{-- If we have total overtime but no breakdown, show total --}}
                                                        @if($regularOvertimeHours == 0 && $nightDiffOvertimeHours == 0 && $displayOvertimeHours > 0)
                                                        @php
                                                            // Use the accurate overtime start time from dynamic calculation
                                                            $overtimeStart = '';
                                                            $overtimeEnd = '';
                                                            
                                                            if ($timeLog->time_out && $timeLog->time_in && isset($dynamicCalc['overtime_start_time']) && $dynamicCalc['overtime_start_time']) {
                                                                // Use the accurate overtime start time from dynamic calculation
                                                                $overtimeStart = $dynamicCalc['overtime_start_time']->format('g:i A');
                                                                
                                                                $workEnd = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', 
                                                                    $timeLog->log_date->format('Y-m-d') . ' ' . \Carbon\Carbon::parse($timeLog->time_out)->format('H:i:s'));
                                                                
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
                                                            OT: {{ number_format($calculatedOTHours * 60, 0) }}m {{ floor($calculatedOTHours) }}h {{ round(($calculatedOTHours - floor($calculatedOTHours)) * 60) }}m
                                                        </div>
                                                        @else
                                                        <div class="text-orange-600 text-xs">
                                                            OT: {{ number_format($overtimeHours * 60, 0) }}m {{ floor($overtimeHours) }}h {{ round(($overtimeHours - floor($overtimeHours)) * 60) }}m
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

    {{-- Mark as Paid Modal --}}
    <div id="markAsPaidModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden" style="z-index: 50;">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Mark Payroll as Paid</h3>
                    <button type="button" onclick="closeMarkAsPaidModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form id="markAsPaidForm" method="POST" action="{{ route('payrolls.mark-as-paid', $payroll) }}" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Proof (Optional)</label>
                        <input type="file" 
                               name="payment_proof[]" 
                               multiple 
                               accept=".jpg,.jpeg,.png,.pdf"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="text-xs text-gray-500 mt-1">You can upload multiple files (JPG, PNG, PDF). Max 10MB per file.</p>
                    </div>

                    <div class="mb-6">
                        <label for="payment_notes" class="block text-sm font-medium text-gray-700 mb-2">Payment Notes (Optional)</label>
                        <textarea name="payment_notes" 
                                  id="payment_notes" 
                                  rows="3" 
                                  class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Add any notes about the payment..."></textarea>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button type="button" 
                                onclick="closeMarkAsPaidModal()"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 border border-gray-300 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Mark as Paid
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openMarkAsPaidModal() {
            document.getElementById('markAsPaidModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeMarkAsPaidModal() {
            document.getElementById('markAsPaidModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
            // Reset form
            document.getElementById('markAsPaidForm').reset();
        }

        // Close modal when clicking outside
        document.getElementById('markAsPaidModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeMarkAsPaidModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMarkAsPaidModal();
            }
        });
    </script>
</x-app-layout>
