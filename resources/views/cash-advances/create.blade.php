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
                                           step="0.01" min="100" max="50000" required
                                           onchange="calculateInstallment()">
                                </div>
                                @error('requested_amount')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-sm text-gray-500">Minimum: ₱100, Maximum: ₱50,000</p>
                            </div>

                            <!-- Number of Installments -->
                            <div>
                                <label for="installments" class="block text-sm font-medium text-gray-700">Number of Installments *</label>
                                <select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('installments') border-red-300 @enderror" 
                                        id="installments" name="installments" required onchange="calculateInstallment()">
                                    <option value="">Select Installments</option>
                                    @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ old('installments') == $i ? 'selected' : '' }}>
                                        {{ $i }} month{{ $i > 1 ? 's' : '' }}
                                    </option>
                                    @endfor
                                </select>
                                @error('installments')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
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
                            <!-- Deduction Period -->
                            <div>
                                <label for="deduction_period" class="block text-sm font-medium text-gray-700">Deduction Period *</label>
                                <select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('deduction_period') border-red-300 @enderror" 
                                        id="deduction_period" name="deduction_period" required onchange="updateDeductionDate()" disabled>
                                    <option value="">{{ !$employee ? 'Select an employee first' : 'Loading payroll periods...' }}</option>
                                </select>
                                @error('deduction_period')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-sm text-gray-500">Choose when deductions should start</p>
                                
                                <!-- Hidden field for actual date -->
                                <input type="hidden" 
                                       id="first_deduction_date" name="first_deduction_date" 
                                       value="{{ old('first_deduction_date') }}">
                                       
                                <!-- Hidden field for payroll ID -->
                                <input type="hidden" 
                                       id="payroll_id" name="payroll_id" 
                                       value="{{ old('payroll_id') }}">
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
    function calculateInstallment() {
        const amount = parseFloat(document.getElementById('requested_amount').value) || 0;
        const installments = parseInt(document.getElementById('installments').value) || 1;
        const interestRate = parseFloat(document.getElementById('interest_rate').value) || 0;
        
        const interestAmount = (amount * interestRate / 100);
        const totalAmount = amount; // Just the requested amount, no interest added
        const monthlyDeduction = amount / installments; // Monthly deduction based on requested amount only
        
        document.getElementById('interest-amount').textContent = '₱' + interestAmount.toFixed(2);
        document.getElementById('total-amount').textContent = '₱' + totalAmount.toFixed(2);
        document.getElementById('monthly-deduction').textContent = '₱' + monthlyDeduction.toFixed(2);
    }

    // Load payroll periods for selected employee
    async function loadEmployeePayrollPeriods() {
        const employeeId = document.getElementById('employee_id').value;
        const deductionPeriodSelect = document.getElementById('deduction_period');
        const deductionDateInput = document.getElementById('first_deduction_date');
        const payrollIdInput = document.getElementById('payroll_id');
        
        // Reset fields
        deductionPeriodSelect.innerHTML = '<option value="">Loading...</option>';
        deductionPeriodSelect.disabled = true;
        deductionDateInput.value = '';
        payrollIdInput.value = '';
        
        if (!employeeId) {
            deductionPeriodSelect.innerHTML = '<option value="">Select an employee first</option>';
            return;
        }

        try {
            const response = await fetch('{{ route('cash-advances.employee-periods') }}', {
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
            
            if (data.error) {
                deductionPeriodSelect.innerHTML = '<option value="">Error loading periods</option>';
                return;
            }
            
            deductionPeriodSelect.innerHTML = '<option value="">Select deduction period</option>';
            
            if (data.periods && data.periods.length > 0) {
                data.periods.forEach(period => {
                    const option = document.createElement('option');
                    option.value = period.value;
                    option.textContent = period.label;
                    option.dataset.startDate = period.start_date;
                    option.dataset.payrollId = period.payroll_id;
                    deductionPeriodSelect.appendChild(option);
                });
                deductionPeriodSelect.disabled = false;
            } else {
                deductionPeriodSelect.innerHTML = '<option value="">No active payroll periods available</option>';
            }
        } catch (error) {
            console.error('Error loading payroll periods:', error);
            deductionPeriodSelect.innerHTML = '<option value="">Error loading periods</option>';
        }
    }

    @if(!$employee)
    function checkEligibility() {
        const employeeId = document.getElementById('employee_id').value;
        const amountInput = document.getElementById('requested_amount');
        
        if (employeeId) {
            // Simulate eligibility check - in real app, this would be an AJAX call
            amountInput.max = 50000;
            calculateInstallment();
        } else {
            amountInput.max = 0;
        }
    }
    @endif

    // Update deduction date and payroll ID based on selected period
    function updateDeductionDate() {
        const deductionPeriodSelect = document.getElementById('deduction_period');
        const selectedOption = deductionPeriodSelect.options[deductionPeriodSelect.selectedIndex];
        const deductionDateInput = document.getElementById('first_deduction_date');
        const payrollIdInput = document.getElementById('payroll_id');
        
        if (selectedOption && selectedOption.dataset.startDate) {
            deductionDateInput.value = selectedOption.dataset.startDate;
            payrollIdInput.value = selectedOption.dataset.payrollId;
        } else {
            deductionDateInput.value = '';
            payrollIdInput.value = '';
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        @if($employee)
        // For employee users, load their payroll periods immediately
        document.getElementById('employee_id').value = '{{ $employee->id }}';
        loadEmployeePayrollPeriods();
        calculateInstallment();
        @else
        checkEligibility();
        @endif
    });

    // Form submission validation
    document.getElementById('cashAdvanceForm').addEventListener('submit', function(e) {
        const amount = parseFloat(document.getElementById('requested_amount').value);
        const reason = document.getElementById('reason').value.trim();
        const installments = document.getElementById('installments').value;
        const deductionPeriod = document.getElementById('deduction_period').value;
        
        if (!amount || amount < 100) {
            e.preventDefault();
            alert('Please enter a valid requested amount (minimum ₱100).');
            return false;
        }
        
        if (!installments) {
            e.preventDefault();
            alert('Please select number of installments.');
            return false;
        }
        
        if (!deductionPeriod) {
            e.preventDefault();
            alert('Please select a deduction period.');
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