<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DaySchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'monday' => 'boolean',
        'tuesday' => 'boolean',
        'wednesday' => 'boolean',
        'thursday' => 'boolean',
        'friday' => 'boolean',
        'saturday' => 'boolean',
        'sunday' => 'boolean',
        'is_active' => 'boolean',
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

    public function getDaysDisplayAttribute()
    {
        $days = [];
        if ($this->monday) $days[] = 'Mon';
        if ($this->tuesday) $days[] = 'Tue';
        if ($this->wednesday) $days[] = 'Wed';
        if ($this->thursday) $days[] = 'Thu';
        if ($this->friday) $days[] = 'Fri';
        if ($this->saturday) $days[] = 'Sat';
        if ($this->sunday) $days[] = 'Sun';

        if (empty($days)) {
            return 'No days selected';
        }

        // Check for common patterns
        if (count($days) === 5 && !$this->saturday && !$this->sunday) {
            return 'Monday to Friday';
        }
        if (count($days) === 6 && !$this->sunday) {
            return 'Monday to Saturday';
        }
        if (count($days) === 7) {
            return 'All days';
        }

        return implode(', ', $days);
    }

    public function getWorkingDaysCountAttribute()
    {
        return collect([
            $this->monday,
            $this->tuesday,
            $this->wednesday,
            $this->thursday,
            $this->friday,
            $this->saturday,
            $this->sunday,
        ])->filter()->count();
    }
}
