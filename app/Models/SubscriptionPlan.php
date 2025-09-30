<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'max_employees',
        'features',
        'price',
        'duration_months',
        'is_active'
    ];

    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
    ];

    public function licenses()
    {
        return $this->hasMany(SystemLicense::class);
    }

    public function hasFeature($feature)
    {
        return in_array($feature, $this->features);
    }
}
