<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Holiday extends Model
{
    protected $fillable = [
        'name',
        'description',
        'date',
        'type',
        'rate_multiplier',
        'is_double_pay',
        'double_pay_rate',
        'pay_rule',
        'is_recurring',
        'is_active',
        'year',
        'sort_order'
    ];

    protected $casts = [
        'date' => 'date',
        'rate_multiplier' => 'decimal:2',
        'double_pay_rate' => 'decimal:2',
        'is_double_pay' => 'boolean',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
        'year' => 'integer',
        'sort_order' => 'integer'
    ];

    protected $attributes = [
        'is_active' => false, // DISABLED by default for fresh installations
        'is_recurring' => false,
        'is_double_pay' => false,
        'type' => 'regular',
        'rate_multiplier' => 1.00,
        'double_pay_rate' => 2.00,
        'pay_rule' => 'holiday_rate'
    ];
}
