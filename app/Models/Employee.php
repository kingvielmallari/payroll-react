<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Employee extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id',
        'department_id',
        'position_id',
        'employee_number',
        'first_name',
        'middle_name',
        'last_name',
        'suffix',
        'birth_date',
        'gender',
        'civil_status',
        'phone',
        'address',
        'hire_date',
        'paid_leaves',
        'benefits_status',
        'employment_type',
        'employment_status',
        'pay_schedule',
        'time_schedule_id',
        'day_schedule_id',
        'basic_salary',
        'hourly_rate',
        'daily_rate',
        'weekly_rate',
        'semi_monthly_rate',
        'sss_number',
        'philhealth_number',
        'pagibig_number',
        'tin_number',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_phone',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'rate_type',
        'fixed_rate',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'hire_date' => 'date',
        'paid_leaves' => 'integer',
        'basic_salary' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'daily_rate' => 'decimal:2',
        'weekly_rate' => 'decimal:2',
        'semi_monthly_rate' => 'decimal:2',
        'fixed_rate' => 'decimal:2',
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'employee_number';
    }

    /**
     * Get the value of the model's route key.
     */
    public function getRouteKey()
    {
        return strtolower($this->getAttribute($this->getRouteKeyName()));
    }

    /**
     * Retrieve the model for a bound value.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), strtoupper($value))->first();
    }

    /**
     * Activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['first_name', 'last_name', 'employment_status', 'basic_salary'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the user associated with the employee.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the department that the employee belongs to.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the position of the employee.
     */
    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Get the time schedule of the employee.
     */
    public function timeSchedule()
    {
        return $this->belongsTo(TimeSchedule::class);
    }

    /**
     * Get the day schedule of the employee.
     */
    public function daySchedule()
    {
        return $this->belongsTo(DaySchedule::class);
    }

    /**
     * Get the complete schedule display for the employee.
     */
    public function getScheduleDisplayAttribute()
    {
        $daySchedule = $this->daySchedule;
        $timeSchedule = $this->timeSchedule;

        if (!$daySchedule || !$timeSchedule) {
            return 'No schedule assigned';
        }

        return $daySchedule->days_display . ' | ' . $timeSchedule->time_range_display;
    }

    /**
     * Get the time logs for the employee.
     */
    public function timeLogs()
    {
        return $this->hasMany(TimeLog::class);
    }

    /**
     * Get the DTR records for the employee.
     */
    public function dtrRecords()
    {
        return $this->hasMany(\App\Models\DTRRecord::class);
    }

    /**
     * Get the payroll details for the employee.
     */
    public function payrollDetails()
    {
        return $this->hasMany(PayrollDetail::class);
    }

    /**
     * Get the deductions for the employee.
     */
    public function deductions()
    {
        return $this->hasMany(Deduction::class);
    }

    /**
     * Get the leave requests for the employee.
     */
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Get the work schedules for the employee.
     */
    public function workSchedules()
    {
        return $this->belongsToMany(WorkSchedule::class, 'employee_work_schedules')
            ->withPivot('effective_date', 'end_date', 'is_active')
            ->withTimestamps();
    }

    /**
     * Get the current work schedule for the employee.
     */
    public function currentWorkSchedule()
    {
        return $this->workSchedules()
            ->wherePivot('is_active', true)
            ->wherePivot('effective_date', '<=', now())
            ->where(function ($query) {
                $query->wherePivotNull('end_date')
                    ->orWherePivot('end_date', '>=', now());
            })
            ->latest('pivot_effective_date')
            ->first();
    }

    /**
     * Get the employee's full name.
     */
    public function getFullNameAttribute()
    {
        $name = trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
        return $this->suffix ? "{$name} {$this->suffix}" : $name;
    }

    /**
     * Get the employee's display name.
     */
    public function getDisplayNameAttribute()
    {
        return "{$this->last_name}, {$this->first_name}";
    }

    /**
     * Check if employee is active.
     */
    public function isActive()
    {
        return $this->employment_status === 'active';
    }

    /**
     * Check if employee is regular.
     */
    public function isRegular()
    {
        return $this->employment_type === 'regular';
    }

    /**
     * Calculate daily rate from basic salary.
     */
    public function calculateDailyRate()
    {
        if ($this->daily_rate) {
            return $this->daily_rate;
        }

        // Calculate based on 22 working days per month
        return $this->basic_salary / 22;
    }

    /**
     * Calculate hourly rate from basic salary.
     */
    public function calculateHourlyRate()
    {
        if ($this->hourly_rate) {
            return $this->hourly_rate;
        }

        // Calculate based on 8 hours per day, 22 working days per month
        return $this->basic_salary / (22 * 8);
    }

    /**
     * Scope to filter active employees.
     */
    public function scopeActive($query)
    {
        return $query->where('employment_status', 'active');
    }

    /**
     * Scope to filter by department.
     */
    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope to filter by employment type.
     */
    public function scopeByEmploymentType($query, $type)
    {
        return $query->where('employment_type', $type);
    }

    /**
     * Get time logs for current month.
     */
    public function thisMonthTimeLogs()
    {
        return $this->timeLogs()->whereMonth('log_date', now()->month)->whereYear('log_date', now()->year);
    }

    /**
     * Get employee's age.
     */
    public function getAgeAttribute()
    {
        return $this->birth_date ? $this->birth_date->age : null;
    }

    /**
     * Get years of service.
     */
    public function getYearsOfServiceAttribute()
    {
        if (!$this->hire_date) {
            return '0 Days';
        }

        $hireDate = $this->hire_date;
        $currentDate = now();

        // Calculate total years (cast to integer)
        $years = (int) $hireDate->diffInYears($currentDate);

        // Calculate remaining months after years (cast to integer)
        $afterYears = $hireDate->copy()->addYears($years);
        $months = (int) $afterYears->diffInMonths($currentDate);

        // Calculate remaining days after years and months (cast to integer)  
        $afterMonths = $afterYears->copy()->addMonths($months);
        $days = (int) $afterMonths->diffInDays($currentDate);

        if ($years >= 1) {
            // 1 year or more: "X years, Y months"
            if ($months == 0) {
                return $years . ' Year' . ($years != 1 ? 's' : '');
            } else {
                return $years . ' Year' . ($years != 1 ? 's' : '') . ', ' . $months . ' Month' . ($months != 1 ? 's' : '');
            }
        } elseif ($months >= 1) {
            // Less than 1 year: "X months, Y days"
            if ($days == 0) {
                return $months . ' Month' . ($months != 1 ? 's' : '');
            } else {
                return $months . ' Month' . ($months != 1 ? 's' : '') . ', ' . $days . ' Day' . ($days != 1 ? 's' : '');
            }
        } else {
            // Less than 1 month: "X days"
            return $days . ' Day' . ($days != 1 ? 's' : '');
        }
    }

    /**
     * Get activities for this employee.
     */
    public function activities()
    {
        return $this->morphMany(\Spatie\Activitylog\Models\Activity::class, 'subject');
    }

    /**
     * Get working days per week based on day schedule
     */
    public function getWorkingDaysPerWeek()
    {
        return match ($this->day_schedule) {
            'monday_friday' => 5,
            'monday_saturday', 'tuesday_saturday' => 6,
            'monday_sunday' => 7,
            'sunday_thursday' => 5,
            'custom' => 5, // Default for custom, should be configured elsewhere
            default => 5
        };
    }

    /**
     * Get human-readable day schedule
     */
    public function getDayScheduleDisplayAttribute()
    {
        return match ($this->day_schedule) {
            'monday_friday' => 'Monday - Friday (5 days)',
            'monday_saturday' => 'Monday - Saturday (6 days)',
            'monday_sunday' => 'Monday - Sunday (7 days)',
            'tuesday_saturday' => 'Tuesday - Saturday (6 days)',
            'sunday_thursday' => 'Sunday - Thursday (5 days)',
            'custom' => 'Custom Schedule',
            default => 'Monday - Friday (5 days)'
        };
    }

    /**
     * Get working days for a specific month
     */
    public function getWorkingDaysForMonth($year, $month)
    {
        $startDate = \Carbon\Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $workingDays = 0;

        $current = $startDate->copy();
        while ($current <= $endDate) {
            if ($this->isWorkingDay($current)) {
                $workingDays++;
            }
            $current->addDay();
        }

        return $workingDays;
    }

    /**
     * Calculate Basic Pay for a specific payroll period based on rate type
     * 
     * @param \Carbon\Carbon $periodStart
     * @param \Carbon\Carbon $periodEnd
     * @return float
     */
    public function calculateBasicPayForPeriod(\Carbon\Carbon $periodStart, \Carbon\Carbon $periodEnd)
    {
        // If no fixed_rate or rate_type, return 0
        if (!$this->fixed_rate || !$this->rate_type) {
            return 0;
        }

        // Count working days in payroll period based on employee's day schedule
        $workingDaysInPeriod = 0;
        $currentDate = $periodStart->copy();

        while ($currentDate->lte($periodEnd)) {
            // Use employee's isWorkingDay method to respect their day schedule
            if ($this->isWorkingDay($currentDate)) {
                $workingDaysInPeriod++;
            }
            $currentDate->addDay();
        }

        // Get assigned total hours from employee's time schedule
        $assignedTotalHours = 8.0; // Default fallback
        if ($this->time_schedule_id && $this->timeSchedule && $this->timeSchedule->total_hours) {
            $assignedTotalHours = $this->timeSchedule->total_hours;
        }

        // Calculate based on rate type using the formulas
        switch ($this->rate_type) {
            case 'hourly':
                // hourly rate * assigned total hours * total workdays on payroll period
                return $this->fixed_rate * $assignedTotalHours * $workingDaysInPeriod;

            case 'daily':
                // daily rate * total workdays on payroll period
                return $this->fixed_rate * $workingDaysInPeriod;

            case 'weekly':
                // Calculate total working days in a standard week for this employee
                $sampleWeekStart = $periodStart->copy()->startOfWeek(); // Monday
                $sampleWeekEnd = $sampleWeekStart->copy()->addDays(6); // Sunday
                $totalWorkdaysInWeeklyPeriod = 0;
                $sampleDate = $sampleWeekStart->copy();

                while ($sampleDate->lte($sampleWeekEnd)) {
                    if ($this->isWorkingDay($sampleDate)) {
                        $totalWorkdaysInWeeklyPeriod++;
                    }
                    $sampleDate->addDay();
                }

                // weekly rate / total workdays in a period * total workdays in a payroll period
                return $totalWorkdaysInWeeklyPeriod > 0 ?
                    ($this->fixed_rate / $totalWorkdaysInWeeklyPeriod) * $workingDaysInPeriod : 0;

            case 'semi_monthly':
            case 'semi-monthly':
                // Get total working days in a semi-monthly period
                // Calculate working days in a typical semi-monthly period (first 15 days)
                $sampleSemiStart = $periodStart->copy()->startOfMonth();
                $sampleSemiEnd = $sampleSemiStart->copy()->addDays(14); // First 15 days
                $totalWorkdaysInSemiPeriod = 0;
                $sampleDate = $sampleSemiStart->copy();

                while ($sampleDate->lte($sampleSemiEnd)) {
                    // Use employee's day schedule to determine working days
                    if ($this->isWorkingDay($sampleDate)) {
                        $totalWorkdaysInSemiPeriod++;
                    }
                    $sampleDate->addDay();
                }

                // fixed semi rate / total workdays in a period * total workdays in a payroll period
                return $totalWorkdaysInSemiPeriod > 0 ?
                    ($this->fixed_rate / $totalWorkdaysInSemiPeriod) * $workingDaysInPeriod : 0;

            case 'monthly':
                // Get total working days in the month (based on employee's day schedule)
                $monthStart = $periodStart->copy()->startOfMonth();
                $monthEnd = $periodStart->copy()->endOfMonth();
                $totalWorkdaysInMonth = 0;
                $monthDate = $monthStart->copy();

                while ($monthDate->lte($monthEnd)) {
                    // Use employee's day schedule to determine working days
                    if ($this->isWorkingDay($monthDate)) {
                        $totalWorkdaysInMonth++;
                    }
                    $monthDate->addDay();
                }

                // fixed monthly / total workdays in a period * total workdays in a period
                return $totalWorkdaysInMonth > 0 ?
                    ($this->fixed_rate / $totalWorkdaysInMonth) * $workingDaysInPeriod : 0;

            default:
                return 0;
        }
    }

    /**
     * Get working days for a specific period (always Monday-Friday for payroll calculations)
     */
    public function getStandardWorkingDaysForPeriod(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate)
    {
        $workingDays = 0;
        $current = $startDate->copy();

        while ($current <= $endDate) {
            // Always use Monday-Friday for standard payroll calculations
            if ($current->dayOfWeek >= 1 && $current->dayOfWeek <= 5) {
                $workingDays++;
            }
            $current->addDay();
        }

        return $workingDays;
    }

    /**
     * Get working days for a specific period
     */
    public function getWorkingDaysForPeriod(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate)
    {
        $workingDays = 0;
        $current = $startDate->copy();

        while ($current <= $endDate) {
            if ($this->isWorkingDay($current)) {
                $workingDays++;
            }
            $current->addDay();
        }

        return $workingDays;
    }

    /**
     * Check if a given date is a working day for this employee
     */
    public function isWorkingDay(\Carbon\Carbon $date)
    {
        // First check if employee has a daySchedule relationship
        if ($this->daySchedule) {
            return $this->daySchedule->isWorkingDay($date);
        }

        // Fallback to day_schedule column
        $dayOfWeek = $date->dayOfWeek; // 0=Sunday, 1=Monday, ..., 6=Saturday

        return match ($this->day_schedule) {
            'monday_friday' => $dayOfWeek >= 1 && $dayOfWeek <= 5, // Mon-Fri
            'monday_saturday' => $dayOfWeek >= 1 && $dayOfWeek <= 6, // Mon-Sat
            'monday_sunday' => true, // All days
            'tuesday_saturday' => $dayOfWeek >= 2 && $dayOfWeek <= 6, // Tue-Sat (rest on Sun-Mon)
            'sunday_thursday' => $dayOfWeek == 0 || ($dayOfWeek >= 1 && $dayOfWeek <= 4), // Sun-Thu
            'custom' => true, // Should be implemented based on custom logic
            default => $dayOfWeek >= 1 && $dayOfWeek <= 5 // Default to Mon-Fri
        };
    }

    /**
     * Get expected working hours per day based on current work schedule
     */
    public function getExpectedHoursPerDay()
    {
        $currentSchedule = $this->currentWorkSchedule();
        if (!$currentSchedule) {
            return 8; // Default 8 hours
        }

        // Calculate hours from work schedule
        $startTime = \Carbon\Carbon::parse($currentSchedule->start_time);
        $endTime = \Carbon\Carbon::parse($currentSchedule->end_time);
        $breakHours = $currentSchedule->break_hours ?? 1; // Default 1 hour break

        return $endTime->diffInHours($startTime) - $breakHours;
    }

    /**
     * Calculate expected working hours for a given period
     */
    public function getExpectedHoursForPeriod(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate)
    {
        $totalHours = 0;
        $current = $startDate->copy();
        $hoursPerDay = $this->getExpectedHoursPerDay();

        while ($current <= $endDate) {
            if ($this->isWorkingDay($current)) {
                $totalHours += $hoursPerDay;
            }
            $current->addDay();
        }

        return $totalHours;
    }

    /**
     * Calculate Monthly Basic Salary (MBS) dynamically based on fixed_rate and rate_type
     * 
     * @param \Carbon\Carbon $periodStart - For daily/hourly calculations that need working days/hours
     * @param \Carbon\Carbon $periodEnd - For daily/hourly calculations that need working days/hours
     * @return float
     */
    public function calculateMonthlyBasicSalary(\Carbon\Carbon $periodStart = null, \Carbon\Carbon $periodEnd = null)
    {
        // If no fixed_rate or rate_type, return 0 as requested
        if (!$this->fixed_rate || !$this->rate_type) {
            return 0;
        }

        $fixedRate = $this->fixed_rate;

        switch ($this->rate_type) {
            case 'monthly':
                // If fixed monthly rate = MBS/basic salary same fixed monthly rate amount
                return $fixedRate;

            case 'semi_monthly':
            case 'semi-monthly':
                // If fixed semi monthly rate = MBS/basic salary is fixed semi-monthly rate amount * 2
                return $fixedRate * 2;

            case 'weekly':
                // If fixed weekly = MBS/basic salary is weekly rate / emp days per week * total workdays in current payroll month
                if ($periodStart && $periodEnd) {
                    // Get the employee's days per week from their day schedule
                    $daysPerWeek = $this->getDaysPerWeek();

                    // Calculate actual working days in the current payroll month
                    $workingDaysInMonth = $this->getWorkingDaysForPeriod($periodStart, $periodEnd);

                    // Calculate: weekly rate / emp days per week * total workdays in current payroll month
                    return ($fixedRate / $daysPerWeek) * $workingDaysInMonth;
                } else {
                    // Fallback: Use the old calculation if no period provided
                    return ($fixedRate * 52) / 12;
                }

            case 'daily':
                // If fixed daily rate = MBS/basic salary is fixed daily rate amount * emp total work days in a FULL MONTH
                // MBS should always be calculated for a full month, not just the payroll period

                if ($periodStart && $periodEnd) {
                    // Calculate working days for the full month (not just the payroll period)
                    // Get the start and end of the month that contains the period
                    $monthStart = $periodStart->copy()->startOfMonth();
                    $monthEnd = $periodStart->copy()->endOfMonth();
                    $workingDaysInMonth = $this->getWorkingDaysForPeriod($monthStart, $monthEnd);
                    return $fixedRate * $workingDaysInMonth;
                } else {
                    // Fallback: Use average 22 working days per month
                    return $fixedRate * 22;
                }

            case 'hourly':
                // If fixed hourly rate = MBS/basic salary is fixed hourly rate amount * emp total hours * emp total work days in a FULL MONTH
                // MBS should always be calculated for a full month, not just the payroll period

                if ($periodStart && $periodEnd) {
                    // Get expected hours per day from time schedule's total_hours field (dynamically calculated)
                    $hoursPerDay = 8; // Default fallback
                    if ($this->timeSchedule && $this->timeSchedule->total_hours) {
                        // Use the total_hours field from time_schedules table - this is the correct total hours per day
                        $hoursPerDay = $this->timeSchedule->total_hours;
                    }

                    // Calculate working days for the full month (not just the payroll period)
                    // Get the start and end of the month that contains the period
                    $monthStart = $periodStart->copy()->startOfMonth();
                    $monthEnd = $periodStart->copy()->endOfMonth();
                    $workingDaysInMonth = $this->getWorkingDaysForPeriod($monthStart, $monthEnd);

                    return $fixedRate * $hoursPerDay * $workingDaysInMonth;
                } else {
                    // Fallback: Use hours from time schedule or default 8 hours * 22 working days per month
                    $fallbackHours = 8; // Default
                    if ($this->timeSchedule && $this->timeSchedule->total_hours) {
                        $fallbackHours = $this->timeSchedule->total_hours;
                    }
                    return $fixedRate * $fallbackHours * 22;
                }

            default:
                // Fallback to basic_salary if rate_type is not recognized
                return $this->basic_salary ?? 0;
        }
    }

    /**
     * Get Monthly Basic Salary for display (alias method)
     * 
     * @param \Carbon\Carbon $periodStart
     * @param \Carbon\Carbon $periodEnd
     * @return float
     */
    public function getMonthlyBasicSalary(\Carbon\Carbon $periodStart = null, \Carbon\Carbon $periodEnd = null)
    {
        return $this->calculateMonthlyBasicSalary($periodStart, $periodEnd);
    }

    /**
     * Get the number of working days per week for this employee based on their day schedule
     * 
     * @return int
     */
    public function getDaysPerWeek()
    {
        if ($this->daySchedule) {
            // Count the enabled days in the day schedule
            $enabledDays = 0;
            $dayFields = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

            foreach ($dayFields as $day) {
                if ($this->daySchedule->$day) {
                    $enabledDays++;
                }
            }

            return $enabledDays;
        }

        // Default fallback: 5 days per week (Monday to Friday)
        return 5;
    }
}
