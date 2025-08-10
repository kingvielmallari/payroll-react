<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PayrollSetting extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'payroll_frequency',
        'payroll_periods',
        'pay_delay_days',
        'adjust_for_weekends',
        'adjust_for_holidays',
        'weekend_adjustment',
        'notes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'payroll_periods' => 'array',
        'adjust_for_weekends' => 'boolean',
        'adjust_for_holidays' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['payroll_frequency', 'payroll_periods', 'pay_delay_days'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the user that created this setting.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user that last updated this setting.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the active payroll setting
     */
    public static function getActiveSetting()
    {
        return self::where('is_active', true)->first();
    }

    /**
     * Calculate payroll periods for a given month and year
     */
    public function calculatePayrollPeriods($year, $month)
    {
        $periods = [];
        
        switch ($this->payroll_frequency) {
            case 'semi_monthly':
                $periods = $this->calculateSemiMonthlyPeriods($year, $month);
                break;
            case 'monthly':
                $periods = $this->calculateMonthlyPeriods($year, $month);
                break;
            case 'weekly':
                $periods = $this->calculateWeeklyPeriods($year, $month);
                break;
            case 'bi_weekly':
                $periods = $this->calculateBiWeeklyPeriods($year, $month);
                break;
        }

        return $periods;
    }

    /**
     * Calculate semi-monthly periods (1st-15th, 16th-end)
     */
    private function calculateSemiMonthlyPeriods($year, $month)
    {
        $firstStart = Carbon::create($year, $month, 1);
        $firstEnd = Carbon::create($year, $month, 15);
        $secondStart = Carbon::create($year, $month, 16);
        $secondEnd = Carbon::create($year, $month)->endOfMonth();

        $firstPayDate = $this->adjustPayDate($firstEnd->copy()->addDays($this->pay_delay_days));
        $secondPayDate = $this->adjustPayDate($secondEnd->copy()->addDays($this->pay_delay_days));

        return [
            [
                'period_start' => $firstStart->format('Y-m-d'),
                'period_end' => $firstEnd->format('Y-m-d'),
                'pay_date' => $firstPayDate->format('Y-m-d'),
                'period_name' => $firstStart->format('M') . ' 1-15',
            ],
            [
                'period_start' => $secondStart->format('Y-m-d'),
                'period_end' => $secondEnd->format('Y-m-d'),
                'pay_date' => $secondPayDate->format('Y-m-d'),
                'period_name' => $secondStart->format('M') . ' 16-' . $secondEnd->day,
            ]
        ];
    }

    /**
     * Calculate monthly periods
     */
    private function calculateMonthlyPeriods($year, $month)
    {
        $start = Carbon::create($year, $month, 1);
        $end = Carbon::create($year, $month)->endOfMonth();
        $payDate = $this->adjustPayDate($end->copy()->addDays($this->pay_delay_days));

        return [
            [
                'period_start' => $start->format('Y-m-d'),
                'period_end' => $end->format('Y-m-d'),
                'pay_date' => $payDate->format('Y-m-d'),
                'period_name' => $start->format('M Y'),
            ]
        ];
    }

    /**
     * Calculate weekly periods
     */
    private function calculateWeeklyPeriods($year, $month)
    {
        $periods = [];
        $start = Carbon::create($year, $month, 1)->startOfWeek(Carbon::MONDAY);
        $monthEnd = Carbon::create($year, $month)->endOfMonth();

        $weekNumber = 1;
        while ($start->lte($monthEnd)) {
            $end = $start->copy()->endOfWeek(Carbon::SUNDAY);
            if ($end->gt($monthEnd)) {
                $end = $monthEnd->copy();
            }

            $payDate = $this->adjustPayDate($end->copy()->addDays($this->pay_delay_days));

            $periods[] = [
                'period_start' => $start->format('Y-m-d'),
                'period_end' => $end->format('Y-m-d'),
                'pay_date' => $payDate->format('Y-m-d'),
                'period_name' => $start->format('M') . ' Week ' . $weekNumber,
            ];

            $start = $start->copy()->addDays(7)->startOfWeek(Carbon::MONDAY);
            $weekNumber++;
        }

        return $periods;
    }

    /**
     * Calculate bi-weekly periods (every 2 weeks)
     */
    private function calculateBiWeeklyPeriods($year, $month)
    {
        $periods = [];
        $start = Carbon::create($year, $month, 1);
        $monthEnd = Carbon::create($year, $month)->endOfMonth();

        $periodNumber = 1;
        while ($start->lte($monthEnd)) {
            $end = $start->copy()->addDays(13); // 14-day period
            if ($end->gt($monthEnd)) {
                $end = $monthEnd->copy();
            }

            $payDate = $this->adjustPayDate($end->copy()->addDays($this->pay_delay_days));

            $periods[] = [
                'period_start' => $start->format('Y-m-d'),
                'period_end' => $end->format('Y-m-d'),
                'pay_date' => $payDate->format('Y-m-d'),
                'period_name' => $start->format('M') . ' Period ' . $periodNumber,
            ];

            $start = $start->copy()->addDays(14);
            $periodNumber++;
        }

        return $periods;
    }

    /**
     * Adjust pay date for weekends and holidays
     */
    private function adjustPayDate($date)
    {
        if ($this->adjust_for_weekends) {
            if ($date->isWeekend()) {
                if ($this->weekend_adjustment === 'before') {
                    // Move to Friday
                    while ($date->isWeekend()) {
                        $date = $date->subDays(1);
                    }
                } else {
                    // Move to Monday
                    while ($date->isWeekend()) {
                        $date = $date->addDays(1);
                    }
                }
            }
        }

        // TODO: Add holiday checking logic here if needed
        // This would require a holidays table or holiday API

        return $date;
    }

    /**
     * Get available payroll periods for current month
     */
    public static function getAvailablePeriodsForCurrentMonth()
    {
        $setting = self::getActiveSetting();
        if (!$setting) {
            return [];
        }

        $now = Carbon::now();
        return $setting->calculatePayrollPeriods($now->year, $now->month);
    }

    /**
     * Get next payroll period
     */
    public static function getNextPayrollPeriod()
    {
        $periods = self::getAvailablePeriodsForCurrentMonth();
        $today = Carbon::today();

        foreach ($periods as $period) {
            $periodEnd = Carbon::parse($period['period_end']);
            if ($periodEnd->gte($today)) {
                return $period;
            }
        }

        // If no current period, get first period of next month
        $nextMonth = Carbon::now()->addMonth();
        $setting = self::getActiveSetting();
        if ($setting) {
            $nextPeriods = $setting->calculatePayrollPeriods($nextMonth->year, $nextMonth->month);
            return $nextPeriods[0] ?? null;
        }

        return null;
    }

    /**
     * Get available payroll periods for the next few months
     */
    public function getAvailablePeriods($months = 3)
    {
        $periods = [];
        $currentDate = Carbon::now();
        
        for ($i = 0; $i < $months; $i++) {
            $targetDate = $currentDate->copy()->addMonths($i);
            $monthPeriods = $this->calculatePayrollPeriods($targetDate->year, $targetDate->month);
            
            foreach ($monthPeriods as $period) {
                $periods[] = [
                    'label' => $period['period_name'],
                    'start' => $period['period_start'],
                    'end' => $period['period_end'],
                    'pay_date' => $period['pay_date'],
                    'type' => $period['type'] ?? 'regular'
                ];
            }
        }
        
        return $periods;
    }
}
