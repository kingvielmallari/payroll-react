<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PaidLeave extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'employee_id',
        'leave_setting_id',
        'reference_number',
        'leave_type',
        'start_date',
        'end_date',
        'total_days',
        'daily_rate',
        'total_amount',
        'status',
        'reason',
        'remarks',
        'requested_date',
        'approved_date',
        'leave_days',
        'is_paid',
        'supporting_document',
        'requested_by',
        'approved_by',
    ];

    protected $casts = [
        'daily_rate' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'requested_date' => 'date',
        'approved_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'leave_days' => 'array',
        'is_paid' => 'boolean',
    ];

    protected $attributes = [
        'status' => 'pending',
        'is_paid' => true,
        'leave_type' => 'sick_leave',
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

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function leaveSetting()
    {
        return $this->belongsTo(PaidLeaveSetting::class, 'leave_setting_id');
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

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    // Accessors
    public function getStatusBadgeAttribute()
    {
        return match ($this->status) {
            'pending' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>',
            'approved' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Approved</span>',
            'rejected' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Rejected</span>',
            'cancelled' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Cancelled</span>',
            default => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Unknown</span>',
        };
    }

    public function getLeaveTypeDisplayAttribute()
    {
        return match ($this->leave_type) {
            'sick_leave' => 'Sick Leave',
            'vacation_leave' => 'Vacation Leave',
            'emergency_leave' => 'Emergency Leave',
            'maternity_leave' => 'Maternity Leave',
            'paternity_leave' => 'Paternity Leave',
            'bereavement_leave' => 'Bereavement Leave',
            default => ucfirst(str_replace('_', ' ', $this->leave_type)),
        };
    }

    // Generate unique reference number
    public static function generateReferenceNumber()
    {
        $year = now()->year;
        $month = now()->format('m');
        $lastRecord = self::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastRecord ? (int) substr($lastRecord->reference_number, -4) + 1 : 1;

        return 'PL-' . $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    // Boot method to auto-generate reference number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($paidLeave) {
            if (empty($paidLeave->reference_number)) {
                $paidLeave->reference_number = self::generateReferenceNumber();
            }

            if (empty($paidLeave->requested_date)) {
                $paidLeave->requested_date = now()->toDateString();
            }

            if (empty($paidLeave->requested_by)) {
                $paidLeave->requested_by = Auth::id();
            }
        });
    }
}
