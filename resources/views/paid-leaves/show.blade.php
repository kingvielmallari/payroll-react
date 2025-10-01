<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Paid Leave Details
            </h2>
            <a href="{{ route('paid-leaves.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Back to List
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Leave Request Header -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">
                                    {{ $paidLeave->reference_number }}
                                </h1>
                                <p class="text-sm text-gray-600">Paid Leave Request</p>
                            </div>
                            <div class="text-right">
                                {!! $paidLeave->status_badge !!}
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons for Pending Requests -->
                    @if($paidLeave->status === 'pending')
                    <div class="mb-6 flex space-x-3">
                        @can('approve paid leaves')
                        <button onclick="approvePaidLeave({{ $paidLeave->id }})" 
                                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Approve Leave
                        </button>
                        <button onclick="rejectPaidLeave({{ $paidLeave->id }})" 
                                class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Reject Leave
                        </button>
                        @endcan
                        
                        @if($paidLeave->employee_id === auth()->user()->employee?->id || auth()->user()->can('edit paid leaves'))
                        <a href="{{ route('paid-leaves.edit', $paidLeave) }}" 
                           class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Edit Leave
                        </a>
                        @endif
                    </div>
                    @endif

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Left Column: Employee Details -->
                        <div class="space-y-6">
                            <!-- Employee Information -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Employee Information</h3>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Full Name</label>
                                        <span class="text-sm text-gray-900">{{ $paidLeave->employee->full_name }}</span>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Employee Number</label>
                                        <span class="text-sm text-gray-900">{{ $paidLeave->employee->employee_number }}</span>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Department</label>
                                        <span class="text-sm text-gray-900">{{ $paidLeave->employee->department->name ?? 'No Department' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Request Details -->
                        <div class="space-y-6">
                            <!-- Request Information -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Request Information</h3>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Requested By</label>
                                        <span class="text-sm text-gray-900">{{ $paidLeave->requestedBy->name }}</span>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Requested Date</label>
                                        <span class="text-sm text-gray-900">{{ $paidLeave->requested_date->format('M d, Y g:i A') }}</span>
                                    </div>
                                    @if($paidLeave->approved_by)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Approved By</label>
                                        <span class="text-sm text-gray-900">{{ $paidLeave->approvedBy->name }}</span>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-500">Approved Date</label>
                                        <span class="text-sm text-gray-900">{{ $paidLeave->approved_date->format('M d, Y g:i A') }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Leave Details -->
                    <div class="mt-6 bg-white border rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Leave Details</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                            <div class="text-center p-4 bg-blue-50 rounded-lg">
                                <div class="text-2xl font-bold text-blue-600">{{ $paidLeave->leave_type_display }}</div>
                                <div class="text-sm text-gray-600">Leave Type</div>
                            </div>
                            <div class="text-center p-4 bg-green-50 rounded-lg">
                                <div class="text-2xl font-bold text-green-600">{{ $paidLeave->total_days }}</div>
                                <div class="text-sm text-gray-600">Total Days</div>
                            </div>
                            <div class="text-center p-4 bg-purple-50 rounded-lg">
                                <div class="text-2xl font-bold text-purple-600">₱{{ number_format($paidLeave->daily_rate, 2) }}</div>
                                <div class="text-sm text-gray-600">Daily Rate</div>
                            </div>
                            <div class="text-center p-4 bg-yellow-50 rounded-lg">
                                <div class="text-2xl font-bold text-yellow-600">₱{{ number_format($paidLeave->total_amount, 2) }}</div>
                                <div class="text-sm text-gray-600">Total Amount</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Leave Period</label>
                                <div class="text-sm text-gray-900">
                                    {{ $paidLeave->start_date->format('M d, Y') }} - {{ $paidLeave->end_date->format('M d, Y') }}
                                </div>
                            </div>
                            @if($paidLeave->supporting_document)
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Supporting Document</label>
                                <a href="{{ asset('storage/' . $paidLeave->supporting_document) }}" target="_blank"
                                   class="inline-flex items-center text-blue-600 hover:text-blue-800">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    View Document
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Reason for Leave -->
                    <div class="mt-6">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Reason for Leave</h4>
                            <p class="text-sm text-gray-800">{{ $paidLeave->reason }}</p>
                        </div>
                    </div>

                    @if($paidLeave->remarks)
                    <!-- Admin Remarks -->
                    <div class="mt-6">
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-yellow-800 mb-2">Administrator Remarks</h4>
                            <p class="text-sm text-yellow-700">{{ $paidLeave->remarks }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        function approvePaidLeave(paidLeaveId) {
            const remarks = prompt('Enter approval remarks (optional):');
            if (remarks !== null) { // User didn't cancel
                fetch(`/paid-leaves/${paidLeaveId}/approve`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ remarks: remarks })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Paid leave approved successfully!');
                        location.reload();
                    } else {
                        alert('Error approving paid leave.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error approving paid leave.');
                });
            }
        }

        function rejectPaidLeave(paidLeaveId) {
            const remarks = prompt('Enter rejection reason (required):');
            if (remarks && remarks.trim()) {
                fetch(`/paid-leaves/${paidLeaveId}/reject`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ remarks: remarks })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Paid leave rejected.');
                        location.reload();
                    } else {
                        alert('Error rejecting paid leave.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error rejecting paid leave.');
                });
            } else if (remarks !== null) {
                alert('Rejection reason is required.');
            }
        }
    </script>
</x-app-layout>