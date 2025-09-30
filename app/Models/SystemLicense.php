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
        'subscription_plan_id',
        'activated_at',
        'expires_at',
        'is_active',
        'system_info'
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'system_info' => 'array',
    ];

    public function subscriptionPlan()
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function isExpired()
    {
        return $this->expires_at < Carbon::now();
    }

    public function isValid()
    {
        return $this->is_active && !$this->isExpired();
    }

    public static function current()
    {
        return static::where('is_active', true)->first();
    }
}
