<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Cash Advance Request') }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('cash-advances.show', $cashAdvance) }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    View Details
                </a>
                <a href="{{ route('cash-advances.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    Back to List
                </a>
            </div>
        </div>
    </x-slot>

    <!-- Custom styles for employee dropdown -->
    <style>
        .employee-option {
            border-bottom: 1px solid #f3f4f6;
        }
        
        .employee-option:last-child {
            border-bottom: none;
        }
        
        .employee-option:hover {
            background-color: #eef2ff !important;
            color: #312e81 !important;
        }
        
        #employee_dropdown {
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        #employee_list {
            max-height: 240px; /* Exactly 5 items at ~48px each */
        }
        
        #employee_dropdown::-webkit-scrollbar {
            width: 6px;
        }
        
        #employee_dropdown::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }
        
        #employee_dropdown::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        
        #employee_dropdown::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        .employee-search-container {
            position: relative;
        }
    </style>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Display Validation Errors -->
                    @if ($errors->any())
                    <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">There were errors with your submission:</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <ul class="list-disc list-inside space-y-1">
                                        @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Display Success Messages -->
                    @if (session('success'))
                    <div class="mb-6 bg-green-50 border border-green-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Display Error Messages -->
                    @if (session('error'))
                    <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Status Notice -->
                    @if($cashAdvance->status !== 'pending')
                    <div class="mb-6 bg-yellow-50 border border-yellow-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>Notice:</strong> This cash advance request has been <strong>{{ $cashAdvance->status }}</strong>. 
                                    You can only edit pending requests.
                                </p>
                            </div>
                        </div>
                    </div>
                    @endif

                    <form action="{{ route('cash-advances.update', $cashAdvance) }}" method="POST" id="cashAdvanceEditForm" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @method('PUT')
                        
                        @if(!$employee)
                        <!-- Employee Selection (HR/Admin only) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="employee_id" class="block text-sm font-medium text-gray-700">Employee *</label>
                                
                                <!-- Hidden input for form submission -->
                                <input type="hidden" id="employee_id" name="employee_id" value="{{ old('employee_id', $cashAdvance->employee_id) }}" required>
                                
                                <!-- Custom searchable dropdown -->
                                <div class="relative mt-1">
                                    <input type="text" 
                                           id="employee_search" 
                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('employee_id') border-red-300 @enderror" 
                                           placeholder="Type to search employees..." 
                                           autocomplete="off"
                                           value="{{ old('employee_search', $cashAdvance->employee->full_name . ' (' . $cashAdvance->employee->employee_number . ')') }}"
                                           onclick="toggleEmployeeDropdown()"
                                           onkeyup="filterEmployees()"
                                           onblur="setTimeout(hideEmployeeDropdown, 200)"
                                           onkeydown="handleKeyDown(event)">
                                    
                                    <!-- Dropdown arrow -->
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                    
                                    <!-- Dropdown list -->
                                    <div id="employee_dropdown" 
                                         class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-48 overflow-y-auto hidden">
                                        <div id="employee_list">
                                            @foreach($employees->sortBy('full_name') as $emp)
                                            <div class="employee-option cursor-pointer px-3 py-2 text-sm hover:bg-indigo-50 hover:text-indigo-900" 
                                                 data-value="{{ $emp->id }}" 
                                                 data-text="{{ $emp->full_name }} ({{ $emp->employee_number }})"
                                                 onclick="selectEmployee({{ $emp->id }}, '{{ $emp->full_name }} ({{ $emp->employee_number }})')">
                                                <div class="font-medium">{{ $emp->full_name }}</div>
                                                <div class="text-gray-500 text-xs">{{ $emp->employee_number }}</div>
                                            </div>
                                            @endforeach
                                        </div>
                                        
                                        <!-- No results message -->
                                        <div id="no_results" class="px-3 py-2 text-sm text-gray-500 hidden">
                                            No employees found
                                        </div>
                                    </div>
                                </div>
                                
                                @error('employee_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Deduction Start Period -->
                            <div>
                                <label for="starting_payroll_period" class="block text-sm font-medium text-gray-700">Deduction Start Period *</label>
                                <select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('starting_payroll_period') border-red-300 @enderror" 
                                        id="starting_payroll_period" name="starting_payroll_period" required disabled>
                                    <option value="">Loading payroll periods...</option>
                                </select>
                                @error('starting_payroll_period')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-muted text-gray-500">Choose the payroll period when deductions should start</p>
                            </div>
                        </div>
                        @else
                        <!-- Hidden field for employee users -->
                        <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                        <div class="mb-6">
                            <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-blue-700">
                                            <strong>Employee:</strong> {{ $employee->full_name }} ({{ $employee->employee_number }})<br>
                                            <strong>Monthly Salary:</strong> ₱{{ number_format($employee->basic_salary, 2) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Form Fields -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <!-- Requested Amount -->
                            <div>
                                <label for="requested_amount" class="block text-sm font-medium text-gray-700">Requested Amount *</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">₱</span>
                                    </div>
                                    <input type="number" 
                                           class="block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500 @error('requested_amount') border-red-300 @enderror" 
                                           id="requested_amount" name="requested_amount" 
                                           value="{{ old('requested_amount', intval($cashAdvance->requested_amount)) }}" 
                                           step="1" required
                                           onchange="calculateInstallment()">
                                </div>
                                @error('requested_amount')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-sm text-gray-500">Minimum: ₱100, Maximum: ₱50,000</p>
                            </div>

                            <!-- Interest Rate -->
                            <div>
                                <label for="interest_rate" class="block text-sm font-medium text-gray-700">Interest Rate (%)</label>
                                <input type="number" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('interest_rate') border-red-300 @enderror" 
                                       id="interest_rate" name="interest_rate" 
                                       value="{{ old('interest_rate', $cashAdvance->interest_rate ? floatval($cashAdvance->interest_rate) : '') }}" 
                                       step="0.1" min="0" max="100"
                                       onchange="calculateInstallment()"
                                       placeholder="0">
                                @error('interest_rate')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-sm text-gray-500">Leave 0 for no interest</p>
                            </div>

                            <!-- Deduction Frequency -->
                            <div>
                                <label for="deduction_frequency" class="block text-sm font-medium text-gray-700">Deduction Frequency *</label>
                                <select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('deduction_frequency') border-red-300 @enderror" 
                                        id="deduction_frequency" name="deduction_frequency" required onchange="toggleInstallmentFields()">
                                    <option value="">Select Frequency</option>
                                    <option value="per_payroll" {{ old('deduction_frequency', $cashAdvance->deduction_frequency) == 'per_payroll' ? 'selected' : '' }}>
                                        Per Pay Period (Regular)
                                    </option>
                                    <option value="monthly" {{ old('deduction_frequency', $cashAdvance->deduction_frequency) == 'monthly' ? 'selected' : '' }}>
                                        Monthly
                                    </option>
                                </select>
                                @error('deduction_frequency')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-sm text-gray-500">Choose deduction frequency</p>
                            </div>

                            <!-- Number of Installments -->
                            <div id="payroll_installments_field">
                                <label for="installments" class="block text-sm font-medium text-gray-700">Number of Pay Period Installments *</label>
                                <select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('installments') border-red-300 @enderror" 
                                        id="installments" name="installments" onchange="calculateInstallment()">
                                    <option value="">Select Installments</option>
                                    @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ old('installments', $cashAdvance->installments) == $i ? 'selected' : '' }}>
                                        {{ $i }} pay period{{ $i > 1 ? 's' : '' }}
                                    </option>
                                    @endfor
                                </select>
                                @error('installments')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Installment Options Row -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Number of Monthly Installments (for monthly frequency) -->
                            <div id="monthly_installments_field" style="display: none;">
                                <label for="monthly_installments" class="block text-sm font-medium text-gray-700">Number of Monthly Installments *</label>
                                <select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('monthly_installments') border-red-300 @enderror" 
                                        id="monthly_installments" name="monthly_installments" onchange="calculateInstallment()">
                                    <option value="">Select Months</option>
                                    @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ old('monthly_installments', $cashAdvance->monthly_installments) == $i ? 'selected' : '' }}>
                                        {{ $i }} month{{ $i > 1 ? 's' : '' }}
                                    </option>
                                    @endfor
                                </select>
                                @error('monthly_installments')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Monthly Deduction Timing (for monthly frequency) -->
                            <div id="monthly_timing_field" style="display: none;">
                                <label for="monthly_deduction_timing" class="block text-sm font-medium text-gray-700">Monthly Deduction Timing *</label>
                                <select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('monthly_deduction_timing') border-red-300 @enderror" 
                                        id="monthly_deduction_timing" name="monthly_deduction_timing">
                                    <option value="">Select Timing</option>
                                    <option value="first_payroll" {{ old('monthly_deduction_timing', $cashAdvance->monthly_deduction_timing) == 'first_payroll' ? 'selected' : '' }}>
                                        1st Cut-off Period
                                    </option>
                                    <option value="last_payroll" {{ old('monthly_deduction_timing', $cashAdvance->monthly_deduction_timing) == 'last_payroll' ? 'selected' : '' }}>
                                        2nd Cut-off Period
                                    </option>
                                </select>
                                @error('monthly_deduction_timing')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-sm text-gray-500" id="timing_help_text">For semi-monthly employees</p>
                            </div>


                        </div>

                        <!-- Calculation Results -->
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-900 mb-3">Calculation Summary</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div class="text-center">
                                    <div class="text-lg font-bold text-blue-600" id="interest-amount">₱{{ number_format($cashAdvance->interest_amount ?? 0, 2) }}</div>
                                    <div class="text-xs text-gray-600">Interest Amount</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-bold text-green-600" id="total-amount">₱{{ number_format($cashAdvance->total_amount ?? 0, 2) }}</div>
                                    <div class="text-xs text-gray-600">Total Amount</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-bold text-purple-600" id="monthly-deduction">₱{{ number_format($cashAdvance->installment_amount ?? 0, 2) }}</div>
                                    <div class="text-xs text-gray-600">Monthly Deduction</div>
                                </div>
                            </div>
                        </div>

                        <!-- Reason -->
                        <div>
                            <label for="reason" class="block text-sm font-medium text-gray-700">Reason for Cash Advance *</label>
                            <input type="text" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('reason') border-red-300 @enderror" 
                                   id="reason" name="reason" required 
                                   placeholder="Please provide the reason for requesting this cash advance..."
                                   value="{{ old('reason', $cashAdvance->reason) }}"
                                   maxlength="500">
                            @error('reason')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">Maximum 500 characters</p>
                        </div>



                        <!-- Submit Button -->
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('cash-advances.show', $cashAdvance) }}" 
                               class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Cancel
                            </a>
                            @if($cashAdvance->status === 'pending')
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Update Request
                            </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    let employeePaySchedule = null;
    let isDropdownOpen = false;
    let isEmployeeSelected = true; // Start as true since we're editing
    let allEmployees = [];

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize employee data
        initializeEmployees();
        
        // Initialize form state
        toggleInstallmentFields();
        calculateInstallment();
        
        // Mark employee as selected if editing and load their data
        const employeeId = document.getElementById('employee_id').value;
        if (employeeId) {
            isEmployeeSelected = true;
            loadEmployeePaySchedule(employeeId);
            // Load payroll periods for the current employee after a short delay
            setTimeout(() => {
                loadEmployeePayrollPeriods();
            }, 500);
        }

        // Add event listener for monthly deduction timing changes
        document.getElementById('monthly_deduction_timing').addEventListener('change', function() {
            const employeeId = document.getElementById('employee_id').value;
            if (employeeId) {
                setTimeout(() => {
                    loadEmployeePayrollPeriods();
                }, 100);
            }
        });
    });

    function initializeEmployees() {
        @if(!$employee)
        allEmployees = [
            @foreach($employees->sortBy('full_name') as $emp)
            {
                id: {{ $emp->id }},
                text: '{{ $emp->full_name }} ({{ $emp->employee_number }})',
                element: document.querySelector('[data-value="{{ $emp->id }}"]')
            },
            @endforeach
        ];
        @endif
    }

    // Employee dropdown functions
    function toggleEmployeeDropdown() {
        const dropdown = document.getElementById('employee_dropdown');
        if (isDropdownOpen) {
            hideEmployeeDropdown();
        } else {
            showEmployeeDropdown();
        }
    }

    function showEmployeeDropdown() {
        const dropdown = document.getElementById('employee_dropdown');
        dropdown.classList.remove('hidden');
        isDropdownOpen = true;
    }

    function hideEmployeeDropdown() {
        const dropdown = document.getElementById('employee_dropdown');
        dropdown.classList.add('hidden');
        isDropdownOpen = false;
    }

    function selectEmployee(employeeId, employeeText) {
        const searchInput = document.getElementById('employee_search');
        
        document.getElementById('employee_id').value = employeeId;
        searchInput.value = employeeText;
        isEmployeeSelected = true;
        
        hideEmployeeDropdown();
        
        // Load employee data and payroll periods
        loadEmployeePaySchedule(employeeId).then(() => {
            // After loading pay schedule, load the payroll periods for this employee
            setTimeout(() => {
                loadEmployeePayrollPeriods();
            }, 300); // Small delay to ensure pay schedule is loaded
        });
    }

    function handleKeyDown(event) {
        const searchInput = document.getElementById('employee_search');
        
        // Handle backspace for selected employee
        if (event.key === 'Backspace' && isEmployeeSelected) {
            event.preventDefault();
            clearEmployeeSelection();
            return;
        }
        
        // Handle other keys
        if (event.key === 'Escape') {
            hideEmployeeDropdown();
        }
    }

    function clearEmployeeSelection() {
        const searchInput = document.getElementById('employee_search');
        
        document.getElementById('employee_id').value = '';
        searchInput.value = '';
        isEmployeeSelected = false;
        
        // Clear payroll periods when no employee selected
        const periodSelect = document.getElementById('starting_payroll_period');
        periodSelect.innerHTML = '<option value="">Select an employee first</option>';
        periodSelect.disabled = true;
        
        // Reset employee pay schedule
        employeePaySchedule = null;
        
        // Show all employees again
        filterEmployees();
        showEmployeeDropdown();
    }

    function filterEmployees() {
        const searchValue = document.getElementById('employee_search').value.toLowerCase();
        const employeeList = document.getElementById('employee_list');
        const noResults = document.getElementById('no_results');
        
        // If user is typing after selecting an employee, clear the selection
        if (isEmployeeSelected && searchValue !== '') {
            isEmployeeSelected = false;
            document.getElementById('employee_id').value = '';
        }
        
        // Clear current list
        employeeList.innerHTML = '';
        
        // Filter employees based on search and sort alphabetically
        const filteredEmployees = allEmployees.filter(employee => 
            employee.text.toLowerCase().includes(searchValue)
        ).sort((a, b) => a.text.localeCompare(b.text));
        
        if (filteredEmployees.length === 0) {
            noResults.classList.remove('hidden');
        } else {
            noResults.classList.add('hidden');
            
            // Add filtered employees to list
            filteredEmployees.forEach(employee => {
                employeeList.appendChild(employee.element.cloneNode(true));
            });
        }
    }

    // Load employee pay schedule information
    async function loadEmployeePaySchedule(employeeId) {
        if (!employeeId) {
            employeePaySchedule = null;
            return;
        }

        try {
            const response = await fetch(`/cash-advances/employee-schedule`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    employee_id: employeeId
                })
            });

            if (response.ok) {
                const data = await response.json();
                employeePaySchedule = data.pay_schedule;
                updateTimingHelpText();
                // Payroll periods will be loaded from DOMContentLoaded or selectEmployee
            }
        } catch (error) {
            console.error('Error loading employee pay schedule:', error);
        }
    }

    // Load payroll periods for selected employee
    async function loadEmployeePayrollPeriods() {
        const employeeId = document.getElementById('employee_id').value;
        const periodSelect = document.getElementById('starting_payroll_period');
        
        // Get the current value from the database (for edit mode) - convert to string for comparison
        const currentValue = '{{ old("starting_payroll_period", $cashAdvance->starting_payroll_period) }}';
        const currentValueInt = parseInt(currentValue) || null;

        if (!employeeId) {
            periodSelect.innerHTML = '<option value="">Select an employee first</option>';
            periodSelect.disabled = true;
            return;
        }

        try {
            // Show loading state
            periodSelect.disabled = true;
            periodSelect.innerHTML = '<option value="">Loading payroll periods...</option>';
            
            console.log('Loading payroll periods for employee:', employeeId);
            console.log('Current value to match:', currentValue);

            // Prepare request body like in create view
            const frequency = document.getElementById('deduction_frequency').value || 'per_payroll';
            const requestBody = {
                employee_id: employeeId,
                deduction_frequency: frequency
            };

            // Add timing for weekly and semi-monthly employees with monthly frequency
            if ((employeePaySchedule === 'weekly' || employeePaySchedule === 'semi_monthly') && frequency === 'monthly') {
                const timing = document.getElementById('monthly_deduction_timing').value;
                if (timing) {
                    requestBody.monthly_deduction_timing = timing;
                }
            }

            const response = await fetch('/cash-advances/employee-periods', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(requestBody)
            });

            if (response.ok) {
                const data = await response.json();
                console.log('Payroll periods response:', data);
                
                // Build new HTML content
                let optionsHTML = '<option value="">Select starting payroll period</option>';
                let selectedValue = '';
                
                if (data.periods && data.periods.length > 0) {
                    console.log('Processing periods:', data.periods.length);
                    
                    // Find if current value exists in periods
                    let foundExactMatch = false;
                    let defaultPeriodValue = '';
                    
                    data.periods.forEach((period, index) => {
                        const value = period.value || '';
                        const text = period.label || period.text || '';
                        const description = period.description || '';
                        
                        console.log(`Period ${index}: value="${value}", text="${text}"`);
                        
                        optionsHTML += `<option value="${value}" title="${description}">${text}</option>`;
                        
                        // Check for exact match (compare both string and integer values)
                        if (currentValue && (value == currentValue || value == currentValueInt)) {
                            foundExactMatch = true;
                            selectedValue = value;
                            console.log('Found exact match:', value);
                        }
                        
                        // Check for default period
                        if (period.is_default && !foundExactMatch) {
                            defaultPeriodValue = value;
                            console.log('Found default period:', text);
                        }
                    });
                    
                    // Determine what to select
                    if (foundExactMatch) {
                        selectedValue = selectedValue; // Use the matched value, not currentValue
                        console.log('Will select exact match:', selectedValue);
                    } else if (defaultPeriodValue) {
                        selectedValue = defaultPeriodValue;
                        console.log('Will select default period:', selectedValue);
                    } else if (data.periods.length > 0) {
                        selectedValue = data.periods[0].value;
                        console.log('Will select first period:', selectedValue);
                    }
                    
                    // Set the HTML and selection in one operation
                    periodSelect.innerHTML = optionsHTML;
                    
                    // Set the selected value
                    if (selectedValue) {
                        periodSelect.value = selectedValue;
                        console.log('Selected value set to:', selectedValue);
                    }
                    
                    periodSelect.disabled = false;
                    console.log('Payroll periods loaded and selected successfully');
                } else {
                    console.log('No periods found in response');
                    periodSelect.innerHTML = '<option value="">No payroll periods available</option>';
                    periodSelect.disabled = false;
                }
            } else {
                const errorText = await response.text();
                console.error('Error response:', errorText);
                
                periodSelect.disabled = false;
            }
        } catch (error) {
            console.error('Error loading payroll periods:', error);
            periodSelect.innerHTML = '<option value="">Error loading periods</option>';
            periodSelect.disabled = false;
        }
    }

    // Toggle installment fields based on deduction frequency
    function toggleInstallmentFields() {
        const frequency = document.getElementById('deduction_frequency').value;
        const payrollInstallmentsField = document.getElementById('payroll_installments_field');
        const monthlyInstallmentsField = document.getElementById('monthly_installments_field');
        const monthlyTimingField = document.getElementById('monthly_timing_field');
        const installmentsSelect = document.getElementById('installments');
        const monthlyInstallmentsSelect = document.getElementById('monthly_installments');
        const monthlyTimingSelect = document.getElementById('monthly_deduction_timing');

        if (frequency === 'monthly') {
            payrollInstallmentsField.style.display = 'none';
            monthlyInstallmentsField.style.display = 'block';
            
            // Clear and disable payroll installments
            installmentsSelect.value = '';
            installmentsSelect.removeAttribute('required');
            installmentsSelect.disabled = true;
            
            // Enable monthly fields
            monthlyInstallmentsSelect.setAttribute('required', 'required');
            monthlyInstallmentsSelect.disabled = false;
            
        } else {
            payrollInstallmentsField.style.display = 'block';
            monthlyInstallmentsField.style.display = 'none';
            
            // Clear and disable monthly fields
            monthlyInstallmentsSelect.value = '';
            monthlyInstallmentsSelect.removeAttribute('required');
            monthlyInstallmentsSelect.disabled = true;
            
            // Enable payroll installments
            installmentsSelect.setAttribute('required', 'required');
            installmentsSelect.disabled = false;
        }

        // Show Monthly Deduction Timing field for weekly and semi-monthly employees with monthly frequency
        if ((employeePaySchedule === 'weekly' || employeePaySchedule === 'semi_monthly') && frequency === 'monthly') {
            monthlyTimingField.style.display = 'block';
            monthlyTimingSelect.setAttribute('required', 'required');
            monthlyTimingSelect.disabled = false;
            updateTimingHelpText();
        } else {
            monthlyTimingField.style.display = 'none';
            monthlyTimingSelect.removeAttribute('required');
            monthlyTimingSelect.value = '';
            monthlyTimingSelect.disabled = true;
        }
        
        // Reload payroll periods when frequency changes (if employee is selected)
        const employeeId = document.getElementById('employee_id').value;
        if (employeeId) {
            setTimeout(() => {
                loadEmployeePayrollPeriods();
            }, 100); // Small delay to ensure UI updates are complete
        }
        
        calculateInstallment();
    }

    function updateTimingHelpText() {
        const helpText = document.getElementById('timing_help_text');
        if (employeePaySchedule) {
            switch (employeePaySchedule) {
                case 'weekly':
                    helpText.textContent = 'For weekly employees: First = 1st week of month, Last = Last week of month';
                    break;
                case 'semi_monthly':
                    helpText.textContent = 'Required for semi-monthly employees: First = 1st cutoff (1-15), Last = 2nd cutoff (16-31)';
                    break;
                case 'monthly':
                    helpText.textContent = 'For monthly employees: Only one payroll per month';
                    break;
                default:
                    helpText.textContent = 'Choose when to deduct during the month';
            }
        }
    }

    function calculateInstallment() {
        const amount = parseFloat(document.getElementById('requested_amount').value) || 0;
        const frequency = document.getElementById('deduction_frequency').value;
        const interestRate = parseFloat(document.getElementById('interest_rate').value) || 0;
        
        let installments = 1;
        let deductionLabel = 'Deduction';

        if (frequency === 'monthly') {
            installments = parseInt(document.getElementById('monthly_installments').value) || 1;
            deductionLabel = 'Monthly Deduction';
        } else {
            installments = parseInt(document.getElementById('installments').value) || 1;
            deductionLabel = 'Per-Payroll Deduction';
        }
        
        const interestAmount = (amount * interestRate / 100);
        const totalAmount = amount + interestAmount;
        const installmentAmount = totalAmount / installments;
        
        document.getElementById('interest-amount').textContent = '₱' + interestAmount.toFixed(2);
        document.getElementById('total-amount').textContent = '₱' + totalAmount.toFixed(2);
        document.getElementById('monthly-deduction').textContent = '₱' + installmentAmount.toFixed(2);
        document.querySelector('#monthly-deduction').nextElementSibling.textContent = deductionLabel;
    }
    </script>
</x-app-layout>