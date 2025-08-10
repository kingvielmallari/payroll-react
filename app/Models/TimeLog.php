<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class TimeLog extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'employee_id',
        'log_date',
        'time_in',
        'time_out',
        'break_in',
        'break_out',
        'total_hours',
        'regular_hours',
        'overtime_hours',
        'late_hours',
        'undertime_hours',
        'log_type',
        'remarks',
        'is_holiday',
        'is_rest_day',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'log_date' => 'date',
        'time_in' => 'datetime:H:i:s',
        'time_out' => 'datetime:H:i:s',
        'break_in' => 'datetime:H:i:s',
        'break_out' => 'datetime:H:i:s',
        'total_hours' => 'decimal:2',
        'regular_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'late_hours' => 'decimal:2',
        'undertime_hours' => 'decimal:2',
        'is_holiday' => 'boolean',
        'is_rest_day' => 'boolean',
        'approved_at' => 'datetime',
    ];

    /**
     * Activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['time_in', 'time_out', 'total_hours', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the employee that owns the time log.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who approved the time log.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Calculate total hours worked.
     */
    public function calculateTotalHours()
    {
        if (!$this->time_in || !$this->time_out) {
            return 0;
        }

        $timeIn = \Carbon\Carbon::parse($this->time_in);
        $timeOut = \Carbon\Carbon::parse($this->time_out);
        
        $totalMinutes = $timeOut->diffInMinutes($timeIn);
        
        // Subtract break time if available
        if ($this->break_in && $this->break_out) {
            $breakIn = \Carbon\Carbon::parse($this->break_in);
            $breakOut = \Carbon\Carbon::parse($this->break_out);
            $breakMinutes = $breakOut->diffInMinutes($breakIn);
            $totalMinutes -= $breakMinutes;
        }

        return round($totalMinutes / 60, 2);
    }

    /**
     * Check if approved.
     */
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    /**
     * Scope to filter by employee.
     */
    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeByDateRange($query, $start, $end)
    {
        return $query->whereBetween('log_date', [$start, $end]);
    }

    /**
     * Scope to filter approved logs.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
