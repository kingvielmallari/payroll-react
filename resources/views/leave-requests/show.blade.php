@extends('layouts.app')

@section('content')
    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">
                                Leave Request Details
                            </h3>
                            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
                                Leave request submitted on {{ $leaveRequest->created_at->format('M d, Y \a\t g:i A') }}
                            </p>
                        </div>
                        <div class="flex space-x-2">
                            <a href="{{ route('leave-requests.index') }}" 
                               class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Back to List
                            </a>
                            
                            @hasrole('Employee')
                                @if($leaveRequest->status === 'pending')
                                    <a href="{{ route('leave-requests.edit', $leaveRequest) }}" 
                                       class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        Edit Request
                                    </a>
                                @endif
                            @endhasrole
                            
                            @hasanyrole('System Administrator|HR Head|HR Staff')
                                @if($leaveRequest->status === 'pending')
                                    <a href="{{ route('leave-requests.edit', $leaveRequest) }}" 
                                       class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                                        Review Request
                                    </a>
                                @endif
                            @endhasanyrole
                        </div>
                    </div>
                </div>

                <div class="px-4 py-5 sm:p-6">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        @hasanyrole('System Administrator|HR Head|HR Staff')
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Employee</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    <div class="font-medium">{{ $leaveRequest->employee->first_name ?? 'N/A' }} {{ $leaveRequest->employee->last_name ?? '' }}</div>
                                    <div class="text-gray-500 dark:text-gray-400">{{ $leaveRequest->employee->employee_code ?? 'N/A' }}</div>
                                    @if($leaveRequest->employee->department)
                                        <div class="text-gray-500 dark:text-gray-400">{{ $leaveRequest->employee->department }}</div>
                                    @endif
                                </dd>
                            </div>
                        @endhasanyrole

                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Leave Type</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 font-medium">
                                {{ $leaveRequest->leave_type_label }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Start Date</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                {{ $leaveRequest->start_date->format('M d, Y (l)') }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">End Date</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                {{ $leaveRequest->end_date->format('M d, Y (l)') }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Days Requested</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 font-medium">
                                {{ $leaveRequest->days_requested }} business day{{ $leaveRequest->days_requested > 1 ? 's' : '' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full 
                                    @if($leaveRequest->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100
                                    @elseif($leaveRequest->status === 'approved') bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100
                                    @elseif($leaveRequest->status === 'rejected') bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100
                                    @endif">
                                    {{ ucfirst($leaveRequest->status) }}
                                </span>
                            </dd>
                        </div>

                        @if($leaveRequest->approved_by && $leaveRequest->approved_at)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Approved By</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $leaveRequest->approvedByUser->name ?? 'N/A' }}
                                    <div class="text-gray-500 dark:text-gray-400">
                                        {{ $leaveRequest->approved_at->format('M d, Y \a\t g:i A') }}
                                    </div>
                                </dd>
                            </div>
                        @endif

                        @if($leaveRequest->is_paid !== null)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Leave Type</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                        @if($leaveRequest->is_paid) bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100
                                        @else bg-orange-100 text-orange-800 dark:bg-orange-800 dark:text-orange-100
                                        @endif">
                                        {{ $leaveRequest->is_paid ? 'Paid Leave' : 'Unpaid Leave' }}
                                    </span>
                                </dd>
                            </div>
                        @endif

                        @if($leaveRequest->deduction_amount && $leaveRequest->deduction_amount > 0)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Deduction Amount</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 font-medium text-red-600">
                                    ₱{{ number_format($leaveRequest->deduction_amount, 2) }}
                                </dd>
                            </div>
                        @endif
                    </dl>

                    <!-- Reason Section -->
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Reason</dt>
                        <dd class="text-sm text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-700 rounded-md p-4">
                            {{ $leaveRequest->reason }}
                        </dd>
                    </div>

                    <!-- Rejection Reason (if applicable) -->
                    @if($leaveRequest->status === 'rejected' && $leaveRequest->rejection_reason)
                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <dt class="text-sm font-medium text-red-600 dark:text-red-400 mb-2">Rejection Reason</dt>
                            <dd class="text-sm text-gray-900 dark:text-gray-100 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md p-4">
                                {{ $leaveRequest->rejection_reason }}
                            </dd>
                        </div>
                    @endif

                    <!-- Request Timeline -->
                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-4">Request Timeline</h4>
                        <div class="flow-root">
                            <ul class="-mb-8">
                                <li>
                                    <div class="relative pb-8">
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                                    <svg class="h-4 w-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                    </svg>
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                <div>
                                                    <p class="text-sm text-gray-900 dark:text-gray-100">Leave request submitted</p>
                                                </div>
                                                <div class="text-right text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                                    {{ $leaveRequest->created_at->format('M d, Y g:i A') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                @if($leaveRequest->approved_at)
                                    <li>
                                        <div class="relative">
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full 
                                                        @if($leaveRequest->status === 'approved') bg-green-500
                                                        @else bg-red-500
                                                        @endif
                                                        flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                                        @if($leaveRequest->status === 'approved')
                                                            <svg class="h-4 w-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                            </svg>
                                                        @else
                                                            <svg class="h-4 w-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                            </svg>
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                    <div>
                                                        <p class="text-sm text-gray-900 dark:text-gray-100">
                                                            Leave request {{ $leaveRequest->status }} by {{ $leaveRequest->approvedByUser->name ?? 'HR' }}
                                                        </p>
                                                    </div>
                                                    <div class="text-right text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                                        {{ $leaveRequest->approved_at->format('M d, Y g:i A') }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection