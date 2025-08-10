<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayScheduleSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'cutoff_periods',
        'pay_day_offset',
        'pay_day_type',
        'pay_day_weekday',
        'move_if_holiday',
        'move_if_weekend',
        'move_direction',
        'is_active',
        'is_system_default',
    ];

    protected $casts = [
        'cutoff_periods' => 'array',
        'pay_day_offset' => 'integer',
        'pay_day_weekday' => 'integer',
        'move_if_holiday' => 'boolean',
        'move_if_weekend' => 'boolean',
        'is_active' => 'boolean',
        'is_system_default' => 'boolean',
    ];

    /**
     * Get employees using this pay schedule
     */
    public function employees()
    {
        return $this->hasMany(Employee::class, 'pay_schedule_setting_id');
    }

    /**
     * Get payrolls using this schedule
     */
    public function payrolls()
    {
        return $this->hasMany(Payroll::class, 'pay_schedule_setting_id');
    }

    /**
     * Scope to get only active schedules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only inactive schedules
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope to get system default schedules
     */
    public function scopeSystemDefault($query)
    {
        return $query->where('is_system_default', true);
    }

    /**
     * Scope to get system default schedules (plural alias)
     */
    public function scopeSystemDefaults($query)
    {
        return $query->where('is_system_default', true);
    }

    /**
     * Scope to get custom schedules
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system_default', false);
    }

    /**
     * Get formatted frequency name
     */
    public function getFormattedFrequencyAttribute()
    {
        return match($this->code) {
            'daily' => 'Daily',
            'weekly' => 'Weekly', 
            'bi_weekly' => 'Bi-Weekly',
            'semi_monthly' => 'Semi-Monthly',
            'monthly' => 'Monthly',
            default => ucfirst($this->name),
        };
    }

    /**
     * Check if this schedule can be deleted
     */
    public function canBeDeleted()
    {
        // Cannot delete system defaults
        if ($this->is_system_default) {
            return false;
        }
        
        // Cannot delete if it has employees or payrolls associated
        return $this->employees()->count() === 0 && $this->payrolls()->count() === 0;
    }

    /**
     * Check if this schedule is currently in use
     */
    public function isInUse()
    {
        return $this->employees()->count() > 0 || $this->payrolls()->count() > 0;
    }
}
