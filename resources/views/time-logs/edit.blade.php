<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Time Log
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('time-logs.show', $timeLog) }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Cancel
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('time-logs.update', $timeLog) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <!-- Employee Selection -->
                        <div>
                            <label for="employee_id" class="block text-sm font-medium text-gray-700">
                                Employee <span class="text-red-500">*</span>
                            </label>
                            <select name="employee_id" id="employee_id" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select Employee</option>
                                @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ (old('employee_id', $timeLog->employee_id) == $employee->id) ? 'selected' : '' }}>
                                    {{ $employee->first_name }} {{ $employee->last_name }} ({{ $employee->employee_number }})
                                </option>
                                @endforeach
                            </select>
                            @error('employee_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Date -->
                        <div>
                            <label for="log_date" class="block text-sm font-medium text-gray-700">
                                Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="log_date" id="log_date" 
                                   value="{{ old('log_date', $timeLog->log_date->format('Y-m-d')) }}" required
                                   max="{{ date('Y-m-d') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('log_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Time In/Out -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="time_in" class="block text-sm font-medium text-gray-700">
                                    Time In <span class="text-red-500">*</span>
                                </label>
                                <input type="time" name="time_in" id="time_in" 
                                       value="{{ old('time_in', $timeLog->time_in?->format('H:i')) }}" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('time_in')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="time_out" class="block text-sm font-medium text-gray-700">
                                    Time Out
                                </label>
                                <input type="time" name="time_out" id="time_out" 
                                       value="{{ old('time_out', $timeLog->time_out?->format('H:i')) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('time_out')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Break In/Out -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="break_in" class="block text-sm font-medium text-gray-700">
                                    Break In
                                </label>
                                <input type="time" name="break_in" id="break_in" 
                                       value="{{ old('break_in', $timeLog->break_in?->format('H:i')) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('break_in')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="break_out" class="block text-sm font-medium text-gray-700">
                                    Break Out
                                </label>
                                <input type="time" name="break_out" id="break_out" 
                                       value="{{ old('break_out', $timeLog->break_out?->format('H:i')) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('break_out')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Log Type -->
                        <div>
                            <label for="log_type" class="block text-sm font-medium text-gray-700">
                                Log Type <span class="text-red-500">*</span>
                            </label>
                            <select name="log_type" id="log_type" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select Log Type</option>
                                <option value="regular" {{ old('log_type', $timeLog->log_type) == 'regular' ? 'selected' : '' }}>Regular</option>
                                <option value="overtime" {{ old('log_type', $timeLog->log_type) == 'overtime' ? 'selected' : '' }}>Overtime</option>
                                <option value="holiday" {{ old('log_type', $timeLog->log_type) == 'holiday' ? 'selected' : '' }}>Holiday</option>
                                <option value="rest_day" {{ old('log_type', $timeLog->log_type) == 'rest_day' ? 'selected' : '' }}>Rest Day</option>
                            </select>
                            @error('log_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Checkboxes -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex items-center">
                                <input type="checkbox" name="is_holiday" id="is_holiday" value="1" 
                                       {{ old('is_holiday', $timeLog->is_holiday) ? 'checked' : '' }}
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="is_holiday" class="ml-2 block text-sm text-gray-900">
                                    Holiday
                                </label>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="is_rest_day" id="is_rest_day" value="1" 
                                       {{ old('is_rest_day', $timeLog->is_rest_day) ? 'checked' : '' }}
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="is_rest_day" class="ml-2 block text-sm text-gray-900">
                                    Rest Day
                                </label>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div>
                            <label for="remarks" class="block text-sm font-medium text-gray-700">
                                Remarks
                            </label>
                            <textarea name="remarks" id="remarks" rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                      placeholder="Optional notes about this time log...">{{ old('remarks', $timeLog->remarks) }}</textarea>
                            @error('remarks')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Current Hours Summary (if available) -->
                        @if($timeLog->total_hours > 0)
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Current Hours Summary</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600">Total:</span>
                                    <span class="font-medium">{{ number_format($timeLog->total_hours, 2) }}h</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Regular:</span>
                                    <span class="font-medium">{{ number_format($timeLog->regular_hours, 2) }}h</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Overtime:</span>
                                    <span class="font-medium">{{ number_format($timeLog->overtime_hours, 2) }}h</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Late:</span>
                                    <span class="font-medium">{{ number_format($timeLog->late_hours, 2) }}h</span>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">Hours will be recalculated when you update the time log.</p>
                        </div>
                        @endif

                        <!-- Submit Button -->
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('time-logs.show', $timeLog) }}"
                               class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Cancel
                            </a>
                            <button type="submit"
                                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Update Time Log
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-calculate hours when time values change
        document.addEventListener('DOMContentLoaded', function() {
            const timeInputs = ['time_in', 'time_out', 'break_in', 'break_out'];
            
            timeInputs.forEach(inputId => {
                const input = document.getElementById(inputId);
                if (input) {
                    input.addEventListener('change', function() {
                        // You could add real-time calculation here if needed
                        console.log('Time updated:', inputId, input.value);
                    });
                }
            });
        });
    </script>
</x-app-layout>
