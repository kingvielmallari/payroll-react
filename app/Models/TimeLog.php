<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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
        'log_type', // Now dynamic based on PayrollRateConfiguration
        'creation_method',
        'remarks',
        'is_holiday',
        'is_rest_day',
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
    ];

    /**
     * Activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['time_in', 'time_out', 'total_hours', 'creation_method'])
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
     * Check if created manually.
     */
    public function isManual()
    {
        return $this->creation_method === 'manual';
    }

    /**
     * Check if imported from file.
     */
    public function isImported()
    {
        return $this->creation_method === 'imported';
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
     * Scope to filter by creation method.
     */
    public function scopeByCreationMethod($query, $method)
    {
        return $query->where('creation_method', $method);
    }

    /**
     * Scope to filter manual entries.
     */
    public function scopeManual($query)
    {
        return $query->where('creation_method', 'manual');
    }

    /**
     * Scope to filter imported entries.
     */
    public function scopeImported($query)
    {
        return $query->where('creation_method', 'imported');
    }

    /**
     * Get available log types from PayrollRateConfiguration
     */
    public static function getAvailableLogTypes()
    {
        $configurations = \App\Models\PayrollRateConfiguration::active()
            ->orderBy('display_name')
            ->get();

        $logTypes = [];
        foreach ($configurations as $config) {
            // Use type_name as both key and value since that's what the database expects
            $logTypes[$config->type_name] = $config->display_name;
        }

        return $logTypes;
    }

    /**
     * Get rate configuration for this log type
     */
    public function getRateConfiguration()
    {
        return \App\Models\PayrollRateConfiguration::where('type_name', $this->log_type)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Calculate pay amount based on rate configuration
     */
    public function calculatePayAmount($hourlyRate)
    {
        $rateConfig = $this->getRateConfiguration();

        if (!$rateConfig) {
            // Fallback to regular workday rates
            $regularAmount = $hourlyRate * $this->regular_hours;
            $overtimeAmount = $hourlyRate * 1.25 * $this->overtime_hours;
        } else {
            $regularAmount = $hourlyRate * $rateConfig->regular_rate_multiplier * $this->regular_hours;
            $overtimeAmount = $hourlyRate * $rateConfig->overtime_rate_multiplier * $this->overtime_hours;
        }

        return [
            'regular_amount' => $regularAmount,
            'overtime_amount' => $overtimeAmount,
            'total_amount' => $regularAmount + $overtimeAmount,
        ];
    }
}
