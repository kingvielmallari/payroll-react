<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Create DTR for') }} {{ $selectedEmployee->first_name }} {{ $selectedEmployee->last_name }}
            </h2>
            <div class="text-sm text-gray-600">
                <span class="font-medium">Period:</span> {{ $currentPeriod['period_label'] }}
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <!-- Employee Information -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Employee Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div>
                                <span class="font-medium text-gray-700">Employee:</span>
                                <span class="text-gray-900">{{ $selectedEmployee->first_name }} {{ $selectedEmployee->last_name }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Schedule:</span>
                                <span class="text-gray-900">{{ $selectedEmployee->schedule_display }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Department:</span>
                                <span class="text-gray-900">{{ $selectedEmployee->department->name ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm mt-2">
                            <div>
                                <span class="font-medium text-gray-700">Hourly Rate:</span>
                                <span class="text-blue-600">₱{{ number_format($selectedEmployee->hourly_rate ?? 0, 2) }}/hr</span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Pay Date:</span>
                                <span class="text-gray-900">{{ date('M d, Y', strtotime($periodEnd)) }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Period:</span>
                                <span class="text-gray-900">{{ $currentPeriod['period_label'] }}</span>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('time-logs.store-bulk') }}" method="POST" id="dtr-form">
                        @csrf
                        <input type="hidden" name="employee_id" value="{{ $selectedEmployee->id }}">
                        @if($payrollId)
                            <input type="hidden" name="payroll_id" value="{{ $payrollId }}">
                        @endif
                        @if(isset($schedule))
                            <input type="hidden" name="schedule" value="{{ $schedule }}">
                        @endif
                        <input type="hidden" name="redirect_to_payroll" value="1">

                        <!-- Bulk Actions -->
                        <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                            <h4 class="text-md font-medium text-blue-900 mb-3">Quick Fill Actions</h4>
                            <div class="flex flex-wrap gap-2">
                                @php
                                    $scheduleStart = '08:00'; // Default fallback
                                    $scheduleEnd = '17:00';   // Default fallback
                                    $scheduleBreakStart = '12:00'; // Default break start
                                    $scheduleBreakEnd = '13:00';   // Default break end
                                    
                                    // Determine break type
                                    $isFlexibleBreak = false;
                                    $isFixedBreak = false;
                                    
                                    if ($selectedEmployee->timeSchedule) {
                                        $scheduleStart = \Carbon\Carbon::parse($selectedEmployee->timeSchedule->time_in)->format('H:i');
                                        $scheduleEnd = \Carbon\Carbon::parse($selectedEmployee->timeSchedule->time_out)->format('H:i');
                                        
                                        // Check if employee has flexible break (break_duration_minutes without fixed times)
                                        if ($selectedEmployee->timeSchedule->break_duration_minutes && $selectedEmployee->timeSchedule->break_duration_minutes > 0 && !($selectedEmployee->timeSchedule->break_start && $selectedEmployee->timeSchedule->break_end)) {
                                            $isFlexibleBreak = true;
                                        } elseif ($selectedEmployee->timeSchedule->break_start && $selectedEmployee->timeSchedule->break_end) {
                                            $isFixedBreak = true;
                                            $scheduleBreakStart = \Carbon\Carbon::parse($selectedEmployee->timeSchedule->break_start)->format('H:i');
                                            $scheduleBreakEnd = \Carbon\Carbon::parse($selectedEmployee->timeSchedule->break_end)->format('H:i');
                                        }
                                    }
                                    
                                    $displayStart = \Carbon\Carbon::parse($scheduleStart)->format('g:i A');
                                    $displayEnd = \Carbon\Carbon::parse($scheduleEnd)->format('g:i A');
                                @endphp
                                
                                @if($isFlexibleBreak)
                                    <button type="button" class="px-3 py-1 bg-blue-500 text-white text-sm rounded hover:bg-blue-600" onclick="fillTimeOnly('{{ $scheduleStart }}', '{{ $scheduleEnd }}')">
                                        Fill Time Fields ({{ $displayStart }} - {{ $displayEnd }})
                                    </button>
                                @elseif($isFixedBreak)
                                    <button type="button" class="px-3 py-1 bg-blue-500 text-white text-sm rounded hover:bg-blue-600" onclick="fillRegularHours('{{ $scheduleStart }}', '{{ $scheduleEnd }}', '{{ $scheduleBreakStart }}', '{{ $scheduleBreakEnd }}')">
                                        Fill Time & Break Fields ({{ $displayStart }} - {{ $displayEnd }})
                                    </button>
                                @else
                                    <button type="button" class="px-3 py-1 bg-blue-500 text-white text-sm rounded hover:bg-blue-600" onclick="fillTimeOnly('{{ $scheduleStart }}', '{{ $scheduleEnd }}')">
                                        Fill Time Fields ({{ $displayStart }} - {{ $displayEnd }})
                                    </button>
                                @endif
                                
                                <button type="button" class="px-3 py-1 bg-red-500 text-white text-sm rounded hover:bg-red-600" onclick="resetAll()">
                                    Reset All
                                </button>
                            </div>
                            {{-- <p class="text-xs text-blue-700 mt-2">
                                <strong>Fill Time & Break:</strong> Fills empty fields with employee's scheduled work hours and break times.<br>
                                <strong>Reset All:</strong> Clears all time fields but preserves day types (including suspension days).
                            </p> --}}
                        </div>

                        <!-- Time Log Entries -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 border border-gray-300">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">DATE</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">DAY</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">TIME IN</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">TIME OUT</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">BREAK IN</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">BREAK OUT</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">TYPE</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ACTION</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($dtrData as $index => $day)
                                        @php
                                            $suspensionInfo = $day['suspension_info'] ?? null;
                                            $isPaidSuspension = $suspensionInfo && ($suspensionInfo['is_paid'] ?? false);
                                            $isSuspension = $day['is_suspension'] ?? false;
                                            $employeeHasBenefits = $selectedEmployee->benefits_status === 'with_benefits';
                                            
                                            // Handle suspension auto-fill logic
                                            if ($isSuspension) {
                                                if ($isPaidSuspension && $employeeHasBenefits) {
                                                    // PAID SUSPENSION + WITH BENEFITS: Auto-fill with default schedule times
                                                    $defaultTimeIn = $day['time_in'] ? $day['time_in']->format('H:i') : null;
                                                    $defaultTimeOut = $day['time_out'] ? $day['time_out']->format('H:i') : null;
                                                    $defaultBreakIn = $day['break_in'] ? $day['break_in']->format('H:i') : null;
                                                    $defaultBreakOut = $day['break_out'] ? $day['break_out']->format('H:i') : null;
                                                } else {
                                                    // UNPAID SUSPENSION OR WITHOUT BENEFITS: Clear all time fields (blank)
                                                    $defaultTimeIn = null;
                                                    $defaultTimeOut = null;
                                                    $defaultBreakIn = null;
                                                    $defaultBreakOut = null;
                                                }
                                            } else {
                                                // NOT SUSPENSION: Use existing data as is
                                                $defaultTimeIn = $day['time_in'] ? $day['time_in']->format('H:i') : null;
                                                $defaultTimeOut = $day['time_out'] ? $day['time_out']->format('H:i') : null;
                                                $defaultBreakIn = $day['break_in'] ? $day['break_in']->format('H:i') : null;
                                                $defaultBreakOut = $day['break_out'] ? $day['break_out']->format('H:i') : null;
                                            }
                                        @endphp
                                        <tr class="{{ $day['is_weekend'] ? 'bg-gray-100' : '' }}"
                                            data-is-paid-suspension="{{ $isPaidSuspension ? 'true' : 'false' }}"
                                            data-employee-has-benefits="{{ $employeeHasBenefits ? 'true' : 'false' }}"
                                            data-time-in-default="{{ $defaultTimeIn }}"
                                            data-time-out-default="{{ $defaultTimeOut }}"
                                            data-break-in-default="{{ $defaultBreakIn }}"
                                            data-break-out-default="{{ $defaultBreakOut }}">
                                            <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900 border-r">
                                                {{ $day['date']->format('M d') }}
                                                <input type="hidden" name="time_logs[{{ $index }}][log_date]" value="{{ $day['date']->format('Y-m-d') }}">
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500 border-r">
                                                {{ $day['day_name'] }}
                                                @if($day['date']->isWeekend())
                                                    <span class="text-xs text-blue-600">(Weekend)</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap border-r">
                                                @php
                                                    $isSuspension = ($day['is_suspension'] ?? false);
                                                    $isActiveHoliday = ($day['is_holiday_active'] ?? false);
                                                    $isDropdownDisabled = $isSuspension || $isActiveHoliday; // Disable dropdown for both suspensions and active holidays
                                                    $isTimeInputDisabled = $isSuspension; // Only disable time inputs for suspensions
                                                    $inputClass = $isSuspension ? 'bg-gray-100 cursor-not-allowed' : '';
                                                @endphp
                                                <input type="time" 
                                                       name="time_logs[{{ $index }}][time_in]" 
                                                       value="{{ $day['time_in'] ? (is_string($day['time_in']) ? (strlen($day['time_in']) >= 5 ? substr($day['time_in'], 0, 5) : $day['time_in']) : $day['time_in']->format('H:i')) : '' }}"
                                                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 {{ $inputClass }} time-input-{{ $index }}"
                                                       data-row="{{ $index }}"
                                                       onchange="calculateHours({{ $index }})"
                                                       {{ $isTimeInputDisabled ? 'disabled' : '' }}>
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap border-r">
                                                <input type="time" 
                                                       name="time_logs[{{ $index }}][time_out]" 
                                                       value="{{ $day['time_out'] ? (is_string($day['time_out']) ? (strlen($day['time_out']) >= 5 ? substr($day['time_out'], 0, 5) : $day['time_out']) : $day['time_out']->format('H:i')) : '' }}"
                                                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 {{ $inputClass }} time-input-{{ $index }}"
                                                       data-row="{{ $index }}"
                                                       onchange="calculateHours({{ $index }})"
                                                       {{ $isTimeInputDisabled ? 'disabled' : '' }}>
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap border-r">
                                                <input type="time" 
                                                       name="time_logs[{{ $index }}][break_in]" 
                                                       value="{{ $day['break_in'] ? (is_string($day['break_in']) ? (strlen($day['break_in']) >= 5 ? substr($day['break_in'], 0, 5) : $day['break_in']) : $day['break_in']->format('H:i')) : '' }}"
                                                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 {{ $inputClass }} time-input-{{ $index }}"
                                                       {{ $isTimeInputDisabled ? 'disabled' : '' }}>
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap border-r">
                                                <input type="time" 
                                                       name="time_logs[{{ $index }}][break_out]" 
                                                       value="{{ $day['break_out'] ? (is_string($day['break_out']) ? (strlen($day['break_out']) >= 5 ? substr($day['break_out'], 0, 5) : $day['break_out']) : $day['break_out']->format('H:i')) : '' }}"
                                                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 {{ $inputClass }} time-input-{{ $index }}"
                                                       {{ $isTimeInputDisabled ? 'disabled' : '' }}>
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap border-r">
                                                <select name="time_logs[{{ $index }}][log_type]" 
                                                        class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500 {{ $isDropdownDisabled ? 'bg-gray-100 cursor-not-allowed' : '' }} log-type-{{ $index }}"
                                                        data-row="{{ $index }}"
                                                        onchange="handleLogTypeChange({{ $index }})"
                                                        {{ $isDropdownDisabled ? 'disabled' : '' }}>
                                                    @foreach($logTypes as $value => $label)
                                                        @php
                                                            $selected = '';
                                                            $isHoliday = $day['is_holiday'] ? true : false;
                                                            $holidayType = $day['holiday_type'] ?? null;
                                                            
                                                            // Priority 1: Use actual log_type from database if exists
                                                            if ($day['log_type'] && $value === $day['log_type']) {
                                                                $selected = 'selected';
                                                            }
                                                            // Priority 2: Smart default selection logic (only if no existing log_type)
                                                            elseif (!$day['log_type']) {
                                                                if (($day['is_suspension'] ?? false) && str_contains($label, 'Suspension')) {
                                                                    $selected = 'selected';
                                                                } elseif (!$day['is_weekend'] && !$isHoliday && !($day['is_suspension'] ?? false) && $value === 'regular_workday') {
                                                                    // 1. Regular Workday (default for work days)
                                                                    $selected = 'selected';
                                                                } elseif ($day['is_weekend'] && !$isHoliday && $value === 'rest_day') {
                                                                    // 2. Rest Day (default for rest days) 
                                                                    $selected = 'selected';
                                                                } elseif ($isHoliday && $holidayType === 'regular' && !$day['is_weekend'] && $value === 'regular_holiday') {
                                                                    // 3. Regular Holiday (default for active regular holidays on work days)
                                                                    $selected = 'selected';
                                                                } elseif ($isHoliday && $holidayType === 'special_non_working' && !$day['is_weekend'] && $value === 'special_holiday') {
                                                                    // 4. Special Holiday (default for active special non-working holidays on work days)
                                                                    $selected = 'selected';
                                                                } elseif ($day['is_weekend'] && $isHoliday && $holidayType === 'regular' && $value === 'rest_day_regular_holiday') {
                                                                    // Rest Day + Regular Holiday (rest day + regular holiday)
                                                                    $selected = 'selected';
                                                                } elseif ($day['is_weekend'] && $isHoliday && $holidayType === 'special_non_working' && $value === 'rest_day_special_holiday') {
                                                                    // Rest Day + Special Holiday (rest day + special non-working holiday)
                                                                    $selected = 'selected';
                                                                }
                                                            }
                                                        @endphp
                                                        <option value="{{ $value }}" {{ $selected }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                @if($isSuspension)
                                                    {{-- For active suspension days, add hidden input for log_type since disabled selects won't submit --}}
                                                    @php
                                                        $selectedLogType = '';
                                                        if ($day['log_type']) {
                                                            $selectedLogType = $day['log_type'];
                                                        } else {
                                                            // Find the suspension log type
                                                            foreach($logTypes as $value => $label) {
                                                                if (str_contains($label, 'Suspension')) {
                                                                    $selectedLogType = $value;
                                                                    break;
                                                                }
                                                            }
                                                        }
                                                    @endphp
                                                    <input type="hidden" name="time_logs[{{ $index }}][log_type]" value="{{ $selectedLogType }}">
                                                    {{-- Add hidden inputs for suspension days since disabled time inputs won't submit their values --}}
                                                    <input type="hidden" name="time_logs[{{ $index }}][time_in_hidden]" value="{{ $defaultTimeIn ?? '' }}">
                                                    <input type="hidden" name="time_logs[{{ $index }}][time_out_hidden]" value="{{ $defaultTimeOut ?? '' }}">
                                                    <input type="hidden" name="time_logs[{{ $index }}][break_in_hidden]" value="{{ $defaultBreakIn ?? '' }}">
                                                    <input type="hidden" name="time_logs[{{ $index }}][break_out_hidden]" value="{{ $defaultBreakOut ?? '' }}">
                                                @elseif($isActiveHoliday)
                                                    {{-- For active holidays, add hidden input for log_type since disabled selects won't submit --}}
                                                    @php
                                                        $selectedLogType = $day['log_type'] ?? '';
                                                    @endphp
                                                    <input type="hidden" name="time_logs[{{ $index }}][log_type]" value="{{ $selectedLogType }}">
                                                @endif
                                                @if($day['is_weekend'])
                                                    <input type="hidden" name="time_logs[{{ $index }}][is_rest_day]" value="1">
                                                @endif
                                                @if($day['is_holiday'])
                                                    <input type="hidden" name="time_logs[{{ $index }}][is_holiday]" value="1">
                                                @endif
                                                @if($isActiveHoliday)
                                                    <input type="hidden" name="time_logs[{{ $index }}][is_holiday_active]" value="1">
                                                @endif
                                                @if($day['is_suspension'] ?? false)
                                                    <input type="hidden" name="time_logs[{{ $index }}][is_suspension]" value="1">
                                                @endif
                                            </td>
                                            <td class="px-3 py-2">
                                                <div class="flex gap-2">
                                                    <button type="button" 
                                                            onclick="setRegularHours({{ $index }})"
                                                            class="px-2 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50"
                                                            title="Set 8:00 AM - 5:00 PM">
                                                        Set Regular
                                                    </button>
                                                    <button type="button" 
                                                            onclick="clearRowTimes({{ $index }})"
                                                            class="px-2 py-1 bg-red-500 text-white text-xs rounded hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50"
                                                            title="Clear all times for this row">
                                                        Clear
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Legend -->
                        {{-- <div class="mt-4 text-sm text-gray-600">
                            <p><strong>Legend:</strong></p>
                            <div class="flex flex-wrap gap-4 mt-1">
                                <span>● <span class="bg-gray-100 px-2 py-1 rounded">Weekend days</span></span>
                                <span>● <span class="bg-yellow-50 px-2 py-1 rounded">Holiday</span></span>
                                <span>● <strong>Overtime Hours:</strong> Hours worked beyond regular shift</span>
                            </div>
                        </div> --}}

                        <!-- Action Buttons -->
                        <div class="mt-6 flex justify-between">
                            <div>
                                @if($payrollId && isset($schedule))
                                    <a href="{{ route('payrolls.automation.show', ['schedule' => $schedule, 'employee' => $selectedEmployee->id]) }}" 
                                       class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        ← Back to Payroll
                                    </a>
                                @elseif($payrollId)
                                    <a href="{{ route('payrolls.show', $payrollId) }}" 
                                       class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        ← Back to Payroll
                                    </a>
                                @else
                                    <a href="{{ route('time-logs.create-bulk') }}" 
                                       class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        ← Back
                                    </a>
                                @endif
                            </div>
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                                @if($payrollId)
                                    Save DTR & Return to Payroll
                                @else
                                    Save DTR
                                @endif
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function fillRegularHours(timeIn, timeOut, breakIn, breakOut) {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                const isRestDay = row.classList.contains('bg-gray-100'); // This is employee's rest day
                const isHoliday = row.classList.contains('bg-yellow-50');
                
                // Check if the row has suspension type selected
                const logTypeSelect = row.querySelector(`select[name="time_logs[${index}][log_type]"]`);
                const selectedOption = logTypeSelect ? logTypeSelect.options[logTypeSelect.selectedIndex] : null;
                const selectedText = selectedOption ? selectedOption.text : '';
                const isSuspension = selectedText.toLowerCase().includes('suspension');
                
                if (!isRestDay && !isHoliday && !isSuspension) {
                    const timeInInput = row.querySelector(`input[name="time_logs[${index}][time_in]"]`);
                    const timeOutInput = row.querySelector(`input[name="time_logs[${index}][time_out]"]`);
                    const breakInInput = row.querySelector(`input[name="time_logs[${index}][break_in]"]`);
                    const breakOutInput = row.querySelector(`input[name="time_logs[${index}][break_out]"]`);
                    
                    // Only fill empty fields to avoid overwriting existing data
                    if (timeInInput && !timeInInput.value && !timeInInput.disabled) timeInInput.value = timeIn;
                    if (timeOutInput && !timeOutInput.value && !timeOutInput.disabled) timeOutInput.value = timeOut;
                    if (breakInInput && !breakInInput.value && !breakInInput.disabled) breakInInput.value = breakIn;
                    if (breakOutInput && !breakOutInput.value && !breakOutInput.disabled) breakOutInput.value = breakOut;
                }
            });
        }

        function fillTimeOnly(timeIn, timeOut) {
            // Fill only time in/out fields for flexible break employees
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                const isRestDay = row.classList.contains('bg-gray-100'); // This is employee's rest day
                const isHoliday = row.classList.contains('bg-yellow-50');
                
                // Check if the row has suspension type selected
                const logTypeSelect = row.querySelector(`select[name="time_logs[${index}][log_type]"]`);
                const selectedOption = logTypeSelect ? logTypeSelect.options[logTypeSelect.selectedIndex] : null;
                const selectedText = selectedOption ? selectedOption.text : '';
                const isSuspension = selectedText.toLowerCase().includes('suspension');
                
                if (!isRestDay && !isHoliday && !isSuspension) {
                    const timeInInput = row.querySelector(`input[name="time_logs[${index}][time_in]"]`);
                    const timeOutInput = row.querySelector(`input[name="time_logs[${index}][time_out]"]`);
                    
                    // Only fill empty fields to avoid overwriting existing data
                    if (timeInInput && !timeInInput.value && !timeInInput.disabled) timeInInput.value = timeIn;
                    if (timeOutInput && !timeOutInput.value && !timeOutInput.disabled) timeOutInput.value = timeOut;
                    // Do not fill break fields for flexible break employees
                }
            });
        }

        function fillRegularHoursAll(timeIn, timeOut) {
            // Alternative function to fill all fields (overwrite existing)
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                const isRestDay = row.classList.contains('bg-gray-100'); // This is employee's rest day
                const isHoliday = row.classList.contains('bg-yellow-50');
                
                if (!isRestDay && !isHoliday) {
                    const timeInInput = row.querySelector(`input[name="time_logs[${index}][time_in]"]`);
                    const timeOutInput = row.querySelector(`input[name="time_logs[${index}][time_out]"]`);
                    const breakInInput = row.querySelector(`input[name="time_logs[${index}][break_in]"]`);
                    const breakOutInput = row.querySelector(`input[name="time_logs[${index}][break_out]"]`);
                    
                    if (timeInInput) timeInInput.value = timeIn;       // 8:00 (8:00 AM)
                    if (timeOutInput) timeOutInput.value = timeOut;    // 17:00 (5:00 PM)
                    if (breakInInput) breakInInput.value = '12:00';    // 12:00 PM
                    if (breakOutInput) breakOutInput.value = '13:00';  // 1:00 PM
                }
            });
        }

        function clearAndSetRegular() {
            // Clear only time logs (time in/out, break in/out), preserve types
            const timeInputs = document.querySelectorAll('input[name*="[time_in]"], input[name*="[time_out]"], input[name*="[break_in]"], input[name*="[break_out]"]');
            timeInputs.forEach(input => {
                input.value = '';
            });
        }

        function resetAll() {
            // Clear all time fields but preserve day types (especially suspension days)
            const timeInputs = document.querySelectorAll('input[name*="[time_in]"], input[name*="[time_out]"], input[name*="[break_in]"], input[name*="[break_out]"]');
            timeInputs.forEach(input => {
                // Only clear if not disabled (suspension days should keep their values)
                if (!input.disabled) {
                    input.value = '';
                }
            });

            // Don't reset log types - preserve current selections including suspension days
            // Just update the time input states based on current log types
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                // Trigger handleLogTypeChange for each row to ensure proper input states
                handleLogTypeChange(index);
            });
        }

        function setRegularHours(rowIndex) {
            // Check if this row is set to suspension type
            const logTypeSelect = document.querySelector(`select[name="time_logs[${rowIndex}][log_type]"]`);
            const selectedOption = logTypeSelect ? logTypeSelect.options[logTypeSelect.selectedIndex] : null;
            const selectedText = selectedOption ? selectedOption.text : '';
            const isSuspension = selectedText.toLowerCase().includes('suspension');
            
            // Don't fill times if it's a suspension day
            if (isSuspension) {
                console.log('Skipping time fill for suspension day on row:', rowIndex);
                return;
            }
            
            @php
                // Pass the employee schedule to JavaScript
                $jsScheduleStart = $selectedEmployee->timeSchedule ? $selectedEmployee->timeSchedule->time_in : '08:00';
                $jsScheduleEnd = $selectedEmployee->timeSchedule ? $selectedEmployee->timeSchedule->time_out : '17:00';
                $jsBreakStart = ($selectedEmployee->timeSchedule && $selectedEmployee->timeSchedule->break_start) ? $selectedEmployee->timeSchedule->break_start : '12:00';
                $jsBreakEnd = ($selectedEmployee->timeSchedule && $selectedEmployee->timeSchedule->break_end) ? $selectedEmployee->timeSchedule->break_end : '13:00';
                
                $jsScheduleStart = \Carbon\Carbon::parse($jsScheduleStart)->format('H:i');
                $jsScheduleEnd = \Carbon\Carbon::parse($jsScheduleEnd)->format('H:i');
                $jsBreakStart = \Carbon\Carbon::parse($jsBreakStart)->format('H:i');
                $jsBreakEnd = \Carbon\Carbon::parse($jsBreakEnd)->format('H:i');
                
                // Determine break type for intelligent behavior
                $jsIsFlexibleBreak = false;
                $jsIsFixedBreak = false;
                
                if ($selectedEmployee->timeSchedule) {
                    // Check if employee has flexible break (break_duration_minutes without fixed times)
                    if ($selectedEmployee->timeSchedule->break_duration_minutes && $selectedEmployee->timeSchedule->break_duration_minutes > 0 && !($selectedEmployee->timeSchedule->break_start && $selectedEmployee->timeSchedule->break_end)) {
                        $jsIsFlexibleBreak = true;
                    } elseif ($selectedEmployee->timeSchedule->break_start && $selectedEmployee->timeSchedule->break_end) {
                        $jsIsFixedBreak = true;
                    }
                }
            @endphp
            
            // Set employee's scheduled working hours for a specific row
            const timeInInput = document.querySelector(`input[name="time_logs[${rowIndex}][time_in]"]`);
            const timeOutInput = document.querySelector(`input[name="time_logs[${rowIndex}][time_out]"]`);
            const breakInInput = document.querySelector(`input[name="time_logs[${rowIndex}][break_in]"]`);
            const breakOutInput = document.querySelector(`input[name="time_logs[${rowIndex}][break_out]"]`);
            
            // Use employee's actual schedule times
            if (timeInInput) timeInInput.value = '{{ $jsScheduleStart }}';
            if (timeOutInput) timeOutInput.value = '{{ $jsScheduleEnd }}';
            
            // Intelligent break field handling based on employee break type
            @if($isFlexibleBreak ?? false)
                // Flexible break employee - do not fill break fields
                // Break fields remain empty for flexible break employees
            @elseif($isFixedBreak ?? false)
                // Fixed break employee - fill break fields
                if (breakInInput) breakInInput.value = '{{ $jsBreakStart }}';
                if (breakOutInput) breakOutInput.value = '{{ $jsBreakEnd }}';
            @else
                // No break configuration - do not fill break fields (default to flexible behavior)
                // Break fields remain empty
            @endif
            
            // Do NOT change the log type - preserve the current selection
            // This allows users to set regular times on Rest Days, Holidays, etc. without changing the type
            
            // Trigger calculation if function exists
            if (typeof calculateHours === 'function') {
                calculateHours(rowIndex);
            }
        }

        function clearRowTimes(rowIndex) {
            // Clear only time entries for a specific row, preserve the log type
            const timeInInput = document.querySelector(`input[name="time_logs[${rowIndex}][time_in]"]`);
            const timeOutInput = document.querySelector(`input[name="time_logs[${rowIndex}][time_out]"]`);
            const breakInInput = document.querySelector(`input[name="time_logs[${rowIndex}][break_in]"]`);
            const breakOutInput = document.querySelector(`input[name="time_logs[${rowIndex}][break_out]"]`);
            
            // Clear only time fields, do NOT change the log type
            if (timeInInput && !timeInInput.disabled) timeInInput.value = '';
            if (timeOutInput && !timeOutInput.disabled) timeOutInput.value = '';
            if (breakInInput && !breakInInput.disabled) breakInInput.value = '';
            if (breakOutInput && !breakOutInput.disabled) breakOutInput.value = '';
            
            // Trigger calculation if function exists
            if (typeof calculateHours === 'function') {
                calculateHours(rowIndex);
            }
        }

        function calculateHours(rowIndex) {
            // You can add automatic hour calculation logic here if needed
            console.log('Calculating hours for row:', rowIndex);
        }

        function handleLogTypeChange(rowIndex) {
            const logTypeSelect = document.querySelector(`select[name="time_logs[${rowIndex}][log_type]"]`);
            const timeInputs = document.querySelectorAll(`.time-input-${rowIndex}`);
            
            if (logTypeSelect && timeInputs.length > 0) {
                const selectedOption = logTypeSelect.options[logTypeSelect.selectedIndex];
                const selectedText = selectedOption ? selectedOption.text : '';
                
                // Check if the selected option contains "Suspension" in its text
                const isSuspension = selectedText.toLowerCase().includes('suspension');
                
                // Get row element to check for suspension data attributes
                const row = document.querySelector(`[data-row="${rowIndex}"]`).closest('tr');
                const isPaidSuspension = row && row.dataset.isPaidSuspension === 'true';
                const employeeHasBenefits = row && row.dataset.employeeHasBenefits === 'true';
                
                // Check if this is an inherent suspension day (from suspension settings)
                const hasHiddenSuspensionInput = document.querySelector(`input[name="time_logs[${rowIndex}][is_suspension]"][type="hidden"]`);
                
                timeInputs.forEach(input => {
                    if (hasHiddenSuspensionInput) {
                        // For suspension setting days - check if employee has benefits
                        if (isPaidSuspension && employeeHasBenefits) {
                            // Paid suspension + with benefits: preserve auto-filled values
                            input.disabled = true;
                            input.classList.add('bg-gray-100', 'cursor-not-allowed');
                            input.classList.remove('focus:ring-indigo-500', 'focus:border-indigo-500');
                            // Keep existing values (already set by PHP)
                        } else {
                            // Unpaid suspension OR without benefits: disable and clear values
                            input.disabled = true;
                            input.value = ''; // Clear the input for unpaid suspension or employees without benefits
                            input.classList.add('bg-gray-100', 'cursor-not-allowed');
                            input.classList.remove('focus:ring-indigo-500', 'focus:border-indigo-500');
                        }
                    } else if (isSuspension) {
                        if (isPaidSuspension) {
                            // For paid suspensions (manual selection), enable inputs but fill with default schedule
                            input.disabled = false;
                            input.classList.remove('bg-gray-100', 'cursor-not-allowed');
                            input.classList.add('focus:ring-indigo-500', 'focus:border-indigo-500');
                            
                            // Fill with default values if empty (these come from server-side processing)
                            if (!input.value) {
                                const fieldName = input.name.match(/\[(\w+)\]$/)[1]; // Extract field name (time_in, time_out, etc.)
                                const defaultValue = row.dataset[fieldName + 'Default'];
                                if (defaultValue && defaultValue !== 'null') {
                                    input.value = defaultValue;
                                }
                            }
                        } else {
                            // For unpaid suspensions (manual selection), disable inputs and clear values
                            input.disabled = true;
                            input.value = ''; // Clear the input for unpaid suspension
                            input.classList.add('bg-gray-100', 'cursor-not-allowed');
                            input.classList.remove('focus:ring-indigo-500', 'focus:border-indigo-500');
                        }
                    } else {
                        // Enable inputs for non-suspension types (only for non-suspension setting days)
                        input.disabled = false;
                        input.classList.remove('bg-gray-100', 'cursor-not-allowed');
                        input.classList.add('focus:ring-indigo-500', 'focus:border-indigo-500');
                    }
                });
                
                // Sync hidden field values for suspension days
                syncHiddenFields(rowIndex);
            }
        }

        // Function to sync hidden field values with actual input values
        function syncHiddenFields(rowIndex) {
            const timeFields = ['time_in', 'time_out', 'break_in', 'break_out'];
            
            timeFields.forEach(field => {
                const actualInput = document.querySelector(`input[name="time_logs[${rowIndex}][${field}]"]`);
                const hiddenInput = document.querySelector(`input[name="time_logs[${rowIndex}][${field}_hidden]"]`);
                
                if (actualInput && hiddenInput) {
                    // If the actual input is disabled, sync its value to the hidden field
                    if (actualInput.disabled && actualInput.value) {
                        hiddenInput.value = actualInput.value;
                    }
                    // If the actual input is enabled, clear the hidden field to avoid conflicts
                    else if (!actualInput.disabled) {
                        hiddenInput.value = '';
                    }
                }
            });
        }

        // Auto-save functionality (optional)
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('dtr-form');
            if (form) {
                // Add any initialization logic here
                console.log('DTR form initialized');
                
                // Initialize disabled state for all rows on page load
                const rows = document.querySelectorAll('tbody tr');
                rows.forEach((row, index) => {
                    const logTypeSelect = row.querySelector(`select[name="time_logs[${index}][log_type]"]`);
                    if (logTypeSelect) {
                        // Check current selected value and apply appropriate state
                        handleLogTypeChange(index);
                    }
                });
                
                // Add event listeners to time inputs for syncing hidden fields
                const timeInputs = form.querySelectorAll('input[type="time"]');
                timeInputs.forEach(input => {
                    input.addEventListener('input', function() {
                        const match = this.name.match(/time_logs\[(\d+)\]/);
                        if (match) {
                            const rowIndex = parseInt(match[1]);
                            syncHiddenFields(rowIndex);
                        }
                    });
                });
            }
        });
    </script>
</x-app-layout>
