<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollDetail;
use App\Models\Payroll;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BIR1601CExport;
use Carbon\Carbon;

class BIR1601CService
{
    /**
     * Generate BIR 1601C data for a specific month and year.
     */
    public function generateData($year, $month)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Get all payrolls for the specified period
        $payrolls = Payroll::whereBetween('pay_period_start', [$startDate, $endDate])
                          ->with(['payrollDetails.employee'])
                          ->get();

        $totalGrossPay = 0;
        $totalTaxWithheld = 0;
        $totalNetPay = 0;
        $employeeCount = 0;
        $employeeData = [];

        foreach ($payrolls as $payroll) {
            foreach ($payroll->payrollDetails as $detail) {
                $employee = $detail->employee;
                
                if (!$employee) continue;

                $employeeId = $employee->id;
                
                if (!isset($employeeData[$employeeId])) {
                    $employeeData[$employeeId] = [
                        'employee' => $employee,
                        'total_gross' => 0,
                        'total_tax_withheld' => 0,
                        'total_net' => 0,
                        'monthly_gross' => 0,
                    ];
                    $employeeCount++;
                }

                $employeeData[$employeeId]['total_gross'] += $detail->gross_pay;
                $employeeData[$employeeId]['total_tax_withheld'] += $detail->tax_withheld;
                $employeeData[$employeeId]['total_net'] += $detail->net_pay;
                $employeeData[$employeeId]['monthly_gross'] = $detail->gross_pay;

                $totalGrossPay += $detail->gross_pay;
                $totalTaxWithheld += $detail->tax_withheld;
                $totalNetPay += $detail->net_pay;
            }
        }

        return [
            'period' => [
                'year' => $year,
                'month' => $month,
                'month_name' => Carbon::create($year, $month)->format('F'),
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'summary' => [
                'total_gross_pay' => $totalGrossPay,
                'total_tax_withheld' => $totalTaxWithheld,
                'total_net_pay' => $totalNetPay,
                'employee_count' => $employeeCount,
            ],
            'employees' => $employeeData,
            'payrolls' => $payrolls,
        ];
    }

    /**
     * Generate annual data for BIR 1604C.
     */
    public function generateAnnualData($year)
    {
        $startDate = Carbon::create($year, 1, 1)->startOfYear();
        $endDate = $startDate->copy()->endOfYear();

        // Get all payrolls for the year
        $payrolls = Payroll::whereBetween('pay_period_start', [$startDate, $endDate])
                          ->with(['payrollDetails.employee'])
                          ->get();

        $monthlyData = [];
        $annualTotals = [
            'total_gross_pay' => 0,
            'total_tax_withheld' => 0,
            'total_net_pay' => 0,
            'total_employees' => 0,
        ];

        // Group by months
        for ($month = 1; $month <= 12; $month++) {
            $monthData = $this->generateData($year, $month);
            $monthlyData[$month] = $monthData;
            
            $annualTotals['total_gross_pay'] += $monthData['summary']['total_gross_pay'];
            $annualTotals['total_tax_withheld'] += $monthData['summary']['total_tax_withheld'];
            $annualTotals['total_net_pay'] += $monthData['summary']['total_net_pay'];
        }

        // Count unique employees for the year
        $uniqueEmployees = collect($payrolls->pluck('payrollDetails')->flatten())
                          ->pluck('employee_id')
                          ->unique()
                          ->count();
        
        $annualTotals['total_employees'] = $uniqueEmployees;

        return [
            'year' => $year,
            'monthly_data' => $monthlyData,
            'annual_totals' => $annualTotals,
        ];
    }

    /**
     * Download BIR 1601C as PDF.
     */
    public function downloadPDF($data, $year, $month)
    {
        $pdf = Pdf::loadView('government-forms.pdf.bir-1601c', compact('data', 'year', 'month'));
        $filename = "BIR_1601C_{$year}_{$month}.pdf";
        
        return $pdf->download($filename);
    }

    /**
     * Download BIR 1601C as Excel.
     */
    public function downloadExcel($data, $year, $month)
    {
        $filename = "BIR_1601C_{$year}_{$month}.xlsx";
        
        return Excel::download(new BIR1601CExport($data), $filename);
    }

    /**
     * Download annual BIR 1604C as PDF.
     */
    public function downloadAnnualPDF($data, $year)
    {
        $pdf = Pdf::loadView('government-forms.pdf.bir-1604c', compact('data', 'year'));
        $filename = "BIR_1604C_{$year}.pdf";
        
        return $pdf->download($filename);
    }

    /**
     * Download annual BIR 1604C as Excel.
     */
    public function downloadAnnualExcel($data, $year)
    {
        $filename = "BIR_1604C_{$year}.xlsx";
        
        return Excel::download(new BIR1604CExport($data), $filename);
    }

    /**
     * Get tax computation breakdown for an employee.
     */
    public function getTaxBreakdown($employee, $grossPay)
    {
        // Simplified tax computation (you may need to implement more complex logic)
        $monthlyExemption = 25000; // PHP 25,000 monthly exemption for single
        $taxableIncome = max(0, $grossPay - $monthlyExemption);
        
        $tax = 0;
        
        // Tax brackets (simplified - 2025 rates)
        if ($taxableIncome <= 33333) {
            $tax = 0;
        } elseif ($taxableIncome <= 166667) {
            $tax = ($taxableIncome - 33333) * 0.15;
        } elseif ($taxableIncome <= 666667) {
            $tax = 20000 + (($taxableIncome - 166667) * 0.20);
        } elseif ($taxableIncome <= 1666667) {
            $tax = 120000 + (($taxableIncome - 666667) * 0.25);
        } elseif ($taxableIncome <= 8333333) {
            $tax = 370000 + (($taxableIncome - 1666667) * 0.30);
        } else {
            $tax = 2370000 + (($taxableIncome - 8333333) * 0.35);
        }

        return [
            'gross_pay' => $grossPay,
            'exemption' => $monthlyExemption,
            'taxable_income' => $taxableIncome,
            'tax_due' => $tax,
        ];
    }
}
