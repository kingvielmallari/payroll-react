<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeductionTaxSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'category',
        'calculation_type',
        'tax_table_type',
        'rate_percentage',
        'fixed_amount',
        'bracket_rates',
        'minimum_amount',
        'maximum_amount',
        'salary_cap',
        'apply_to_regular',
        'apply_to_overtime',
        'apply_to_bonus',
        'apply_to_allowances',
        'apply_to_basic_pay',
        'apply_to_gross_pay',
        'apply_to_taxable_income',
        'apply_to_net_pay',
        'employer_share_rate',
        'employer_share_fixed',
        'share_with_employer',
        'is_active',
        'is_system_default',
        'sort_order',
        'benefit_eligibility',
    ];

    protected $casts = [
        'bracket_rates' => 'array',
        'rate_percentage' => 'decimal:4',
        'fixed_amount' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'maximum_amount' => 'decimal:2',
        'salary_cap' => 'decimal:2',
        'employer_share_rate' => 'decimal:4',
        'employer_share_fixed' => 'decimal:2',
        'apply_to_regular' => 'boolean',
        'apply_to_overtime' => 'boolean',
        'apply_to_bonus' => 'boolean',
        'apply_to_allowances' => 'boolean',
        'apply_to_basic_pay' => 'boolean',
        'apply_to_gross_pay' => 'boolean',
        'apply_to_taxable_income' => 'boolean',
        'apply_to_net_pay' => 'boolean',
        'share_with_employer' => 'boolean',
        'is_active' => 'boolean',
        'is_system_default' => 'boolean',
    ];

    /**
     * Scope to get only active deductions
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
     * Scope to get government deductions
     */
    public function scopeGovernment($query)
    {
        return $query->where('type', 'government');
    }

    /**
     * Calculate deduction amount based on salary components
     */
    public function calculateDeduction($basicPay = 0, $overtime = 0, $bonus = 0, $allowances = 0, $grossPay = null, $taxableIncome = null, $netPay = null)
    {
        // Calculate gross pay if not provided
        if ($grossPay === null) {
            $grossPay = $basicPay + $overtime + $bonus + $allowances;
        }

        // Determine the base amount to apply deduction to
        $applicableSalary = 0;

        if ($this->apply_to_basic_pay) $applicableSalary += $basicPay;
        if ($this->apply_to_regular) $applicableSalary += $basicPay; // backwards compatibility
        if ($this->apply_to_overtime) $applicableSalary += $overtime;
        if ($this->apply_to_bonus) $applicableSalary += $bonus;
        if ($this->apply_to_allowances) $applicableSalary += $allowances;
        if ($this->apply_to_gross_pay && $grossPay) $applicableSalary = $grossPay;
        if ($this->apply_to_taxable_income && $taxableIncome) $applicableSalary = $taxableIncome;
        if ($this->apply_to_net_pay && $netPay) $applicableSalary = $netPay;

        // Apply salary cap if set
        if ($this->salary_cap && $applicableSalary > $this->salary_cap) {
            $applicableSalary = $this->salary_cap;
        }

        $deduction = 0;

        switch ($this->calculation_type) {
            case 'percentage':
                $deduction = $applicableSalary * ($this->rate_percentage / 100);
                break;

            case 'fixed_amount':
                $deduction = $this->fixed_amount;
                break;

            case 'bracket':
                if ($this->tax_table_type) {
                    $deduction = $this->calculateTaxTableDeduction($applicableSalary, $this->tax_table_type);
                } else {
                    $deduction = $this->calculateBracketDeduction($applicableSalary);
                }
                break;
        }

        // Apply minimum and maximum limits
        if ($this->minimum_amount && $deduction < $this->minimum_amount) {
            $deduction = $this->minimum_amount;
        }

        if ($this->maximum_amount && $deduction > $this->maximum_amount) {
            $deduction = $this->maximum_amount;
        }

        return round($deduction, 2);
    }

    /**
     * Calculate bracket-based deduction (for tax brackets)
     */
    private function calculateBracketDeduction($amount)
    {
        if (!$this->bracket_rates) return 0;

        $totalDeduction = 0;
        $remainingAmount = $amount;

        foreach ($this->bracket_rates as $bracket) {
            $bracketMin = $bracket['min'] ?? 0;
            $bracketMax = $bracket['max'] ?? PHP_INT_MAX;
            $rate = $bracket['rate'] ?? 0;

            if ($remainingAmount <= $bracketMin) break;

            $taxableInBracket = min($remainingAmount - $bracketMin, $bracketMax - $bracketMin);
            $totalDeduction += $taxableInBracket * ($rate / 100);
        }

        return $totalDeduction;
    }

    /**
     * Calculate tax table-based deduction (SSS, PhilHealth, Pag-IBIG, Withholding Tax)
     */
    private function calculateTaxTableDeduction($amount, $type)
    {
        switch ($type) {
            case 'sss':
                return $this->calculateSSSDeduction($amount);
            case 'philhealth':
                return $this->calculatePhilHealthDeduction($amount);
            case 'pagibig':
                return $this->calculatePagibigDeduction($amount);
            case 'withholding_tax':
                return $this->calculateWithholdingTaxDeduction($amount);
            default:
                return 0;
        }
    }

    /**
     * Calculate SSS deduction based on database contribution table and sharing setting
     */
    private function calculateSSSDeduction($salary)
    {
        // Query the SSS tax table from database for the salary range
        $sssContribution = \DB::table('sss_tax_table')
            ->where('range_start', '<=', $salary)
            ->where(function ($query) use ($salary) {
                $query->where('range_end', '>=', $salary)
                    ->orWhereNull('range_end'); // For "above" ranges
            })
            ->where('is_active', true)
            ->first();

        if (!$sssContribution) {
            return 0; // No matching range found
        }

        $employeeShare = (float) $sssContribution->employee_share;
        $employerShare = (float) $sssContribution->employer_share;

        if ($this->share_with_employer) {
            // If shared with employer, only deduct employee share from employee salary
            return $employeeShare;
        } else {
            // If not shared, deduct both employee and employer shares from employee salary
            return $employeeShare + $employerShare;
        }
    }

    /**
     * Calculate PhilHealth deduction based on sharing setting
     */
    private function calculatePhilHealthDeduction($salary)
    {
        // PhilHealth rates for 2024-2025
        // Total contribution is 5% (2.5% employee, 2.5% employer)
        $employeeRate = 0.025; // 2.5%
        $employerRate = 0.025; // 2.5%

        // Calculate employee share
        $employeeShare = $salary * $employeeRate;

        // Apply salary cap of ₱80,000
        $maxSalary = 80000;
        if ($salary > $maxSalary) {
            $employeeShare = $maxSalary * $employeeRate;
        }

        if ($this->share_with_employer) {
            // If shared with employer, only deduct employee share
            return $employeeShare;
        } else {
            // If not shared, deduct both employee and employer shares from employee
            $employerShare = $salary * $employerRate;
            if ($salary > $maxSalary) {
                $employerShare = $maxSalary * $employerRate;
            }
            return $employeeShare + $employerShare;
        }
    }

    /**
     * Calculate Pag-IBIG deduction based on sharing setting
     */
    private function calculatePagibigDeduction($salary)
    {
        // Pag-IBIG rates for 2024-2025
        $employeeRate = 0.02; // 2%
        $employerRate = 0.02; // 2%

        // For salaries ≤ ₱1,500: 1% employee, 2% employer
        if ($salary <= 1500) {
            $employeeRate = 0.01; // 1%
        }

        // Calculate employee share with max of ₱200
        $employeeShare = min($salary * $employeeRate, 200);

        if ($this->share_with_employer) {
            // If shared with employer, only deduct employee share
            return $employeeShare;
        } else {
            // If not shared, deduct both employee and employer shares from employee
            $employerShare = min($salary * $employerRate, 200);
            return $employeeShare + $employerShare;
        }
    }

    /**
     * Calculate BIR Withholding Tax deduction
     */
    private function calculateWithholdingTaxDeduction($taxableIncome)
    {
        // BIR Withholding Tax Table for 2023 onwards
        // Assuming semi-monthly pay period
        $taxBrackets = [
            ['min' => 0, 'max' => 10417, 'rate' => 0, 'baseAmount' => 0],
            ['min' => 10417.01, 'max' => 16666, 'rate' => 15, 'baseAmount' => 0],
            ['min' => 16666.01, 'max' => 33332, 'rate' => 20, 'baseAmount' => 937.50],
            ['min' => 33332.01, 'max' => 83332, 'rate' => 25, 'baseAmount' => 4270.70],
            ['min' => 83332.01, 'max' => 333332, 'rate' => 30, 'baseAmount' => 16770.70],
            ['min' => 333332.01, 'max' => PHP_INT_MAX, 'rate' => 35, 'baseAmount' => 91770.70],
        ];

        foreach ($taxBrackets as $bracket) {
            if ($taxableIncome >= $bracket['min'] && $taxableIncome <= $bracket['max']) {
                $excess = max(0, $taxableIncome - $bracket['min']);
                return $bracket['baseAmount'] + ($excess * ($bracket['rate'] / 100));
            }
        }

        return 0;
    }

    /**
     * Calculate employer share
     */
    public function calculateEmployerShare($employeeDeduction, $salary)
    {
        if ($this->employer_share_rate) {
            return $salary * ($this->employer_share_rate / 100);
        }

        if ($this->employer_share_fixed) {
            return $this->employer_share_fixed;
        }

        return 0;
    }

    /**
     * Get share percentage display for UI badge
     */
    public function getSharePercentageAttribute()
    {
        if (!$this->share_with_employer) {
            return null; // No sharing
        }

        // Return standard sharing percentages for government deductions
        switch ($this->tax_table_type) {
            case 'sss':
                return '50%'; // Typically employee pays about 1/3, employer pays 2/3
            case 'philhealth':
                return '50%'; // Equal sharing 2.5% each
            case 'pagibig':
                return '50%'; // Equal sharing for most salary ranges
            default:
                return '50%';
        }
    }

    /**
     * Check if this deduction supports employer sharing
     */
    public function getSupportsEmployerSharingAttribute()
    {
        return in_array($this->tax_table_type, ['sss', 'philhealth', 'pagibig']);
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

    /**
     * Calculate employer share only for reporting purposes
     */
    public function calculateEmployerShareOnly($salary)
    {
        if (!$this->share_with_employer) {
            return 0; // No employer share if not sharing
        }

        switch ($this->tax_table_type) {
            case 'sss':
                return $this->calculateSSSEmployerShare($salary);
            case 'philhealth':
                return $this->calculatePhilHealthEmployerShare($salary);
            case 'pagibig':
                return $this->calculatePagibigEmployerShare($salary);
            default:
                return 0;
        }
    }

    /**
     * Calculate SSS employer share only
     */
    private function calculateSSSEmployerShare($salary)
    {
        if (!$this->share_with_employer) {
            return 0;
        }

        $sssContribution = \DB::table('sss_tax_table')
            ->where('range_start', '<=', $salary)
            ->where(function ($query) use ($salary) {
                $query->where('range_end', '>=', $salary)
                    ->orWhereNull('range_end');
            })
            ->where('is_active', true)
            ->first();

        return $sssContribution ? (float) $sssContribution->employer_share : 0;
    }

    /**
     * Calculate PhilHealth employer share only
     */
    private function calculatePhilHealthEmployerShare($salary)
    {
        if (!$this->share_with_employer) {
            return 0;
        }

        $employerRate = 0.025; // 2.5%
        $maxSalary = 80000;

        if ($salary > $maxSalary) {
            return $maxSalary * $employerRate;
        }

        return $salary * $employerRate;
    }

    /**
     * Calculate Pag-IBIG employer share only
     */
    private function calculatePagibigEmployerShare($salary)
    {
        if (!$this->share_with_employer) {
            return 0;
        }

        $employerRate = $salary <= 1500 ? 0.02 : 0.02; // 2% for all salary levels
        return min($salary * $employerRate, 200);
    }
}
