@extends('layouts.app')

@section('content')
    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                                Submit Leave Request
                            </h3>
                            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                                Fill out the form below to submit your paid leave request.
                            </p>
                        </div>
                        <a href="{{ route('leave-requests.index') }}" 
                           class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            Back to Leave Requests
                        </a>
                    </div>
                </div>

                <form action="{{ route('leave-requests.store') }}" method="POST" class="p-6">
                    @csrf
                    
                    <div class="grid grid-cols-1 gap-6">
                        <!-- Leave Type -->
                        <div>
                            <label for="leave_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Leave Type <span class="text-red-500">*</span>
                            </label>
                            <select id="leave_type" name="leave_type" required
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100 @error('leave_type') border-red-500 @enderror">
                                <option value="">Select Leave Type</option>
                                <option value="sick" {{ old('leave_type') === 'sick' ? 'selected' : '' }}>Sick Leave</option>
                                <option value="vacation" {{ old('leave_type') === 'vacation' ? 'selected' : '' }}>Vacation Leave</option>
                                <option value="emergency" {{ old('leave_type') === 'emergency' ? 'selected' : '' }}>Emergency Leave</option>
                                <option value="maternity" {{ old('leave_type') === 'maternity' ? 'selected' : '' }}>Maternity Leave</option>
                                <option value="paternity" {{ old('leave_type') === 'paternity' ? 'selected' : '' }}>Paternity Leave</option>
                                <option value="bereavement" {{ old('leave_type') === 'bereavement' ? 'selected' : '' }}>Bereavement Leave</option>
                                <option value="special" {{ old('leave_type') === 'special' ? 'selected' : '' }}>Special Leave</option>
                            </select>
                            @error('leave_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Date Range -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Start Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" id="start_date" name="start_date" required
                                       value="{{ old('start_date') }}"
                                       min="{{ date('Y-m-d') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100 @error('start_date') border-red-500 @enderror">
                                @error('start_date')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    End Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" id="end_date" name="end_date" required
                                       value="{{ old('end_date') }}"
                                       min="{{ date('Y-m-d') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100 @error('end_date') border-red-500 @enderror">
                                @error('end_date')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Days Requested (Auto-calculated) -->
                        <div>
                            <label for="days_requested" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Days Requested
                            </label>
                            <input type="number" id="days_requested" name="days_requested" readonly
                                   value="{{ old('days_requested') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-gray-100 dark:bg-gray-600 dark:text-gray-100">
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Number of days will be calculated automatically based on your selected date range.
                            </p>
                        </div>

                        <!-- Reason -->
                        <div>
                            <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Reason <span class="text-red-500">*</span>
                            </label>
                            <textarea id="reason" name="reason" rows="4" required
                                      class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100 @error('reason') border-red-500 @enderror"
                                      placeholder="Please provide a detailed reason for your leave request...">{{ old('reason') }}</textarea>
                            @error('reason')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <a href="{{ route('leave-requests.index') }}" 
                               class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Submit Leave Request
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');
            const daysRequested = document.getElementById('days_requested');

            function calculateDays() {
                if (startDate.value && endDate.value) {
                    const start = new Date(startDate.value);
                    const end = new Date(endDate.value);
                    
                    if (end >= start) {
                        // Calculate business days (excluding weekends)
                        let count = 0;
                        const current = new Date(start);
                        
                        while (current <= end) {
                            const dayOfWeek = current.getDay();
                            if (dayOfWeek !== 0 && dayOfWeek !== 6) { // Not Sunday (0) or Saturday (6)
                                count++;
                            }
                            current.setDate(current.getDate() + 1);
                        }
                        
                        daysRequested.value = count;
                    } else {
                        daysRequested.value = '';
                    }
                } else {
                    daysRequested.value = '';
                }
            }

            // Update end date minimum when start date changes
            startDate.addEventListener('change', function() {
                endDate.min = this.value;
                if (endDate.value && endDate.value < this.value) {
                    endDate.value = this.value;
                }
                calculateDays();
            });

            endDate.addEventListener('change', calculateDays);
            
            // Calculate on page load if dates are already set
            calculateDays();
        });
    </script>
@endsection