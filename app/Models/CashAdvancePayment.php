<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashAdvancePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'cash_advance_id',
        'payroll_id',
        'payroll_detail_id',
        'amount',
        'payment_amount',
        'remaining_balance',
        'payment_date',
        'payment_method',
        'reference_number',
        'recorded_by',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'payment_date' => 'date',
    ];

    // Relationships
    public function cashAdvance()
    {
        return $this->belongsTo(CashAdvance::class);
    }

    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    public function payrollDetail()
    {
        return $this->belongsTo(PayrollDetail::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
