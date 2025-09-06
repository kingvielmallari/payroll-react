<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'time_in',
        'time_out',
        'break_start',
        'break_end',
        'break_duration_minutes',
        'total_hours',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'time_in' => 'datetime:H:i',
        'time_out' => 'datetime:H:i',
        'break_start' => 'datetime:H:i',
        'break_end' => 'datetime:H:i',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTimeRangeAttribute()
    {
        return $this->time_in->format('H:i') . ' - ' . $this->time_out->format('H:i');
    }

    public function getTimeRangeDisplayAttribute()
    {
        return $this->time_in->format('g:iA') . ' - ' . $this->time_out->format('g:iA');
    }

    public function getWorkingHoursAttribute()
    {
        $timeIn = $this->time_in;
        $timeOut = $this->time_out;
        $breakStart = $this->break_start;
        $breakEnd = $this->break_end;

        // Calculate total working hours
        $totalMinutes = $timeOut->diffInMinutes($timeIn);

        // Subtract break time if applicable
        if ($breakStart && $breakEnd) {
            $breakMinutes = $breakEnd->diffInMinutes($breakStart);
            $totalMinutes -= $breakMinutes;
        }

        return round($totalMinutes / 60, 2);
    }

    /**
     * Calculate and return total working hours based on break type
     */
    public function calculateTotalHours()
    {
        if (!$this->time_in || !$this->time_out) {
            return 0;
        }

        // Handle both datetime and time formats
        if ($this->time_in instanceof \Carbon\Carbon) {
            $timeIn = $this->time_in->copy();
            $timeOut = $this->time_out->copy();
        } else {
            $timeIn = \Carbon\Carbon::createFromFormat('H:i:s', $this->time_in);
            $timeOut = \Carbon\Carbon::createFromFormat('H:i:s', $this->time_out);
        }

        // Handle overnight schedules
        if ($timeOut->lessThan($timeIn)) {
            $timeOut->addDay();
        }

        // Calculate total minutes
        $totalMinutes = $timeIn->diffInMinutes($timeOut);

        // Handle different break types
        if ($this->break_duration_minutes > 0) {
            // Flexible break - use break_duration_minutes
            $totalMinutes -= $this->break_duration_minutes;
        } elseif ($this->break_start && $this->break_end) {
            // Fixed break times - calculate break duration
            if ($this->break_start instanceof \Carbon\Carbon) {
                $breakStart = $this->break_start->copy();
                $breakEnd = $this->break_end->copy();
            } else {
                $breakStart = \Carbon\Carbon::createFromFormat('H:i:s', $this->break_start);
                $breakEnd = \Carbon\Carbon::createFromFormat('H:i:s', $this->break_end);
            }
            $breakMinutes = $breakStart->diffInMinutes($breakEnd);
            $totalMinutes -= $breakMinutes;
        }
        // No break type - no deduction

        return round($totalMinutes / 60, 2);
    }

    /**
     * Update the total_hours field
     */
    public function updateTotalHours()
    {
        $this->total_hours = $this->calculateTotalHours();
        $this->save();
        return $this;
    }

    /**
     * Mutator to handle empty break_start values
     */
    public function setBreakStartAttribute($value)
    {
        $this->attributes['break_start'] = empty($value) ? null : $value;
    }

    /**
     * Mutator to handle empty break_end values
     */
    public function setBreakEndAttribute($value)
    {
        $this->attributes['break_end'] = empty($value) ? null : $value;
    }
}
