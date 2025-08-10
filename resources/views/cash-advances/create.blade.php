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
                                        id="employee_id" name="employee_id" required onchange="checkEligibility()">
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
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                                    <option value="">Select installments</option>
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

                            <!-- Monthly Deduction Preview -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Monthly Deduction</label>
                                <div class="mt-1 text-sm font-semibold text-indigo-600" id="monthlyDeduction">
                                    ₱0.00
                                </div>
                            </div>
                            <!-- First Deduction Date -->
                            <div>
                                <label for="first_deduction_date" class="block text-sm font-medium text-gray-700">First Deduction Date</label>
                                <input type="date" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('first_deduction_date') border-red-300 @enderror" 
                                       id="first_deduction_date" name="first_deduction_date" 
                                       value="{{ old('first_deduction_date', now()->addMonth()->format('Y-m-d')) }}" 
                                       min="{{ now()->format('Y-m-d') }}">
                                @error('first_deduction_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-sm text-gray-500">Deductions will start from this date</p>
                            </div>
                        </div>

                        <!-- Reason -->
                        <div>
                            <label for="reason" class="block text-sm font-medium text-gray-700">Reason for Cash Advance *</label>
                            <textarea class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('reason') border-red-300 @enderror" 
                                      id="reason" name="reason" rows="4" 
                                      placeholder="Please provide the reason for requesting this cash advance..." 
                                      required>{{ old('reason') }}</textarea>
                            @error('reason')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">Maximum 500 characters</p>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('cash-advances.index') }}" 
                               class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150" 
                                    id="submitBtn">
                                Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            <!-- Guidelines Section -->
            <div class="mt-8 bg-gray-50 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Cash Advance Guidelines</h3>
                    <ul class="space-y-3 text-sm text-gray-600">
                        <li class="flex items-center">
                            <svg class="flex-shrink-0 h-4 w-4 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Minimum amount: ₱100
                        </li>
                        <li class="flex items-center">
                            <svg class="flex-shrink-0 h-4 w-4 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Maximum amount: ₱50,000
                        </li>
                        <li class="flex items-center">
                            <svg class="flex-shrink-0 h-4 w-4 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            Repayment period: 1-12 months
                        </li>
                        <li class="flex items-center">
                            <svg class="flex-shrink-0 h-4 w-4 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                            No interest charged
                        </li>
                        <li class="flex items-center">
                            <svg class="flex-shrink-0 h-4 w-4 text-blue-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                            Only one active cash advance per employee
                        </li>
                        <li class="flex items-center">
                            <svg class="flex-shrink-0 h-4 w-4 text-blue-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                            Automatic payroll deduction
                        </li>
                        <li class="flex items-center">
                            <svg class="flex-shrink-0 h-4 w-4 text-yellow-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            Subject to management approval
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Eligibility Card (Hidden by default) -->
            <div id="eligibilityCard" class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg" style="display: none;">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Eligibility Status</h3>
                    <div id="eligibilityContent">
                        <!-- Content will be populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function calculateInstallment() {
        const amount = parseFloat(document.getElementById('requested_amount').value) || 0;
        const installments = parseInt(document.getElementById('installments').value) || 0;
        
        if (amount > 0 && installments > 0) {
            const monthlyDeduction = amount / installments;
            document.getElementById('monthlyDeduction').textContent = `₱${monthlyDeduction.toFixed(2)}`;
        } else {
            document.getElementById('monthlyDeduction').textContent = '₱0.00';
        }
    }

    function checkEligibility() {
        const employeeId = document.getElementById('employee_id').value || {{ $employee->id ?? 'null' }};
        const eligibilityCard = document.getElementById('eligibilityCard');
        const eligibilityContent = document.getElementById('eligibilityContent');
        
        if (!employeeId) {
            eligibilityCard.style.display = 'none';
            return;
        }
        
        // Show loading
        eligibilityContent.innerHTML = '<div class="text-center"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 mx-auto"></div><p class="mt-2 text-sm text-gray-600">Checking eligibility...</p></div>';
        eligibilityCard.style.display = 'block';
        
        fetch(`{{ route('cash-advances.check-eligibility') }}?employee_id=${employeeId}`)
            .then(response => response.json())
            .then(data => {
                if (data.eligible) {
                    eligibilityContent.innerHTML = `
                        <div class="bg-green-50 border border-green-200 rounded-md p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-green-800">Employee is eligible</p>
                                    <div class="mt-2 text-sm text-green-700">
                                        <strong>Monthly Salary:</strong> ₱${data.monthly_salary.toFixed(2)}<br>
                                        <strong>Max Eligible:</strong> ₱${data.max_amount.toFixed(2)}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Update max amount in input
                    document.getElementById('requested_amount').max = data.max_amount;
                } else {
                    eligibilityContent.innerHTML = `
                        <div class="bg-red-50 border border-red-200 rounded-md p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-red-800">${data.reason}</p>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Disable form submission
                    document.getElementById('submitBtn').disabled = true;
                }
            })
            .catch(error => {
                eligibilityContent.innerHTML = `
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-yellow-800">Unable to check eligibility</p>
                            </div>
                        </div>
                    </div>
                `;
                console.error('Error:', error);
            });
    }

    // Initialize for employee users
    @if($employee)
    document.addEventListener('DOMContentLoaded', function() {
        checkEligibility();
    });
    @endif

    // Form submission validation
    document.getElementById('cashAdvanceForm').addEventListener('submit', function(e) {
        const amount = parseFloat(document.getElementById('requested_amount').value);
        const maxAmount = parseFloat(document.getElementById('requested_amount').max);
        
        if (amount > maxAmount) {
            e.preventDefault();
            alert(`Requested amount exceeds maximum eligible amount of ₱${maxAmount.toFixed(2)}`);
            return false;
        }
    });
    </script>
</x-app-layout>
