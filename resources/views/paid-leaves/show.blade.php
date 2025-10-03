<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Paid Leave Details
            </h2>
            <a href="{{ route('paid-leaves.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Back to List
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Main Details Card -->
            <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg mb-6">
                <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-gradient-to-r from-jade-50 to-emerald-50">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-jade-600 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h5 class="text-xl font-bold text-gray-900">
                                {{ $paidLeave->reference_number }}
                            </h5>
                            <p class="text-sm text-gray-600">Paid Leave Request</p>
                        </div>
                        @switch($paidLeave->status)
                            @case('pending')
                                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-yellow-100 text-yellow-800 border border-yellow-200">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                    </svg>
                                    Pending
                                </span>
                                @break
                            @case('approved')
                                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800 border border-green-200">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    Approved
                                </span>
                                @break
                            @case('rejected')
                                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-red-100 text-red-800 border border-red-200">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                    Rejected
                                </span>
                                @break
                        @endswitch
                    </div>
                    
                    @if($paidLeave->status === 'pending')
                        <div class="flex space-x-2">
                            @can('approve paid leaves')
                            <button type="button" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 shadow-md hover:shadow-lg" 
                                    onclick="approvePaidLeave({{ $paidLeave->id }})">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Approve
                            </button>
                            <button type="button" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all duration-200 shadow-md hover:shadow-lg"
                                    onclick="rejectPaidLeave({{ $paidLeave->id }})">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Reject
                            </button>
                            @endcan
                            
                            @if($paidLeave->employee_id === auth()->user()->employee?->id || auth()->user()->can('edit paid leaves'))
                            <a href="{{ route('paid-leaves.edit', $paidLeave) }}" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 shadow-md hover:shadow-lg">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Edit Leave
                            </a>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="p-6">
                    <!-- Employee and Request Information -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div class="space-y-6">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h6 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                                    <svg class="w-5 h-5 text-jade-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    Employee Information
                                </h6>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-600">Name:</span>
                                        <span class="text-sm text-gray-900">{{ $paidLeave->employee->full_name }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-600">Employee #:</span>
                                        <span class="text-sm text-gray-900">{{ $paidLeave->employee->employee_number }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-600">Department:</span>
                                        <span class="text-sm text-gray-900">{{ $paidLeave->employee->department->name ?? 'No Department' }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h6 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                                    <svg class="w-5 h-5 text-jade-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Request Details
                                </h6>
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-600">Requested By:</span>
                                        <span class="text-sm text-gray-900">{{ $paidLeave->requestedBy->name }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-600">Request Date:</span>
                                        <span class="text-sm text-gray-900">{{ $paidLeave->requested_date->format('M d, Y g:i A') }}</span>
                                    </div>
                                    @if($paidLeave->approved_by)
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-600">Approved By:</span>
                                        <span class="text-sm text-gray-900">{{ $paidLeave->approvedBy->name }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-600">Approval Date:</span>
                                        <span class="text-sm text-gray-900">{{ $paidLeave->approved_date->format('M d, Y g:i A') }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Reason for Leave -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h6 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                                    <svg class="w-5 h-5 text-jade-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Reason for Leave
                                </h6>
                                <div class="bg-white border border-gray-200 rounded-lg p-3">
                                    <p class="text-sm text-gray-800">{{ $paidLeave->reason }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Amount Details -->
                        <div class="space-y-6">
                            <div class="bg-gradient-to-br from-jade-50 to-emerald-50 rounded-lg p-4 border border-jade-200">
                                <h6 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <svg class="w-5 h-5 text-jade-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                    Leave Details
                                </h6>
                                <div class="grid grid-cols-1 gap-4">
                                    <div class="bg-white rounded-lg p-3 shadow-sm">
                                        <div class="text-center">
                                            <div class="text-sm font-medium text-gray-600">Leave Type</div>
                                            <div class="text-xl font-bold text-blue-600">{{ $paidLeave->leave_type_display }}</div>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-2">
                                        <div class="bg-white rounded-lg p-3 shadow-sm">
                                            <div class="text-center">
                                                <div class="text-xs font-medium text-gray-600">Total Days</div>
                                                <div class="text-lg font-bold text-green-600">{{ $paidLeave->total_days }}</div>
                                            </div>
                                        </div>
                                        <div class="bg-white rounded-lg p-3 shadow-sm">
                                            <div class="text-center">
                                                <div class="text-xs font-medium text-gray-600">Daily Rate</div>
                                                <div class="text-lg font-bold text-purple-600">₱{{ number_format($paidLeave->daily_rate, 2) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                         <div class="bg-white rounded-lg p-3 shadow-sm">
                                        <div class="text-center">
                                            <div class="text-xs font-medium text-gray-600">Leave Period</div>
                                            <div class="text-sm font-semibold text-gray-900">
                                                @if($paidLeave->start_date->format('Y-m-d') === $paidLeave->end_date->format('Y-m-d'))
                                                    {{ $paidLeave->start_date->format('M d, Y') }}
                                                @elseif($paidLeave->start_date->format('Y-m') === $paidLeave->end_date->format('Y-m'))
                                                    {{ $paidLeave->start_date->format('M d') }}-{{ $paidLeave->end_date->format('d, Y') }}
                                                @else
                                                    {{ $paidLeave->start_date->format('M d, Y') }} - {{ $paidLeave->end_date->format('M d, Y') }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-white rounded-lg p-3 shadow-sm border-2 border-green-300">
                                        <div class="text-center">
                                            <div class="text-sm font-medium text-gray-600">Total Amount</div>
                                            <div class="text-2xl font-bold text-green-600">₱{{ number_format($paidLeave->total_amount, 2) }}</div>
                                        </div>
                                    </div>
                                    
                               
                                    
                                    @if($paidLeave->supporting_document)
                                    <div class="bg-blue-50 rounded-lg p-3 border border-blue-200">
                                        <div class="text-center">
                                            <div class="text-xs font-medium text-blue-600 mb-2">Supporting Document</div>
                                            <a href="{{ asset('storage/' . $paidLeave->supporting_document) }}" target="_blank"
                                               class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                View Document
                                            </a>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
{{--             
            @if($paidLeave->remarks)
            <!-- Admin Remarks -->
            <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
                <div class="bg-gradient-to-r from-yellow-50 to-orange-50 p-6 border-b border-gray-200">
                    <h5 class="text-lg font-bold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-1l-4 4z"></path>
                        </svg>
                        Administrator Remarks
                    </h5>
                </div>
                <div class="p-6">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p class="text-sm text-yellow-800">{{ $paidLeave->remarks }}</p>
                    </div>
                </div>
            </div>
            @endif --}}
        </div>
    </div>

    <script>
        function approvePaidLeave(paidLeaveId) {
            if (confirm('Are you sure you want to approve this paid leave request?')) {
                const formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                formData.append('remarks', '');
                
                fetch(`/paid-leaves/${paidLeaveId}/approve`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    if (data.success) {
                        alert('Paid leave approved successfully.');
                        location.reload();
                    } else {
                        alert('Error approving paid leave: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error details:', error);
                    alert('Error approving paid leave: ' + error.message);
                });
            }
        }

        function rejectPaidLeave(paidLeaveId) {
            if (confirm('Are you sure you want to reject this paid leave request?')) {
                const formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                formData.append('remarks', 'Request rejected by administrator');
                
                fetch(`/paid-leaves/${paidLeaveId}/reject`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    if (data.success) {
                        alert('Paid leave rejected successfully.');
                        location.reload();
                    } else {
                        alert('Error rejecting paid leave: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error details:', error);
                    alert('Error rejecting paid leave: ' + error.message);
                });
            }
        }
    </script>
</x-app-layout>