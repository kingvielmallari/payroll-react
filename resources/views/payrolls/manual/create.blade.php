<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Create Manual Payroll') }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ ucfirst(str_replace('_', ' ', $selectedSchedule->code)) }} schedule - Select employees manually
                </p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('payrolls.manual.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Back to Manual
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('payrolls.manual.store') }}" method="POST" id="payrollForm">
                @csrf
                <input type="hidden" name="pay_schedule" value="{{ $selectedSchedule->code }}">
                <input type="hidden" name="payroll_type" value="manual">

                <!-- Payroll Details -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Payroll Details</h3>
                        
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

                <!-- Employee Selection -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Employee Selection</h3>
                            <div class="flex items-center space-x-4">
                                <span class="text-sm text-gray-500">
                                    <span id="selectedCount">0</span> of {{ $employees->count() }} selected
                                </span>
                                <div class="flex space-x-2">
                                    <button type="button" id="selectAll" class="text-sm bg-blue-100 text-blue-700 hover:bg-blue-200 px-3 py-1 rounded">
                                        Select All
                                    </button>
                                    <button type="button" id="selectActive" class="text-sm bg-green-100 text-green-700 hover:bg-green-200 px-3 py-1 rounded">
                                        Active Only
                                    </button>
                                    <button type="button" id="clearAll" class="text-sm bg-gray-100 text-gray-700 hover:bg-gray-200 px-3 py-1 rounded">
                                        Clear All
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Search and Filter -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                            <div class="md:col-span-2">
                                <label for="employeeSearch" class="block text-sm font-medium text-gray-700 mb-1">Search Employees</label>
                                <div class="relative">
                                    <input type="text" id="employeeSearch" placeholder="Search by name, employee number, or department..."
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 pl-10">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <label for="statusFilter" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select id="statusFilter" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All Statuses</option>
                                    <option value="active">Active Only</option>
                                    <option value="inactive">Inactive Only</option>
                                </select>
                            </div>

                            <div>
                                <label for="departmentFilter" class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                                <select id="departmentFilter" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All Departments</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        @if($employees->count() > 0)
                            <!-- Employee List -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">
                                                <input type="checkbox" id="selectAllCheckbox" 
                                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                            </th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salary</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200" id="employeeTableBody">
                                        @foreach($employees as $employee)
                                            <tr class="hover:bg-gray-50 employee-row" 
                                                data-employee-id="{{ $employee->id }}"
                                                data-status="{{ $employee->employment_status === 'active' ? 'active' : 'inactive' }}"
                                                data-department="{{ $employee->department_id }}"
                                                data-search-text="{{ strtolower($employee->first_name . ' ' . $employee->last_name . ' ' . $employee->employee_number . ' ' . ($employee->department->name ?? '')) }}">
                                                <td class="px-4 py-4 whitespace-nowrap">
                                                    <input type="checkbox" name="employee_ids[]" value="{{ $employee->id }}" 
                                                           class="employee-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                           {{ $employee->employment_status === 'active' ? '' : '' }}>
                                                </td>
                                                <td class="px-4 py-4 whitespace-nowrap">
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
                                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $employee->department->name ?? 'No Department' }}
                                                </td>
                                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    {{ $employee->position ?? 'No Position' }}
                                                </td>
                                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    ₱{{ number_format($employee->basic_salary ?? 0, 2) }}
                                                </td>
                                                <td class="px-4 py-4 whitespace-nowrap">
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                                        {{ $employee->employment_status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                        {{ $employee->employment_status === 'active' ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- No results message (initially hidden) -->
                            <div id="noResults" class="hidden text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No employees found</h3>
                                <p class="mt-1 text-sm text-gray-500">Try adjusting your search or filter criteria.</p>
                            </div>

                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M12 4.354a48.003 48.003 0 0-12 6.364 48.003 48.003 0 0012-6.364zm0 0c4.882 0 9.439.904 13.314 2.514a2.998 2.998 0 01.686 5.022L12 12l-14-8.11a2.998 2.998 0 01.686-5.022A47.672 47.672 0 0112 4.354z"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No employees found</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    There are no employees assigned to the {{ $selectedSchedule->name }} schedule.
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
                                <a href="{{ route('payrolls.manual.index') }}" 
                                   class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                    Cancel
                                </a>
                                <button type="submit" id="createButton" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-6 rounded opacity-50 cursor-not-allowed" disabled>
                                    Create Manual Payroll
                                </button>
                            </div>
                        </div>

                        <div id="noEmployeesSelected" class="text-sm text-red-600 mt-2">
                            Please select at least one employee to create the payroll
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const employeeCheckboxes = document.querySelectorAll('.employee-checkbox');
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const selectedCountSpan = document.getElementById('selectedCount');
            const createButton = document.getElementById('createButton');
            const noEmployeesMessage = document.getElementById('noEmployeesSelected');
            const searchInput = document.getElementById('employeeSearch');
            const statusFilter = document.getElementById('statusFilter');
            const departmentFilter = document.getElementById('departmentFilter');
            const employeeRows = document.querySelectorAll('.employee-row');
            const tableBody = document.getElementById('employeeTableBody');
            const noResults = document.getElementById('noResults');

            // Update selected count and button state
            function updateSelection() {
                const visibleCheckboxes = document.querySelectorAll('.employee-row:not(.hidden) .employee-checkbox');
                const selectedCheckboxes = document.querySelectorAll('.employee-checkbox:checked');
                const visibleSelectedCheckboxes = document.querySelectorAll('.employee-row:not(.hidden) .employee-checkbox:checked');
                
                selectedCountSpan.textContent = selectedCheckboxes.length;
                
                // Update create button
                if (selectedCheckboxes.length > 0) {
                    createButton.classList.remove('opacity-50', 'cursor-not-allowed');
                    createButton.disabled = false;
                    noEmployeesMessage.classList.add('hidden');
                } else {
                    createButton.classList.add('opacity-50', 'cursor-not-allowed');
                    createButton.disabled = true;
                    noEmployeesMessage.classList.remove('hidden');
                }
                
                // Update select all checkbox
                if (visibleCheckboxes.length > 0) {
                    if (visibleSelectedCheckboxes.length === visibleCheckboxes.length) {
                        selectAllCheckbox.checked = true;
                        selectAllCheckbox.indeterminate = false;
                    } else if (visibleSelectedCheckboxes.length > 0) {
                        selectAllCheckbox.checked = false;
                        selectAllCheckbox.indeterminate = true;
                    } else {
                        selectAllCheckbox.checked = false;
                        selectAllCheckbox.indeterminate = false;
                    }
                } else {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = false;
                }
            }

            // Filter employees
            function filterEmployees() {
                const searchTerm = searchInput.value.toLowerCase();
                const statusFilterValue = statusFilter.value;
                const departmentFilterValue = departmentFilter.value;
                let visibleCount = 0;

                employeeRows.forEach(row => {
                    const searchText = row.dataset.searchText;
                    const status = row.dataset.status;
                    const department = row.dataset.department;
                    
                    let show = true;
                    
                    // Search filter
                    if (searchTerm && !searchText.includes(searchTerm)) {
                        show = false;
                    }
                    
                    // Status filter
                    if (statusFilterValue && status !== statusFilterValue) {
                        show = false;
                    }
                    
                    // Department filter
                    if (departmentFilterValue && department !== departmentFilterValue) {
                        show = false;
                    }
                    
                    if (show) {
                        row.classList.remove('hidden');
                        visibleCount++;
                    } else {
                        row.classList.add('hidden');
                    }
                });

                // Show/hide no results message
                if (visibleCount === 0) {
                    noResults.classList.remove('hidden');
                    tableBody.classList.add('hidden');
                } else {
                    noResults.classList.add('hidden');
                    tableBody.classList.remove('hidden');
                }

                updateSelection();
            }

            // Event listeners
            employeeCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelection);
            });

            selectAllCheckbox.addEventListener('change', function() {
                const visibleCheckboxes = document.querySelectorAll('.employee-row:not(.hidden) .employee-checkbox');
                visibleCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateSelection();
            });

            // Quick selection buttons
            document.getElementById('selectAll').addEventListener('click', function() {
                const visibleCheckboxes = document.querySelectorAll('.employee-row:not(.hidden) .employee-checkbox');
                visibleCheckboxes.forEach(checkbox => {
                    checkbox.checked = true;
                });
                updateSelection();
            });

            document.getElementById('selectActive').addEventListener('click', function() {
                employeeCheckboxes.forEach(checkbox => {
                    const row = checkbox.closest('.employee-row');
                    if (!row.classList.contains('hidden') && row.dataset.status === 'active') {
                        checkbox.checked = true;
                    } else {
                        checkbox.checked = false;
                    }
                });
                updateSelection();
            });

            document.getElementById('clearAll').addEventListener('click', function() {
                employeeCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                updateSelection();
            });

            // Search and filter listeners
            searchInput.addEventListener('input', filterEmployees);
            statusFilter.addEventListener('change', filterEmployees);
            departmentFilter.addEventListener('change', filterEmployees);

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

            // Initial updates
            updateSelection();
        });
    </script>
</x-app-layout>
