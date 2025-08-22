@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">Payroll Details</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('payrolls.index') }}">Payrolls</a></li>
                            <li class="breadcrumb-item active">{{ $payroll->payroll_number }}</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <!-- Status Badge -->
                    <span class="badge badge-{{ $payroll->status === 'draft' ? 'warning' : ($payroll->status === 'processing' ? 'info' : 'success') }} fs-6 me-2">
                        {{ ucfirst($payroll->status) }}
                        @if($payrollData['is_dynamic'])
                            <i class="fas fa-sync-alt ms-1" title="Dynamic Calculation"></i>
                        @else
                            <i class="fas fa-lock ms-1" title="Static Snapshot"></i>
                        @endif
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Payroll Information Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Payroll Information
                        @if($payrollData['is_dynamic'])
                            <small class="text-warning ms-2">
                                <i class="fas fa-exclamation-triangle"></i>
                                Calculations are dynamic and will update based on current DTR data
                            </small>
                        @else
                            <small class="text-info ms-2">
                                <i class="fas fa-camera"></i>
                                Data is locked as snapshot from submission time
                            </small>
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Payroll Number:</strong><br>
                            <span class="text-primary">{{ $payroll->payroll_number }}</span>
                        </div>
                        <div class="col-md-3">
                            <strong>Period:</strong><br>
                            {{ \Carbon\Carbon::parse($payroll->period_start)->format('M d, Y') }} - 
                            {{ \Carbon\Carbon::parse($payroll->period_end)->format('M d, Y') }}
                        </div>
                        <div class="col-md-3">
                            <strong>Pay Date:</strong><br>
                            {{ \Carbon\Carbon::parse($payroll->pay_date)->format('M d, Y') }}
                        </div>
                        <div class="col-md-3">
                            <strong>Schedule:</strong><br>
                            {{ ucfirst(str_replace('_', ' ', $payroll->pay_schedule)) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    @if($payroll->status === 'draft')
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <h6 class="text-warning mb-3">
                            <i class="fas fa-edit me-2"></i>Draft Mode - Ready for Review
                        </h6>
                        <p class="mb-3">This payroll is in draft mode. All calculations are dynamic and will reflect current DTR data and system settings.</p>
                        <form action="{{ route('payrolls.submit-to-processing', $payroll) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-warning btn-lg" onclick="return confirm('Are you sure you want to submit this payroll to processing? This will create a snapshot and lock the calculations.')">
                                <i class="fas fa-arrow-right me-2"></i>Submit to Processing
                            </button>
                        </form>
                        <a href="{{ route('payrolls.edit', $payroll) }}" class="btn btn-outline-secondary btn-lg ms-2">
                            <i class="fas fa-edit me-2"></i>Edit Employees
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @elseif($payroll->status === 'processing')
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <h6 class="text-info mb-3">
                            <i class="fas fa-cog me-2"></i>Processing Mode - Ready for Approval
                        </h6>
                        <p class="mb-3">This payroll is being processed. Data is locked as a snapshot from submission time.</p>
                        @can('approve payrolls')
                            <form action="{{ route('payrolls.approve', $payroll) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-success btn-lg" onclick="return confirm('Are you sure you want to approve this payroll? This action cannot be undone.')">
                                    <i class="fas fa-check me-2"></i>Approve Payroll
                                </button>
                            </form>
                        @endcan
                        <button class="btn btn-outline-danger btn-lg ms-2" onclick="return confirm('Are you sure you want to reject this payroll? It will be sent back to draft mode.')">
                            <i class="fas fa-times me-2"></i>Reject
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @elseif($payroll->status === 'approved')
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h6 class="text-success mb-3">
                            <i class="fas fa-check-circle me-2"></i>Approved Payroll
                        </h6>
                        <p class="mb-3">This payroll has been approved and is ready for payment processing.</p>
                        <a href="{{ route('payrolls.payslip', $payroll) }}" class="btn btn-success btn-lg" target="_blank">
                            <i class="fas fa-file-pdf me-2"></i>Download Payslips
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-primary">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                    </div>
                    <h3 class="text-primary mb-1">₱{{ number_format($payrollData['totals']['basic'], 2) }}</h3>
                    <p class="text-muted mb-0">Total Basic</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-warning">
                        <i class="fas fa-calendar-alt fa-2x mb-2"></i>
                    </div>
                    <h3 class="text-warning mb-1">₱{{ number_format($payrollData['totals']['holiday'], 2) }}</h3>
                    <p class="text-muted mb-0">Total Holiday</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-info">
                        <i class="fas fa-moon fa-2x mb-2"></i>
                    </div>
                    <h3 class="text-info mb-1">₱{{ number_format($payrollData['totals']['rest'], 2) }}</h3>
                    <p class="text-muted mb-0">Total Rest</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-success">
                        <i class="fas fa-money-bill-wave fa-2x mb-2"></i>
                    </div>
                    <h3 class="text-success mb-1">₱{{ number_format($payrollData['totals']['gross'], 2) }}</h3>
                    <p class="text-muted mb-0">Total Gross</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-danger">
                        <i class="fas fa-minus-circle fa-2x mb-2"></i>
                    </div>
                    <h3 class="text-danger mb-1">₱{{ number_format($payrollData['totals']['deductions'], 2) }}</h3>
                    <p class="text-muted mb-0">Total Deductions</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-dark">
                        <i class="fas fa-hand-holding-usd fa-2x mb-2"></i>
                    </div>
                    <h3 class="text-dark mb-1">₱{{ number_format($payrollData['totals']['net'], 2) }}</h3>
                    <p class="text-muted mb-0">Total Net</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Employee Details -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>Employee Payroll Details
                        <span class="badge bg-secondary ms-2">{{ count($payrollData['employees']) }} employees</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Employee</th>
                                    <th class="text-end">Basic</th>
                                    <th class="text-end">Holiday</th>
                                    <th class="text-end">Rest</th>
                                    <th class="text-end">Overtime</th>
                                    <th class="text-end">Allowances</th>
                                    <th class="text-end">Bonuses</th>
                                    <th class="text-end">Gross Pay</th>
                                    <th class="text-end">Deductions</th>
                                    <th class="text-end">Net Pay</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payrollData['employees'] as $employeeData)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm bg-light rounded-circle me-2">
                                                    <span class="text-dark fw-bold">{{ substr($employeeData['employee']->first_name, 0, 1) }}{{ substr($employeeData['employee']->last_name, 0, 1) }}</span>
                                                </div>
                                                <div>
                                                    <div class="fw-bold">{{ $employeeData['employee']->first_name }} {{ $employeeData['employee']->last_name }}</div>
                                                    <small class="text-muted">{{ $employeeData['employee']->employee_number }}</small>
                                                    <div class="text-xs text-muted">{{ $employeeData['employee']->position->title ?? 'No Position' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <div class="text-xs text-muted">
                                                Total: {{ number_format($employeeData['hours_breakdown']['regular_hours'] ?? 0, 1) }} hrs
                                            </div>
                                            <div class="fw-bold text-primary">₱{{ number_format($employeeData['basic_pay'], 2) }}</div>
                                        </td>
                                        <td class="text-end">
                                            <div class="text-xs text-muted">
                                                @if($payrollData['is_dynamic'])
                                                    <span class="badge badge-warning">{{ ucfirst($payroll->status) }}</span>
                                                @else
                                                    <span class="badge badge-info">Locked snapshot</span>
                                                @endif
                                            </div>
                                            <div class="fw-bold text-warning">₱{{ number_format($employeeData['holiday_pay'], 2) }}</div>
                                        </td>
                                        <td class="text-end">
                                            <div class="text-xs text-muted">
                                                {{ number_format($employeeData['hours_breakdown']['rest_day_hours'] ?? 0, 1) }} hrs
                                            </div>
                                            <div class="fw-bold text-info">₱{{ number_format($employeeData['rest_day_pay'], 2) }}</div>
                                        </td>
                                        <td class="text-end">
                                            @php
                                                $regularOvertimeHours = 0;
                                                $regularOvertimeAmount = 0;
                                                $holidayOvertimeHours = 0;
                                                $holidayOvertimeAmount = 0;
                                                $totalOvertimeAmount = $employeeData['overtime_pay'];
                                            @endphp
                                            <div class="text-xs text-muted">
                                                Total: {{ number_format($employeeData['hours_breakdown']['overtime_hours'] ?? 0, 1) }} hrs
                                            </div>
                                            <div class="fw-bold" style="color: #FF6B35;">₱{{ number_format($totalOvertimeAmount, 2) }}</div>
                                        </td>
                                        <td class="text-end">
                                            <div class="text-xs text-muted">
                                                @if($payrollData['is_dynamic'])
                                                    Current settings
                                                @else
                                                    Locked snapshot
                                                @endif
                                            </div>
                                            <div class="fw-bold text-success">₱{{ number_format($employeeData['allowances'], 2) }}</div>
                                        </td>
                                        <td class="text-end">
                                            <div class="fw-bold text-success">₱{{ number_format($employeeData['bonuses'], 2) }}</div>
                                        </td>
                                        <td class="text-end">
                                            <div class="fw-bold text-success">₱{{ number_format($employeeData['gross_pay'], 2) }}</div>
                                        </td>
                                        <td class="text-end">
                                            <div class="text-xs text-muted">
                                                SSS: ₱{{ number_format($employeeData['deductions']['sss'], 2) }}<br>
                                                PhilHealth: ₱{{ number_format($employeeData['deductions']['philhealth'], 2) }}<br>
                                                Pag-IBIG: ₱{{ number_format($employeeData['deductions']['pagibig'], 2) }}<br>
                                                Tax: ₱{{ number_format($employeeData['deductions']['tax'], 2) }}
                                            </div>
                                            <div class="fw-bold text-danger">₱{{ number_format($employeeData['deductions']['total'], 2) }}</div>
                                        </td>
                                        <td class="text-end">
                                            <div class="fw-bold text-dark">₱{{ number_format($employeeData['net_pay'], 2) }}</div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Employee Detail Modals -->
@foreach($payrollData['employees'] as $employeeData)
<div class="modal fade" id="employeeDetailModal{{ $employeeData['employee']->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    {{ $employeeData['employee']->first_name }} {{ $employeeData['employee']->last_name }} - Payroll Details
                    @if($payrollData['is_dynamic'])
                        <span class="badge bg-warning ms-2">Dynamic</span>
                    @else
                        <span class="badge bg-info ms-2">Snapshot</span>
                    @endif
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @if($payrollData['is_dynamic'])
                    <!-- DRAFT MODE: Show dynamic calculation details -->
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Time Summary</h6>
                            <table class="table table-sm">
                                <tr><td>Days Worked:</td><td>{{ $employeeData['calculation']['days_worked'] }}</td></tr>
                                <tr><td>Regular Hours:</td><td>{{ number_format($employeeData['calculation']['regular_hours'], 2) }}</td></tr>
                                <tr><td>Overtime Hours:</td><td>{{ number_format($employeeData['calculation']['overtime_hours'], 2) }}</td></tr>
                                <tr><td>Holiday Hours:</td><td>{{ number_format($employeeData['calculation']['holiday_hours'], 2) }}</td></tr>
                                <tr><td>Night Diff Hours:</td><td>{{ number_format($employeeData['calculation']['night_diff_hours'], 2) }}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Earnings Breakdown</h6>
                            <table class="table table-sm">
                                <tr><td>Regular Pay:</td><td>₱{{ number_format($employeeData['calculation']['regular_pay'], 2) }}</td></tr>
                                <tr><td>Overtime Pay:</td><td>₱{{ number_format($employeeData['calculation']['overtime_pay'], 2) }}</td></tr>
                                <tr><td>Holiday Pay:</td><td>₱{{ number_format($employeeData['calculation']['holiday_pay'], 2) }}</td></tr>
                                <tr><td>Night Diff Pay:</td><td>₱{{ number_format($employeeData['calculation']['night_diff_pay'], 2) }}</td></tr>
                                <tr><td>Allowances:</td><td>₱{{ number_format($employeeData['calculation']['allowances'], 2) }}</td></tr>
                                <tr><td>Bonuses:</td><td>₱{{ number_format($employeeData['calculation']['bonuses'], 2) }}</td></tr>
                                <tr class="fw-bold"><td>Gross Pay:</td><td>₱{{ number_format($employeeData['calculation']['gross_pay'], 2) }}</td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Deductions Breakdown</h6>
                            <table class="table table-sm">
                                <tr><td>SSS Contribution:</td><td>₱{{ number_format($employeeData['deductions']['sss'], 2) }}</td></tr>
                                <tr><td>PhilHealth Contribution:</td><td>₱{{ number_format($employeeData['deductions']['philhealth'], 2) }}</td></tr>
                                <tr><td>Pag-IBIG Contribution:</td><td>₱{{ number_format($employeeData['deductions']['pagibig'], 2) }}</td></tr>
                                <tr><td>Withholding Tax:</td><td>₱{{ number_format($employeeData['deductions']['tax'], 2) }}</td></tr>
                                <tr><td>Other Deductions:</td><td>₱{{ number_format($employeeData['deductions']['custom'], 2) }}</td></tr>
                                <tr class="fw-bold"><td>Total Deductions:</td><td>₱{{ number_format($employeeData['deductions']['total'], 2) }}</td></tr>
                                <tr class="fw-bold text-primary"><td>Net Pay:</td><td>₱{{ number_format($employeeData['net_pay'], 2) }}</td></tr>
                            </table>
                        </div>
                    </div>
                @else
                    <!-- PROCESSING/APPROVED MODE: Show snapshot data -->
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Time Summary (Snapshot)</h6>
                            <table class="table table-sm">
                                <tr><td>Days Worked:</td><td>{{ $employeeData['detail']->days_worked }}</td></tr>
                                <tr><td>Regular Hours:</td><td>{{ number_format($employeeData['detail']->regular_hours, 2) }}</td></tr>
                                <tr><td>Overtime Hours:</td><td>{{ number_format($employeeData['detail']->overtime_hours, 2) }}</td></tr>
                                <tr><td>Holiday Hours:</td><td>{{ number_format($employeeData['detail']->holiday_hours, 2) }}</td></tr>
                                <tr><td>Night Diff Hours:</td><td>{{ number_format($employeeData['detail']->night_differential_hours, 2) }}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Earnings Breakdown (Snapshot)</h6>
                            <table class="table table-sm">
                                <tr><td>Regular Pay:</td><td>₱{{ number_format($employeeData['detail']->regular_pay, 2) }}</td></tr>
                                <tr><td>Overtime Pay:</td><td>₱{{ number_format($employeeData['detail']->overtime_pay, 2) }}</td></tr>
                                <tr><td>Holiday Pay:</td><td>₱{{ number_format($employeeData['detail']->holiday_pay, 2) }}</td></tr>
                                <tr><td>Night Diff Pay:</td><td>₱{{ number_format($employeeData['detail']->night_differential_pay, 2) }}</td></tr>
                                <tr><td>Allowances:</td><td>₱{{ number_format($employeeData['detail']->allowances, 2) }}</td></tr>
                                <tr><td>Bonuses:</td><td>₱{{ number_format($employeeData['detail']->bonuses, 2) }}</td></tr>
                                <tr class="fw-bold"><td>Gross Pay:</td><td>₱{{ number_format($employeeData['detail']->gross_pay, 2) }}</td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Deductions Breakdown (Snapshot)</h6>
                            <table class="table table-sm">
                                <tr><td>SSS Contribution:</td><td>₱{{ number_format($employeeData['detail']->sss_contribution, 2) }}</td></tr>
                                <tr><td>PhilHealth Contribution:</td><td>₱{{ number_format($employeeData['detail']->philhealth_contribution, 2) }}</td></tr>
                                <tr><td>Pag-IBIG Contribution:</td><td>₱{{ number_format($employeeData['detail']->pagibig_contribution, 2) }}</td></tr>
                                <tr><td>Withholding Tax:</td><td>₱{{ number_format($employeeData['detail']->withholding_tax, 2) }}</td></tr>
                                <tr><td>Other Deductions:</td><td>₱{{ number_format($employeeData['detail']->other_deductions, 2) }}</td></tr>
                                <tr class="fw-bold"><td>Total Deductions:</td><td>₱{{ number_format($employeeData['detail']->total_deductions, 2) }}</td></tr>
                                <tr class="fw-bold text-primary"><td>Net Pay:</td><td>₱{{ number_format($employeeData['detail']->net_pay, 2) }}</td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                This data was captured as a snapshot when the payroll was submitted to processing on {{ $payroll->submitted_at ? \Carbon\Carbon::parse($payroll->submitted_at)->format('M d, Y H:i A') : 'N/A' }}.
                            </small>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endforeach
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if($payrollData['is_dynamic'])
        // Add a visual indicator that data is dynamic
        console.log('Payroll is in draft mode - calculations are dynamic');
        
        // You could add auto-refresh functionality here if needed
        // setInterval(function() {
        //     location.reload();
        // }, 60000); // Refresh every minute
    @else
        console.log('Payroll is in processing/approved mode - data is static from snapshot');
    @endif
});
</script>
@endsection
