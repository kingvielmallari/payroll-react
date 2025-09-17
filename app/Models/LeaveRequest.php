<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $fillable = [
        'employee_id',
        'leave_type',
        'start_date',
        'end_date',
        'days_requested',
        'reason',
        'status',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'is_paid',
        'deduction_amount',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'is_paid' => 'boolean',
        'deduction_amount' => 'decimal:2',
    ];

    /**
     * Get the employee that owns the leave request.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who approved this leave request.
     */
    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the leave type label.
     */
    public function getLeaveTypeLabelAttribute()
    {
        $labels = [
            'sick' => 'Sick Leave',
            'vacation' => 'Vacation Leave',
            'emergency' => 'Emergency Leave',
            'maternity' => 'Maternity Leave',
            'paternity' => 'Paternity Leave',
            'bereavement' => 'Bereavement Leave',
            'special' => 'Special Leave',
        ];

        return $labels[$this->leave_type] ?? ucfirst($this->leave_type);
    }

    /**
     * Get the status badge color.
     */
    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            'cancelled' => 'gray',
        ];

        return $colors[$this->status] ?? 'gray';
    }
}
