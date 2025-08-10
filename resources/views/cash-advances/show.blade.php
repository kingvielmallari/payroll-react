@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0">Cash Advance Details</h4>
                <div class="page-title-right">
                    <a href="{{ route('cash-advances.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 col-12">
            <!-- Main Details Card -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        {{ $cashAdvance->reference_number }}
                        @switch($cashAdvance->status)
                            @case('pending')
                                <span class="badge bg-warning ms-2">Pending</span>
                                @break
                            @case('approved')
                                <span class="badge bg-success ms-2">Approved</span>
                                @break
                            @case('rejected')
                                <span class="badge bg-danger ms-2">Rejected</span>
                                @break
                            @case('fully_paid')
                                <span class="badge bg-info ms-2">Fully Paid</span>
                                @break
                            @case('cancelled')
                                <span class="badge bg-secondary ms-2">Cancelled</span>
                                @break
                        @endswitch
                    </h5>
                    
                    @if($cashAdvance->status === 'pending')
                        <div class="btn-group">
                            @can('approve cash advances')
                            <button type="button" class="btn btn-success btn-sm" 
                                    onclick="showApproveModal()">
                                <i class="fas fa-check me-1"></i> Approve
                            </button>
                            <button type="button" class="btn btn-danger btn-sm"
                                    onclick="showRejectModal()">
                                <i class="fas fa-times me-1"></i> Reject
                            </button>
                            @endcan
                        </div>
                    @endif
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Employee</label>
                            <div>
                                {{ $cashAdvance->employee->full_name }}
                                <br>
                                <small class="text-muted">{{ $cashAdvance->employee->employee_number }}</small>
                                <br>
                                <small class="text-muted">{{ $cashAdvance->employee->department->name ?? 'No Department' }}</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Requested By</label>
                            <div>
                                {{ $cashAdvance->requestedBy->name }}
                                <br>
                                <small class="text-muted">{{ $cashAdvance->requested_date->format('M d, Y g:i A') }}</small>
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
                                @if($cashAdvance->approved_amount && $cashAdvance->installments)
                                <br>
                                <small class="text-muted">
                                    ₱{{ number_format($cashAdvance->approved_amount / $cashAdvance->installments, 2) }} per month
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

                    <button type="button" class="btn btn-outline-primary w-100" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Print Details
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
@can('approve cash advances')
@if($cashAdvance->status === 'pending')
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('cash-advances.approve', $cashAdvance) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Approve Cash Advance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to approve cash advance <strong>{{ $cashAdvance->reference_number }}</strong>?</p>
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="approved_amount" class="form-label">Approved Amount *</label>
                            <input type="number" class="form-control" id="approved_amount" name="approved_amount" 
                                   value="{{ $cashAdvance->requested_amount }}"
                                   step="0.01" min="100" max="{{ $cashAdvance->requested_amount }}" required>
                        </div>
                        <div class="col-12">
                            <label for="installments" class="form-label">Number of Installments *</label>
                            <select class="form-select" id="installments" name="installments" required>
                                @for($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ $cashAdvance->installments == $i ? 'selected' : '' }}>
                                    {{ $i }} month{{ $i > 1 ? 's' : '' }}
                                </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('cash-advances.reject', $cashAdvance) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Reject Cash Advance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to reject cash advance <strong>{{ $cashAdvance->reference_number }}</strong>?</p>
                    
                    <div class="mb-3">
                        <label for="reject_remarks" class="form-label">Reason for rejection *</label>
                        <textarea class="form-control" id="reject_remarks" name="remarks" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endcan
@endsection

@push('scripts')
<script>
function showApproveModal() {
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}

function showRejectModal() {
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>
@endpush
