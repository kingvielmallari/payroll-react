<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Add New Time Log
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('time-logs.store') }}" class="space-y-6">
                        @csrf

                        <!-- Employee Selection -->
                        <div>
                            <label for="employee_id" class="block text-sm font-medium text-gray-700">
                                Employee <span class="text-red-500">*</span>
                            </label>
                            <select name="employee_id" id="employee_id" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select Employee</option>
                                @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
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
                            <input type="date" name="log_date" id="log_date" value="{{ old('log_date', date('Y-m-d')) }}" required
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
                                <input type="time" name="time_in" id="time_in" value="{{ old('time_in') }}" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('time_in')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="time_out" class="block text-sm font-medium text-gray-700">
                                    Time Out
                                </label>
                                <input type="time" name="time_out" id="time_out" value="{{ old('time_out') }}"
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
                                <input type="time" name="break_in" id="break_in" value="{{ old('break_in') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @error('break_in')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="break_out" class="block text-sm font-medium text-gray-700">
                                    Break Out
                                </label>
                                <input type="time" name="break_out" id="break_out" value="{{ old('break_out') }}"
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
                                <option value="regular" {{ old('log_type') == 'regular' ? 'selected' : '' }}>Regular</option>
                                <option value="overtime" {{ old('log_type') == 'overtime' ? 'selected' : '' }}>Overtime</option>
                                <option value="holiday" {{ old('log_type') == 'holiday' ? 'selected' : '' }}>Holiday</option>
                                <option value="rest_day" {{ old('log_type') == 'rest_day' ? 'selected' : '' }}>Rest Day</option>
                            </select>
                            @error('log_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Special Day Flags -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex items-center">
                                <input type="checkbox" name="is_holiday" id="is_holiday" value="1" 
                                       {{ old('is_holiday') ? 'checked' : '' }}
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="is_holiday" class="ml-2 block text-sm text-gray-700">
                                    Holiday
                                </label>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="is_rest_day" id="is_rest_day" value="1" 
                                       {{ old('is_rest_day') ? 'checked' : '' }}
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="is_rest_day" class="ml-2 block text-sm text-gray-700">
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
                                      placeholder="Optional remarks or notes">{{ old('remarks') }}</textarea>
                            @error('remarks')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Time Calculation Preview -->
                        <div id="time-preview" class="bg-blue-50 p-4 rounded-lg hidden">
                            <h4 class="text-sm font-medium text-blue-900 mb-2">Time Calculation Preview</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <span class="text-blue-700">Total Hours:</span>
                                    <span id="total-hours" class="text-blue-900 font-medium">0.00</span>
                                </div>
                                <div>
                                    <span class="text-blue-700">Regular Hours:</span>
                                    <span id="regular-hours" class="text-blue-900 font-medium">0.00</span>
                                </div>
                                <div>
                                    <span class="text-blue-700">Overtime Hours:</span>
                                    <span id="overtime-hours" class="text-blue-900 font-medium">0.00</span>
                                </div>
                                <div>
                                    <span class="text-blue-700">Late Hours:</span>
                                    <span id="late-hours" class="text-red-600 font-medium">0.00</span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="border-t border-gray-200 pt-6">
                            <div class="flex justify-end space-x-3">
                                <a href="{{ route('time-logs.index') }}" 
                                   class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Cancel
                                </a>
                                <button type="submit" 
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Create Time Log
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for time calculation -->
    <script>
        function calculateHours() {
            const timeIn = document.getElementById('time_in').value;
            const timeOut = document.getElementById('time_out').value;
            const breakIn = document.getElementById('break_in').value;
            const breakOut = document.getElementById('break_out').value;
            
            if (!timeIn || !timeOut) {
                document.getElementById('time-preview').classList.add('hidden');
                return;
            }

            // Calculate total minutes worked
            const timeInMinutes = timeToMinutes(timeIn);
            const timeOutMinutes = timeToMinutes(timeOut);
            let totalMinutes = timeOutMinutes - timeInMinutes;

            // Subtract break time if both break times are provided
            if (breakIn && breakOut) {
                const breakInMinutes = timeToMinutes(breakIn);
                const breakOutMinutes = timeToMinutes(breakOut);
                const breakDuration = breakOutMinutes - breakInMinutes;
                totalMinutes -= breakDuration;
            }

            const totalHours = totalMinutes / 60;
            
            // Calculate regular and overtime hours
            const standardHours = 8;
            let regularHours = totalHours <= standardHours ? totalHours : standardHours;
            let overtimeHours = totalHours > standardHours ? totalHours - standardHours : 0;

            // Calculate late hours (if time in is after 8:00 AM)
            const standardTimeIn = timeToMinutes('08:00');
            let lateHours = timeInMinutes > standardTimeIn ? (timeInMinutes - standardTimeIn) / 60 : 0;

            // Update display
            document.getElementById('total-hours').textContent = totalHours.toFixed(2);
            document.getElementById('regular-hours').textContent = regularHours.toFixed(2);
            document.getElementById('overtime-hours').textContent = overtimeHours.toFixed(2);
            document.getElementById('late-hours').textContent = lateHours.toFixed(2);
            
            document.getElementById('time-preview').classList.remove('hidden');
        }

        function timeToMinutes(timeString) {
            const [hours, minutes] = timeString.split(':').map(Number);
            return hours * 60 + minutes;
        }

        // Add event listeners
        document.getElementById('time_in').addEventListener('change', calculateHours);
        document.getElementById('time_out').addEventListener('change', calculateHours);
        document.getElementById('break_in').addEventListener('change', calculateHours);
        document.getElementById('break_out').addEventListener('change', calculateHours);
    </script>
</x-app-layout>
