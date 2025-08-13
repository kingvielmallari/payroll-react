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
                                <span class="font-medium text-gray-700">Employee #:</span>
                                <span class="text-gray-900">{{ $selectedEmployee->employee_number }}</span>
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
                        <input type="hidden" name="redirect_to_payroll" value="1">

                        <!-- Bulk Actions -->
                        <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                            <h4 class="text-md font-medium text-blue-900 mb-3">Quick Fill Actions</h4>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" class="px-3 py-1 bg-blue-500 text-white text-sm rounded hover:bg-blue-600" onclick="fillRegularHours('08:00', '17:00')">
                                    Fill Empty Fields (8:00 AM - 5:00 PM)
                                </button>
                                <button type="button" class="px-3 py-1 bg-purple-500 text-white text-sm rounded hover:bg-purple-600" onclick="fillRegularHoursAll('08:00', '17:00')">
                                    Fill All Fields (Overwrite)
                                </button>
                                <button type="button" class="px-3 py-1 bg-green-500 text-white text-sm rounded hover:bg-green-600" onclick="clearAll()">
                                    Clear All
                                </button>
                            </div>
                            <p class="text-xs text-blue-700 mt-2">
                                <strong>Fill Empty Fields:</strong> Only fills blank time entries, preserves existing data.<br>
                                <strong>Fill All Fields:</strong> Overwrites all time entries with standard schedule.
                            </p>
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
                                        <tr class="{{ $day['is_weekend'] ? 'bg-gray-100' : '' }} {{ $day['is_holiday'] ? 'bg-yellow-50' : '' }}">
                                            <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900 border-r">
                                                {{ $day['date']->format('M d') }}
                                                <input type="hidden" name="time_logs[{{ $index }}][log_date]" value="{{ $day['date']->format('Y-m-d') }}">
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500 border-r">
                                                {{ $day['day_name'] }}
                                                @if($day['is_weekend'])
                                                    <span class="text-xs text-blue-600">(Weekend)</span>
                                                @endif
                                                @if($day['is_holiday'])
                                                    <span class="text-xs text-red-600">({{ $day['is_holiday'] }})</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap border-r">
                                                <input type="time" 
                                                       name="time_logs[{{ $index }}][time_in]" 
                                                       value="{{ $day['time_in'] ? (is_string($day['time_in']) ? (strlen($day['time_in']) >= 5 ? substr($day['time_in'], 0, 5) : $day['time_in']) : $day['time_in']->format('H:i')) : '' }}"
                                                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500"
                                                       data-row="{{ $index }}"
                                                       onchange="calculateHours({{ $index }})">
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap border-r">
                                                <input type="time" 
                                                       name="time_logs[{{ $index }}][time_out]" 
                                                       value="{{ $day['time_out'] ? (is_string($day['time_out']) ? (strlen($day['time_out']) >= 5 ? substr($day['time_out'], 0, 5) : $day['time_out']) : $day['time_out']->format('H:i')) : '' }}"
                                                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500"
                                                       data-row="{{ $index }}"
                                                       onchange="calculateHours({{ $index }})">
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap border-r">
                                                <input type="time" 
                                                       name="time_logs[{{ $index }}][break_in]" 
                                                       value="{{ $day['break_in'] ? (is_string($day['break_in']) ? (strlen($day['break_in']) >= 5 ? substr($day['break_in'], 0, 5) : $day['break_in']) : $day['break_in']->format('H:i')) : '' }}"
                                                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500">
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap border-r">
                                                <input type="time" 
                                                       name="time_logs[{{ $index }}][break_out]" 
                                                       value="{{ $day['break_out'] ? (is_string($day['break_out']) ? (strlen($day['break_out']) >= 5 ? substr($day['break_out'], 0, 5) : $day['break_out']) : $day['break_out']->format('H:i')) : '' }}"
                                                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500">
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap border-r">
                                                <select name="time_logs[{{ $index }}][log_type]" 
                                                        class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500">
                                                    @foreach($logTypes as $value => $label)
                                                        @php
                                                            $selected = '';
                                                            $dateKey = $day['date']->format('Y-m-d');
                                                            $isHoliday = isset($holidays[$dateKey]);
                                                            $holidayType = $isHoliday ? $holidays[$dateKey]->type : null;
                                                            
                                                            // Smart default selection logic based on actual display names
                                                            if (!$day['is_weekend'] && !$isHoliday && $label === 'Regular Day') {
                                                                // 1. Regular Day (default for weekdays without holidays)
                                                                $selected = 'selected';
                                                            } elseif ($day['is_weekend'] && !$isHoliday && $label === 'Rest Day') {
                                                                // 2. Rest Day (default for weekends without holidays) 
                                                                $selected = 'selected';
                                                            } elseif ($isHoliday && $holidayType === 'regular' && !$day['is_weekend'] && $label === 'RE Holiday') {
                                                                // 3. RE Holiday (default for active regular holidays on weekdays)
                                                                $selected = 'selected';
                                                            } elseif ($isHoliday && $holidayType === 'special' && !$day['is_weekend'] && $label === 'SP Holiday') {
                                                                // 4. SP Holiday (default for active special holidays on weekdays)
                                                                $selected = 'selected';
                                                            } elseif ($day['is_weekend'] && $isHoliday && $holidayType === 'regular' && $label === 'Rest + RE Holiday') {
                                                                // Rest Day + RE Holiday (weekend + regular holiday)
                                                                $selected = 'selected';
                                                            } elseif ($day['is_weekend'] && $isHoliday && $holidayType === 'special' && $label === 'Rest + SP Holiday') {
                                                                // Rest Day + SE Holiday (weekend + special holiday)
                                                                $selected = 'selected';
                                                            }
                                                        @endphp
                                                        <option value="{{ $value }}" {{ $selected }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                @if($day['is_weekend'])
                                                    <input type="hidden" name="time_logs[{{ $index }}][is_rest_day]" value="1">
                                                @endif
                                                @if($day['is_holiday'])
                                                    <input type="hidden" name="time_logs[{{ $index }}][is_holiday]" value="1">
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
                        <div class="mt-4 text-sm text-gray-600">
                            <p><strong>Legend:</strong></p>
                            <div class="flex flex-wrap gap-4 mt-1">
                                <span>● <span class="bg-gray-100 px-2 py-1 rounded">Weekend days</span></span>
                                <span>● <span class="bg-yellow-50 px-2 py-1 rounded">Holiday</span></span>
                                <span>● <strong>Overtime Hours:</strong> Hours worked beyond regular shift</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-6 flex justify-between">
                            <div>
                                @if($payrollId)
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
        function fillRegularHours(timeIn, timeOut) {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                const isWeekend = row.classList.contains('bg-gray-100');
                const isHoliday = row.classList.contains('bg-yellow-50');
                
                if (!isWeekend && !isHoliday) {
                    const timeInInput = row.querySelector(`input[name="time_logs[${index}][time_in]"]`);
                    const timeOutInput = row.querySelector(`input[name="time_logs[${index}][time_out]"]`);
                    const breakInInput = row.querySelector(`input[name="time_logs[${index}][break_in]"]`);
                    const breakOutInput = row.querySelector(`input[name="time_logs[${index}][break_out]"]`);
                    
                    // Only fill empty fields to avoid overwriting existing data
                    if (timeInInput && !timeInInput.value) timeInInput.value = timeIn;       // 8:00 (8:00 AM)
                    if (timeOutInput && !timeOutInput.value) timeOutInput.value = timeOut;    // 17:00 (5:00 PM)
                    if (breakInInput && !breakInInput.value) breakInInput.value = '12:00';    // 12:00 PM
                    if (breakOutInput && !breakOutInput.value) breakOutInput.value = '13:00';  // 1:00 PM
                }
            });
        }

        function fillRegularHoursAll(timeIn, timeOut) {
            // Alternative function to fill all fields (overwrite existing)
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                const isWeekend = row.classList.contains('bg-gray-100');
                const isHoliday = row.classList.contains('bg-yellow-50');
                
                if (!isWeekend && !isHoliday) {
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

        function clearAll() {
            const inputs = document.querySelectorAll('input[type="time"], input[type="text"]:not([type="hidden"])');
            inputs.forEach(input => {
                if (!input.name.includes('[log_date]') && !input.name.includes('[is_')) {
                    input.value = '';
                }
            });
        }

        function setRegularHours(rowIndex) {
            // Set regular working hours for a specific row
            const timeInInput = document.querySelector(`input[name="time_logs[${rowIndex}][time_in]"]`);
            const timeOutInput = document.querySelector(`input[name="time_logs[${rowIndex}][time_out]"]`);
            const breakInInput = document.querySelector(`input[name="time_logs[${rowIndex}][break_in]"]`);
            const breakOutInput = document.querySelector(`input[name="time_logs[${rowIndex}][break_out]"]`);
            
            // Only set times, do NOT change the day type
            if (timeInInput) timeInInput.value = '08:00';       // 8:00 AM
            if (timeOutInput) timeOutInput.value = '17:00';     // 5:00 PM  
            if (breakInInput) breakInInput.value = '12:00';     // 12:00 PM
            if (breakOutInput) breakOutInput.value = '13:00';   // 1:00 PM
            
            // Do NOT change the log type - preserve the current selection
            // This allows users to set regular times on Rest Days, Holidays, etc. without changing the type
            
            // Trigger calculation if function exists
            if (typeof calculateHours === 'function') {
                calculateHours(rowIndex);
            }
        }

        function clearRowTimes(rowIndex) {
            // Clear all time entries for a specific row
            const timeInInput = document.querySelector(`input[name="time_logs[${rowIndex}][time_in]"]`);
            const timeOutInput = document.querySelector(`input[name="time_logs[${rowIndex}][time_out]"]`);
            const breakInInput = document.querySelector(`input[name="time_logs[${rowIndex}][break_in]"]`);
            const breakOutInput = document.querySelector(`input[name="time_logs[${rowIndex}][break_out]"]`);
            const logTypeSelect = document.querySelector(`select[name="time_logs[${rowIndex}][log_type]"]`);
            const row = document.querySelector(`tbody tr:nth-child(${rowIndex + 1})`);
            
            if (timeInInput) timeInInput.value = '';
            if (timeOutInput) timeOutInput.value = '';
            if (breakInInput) breakInInput.value = '';
            if (breakOutInput) breakOutInput.value = '';
            
            // Smart default based on actual day type
            if (logTypeSelect && row) {
                const isWeekend = row.classList.contains('bg-gray-100');
                const isHoliday = row.classList.contains('bg-yellow-50');
                
                // Determine appropriate default based on day type
                let defaultType = 'Regular Day'; // Default for weekdays
                
                if (isWeekend && !isHoliday) {
                    defaultType = 'Rest Day';
                } else if (isHoliday && !isWeekend) {
                    // Check for holiday type in hidden inputs or span text
                    const holidaySpan = row.querySelector('.text-red-600');
                    if (holidaySpan && holidaySpan.textContent.toLowerCase().includes('regular')) {
                        defaultType = 'RE Holiday';
                    } else if (holidaySpan && holidaySpan.textContent.toLowerCase().includes('special')) {
                        defaultType = 'SP Holiday';
                    }
                } else if (isWeekend && isHoliday) {
                    // Weekend + Holiday combination
                    const holidaySpan = row.querySelector('.text-red-600');
                    if (holidaySpan && holidaySpan.textContent.toLowerCase().includes('regular')) {
                        defaultType = 'Rest + RE Holiday';
                    } else if (holidaySpan && holidaySpan.textContent.toLowerCase().includes('special')) {
                        defaultType = 'Rest + SP Holiday';
                    } else {
                        defaultType = 'Rest Day';
                    }
                }
                
                // Set the appropriate default
                for (let option of logTypeSelect.options) {
                    if (option.text === defaultType) {
                        option.selected = true;
                        break;
                    }
                }
                
                // If specific type not found, fallback to first option
                if (!logTypeSelect.value && logTypeSelect.options.length > 0) {
                    logTypeSelect.selectedIndex = 0;
                }
            }
            
            // Trigger calculation if function exists
            if (typeof calculateHours === 'function') {
                calculateHours(rowIndex);
            }
        }

        function calculateHours(rowIndex) {
            // You can add automatic hour calculation logic here if needed
            console.log('Calculating hours for row:', rowIndex);
        }

        // Auto-save functionality (optional)
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('dtr-form');
            if (form) {
                // Add any initialization logic here
                console.log('DTR form initialized');
            }
        });
    </script>
</x-app-layout>
