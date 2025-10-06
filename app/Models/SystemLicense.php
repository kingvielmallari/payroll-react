<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SystemLicense extends Model
{
    use HasFactory;

    protected $fillable = [
        'license_key',
        'server_fingerprint',
        'plan_info',
        'activated_at',
        'expires_at',
        'countdown_started_at',
        'is_active',
        'system_info'
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'countdown_started_at' => 'datetime',
        'system_info' => 'array',
        'plan_info' => 'array',
    ];

    public function isExpired()
    {
        return $this->expires_at && $this->expires_at < Carbon::now();
    }

    public function isValid()
    {
        return $this->is_active && !$this->isExpired();
    }

    public function hasReachedEmployeeLimit()
    {
        if (!$this->plan_info || !isset($this->plan_info['max_employees'])) {
            return false;
        }

        $employeeCount = \App\Models\Employee::count();
        return $employeeCount >= $this->plan_info['max_employees'];
    }

    public function getEmployeeLimitAttribute()
    {
        return $this->plan_info['max_employees'] ?? null;
    }

    public function getPriceAttribute()
    {
        return $this->plan_info['price'] ?? null;
    }

    public function getDurationDaysAttribute()
    {
        return $this->plan_info['duration_days'] ?? null;
    }

    public function getCustomerAttribute()
    {
        return $this->plan_info['customer'] ?? null;
    }

    public function startCountdown()
    {
        if (!$this->countdown_started_at) {
            $this->update([
                'countdown_started_at' => Carbon::now(),
                'expires_at' => Carbon::now()->addDays($this->duration_days ?? 30)
            ]);
        }
    }

    public static function current()
    {
        return static::where('is_active', true)->first();
    }
}
