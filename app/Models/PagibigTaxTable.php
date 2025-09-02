<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PagibigTaxTable extends Model
{
    use HasFactory;

    protected $table = 'pagibig_tax_table';

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
     * Get the current active Pag-IBIG rates
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
                $query->where('range_end', '>=', $monthlyBasicSalary)
                    ->orWhereNull('range_end'); // For "above" ranges
            })
            ->first();
    }

    /**
     * Calculate Pag-IBIG contribution for a given monthly basic salary
     */
    public static function calculateContribution($monthlyBasicSalary)
    {
        $bracket = static::findBracketForSalary($monthlyBasicSalary);

        if (!$bracket) {
            return null; // No applicable bracket found
        }

        // Calculate contributions based on percentage
        $employeeContribution = ($monthlyBasicSalary * $bracket->employee_share) / 100;
        $employerContribution = ($monthlyBasicSalary * $bracket->employer_share) / 100;
        $totalContribution = $employeeContribution + $employerContribution;

        // Apply minimum and maximum limits
        if ($bracket->min_contribution > 0) {
            $employeeContribution = max($employeeContribution, $bracket->min_contribution);
            $employerContribution = max($employerContribution, $bracket->min_contribution);
        }

        if ($bracket->max_contribution > 0) {
            $employeeContribution = min($employeeContribution, $bracket->max_contribution);
            $employerContribution = min($employerContribution, $bracket->max_contribution);
        }

        $totalContribution = $employeeContribution + $employerContribution;

        return [
            'employee_share' => round($employeeContribution, 2),
            'employer_share' => round($employerContribution, 2),
            'total' => round($totalContribution, 2),
            'bracket' => $bracket
        ];
    }

    /**
     * Get all brackets as formatted array for API responses
     */
    public static function getAllFormatted()
    {
        return static::where('is_active', true)
            ->orderBy('range_start')
            ->get()
            ->map(function ($bracket) {
                return [
                    'id' => $bracket->id,
                    'range' => $bracket->range_end
                        ? number_format($bracket->range_start, 2) . ' - ' . number_format($bracket->range_end, 2)
                        : number_format($bracket->range_start, 2) . ' - Above',
                    'employee_share' => $bracket->employee_share . '%',
                    'employer_share' => $bracket->employer_share . '%',
                    'total_contribution' => $bracket->total_contribution . '%',
                    'min_contribution' => number_format($bracket->min_contribution, 2),
                    'max_contribution' => number_format($bracket->max_contribution, 2),
                ];
            });
    }
}
