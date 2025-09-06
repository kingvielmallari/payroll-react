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
        'night_diff_regular_hours',
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
        'night_diff_regular_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'regular_overtime_hours' => 'decimal:2',
        'night_diff_overtime_hours' => 'decimal:2',
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
            // Fallback to regular workday rates - using per-minute calculation
            // Use dynamic values for draft payrolls, saved values for processed payrolls
            $regularHours = $this->dynamic_regular_hours ?? $this->regular_hours ?? 0;
            $overtimeHours = $this->dynamic_overtime_hours ?? $this->overtime_hours ?? 0;

            // Per-minute calculation for regular hours
            $regularAmount = 0;
            if ($regularHours > 0) {
                $regularAmount = $this->calculatePerMinuteAmount($hourlyRate, 1.0, $regularHours);
            }

            // Per-minute calculation for overtime hours
            $overtimeAmount = 0;
            if ($overtimeHours > 0) {
                $overtimeAmount = $this->calculatePerMinuteAmount($hourlyRate, 1.25, $overtimeHours);
            }
        } else {
            // Calculate regular pay with night differential breakdown using per-minute calculation
            $regularAmount = 0;

            // Check for dynamic calculation fields (used in draft payrolls)
            $regularHours = $this->dynamic_regular_hours ?? $this->regular_hours ?? 0;
            $nightDiffRegularHours = $this->dynamic_night_diff_regular_hours ?? $this->night_diff_regular_hours ?? 0;

            // Regular hours without night differential - per-minute calculation
            if ($regularHours > 0) {
                $regularAmount += $this->calculatePerMinuteAmount($hourlyRate, $rateConfig->regular_rate_multiplier, $regularHours);
            }

            // Regular hours WITH night differential - per-minute calculation
            if ($nightDiffRegularHours > 0) {
                // Get night differential setting
                $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
                $nightDiffMultiplier = $nightDiffSetting ? $nightDiffSetting->rate_multiplier : 1.10;

                // Combined rate: base regular rate + night differential bonus (SAME AS BREAKDOWN METHOD)
                $combinedMultiplier = $rateConfig->regular_rate_multiplier + ($nightDiffMultiplier - 1);
                $regularAmount += $this->calculatePerMinuteAmount($hourlyRate, $combinedMultiplier, $nightDiffRegularHours);
            }

            // Calculate overtime pay with night differential breakdown using per-minute calculation
            $overtimeAmount = 0;

            // Check for dynamic calculation fields (used in draft payrolls)
            $regularOvertimeHours = $this->dynamic_regular_overtime_hours ?? $this->regular_overtime_hours ?? 0;
            $nightDiffOvertimeHours = $this->dynamic_night_diff_overtime_hours ?? $this->night_diff_overtime_hours ?? 0;

            if ($regularOvertimeHours > 0 || $nightDiffOvertimeHours > 0) {
                // Regular overtime pay - per-minute calculation
                if ($regularOvertimeHours > 0) {
                    $overtimeAmount += $this->calculatePerMinuteAmount($hourlyRate, $rateConfig->overtime_rate_multiplier, $regularOvertimeHours);
                }

                // Night differential overtime pay - per-minute calculation
                if ($nightDiffOvertimeHours > 0) {
                    // Get night differential setting
                    $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
                    $nightDiffMultiplier = $nightDiffSetting ? $nightDiffSetting->rate_multiplier : 1.10;

                    // Combined rate: base overtime rate + night differential bonus
                    $combinedMultiplier = $rateConfig->overtime_rate_multiplier + ($nightDiffMultiplier - 1);
                    $overtimeAmount += $this->calculatePerMinuteAmount($hourlyRate, $combinedMultiplier, $nightDiffOvertimeHours);
                }
            } else {
                // Fallback to simple calculation if no breakdown available - per-minute calculation
                $overtimeHours = $this->overtime_hours ?? 0;
                if ($overtimeHours > 0) {
                    $overtimeAmount = $this->calculatePerMinuteAmount($hourlyRate, $rateConfig->overtime_rate_multiplier, $overtimeHours);
                }
            }
        }

        return [
            'regular_amount' => $regularAmount,
            'overtime_amount' => $overtimeAmount,
            'total_amount' => $regularAmount + $overtimeAmount,
        ];
    }

    /**
     * Calculate per-minute amount with proper precision
     * This ensures consistent calculation across all methods
     */
    public function calculatePerMinuteAmount($hourlyRate, $multiplier, $hours)
    {
        // Convert hours to minutes and round minutes (not hours)
        $actualMinutes = $hours * 60;
        $roundedMinutes = round($actualMinutes);

        // Calculate per-minute rate: emp_rate / 60
        $fullPerMinuteRate = $hourlyRate / 60;

        // TRUNCATE (not round) to exactly 4 decimals: 3.333333333 becomes 3.3333
        $truncatedPerMinuteRate = floor($fullPerMinuteRate * 10000) / 10000;

        // Apply multiplier to the truncated per-minute rate
        $finalPerMinuteRate = $truncatedPerMinuteRate * $multiplier;

        // Calculate amount: truncated per-minute rate × multiplier × total minutes
        $amount = $finalPerMinuteRate * $roundedMinutes;

        // Return amount with full precision (rounding will happen at display level)
        return $amount;
    }

    /**
     * Get detailed breakdown of time periods for DTR display
     * Returns array of time periods with start/end times and hours
     */
    public function getTimePeriodBreakdown($forceDynamicValues = null)
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

        // Get log date as string for consistent parsing
        $logDateOnly = \Carbon\Carbon::parse($this->log_date)->format('Y-m-d');

        // Default to 8-hour schedule if no schedule is set
        $scheduledStart = $timeSchedule ?
            \Carbon\Carbon::parse($logDateOnly . ' ' . $timeSchedule->start_time) :
            \Carbon\Carbon::parse($logDateOnly . ' 08:00');
        $scheduledEnd = $timeSchedule ?
            \Carbon\Carbon::parse($logDateOnly . ' ' . $timeSchedule->end_time) :
            \Carbon\Carbon::parse($logDateOnly . ' 17:00');

        // Handle next day scheduled end time
        if ($scheduledEnd->lte($scheduledStart)) {
            $scheduledEnd->addDay();
        }

        // Get overtime threshold (default 8 hours)
        $overtimeThreshold = 8;
        if ($timeSchedule) {
            if ($timeSchedule->break_start && $timeSchedule->break_end) {
                // Fixed break: calculate based on schedule minus break time
                $scheduledHours = $scheduledStart->diffInHours($scheduledEnd);
                $breakHours = $timeSchedule->break_start->diffInHours($timeSchedule->break_end);
                $overtimeThreshold = $scheduledHours - $breakHours;
            } elseif ($timeSchedule->break_duration_minutes && $timeSchedule->break_duration_minutes > 0) {
                // Flexible break: calculate based on schedule minus break duration
                $scheduledHours = $scheduledStart->diffInHours($scheduledEnd);
                $breakHours = $timeSchedule->break_duration_minutes / 60;
                $overtimeThreshold = $scheduledHours - $breakHours;
            }
        }

        // Calculate overtime start time
        $overtimeStartTime = $workStart->copy()->addHours($overtimeThreshold);

        // For flexible break, also add break duration to the overtime start time
        // (this matches the logic in TimeLogController)
        if ($timeSchedule && $timeSchedule->break_duration_minutes && $timeSchedule->break_duration_minutes > 0 && !($timeSchedule->break_start && $timeSchedule->break_end)) {
            $overtimeStartTime->addMinutes($timeSchedule->break_duration_minutes);
        }

        // Get night differential settings
        $nightDiffSetting = \App\Models\NightDifferentialSetting::current();

        $breakdown = [];

        // Use forced dynamic values if provided, otherwise check dynamic fields, then fallback to regular fields
        $regularHours = $forceDynamicValues['regular_hours'] ?? $this->dynamic_regular_hours ?? $this->regular_hours ?? 0;
        $regularOvertimeHours = $forceDynamicValues['regular_overtime_hours'] ?? $this->dynamic_regular_overtime_hours ?? $this->regular_overtime_hours ?? 0;
        $nightDiffOvertimeHours = $forceDynamicValues['night_diff_overtime_hours'] ?? $this->dynamic_night_diff_overtime_hours ?? $this->night_diff_overtime_hours ?? 0;
        $overtimeHours = $forceDynamicValues['overtime_hours'] ?? $this->dynamic_overtime_hours ?? $this->overtime_hours ?? 0;

        // Regular work period
        $regularEndTime = min($workEnd, $overtimeStartTime);
        if ($workStart->lt($regularEndTime)) {
            $breakdown[] = [
                'type' => 'regular',
                'label' => 'Regular Hours',
                'start_time' => $workStart->format('g:i A'),
                'end_time' => $regularEndTime->format('g:i A'),
                'hours' => $regularHours,
                'color_class' => 'text-green-600'
            ];
        }

        // Overtime periods
        if ($overtimeStartTime->lt($workEnd)) {
            // If we have breakdown data and night differential is enabled
            if (($regularOvertimeHours > 0 || $nightDiffOvertimeHours > 0) && $nightDiffSetting && $nightDiffSetting->is_active) {
                // Night differential time boundaries
                $logDateOnly = \Carbon\Carbon::parse($this->log_date)->format('Y-m-d');
                $nightStart = \Carbon\Carbon::parse($logDateOnly . ' ' . $nightDiffSetting->start_time);
                $nightEnd = \Carbon\Carbon::parse($logDateOnly . ' ' . $nightDiffSetting->end_time);

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
                        // Use workEnd if it's earlier than nightStart to avoid extending OT periods
                        $regularOTEnd = $workEnd->lt($nightStart) ? $workEnd : $nightStart;
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
                $totalOvertimeHours = $overtimeHours;

                // Try to get regular and night diff breakdown even if night diff not active
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
