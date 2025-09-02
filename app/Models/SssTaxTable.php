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
     * Get the current active SSS tax table entries
     */
    public static function current()
    {
        return static::where('is_active', true)
            ->orderBy('range_start')
            ->get();
    }

    /**
     * Find the appropriate SSS bracket for a given salary
     */
    public static function findBracketForSalary($salary)
    {
        return static::where('is_active', true)
            ->where('range_start', '<=', $salary)
            ->where(function ($query) use ($salary) {
                $query->where('range_end', '>=', $salary)
                    ->orWhereNull('range_end');
            })
            ->orderBy('range_start', 'desc')
            ->first();
    }

    /**
     * Calculate SSS contribution for a given salary
     */
    public static function calculateContribution($salary)
    {
        $bracket = static::findBracketForSalary($salary);

        if (!$bracket) {
            return [
                'employee_share' => 0,
                'employer_share' => 0,
                'total_contribution' => 0
            ];
        }

        return [
            'employee_share' => $bracket->employee_share,
            'employer_share' => $bracket->employer_share,
            'total_contribution' => $bracket->total_contribution
        ];
    }
}
