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
     * Calculate SSS deduction based on contribution table and sharing setting
     */
    private function calculateSSSDeduction($salary)
    {
        // SSS Contribution Table for 2025
        $sssTable = [
            ['min' => 0, 'max' => 5249.99, 'ee' => 275.00, 'er' => 550.00],
            ['min' => 5250, 'max' => 5749.99, 'ee' => 275.00, 'er' => 610.00],
            ['min' => 5750, 'max' => 6249.99, 'ee' => 300.00, 'er' => 670.00],
            ['min' => 6250, 'max' => 6749.99, 'ee' => 330.00, 'er' => 730.00],
            ['min' => 6750, 'max' => 7249.99, 'ee' => 360.00, 'er' => 790.00],
            ['min' => 7250, 'max' => 7749.99, 'ee' => 390.00, 'er' => 850.00],
            ['min' => 7750, 'max' => 8249.99, 'ee' => 420.00, 'er' => 910.00],
            ['min' => 8250, 'max' => 8749.99, 'ee' => 450.00, 'er' => 970.00],
            ['min' => 8750, 'max' => 9249.99, 'ee' => 480.00, 'er' => 1030.00],
            ['min' => 9250, 'max' => 9749.99, 'ee' => 510.00, 'er' => 1090.00],
            ['min' => 9750, 'max' => 10249.99, 'ee' => 540.00, 'er' => 1150.00],
            ['min' => 10250, 'max' => 10749.99, 'ee' => 570.00, 'er' => 1210.00],
            ['min' => 10750, 'max' => 11249.99, 'ee' => 600.00, 'er' => 1270.00],
            ['min' => 11250, 'max' => 11749.99, 'ee' => 630.00, 'er' => 1330.00],
            ['min' => 11750, 'max' => 12249.99, 'ee' => 660.00, 'er' => 1390.00],
            ['min' => 12250, 'max' => 12749.99, 'ee' => 690.00, 'er' => 1450.00],
            ['min' => 12750, 'max' => 13249.99, 'ee' => 720.00, 'er' => 1510.00],
            ['min' => 13250, 'max' => 13749.99, 'ee' => 750.00, 'er' => 1570.00],
            ['min' => 13750, 'max' => 14249.99, 'ee' => 780.00, 'er' => 1630.00],
            ['min' => 14250, 'max' => 14749.99, 'ee' => 810.00, 'er' => 1690.00],
            ['min' => 14750, 'max' => 15249.99, 'ee' => 840.00, 'er' => 1750.00],
            ['min' => 15250, 'max' => 15749.99, 'ee' => 870.00, 'er' => 1810.00],
            ['min' => 15750, 'max' => 16249.99, 'ee' => 900.00, 'er' => 1870.00],
            ['min' => 16250, 'max' => 16749.99, 'ee' => 930.00, 'er' => 1930.00],
            ['min' => 16750, 'max' => 17249.99, 'ee' => 960.00, 'er' => 1990.00],
            ['min' => 17250, 'max' => 17749.99, 'ee' => 990.00, 'er' => 2050.00],
            ['min' => 17750, 'max' => 18249.99, 'ee' => 1020.00, 'er' => 2110.00],
            ['min' => 18250, 'max' => 18749.99, 'ee' => 1050.00, 'er' => 2170.00],
            ['min' => 18750, 'max' => 19249.99, 'ee' => 1080.00, 'er' => 2230.00],
            ['min' => 19250, 'max' => 19749.99, 'ee' => 1110.00, 'er' => 2290.00],
            ['min' => 19750, 'max' => 20249.99, 'ee' => 1140.00, 'er' => 2350.00],
            // Continue pattern up to maximum
            ['min' => 20250, 'max' => PHP_INT_MAX, 'ee' => 1170.00, 'er' => 2410.00],
        ];

        foreach ($sssTable as $bracket) {
            if ($salary >= $bracket['min'] && $salary <= $bracket['max']) {
                if ($this->share_with_employer) {
                    // If shared with employer, only deduct employee share
                    return $bracket['ee'];
                } else {
                    // If not shared, deduct both employee and employer shares from employee
                    return $bracket['ee'] + $bracket['er'];
                }
            }
        }

        return 0;
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
}
