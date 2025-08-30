<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SssTaxTable extends Model
{
    use HasFactory;

    protected $table = 'sss_tax_table';

    protected $fillable = [
        'range_start',
        'range_end',
        'employee_share',
        'employer_share',
        'total_contribution',
        'is_active'
    ];

    protected $casts = [
        'range_start' => 'decimal:2',
        'range_end' => 'decimal:2',
        'employee_share' => 'decimal:2',
        'employer_share' => 'decimal:2',
        'total_contribution' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    /**
     * Get SSS contribution for a given salary
     */
    public static function getContribution($salary)
    {
        return static::where('is_active', true)
            ->where('range_start', '<=', $salary)
            ->where(function ($query) use ($salary) {
                $query->where('range_end', '>=', $salary)
                      ->orWhereNull('range_end');
            })
            ->first();
    }

    /**
     * Get formatted range display
     */
    public function getRangeDisplayAttribute()
    {
        if ($this->range_end) {
            return '₱' . number_format($this->range_start, 2) . ' - ₱' . number_format($this->range_end, 2);
        } else {
            return '₱' . number_format($this->range_start, 2) . ' and above';
        }
    }
}
