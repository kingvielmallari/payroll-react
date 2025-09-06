<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PayrollDetail extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'payroll_id',
        'employee_id',
        'basic_salary',
        'daily_rate',
        'hourly_rate',
        'days_worked',
        'regular_hours',
        'overtime_hours',
        'holiday_hours',
        'rest_day_hours',
        'night_differential_hours',
        'regular_pay',
        'overtime_pay',
        'holiday_pay',
        'rest_day_pay',
        'night_differential_pay',
        'allowances',
        'bonuses',
        'other_earnings',
        'gross_pay',
        'sss_contribution',
        'philhealth_contribution',
        'pagibig_contribution',
        'withholding_tax',
        'late_deductions',
        'undertime_deductions',
        'cash_advance_deductions',
        'other_deductions',
        'total_deductions',
        'net_pay',
        'remarks',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'daily_rate' => 'decimal:2',
        'hourly_rate' => 'decimal:8',
        'regular_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'holiday_hours' => 'decimal:2',
        'rest_day_hours' => 'decimal:2',
        'night_differential_hours' => 'decimal:2',
        'regular_pay' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'holiday_pay' => 'decimal:2',
        'rest_day_pay' => 'decimal:2',
        'night_differential_pay' => 'decimal:2',
        'allowances' => 'decimal:2',
        'bonuses' => 'decimal:2',
        'other_earnings' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'sss_contribution' => 'decimal:2',
        'philhealth_contribution' => 'decimal:2',
        'pagibig_contribution' => 'decimal:2',
        'withholding_tax' => 'decimal:2',
        'late_deductions' => 'decimal:2',
        'undertime_deductions' => 'decimal:2',
        'cash_advance_deductions' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
    ];

    /**
     * Activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['gross_pay', 'total_deductions', 'net_pay'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the payroll that owns the payroll detail.
     */
    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    /**
     * Get the employee that owns the payroll detail.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the cash advance payments for this payroll detail.
     */
    public function cashAdvancePayments()
    {
        return $this->hasMany(CashAdvancePayment::class);
    }

    /**
     * Calculate government contributions based on dynamic settings
     */
    public function calculateGovernmentContributions()
    {
        $basicSalary = $this->basic_salary;
        $grossPay = $this->gross_pay;

        // Reset contributions
        $this->sss_contribution = 0;
        $this->philhealth_contribution = 0;
        $this->pagibig_contribution = 0;

        // Only calculate if employee has benefits
        if ($this->employee && $this->employee->benefits_status === 'with_benefits') {
            // Get active government deduction settings
            $deductionSettings = \App\Models\DeductionTaxSetting::active()
                ->where('type', 'government')
                ->get();

            foreach ($deductionSettings as $setting) {
                if ($setting->tax_table_type !== 'withholding_tax') {
                    $amount = $setting->calculateDeduction($basicSalary, $this->overtime_pay, $this->bonuses, $this->allowances, $grossPay);

                    // Map to appropriate field based on code
                    switch ($setting->code) {
                        case 'sss':
                            $this->sss_contribution = $amount;
                            break;
                        case 'philhealth':
                            $this->philhealth_contribution = $amount;
                            break;
                        case 'pagibig':
                            $this->pagibig_contribution = $amount;
                            break;
                    }
                }
            }
        }
    }

    /**
     * Calculate government contributions with employer sharing logic
     */
    public function calculateGovernmentContributionsWithSharing()
    {
        $basicSalary = $this->basic_salary;
        $grossPay = $this->gross_pay;
        $taxableIncome = $this->getTaxableIncomeAttribute();

        // Reset contributions
        $this->sss_contribution = 0;
        $this->philhealth_contribution = 0;
        $this->pagibig_contribution = 0;

        // Only calculate if employee has benefits
        if ($this->employee && $this->employee->benefits_status === 'with_benefits') {
            // Get active government deduction settings
            $deductionSettings = \App\Models\DeductionTaxSetting::active()
                ->where('type', 'government')
                ->get();

            foreach ($deductionSettings as $setting) {
                if ($setting->tax_table_type !== 'withholding_tax') {
                    // Determine the salary base to use for calculation
                    $salaryBase = $this->getSalaryBaseForCalculation($setting);

                    // Calculate employee deduction amount (already considers sharing)
                    $employeeDeduction = $setting->calculateDeduction($salaryBase, $this->overtime_pay, $this->bonuses, $this->allowances, $grossPay);

                    // Map to appropriate field based on code
                    switch ($setting->code) {
                        case 'sss':
                            $this->sss_contribution = $employeeDeduction;
                            break;
                        case 'philhealth':
                            $this->philhealth_contribution = $employeeDeduction;
                            break;
                        case 'pagibig':
                            $this->pagibig_contribution = $employeeDeduction;
                            break;
                    }
                }
            }
        }
    }

    /**
     * Get the salary base for calculation based on deduction setting's pay basis
     */
    private function getSalaryBaseForCalculation($setting)
    {
        // Check if setting has pay_basis configured
        if (property_exists($setting, 'pay_basis')) {
            switch ($setting->pay_basis) {
                case 'taxable_income':
                    return $this->getTaxableIncomeAttribute();
                case 'total_gross':
                    return $this->gross_pay;
                default:
                    return $this->basic_salary;
            }
        }

        // Default to basic salary if no pay basis is configured
        return $this->basic_salary;
    }

    /**
     * Calculate withholding tax using dynamic settings
     */
    public function calculateWithholdingTax()
    {
        $this->withholding_tax = 0;

        // Only calculate if employee has benefits
        if ($this->employee && $this->employee->benefits_status === 'with_benefits') {
            // Calculate taxable income (gross pay minus government deductions)
            $taxableIncome = $this->gross_pay - $this->sss_contribution - $this->philhealth_contribution - $this->pagibig_contribution;

            // Get active withholding tax settings
            $taxSettings = \App\Models\DeductionTaxSetting::active()
                ->where('type', 'government')
                ->where('tax_table_type', 'withholding_tax')
                ->first();

            if ($taxSettings) {
                $this->withholding_tax = $taxSettings->calculateDeduction(
                    $this->basic_salary,
                    $this->overtime_pay,
                    $this->bonuses,
                    $this->allowances,
                    $this->gross_pay,
                    $taxableIncome
                );
            }
        }
    }

    /**
     * Calculate total deductions
     */
    public function calculateTotalDeductions()
    {
        $this->total_deductions = $this->sss_contribution +
            $this->philhealth_contribution +
            $this->pagibig_contribution +
            $this->withholding_tax +
            $this->late_deductions +
            $this->undertime_deductions +
            $this->cash_advance_deductions +
            $this->other_deductions;
    }

    /**
     * Calculate net pay
     */
    public function calculateNetPay()
    {
        $this->net_pay = $this->gross_pay - $this->total_deductions;
    }

    /**
     * Calculate and set cash advance deductions for this payroll period
     */
    public function calculateCashAdvanceDeductions()
    {
        $this->cash_advance_deductions = 0;

        // Find approved cash advances for this employee with outstanding balance
        $activeCashAdvances = \App\Models\CashAdvance::where('employee_id', $this->employee_id)
            ->where('status', 'approved')
            ->where('outstanding_balance', '>', 0)
            ->get();

        $totalDeduction = 0;

        foreach ($activeCashAdvances as $cashAdvance) {
            // Calculate installment amount
            $installmentAmount = $cashAdvance->approved_amount / $cashAdvance->installments;

            // Don't exceed the outstanding balance
            $deductionAmount = min($installmentAmount, $cashAdvance->outstanding_balance);

            if ($deductionAmount > 0) {
                $totalDeduction += $deductionAmount;
            }
        }

        $this->cash_advance_deductions = $totalDeduction;
    }

    /**
     * Process cash advance payments after payroll is marked as paid
     */
    public function processCashAdvancePayments()
    {
        if ($this->cash_advance_deductions <= 0) {
            return;
        }

        // Only process payments if payroll is marked as paid
        if (!$this->payroll->is_paid) {
            return;
        }

        // Find approved cash advances for this employee
        $activeCashAdvances = \App\Models\CashAdvance::where('employee_id', $this->employee_id)
            ->where('status', 'approved')
            ->where('outstanding_balance', '>', 0)
            ->get();

        foreach ($activeCashAdvances as $cashAdvance) {
            // Calculate installment amount
            $installmentAmount = $cashAdvance->approved_amount / $cashAdvance->installments;

            // Don't exceed the outstanding balance
            $deductionAmount = min($installmentAmount, $cashAdvance->outstanding_balance);

            if ($deductionAmount > 0) {
                // Record the payment
                $cashAdvance->recordPayment($deductionAmount, $this->payroll_id, $this->id);
            }
        }
    }

    /**
     * SSS contribution calculation (2025 rates)
     */
    private function calculateSSSContribution($salary)
    {
        if ($salary <= 4000) return 180;
        if ($salary <= 25000) {
            // Use bracket system - simplified for demo
            $rate = 0.045; // 4.5% employee share
            return min($salary * $rate, 1125); // Max employee contribution
        }
        return 1125; // Maximum SSS contribution
    }

    /**
     * PhilHealth contribution calculation (2025 rates)
     */
    private function calculatePhilHealthContribution($salary)
    {
        $contribution = $salary * 0.025; // 2.5% employee share of 5% total
        return min($contribution, 2500); // Maximum employee share
    }

    /**
     * Pag-IBIG contribution calculation (2025 rates)
     */
    private function calculatePagibigContribution($salary)
    {
        if ($salary <= 1500) {
            return $salary * 0.01; // 1% for salary ≤ ₱1,500
        } else {
            return min($salary * 0.02, 200); // 2% capped at ₱200
        }
    }

    /**
     * Withholding tax calculation (2025 rates)
     */
    private function calculateTax($taxableIncome)
    {
        // Monthly tax brackets (2025)
        if ($taxableIncome <= 20833) return 0; // ₱250,000 annual exemption
        if ($taxableIncome <= 33333) return ($taxableIncome - 20833) * 0.15;
        if ($taxableIncome <= 66667) return 1875 + (($taxableIncome - 33333) * 0.20);
        if ($taxableIncome <= 166667) return 8541.67 + (($taxableIncome - 66667) * 0.25);
        if ($taxableIncome <= 666667) return 33541.67 + (($taxableIncome - 166667) * 0.30);

        return 183541.67 + (($taxableIncome - 666667) * 0.35);
    }
}
