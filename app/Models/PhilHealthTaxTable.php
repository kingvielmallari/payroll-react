<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PhilHealthTaxTable extends Model
{
    use HasFactory;

    protected $table = 'philhealth_tax_table';

    protected $fillable = [
        'range_start',
        'range_end',
        'employee_share',
        'employer_share',
        'total_contribution',
        'min_contribution',
        'max_contribution',
        'is_active'
    ];

    protected $casts = [
        'range_start' => 'decimal:2',
        'range_end' => 'decimal:2',
        'employee_share' => 'decimal:2',
        'employer_share' => 'decimal:2',
        'total_contribution' => 'decimal:2',
        'min_contribution' => 'decimal:2',
        'max_contribution' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    /**
     * Get the current active PhilHealth rates
     */
    public static function current()
    {
        return static::where('is_active', true)
            ->orderBy('range_start')
            ->get();
    }

    /**
     * Find the applicable tax bracket for a given monthly basic salary
     */
    public static function findBracketForSalary($monthlyBasicSalary)
    {
        return static::where('is_active', true)
            ->where('range_start', '<=', $monthlyBasicSalary)
            ->where(function ($query) use ($monthlyBasicSalary) {
                $query->whereNull('range_end')
                    ->orWhere('range_end', '>=', $monthlyBasicSalary);
            })
            ->first();
    }

    /**
     * Calculate PhilHealth contribution for a given monthly basic salary
     */
    public static function calculateContribution($monthlyBasicSalary, $shareWithEmployer = true)
    {
        $bracket = static::findBracketForSalary($monthlyBasicSalary);

        if (!$bracket) {
            return [
                'employee_share' => 0,
                'employer_share' => 0,
                'total' => 0,
                'bracket' => null
            ];
        }

        // Calculate based on percentage of Monthly Basic Salary
        // Convert percentage to decimal (2.50 -> 0.025)
        $eeContribution = $monthlyBasicSalary * ($bracket->employee_share / 100);
        $erContribution = $monthlyBasicSalary * ($bracket->employer_share / 100);

        // Apply minimum and maximum limits if they exist
        // Only apply minimum if it's greater than 0 (allow 0 minimum contributions)
        if ($bracket->min_contribution && $bracket->min_contribution > 0 && $eeContribution < $bracket->min_contribution) {
            $eeContribution = $bracket->min_contribution;
        }

        if ($bracket->max_contribution && $eeContribution > $bracket->max_contribution) {
            $eeContribution = $bracket->max_contribution;
        }

        // For employer share, apply the same limits
        // Only apply minimum if it's greater than 0 (allow 0 minimum contributions)
        if ($bracket->min_contribution && $bracket->min_contribution > 0 && $erContribution < $bracket->min_contribution) {
            $erContribution = $bracket->min_contribution;
        }

        if ($bracket->max_contribution && $erContribution > $bracket->max_contribution) {
            $erContribution = $bracket->max_contribution;
        }

        $totalContribution = $eeContribution + $erContribution;

        return [
            'employee_share' => round($eeContribution, 2),
            'employer_share' => round($erContribution, 2),
            'total' => round($totalContribution, 2),
            'bracket' => $bracket
        ];
    }
}
