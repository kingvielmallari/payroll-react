<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeductionSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'calculation_type',
        'rate',
        'fixed_amount',
        'minimum_amount',
        'maximum_amount',
        'salary_threshold',
        'rate_table',
        'is_mandatory',
        'is_active',
        'description',
        'formula_notes',
    ];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
        'rate_table' => 'array',
        'rate' => 'decimal:2',
        'fixed_amount' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'maximum_amount' => 'decimal:2',
        'salary_threshold' => 'decimal:2',
    ];

    /**
     * Get active deduction settings
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get government deduction settings
     */
    public function scopeGovernment($query)
    {
        return $query->where('type', 'government');
    }

    /**
     * Get custom deduction settings
     */
    public function scopeCustom($query)
    {
        return $query->where('type', 'custom');
    }

    /**
     * Get mandatory deduction settings
     */
    public function scopeMandatory($query)
    {
        return $query->where('is_mandatory', true);
    }

    /**
     * Calculate deduction amount based on salary
     */
    public function calculateDeduction($salary)
    {
        // Check if salary meets threshold
        if ($this->salary_threshold && $salary < $this->salary_threshold) {
            return 0;
        }

        $amount = 0;

        switch ($this->calculation_type) {
            case 'percentage':
                $amount = $salary * ($this->rate / 100);
                break;
            
            case 'fixed':
                $amount = $this->fixed_amount;
                break;
            
            case 'tiered':
                $amount = $this->calculateTieredAmount($salary);
                break;
            
            case 'table_based':
                $amount = $this->calculateTableBasedAmount($salary);
                break;
        }

        // Apply minimum and maximum limits
        if ($this->minimum_amount && $amount < $this->minimum_amount) {
            $amount = $this->minimum_amount;
        }

        if ($this->maximum_amount && $amount > $this->maximum_amount) {
            $amount = $this->maximum_amount;
        }

        return round($amount, 2);
    }

    /**
     * Calculate tiered deduction amount
     */
    private function calculateTieredAmount($salary)
    {
        if (!$this->rate_table) {
            return 0;
        }

        $amount = 0;
        foreach ($this->rate_table as $tier) {
            $min = $tier['min'] ?? 0;
            $max = $tier['max'] ?? PHP_INT_MAX;
            $rate = $tier['rate'] ?? 0;

            if ($salary >= $min && $salary <= $max) {
                $amount = $salary * ($rate / 100);
                break;
            }
        }

        return $amount;
    }

    /**
     * Calculate table-based deduction amount
     */
    private function calculateTableBasedAmount($salary)
    {
        if (!$this->rate_table) {
            return 0;
        }

        // Find the appropriate bracket
        foreach ($this->rate_table as $bracket) {
            $min = $bracket['min'] ?? 0;
            $max = $bracket['max'] ?? PHP_INT_MAX;
            $amount = $bracket['amount'] ?? 0;

            if ($salary >= $min && $salary <= $max) {
                return $amount;
            }
        }

        return 0;
    }
}
