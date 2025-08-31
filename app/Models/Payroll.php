<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Payroll extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'payroll_number',
        'payroll_period_id',
        'period_start',
        'period_end',
        'pay_date',
        'payroll_type',
        'pay_schedule',
        'status',
        'is_paid',
        'payment_proof_files',
        'payment_notes',
        'marked_paid_by',
        'marked_paid_at',
        'total_gross',
        'total_deductions',
        'total_net',
        'description',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'pay_date' => 'date',
        'is_paid' => 'boolean',
        'payment_proof_files' => 'array',
        'total_gross' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_net' => 'decimal:2',
        'approved_at' => 'datetime',
        'marked_paid_at' => 'datetime',
    ];

    /**
     * Activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['payroll_number', 'status', 'total_gross', 'total_net'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the payroll period for this payroll.
     */
    public function payrollPeriod()
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    /**
     * Get the user who created the payroll.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved the payroll.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who marked the payroll as paid.
     */
    public function markedPaidBy()
    {
        return $this->belongsTo(User::class, 'marked_paid_by');
    }

    /**
     * Get the payroll details for the payroll.
     */
    public function payrollDetails()
    {
        return $this->hasMany(PayrollDetail::class);
    }

    /**
     * Get the payroll snapshots for the payroll.
     */
    public function snapshots()
    {
        return $this->hasMany(PayrollSnapshot::class);
    }

    /**
     * Check if payroll is approved.
     */
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    /**
     * Check if payroll is paid.
     */
    public function isPaid()
    {
        return $this->is_paid;
    }

    /**
     * Check if payroll can be marked as paid.
     */
    public function canBeMarkedAsPaid()
    {
        return $this->status === 'approved' && !$this->is_paid;
    }

    /**
     * Check if payroll can be unmarked as paid.
     */
    public function canBeUnmarkedAsPaid()
    {
        return $this->status === 'approved' && $this->is_paid;
    }

    /**
     * Check if payroll can be edited.
     */
    public function canBeEdited()
    {
        return in_array($this->status, ['draft', 'processing']);
    }

    /**
     * Check if payroll can be deleted.
     */
    public function canBeDeleted()
    {
        return $this->status !== 'approved';
    }

    /**
     * Check if payroll uses dynamic calculations.
     */
    public function isDynamic()
    {
        return $this->status === 'draft';
    }

    /**
     * Check if payroll uses snapshot data.
     */
    public function usesSnapshot()
    {
        return in_array($this->status, ['processing', 'approved']);
    }

    /**
     * Generate unique payroll number.
     */
    public static function generatePayrollNumber($type = 'regular')
    {
        // Map the type to the proper prefix
        $prefix = match (strtolower($type)) {
            'semimonthly', 'semi_monthly' => 'SEMIMONTHLY',
            'monthly' => 'MONTHLY',
            'weekly' => 'WEEKLY',
            'biweekly', 'bi_weekly' => 'BIWEEKLY',
            default => strtoupper($type)
        };

        $year = date('Y');
        $month = date('m');

        // Get all existing payroll numbers for this type/period, sorted
        $existingPayrolls = static::where('payroll_number', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy('payroll_number', 'asc')
            ->pluck('payroll_number')
            ->toArray();

        if (empty($existingPayrolls)) {
            $newNumber = '001';
        } else {
            // Extract the sequence numbers and find the first gap
            $sequenceNumbers = [];
            foreach ($existingPayrolls as $payrollNumber) {
                // Split by '-' and get the last part (sequence number)
                $parts = explode('-', $payrollNumber);
                $sequenceNumbers[] = (int) end($parts);
            }

            // Find the first available number (start from 1)
            $newNumber = 1;
            while (in_array($newNumber, $sequenceNumbers)) {
                $newNumber++;
            }

            $newNumber = str_pad($newNumber, 3, '0', STR_PAD_LEFT);
        }

        return "{$prefix}-{$year}{$month}-{$newNumber}";
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeByDateRange($query, $start, $end)
    {
        return $query->whereBetween('period_start', [$start, $end]);
    }

    /**
     * Scope to filter by payroll type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('payroll_type', $type);
    }
}
