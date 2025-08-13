<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AllowanceBonusSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'category',
        'calculation_type',
        'rate_percentage',
        'fixed_amount',
        'multiplier',
        'is_taxable',
        'apply_to_regular_days',
        'apply_to_overtime',
        'apply_to_holidays',
        'apply_to_rest_days',
        'frequency',
        'semi_monthly_distribution',
        'conditions',
        'minimum_amount',
        'maximum_amount',
        'max_days_per_period',
        'is_active',
        'is_system_default',
        'sort_order',
        'benefit_eligibility',
    ];

    protected $casts = [
        'rate_percentage' => 'decimal:4',
        'fixed_amount' => 'decimal:2',
        'multiplier' => 'decimal:4',
        'minimum_amount' => 'decimal:2',
        'maximum_amount' => 'decimal:2',
        'conditions' => 'array',
        'is_taxable' => 'boolean',
        'apply_to_regular_days' => 'boolean',
        'apply_to_overtime' => 'boolean',
        'apply_to_holidays' => 'boolean',
        'apply_to_rest_days' => 'boolean',
        'is_active' => 'boolean',
        'is_system_default' => 'boolean',
    ];

    /**
     * Scope to get only active allowances/bonuses
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get allowances
     */
    public function scopeAllowances($query)
    {
        return $query->where('type', 'allowance');
    }

    /**
     * Scope to get bonuses
     */
    public function scopeBonuses($query)
    {
        return $query->where('type', 'bonus');
    }

    /**
     * Scope to get by frequency
     */
    public function scopeByFrequency($query, $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    /**
     * Calculate allowance/bonus amount
     */
    public function calculateAmount($basicSalary, $dailyRate = null, $workingDays = null, $employee = null)
    {
        $amount = 0;

        switch ($this->calculation_type) {
            case 'percentage':
                $amount = $basicSalary * ($this->rate_percentage / 100);
                break;

            case 'fixed_amount':
                $amount = $this->fixed_amount;
                break;

            case 'daily_rate_multiplier':
                if ($dailyRate && $workingDays) {
                    $applicableDays = min($workingDays, $this->max_days_per_period ?: $workingDays);
                    $amount = $dailyRate * $this->multiplier * $applicableDays;
                }
                break;
        }

        // Apply conditions if any
        if ($this->conditions && $employee) {
            $amount = $this->applyConditions($amount, $employee);
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
     * Apply conditional rules
     */
    private function applyConditions($amount, $employee)
    {
        if (!$this->conditions || empty($this->conditions)) {
            return $amount;
        }

        foreach ($this->conditions as $condition) {
            $field = $condition['field'] ?? '';
            $operator = $condition['operator'] ?? '';
            $value = $condition['value'] ?? '';
            $action = $condition['action'] ?? '';
            $actionValue = $condition['action_value'] ?? 0;

            $employeeValue = data_get($employee, $field);

            $conditionMet = $this->evaluateCondition($employeeValue, $operator, $value);

            if ($conditionMet) {
                switch ($action) {
                    case 'multiply':
                        $amount *= $actionValue;
                        break;
                    case 'add':
                        $amount += $actionValue;
                        break;
                    case 'subtract':
                        $amount -= $actionValue;
                        break;
                    case 'set':
                        $amount = $actionValue;
                        break;
                    case 'percentage':
                        $amount = $amount * ($actionValue / 100);
                        break;
                }
            }
        }

        return $amount;
    }

    /**
     * Evaluate a condition
     */
    private function evaluateCondition($employeeValue, $operator, $conditionValue)
    {
        switch ($operator) {
            case 'equals':
                return $employeeValue == $conditionValue;
            case 'greater_than':
                return $employeeValue > $conditionValue;
            case 'less_than':
                return $employeeValue < $conditionValue;
            case 'contains':
                return str_contains($employeeValue, $conditionValue);
            case 'in':
                return in_array($employeeValue, (array)$conditionValue);
            default:
                return false;
        }
    }

    /**
     * Check if this setting applies to the given employee based on their benefit status
     */
    public function appliesTo($employee)
    {
        if ($this->benefit_eligibility === 'both') {
            return true;
        }

        return $this->benefit_eligibility === $employee->benefits_status;
    }

    /**
     * Scope to filter settings by benefit eligibility
     */
    public function scopeForBenefitStatus($query, $benefitStatus)
    {
        return $query->where(function ($q) use ($benefitStatus) {
            $q->where('benefit_eligibility', 'both')
                ->orWhere('benefit_eligibility', $benefitStatus);
        });
    }
}
