<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollDetail;
use App\Models\Payroll;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BIR2316Service
{
    /**
     * Generate BIR 2316 data for a specific employee and year.
     */
    public function generateForEmployee(Employee $employee, $year)
    {
        $startDate = Carbon::create($year, 1, 1)->startOfYear();
        $endDate = $startDate->copy()->endOfYear();

        // Get all payroll details for the employee for the year
        $payrollDetails = PayrollDetail::where('employee_id', $employee->id)
                                     ->whereHas('payroll', function($query) use ($startDate, $endDate) {
                                         $query->whereBetween('pay_period_start', [$startDate, $endDate]);
                                     })
                                     ->with('payroll')
                                     ->get();

        $monthlyData = [];
        $totals = [
            'gross_compensation' => 0,
            'basic_salary' => 0,
            'overtime_pay' => 0,
            'night_differential' => 0,
            'holiday_pay' => 0,
            'hazard_pay' => 0,
            'allowances' => 0,
            'bonuses' => 0,
            'other_compensation' => 0,
            'total_tax_withheld' => 0,
            'sss_contributions' => 0,
            'philhealth_contributions' => 0,
            'pagibig_contributions' => 0,
            'total_contributions' => 0,
            'net_pay' => 0,
        ];

        // Group by months
        for ($month = 1; $month <= 12; $month++) {
            $monthlyDetails = $payrollDetails->filter(function($detail) use ($month) {
                return $detail->payroll->pay_period_start->month == $month;
            });

            $monthData = [
                'month' => $month,
                'month_name' => Carbon::create($year, $month)->format('F'),
                'gross_compensation' => $monthlyDetails->sum('gross_pay'),
                'basic_salary' => $monthlyDetails->sum('basic_pay'),
                'overtime_pay' => $monthlyDetails->sum('overtime_pay'),
                'allowances' => $monthlyDetails->sum('allowances'),
                'bonuses' => $monthlyDetails->sum('bonuses'),
                'tax_withheld' => $monthlyDetails->sum('tax_withheld'),
                'sss_contribution' => $monthlyDetails->sum('sss_contribution'),
                'philhealth_contribution' => $monthlyDetails->sum('philhealth_contribution'),
                'pagibig_contribution' => $monthlyDetails->sum('pagibig_contribution'),
                'net_pay' => $monthlyDetails->sum('net_pay'),
            ];

            $monthlyData[$month] = $monthData;

            // Add to totals
            $totals['gross_compensation'] += $monthData['gross_compensation'];
            $totals['basic_salary'] += $monthData['basic_salary']; 
            $totals['overtime_pay'] += $monthData['overtime_pay'];
            $totals['allowances'] += $monthData['allowances'];
            $totals['bonuses'] += $monthData['bonuses'];
            $totals['total_tax_withheld'] += $monthData['tax_withheld'];
            $totals['sss_contributions'] += $monthData['sss_contribution'];
            $totals['philhealth_contributions'] += $monthData['philhealth_contribution'];
            $totals['pagibig_contributions'] += $monthData['pagibig_contribution'];
            $totals['net_pay'] += $monthData['net_pay'];
        }

        $totals['total_contributions'] = $totals['sss_contributions'] + 
                                       $totals['philhealth_contributions'] + 
                                       $totals['pagibig_contributions'];

        return [
            'employee' => $employee,
            'year' => $year,
            'monthly_data' => $monthlyData,
            'totals' => $totals,
            'employer_info' => $this->getEmployerInfo(),
        ];
    }

    /**
     * Download BIR 2316 PDF for specific employee.
     */
    public function downloadPDF(Employee $employee, $data, $year)
    {
        $pdf = Pdf::loadView('government-forms.pdf.bir-2316', compact('employee', 'data', 'year'));
        $filename = "BIR_2316_{$employee->employee_number}_{$year}.pdf";
        
        return $pdf->download($filename);
    }

    /**
     * Download all BIR 2316 forms for all employees.
     */
    public function downloadAllPDF($year)
    {
        $employees = Employee::active()->get();
        $pdf = Pdf::loadView('government-forms.pdf.bir-2316-all', compact('employees', 'year'));
        $filename = "BIR_2316_All_Employees_{$year}.pdf";
        
        return $pdf->download($filename);
    }

    /**
     * Get employer information.
     */
    private function getEmployerInfo()
    {
        return [
            'tin' => config('company.tin', '000-000-000-000'),
            'company_name' => config('company.name', 'Your Company Name'),
            'address' => config('company.address', 'Your Company Address'),
            'zip_code' => config('company.zip_code', '0000'),
            'rdo_code' => config('company.rdo_code', '000'),
        ];
    }

    /**
     * Generate summary for all employees.
     */
    public function generateSummaryForAllEmployees($year)
    {
        $employees = Employee::active()->get();
        $summaryData = [];
        
        $grandTotals = [
            'gross_compensation' => 0,
            'total_tax_withheld' => 0,
            'total_contributions' => 0,
            'net_pay' => 0,
        ];

        foreach ($employees as $employee) {
            $employeeData = $this->generateForEmployee($employee, $year);
            $summaryData[] = [
                'employee' => $employee,
                'totals' => $employeeData['totals'],
            ];

            $grandTotals['gross_compensation'] += $employeeData['totals']['gross_compensation'];
            $grandTotals['total_tax_withheld'] += $employeeData['totals']['total_tax_withheld'];
            $grandTotals['total_contributions'] += $employeeData['totals']['total_contributions'];
            $grandTotals['net_pay'] += $employeeData['totals']['net_pay'];
        }

        return [
            'year' => $year,
            'employees' => $summaryData,
            'grand_totals' => $grandTotals,
            'employer_info' => $this->getEmployerInfo(),
        ];
    }

    /**
     * Calculate tax due for employee (for verification purposes).
     */
    public function calculateAnnualTax($grossAnnualPay)
    {
        // Annual tax computation for Philippines (2025 rates)
        $annualExemption = 300000; // PHP 300,000 annual exemption
        $taxableIncome = max(0, $grossAnnualPay - $annualExemption);
        
        $tax = 0;
        
        if ($taxableIncome <= 400000) {
            $tax = 0;
        } elseif ($taxableIncome <= 2000000) {
            $tax = ($taxableIncome - 400000) * 0.15;
        } elseif ($taxableIncome <= 8000000) {
            $tax = 240000 + (($taxableIncome - 2000000) * 0.20);
        } elseif ($taxableIncome <= 20000000) {
            $tax = 1440000 + (($taxableIncome - 8000000) * 0.25);
        } elseif ($taxableIncome <= 100000000) {
            $tax = 4440000 + (($taxableIncome - 20000000) * 0.30);
        } else {
            $tax = 28440000 + (($taxableIncome - 100000000) * 0.35);
        }

        return [
            'gross_annual_pay' => $grossAnnualPay,
            'exemption' => $annualExemption,
            'taxable_income' => $taxableIncome,
            'tax_due' => $tax,
        ];
    }
}
