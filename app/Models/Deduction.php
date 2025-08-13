<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deduction extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'deduction_setting_id',
        'cash_advance_id',
        'name',
        'type',
        'amount',
        'frequency',
        'semi_monthly_distribution',
        'start_date',
        'end_date',
        'installments',
        'remaining_installments',
        'balance',
        'is_active',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the employee that owns this deduction
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the deduction setting (if applicable)
     */
    public function deductionSetting()
    {
        return $this->belongsTo(DeductionSetting::class);
    }

    /**
     * Get the cash advance (if applicable)
     */
    public function cashAdvance()
    {
        return $this->belongsTo(CashAdvance::class);
    }

    /**
     * Get active deductions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    /**
     * Get deductions by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get government deductions
     */
    public function scopeGovernment($query)
    {
        return $query->whereIn('type', ['sss', 'philhealth', 'pagibig', 'withholding_tax']);
    }

    /**
     * Get loan deductions
     */
    public function scopeLoans($query)
    {
        return $query->whereIn('type', ['loan', 'cash_advance']);
    }

    /**
     * Check if deduction is government-mandated
     */
    public function isGovernmentDeduction()
    {
        return in_array($this->type, ['sss', 'philhealth', 'pagibig', 'withholding_tax']);
    }

    /**
     * Check if deduction is a loan
     */
    public function isLoan()
    {
        return in_array($this->type, ['loan', 'cash_advance']);
    }

    /**
     * Update remaining balance after payment
     */
    public function processPayment($amount)
    {
        if ($this->isLoan() && $this->balance > 0) {
            $this->balance -= $amount;

            if ($this->remaining_installments > 0) {
                $this->remaining_installments--;
            }

            // Deactivate if fully paid
            if ($this->balance <= 0 || $this->remaining_installments <= 0) {
                $this->balance = 0;
                $this->is_active = false;
                $this->end_date = now();
            }

            $this->save();
        }
    }
}
