<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WithholdingTaxTable extends Model
{
    protected $fillable = [
        'pay_frequency',
        'bracket',
        'range_start',
        'range_end',
        'base_tax',
        'tax_rate',
        'excess_over'
    ];

    protected $casts = [
        'range_start' => 'decimal:2',
        'range_end' => 'decimal:2',
        'base_tax' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'excess_over' => 'decimal:2'
    ];

    /**
     * Calculate withholding tax based on taxable income and pay frequency
     */
    public static function calculateWithholdingTax($taxableIncome, $payFrequency = 'semi_monthly')
    {
        // Find the appropriate tax bracket
        $taxBracket = self::where('pay_frequency', $payFrequency)
            ->where('range_start', '<=', $taxableIncome)
            ->where(function ($query) use ($taxableIncome) {
                $query->where('range_end', '>=', $taxableIncome)
                    ->orWhereNull('range_end'); // For "and above" ranges
            })
            ->first();

        if (!$taxBracket) {
            return 0; // No tax if no bracket found
        }

        // Calculate tax
        $baseTax = $taxBracket->base_tax;
        $taxRate = $taxBracket->tax_rate / 100; // Convert percentage to decimal
        $excessOver = $taxBracket->excess_over;

        // Calculate excess amount
        $excessAmount = max(0, $taxableIncome - $excessOver);

        // Calculate total tax
        $totalTax = $baseTax + ($excessAmount * $taxRate);

        return round($totalTax, 2);
    }

    /**
     * Get tax brackets for a specific pay frequency
     */
    public static function getTaxBrackets($payFrequency = 'semi_monthly')
    {
        return self::where('pay_frequency', $payFrequency)
            ->orderBy('bracket')
            ->get();
    }

    /**
     * Get all available pay frequencies
     */
    public static function getPayFrequencies()
    {
        return [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'semi_monthly' => 'Semi-Monthly',
            'monthly' => 'Monthly'
        ];
    }
}
