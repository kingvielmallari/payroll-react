<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('New Cash Advance Request') }}
            </h2>
            <a href="{{ route('cash-advances.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Back to List
            </a>
        </div>
    </x-slot>

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

                    <form action="{{ route('cash-advances.store') }}" method="POST" id="cashAdvanceForm" class="space-y-6">
                        @csrf
                        
                        @if(!$employee)
                        <!-- Employee Selection (HR/Admin only) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="employee_id" class="block text-sm font-medium text-gray-700">Employee *</label>
                                <select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('employee_id') border-red-300 @enderror" 
                                        id="employee_id" name="employee_id" required onchange="loadEmployeePayrollPeriods()">
                                    <option value="">Select Employee</option>
                                    @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->full_name }} ({{ $emp->employee_number }})
                                    </option>
                                    @endforeach
                                </select>
                                @error('employee_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
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
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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
                                           value="{{ old('requested_amount') }}" 
                                           step="0.01" required
                                           onchange="calculateInstallment()">
                                </div>
                                @error('requested_amount')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-sm text-gray-500">Minimum: ₱100, Maximum: ₱50,000</p>
                            </div>

                            <!-- Deduction Frequency -->
                            <div>
                                <label for="deduction_frequency" class="block text-sm font-medium text-gray-700">Deduction Frequency *</label>
                                <select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('deduction_frequency') border-red-300 @enderror" 
                                        id="deduction_frequency" name="deduction_frequency" required onchange="toggleInstallmentFields()">
                                    <option value="">Select Frequency</option>
                                    <option value="per_payroll" {{ old('deduction_frequency') == 'per_payroll' ? 'selected' : '' }}>
                                        Per Pay Period (Regular)
                                    </option>
                                    <option value="monthly" {{ old('deduction_frequency') == 'monthly' ? 'selected' : '' }}>
                                        Monthly
                                    </option>
                                </select>
                                @error('deduction_frequency')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-sm text-gray-500">Choose deduction frequency</p>
                            </div>

                            <!-- Interest Rate -->
                            <div>
                                <label for="interest_rate" class="block text-sm font-medium text-gray-700">Interest Rate (%)</label>
                                <input type="number" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('interest_rate') border-red-300 @enderror" 
                                       id="interest_rate" name="interest_rate" 
                                       value="{{ old('interest_rate') }}" 
                                       step="0.1" min="0" max="100"
                                       onchange="calculateInstallment()"
                                       placeholder="0">
                                @error('interest_rate')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-sm text-gray-500">Leave 0 for no interest</p>
                            </div>
                        </div>

                        <!-- Installment Options Row -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Number of Payroll Installments (for per_payroll frequency) -->
                            <div id="payroll_installments_field">
                                <label for="installments" class="block text-sm font-medium text-gray-700">Number of Pay Period Installments *</label>
                                <select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('installments') border-red-300 @enderror" 
                                        id="installments" name="installments" onchange="calculateInstallment()">
                                    <option value="">Select Installments</option>
                                    @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ old('installments') == $i ? 'selected' : '' }}>
                                        {{ $i }} pay period{{ $i > 1 ? 's' : '' }}
                                    </option>
                                    @endfor
                                </select>
                                @error('installments')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Number of Monthly Installments (for monthly frequency) -->
                            <div id="monthly_installments_field" style="display: none;">
                                <label for="monthly_installments" class="block text-sm font-medium text-gray-700">Number of Monthly Installments *</label>
                                <select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('monthly_installments') border-red-300 @enderror" 
                                        id="monthly_installments" name="monthly_installments" onchange="calculateInstallment()">
                                    <option value="">Select Months</option>
                                    @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ old('monthly_installments') == $i ? 'selected' : '' }}>
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
                                        id="monthly_deduction_timing" name="monthly_deduction_timing" onchange="onMonthlyTimingChange()">
                                    <option value="">Select Timing</option>
                                    <option value="first_payroll" {{ old('monthly_deduction_timing') == 'first_payroll' ? 'selected' : '' }}>
                                        First Payroll of Month
                                    </option>
                                    <option value="last_payroll" {{ old('monthly_deduction_timing') == 'last_payroll' ? 'selected' : '' }}>
                                        Last Payroll of Month
                                    </option>
                                </select>
                                @error('monthly_deduction_timing')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-sm text-gray-500" id="timing_help_text">For weekly/semi-monthly employees</p>
                            </div>
                        </div>

                        <!-- Calculation Results -->
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-900 mb-3">Calculation Summary</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div class="text-center">
                                    <div class="text-lg font-bold text-blue-600" id="interest-amount">₱0.00</div>
                                    <div class="text-xs text-gray-600">Interest Amount</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-bold text-green-600" id="total-amount">₱0.00</div>
                                    <div class="text-xs text-gray-600">Total Amount</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-bold text-purple-600" id="monthly-deduction">₱0.00</div>
                                    <div class="text-xs text-gray-600">Monthly Deduction</div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-6">
                            <!-- Starting Payroll Period -->
                            <div>
                                <label for="starting_payroll_period" class="block text-sm font-medium text-gray-700">Deduction Start Period *</label>
                                <select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('starting_payroll_period') border-red-300 @enderror" 
                                        id="starting_payroll_period" name="starting_payroll_period" required disabled>
                                    <option value="">{{ !$employee ? 'Select an employee first' : 'Loading payroll periods...' }}</option>
                                </select>
                                @error('starting_payroll_period')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-sm text-gray-500">Choose the payroll period when deductions should start</p>
                            </div>
                        </div>

                        <!-- Reason -->
                        <div>
                            <label for="reason" class="block text-sm font-medium text-gray-700">Reason for Cash Advance *</label>
                            <input type="text" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('reason') border-red-300 @enderror" 
                                   id="reason" name="reason" required 
                                   placeholder="Please provide the reason for requesting this cash advance..."
                                   value="{{ old('reason') }}"
                                   maxlength="500">
                            @error('reason')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">Maximum 500 characters</p>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('cash-advances.index') }}" 
                               class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    let employeePaySchedule = null;

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
            installmentsSelect.disabled = true; // Disable so it's not sent
            
            // Enable monthly fields
            monthlyInstallmentsSelect.setAttribute('required', 'required');
            monthlyInstallmentsSelect.disabled = false;
            
        } else {
            payrollInstallmentsField.style.display = 'block';
            monthlyInstallmentsField.style.display = 'none';
            
            // Clear and disable monthly fields
            monthlyInstallmentsSelect.value = '';
            monthlyInstallmentsSelect.removeAttribute('required');
            monthlyInstallmentsSelect.disabled = true; // Disable so it's not sent
            
            // Enable payroll installments
            installmentsSelect.setAttribute('required', 'required');
            installmentsSelect.disabled = false;
        }

        // Show Monthly Deduction Timing field only for semi-monthly employees with monthly frequency
        if (employeePaySchedule === 'semi_monthly' && frequency === 'monthly') {
            monthlyTimingField.style.display = 'block';
            monthlyTimingSelect.setAttribute('required', 'required');
            updateTimingHelpText();
            // Reset periods when frequency changes
            resetStartingPayrollPeriods();
        } else {
            monthlyTimingField.style.display = 'none';
            monthlyTimingSelect.removeAttribute('required');
            monthlyTimingSelect.value = '';
            // Load periods immediately for non-monthly frequency or non-semi-monthly employees
            if (employeePaySchedule) {
                loadEmployeePayrollPeriods();
            }
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

    // Load employee pay schedule information
    async function loadEmployeePaySchedule(employeeId) {
        if (!employeeId) {
            employeePaySchedule = null;
            return;
        }

        try {
            const response = await fetch('{{ route('cash-advances.employee-schedule') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    employee_id: employeeId
                })
            });
            const data = await response.json();
            
            if (!data.error) {
                employeePaySchedule = data.pay_schedule;
                updateTimingHelpText();
            }
        } catch (error) {
            console.error('Error loading employee pay schedule:', error);
        }
    }

    // Load payroll periods for selected employee
    async function loadEmployeePayrollPeriods() {
        const employeeId = document.getElementById('employee_id').value;
        const frequency = document.getElementById('deduction_frequency').value;
        const startingPayrollSelect = document.getElementById('starting_payroll_period');
        
        // Reset fields
        startingPayrollSelect.innerHTML = '<option value="">Loading...</option>';
        startingPayrollSelect.disabled = true;
        
        if (!employeeId) {
            startingPayrollSelect.innerHTML = '<option value="">Select an employee first</option>';
            return;
        }

        // Load employee pay schedule first
        await loadEmployeePaySchedule(employeeId);

        // For semi-monthly employees with monthly frequency, require timing selection first
        if (employeePaySchedule === 'semi_monthly' && frequency === 'monthly') {
            const timing = document.getElementById('monthly_deduction_timing').value;
            if (!timing) {
                startingPayrollSelect.innerHTML = '<option value="">Select monthly timing first</option>';
                startingPayrollSelect.disabled = true;
                return;
            }
        }

        // Prepare request body
        const requestBody = {
            employee_id: employeeId,
            deduction_frequency: frequency
        };

        // Add timing for semi-monthly employees with monthly frequency
        if (employeePaySchedule === 'semi_monthly' && frequency === 'monthly') {
            requestBody.monthly_deduction_timing = document.getElementById('monthly_deduction_timing').value;
        }

        try {
            const response = await fetch('{{ route('cash-advances.employee-periods') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(requestBody)
            });
            const data = await response.json();
            
            if (data.error) {
                startingPayrollSelect.innerHTML = '<option value="">Error loading periods</option>';
                return;
            }
            
            startingPayrollSelect.innerHTML = '<option value="">Select starting payroll period</option>';
            
            if (data.periods && data.periods.length > 0) {
                data.periods.forEach((period) => {
                    const option = document.createElement('option');
                    option.value = period.value;
                    option.textContent = period.label;
                    option.title = period.description;
                    
                    // Auto-select the default option (current period)
                    if (period.is_default) {
                        option.selected = true;
                    }
                    
                    startingPayrollSelect.appendChild(option);
                });
                
                startingPayrollSelect.disabled = false;
            } else {
                startingPayrollSelect.innerHTML = '<option value="">No payroll periods available</option>';
            }
        } catch (error) {
            console.error('Error loading payroll periods:', error);
            startingPayrollSelect.innerHTML = '<option value="">Error loading periods</option>';
        }
    }

    @if(!$employee)
    function checkEligibility() {
        const employeeId = document.getElementById('employee_id').value;
        const amountInput = document.getElementById('requested_amount');
        
        if (employeeId) {
            // Employee selected - enable calculation
            calculateInstallment();
        }
        // Remove max attribute manipulation - validation handled server-side
    }
    @endif

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        @if($employee)
        // For employee users, load their payroll periods immediately
        document.getElementById('employee_id').value = '{{ $employee->id }}';
        loadEmployeePayrollPeriods();
        @else
        checkEligibility();
        @endif
        
        // Set default values for old input
        const frequency = '{{ old('deduction_frequency') }}';
        if (frequency) {
            document.getElementById('deduction_frequency').value = frequency;
            toggleInstallmentFields();
        }

        calculateInstallment();
    });

    // Reset starting payroll periods dropdown
    function resetStartingPayrollPeriods() {
        const startingPayrollSelect = document.getElementById('starting_payroll_period');
        startingPayrollSelect.innerHTML = '<option value="">Select an employee and frequency first</option>';
        startingPayrollSelect.disabled = true;
    }

    // Handle monthly timing change for semi-monthly employees
    function onMonthlyTimingChange() {
        const employeeId = document.getElementById('employee_id').value;
        const timing = document.getElementById('monthly_deduction_timing').value;
        
        // For semi-monthly employees, load periods when timing is selected
        if (employeeId && employeePaySchedule === 'semi_monthly' && timing) {
            loadEmployeePayrollPeriods();
        }
    }

    // Form submission validation
    document.getElementById('cashAdvanceForm').addEventListener('submit', function(e) {
        const employeeId = document.getElementById('employee_id').value;
        const amount = parseFloat(document.getElementById('requested_amount').value);
        const reason = document.getElementById('reason').value.trim();
        const frequency = document.getElementById('deduction_frequency').value;
        const startingPayrollPeriod = document.getElementById('starting_payroll_period').value;
        
        // Check if employee is selected (for HR/Admin users)
        @if(!$employee)
        if (!employeeId) {
            e.preventDefault();
            alert('Please select an employee.');
            return false;
        }
        @endif
        
        if (!amount || amount < 100) {
            e.preventDefault();
            alert('Please enter a valid requested amount (minimum ₱100).');
            return false;
        }
        
        if (!frequency) {
            e.preventDefault();
            alert('Please select deduction frequency.');
            return false;
        }
        
        // Check timing requirement for semi-monthly employees with monthly frequency
        if (employeePaySchedule === 'semi_monthly' && frequency === 'monthly') {
            const monthlyTiming = document.getElementById('monthly_deduction_timing').value;
            if (!monthlyTiming) {
                e.preventDefault();
                alert('Please select monthly deduction timing for semi-monthly employees.');
                return false;
            }
        }
        
        if (frequency === 'monthly') {
            const monthlyInstallments = document.getElementById('monthly_installments').value;
            
            if (!monthlyInstallments) {
                e.preventDefault();
                alert('Please select number of monthly installments.');
                return false;
            }
        } else {
            const installments = document.getElementById('installments').value;
            if (!installments) {
                e.preventDefault();
                alert('Please select number of installments.');
                return false;
            }
        }
        
        if (!startingPayrollPeriod) {
            e.preventDefault();
            alert('Please select a starting payroll period.');
            return false;
        }
        
        if (!reason) {
            e.preventDefault();
            alert('Please provide a reason for the cash advance.');
            return false;
        }
        
        // If all validations pass, allow form submission
        return true;
    });
    </script>
</x-app-layout>