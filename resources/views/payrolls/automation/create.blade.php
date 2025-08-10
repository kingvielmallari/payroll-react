<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Automated Payroll Preview') }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ ucfirst(str_replace('_', ' ', $selectedSchedule->code)) }} schedule - All {{ $employees->count() }} active employees automatically included
                </p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('payrolls.automation.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Back to Automation
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('payrolls.automation.store') }}" method="POST" id="payrollForm">
                @csrf
                <input type="hidden" name="pay_schedule" value="{{ $selectedSchedule->code }}">
                <input type="hidden" name="payroll_type" value="automated">

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Automated Payroll Details</h3>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-blue-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                                <p class="text-sm text-blue-700">
                                    <strong>Automated Process:</strong> All active employees for this schedule are automatically included. Review and adjust the details below before proceeding.
                                </p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label for="period_start" class="block text-sm font-medium text-gray-700 mb-1">Period Start <span class="text-red-500">*</span></label>
                                <input type="date" name="period_start" id="period_start" required
                                       value="{{ $suggestedPeriod['start'] ?? '' }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('period_start') border-red-500 @enderror">
                                @error('period_start')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="period_end" class="block text-sm font-medium text-gray-700 mb-1">Period End <span class="text-red-500">*</span></label>
                                <input type="date" name="period_end" id="period_end" required
                                       value="{{ $suggestedPeriod['end'] ?? '' }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('period_end') border-red-500 @enderror">
                                @error('period_end')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="pay_date" class="block text-sm font-medium text-gray-700 mb-1">Pay Date <span class="text-red-500">*</span></label>
                                <input type="date" name="pay_date" id="pay_date" required
                                       value="{{ $suggestedPeriod['pay_date'] ?? '' }}"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('pay_date') border-red-500 @enderror">
                                @error('pay_date')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="payroll_number" class="block text-sm font-medium text-gray-700 mb-1">Payroll Number</label>
                                <input type="text" name="payroll_number" id="payroll_number"
                                       value="{{ $suggestedPayrollNumber ?? '' }}" placeholder="Auto-generated if empty"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('payroll_number') border-red-500 @enderror">
                                @error('payroll_number')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea name="description" id="description" rows="2" placeholder="Optional description for this payroll"
                                      class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Employee Selection Summary -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Included Employees</h3>
                            <span class="px-3 py-1 text-sm font-medium bg-blue-100 text-blue-800 rounded-full">
                                {{ $employees->count() }} employees
                            </span>
                        </div>

                        @if($employees->count() > 0)
                            <!-- Employee Summary Cards -->
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                                @foreach($employees->take(6) as $employee)
                                    <div class="border border-gray-200 rounded-lg p-3">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0">
                                                <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                                    <span class="text-xs font-medium text-gray-600">
                                                        {{ substr($employee->first_name, 0, 1) }}{{ substr($employee->last_name, 0, 1) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ml-3 min-w-0 flex-1">
                                                <p class="text-sm font-medium text-gray-900 truncate">
                                                    {{ $employee->first_name }} {{ $employee->last_name }}
                                                </p>
                                                <p class="text-xs text-gray-500">{{ $employee->employee_number }}</p>
                                                <p class="text-xs text-gray-500">{{ $employee->department->name ?? 'No Dept' }}</p>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Active
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if($employees->count() > 6)
                                <div class="text-center py-2">
                                    <button type="button" id="toggleAllEmployees" class="text-sm text-blue-600 hover:text-blue-800">
                                        View all {{ $employees->count() }} employees
                                    </button>
                                </div>

                                <!-- Hidden employees list -->
                                <div id="allEmployeesList" class="hidden">
                                    <div class="border-t border-gray-200 pt-4 mt-4">
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Salary</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                    @foreach($employees as $employee)
                                                        <tr class="hover:bg-gray-50">
                                                            <td class="px-4 py-2 whitespace-nowrap">
                                                                <div class="flex items-center">
                                                                    <div class="flex-shrink-0 w-8 h-8">
                                                                        <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                                                                            <span class="text-xs font-medium text-gray-600">
                                                                                {{ substr($employee->first_name, 0, 1) }}{{ substr($employee->last_name, 0, 1) }}
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                    <div class="ml-3">
                                                                        <div class="text-sm font-medium text-gray-900">
                                                                            {{ $employee->first_name }} {{ $employee->last_name }}
                                                                        </div>
                                                                        <div class="text-sm text-gray-500">{{ $employee->employee_number }}</div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">
                                                                {{ $employee->department->name ?? 'No Department' }}
                                                            </td>
                                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">
                                                                {{ $employee->position ?? 'No Position' }}
                                                            </td>
                                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                                                ₱{{ number_format($employee->basic_salary ?? 0, 2) }}
                                                            </td>
                                                            <td class="px-4 py-2 whitespace-nowrap">
                                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                                    Active
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Hidden input for all employee IDs -->
                            @foreach($employees as $employee)
                                <input type="hidden" name="employee_ids[]" value="{{ $employee->id }}">
                            @endforeach

                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M12 4.354a48.003 48.003 0 0-12 6.364 48.003 48.003 0 0012-6.364zm0 0c4.882 0 9.439.904 13.314 2.514a2.998 2.998 0 01.686 5.022L12 12l-14-8.11a2.998 2.998 0 01.686-5.022A47.672 47.672 0 0112 4.354z"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No active employees found</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    There are no active employees assigned to the {{ $selectedSchedule->name }} schedule.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="flex items-center">
                                    <input type="checkbox" id="auto_calculate" name="auto_calculate" value="1" checked
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="auto_calculate" class="ml-2 text-sm text-gray-900">
                                        Auto-calculate payroll amounts
                                    </label>
                                </div>
                            </div>

                            <div class="flex items-center space-x-3">
                                <a href="{{ route('payrolls.automation.index') }}" 
                                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                    Cancel
                                </a>
                                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded
                                    {{ $employees->count() === 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
                                    {{ $employees->count() === 0 ? 'disabled' : '' }}>
                                    Generate Payroll
                                </button>
                            </div>
                        </div>

                        @if($employees->count() === 0)
                            <p class="text-sm text-red-600 mt-2">Cannot generate payroll without active employees</p>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle all employees list
            const toggleButton = document.getElementById('toggleAllEmployees');
            const allEmployeesList = document.getElementById('allEmployeesList');
            
            if (toggleButton && allEmployeesList) {
                toggleButton.addEventListener('click', function() {
                    allEmployeesList.classList.toggle('hidden');
                    this.textContent = allEmployeesList.classList.contains('hidden') 
                        ? 'View all {{ $employees->count() }} employees'
                        : 'Hide employee list';
                });
            }

            // Date validation
            const periodStart = document.getElementById('period_start');
            const periodEnd = document.getElementById('period_end');
            const payDate = document.getElementById('pay_date');

            function validateDates() {
                if (periodStart.value && periodEnd.value) {
                    if (new Date(periodStart.value) >= new Date(periodEnd.value)) {
                        periodEnd.setCustomValidity('End date must be after start date');
                    } else {
                        periodEnd.setCustomValidity('');
                    }
                }

                if (periodEnd.value && payDate.value) {
                    if (new Date(payDate.value) < new Date(periodEnd.value)) {
                        payDate.setCustomValidity('Pay date should be after period end date');
                    } else {
                        payDate.setCustomValidity('');
                    }
                }
            }

            periodStart.addEventListener('change', validateDates);
            periodEnd.addEventListener('change', validateDates);
            payDate.addEventListener('change', validateDates);
        });
    </script>
</x-app-layout>
