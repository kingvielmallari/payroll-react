<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Create/Edit DTR') }} - {{ $employee->user->name }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Period: {{ $periodStart->format('M d, Y') }} - {{ $periodEnd->format('M d, Y') }}
                    ({{ $periodStart->diffInDays($periodEnd) + 1 }} days)
                </p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ url()->previous() }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Back
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Employee Info -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Employee Information</h3>
                            <div class="mt-2 space-y-1">
                                <p class="text-sm"><strong>Name:</strong> {{ $employee->user->name }}</p>
                                <p class="text-sm"><strong>Employee Number:</strong> {{ $employee->employee_number }}</p>
                                <p class="text-sm"><strong>Department:</strong> {{ $employee->department->name ?? 'N/A' }}</p>
                                <p class="text-sm"><strong>Position:</strong> {{ $employee->position->title ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Rate Information</h3>
                            <div class="mt-2 space-y-1">
                                <p class="text-sm"><strong>Hourly Rate:</strong> ₱{{ number_format($employee->hourly_rate ?? 0, 2) }}</p>
                                <p class="text-sm"><strong>Daily Rate:</strong> ₱{{ number_format($employee->daily_rate ?? 0, 2) }}</p>
                                <p class="text-sm"><strong>Monthly Salary:</strong> ₱{{ number_format($employee->basic_salary ?? 0, 2) }}</p>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Schedule Information</h3>
                            <div class="mt-2 space-y-1">
                                <p class="text-sm"><strong>Time Schedule:</strong> {{ $employee->timeSchedule->name ?? 'N/A' }}</p>
                                <p class="text-sm"><strong>Day Schedule:</strong> {{ $employee->daySchedule->name ?? 'N/A' }}</p>
                                @if($employee->timeSchedule)
                                    <p class="text-sm"><strong>Work Hours:</strong> 
                                        {{ $employee->timeSchedule->start_time ?? '08:00' }} - {{ $employee->timeSchedule->end_time ?? '17:00' }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DTR Form -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Daily Time Records</h3>
                        <div class="flex space-x-2">
                            <button type="button" id="autoFillBtn" class="bg-blue-500 hover:bg-blue-600 text-white text-sm px-3 py-1 rounded">
                                Auto Fill Standard Hours
                            </button>
                            <button type="button" id="clearAllBtn" class="bg-red-500 hover:bg-red-600 text-white text-sm px-3 py-1 rounded">
                                Clear All
                            </button>
                        </div>
                    </div>

                    <form action="{{ route('dtr.store') }}" method="POST" id="dtrForm">
                        @csrf
                        <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                        <input type="hidden" name="period_start" value="{{ $periodStart->format('Y-m-d') }}">
                        <input type="hidden" name="period_end" value="{{ $periodEnd->format('Y-m-d') }}">

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Day</th>
                                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Time In</th>
                                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Time Out</th>
                                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Break In</th>
                                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Break Out</th>
                                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Hours</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($dtrRecords as $index => $record)
                                    <tr class="{{ $record['is_weekend'] || $record['is_holiday'] ? 'bg-gray-50' : '' }}">
                                        <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ \Carbon\Carbon::parse($record['date'])->format('M d, Y') }}
                                            <input type="hidden" name="dtr_records[{{ $index }}][date]" value="{{ $record['date'] }}">
                                            @if($record['is_holiday'])
                                                <span class="ml-1 px-2 py-1 text-xs bg-red-100 text-red-800 rounded">Holiday</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-center text-sm text-gray-500">
                                            {{ $record['day_name'] }}
                                            @if($record['is_weekend'])
                                                <br><span class="text-xs text-orange-600">Weekend</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <input type="time" 
                                                   name="dtr_records[{{ $index }}][time_in]" 
                                                   value="{{ $record['time_in'] }}"
                                                   class="time-input w-20 text-xs border-gray-300 rounded focus:border-indigo-500 focus:ring-indigo-500"
                                                   {{ $record['is_weekend'] && !$record['is_holiday'] ? 'disabled' : '' }}>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <input type="time" 
                                                   name="dtr_records[{{ $index }}][time_out]" 
                                                   value="{{ $record['time_out'] }}"
                                                   class="time-input w-20 text-xs border-gray-300 rounded focus:border-indigo-500 focus:ring-indigo-500"
                                                   {{ $record['is_weekend'] && !$record['is_holiday'] ? 'disabled' : '' }}>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <input type="time" 
                                                   name="dtr_records[{{ $index }}][break_in]" 
                                                   value="{{ $record['break_in'] }}"
                                                   class="time-input w-20 text-xs border-gray-300 rounded focus:border-indigo-500 focus:ring-indigo-500"
                                                   {{ $record['is_weekend'] && !$record['is_holiday'] ? 'disabled' : '' }}>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <input type="time" 
                                                   name="dtr_records[{{ $index }}][break_out]" 
                                                   value="{{ $record['break_out'] }}"
                                                   class="time-input w-20 text-xs border-gray-300 rounded focus:border-indigo-500 focus:ring-indigo-500"
                                                   {{ $record['is_weekend'] && !$record['is_holiday'] ? 'disabled' : '' }}>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <select name="dtr_records[{{ $index }}][log_type]" 
                                                    class="w-20 text-xs border-gray-300 rounded focus:border-indigo-500 focus:ring-indigo-500"
                                                    {{ $record['is_weekend'] && !$record['is_holiday'] ? 'disabled' : '' }}>
                                                <option value="regular" {{ $record['log_type'] == 'regular' ? 'selected' : '' }}>Regular</option>
                                                <option value="overtime" {{ $record['log_type'] == 'overtime' ? 'selected' : '' }}>Overtime</option>
                                                <option value="holiday" {{ $record['log_type'] == 'holiday' ? 'selected' : '' }}>Holiday</option>
                                                <option value="leave" {{ $record['log_type'] == 'leave' ? 'selected' : '' }}>Leave</option>
                                            </select>
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <input type="text" 
                                                   name="dtr_records[{{ $index }}][remarks]" 
                                                   value="{{ $record['remarks'] }}"
                                                   class="w-24 text-xs border-gray-300 rounded focus:border-indigo-500 focus:ring-indigo-500"
                                                   placeholder="Notes"
                                                   {{ $record['is_weekend'] && !$record['is_holiday'] ? 'disabled' : '' }}>
                                        </td>
                                        <td class="px-3 py-2 text-center text-xs">
                                            <div class="hours-display" data-index="{{ $index }}">
                                                <div class="text-blue-600 font-medium">
                                                    <span class="regular-hours">{{ number_format($record['regular_hours'], 1) }}</span>h
                                                </div>
                                                @if($record['overtime_hours'] > 0)
                                                <div class="text-orange-600">
                                                    OT: <span class="overtime-hours">{{ number_format($record['overtime_hours'], 1) }}</span>h
                                                </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6 flex justify-between items-center">
                            <div class="text-sm text-gray-600">
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div>
                                        <strong>Total Regular Hours:</strong> 
                                        <span id="totalRegular" class="text-blue-600 font-medium">0.0</span>
                                    </div>
                                    <div>
                                        <strong>Total Overtime Hours:</strong> 
                                        <span id="totalOvertime" class="text-orange-600 font-medium">0.0</span>
                                    </div>
                                    <div>
                                        <strong>Total Amount:</strong> 
                                        <span id="totalAmount" class="text-green-600 font-medium">₱0.00</span>
                                    </div>
                                    <div>
                                        <strong>Working Days:</strong> 
                                        <span id="workingDays" class="text-gray-800 font-medium">0</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex space-x-3">
                                <button type="button" onclick="window.history.back()" 
                                        class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
                                    Cancel
                                </button>
                                <button type="submit" 
                                        class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">
                                    Save DTR Records
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hourlyRate = {{ $employee->hourly_rate ?? 0 }};
            const standardStartTime = '{{ $employee->timeSchedule->start_time ?? "08:00" }}';
            const standardEndTime = '{{ $employee->timeSchedule->end_time ?? "17:00" }}';
            
            // Auto-fill button functionality
            document.getElementById('autoFillBtn').addEventListener('click', function() {
                const timeInputs = document.querySelectorAll('input[type="time"]');
                timeInputs.forEach(input => {
                    if (!input.disabled) {
                        if (input.name.includes('time_in')) {
                            input.value = standardStartTime;
                        } else if (input.name.includes('time_out')) {
                            input.value = standardEndTime;
                        }
                    }
                });
                calculateAllHours();
            });

            // Clear all button functionality
            document.getElementById('clearAllBtn').addEventListener('click', function() {
                if (confirm('Are you sure you want to clear all time entries?')) {
                    const timeInputs = document.querySelectorAll('input[type="time"], input[type="text"]');
                    timeInputs.forEach(input => {
                        if (!input.disabled && !input.name.includes('date')) {
                            input.value = '';
                        }
                    });
                    calculateAllHours();
                }
            });

            // Calculate hours when time inputs change
            document.querySelectorAll('.time-input').forEach(input => {
                input.addEventListener('change', function() {
                    const row = this.closest('tr');
                    const index = this.name.match(/\[(\d+)\]/)[1];
                    calculateHoursForRow(row, index);
                    calculateTotals();
                });
            });

            function calculateHoursForRow(row, index) {
                const timeInInput = row.querySelector(`input[name="dtr_records[${index}][time_in]"]`);
                const timeOutInput = row.querySelector(`input[name="dtr_records[${index}][time_out]"]`);
                const hoursDisplay = row.querySelector(`.hours-display[data-index="${index}"]`);
                
                if (!timeInInput.value || !timeOutInput.value) {
                    hoursDisplay.innerHTML = '<div class="text-gray-400">-</div>';
                    return;
                }

                const timeIn = new Date(`1970-01-01T${timeInInput.value}:00`);
                const timeOut = new Date(`1970-01-01T${timeOutInput.value}:00`);
                
                // Handle overnight shifts
                if (timeOut < timeIn) {
                    timeOut.setDate(timeOut.getDate() + 1);
                }

                const totalHours = (timeOut - timeIn) / (1000 * 60 * 60);
                const regularHours = Math.min(totalHours, 8);
                const overtimeHours = Math.max(totalHours - 8, 0);

                hoursDisplay.innerHTML = `
                    <div class="text-blue-600 font-medium">
                        <span class="regular-hours">${regularHours.toFixed(1)}</span>h
                    </div>
                    ${overtimeHours > 0 ? `<div class="text-orange-600">OT: <span class="overtime-hours">${overtimeHours.toFixed(1)}</span>h</div>` : ''}
                `;
            }

            function calculateAllHours() {
                document.querySelectorAll('tbody tr').forEach((row, index) => {
                    calculateHoursForRow(row, index);
                });
                calculateTotals();
            }

            function calculateTotals() {
                let totalRegular = 0;
                let totalOvertime = 0;
                let workingDays = 0;

                document.querySelectorAll('.regular-hours').forEach(span => {
                    const hours = parseFloat(span.textContent) || 0;
                    totalRegular += hours;
                    if (hours > 0) workingDays++;
                });

                document.querySelectorAll('.overtime-hours').forEach(span => {
                    totalOvertime += parseFloat(span.textContent) || 0;
                });

                const totalAmount = (totalRegular * hourlyRate) + (totalOvertime * hourlyRate * 1.25);

                document.getElementById('totalRegular').textContent = totalRegular.toFixed(1);
                document.getElementById('totalOvertime').textContent = totalOvertime.toFixed(1);
                document.getElementById('totalAmount').textContent = '₱' + totalAmount.toLocaleString('en-PH', {minimumFractionDigits: 2});
                document.getElementById('workingDays').textContent = workingDays;
            }

            // Initial calculation
            calculateAllHours();
        });
    </script>
    @endpush
</x-app-layout>
