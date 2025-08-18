<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NightDifferentialSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_time',
        'end_time',
        'rate_multiplier',
        'description',
        'is_active'
    ];

    protected $casts = [
        'rate_multiplier' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    /**
     * Get the current active night differential setting
     */
    public static function current()
    {
        return static::where('is_active', true)->first();
    }
}
