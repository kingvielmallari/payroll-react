<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Bulk Time Log Creation
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Employee Selection Form -->
                    <form method="GET" action="{{ route('time-logs.create-bulk') }}" class="mb-6">
                        <div class="flex items-end space-x-4">
                            <div class="flex-1">
                                <label for="employee_id" class="block text-sm font-medium text-gray-700">
                                    Select Employee <span class="text-red-500">*</span>
                                </label>
                                <select name="employee_id" id="employee_id" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Choose Employee</option>
                                    @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                        {{ $employee->first_name }} {{ $employee->last_name }} ({{ $employee->employee_number }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <button type="submit" 
                                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition duration-200">
                                    Load Time Logs
                                </button>
                            </div>
                        </div>
                    </form>

                    @if($selectedEmployee && $currentPeriod)
                    <!-- Employee Info -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            {{ $selectedEmployee->first_name }} {{ $selectedEmployee->last_name }}
                        </h3>
                        <p class="text-sm text-gray-600">Employee #: {{ $selectedEmployee->employee_number }}</p>
                        <p class="text-sm text-gray-600">{{ $currentPeriod['period_label'] }}</p>
                        <p class="text-sm text-gray-600">{{ $currentPeriod['pay_label'] }}</p>
                    </div>

                    <!-- Bulk Time Log Form -->
                    <form method="POST" action="{{ route('time-logs.store-bulk') }}" id="bulkTimeLogForm">
                        @csrf
                        <input type="hidden" name="employee_id" value="{{ $selectedEmployee->id }}">

                        <div class="mb-4 flex justify-between items-center">
                            <h4 class="text-lg font-medium text-gray-900">Time Log Entries</h4>
                            <div class="space-x-2">
                                <button type="button" onclick="fillRegularHours()" 
                                        class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">
                                    Fill Regular Hours (8AM-5PM)
                                </button>
                                <button type="button" onclick="clearAll()" 
                                        class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700">
                                    Clear All
                                </button>
                            </div>
                        </div>

                        <!-- Time Log Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time In</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time Out</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Break In</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Break Out</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($dtrData as $index => $day)
                                    <tr class="{{ $day['is_weekend'] ? 'bg-blue-50' : '' }} {{ $day['is_holiday'] ? 'bg-yellow-50' : '' }}">
                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                                            {{ $day['date']->format('M d') }}
                                            <input type="hidden" name="time_logs[{{ $index }}][log_date]" value="{{ $day['date']->format('Y-m-d') }}">
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-sm">
                                            <span class="text-gray-600">{{ $day['day_name'] }}</span>
                                            @if($day['is_weekend'])
                                                <span class="text-blue-600 text-xs">(Weekend)</span>
                                            @endif
                                            @if($day['is_holiday'])
                                                <span class="text-yellow-600 text-xs">({{ $day['is_holiday'] }})</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            <input type="time" 
                                                   name="time_logs[{{ $index }}][time_in]" 
                                                   value="{{ $day['time_in'] }}"
                                                   class="w-full text-sm border-gray-300 rounded focus:border-indigo-500 focus:ring-indigo-500 {{ ($day['is_weekend'] && !$day['is_holiday']) ? 'bg-gray-100' : '' }}"
                                                   {{ ($day['is_weekend'] && !$day['is_holiday']) ? 'readonly' : '' }}>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            <input type="time" 
                                                   name="time_logs[{{ $index }}][time_out]" 
                                                   value="{{ $day['time_out'] }}"
                                                   class="w-full text-sm border-gray-300 rounded focus:border-indigo-500 focus:ring-indigo-500 {{ ($day['is_weekend'] && !$day['is_holiday']) ? 'bg-gray-100' : '' }}"
                                                   {{ ($day['is_weekend'] && !$day['is_holiday']) ? 'readonly' : '' }}>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            <input type="time" 
                                                   name="time_logs[{{ $index }}][break_in]" 
                                                   value="{{ $day['break_in'] }}"
                                                   class="w-full text-sm border-gray-300 rounded focus:border-indigo-500 focus:ring-indigo-500 {{ ($day['is_weekend'] && !$day['is_holiday']) ? 'bg-gray-100' : '' }}"
                                                   {{ ($day['is_weekend'] && !$day['is_holiday']) ? 'readonly' : '' }}>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            <input type="time" 
                                                   name="time_logs[{{ $index }}][break_out]" 
                                                   value="{{ $day['break_out'] }}"
                                                   class="w-full text-sm border-gray-300 rounded focus:border-indigo-500 focus:ring-indigo-500 {{ ($day['is_weekend'] && !$day['is_holiday']) ? 'bg-gray-100' : '' }}"
                                                   {{ ($day['is_weekend'] && !$day['is_holiday']) ? 'readonly' : '' }}>
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            <select name="time_logs[{{ $index }}][log_type]" 
                                                    class="w-full text-sm border-gray-300 rounded focus:border-indigo-500 focus:ring-indigo-500 {{ ($day['is_weekend'] && !$day['is_holiday']) ? 'bg-gray-100' : '' }}"
                                                    {{ ($day['is_weekend'] && !$day['is_holiday']) ? 'readonly' : '' }}>
                                                <option value="regular" {{ (!$day['is_holiday'] && !$day['is_weekend']) ? 'selected' : '' }}>Regular</option>
                                                <option value="overtime">Overtime</option>
                                                <option value="holiday" {{ $day['is_holiday'] ? 'selected' : '' }}>Holiday</option>
                                                <option value="rest_day" {{ ($day['is_weekend'] && !$day['is_holiday']) ? 'selected' : '' }}>Rest Day</option>
                                            </select>
                                            <input type="hidden" name="time_logs[{{ $index }}][is_holiday]" value="{{ $day['is_holiday'] ? '1' : '0' }}">
                                            <input type="hidden" name="time_logs[{{ $index }}][is_rest_day]" value="{{ ($day['is_weekend'] && !$day['is_holiday']) ? '1' : '0' }}">
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap">
                                            <input type="text" 
                                                   name="time_logs[{{ $index }}][remarks]" 
                                                   value="{{ $day['remarks'] }}"
                                                   placeholder="Remarks..."
                                                   class="w-full text-sm border-gray-300 rounded focus:border-indigo-500 focus:ring-indigo-500 {{ ($day['is_weekend'] && !$day['is_holiday']) ? 'bg-gray-100' : '' }}"
                                                   {{ ($day['is_weekend'] && !$day['is_holiday']) ? 'readonly' : '' }}>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-6 flex justify-end space-x-3">
                            <a href="{{ route('time-logs.index') }}" 
                               class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition duration-200">
                                Cancel
                            </a>
                            <button type="submit" id="saveButton"
                                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition duration-200">
                                Save Time Logs
                            </button>
                        </div>
                    </form>
                    @else
                    <div class="text-center py-8">
                        <p class="text-gray-500">Please select an employee to load their payroll period time logs.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        function fillRegularHours() {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                const timeInInput = row.querySelector(`input[name="time_logs[${index}][time_in]"]`);
                const timeOutInput = row.querySelector(`input[name="time_logs[${index}][time_out]"]`);
                const breakInInput = row.querySelector(`input[name="time_logs[${index}][break_in]"]`);
                const breakOutInput = row.querySelector(`input[name="time_logs[${index}][break_out]"]`);
                const logTypeSelect = row.querySelector(`select[name="time_logs[${index}][log_type]"]`);
                
                // Skip if readonly (weekends/holidays) or if log type is not regular
                if (timeInInput && !timeInInput.readOnly && logTypeSelect.value === 'regular') {
                    // Fill with standard work schedule: 8:00 AM - 5:00 PM with 12:00-1:00 PM break
                    timeInInput.value = '08:00';      // 8:00 AM
                    timeOutInput.value = '17:00';     // 5:00 PM  
                    breakInInput.value = '12:00';     // 12:00 PM
                    breakOutInput.value = '13:00';    // 1:00 PM
                }
            });
        }

        function clearAll() {
            if (confirm('Are you sure you want to clear all time entries?')) {
                const inputs = document.querySelectorAll('input[type="time"], input[type="text"]:not([type="hidden"])');
                inputs.forEach(input => {
                    if (!input.readOnly) {
                        input.value = '';
                    }
                });
                
                // Reset log types to regular for non-weekends/holidays
                const selects = document.querySelectorAll('select[name*="log_type"]');
                selects.forEach(select => {
                    if (!select.readOnly) {
                        const hiddenHoliday = select.closest('tr').querySelector('input[name*="is_holiday"]');
                        const hiddenRestDay = select.closest('tr').querySelector('input[name*="is_rest_day"]');
                        
                        if (hiddenHoliday && hiddenHoliday.value === '1') {
                            select.value = 'holiday';
                        } else if (hiddenRestDay && hiddenRestDay.value === '1') {
                            select.value = 'rest_day';
                        } else {
                            select.value = 'regular';
                        }
                    }
                });
            }
        }

        // Auto-submit form when employee is selected
        document.getElementById('employee_id').addEventListener('change', function() {
            if (this.value) {
                this.closest('form').submit();
            }
        });

        // Add debugging for form submission
        document.addEventListener('DOMContentLoaded', function() {
            const bulkForm = document.getElementById('bulkTimeLogForm');
            const saveButton = document.getElementById('saveButton');
            
            if (bulkForm && saveButton) {
                console.log('Bulk form found, adding event listener');
                
                bulkForm.addEventListener('submit', function(e) {
                    console.log('Form submission started');
                    
                    // Disable button to prevent double submission
                    saveButton.disabled = true;
                    saveButton.textContent = 'Saving...';
                    
                    // Log form data for debugging
                    const formData = new FormData(bulkForm);
                    console.log('Form data:', Array.from(formData.entries()));
                    
                    // Count time logs with data
                    const timeLogsWithData = Array.from(formData.entries())
                        .filter(([key, value]) => key.includes('time_logs') && key.includes('time_in') && value)
                        .length;
                    
                    console.log('Time logs with time_in data:', timeLogsWithData);
                    
                    if (timeLogsWithData === 0) {
                        e.preventDefault();
                        alert('Please fill in at least one time log entry with Time In data.');
                        saveButton.disabled = false;
                        saveButton.textContent = 'Save Time Logs';
                        return false;
                    }
                });
            } else {
                console.log('Bulk form not found');
            }
        });
    </script>
</x-app-layout>
