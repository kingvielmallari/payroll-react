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
        <div class="col-lg-8 col-12">
            <!-- Main Details Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="flex justify-between items-center p-6 border-b border-gray-200">
                    <div class="flex items-center space-x-3">
                        <h5 class="text-lg font-semibold text-gray-900">
                            {{ $cashAdvance->reference_number }}
                        </h5>
                        @switch($cashAdvance->status)
                            @case('pending')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                @break
                            @case('approved')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Approved</span>
                                @break
                            @case('rejected')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Rejected</span>
                                @break
                            @case('fully_paid')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Fully Paid</span>
                                @break
                            @case('cancelled')
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Cancelled</span>
                                @break
                        @endswitch
                    </div>
                    
                    @if($cashAdvance->status === 'pending')
                        <div class="flex space-x-2">
                            @can('approve cash advances')
                            <button type="button" class="inline-flex items-center px-3 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150" 
                                    onclick="showApproveModal()">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Approve
                            </button>
                            <button type="button" class="inline-flex items-center px-3 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    onclick="showRejectModal()">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Reject
                            </button>
                            @endcan
                        </div>
                    @endif
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Employee</label>
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $cashAdvance->employee->full_name }}</div>
                                <div class="text-sm text-gray-500">{{ $cashAdvance->employee->employee_number }}</div>
                                <div class="text-sm text-gray-500">{{ $cashAdvance->employee->department->name ?? 'No Department' }}</div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Requested By</label>
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $cashAdvance->requestedBy->name }}</div>
                                <div class="text-sm text-gray-500">{{ $cashAdvance->requested_date->format('M d, Y g:i A') }}</div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Requested Amount</label>
                            <div class="fs-5 text-primary">₱{{ number_format($cashAdvance->requested_amount, 2) }}</div>
                        </div>

                        @if($cashAdvance->approved_amount)
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Approved Amount</label>
                            <div class="fs-5 text-success">₱{{ number_format($cashAdvance->approved_amount, 2) }}</div>
                        </div>
                        @endif

                        @if($cashAdvance->interest_rate > 0)
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Interest Rate</label>
                            <div class="fs-6 text-orange-600">{{ number_format($cashAdvance->interest_rate, 2) }}%</div>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Interest Amount</label>
                            <div class="fs-6 text-orange-600">₱{{ number_format($cashAdvance->interest_amount, 2) }}</div>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Total Amount</label>
                            <div class="fs-5 text-danger">₱{{ number_format($cashAdvance->total_amount, 2) }}</div>
                        </div>
                        @endif

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Outstanding Balance</label>
                            <div class="fs-5 {{ $cashAdvance->outstanding_balance > 0 ? 'text-warning' : 'text-success' }}">
                                ₱{{ number_format($cashAdvance->outstanding_balance, 2) }}
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Installments</label>
                            <div>
                                {{ $cashAdvance->installments }} month{{ $cashAdvance->installments > 1 ? 's' : '' }}
                                @if($cashAdvance->installment_amount)
                                <br>
                                <small class="text-muted">
                                    ₱{{ number_format($cashAdvance->installment_amount, 2) }} per month
                                    @if($cashAdvance->interest_rate > 0)
                                        <span class="text-orange-600">(includes interest)</span>
                                    @endif
                                </small>
                                @endif
                            </div>
                        </div>

                        @if($cashAdvance->first_deduction_date)
                        <div class="col-md-6">
                            <label class="form-label fw-bold">First Deduction Date</label>
                            <div>{{ $cashAdvance->first_deduction_date->format('M d, Y') }}</div>
                        </div>
                        @endif

                        <div class="col-12">
                            <label class="form-label fw-bold">Reason</label>
                            <div class="bg-light p-3 rounded">{{ $cashAdvance->reason }}</div>
                        </div>

                        @if($cashAdvance->approved_by)
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Approved By</label>
                            <div>
                                {{ $cashAdvance->approvedBy->name }}
                                <br>
                                <small class="text-muted">{{ $cashAdvance->approved_date->format('M d, Y g:i A') }}</small>
                            </div>
                        </div>
                        @endif

                        @if($cashAdvance->remarks)
                        <div class="col-12">
                            <label class="form-label fw-bold">Remarks</label>
                            <div class="bg-light p-3 rounded">{{ $cashAdvance->remarks }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Payment History -->
            @if($cashAdvance->payments->count() > 0)
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Payment Date</th>
                                    <th>Amount</th>
                                    <th>Payroll Period</th>
                                    <th>Remaining Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cashAdvance->payments->sortByDesc('payment_date') as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date->format('M d, Y') }}</td>
                                    <td>₱{{ number_format($payment->amount, 2) }}</td>
                                    <td>
                                        @if($payment->payroll)
                                            {{ $payment->payroll->period_start->format('M d') }} - 
                                            {{ $payment->payroll->period_end->format('M d, Y') }}
                                        @else
                                            Manual Payment
                                        @endif
                                    </td>
                                    <td>₱{{ number_format($payment->remaining_balance, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4 col-12">
            <!-- Summary Card -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Summary</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="text-muted small">Total Approved</div>
                                <div class="fw-bold">
                                    ₱{{ number_format($cashAdvance->approved_amount ?? $cashAdvance->requested_amount, 2) }}
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="text-muted small">Total Paid</div>
                                <div class="fw-bold text-success">
                                    ₱{{ number_format($cashAdvance->payments->sum('amount'), 2) }}
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="text-muted small">Outstanding</div>
                                <div class="fw-bold text-warning">
                                    ₱{{ number_format($cashAdvance->outstanding_balance, 2) }}
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="text-muted small">Progress</div>
                                <div class="fw-bold">
                                    {{ $cashAdvance->approved_amount > 0 ? number_format(($cashAdvance->payments->sum('amount') / $cashAdvance->approved_amount) * 100, 1) : 0 }}%
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    @if($cashAdvance->approved_amount > 0)
                    <div class="mt-3">
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" 
                                 style="width: {{ ($cashAdvance->payments->sum('amount') / $cashAdvance->approved_amount) * 100 }}%">
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Actions Card -->
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Actions</h6>
                </div>
                <div class="card-body">
                    @if($cashAdvance->status === 'pending')
                        @can('approve cash advances')
                        <button type="button" class="btn btn-success w-100 mb-2" onclick="showApproveModal()">
                            <i class="fas fa-check me-1"></i> Approve Request
                        </button>
                        <button type="button" class="btn btn-danger w-100 mb-2" onclick="showRejectModal()">
                            <i class="fas fa-times me-1"></i> Reject Request
                        </button>
                        @endcan
                    @endif

                    <button type="button" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150 w-full justify-center" onclick="window.print()">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        Print Details
                    </button>
                </div>
            </div>
        </div>
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
