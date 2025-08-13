<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CashAdvance extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'employee_id',
        'payroll_id',
        'reference_number',
        'requested_amount',
        'approved_amount',
        'outstanding_balance',
        'status',
        'installments',
        'installment_amount',
        'interest_rate',
        'interest_amount',
        'total_amount',
        'reason',
        'remarks',
        'requested_date',
        'approved_date',
        'first_deduction_date',
        'deduction_period',
        'semi_monthly_distribution',
        'requested_by',
        'approved_by',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'requested_date' => 'date',
        'approved_date' => 'date',
        'first_deduction_date' => 'date',
    ];

    protected $attributes = [
        'status' => 'pending',
        'outstanding_balance' => 0,
        'interest_rate' => 0,
        'interest_amount' => 0,
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payments()
    {
        return $this->hasMany(CashAdvancePayment::class);
    }

    public function deductions()
    {
        return $this->hasMany(Deduction::class, 'cash_advance_id');
    }

    public function payrollDetails()
    {
        return $this->hasMany(PayrollDetail::class, 'cash_advance_id');
    }

    // Scope for filtering by status
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Scope for filtering by employee
    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    // Calculate total amount to be repaid (principal + interest)
    public function getTotalAmountAttribute($value)
    {
        if ($value) {
            return $value;
        }
        return $this->approved_amount + $this->interest_amount;
    }

    // Calculate total amount paid so far
    public function getTotalPaidAttribute()
    {
        return $this->payments()->sum('amount');
    }

    // Calculate remaining balance
    public function getRemainingBalanceAttribute()
    {
        return $this->total_amount - $this->total_paid;
    }

    // Check if cash advance is fully paid
    public function getIsFullyPaidAttribute()
    {
        return $this->remaining_balance <= 0;
    }

    // Get next payment due date
    public function getNextPaymentDueDateAttribute()
    {
        if ($this->is_fully_paid) {
            return null;
        }

        $lastPayment = $this->payments()->latest('payment_date')->first();
        if (!$lastPayment) {
            return $this->first_deduction_date;
        }

        // Calculate next payment date based on payroll frequency
        return $lastPayment->payment_date->addDays(15); // Assuming bi-weekly payroll
    }

    // Generate unique reference number
    public static function generateReferenceNumber()
    {
        $year = date('Y');
        $month = date('m');
        $lastRecord = self::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastRecord ? (int)substr($lastRecord->reference_number, -4) + 1 : 1;

        return 'CA-' . $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    // Calculate installment amount
    public function calculateInstallmentAmount()
    {
        if ($this->installments > 0) {
            return round($this->total_amount / $this->installments, 2);
        }
        return 0;
    }

    // Update outstanding balance
    public function updateOutstandingBalance()
    {
        $this->outstanding_balance = $this->remaining_balance;
        $this->save();
    }

    // Add cash advance to current payroll period
    public function addToCurrentPayroll()
    {
        // Get current payroll period for this employee
        $currentPayroll = Payroll::where('employee_id', $this->employee_id)
            ->whereIn('status', ['draft', 'in_progress'])
            ->orderBy('pay_period_end', 'desc')
            ->first();

        if (!$currentPayroll) {
            return false;
        }

        // Create automatic deduction
        return $this->createAutomaticDeduction($currentPayroll);
    }

    // Create automatic deduction for payroll
    public function createAutomaticDeduction($payroll)
    {
        // Check if deduction already exists for this payroll
        $existingDeduction = Deduction::where('payroll_id', $payroll->id)
            ->where('cash_advance_id', $this->id)
            ->first();

        if ($existingDeduction) {
            return $existingDeduction;
        }

        // Calculate deduction amount (installment amount or remaining balance, whichever is smaller)
        $deductionAmount = min($this->installment_amount, $this->remaining_balance);

        if ($deductionAmount <= 0) {
            return null;
        }

        // Create deduction record
        $deduction = Deduction::create([
            'payroll_id' => $payroll->id,
            'cash_advance_id' => $this->id,
            'type' => 'cash_advance',
            'description' => 'Cash Advance Deduction - ' . $this->reference_number,
            'amount' => $deductionAmount,
            'status' => 'active',
        ]);

        // Update outstanding balance
        $this->outstanding_balance = $this->remaining_balance - $deductionAmount;

        // Mark as fully paid if balance reaches zero
        if ($this->outstanding_balance <= 0) {
            $this->status = 'completed';
            $this->outstanding_balance = 0;
        }

        $this->save();

        return $deduction;
    }

    // Get deduction schedule
    public function getDeductionSchedule()
    {
        $schedule = [];
        $remainingBalance = $this->total_amount;
        $currentDate = $this->first_deduction_date;

        for ($i = 1; $i <= $this->installments && $remainingBalance > 0; $i++) {
            $deductionAmount = min($this->installment_amount, $remainingBalance);

            $schedule[] = [
                'installment' => $i,
                'date' => $currentDate,
                'amount' => $deductionAmount,
                'remaining_balance' => $remainingBalance - $deductionAmount,
            ];

            $remainingBalance -= $deductionAmount;
            $currentDate = $currentDate->addDays(15); // Assuming bi-weekly payroll
        }

        return $schedule;
    }

    // Check if cash advance can be deleted
    public function canBeDeleted()
    {
        // Can't delete if there are payments or if status is not pending
        return $this->payments()->count() === 0 && $this->status === 'pending';
    }

    // Auto-approve if amount is within policy limits
    public function autoApproveIfEligible()
    {
        // Example: Auto-approve if amount is less than 10,000 and employee has good standing
        if ($this->requested_amount <= 10000 && $this->employee->hasGoodStanding()) {
            $this->status = 'approved';
            $this->approved_amount = $this->requested_amount;
            $this->approved_date = now();
            $this->approved_by = Auth::id();

            // Calculate total amount with interest
            $this->calculateInterestAndTotal();

            $this->save();
            return true;
        }

        return false;
    }

    // Calculate interest and total amount
    public function calculateInterestAndTotal()
    {
        // Use approved_amount if set, otherwise use requested_amount for calculations
        $baseAmount = $this->approved_amount ?? $this->requested_amount;

        if ($this->interest_rate > 0 && $baseAmount) {
            $this->interest_amount = round(($baseAmount * $this->interest_rate / 100), 2);
        } else {
            $this->interest_amount = 0;
        }

        $this->total_amount = $baseAmount + $this->interest_amount;
        $this->outstanding_balance = $this->total_amount;

        // Calculate installment amount based on total amount (including interest)
        if ($this->installments > 0) {
            $this->installment_amount = round($this->total_amount / $this->installments, 2);
        }
    }

    // Update calculations - alias for calculateInterestAndTotal
    public function updateCalculations()
    {
        return $this->calculateInterestAndTotal();
    }

    // Approve the cash advance
    public function approve($approvedAmount, $installments, $approvedById, $remarks = null, $interestRate = null)
    {
        $this->approved_amount = $approvedAmount;
        $this->installments = $installments;
        $this->interest_rate = $interestRate ?? $this->interest_rate;
        $this->approved_by = $approvedById;
        $this->approved_date = now();
        $this->remarks = $remarks;
        $this->status = 'approved';

        // Recalculate all amounts based on approved amount
        $this->calculateInterestAndTotal();

        $this->save();

        return true;
    }
}
