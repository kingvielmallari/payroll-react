<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Cash Advance Details
            </h2>
            <a href="{{ route('cash-advances.index') }}" 
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h5 class="text-xl font-bold text-gray-900">
                                {{ $cashAdvance->reference_number }}
                            </h5>
                            <p class="text-sm text-gray-600">Cash Advance Request</p>
                        </div>
                        @switch($cashAdvance->status)
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
                            @case('fully_paid')
                                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 text-blue-800 border border-blue-200">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    Fully Paid
                                </span>
                                @break
                            @case('cancelled')
                                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-gray-100 text-gray-800 border border-gray-200">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                    Cancelled
                                </span>
                                @break
                        @endswitch
                    </div>
                    
                    @if($cashAdvance->status === 'pending')
                        <div class="flex space-x-2">
                            @can('approve cash advances')
                            <button type="button" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 shadow-md hover:shadow-lg" 
                                    onclick="showApproveModal()">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Approve
                            </button>
                            <button type="button" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all duration-200 shadow-md hover:shadow-lg"
                                    onclick="showRejectModal()">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Reject
                            </button>
                            @endcan
                        </div>
                    @endif
                </div>
                
                <div class="p-6">
                    <!-- Employee Information -->
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
                                        <span class="text-sm text-gray-900">{{ $cashAdvance->employee->full_name }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-600">Employee #:</span>
                                        <span class="text-sm text-gray-900">{{ $cashAdvance->employee->employee_number }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-600">Department:</span>
                                        <span class="text-sm text-gray-900">{{ $cashAdvance->employee->department->name ?? 'No Department' }}</span>
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
                                        <span class="text-sm text-gray-900">{{ $cashAdvance->requestedBy->name }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-600">Request Date:</span>
                                        <span class="text-sm text-gray-900">{{ $cashAdvance->requested_date->format('M d, Y g:i A') }}</span>
                                    </div>
                                    @if($cashAdvance->approved_by)
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-600">Approved By:</span>
                                        <span class="text-sm text-gray-900">{{ $cashAdvance->approvedBy->name }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-600">Approval Date:</span>
                                        <span class="text-sm text-gray-900">{{ $cashAdvance->approved_date->format('M d, Y g:i A') }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Reason for Cash Advance -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h6 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                                    <svg class="w-5 h-5 text-jade-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Reason for Cash Advance
                                </h6>
                                <div class="bg-white border border-gray-200 rounded-lg p-3">
                                    <p class="text-sm text-gray-800">{{ $cashAdvance->reason }}</p>
                                </div>
                            </div>

                            @if($cashAdvance->installments)
                            <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                                <h6 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                                    <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Payment Plan
                                </h6>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-600">Installments:</span>
                                        <span class="text-lg font-bold text-blue-700">{{ $cashAdvance->installments }} month{{ $cashAdvance->installments > 1 ? 's' : '' }}</span>
                                    </div>
                                    @if($cashAdvance->installment_amount)
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-600">Monthly Deduction:</span>
                                        <span class="text-lg font-bold text-blue-700">₱{{ number_format($cashAdvance->installment_amount, 2) }}</span>
                                    </div>
                                    @if($cashAdvance->monthly_deduction_timing)
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-600">Deduction Timing:</span>
                                        <span class="text-sm font-semibold text-blue-700">
                                            @if($cashAdvance->monthly_deduction_timing === 'first_payroll')
                                                1st Cut-off Period
                                            @elseif($cashAdvance->monthly_deduction_timing === 'last_payroll')
                                                2nd Cut-off Period
                                            @endif
                                        </span>
                                    </div>
                                    @endif
                                    @endif
                                    @if($cashAdvance->first_deduction_date)
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-600">First Deduction:</span>
                                        <span class="text-sm font-semibold text-blue-700">{{ $cashAdvance->first_deduction_period ?? $cashAdvance->first_deduction_date->format('M d, Y') }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Financial Information -->
                        <div class="space-y-6">
                            <div class="bg-gradient-to-br from-jade-50 to-emerald-50 rounded-lg p-4 border border-jade-200">
                                <h6 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <svg class="w-5 h-5 text-jade-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                    Amount Details
                                </h6>
                                <div class="grid grid-cols-1 gap-4">
                                    @if($cashAdvance->approved_amount)
                                    <div class="bg-white rounded-lg p-3 shadow-sm">
                                        <div class="text-center">
                                            <div class="text-sm font-medium text-gray-600">Approved Amount</div>
                                            <div class="text-2xl font-bold text-green-600">₱{{ number_format($cashAdvance->approved_amount, 2) }}</div>
                                        </div>
                                    </div>
                                    @endif
                                    
                                    <div class="bg-white rounded-lg p-3 shadow-sm">
                                        <div class="text-center">
                                            <div class="text-sm font-medium text-gray-600">Total Amount Paid</div>
                                            <div class="text-2xl font-bold text-blue-600">₱{{ number_format($cashAdvance->total_paid, 2) }}</div>
                                        </div>
                                    </div>
                                    
                                    @if($cashAdvance->interest_rate > 0)
                                    <div class="grid grid-cols-2 gap-2">
                                        <div class="bg-orange-50 rounded-lg p-3 border border-orange-200">
                                            <div class="text-center">
                                                <div class="text-xs font-medium text-orange-600">Interest</div>
                                                <div class="text-sm font-bold text-orange-700">{{ number_format($cashAdvance->interest_rate, 2) }}%</div>
                                                <div class="text-lg font-bold text-orange-700">₱{{ number_format($cashAdvance->interest_amount, 2) }}</div>
                                            </div>
                                        </div>
                                        <div class="bg-red-50 rounded-lg p-3 border border-red-200">
                                            <div class="text-center">
                                                <div class="text-xs font-medium text-red-600">Total Amount (with Interest)</div>
                                                <div class="text-lg font-bold text-red-700">₱{{ number_format($cashAdvance->total_amount, 2) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    
                                    <div class="bg-white rounded-lg p-3 shadow-sm border-2 {{ $cashAdvance->outstanding_balance > 0 ? 'border-yellow-300' : 'border-green-300' }}">
                                        <div class="text-center">
                                            <div class="text-sm font-medium text-gray-600">Outstanding Balance</div>
                                            <div class="text-2xl font-bold {{ $cashAdvance->outstanding_balance > 0 ? 'text-yellow-600' : 'text-green-600' }}">
                                                ₱{{ number_format($cashAdvance->outstanding_balance, 2) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @if($cashAdvance->payments->count() > 0)
            <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 border-b border-gray-200">
                    <h5 class="text-lg font-bold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                        Payment History
                    </h5>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payroll Period</th>
                               
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($cashAdvance->payments->sortByDesc('payment_date') as $payment)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $payment->payment_date->format('M d, Y') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-green-600">₱{{ number_format($payment->payment_amount ?? $payment->amount, 2) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            @if($payment->payroll)
                                                {{ $payment->payroll->period_start->format('M d') }} - 
                                                {{ $payment->payroll->period_end->format('M d, Y') }}
                                            @else
                                                <span class="text-gray-500">Manual Payment</span>
                                            @endif
                                        </div>
                                    </td>
                                 
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
                            
                      
                        </div>
                    </div>

                    <!-- Reason and Remarks -->
                    <!-- Remarks Section -->
                    @if($cashAdvance->remarks)
                    <div class="mt-8">
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <h6 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">
                                <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                                </svg>
                                Approval/Rejection Remarks
                            </h6>
                            <p class="text-sm text-gray-800">{{ $cashAdvance->remarks }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

           

            <!-- Payment History -->
            
        </div>
    </div>

<!-- Approve Modal -->
@can('approve cash advances')
@if($cashAdvance->status === 'pending')
<div id="approveModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
        <form action="{{ route('cash-advances.approve', $cashAdvance) }}" method="POST">
            @csrf
            <div class="flex items-center justify-between p-5 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Approve Cash Advance</h3>
                <button type="button" onclick="closeModal('approveModal')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6">
                <p class="mb-4 text-gray-700">Are you sure you want to approve cash advance <strong>{{ $cashAdvance->reference_number }}</strong>?</p>
                
                <div class="grid gap-4">
                    <div>
                        <label for="approved_amount" class="block text-sm font-medium text-gray-700 mb-1">Approved Amount *</label>
                        <input type="number" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-jade-500 focus:border-jade-500" 
                               id="approved_amount" name="approved_amount" 
                               value="{{ $cashAdvance->requested_amount }}"
                               step="0.01" min="100" max="{{ $cashAdvance->requested_amount }}" required>
                    </div>
                    <div>
                        <label for="installments" class="block text-sm font-medium text-gray-700 mb-1">Number of Installments *</label>
                        <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-jade-500 focus:border-jade-500" 
                                id="installments" name="installments" required onchange="calculateApprovalTotals()">
                            @for($i = 1; $i <= 12; $i++)
                            <option value="{{ $i }}" {{ $cashAdvance->installments == $i ? 'selected' : '' }}>
                                {{ $i }} month{{ $i > 1 ? 's' : '' }}
                            </option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label for="interest_rate" class="block text-sm font-medium text-gray-700 mb-1">Interest Rate (%)</label>
                        <input type="number" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-jade-500 focus:border-jade-500" 
                               id="interest_rate" name="interest_rate" 
                               value="{{ $cashAdvance->interest_rate ?? 0 }}"
                               step="0.01" min="0" max="100" onchange="calculateApprovalTotals()">
                        <p class="text-sm text-gray-500 mt-1">Leave 0 for no interest</p>
                    </div>
                    <div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="grid grid-cols-3 gap-4 text-center">
                                <div>
                                    <p class="text-sm text-gray-600">Interest Amount</p>
                                    <div class="font-bold text-amber-600" id="modal_interest_amount">₱0.00</div>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Total Amount</p>
                                    <div class="font-bold text-red-600" id="modal_total_amount">₱0.00</div>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Monthly Deduction</p>
                                    <div class="font-bold text-jade-600" id="modal_monthly_deduction">₱0.00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label for="remarks" class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                        <textarea class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-jade-500 focus:border-jade-500" 
                                  id="remarks" name="remarks" rows="3"></textarea>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end p-6 border-t border-gray-200 space-x-2">
                <button type="button" onclick="closeModal('approveModal')" 
                        class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition duration-200">Cancel</button>
                <button type="submit" 
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition duration-200">Approve</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white">
        <form action="{{ route('cash-advances.reject', $cashAdvance) }}" method="POST">
            @csrf
            <div class="flex items-center justify-between p-5 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Reject Cash Advance</h3>
                <button type="button" onclick="closeModal('rejectModal')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6">
                <p class="mb-4 text-gray-700">Are you sure you want to reject cash advance <strong>{{ $cashAdvance->reference_number }}</strong>?</p>
                
                <div>
                    <label for="reject_remarks" class="block text-sm font-medium text-gray-700 mb-1">Reason for rejection *</label>
                    <textarea class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-jade-500 focus:border-jade-500" 
                              id="reject_remarks" name="remarks" rows="3" required></textarea>
                </div>
            </div>
            <div class="flex items-center justify-end p-6 border-t border-gray-200 space-x-2">
                <button type="button" onclick="closeModal('rejectModal')" 
                        class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition duration-200">Cancel</button>
                <button type="submit" 
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition duration-200">Reject</button>
            </div>
        </form>
    </div>
</div>
@endif
@endcan

<script>
function showApproveModal() {
    calculateApprovalTotals(); // Calculate on modal open
    document.getElementById('approveModal').classList.remove('hidden');
}

function showRejectModal() {
    document.getElementById('rejectModal').classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

function calculateApprovalTotals() {
    const approvedAmount = parseFloat(document.getElementById('approved_amount').value) || 0;
    const interestRate = parseFloat(document.getElementById('interest_rate').value) || 0;
    const installments = parseInt(document.getElementById('installments').value) || 0;
    
    if (approvedAmount > 0) {
        // Calculate interest amount
        const interestAmount = (approvedAmount * interestRate) / 100;
        document.getElementById('modal_interest_amount').textContent = `₱${interestAmount.toFixed(2)}`;
        
        // Calculate total amount (principal + interest)
        const totalAmount = approvedAmount + interestAmount;
        document.getElementById('modal_total_amount').textContent = `₱${totalAmount.toFixed(2)}`;
        
        // Calculate monthly deduction
        if (installments > 0) {
            const monthlyDeduction = totalAmount / installments;
            document.getElementById('modal_monthly_deduction').textContent = `₱${monthlyDeduction.toFixed(2)}`;
        } else {
            document.getElementById('modal_monthly_deduction').textContent = '₱0.00';
        }
    } else {
        document.getElementById('modal_interest_amount').textContent = '₱0.00';
        document.getElementById('modal_total_amount').textContent = '₱0.00';
        document.getElementById('modal_monthly_deduction').textContent = '₱0.00';
    }
}

// Add event listener for approved amount changes
document.addEventListener('DOMContentLoaded', function() {
    const approvedAmountInput = document.getElementById('approved_amount');
    if (approvedAmountInput) {
        approvedAmountInput.addEventListener('input', calculateApprovalTotals);
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const approveModal = document.getElementById('approveModal');
        const rejectModal = document.getElementById('rejectModal');
        if (event.target == approveModal) {
            approveModal.classList.add('hidden');
        }
        if (event.target == rejectModal) {
            rejectModal.classList.add('hidden');
        }
    }
});
</script>
</x-app-layout>
