<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DTRRecord extends Model
{
    use HasFactory;

    protected $table = 'd_t_r_s';

    protected $fillable = [
        'employee_id',
        'period_type',
        'period_start',
        'period_end',
        'month_year',
        'regular_days',
        'saturday_count',
        'dtr_data',
        'total_regular_hours',
        'total_overtime_hours',
        'total_late_hours',
        'total_undertime_hours',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'remarks'
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'dtr_data' => 'array',
        'total_regular_hours' => 'decimal:2',
        'total_overtime_hours' => 'decimal:2',
        'total_late_hours' => 'decimal:2',
        'total_undertime_hours' => 'decimal:2',
        'approved_at' => 'datetime'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeForPeriod($query, $startDate, $endDate)
    {
        return $query->where('period_start', $startDate)->where('period_end', $endDate);
    }

    public function scopeByPeriodType($query, $type)
    {
        return $query->where('period_type', $type);
    }
}
