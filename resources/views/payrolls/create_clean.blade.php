@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">{{ isset($payroll) ? 'Edit' : 'Create' }} Payroll</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('payrolls.index') }}">Payrolls</a></li>
                            <li class="breadcrumb-item active">{{ isset($payroll) ? 'Edit' : 'Create' }}</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ isset($payroll) ? route('payrolls.update', $payroll) : route('payrolls.store') }}" method="POST">
        @csrf
        @if(isset($payroll))
            @method('PUT')
        @endif

        <div class="row">
            <!-- Schedule Selection -->
            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar me-2"></i>Schedule & Period
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Schedule Selection -->
                        <div class="mb-3">
                            <label for="schedule" class="form-label">Pay Schedule</label>
                            <select class="form-select" id="schedule" name="schedule" {{ isset($payroll) ? 'disabled' : '' }}>
                                <option value="">Select Pay Schedule</option>
                                @foreach($scheduleSettings as $setting)
                                    <option value="{{ $setting->code }}" 
                                        {{ (old('schedule', $selectedSchedule ?? $payroll->pay_schedule ?? '') == $setting->code) ? 'selected' : '' }}>
                                        {{ $setting->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('schedule')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Period Selection -->
                        @if(!empty($availablePeriods))
                            <div class="mb-3">
                                <label for="selected_period" class="form-label">Pay Period</label>
                                <select class="form-select" id="selected_period" name="selected_period" {{ isset($payroll) ? 'disabled' : '' }}>
                                    <option value="">Select Period</option>
                                    @foreach($availablePeriods as $period)
                                        <option value="{{ base64_encode(json_encode($period)) }}">
                                            {{ $period['period_display'] }} (Pay Date: {{ \Carbon\Carbon::parse($period['pay_date'])->format('M d, Y') }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('selected_period')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        @elseif($selectedSchedule)
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No available periods found for the selected schedule.
                            </div>
                        @endif

                        @if(isset($payroll))
                            <!-- Show current period info for edit mode -->
                            <div class="alert alert-info">
                                <strong>Current Period:</strong><br>
                                {{ \Carbon\Carbon::parse($payroll->period_start)->format('M d, Y') }} - 
                                {{ \Carbon\Carbon::parse($payroll->period_end)->format('M d, Y') }}<br>
                                <strong>Pay Date:</strong> {{ \Carbon\Carbon::parse($payroll->pay_date)->format('M d, Y') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Employee Selection -->
            <div class="col-md-6">
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>Employee Selection
                            @if($employees->count() > 0)
                                <span class="badge bg-secondary ms-2">{{ $employees->count() }} available</span>
                            @endif
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($employees->count() > 0)
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">Select Employees</label>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllEmployees()">
                                            Select All
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllEmployees()">
                                            Deselect All
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="employee-list" style="max-height: 300px; overflow-y: auto;">
                                    @foreach($employees as $employee)
                                        <div class="form-check mb-2">
                                            <input class="form-check-input employee-checkbox" 
                                                   type="checkbox" 
                                                   value="{{ $employee->id }}" 
                                                   id="employee_{{ $employee->id }}"
                                                   name="employee_ids[]"
                                                   {{ (old('employee_ids') && in_array($employee->id, old('employee_ids'))) || 
                                                      (isset($payroll) && $payroll->payrollDetails->pluck('employee_id')->contains($employee->id)) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="employee_{{ $employee->id }}">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar avatar-sm bg-light rounded-circle me-2">
                                                        <span class="text-dark fw-bold" style="font-size: 12px;">
                                                            {{ substr($employee->first_name, 0, 1) }}{{ substr($employee->last_name, 0, 1) }}
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold">{{ $employee->first_name }} {{ $employee->last_name }}</div>
                                                        <small class="text-muted">
                                                            {{ $employee->employee_number }} | 
                                                            {{ $employee->department->name ?? 'No Department' }} | 
                                                            {{ $employee->position->name ?? 'No Position' }}
                                                        </small>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                                
                                @error('employee_ids')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                @error('employee_ids.*')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>{{ isset($payroll) ? 'Draft Mode:' : 'Note:' }}</strong> 
                                {{ isset($payroll) ? 'You can modify employee selection only in draft mode.' : 'Calculations will be done dynamically when the payroll is created.' }}
                            </div>
                        @elseif($selectedSchedule)
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No active employees found for the selected pay schedule.
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Select a pay schedule first to see available employees.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" disabled>
                            <i class="fas fa-save me-2"></i>
                            {{ isset($payroll) ? 'Update' : 'Create' }} Draft Payroll
                        </button>
                        <a href="{{ isset($payroll) ? route('payrolls.show', $payroll) : route('payrolls.index') }}" 
                           class="btn btn-outline-secondary btn-lg ms-2">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                        
                        @if(isset($payroll))
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    This payroll is in <strong>{{ ucfirst($payroll->status) }}</strong> mode.
                                    @if($payroll->status === 'draft')
                                        You can modify the employee selection.
                                    @else
                                        Employee selection cannot be modified.
                                    @endif
                                </small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const scheduleSelect = document.getElementById('schedule');
    const submitBtn = document.getElementById('submitBtn');
    const employeeCheckboxes = document.querySelectorAll('.employee-checkbox');

    // Auto-submit form when schedule changes (for create mode)
    @if(!isset($payroll))
    scheduleSelect.addEventListener('change', function() {
        if (this.value) {
            const form = this.closest('form');
            const currentAction = form.action;
            
            // Change form action to create page with schedule parameter
            window.location.href = '{{ route("payrolls.create") }}?schedule=' + this.value;
        }
    });
    @endif

    // Enable/disable submit button based on form validation
    function validateForm() {
        const scheduleSelected = scheduleSelect.value !== '';
        const periodSelected = document.getElementById('selected_period') ? 
                              document.getElementById('selected_period').value !== '' : true;
        const employeesSelected = Array.from(employeeCheckboxes).some(cb => cb.checked);

        @if(isset($payroll))
        // For edit mode, only require employees selected
        submitBtn.disabled = !employeesSelected;
        @else
        // For create mode, require all fields
        submitBtn.disabled = !(scheduleSelected && periodSelected && employeesSelected);
        @endif
    }

    // Add event listeners
    scheduleSelect.addEventListener('change', validateForm);
    
    if (document.getElementById('selected_period')) {
        document.getElementById('selected_period').addEventListener('change', validateForm);
    }
    
    employeeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', validateForm);
    });

    // Initial validation
    validateForm();
});

function selectAllEmployees() {
    const checkboxes = document.querySelectorAll('.employee-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
    
    // Trigger validation
    const event = new Event('change');
    checkboxes[0]?.dispatchEvent(event);
}

function deselectAllEmployees() {
    const checkboxes = document.querySelectorAll('.employee-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Trigger validation
    const event = new Event('change');
    checkboxes[0]?.dispatchEvent(event);
}
</script>
@endsection
