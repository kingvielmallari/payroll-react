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

        // Calculate total working hours
        $totalMinutes = $timeOut->diffInMinutes($timeIn);

        // Subtract break time based on break type
        if ($this->break_duration_minutes > 0) {
            // Break duration system
            $totalMinutes -= $this->break_duration_minutes;
        } else if ($this->break_start && $this->break_end) {
            // Fixed break times system
            $breakMinutes = $this->break_end->diffInMinutes($this->break_start);
            $totalMinutes -= $breakMinutes;
        }

        return round($totalMinutes / 60, 2);
    }
}
