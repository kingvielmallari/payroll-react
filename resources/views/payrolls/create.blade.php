<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Create New Payroll') }}
            </h2>
            <a href="{{ route('payrolls.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Back to Payrolls
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Error Messages -->
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-red-800">Validation Errors</h4>
                            <div class="text-sm text-red-700 mt-2">
                                <ul class="list-disc pl-5 space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if (session('success'))
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-green-800">Success</h4>
                            <div class="text-sm text-green-700 mt-1">
                                {{ session('success') }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Regular Manual Payroll Form -->
            @if(!$selectedSchedule)
            <!-- Schedule Selection -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Select Pay Schedule</h3>
                    <p class="text-gray-600 mb-6">Choose the pay schedule type to view available payroll periods for the current month.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @php
                            function getOrdinal($number) {
                                if ($number % 100 >= 11 && $number % 100 <= 13) {
                                    return $number . 'th';
                                }
                                switch ($number % 10) {
                                    case 1: return $number . 'st';
                                    case 2: return $number . 'nd';
                                    case 3: return $number . 'rd';
                                    default: return $number . 'th';
                                }
                            }
                        @endphp
                        @foreach($scheduleSettings as $schedule)
                        <div class="relative h-full">
                            @if($schedule->is_active)
                                <a href="{{ route('payrolls.create', ['schedule' => $schedule->code]) }}" 
                                   class="flex flex-col h-full p-6 border-2 border-gray-200 rounded-xl hover:border-indigo-300 hover:bg-indigo-50 transition-all duration-200 shadow-sm hover:shadow-md group">
                            @else
                                <div class="flex flex-col h-full p-6 border-2 border-gray-200 rounded-xl opacity-50 cursor-not-allowed bg-gray-50">
                            @endif
                            
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-12 h-12 {{ $schedule->is_active ? 'bg-indigo-100 group-hover:bg-indigo-200' : 'bg-gray-100' }} rounded-lg flex items-center justify-center transition-colors">
                                    <svg class="w-6 h-6 {{ $schedule->is_active ? 'text-indigo-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($schedule->code === 'weekly')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        @elseif($schedule->code === 'semi_monthly')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        @endif
                                    </svg>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $schedule->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $schedule->is_active ? 'Active' : 'Disabled' }}
                                </span>
                            </div>
                            
                            <h4 class="text-base font-semibold {{ $schedule->is_active ? 'text-gray-900' : 'text-gray-500' }} mb-2">
                                {{ $schedule->name }}
                            </h4>
                            
                            @if($schedule->is_active && isset($schedule->current_period_display))
                                <div class="bg-blue-50 border border-blue-200 rounded-md p-2 mb-3">
                                    <p class="text-sm font-medium text-blue-800">Current Payroll Period</p>
                                    <p class="text-lg font-bold text-blue-900">{{ $schedule->current_period_display }}</p>
                                </div>
                            @endif
                            
            <div class="text-sm {{ $schedule->is_active ? 'text-gray-600' : 'text-gray-400' }} space-y-1 flex-grow">
                @if($schedule->is_active)
                    @php
                        $periods = $schedule->cutoff_periods ?? [];
                    @endphp
                    
                    @if($schedule->code === 'weekly' && isset($periods[0]))
                        <p><strong>Period:</strong> {{ ucfirst($periods[0]['start_day']) }} to {{ ucfirst($periods[0]['end_day']) }}</p>
                        <p><strong>Pay Day:</strong> {{ ucfirst($periods[0]['pay_day']) }}</p>
                    @elseif($schedule->code === 'semi_monthly' && count($periods) >= 2)
                        <p><strong>Period 1:</strong> {{ getOrdinal($periods[0]['start_day']) }}-{{ getOrdinal($periods[0]['end_day']) }} → Pay {{ getOrdinal($periods[0]['pay_date']) }}</p>
                        <p><strong>Period 2:</strong> {{ getOrdinal($periods[1]['start_day']) }}-{{ getOrdinal($periods[1]['end_day']) }} → Pay {{ getOrdinal($periods[1]['pay_date']) }}</p>
                    @elseif($schedule->code === 'monthly' && isset($periods[0]))
                        <p><strong>Period:</strong> {{ getOrdinal($periods[0]['start_day']) }} to {{ getOrdinal($periods[0]['end_day']) }} of month</p>
                        <p><strong>Pay Day:</strong> {{ getOrdinal($periods[0]['pay_date']) }} of month</p>
                    @endif
                    
                    @if($schedule->move_if_holiday || $schedule->move_if_weekend)
                        <p class="text-xs text-blue-600">
                            <strong>Holiday Rule:</strong> Move {{ $schedule->move_direction }} if {{ $schedule->move_if_holiday ? 'holiday' : '' }}{{ $schedule->move_if_holiday && $schedule->move_if_weekend ? '/' : '' }}{{ $schedule->move_if_weekend ? 'weekend' : '' }}
                        </p>
                    @endif
                @endif
            </div>                            @if(!$schedule->is_active)
                                <div class="mt-3 text-xs text-red-600 font-medium">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                    This schedule is disabled in settings
                                </div>
                            @endif
                            
                            @if($schedule->is_active)
                                </a>
                            @else
                                </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @else
            <!-- Payroll Creation Form -->
            <form method="POST" action="{{ route('payrolls.store') }}" class="space-y-6" id="payroll-form">
                @csrf
                
                <!-- Schedule and Period Selection -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Selected Schedule Info -->
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        @if($selectedSchedule === 'weekly')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        @elseif($selectedSchedule === 'semi_monthly')
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        @endif
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">{{ ucwords(str_replace('_', ' ', $selectedSchedule)) }} Payroll</h3>
                                    <p class="text-sm text-gray-600">Current month periods only</p>
                                </div>
                            </div>
                            <a href="{{ route('payrolls.create') }}" 
                               class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Change Schedule
                            </a>
                        </div>

                        <!-- Period Selection -->
                        @if(count($availablePeriods) > 0)
                        <div class="mb-6">
                            <label for="period_select" class="block text-sm font-medium text-gray-700 mb-3">
                                Select Pay Period <span class="text-red-500">*</span>
                                <span class="text-xs text-gray-500 block mt-1">Available periods for {{ \Carbon\Carbon::now()->format('F Y') }}</span>
                            </label>
                            <select name="selected_period" id="period_select" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                <option value="">Choose a pay period...</option>
                                @foreach($availablePeriods as $period)
                                    <option value="{{ base64_encode(json_encode($period)) }}" 
                                            data-pay-schedule="{{ $period['pay_schedule'] }}"
                                            {{ $selectedPeriod && $selectedPeriod['id'] === $period['id'] ? 'selected' : '' }}
                                            @if(isset($period['is_current']) && $period['is_current']) data-current="true" @endif>
                                        {{ $period['period_display'] }} (Pay: {{ $period['pay_date_display'] }})
                                        @if(isset($period['is_current']) && $period['is_current']) - Current Period @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('selected_period')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            
                            <!-- Hidden fields for period details -->
                            <input type="hidden" name="period_start" id="period_start" value="{{ $selectedPeriod['period_start'] ?? '' }}">
                            <input type="hidden" name="period_end" id="period_end" value="{{ $selectedPeriod['period_end'] ?? '' }}">
                            <input type="hidden" name="pay_date" id="pay_date" value="{{ $selectedPeriod['pay_date'] ?? '' }}">
                            <input type="hidden" name="payroll_type" id="payroll_type" value="regular">
                        </div>
                        @else
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-yellow-800">No Available Periods</h4>
                                    <p class="text-sm text-yellow-700 mt-1">
                                        No payroll periods are available for {{ ucwords(str_replace('_', ' ', $selectedSchedule)) }} schedule in the current month.
                                        This may be because the payroll settings need to be configured or all periods have already been processed.
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Employee Selection -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Select Employees</h3>
                            <div class="space-x-2">
                                <button type="button" id="selectAll" class="text-sm text-blue-600 hover:text-blue-800">
                                    Select All
                                </button>
                                <button type="button" id="deselectAll" class="text-sm text-gray-600 hover:text-gray-800">
                                    Deselect All
                                </button>
                            </div>
                        </div>

                        @if($employees->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($employees as $employee)
                            <div class="relative">
                                <label class="group flex items-start space-x-4 p-6 border-2 border-gray-200 rounded-xl hover:border-indigo-300 hover:bg-indigo-50 cursor-pointer transition-all duration-200 shadow-sm hover:shadow-md">
                                    <input type="checkbox" name="employee_ids[]" value="{{ $employee->id }}"
                                           class="employee-checkbox mt-1.5 h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded transition-all"
                                           {{ in_array($employee->id, old('employee_ids', [])) ? 'checked' : '' }}>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-base font-semibold text-gray-900 mb-2">
                                            {{ $employee->first_name }} {{ $employee->last_name }}
                                        </div>
                                        <div class="space-y-2">
                                            <div class="text-sm text-gray-600">
                                                <span class="font-medium">{{ $employee->employee_number }}</span>
                                            </div>
                                            <div class="text-sm text-gray-600">
                                                {{ $employee->position->title ?? 'No Position' }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ $employee->department->name ?? 'No Department' }}
                                            </div>
                                            <div class="flex items-center justify-between mt-3">
                                                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full
                                                    {{ $employee->pay_schedule == 'weekly' ? 'bg-blue-100 text-blue-800' : 
                                                       ($employee->pay_schedule == 'semi_monthly' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800') }}">
                                                    {{ ucfirst(str_replace('_', '-', $employee->pay_schedule ?? 'monthly')) }}
                                                </span>
                                            </div>
                                            <div class="mt-3 pt-3 border-t border-gray-100">
                                                @if($employee->pay_schedule == 'weekly')
                                                    <span class="text-lg font-bold text-blue-600">
                                                        ₱{{ number_format($employee->weekly_rate ?? ($employee->basic_salary / 4.33), 2) }}
                                                    </span>
                                                @elseif($employee->pay_schedule == 'semi_monthly')
                                                    <span class="text-lg font-bold text-green-600">
                                                        ₱{{ number_format($employee->semi_monthly_rate ?? ($employee->basic_salary / 2), 2) }}
                                                    </span>
                                                @else
                                                    <span class="text-lg font-bold text-purple-600">
                                                        ₱{{ number_format($employee->basic_salary, 2) }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            @endforeach
                        </div>
                        @error('employee_ids')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @error('employee_ids.*')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @else
                        <div class="text-center py-8">
                            <div class="text-gray-500 text-lg">No active employees found</div>
                            <div class="mt-2 text-sm text-gray-400">
                                Please add employees first before creating a payroll.
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Submit Buttons -->
                @if($employees->count() > 0)
                <div class="bg-white border-t border-gray-200 px-6 py-4 flex justify-between items-center sticky bottom-0 shadow-lg">
                    <div class="text-sm text-gray-500">
                        Select employees to include in the payroll
                    </div>
                    <div class="flex space-x-4">
                        <a href="{{ route('payrolls.index') }}" 
                           class="inline-flex items-center px-6 py-3 border-2 border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200">
                            Cancel
                        </a>
                        <button type="submit"
                                class="inline-flex items-center px-6 py-3 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Create Payroll
                        </button>
                    </div>
                </div>
                @endif
            </form>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Payroll create form loaded');
            
            const selectAllBtn = document.getElementById('selectAll');
            const deselectAllBtn = document.getElementById('deselectAll');
            const checkboxes = document.querySelectorAll('.employee-checkbox');

            if (selectAllBtn) {
                selectAllBtn.addEventListener('click', function() {
                    checkboxes.forEach(checkbox => checkbox.checked = true);
                });
            }

            if (deselectAllBtn) {
                deselectAllBtn.addEventListener('click', function() {
                    checkboxes.forEach(checkbox => checkbox.checked = false);
                });
            }

            // Handle period selection with redirect to load employees
            const periodSelect = document.getElementById('period_select');
            if (periodSelect) {
                periodSelect.addEventListener('change', function() {
                    if (this.value) {
                        // Get current URL parameters
                        const currentUrl = new URL(window.location);
                        const periodData = JSON.parse(atob(this.value));
                        
                        // Set the period parameter and redirect
                        currentUrl.searchParams.set('period', periodData.id);
                        window.location.href = currentUrl.toString();
                    }
                });
                
                // Populate hidden fields if a period is already selected
                if (periodSelect.value) {
                    try {
                        const periodData = JSON.parse(atob(periodSelect.value));
                        const periodStartField = document.getElementById('period_start');
                        const periodEndField = document.getElementById('period_end');
                        const payDateField = document.getElementById('pay_date');
                        
                        if (periodStartField) periodStartField.value = periodData.period_start || '';
                        if (periodEndField) periodEndField.value = periodData.period_end || '';
                        if (payDateField) payDateField.value = periodData.pay_date || '';
                    } catch (e) {
                        console.error('Error parsing selected period data:', e);
                    }
                }
            }

            // Form validation
            const payrollForm = document.getElementById('payroll-form');
            if (payrollForm) {
                payrollForm.addEventListener('submit', function(e) {
                    const periodSelect = document.getElementById('period_select');
                    const selectedEmployees = document.querySelectorAll('input[name="employee_ids[]"]:checked');
                    
                    if (!periodSelect || !periodSelect.value) {
                        e.preventDefault();
                        alert('Please select a pay period');
                        return false;
                    }
                    
                    if (selectedEmployees.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one employee');
                        return false;
                    }
                    
                    // Ensure hidden fields are populated before submission
                    if (periodSelect.value) {
                        try {
                            const periodData = JSON.parse(atob(periodSelect.value));
                            const periodStartField = document.getElementById('period_start');
                            const periodEndField = document.getElementById('period_end');
                            const payDateField = document.getElementById('pay_date');
                            
                            if (periodStartField) periodStartField.value = periodData.period_start || '';
                            if (periodEndField) periodEndField.value = periodData.period_end || '';
                            if (payDateField) payDateField.value = periodData.pay_date || '';
                        } catch (e) {
                            console.error('Error populating hidden fields:', e);
                            e.preventDefault();
                            alert('Error processing period data. Please refresh and try again.');
                            return false;
                        }
                    }
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
