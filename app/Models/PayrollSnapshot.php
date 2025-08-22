<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_id',
        'employee_id',
        'employee_number',
        'employee_name',
        'department',
        'position',
        'basic_salary',
        'daily_rate',
        'hourly_rate',
        'days_worked',
        'regular_hours',
        'overtime_hours',
        'holiday_hours',
        'night_differential_hours',
        'regular_pay',
        'overtime_pay',
        'holiday_pay',
        'night_differential_pay',
        'basic_breakdown',
        'holiday_breakdown',
        'rest_breakdown',
        'overtime_breakdown',
        'allowances_breakdown',
        'allowances_total',
        'bonuses_breakdown',
        'bonuses_total',
        'other_earnings',
        'gross_pay',
        'deductions_breakdown',
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
        'settings_snapshot',
        'remarks',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'daily_rate' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'days_worked' => 'decimal:2',
        'regular_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'holiday_hours' => 'decimal:2',
        'night_differential_hours' => 'decimal:2',
        'regular_pay' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'holiday_pay' => 'decimal:2',
        'night_differential_pay' => 'decimal:2',
        'basic_breakdown' => 'array',
        'holiday_breakdown' => 'array',
        'rest_breakdown' => 'array',
        'overtime_breakdown' => 'array',
        'allowances_breakdown' => 'array',
        'allowances_total' => 'decimal:2',
        'bonuses_breakdown' => 'array',
        'bonuses_total' => 'decimal:2',
        'other_earnings' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'deductions_breakdown' => 'array',
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
        'settings_snapshot' => 'array',
    ];

    /**
     * Get the payroll that owns the snapshot
     */
    public function payroll()
    {
        return $this->belongsTo(Payroll::class);
    }

    /**
     * Get the employee that owns the snapshot
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
