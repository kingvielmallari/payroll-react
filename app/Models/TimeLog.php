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
        'regular_overtime_hours',
        'night_diff_overtime_hours',
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
            ->where('type_name', 'NOT LIKE', '%night_differential%')  // Exclude night differential types
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

            // Calculate overtime pay with night differential breakdown if available
            $overtimeAmount = 0;

            // Check for dynamic calculation fields (used in draft payrolls)
            $regularOvertimeHours = $this->dynamic_regular_overtime_hours ?? $this->regular_overtime_hours ?? 0;
            $nightDiffOvertimeHours = $this->dynamic_night_diff_overtime_hours ?? $this->night_diff_overtime_hours ?? 0;

            if ($regularOvertimeHours > 0 || $nightDiffOvertimeHours > 0) {
                // Use breakdown calculation

                // Regular overtime pay
                if ($regularOvertimeHours > 0) {
                    $regularOvertimeAmount = $hourlyRate * $rateConfig->overtime_rate_multiplier * $regularOvertimeHours;
                    $overtimeAmount += $regularOvertimeAmount;
                }

                // Night differential overtime pay (overtime rate + night differential bonus)
                if ($nightDiffOvertimeHours > 0) {
                    // Get night differential setting
                    $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
                    $nightDiffMultiplier = $nightDiffSetting ? $nightDiffSetting->rate_multiplier : 1.10;

                    // Combined rate: base overtime rate + night differential bonus
                    // e.g., 1.25 (overtime) + 0.10 (night diff bonus) = 1.35
                    $combinedMultiplier = $rateConfig->overtime_rate_multiplier + ($nightDiffMultiplier - 1);
                    $nightDiffOvertimeAmount = $hourlyRate * $combinedMultiplier * $nightDiffOvertimeHours;
                    $overtimeAmount += $nightDiffOvertimeAmount;
                }
            } else {
                // Fallback to simple calculation if no breakdown available
                $overtimeAmount = $hourlyRate * $rateConfig->overtime_rate_multiplier * $this->overtime_hours;
            }
        }

        return [
            'regular_amount' => $regularAmount,
            'overtime_amount' => $overtimeAmount,
            'total_amount' => $regularAmount + $overtimeAmount,
        ];
    }

    /**
     * Get detailed time period breakdown for display
     */
    public function getTimePeriodBreakdown()
    {
        if (!$this->time_in || !$this->time_out) {
            return [];
        }

        $workStart = \Carbon\Carbon::parse($this->time_in);
        $workEnd = \Carbon\Carbon::parse($this->time_out);

        // Handle next day time out
        if ($workEnd->lt($workStart)) {
            $workEnd->addDay();
        }

        // Get employee schedule
        $employee = $this->employee;
        $timeSchedule = $employee ? $employee->timeSchedule : null;

        // Default to 8-hour schedule if no schedule is set
        $scheduledStart = $timeSchedule ?
            \Carbon\Carbon::parse($this->log_date . ' ' . $timeSchedule->start_time) :
            \Carbon\Carbon::parse($this->log_date . ' 08:00');
        $scheduledEnd = $timeSchedule ?
            \Carbon\Carbon::parse($this->log_date . ' ' . $timeSchedule->end_time) :
            \Carbon\Carbon::parse($this->log_date . ' 17:00');

        // Handle next day scheduled end time
        if ($scheduledEnd->lte($scheduledStart)) {
            $scheduledEnd->addDay();
        }

        // Get overtime threshold (default 8 hours)
        $overtimeThreshold = 8;
        if ($timeSchedule && $timeSchedule->break_start && $timeSchedule->break_end) {
            $scheduledHours = $scheduledStart->diffInHours($scheduledEnd);
            $breakHours = $timeSchedule->break_start->diffInHours($timeSchedule->break_end);
            $overtimeThreshold = $scheduledHours - $breakHours;
        }

        // Calculate overtime start time
        $overtimeStartTime = $workStart->copy()->addHours($overtimeThreshold);

        // Get night differential settings
        $nightDiffSetting = \App\Models\NightDifferentialSetting::current();

        $breakdown = [];

        // Regular work period
        $regularEndTime = min($workEnd, $overtimeStartTime);
        if ($workStart->lt($regularEndTime)) {
            $breakdown[] = [
                'type' => 'regular',
                'label' => 'Regular Hours',
                'start_time' => $workStart->format('g:i A'),
                'end_time' => $regularEndTime->format('g:i A'),
                'hours' => $this->dynamic_regular_hours ?? $this->regular_hours ?? 0,
                'color_class' => 'text-green-600'
            ];
        }

        // Overtime periods
        if ($overtimeStartTime->lt($workEnd)) {
            $regularOvertimeHours = $this->dynamic_regular_overtime_hours ?? $this->regular_overtime_hours ?? 0;
            $nightDiffOvertimeHours = $this->dynamic_night_diff_overtime_hours ?? $this->night_diff_overtime_hours ?? 0;

            // If we have breakdown data and night differential is enabled
            if (($regularOvertimeHours > 0 || $nightDiffOvertimeHours > 0) && $nightDiffSetting && $nightDiffSetting->is_active) {
                // Night differential time boundaries
                $nightStart = \Carbon\Carbon::parse($this->log_date . ' ' . $nightDiffSetting->start_time);
                $nightEnd = \Carbon\Carbon::parse($this->log_date . ' ' . $nightDiffSetting->end_time);

                // Handle next day end time
                if ($nightEnd->lte($nightStart)) {
                    $nightEnd->addDay();
                }

                // Regular overtime period (before night differential or after)
                if ($regularOvertimeHours > 0) {
                    // Calculate regular OT period
                    $regularOTStart = $overtimeStartTime;
                    $regularOTEnd = null;

                    if ($overtimeStartTime->lt($nightStart)) {
                        // Regular OT before night differential starts
                        $regularOTEnd = min($workEnd, $nightStart);
                    } else {
                        // Regular OT after night differential ends
                        $regularOTStart = max($overtimeStartTime, $nightEnd);
                        $regularOTEnd = $workEnd;
                    }

                    if ($regularOTStart->lt($regularOTEnd)) {
                        $breakdown[] = [
                            'type' => 'regular_overtime',
                            'label' => 'Regular Overtime',
                            'start_time' => $regularOTStart->format('g:i A'),
                            'end_time' => $regularOTEnd->format('g:i A'),
                            'hours' => $regularOvertimeHours,
                            'color_class' => 'text-orange-600'
                        ];
                    }
                }

                // Night differential overtime period
                if ($nightDiffOvertimeHours > 0) {
                    $nightOTStart = max($overtimeStartTime, $nightStart);
                    $nightOTEnd = min($workEnd, $nightEnd);

                    if ($nightOTStart->lt($nightOTEnd)) {
                        $breakdown[] = [
                            'type' => 'night_diff_overtime',
                            'label' => 'Overtime + Night Differential',
                            'start_time' => $nightOTStart->format('g:i A'),
                            'end_time' => $nightOTEnd->format('g:i A'),
                            'hours' => $nightDiffOvertimeHours,
                            'color_class' => 'text-purple-600',
                            'night_diff_rate' => $nightDiffSetting->rate_multiplier ?? 1.2
                        ];
                    }
                }
            } else {
                // Simple overtime display (no breakdown available or night diff not active)
                $totalOvertimeHours = $this->dynamic_overtime_hours ?? $this->overtime_hours ?? 0;

                // Try to get regular and night diff breakdown even if night diff not active
                $regularOvertimeHours = $this->dynamic_regular_overtime_hours ?? $this->regular_overtime_hours ?? 0;
                $nightDiffOvertimeHours = $this->dynamic_night_diff_overtime_hours ?? $this->night_diff_overtime_hours ?? 0;

                if ($regularOvertimeHours > 0) {
                    $breakdown[] = [
                        'type' => 'regular_overtime',
                        'label' => 'Regular Overtime',
                        'start_time' => $overtimeStartTime->format('g:i A'),
                        'end_time' => $overtimeStartTime->copy()->addHours($regularOvertimeHours)->format('g:i A'),
                        'hours' => $regularOvertimeHours,
                        'color_class' => 'text-orange-600'
                    ];
                }

                if ($nightDiffOvertimeHours > 0) {
                    $nightOTStart = $regularOvertimeHours > 0 ?
                        $overtimeStartTime->copy()->addHours($regularOvertimeHours) :
                        $overtimeStartTime;

                    $breakdown[] = [
                        'type' => 'night_diff_overtime',
                        'label' => 'Overtime + Night Differential',
                        'start_time' => $nightOTStart->format('g:i A'),
                        'end_time' => $nightOTStart->copy()->addHours($nightDiffOvertimeHours)->format('g:i A'),
                        'hours' => $nightDiffOvertimeHours,
                        'color_class' => 'text-purple-600',
                        'night_diff_rate' => $nightDiffSetting ? $nightDiffSetting->rate_multiplier : 1.2
                    ];
                }

                // If no breakdown at all, show total overtime
                if (empty($breakdown) && $totalOvertimeHours > 0) {
                    $breakdown[] = [
                        'type' => 'overtime',
                        'label' => 'Overtime',
                        'start_time' => $overtimeStartTime->format('g:i A'),
                        'end_time' => $workEnd->format('g:i A'),
                        'hours' => $totalOvertimeHours,
                        'color_class' => 'text-orange-600'
                    ];
                }
            }
        }

        return $breakdown;
    }
}
