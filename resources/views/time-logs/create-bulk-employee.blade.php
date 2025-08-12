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
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">REMARKS</th>
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
                                                       value="{{ $day['time_in'] ? (is_string($day['time_in']) ? \Carbon\Carbon::parse($day['time_in'])->format('H:i') : $day['time_in']->format('H:i')) : '' }}"
                                                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500"
                                                       data-row="{{ $index }}"
                                                       onchange="calculateHours({{ $index }})">
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap border-r">
                                                <input type="time" 
                                                       name="time_logs[{{ $index }}][time_out]" 
                                                       value="{{ $day['time_out'] ? (is_string($day['time_out']) ? \Carbon\Carbon::parse($day['time_out'])->format('H:i') : $day['time_out']->format('H:i')) : '' }}"
                                                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500"
                                                       data-row="{{ $index }}"
                                                       onchange="calculateHours({{ $index }})">
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap border-r">
                                                <input type="time" 
                                                       name="time_logs[{{ $index }}][break_in]" 
                                                       value="{{ $day['break_in'] ? (is_string($day['break_in']) ? \Carbon\Carbon::parse($day['break_in'])->format('H:i') : $day['break_in']->format('H:i')) : '' }}"
                                                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500">
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap border-r">
                                                <input type="time" 
                                                       name="time_logs[{{ $index }}][break_out]" 
                                                       value="{{ $day['break_out'] ? (is_string($day['break_out']) ? \Carbon\Carbon::parse($day['break_out'])->format('H:i') : $day['break_out']->format('H:i')) : '' }}"
                                                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500">
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap border-r">
                                                <select name="time_logs[{{ $index }}][log_type]" 
                                                        class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500">
                                                    <option value="regular" {{ (!$day['is_weekend'] && !$day['is_holiday']) ? 'selected' : '' }}>Regular</option>
                                                    <option value="rest_day" {{ $day['is_weekend'] ? 'selected' : '' }}>Rest Day</option>
                                                    <option value="holiday" {{ $day['is_holiday'] ? 'selected' : '' }}>Holiday</option>
                                                    <option value="overtime">Overtime</option>
                                                </select>
                                                @if($day['is_weekend'])
                                                    <input type="hidden" name="time_logs[{{ $index }}][is_rest_day]" value="1">
                                                @endif
                                                @if($day['is_holiday'])
                                                    <input type="hidden" name="time_logs[{{ $index }}][is_holiday]" value="1">
                                                @endif
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="text" 
                                                       name="time_logs[{{ $index }}][remarks]" 
                                                       value="{{ $day['remarks'] ?? '' }}"
                                                       placeholder="Remarks..."
                                                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-indigo-500 focus:border-indigo-500">
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
