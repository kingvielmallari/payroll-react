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
        'monthly_installments',
        'installment_amount',
        'interest_rate',
        'interest_amount',
        'total_amount',
        'reason',
        'remarks',
        'requested_date',
        'approved_date',
        'first_deduction_date',
        'first_deduction_period_start',
        'first_deduction_period_end',
        'starting_payroll_period',
        'deduction_frequency',
        'monthly_deduction_timing',
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
        'first_deduction_period_start' => 'date',
        'first_deduction_period_end' => 'date',
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

    /**
     * Record a payment for this cash advance
     */
    public function recordPayment($amount, $payrollId = null, $payrollDetailId = null, $notes = null)
    {
        if ($amount <= 0) {
            return null;
        }

        // Don't exceed outstanding balance
        $paymentAmount = min($amount, $this->outstanding_balance);

        if ($paymentAmount <= 0) {
            return null;
        }

        // Create payment record
        $payment = CashAdvancePayment::create([
            'cash_advance_id' => $this->id,
            'payroll_id' => $payrollId,
            'payroll_detail_id' => $payrollDetailId,
            'amount' => $paymentAmount,
            'payment_amount' => $paymentAmount,
            'remaining_balance' => $this->outstanding_balance - $paymentAmount,
            'payment_date' => now(),
            'notes' => $notes,
        ]);

        // Update outstanding balance
        $this->outstanding_balance -= $paymentAmount;

        // Mark as completed if fully paid
        if ($this->outstanding_balance <= 0) {
            $this->status = 'completed';
            $this->outstanding_balance = 0;
        }

        $this->save();

        return $payment;
    }

    /**
     * Reject the cash advance
     */
    public function reject($remarks, $rejectedById)
    {
        $this->status = 'rejected';
        $this->approved_by = $rejectedById;
        $this->approved_date = now();
        $this->remarks = $remarks;

        $this->save();

        return true;
    }

    /**
     * Get the first deduction period as a formatted string (e.g., "Aug 16 - 31, 2025")
     */
    public function getFirstDeductionPeriodAttribute()
    {
        // If both start and end dates are stored, use them directly
        if ($this->first_deduction_period_start && $this->first_deduction_period_end) {
            $startDate = \Carbon\Carbon::parse($this->first_deduction_period_start);
            $endDate = \Carbon\Carbon::parse($this->first_deduction_period_end);
            return $startDate->format('M d') . ' - ' . $endDate->format('d, Y');
        }

        // Fallback to first_deduction_date if available
        if ($this->first_deduction_date) {
            return \Carbon\Carbon::parse($this->first_deduction_date)->format('M d, Y');
        }

        return null;
    }

    /**
     * Get the deduction period display text (e.g., "Current Payroll Period", "Next Payroll Period")
     */
    public function getDeductionPeriodDisplayAttribute()
    {
        $periodNumber = $this->starting_payroll_period ?? 1;

        switch ($periodNumber) {
            case 1:
                return 'Current Payroll Period';
            case 2:
                return 'Next Payroll Period';
            case 3:
                return '3rd Payroll Period';
            case 4:
                return '3rd Next Payroll Period';
            default:
                return 'Payroll Period ' . $periodNumber;
        }
    }

    /**
     * Calculate cash advance deduction for a specific employee and payroll period
     */
    public static function calculateDeductionForPeriod($employeeId, $periodStart, $periodEnd)
    {
        // Find approved cash advances for this employee that should have deductions in this period
        $cashAdvances = self::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->where('outstanding_balance', '>', 0)
            ->get();

        $totalDeduction = 0;
        $deductionDetails = [];

        foreach ($cashAdvances as $cashAdvance) {
            // Check if this payroll period should have a deduction for this cash advance
            if (!self::shouldDeductInPeriod($cashAdvance, $periodStart, $periodEnd)) {
                continue;
            }

            // Calculate installment amount based on deduction frequency
            $installmentAmount = 0;

            if ($cashAdvance->deduction_frequency === 'monthly') {
                // For monthly deductions, use monthly installment amount
                $installmentAmount = $cashAdvance->total_amount / $cashAdvance->monthly_installments;
            } else {
                // For per-payroll deductions, use regular installment amount
                $installmentAmount = $cashAdvance->total_amount / $cashAdvance->installments;
            }

            // Don't exceed outstanding balance
            $deductionAmount = min($installmentAmount, $cashAdvance->outstanding_balance);

            if ($deductionAmount > 0) {
                $totalDeduction += $deductionAmount;
                $deductionDetails[] = [
                    'cash_advance_id' => $cashAdvance->id,
                    'reference_number' => $cashAdvance->reference_number,
                    'amount' => $deductionAmount,
                    'outstanding_balance' => $cashAdvance->outstanding_balance,
                ];
            }
        }

        return [
            'total' => $totalDeduction,
            'details' => $deductionDetails,
        ];
    }

    /**
     * Determine if a cash advance should have a deduction in the given payroll period
     */
    private static function shouldDeductInPeriod($cashAdvance, $periodStart, $periodEnd)
    {
        // Convert dates to Carbon instances for easier comparison
        $periodStart = \Carbon\Carbon::parse($periodStart);
        $periodEnd = \Carbon\Carbon::parse($periodEnd);
        $firstDeductionStart = \Carbon\Carbon::parse($cashAdvance->first_deduction_period_start);
        $firstDeductionEnd = \Carbon\Carbon::parse($cashAdvance->first_deduction_period_end);

        // Check if this payroll period matches the first deduction period exactly
        if ($periodStart->equalTo($firstDeductionStart) && $periodEnd->equalTo($firstDeductionEnd)) {
            return true;
        }

        // For subsequent periods, calculate based on deduction frequency
        if ($cashAdvance->deduction_frequency === 'monthly') {
            // For monthly deductions, check if this is the correct month and timing
            return self::shouldDeductMonthly($cashAdvance, $periodStart, $periodEnd, $firstDeductionStart, $firstDeductionEnd);
        } else {
            // For per-payroll deductions, check if this is the next payroll period in sequence
            return self::shouldDeductPerPayroll($cashAdvance, $periodStart, $periodEnd, $firstDeductionStart, $firstDeductionEnd);
        }
    }

    /**
     * Check if monthly deduction should occur in this period
     */
    private static function shouldDeductMonthly($cashAdvance, $periodStart, $periodEnd, $firstDeductionStart, $firstDeductionEnd)
    {
        // For monthly deductions, we need to check if:
        // 1. This period matches the timing (1st or 2nd cutoff based on monthly_deduction_timing)
        // 2. This is a month where deduction should occur (starting from first deduction month)

        $monthsBetween = $firstDeductionStart->diffInMonths($periodStart);

        // Check if we've already started deductions (period is same or after first deduction)
        if ($periodStart->lt($firstDeductionStart)) {
            return false;
        }

        // For semi-monthly employees with monthly deduction
        if ($cashAdvance->monthly_deduction_timing) {
            $is1stCutoff = $periodStart->day <= 15;
            $is2ndCutoff = $periodStart->day >= 16;

            if ($cashAdvance->monthly_deduction_timing === 'first_payroll' && !$is1stCutoff) {
                return false;
            }

            if ($cashAdvance->monthly_deduction_timing === 'last_payroll' && !$is2ndCutoff) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if per-payroll deduction should occur in this period
     */
    private static function shouldDeductPerPayroll($cashAdvance, $periodStart, $periodEnd, $firstDeductionStart, $firstDeductionEnd)
    {
        // For per-payroll deductions, deduct from every payroll period starting from the first deduction period
        return $periodStart->gte($firstDeductionStart);
    }
}
