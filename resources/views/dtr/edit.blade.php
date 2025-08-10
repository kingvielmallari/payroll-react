<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Edit DTR - {{ $dtr->employee->user->name ?? ($dtr->employee->first_name ?? 'Unknown') . ' ' . ($dtr->employee->last_name ?? '') }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Period: {{ \Carbon\Carbon::parse($dtr->period_start)->format('M d') }} - {{ \Carbon\Carbon::parse($dtr->period_end)->format('M d, Y') }} 
                    ({{ $dtr->employee->employee_number ?? 'N/A' }})
                </p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('dtr.show', $dtr->id) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    View DTR
                </a>
                <a href="{{ route('payrolls.show', $dtr->payroll_id) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Back to Payroll
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('dtr.update', $dtr->id) }}" method="POST" id="dtr-form">
                @csrf
                @method('PUT')
                
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Employee Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Employee</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $dtr->employee->user->name ?? ($dtr->employee->first_name ?? 'Unknown') . ' ' . ($dtr->employee->last_name ?? '') }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Department</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $dtr->employee->department->name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Employee Number</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $dtr->employee->employee_number ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Time Logs</h3>
                            <div class="text-sm text-gray-500">
                                Click on time fields to edit. Times should be in 24-hour format (e.g., 08:00, 17:30)
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date
                                        </th>
                                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Time In
                                        </th>
                                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Break Start
                                        </th>
                                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Break End
                                        </th>
                                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Time Out
                                        </th>
                                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Regular Hours
                                        </th>
                                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Overtime
                                        </th>
                                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Remarks
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($periodDates as $dateInfo)
                                        @php
                                            $dateStr = $dateInfo['date'];
                                            $dayData = $dtr->dtr_data[$dateStr] ?? [];
                                            $isWeekend = $dateInfo['is_weekend'];
                                        @endphp
                                        <tr class="{{ $isWeekend ? 'bg-gray-50' : '' }}">
                                            <td class="px-3 py-4 whitespace-nowrap text-sm">
                                                <div class="font-medium text-gray-900">{{ $dateInfo['formatted'] }}</div>
                                                <div class="text-xs text-gray-500">{{ $dateInfo['day_name'] }}</div>
                                            </td>
                                            
                                            <!-- Time In -->
                                            <td class="px-3 py-4 whitespace-nowrap text-center">
                                                <input type="time" 
                                                       name="dtr_data[{{ $dateStr }}][time_in]" 
                                                       value="{{ $dayData['time_in'] ?? '' }}"
                                                       class="w-20 text-xs border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 time-input"
                                                       data-date="{{ $dateStr }}"
                                                       {{ $isWeekend ? 'disabled' : '' }}>
                                            </td>
                                            
                                            <!-- Break Start -->
                                            <td class="px-3 py-4 whitespace-nowrap text-center">
                                                <input type="time" 
                                                       name="dtr_data[{{ $dateStr }}][break_start]" 
                                                       value="{{ $dayData['break_start'] ?? '' }}"
                                                       class="w-20 text-xs border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                                       {{ $isWeekend ? 'disabled' : '' }}>
                                            </td>
                                            
                                            <!-- Break End -->
                                            <td class="px-3 py-4 whitespace-nowrap text-center">
                                                <input type="time" 
                                                       name="dtr_data[{{ $dateStr }}][break_end]" 
                                                       value="{{ $dayData['break_end'] ?? '' }}"
                                                       class="w-20 text-xs border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                                       {{ $isWeekend ? 'disabled' : '' }}>
                                            </td>
                                            
                                            <!-- Time Out -->
                                            <td class="px-3 py-4 whitespace-nowrap text-center">
                                                <input type="time" 
                                                       name="dtr_data[{{ $dateStr }}][time_out]" 
                                                       value="{{ $dayData['time_out'] ?? '' }}"
                                                       class="w-20 text-xs border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 time-input"
                                                       data-date="{{ $dateStr }}"
                                                       {{ $isWeekend ? 'disabled' : '' }}>
                                            </td>
                                            
                                            <!-- Regular Hours -->
                                            <td class="px-3 py-4 whitespace-nowrap text-center">
                                                <input type="number" 
                                                       name="dtr_data[{{ $dateStr }}][regular_hours]" 
                                                       value="{{ $dayData['regular_hours'] ?? 0 }}"
                                                       step="0.25" 
                                                       min="0" 
                                                       max="12"
                                                       class="w-16 text-xs border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-center regular-hours"
                                                       data-date="{{ $dateStr }}"
                                                       {{ $isWeekend ? 'disabled' : '' }}>
                                            </td>
                                            
                                            <!-- Overtime Hours -->
                                            <td class="px-3 py-4 whitespace-nowrap text-center">
                                                <input type="number" 
                                                       name="dtr_data[{{ $dateStr }}][overtime_hours]" 
                                                       value="{{ $dayData['overtime_hours'] ?? 0 }}"
                                                       step="0.25" 
                                                       min="0" 
                                                       max="8"
                                                       class="w-16 text-xs border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-center"
                                                       {{ $isWeekend ? 'disabled' : '' }}>
                                            </td>
                                            
                                            <!-- Status -->
                                            <td class="px-3 py-4 whitespace-nowrap text-center">
                                                <select name="dtr_data[{{ $dateStr }}][status]" 
                                                        class="text-xs border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                                        {{ $isWeekend ? 'disabled' : '' }}>
                                                    <option value="present" {{ ($dayData['status'] ?? '') === 'present' ? 'selected' : '' }}>Present</option>
                                                    <option value="absent" {{ ($dayData['status'] ?? '') === 'absent' ? 'selected' : '' }}>Absent</option>
                                                    <option value="leave" {{ ($dayData['status'] ?? '') === 'leave' ? 'selected' : '' }}>Leave</option>
                                                    <option value="holiday" {{ ($dayData['status'] ?? '') === 'holiday' ? 'selected' : '' }}>Holiday</option>
                                                    <option value="weekend" {{ ($dayData['status'] ?? '') === 'weekend' ? 'selected' : '' }}>Weekend</option>
                                                </select>
                                            </td>
                                            
                                            <!-- Remarks -->
                                            <td class="px-3 py-4 whitespace-nowrap">
                                                <input type="text" 
                                                       name="dtr_data[{{ $dateStr }}][remarks]" 
                                                       value="{{ $dayData['remarks'] ?? '' }}"
                                                       placeholder="Optional notes"
                                                       class="w-32 text-xs border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                                       {{ $isWeekend ? 'disabled' : '' }}>
                                            </td>
                                            
                                            <!-- Hidden fields for date info -->
                                            <input type="hidden" name="dtr_data[{{ $dateStr }}][date]" value="{{ $dateStr }}">
                                            <input type="hidden" name="dtr_data[{{ $dateStr }}][day_name]" value="{{ $dateInfo['day_name'] }}">
                                            <input type="hidden" name="dtr_data[{{ $dateStr }}][is_weekend]" value="{{ $isWeekend ? 1 : 0 }}">
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Summary Section -->
                        <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4 bg-gray-50 p-4 rounded-lg">
                            <div class="text-center">
                                <div class="text-sm font-medium text-gray-500">Total Regular Hours</div>
                                <div class="text-lg font-bold text-blue-600" id="total-regular">{{ number_format($dtr->total_regular_hours, 1) }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-sm font-medium text-gray-500">Total Overtime Hours</div>
                                <div class="text-lg font-bold text-orange-600" id="total-overtime">{{ number_format($dtr->total_overtime_hours, 1) }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-sm font-medium text-gray-500">Total Late Hours</div>
                                <div class="text-lg font-bold text-red-600" id="total-late">{{ number_format($dtr->total_late_hours, 1) }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-sm font-medium text-gray-500">Working Days</div>
                                <div class="text-lg font-bold text-green-600" id="working-days">{{ $dtr->regular_days }}</div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-6 flex justify-between items-center">
                            <div class="text-sm text-gray-500">
                                <strong>Note:</strong> Hours will be automatically calculated based on time in/out. You can manually override if needed.
                            </div>
                            <div class="flex space-x-3">
                                <button type="button" id="calculate-hours" 
                                        class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                                    Calculate Hours
                                </button>
                                <button type="submit" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Save DTR
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-calculate hours when time in/out changes
            const timeInputs = document.querySelectorAll('.time-input');
            const regularHoursInputs = document.querySelectorAll('.regular-hours');
            
            // Function to calculate hours between two times
            function calculateHours(timeIn, timeOut, breakStart = null, breakEnd = null) {
                if (!timeIn || !timeOut) return 0;
                
                const inTime = new Date('2000-01-01 ' + timeIn);
                const outTime = new Date('2000-01-01 ' + timeOut);
                
                if (outTime <= inTime) return 0;
                
                let totalMinutes = (outTime - inTime) / (1000 * 60);
                
                // Subtract break time if provided
                if (breakStart && breakEnd) {
                    const breakStartTime = new Date('2000-01-01 ' + breakStart);
                    const breakEndTime = new Date('2000-01-01 ' + breakEnd);
                    
                    if (breakEndTime > breakStartTime) {
                        const breakMinutes = (breakEndTime - breakStartTime) / (1000 * 60);
                        totalMinutes -= breakMinutes;
                    }
                }
                
                return Math.max(0, totalMinutes / 60);
            }
            
            // Function to update hours for a specific date
            function updateHoursForDate(date) {
                const timeIn = document.querySelector(`input[name="dtr_data[${date}][time_in]"]`)?.value;
                const timeOut = document.querySelector(`input[name="dtr_data[${date}][time_out]"]`)?.value;
                const breakStart = document.querySelector(`input[name="dtr_data[${date}][break_start]"]`)?.value;
                const breakEnd = document.querySelector(`input[name="dtr_data[${date}][break_end]"]`)?.value;
                const regularHoursInput = document.querySelector(`input[name="dtr_data[${date}][regular_hours]"]`);
                
                if (timeIn && timeOut && regularHoursInput) {
                    const hours = calculateHours(timeIn, timeOut, breakStart, breakEnd);
                    regularHoursInput.value = hours.toFixed(2);
                    updateTotals();
                }
            }
            
            // Function to update totals
            function updateTotals() {
                let totalRegular = 0;
                let totalOvertime = 0;
                let workingDays = 0;
                
                regularHoursInputs.forEach(input => {
                    const hours = parseFloat(input.value) || 0;
                    totalRegular += hours;
                    if (hours > 0) workingDays++;
                });
                
                const overtimeInputs = document.querySelectorAll('input[name*="[overtime_hours]"]');
                overtimeInputs.forEach(input => {
                    totalOvertime += parseFloat(input.value) || 0;
                });
                
                document.getElementById('total-regular').textContent = totalRegular.toFixed(1);
                document.getElementById('total-overtime').textContent = totalOvertime.toFixed(1);
                document.getElementById('working-days').textContent = workingDays;
            }
            
            // Add event listeners to time inputs
            timeInputs.forEach(input => {
                input.addEventListener('blur', function() {
                    const date = this.dataset.date;
                    updateHoursForDate(date);
                });
            });
            
            // Add event listeners to all number inputs for real-time totals update
            document.querySelectorAll('input[type="number"]').forEach(input => {
                input.addEventListener('input', updateTotals);
            });
            
            // Calculate hours button
            document.getElementById('calculate-hours').addEventListener('click', function() {
                timeInputs.forEach(input => {
                    const date = input.dataset.date;
                    updateHoursForDate(date);
                });
            });
            
            // Initial totals calculation
            updateTotals();
        });
    </script>
    @endpush
</x-app-layout>
