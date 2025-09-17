@extends('layouts.app')

@section('content')
    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                                @hasrole('Employee')
                                    Edit Leave Request
                                @else
                                    Review Leave Request
                                @endhasrole
                            </h3>
                            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                                @hasrole('Employee')
                                    Update your leave request details.
                                @else
                                    Review and approve or reject this leave request.
                                @endhasrole
                            </p>
                        </div>
                        <div class="flex space-x-2">
                            <a href="{{ route('leave-requests.show', $leaveRequest) }}" 
                               class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                View Details
                            </a>
                            <a href="{{ route('leave-requests.index') }}" 
                               class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Back to List
                            </a>
                        </div>
                    </div>
                </div>

                @hasanyrole('System Administrator|HR Head|HR Staff')
                    <!-- Employee Info for HR -->
                    <div class="px-4 py-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                        <div class="flex items-center space-x-4">
                            <div class="flex-1">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">Employee Information</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    <span class="font-medium">{{ $leaveRequest->employee->first_name ?? 'N/A' }} {{ $leaveRequest->employee->last_name ?? '' }}</span>
                                    ({{ $leaveRequest->employee->employee_code ?? 'N/A' }})
                                    @if($leaveRequest->employee->department)
                                        • {{ $leaveRequest->employee->department }}
                                    @endif
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Submitted</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $leaveRequest->created_at->format('M d, Y') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endhasanyrole

                <form action="{{ route('leave-requests.update', $leaveRequest) }}" method="POST" class="p-6">
                    @csrf
                    @method('PUT')
                    
                    <div class="grid grid-cols-1 gap-6">
                        <!-- Leave Type -->
                        <div>
                            <label for="leave_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Leave Type <span class="text-red-500">*</span>
                            </label>
                            <select id="leave_type" name="leave_type" required
                                    @hasanyrole('System Administrator|HR Head|HR Staff') @if($leaveRequest->status !== 'pending') disabled @endif @endhasanyrole
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100 @error('leave_type') border-red-500 @enderror">
                                <option value="">Select Leave Type</option>
                                <option value="sick" {{ old('leave_type', $leaveRequest->leave_type) === 'sick' ? 'selected' : '' }}>Sick Leave</option>
                                <option value="vacation" {{ old('leave_type', $leaveRequest->leave_type) === 'vacation' ? 'selected' : '' }}>Vacation Leave</option>
                                <option value="emergency" {{ old('leave_type', $leaveRequest->leave_type) === 'emergency' ? 'selected' : '' }}>Emergency Leave</option>
                                <option value="maternity" {{ old('leave_type', $leaveRequest->leave_type) === 'maternity' ? 'selected' : '' }}>Maternity Leave</option>
                                <option value="paternity" {{ old('leave_type', $leaveRequest->leave_type) === 'paternity' ? 'selected' : '' }}>Paternity Leave</option>
                                <option value="bereavement" {{ old('leave_type', $leaveRequest->leave_type) === 'bereavement' ? 'selected' : '' }}>Bereavement Leave</option>
                                <option value="special" {{ old('leave_type', $leaveRequest->leave_type) === 'special' ? 'selected' : '' }}>Special Leave</option>
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
                                       value="{{ old('start_date', $leaveRequest->start_date->format('Y-m-d')) }}"
                                       @hasrole('Employee') min="{{ date('Y-m-d') }}" @endhasrole
                                       @hasanyrole('System Administrator|HR Head|HR Staff') @if($leaveRequest->status !== 'pending') readonly @endif @endhasanyrole
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
                                       value="{{ old('end_date', $leaveRequest->end_date->format('Y-m-d')) }}"
                                       @hasrole('Employee') min="{{ date('Y-m-d') }}" @endhasrole
                                       @hasanyrole('System Administrator|HR Head|HR Staff') @if($leaveRequest->status !== 'pending') readonly @endif @endhasanyrole
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100 @error('end_date') border-red-500 @enderror">
                                @error('end_date')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Days Requested -->
                        <div>
                            <label for="days_requested" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Days Requested
                            </label>
                            <input type="number" id="days_requested" name="days_requested" readonly
                                   value="{{ old('days_requested', $leaveRequest->days_requested) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm bg-gray-100 dark:bg-gray-600 dark:text-gray-100">
                        </div>

                        <!-- Reason -->
                        <div>
                            <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Reason <span class="text-red-500">*</span>
                            </label>
                            <textarea id="reason" name="reason" rows="4" required
                                      @hasanyrole('System Administrator|HR Head|HR Staff') @if($leaveRequest->status !== 'pending') readonly @endif @endhasanyrole
                                      class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100 @error('reason') border-red-500 @enderror"
                                      placeholder="Please provide a detailed reason for your leave request...">{{ old('reason', $leaveRequest->reason) }}</textarea>
                            @error('reason')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @hasanyrole('System Administrator|HR Head|HR Staff')
                            @if($leaveRequest->status === 'pending')
                                <!-- HR Review Section -->
                                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                                    <h4 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">HR Review</h4>
                                    
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <!-- Status -->
                                        <div>
                                            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Decision <span class="text-red-500">*</span>
                                            </label>
                                            <select id="status" name="status" required
                                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100 @error('status') border-red-500 @enderror">
                                                <option value="">Select Decision</option>
                                                <option value="approved" {{ old('status', $leaveRequest->status) === 'approved' ? 'selected' : '' }}>Approve</option>
                                                <option value="rejected" {{ old('status', $leaveRequest->status) === 'rejected' ? 'selected' : '' }}>Reject</option>
                                            </select>
                                            @error('status')
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- Is Paid -->
                                        <div>
                                            <label for="is_paid" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Leave Type
                                            </label>
                                            <select id="is_paid" name="is_paid"
                                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100">
                                                <option value="">Not Specified</option>
                                                <option value="1" {{ old('is_paid', $leaveRequest->is_paid) === 1 ? 'selected' : '' }}>Paid Leave</option>
                                                <option value="0" {{ old('is_paid', $leaveRequest->is_paid) === 0 ? 'selected' : '' }}>Unpaid Leave</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Deduction Amount -->
                                    <div class="mt-4">
                                        <label for="deduction_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Deduction Amount (if unpaid or partial)
                                        </label>
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <span class="text-gray-500 sm:text-sm">₱</span>
                                            </div>
                                            <input type="number" id="deduction_amount" name="deduction_amount" step="0.01" min="0"
                                                   value="{{ old('deduction_amount', $leaveRequest->deduction_amount) }}"
                                                   class="pl-7 mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100"
                                                   placeholder="0.00">
                                        </div>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            Enter the amount to be deducted from the employee's salary for this leave period.
                                        </p>
                                    </div>

                                    <!-- Rejection Reason -->
                                    <div class="mt-4" id="rejection_reason_field" style="display: none;">
                                        <label for="rejection_reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Rejection Reason <span class="text-red-500">*</span>
                                        </label>
                                        <textarea id="rejection_reason" name="rejection_reason" rows="3"
                                                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-100"
                                                  placeholder="Please provide a reason for rejecting this leave request...">{{ old('rejection_reason', $leaveRequest->rejection_reason) }}</textarea>
                                        @error('rejection_reason')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            @endif
                        @endhasanyrole

                        <!-- Submit Buttons -->
                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <a href="{{ route('leave-requests.show', $leaveRequest) }}" 
                               class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Cancel
                            </a>
                            
                            @hasrole('Employee')
                                @if($leaveRequest->status === 'pending')
                                    <button type="submit" 
                                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                        Update Request
                                    </button>
                                @endif
                            @endhasrole
                            
                            @hasanyrole('System Administrator|HR Head|HR Staff')
                                @if($leaveRequest->status === 'pending')
                                    <button type="submit" 
                                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                        Submit Review
                                    </button>
                                @endif
                            @endhasanyrole
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
            const statusSelect = document.getElementById('status');
            const rejectionReasonField = document.getElementById('rejection_reason_field');

            function calculateDays() {
                if (startDate.value && endDate.value && !startDate.readOnly) {
                    const start = new Date(startDate.value);
                    const end = new Date(endDate.value);
                    
                    if (end >= start) {
                        let count = 0;
                        const current = new Date(start);
                        
                        while (current <= end) {
                            const dayOfWeek = current.getDay();
                            if (dayOfWeek !== 0 && dayOfWeek !== 6) {
                                count++;
                            }
                            current.setDate(current.getDate() + 1);
                        }
                        
                        daysRequested.value = count;
                    } else {
                        daysRequested.value = '';
                    }
                }
            }

            function toggleRejectionReason() {
                if (statusSelect && rejectionReasonField) {
                    if (statusSelect.value === 'rejected') {
                        rejectionReasonField.style.display = 'block';
                        document.getElementById('rejection_reason').required = true;
                    } else {
                        rejectionReasonField.style.display = 'none';
                        document.getElementById('rejection_reason').required = false;
                    }
                }
            }

            // Event listeners
            if (startDate && endDate && !startDate.readOnly) {
                startDate.addEventListener('change', function() {
                    endDate.min = this.value;
                    if (endDate.value && endDate.value < this.value) {
                        endDate.value = this.value;
                    }
                    calculateDays();
                });

                endDate.addEventListener('change', calculateDays);
            }

            if (statusSelect) {
                statusSelect.addEventListener('change', toggleRejectionReason);
                toggleRejectionReason(); // Initial check
            }

            // Calculate on page load
            calculateDays();
        });
    </script>
@endsection