<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PayrollScheduleSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'pay_type',
        'cutoff_description',
        'cutoff_start_day',
        'cutoff_end_day',
        'payday_offset_days',
        'payday_description',
        'notes',
        'cutoff_rules',
        'is_active',
        // New flexible fields
        'weekly_start_day',
        'weekly_end_day',
        'weekly_pay_day',
        'semi_monthly_config',
        'monthly_start_day',
        'monthly_end_day',
        'monthly_pay_day',
        'holiday_handling',
        'skip_weekends',
        'skip_holidays',
        'working_days',
        'special_rules',
    ];

    protected $casts = [
        'cutoff_rules' => 'array',
        'semi_monthly_config' => 'array',
        'working_days' => 'array',
        'is_active' => 'boolean',
        'skip_weekends' => 'boolean',
        'skip_holidays' => 'boolean',
        'monthly_start_day' => 'integer',
        'monthly_end_day' => 'integer',
        'monthly_pay_day' => 'integer',
    ];

    /**
     * Get the formatted pay type for display
     */
    public function getPayTypeDisplayAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->pay_type));
    }

    /**
     * Get working days array with defaults
     */
    public function getWorkingDaysAttribute($value)
    {
        if ($value) {
            return json_decode($value, true);
        }
        
        // Default working days (Monday to Friday)
        return ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
    }

    /**
     * Adjust date for weekends and holidays
     */
    public function adjustDateForHolidays(Carbon $date)
    {
        // Handle weekend adjustment
        if ($this->skip_weekends) {
            while ($date->isWeekend()) {
                if ($this->holiday_handling === 'before') {
                    $date->subDay();
                } else {
                    $date->addDay();
                }
            }
        }

        // TODO: Add holiday checking logic here
        // This would require a holidays table or service
        
        return $date;
    }

    /**
     * Get semi-monthly configuration with defaults
     */
    public function getSemiMonthlyConfigAttribute($value)
    {
        if ($value) {
            $config = json_decode($value, true);
            if ($config) return $config;
        }
        
        // Default semi-monthly configuration
        return [
            'first_period' => [
                'start_day' => 1,
                'end_day' => 15,
                'pay_day' => 20
            ],
            'second_period' => [
                'start_day' => 16,
                'end_day' => -1, // Last day of month
                'pay_day' => -1  // Last day of month
            ]
        ];
    }
}
