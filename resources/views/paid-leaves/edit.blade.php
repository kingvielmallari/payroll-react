<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Paid Leave Request') }}
            </h2>
            <a href="{{ route('paid-leaves.show', $paidLeave) }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Back to Details
            </a>
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
            max-height: 240px;
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

                    <form action="{{ route('paid-leaves.update', $paidLeave) }}" method="POST" id="paidLeaveForm" class="space-y-6" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        @if(!$employee)
                        <!-- Employee Selection (HR/Admin only) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="employee_id" class="block text-sm font-medium text-gray-700">Employee *</label>
                                
                                <!-- Hidden input for form submission -->
                                <input type="hidden" id="employee_id" name="employee_id" value="{{ old('employee_id', $paidLeave->employee_id) }}" required>
                                
                                <!-- Custom searchable dropdown -->
                                <div class="relative mt-1">
                                    <input type="text" 
                                           id="employee_search" 
                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('employee_id') border-red-300 @enderror" 
                                           placeholder="Type to search employees..." 
                                           autocomplete="off"
                                           onclick="toggleEmployeeDropdown()"
                                           onkeyup="filterEmployees()"
                                           onblur="setTimeout(hideEmployeeDropdown, 200)"
                                           value="{{ old('employee_search', $paidLeave->employee->full_name . ' (' . $paidLeave->employee->employee_number . ')') }}">
                                    
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
                        </div>
                        @else
                        <!-- Employee is pre-selected for regular users -->
                        <input type="hidden" name="employee_id" value="{{ $paidLeave->employee_id }}">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-medium text-gray-900">Leave Request for:</h3>
                            <p class="text-sm text-gray-600">{{ $paidLeave->employee->full_name }} ({{ $paidLeave->employee->employee_number }})</p>
                        </div>
                        @endif

                        <!-- Leave Type Selection (for both HR/Admin and Employee users) -->
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label for="leave_setting_id" class="block text-sm font-medium text-gray-700">Leave Type *</label>
                                <select class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('leave_setting_id') border-red-300 @enderror" 
                                        id="leave_setting_id" name="leave_setting_id" required>
                                    <option value="">@if($employee)Loading...@else Select Leave Type @endif</option>
                                    <!-- Options will be populated by JavaScript based on selected employee -->
                                </select>
                                @error('leave_setting_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-sm text-gray-500" id="leave_balance_info">@if($employee)Loading leave types...@else Choose the type of leave request @endif</p>
                            </div>
                        </div>

                        <!-- Leave Period Row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Start Date -->
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date *</label>
                                <input type="date" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('start_date') border-red-300 @enderror" 
                                       id="start_date" name="start_date" value="{{ old('start_date', $paidLeave->start_date->format('Y-m-d')) }}" required
                                       min="{{ date('Y-m-d') }}">
                                @error('start_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- End Date -->
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700">End Date *</label>
                                <input type="date" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('end_date') border-red-300 @enderror" 
                                       id="end_date" name="end_date" value="{{ old('end_date', $paidLeave->end_date->format('Y-m-d')) }}" required
                                       min="{{ date('Y-m-d') }}">
                                @error('end_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        
                        <!-- Supporting Document -->
                        <div>
                            <label for="supporting_document" class="block text-sm font-medium text-gray-700">Supporting Document</label>
                            
                            @if($paidLeave->supporting_document)
                            <div class="mb-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <span class="text-sm text-blue-800">Current document uploaded</span>
                                    </div>
                                    <a href="{{ asset('storage/' . $paidLeave->supporting_document) }}" target="_blank"
                                       class="inline-flex items-center px-3 py-1 border border-blue-300 shadow-sm text-xs font-medium rounded-md text-blue-700 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        View
                                    </a>
                                </div>
                            </div>
                            @endif
                            
                            <input type="file" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('supporting_document') border-red-300 @enderror" 
                                   id="supporting_document" name="supporting_document" 
                                   accept=".jpg,.jpeg,.png,.pdf">
                            @error('supporting_document')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">Upload new document (JPG, PNG, PDF - max 2MB) @if($paidLeave->supporting_document) or leave empty to keep current document @endif</p>
                        </div>
                        
                        <!-- Calculation Results -->
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-900 mb-3">Leave Summary</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                                <div class="text-center">
                                    <div class="text-lg font-bold text-blue-600" id="total-days">{{ $paidLeave->total_days }}</div>
                                    <div class="text-xs text-gray-600">Total Days</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-bold text-green-600" id="daily-rate">₱{{ number_format($paidLeave->daily_rate, 2) }}</div>
                                    <div class="text-xs text-gray-600">Daily Rate</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-lg font-bold text-purple-600" id="total-amount">₱{{ number_format($paidLeave->total_amount, 2) }}</div>
                                    <div class="text-xs text-gray-600">Total Amount</div>
                                </div>
                            </div>
                            
                            <!-- Calculation Details -->
                            <div id="calculation-details" class="text-xs text-gray-600 border-t pt-2 hidden">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                    <div>
                                        <span class="font-medium">Schedule:</span> <span id="schedule-info">-</span>
                                    </div>
                                    <div>
                                        <span class="font-medium">Method:</span> <span id="calculation-method">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reason -->
                        <div>
                            <label for="reason" class="block text-sm font-medium text-gray-700">Reason for Leave *</label>
                            <textarea class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 @error('reason') border-red-300 @enderror" 
                                      id="reason" name="reason" rows="4" required placeholder="Please provide the reason for requesting this paid leave...">{{ old('reason', $paidLeave->reason) }}</textarea>
                            @error('reason')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">Maximum 500 characters</p>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-end space-x-3">
                            <a href="{{ route('paid-leaves.show', $paidLeave) }}" 
                               class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Update Leave Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Employee dropdown functionality
        let allEmployees = [];
        let isDropdownOpen = false;
        let isEmployeeSelected = true; // Set to true for edit mode
        
        // Store original leave setting ID for pre-selection
        const originalLeaveSettingId = {{ $paidLeave->leave_setting_id }};

        // Initialize employee data on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Store all employee options for filtering
            const employeeOptions = document.querySelectorAll('.employee-option');
            employeeOptions.forEach(option => {
                allEmployees.push({
                    id: option.dataset.value,
                    text: option.dataset.text,
                    element: option.cloneNode(true)
                });
            });
            
            // Sort employees alphabetically by name
            allEmployees.sort((a, b) => a.text.localeCompare(b.text));

            // Load leave settings data
            const leaveSettings = @json($leaveSettings);

            // Apply initial display limit
            limitDisplayedEmployees();

            // Add keydown event listener for backspace handling
            const searchInput = document.getElementById('employee_search');
            if (searchInput) {
                searchInput.addEventListener('keydown', handleKeyDown);
            }

            // Load leave types for the existing employee
            const employeeId = document.getElementById('employee_id').value;
            if (employeeId) {
                loadEmployeeLeaveTypes(employeeId);
            }

            // Initialize leave calculation
            calculateTotal();
        });

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
            limitDisplayedEmployees();
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
            loadEmployeeLeaveTypes(employeeId);
            
            // Recalculate leave amounts when employee changes
            calculateTotal();
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
                
                // Apply display limit
                limitDisplayedEmployees();
            }
            
            // Show dropdown if hidden
            if (!isDropdownOpen) {
                showEmployeeDropdown();
            }
            
            // Check for exact match
            const exactMatch = filteredEmployees.find(emp => emp.text.toLowerCase() === searchValue.toLowerCase());
            if (exactMatch && !isEmployeeSelected) {
                document.getElementById('employee_id').value = exactMatch.id;
            } else if (!exactMatch && !isEmployeeSelected) {
                document.getElementById('employee_id').value = '';
            }
        }

        function limitDisplayedEmployees() {
            const employeeList = document.getElementById('employee_list');
            const employeeOptions = document.querySelectorAll('#employee_list .employee-option');
            const maxDisplay = 5;
            
            // Always show all options but limit the container height
            employeeOptions.forEach(option => {
                option.style.display = 'block';
            });
            
            // Set fixed height for exactly 5 items (each item is about 48px)
            const itemHeight = 48; // Approximate height per employee option
            const maxHeight = itemHeight * maxDisplay;
            
            employeeList.style.maxHeight = maxHeight + 'px';
            employeeList.style.overflowY = employeeOptions.length > maxDisplay ? 'auto' : 'hidden';
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const searchInput = document.getElementById('employee_search');
            const dropdown = document.getElementById('employee_dropdown');
            
            if (searchInput && dropdown && !searchInput.contains(event.target) && !dropdown.contains(event.target)) {
                hideEmployeeDropdown();
            }
        });

        // Leave calculation using actual employee data
        function calculateLeaveDays() {
            calculateTotal();
        }

        // Main calculation function
        function calculateTotal() {
            const employeeId = document.getElementById('employee_id').value;
            const leaveTypeSelect = document.getElementById('leave_setting_id');
            const selectedOption = leaveTypeSelect.options[leaveTypeSelect.selectedIndex];
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;

            if (employeeId && selectedOption && selectedOption.dataset.totalDays && startDate && endDate) {
                const totalDays = parseInt(selectedOption.dataset.totalDays);
                
                // Validate that calculated days match the leave setting days
                const start = new Date(startDate);
                const end = new Date(endDate);
                const calculatedDays = Math.ceil((end.getTime() - start.getTime()) / (1000 * 3600 * 24)) + 1;
                
                if (calculatedDays !== totalDays) {
                    document.getElementById('total-days').textContent = totalDays + ' (required)';
                    document.getElementById('daily-rate').textContent = '₱0.00';
                    document.getElementById('total-amount').textContent = '₱0.00';
                    return;
                }

                // Fetch employee daily rate
                fetch('{{ route("paid-leaves.employee-daily-rate") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ employee_id: employeeId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.daily_rate) {
                        // Get pay percentage from the selected option data attributes or leave settings
                        let dailyRate = data.daily_rate;
                        let payPercentage = 100; // Default to full pay
                        
                        if (selectedOption.dataset.payPercentage) {
                            payPercentage = parseInt(selectedOption.dataset.payPercentage);
                        } else {
                            // Fallback to leave settings lookup
                            const leaveSettings = @json($leaveSettings);
                            const leaveSettingId = selectedOption.value;
                            const leaveSetting = leaveSettings.find(ls => ls.id == leaveSettingId);
                            
                            if (leaveSetting && leaveSetting.pay_rule) {
                                payPercentage = leaveSetting.pay_rule === 'full' ? 100 : 50;
                            }
                        }
                        
                        const adjustedDailyRate = Math.round((dailyRate * (payPercentage / 100)) * 100) / 100;
                        const totalAmount = Math.round((adjustedDailyRate * totalDays) * 100) / 100;
                        
                        // Update display
                        document.getElementById('total-days').textContent = totalDays;
                        document.getElementById('daily-rate').textContent = '₱' + adjustedDailyRate.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        document.getElementById('total-amount').textContent = '₱' + totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        
                        // Show calculation details
                        updateCalculationDetails(data, payPercentage);
                    } else {
                        resetCalculation();
                    }
                })
                .catch(error => {
                    console.error('Error fetching daily rate:', error);
                    resetCalculation();
                });
            } else {
                resetCalculation();
            }
        }

        function resetCalculation() {
            document.getElementById('total-days').textContent = '0';
            document.getElementById('daily-rate').textContent = '₱0.00';
            document.getElementById('total-amount').textContent = '₱0.00';
            
            // Hide calculation details
            const detailsDiv = document.getElementById('calculation-details');
            if (detailsDiv) {
                detailsDiv.classList.add('hidden');
            }
        }

        // Update calculation details display
        function updateCalculationDetails(data, payPercentage) {
            const detailsDiv = document.getElementById('calculation-details');
            const scheduleInfoSpan = document.getElementById('schedule-info');
            const methodSpan = document.getElementById('calculation-method');
            
            if (detailsDiv && scheduleInfoSpan && methodSpan) {
                // Build schedule information
                let scheduleText = 'Dynamic calculation based on employee schedule';
                if (data.schedule_info) {
                    const timeSchedule = data.schedule_info.time_schedule;
                    const daySchedule = data.schedule_info.day_schedule;
                    
                    if (timeSchedule && daySchedule) {
                        scheduleText = `${timeSchedule.total_hours}h/day, ${daySchedule.days_per_week} days/week`;
                    } else if (timeSchedule) {
                        scheduleText = `${timeSchedule.total_hours}h/day schedule`;
                    } else if (daySchedule) {
                        scheduleText = `${daySchedule.days_per_week} days/week schedule`;
                    }
                }
                
                // Build method information
                const rateType = data.rate_type || 'monthly';
                const payRule = payPercentage === 100 ? 'Full Rate' : `${payPercentage}% Rate`;
                const methodText = `${rateType.replace('_', '-').toUpperCase()} rate system (${payRule})`;
                
                scheduleInfoSpan.textContent = scheduleText;
                methodSpan.textContent = methodText;
                
                // Show the details
                detailsDiv.classList.remove('hidden');
            }
        }

        // Load available leave types for selected employee
        function loadEmployeeLeaveTypes(employeeId) {
            const leaveTypeSelect = document.getElementById('leave_setting_id');
            const balanceInfo = document.getElementById('leave_balance_info');
            
            // Clear existing options
            leaveTypeSelect.innerHTML = '<option value="">Loading...</option>';
            balanceInfo.textContent = 'Loading leave balances...';
            
            // Fetch employee leave balances
            fetch('{{ route("paid-leaves.employee-balances") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ employee_id: employeeId })
            })
            .then(response => response.json())
            .then(data => {
                leaveTypeSelect.innerHTML = '<option value="">Select Leave Type</option>';
                
                if (data.balances && data.balances.length > 0) {
                    data.balances.forEach(balance => {
                        const option = document.createElement('option');
                        option.value = balance.leave_setting_id;
                        option.textContent = `${balance.name} (${balance.available_leaves} available)`;
                        option.dataset.totalDays = balance.total_days;
                        option.dataset.availableLeaves = balance.available_leaves;
                        option.dataset.payRule = balance.pay_rule;
                        option.dataset.payPercentage = balance.pay_percentage;
                        
                        // Pre-select the original leave type
                        if (balance.leave_setting_id == originalLeaveSettingId) {
                            option.selected = true;
                        }
                        
                        leaveTypeSelect.appendChild(option);
                    });
                    balanceInfo.textContent = 'Choose the type of leave request';
                    
                    // Trigger calculation after loading leave types
                    calculateTotal();
                } else {
                    balanceInfo.textContent = 'No leave types available for this employee';
                }
            })
            .catch(error => {
                console.error('Error loading leave types:', error);
                leaveTypeSelect.innerHTML = '<option value="">Error loading leave types</option>';
                balanceInfo.textContent = 'Error loading leave types';
            });
        }

        // Auto-calculate end date based on leave type
        document.getElementById('leave_setting_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const totalDays = selectedOption.dataset.totalDays;
            const availableLeaves = selectedOption.dataset.availableLeaves;
            const balanceInfo = document.getElementById('leave_balance_info');
            
            if (totalDays && availableLeaves) {
                balanceInfo.textContent = `${totalDays} day(s) per request, ${availableLeaves} available`;
                
                // Auto-calculate end date when start date changes
                const startDateInput = document.getElementById('start_date');
                if (startDateInput.value) {
                    calculateEndDate(startDateInput.value, totalDays);
                }
            } else {
                resetCalculation();
            }
        });

        // Auto-calculate end date when start date changes
        document.getElementById('start_date').addEventListener('change', function() {
            const leaveTypeSelect = document.getElementById('leave_setting_id');
            const selectedOption = leaveTypeSelect.options[leaveTypeSelect.selectedIndex];
            const totalDays = selectedOption.dataset.totalDays;
            
            if (totalDays && this.value) {
                calculateEndDate(this.value, totalDays);
            } else {
                calculateTotal(); // Recalculate if date changed manually
            }
        });

        // Recalculate when end date changes
        document.getElementById('end_date').addEventListener('change', function() {
            calculateTotal();
        });

        // Calculate end date based on start date and total days
        function calculateEndDate(startDate, totalDays) {
            const start = new Date(startDate);
            const end = new Date(start);
            end.setDate(start.getDate() + parseInt(totalDays) - 1);
            
            const endDateInput = document.getElementById('end_date');
            const formattedEndDate = end.toISOString().split('T')[0];
            endDateInput.value = formattedEndDate;
            
            // Trigger calculation update
            calculateTotal();
        }

        // Form validation
        document.getElementById('paidLeaveForm').addEventListener('submit', function(e) {
            const reason = document.getElementById('reason').value.trim();
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            const leaveSettingId = document.getElementById('leave_setting_id').value;
            
            if (!reason) {
                e.preventDefault();
                alert('Please provide a reason for the leave request.');
                return false;
            }
            
            if (!startDate || !endDate) {
                e.preventDefault();
                alert('Please select both start and end dates.');
                return false;
            }
            
            if (!leaveSettingId) {
                e.preventDefault();
                alert('Please select a leave type.');
                return false;
            }
            
            return true;
        });
    </script>
</x-app-layout>