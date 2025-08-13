<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CashAdvance extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'employee_id',
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
        'requested_by',
        'approved_by',
    ];
        'reason',
        'remarks',
        'requested_date',
        'approved_date',
        'first_deduction_date',
        'deduction_period',
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

    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class);
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
        return $this->hasMany(Deduction::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['approved'])
            ->where('outstanding_balance', '>', 0);
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    // Accessors & Mutators
    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            'fully_paid' => 'bg-blue-100 text-blue-800',
            'cancelled' => 'bg-gray-100 text-gray-800',
        ];

        return $badges[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'approved' && $this->outstanding_balance > 0;
    }

    public function getTotalPaidAttribute()
    {
        // Calculate total paid from actual payments, not just approved_amount - outstanding_balance
        // because outstanding_balance might include interest while payments don't
        return $this->payments()->sum('payment_amount') ?? 0;
    }

    public function getPaymentProgressAttribute()
    {
        if ($this->approved_amount <= 0) return 0;
        return ($this->total_paid / $this->approved_amount) * 100;
    }

    // Methods
    public static function generateReferenceNumber()
    {
        $prefix = 'CA-' . date('Y') . '-';
        $lastRecord = static::where('reference_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        if ($lastRecord) {
            $lastNumber = intval(substr($lastRecord->reference_number, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function calculateInstallmentAmount()
    {
        if ($this->approved_amount && $this->installments > 0) {
            return round($this->approved_amount / $this->installments, 2);
        }
        return 0;
    }

    public function approve($approvedAmount = null, $installments = null, $approvedBy = null, $remarks = null, $interestRate = null)
    {
        $this->approved_amount = $approvedAmount ?? $this->requested_amount;
        $this->installments = $installments ?? $this->installments;

        // Update interest rate if provided
        if ($interestRate !== null) {
            $this->interest_rate = $interestRate;
        }

        // Calculate interest and total amounts
        $this->updateCalculations();

        // Set outstanding balance to total amount (including interest)
        $this->outstanding_balance = $this->total_amount;
        $this->status = 'approved';
        $this->approved_date = now();
        $this->approved_by = $approvedBy ?? \Illuminate\Support\Facades\Auth::id();

        if ($remarks) {
            $this->remarks = $remarks;
        }

        $this->save();

        // Create automatic deduction record
        $this->createAutomaticDeduction();

        return $this;
    }

    public function reject($remarks = null, $rejectedBy = null)
    {
        $this->status = 'rejected';
        $this->approved_by = $rejectedBy ?? \Illuminate\Support\Facades\Auth::id();

        if ($remarks) {
            $this->remarks = $remarks;
        }

        $this->save();

        return $this;
    }

    public function recordPayment($payrollId, $payrollDetailId, $paymentAmount, $notes = null)
    {
        $payment = CashAdvancePayment::create([
            'cash_advance_id' => $this->id,
            'payroll_id' => $payrollId,
            'payroll_detail_id' => $payrollDetailId,
            'payment_amount' => $paymentAmount,
            'remaining_balance' => $this->outstanding_balance - $paymentAmount,
            'payment_date' => now(),
            'notes' => $notes,
        ]);

        // Update outstanding balance
        $this->outstanding_balance -= $paymentAmount;

        // Check if fully paid
        if ($this->outstanding_balance <= 0) {
            $this->outstanding_balance = 0;
            $this->status = 'fully_paid';
        }

        $this->save();

        return $payment;
    }

    /**
     * Calculate interest amount based on principal and interest rate
     */
    public function calculateInterest($principal = null, $rate = null)
    {
        $principal = $principal ?? $this->approved_amount ?? $this->requested_amount;
        $rate = $rate ?? $this->interest_rate ?? 0;

        return ($principal * $rate) / 100;
    }

    /**
     * Calculate total amount (principal + interest)
     */
    public function calculateTotalAmount($principal = null, $rate = null)
    {
        $principal = $principal ?? $this->approved_amount ?? $this->requested_amount;
        $interestAmount = $this->calculateInterest($principal, $rate);

        return $principal + $interestAmount;
    }

    /**
     * Update interest and total calculations
     */
    public function updateCalculations()
    {
        if ($this->approved_amount && $this->interest_rate !== null) {
            $this->interest_amount = $this->calculateInterest();
            $this->total_amount = $this->calculateTotalAmount();

            // Update installment amount based on total amount
            if ($this->installments > 0) {
                $this->installment_amount = $this->total_amount / $this->installments;
            }

            // Update outstanding balance to total amount when first approved
            if ($this->status === 'approved' && $this->outstanding_balance == 0) {
                $this->outstanding_balance = $this->total_amount;
            }
        }
    }

    protected function createAutomaticDeduction()
    {
        // If deduction period is 'current', try to add to current payroll
        if ($this->deduction_period === 'current') {
            $this->addToCurrentPayroll();
        }

        // Create a deduction record for automatic payroll deduction
        \App\Models\Deduction::create([
            'employee_id' => $this->employee_id,
            'name' => 'Cash Advance - ' . $this->reference_number,
            'type' => 'cash_advance',
            'amount' => $this->installment_amount,
            'frequency' => 'per_payroll',
            'start_date' => $this->first_deduction_date ?? now(),
            'installments' => $this->installments,
            'remaining_installments' => $this->installments,
            'balance' => $this->total_amount,
            'is_active' => true,
            'description' => "Cash advance deduction for {$this->reference_number}",
            'cash_advance_id' => $this->id,
        ]);
    }

    /**
     * Add cash advance deduction to current active payroll
     */
    protected function addToCurrentPayroll()
    {
        // Find current active payroll for this employee
        $currentPayroll = \App\Models\Payroll::whereHas('payrollDetails', function ($query) {
            $query->where('employee_id', $this->employee_id);
        })
            ->where('status', 'in_progress')
            ->latest()
            ->first();

        if ($currentPayroll) {
            // Get the payroll detail for this employee
            $payrollDetail = $currentPayroll->payrollDetails()
                ->where('employee_id', $this->employee_id)
                ->first();

            if ($payrollDetail) {
                // Add cash advance deduction to the current payroll
                $currentDeduction = $payrollDetail->cash_advance_deductions ?? 0;
                $newDeduction = $currentDeduction + $this->installment_amount;

                $payrollDetail->update([
                    'cash_advance_deductions' => $newDeduction,
                    'total_deductions' => $payrollDetail->total_deductions + $this->installment_amount,
                    'net_pay' => $payrollDetail->gross_pay - ($payrollDetail->total_deductions + $this->installment_amount),
                ]);

                // Record the payment
                $this->recordPayment(
                    $currentPayroll->id,
                    $payrollDetail->id,
                    $this->installment_amount,
                    "Auto-deduction from payroll {$currentPayroll->payroll_number}"
                );

                // Update payroll totals
                $currentPayroll->update([
                    'total_deductions' => $currentPayroll->payrollDetails()->sum('total_deductions'),
                    'total_net' => $currentPayroll->payrollDetails()->sum('net_pay'),
                ]);
            }
        }
    }

    // Activity Log
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
