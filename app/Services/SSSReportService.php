<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollDetail;
use App\Models\Payroll;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class SSSReportService
{
    /**
     * Generate SSS R-3 report data.
     */
    public function generateR3Data($year, $month)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Get all payrolls for the specified period
        $payrolls = Payroll::whereBetween('pay_period_start', [$startDate, $endDate])
                          ->with(['payrollDetails.employee'])
                          ->get();

        $employeeData = [];
        $totals = [
            'total_salary_credit' => 0,
            'employee_contribution' => 0,
            'employer_contribution' => 0,
            'total_contribution' => 0,
            'employee_count' => 0,
        ];

        foreach ($payrolls as $payroll) {
            foreach ($payroll->payrollDetails as $detail) {
                $employee = $detail->employee;
                
                if (!$employee || !$employee->sss_number) continue;

                $employeeId = $employee->id;
                
                if (!isset($employeeData[$employeeId])) {
                    $salaryCredit = $this->getSSSalaryCredit($detail->gross_pay);
                    $contributions = $this->calculateSSSContributions($salaryCredit);

                    $employeeData[$employeeId] = [
                        'employee' => $employee,
                        'sss_number' => $employee->sss_number,
                        'salary_credit' => $salaryCredit,
                        'employee_contribution' => $contributions['employee'],
                        'employer_contribution' => $contributions['employer'],
                        'total_contribution' => $contributions['total'],
                        'gross_pay' => $detail->gross_pay,
                    ];

                    $totals['total_salary_credit'] += $salaryCredit;
                    $totals['employee_contribution'] += $contributions['employee'];
                    $totals['employer_contribution'] += $contributions['employer'];
                    $totals['total_contribution'] += $contributions['total'];
                    $totals['employee_count']++;
                }
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
            'employees' => $employeeData,
            'totals' => $totals,
            'employer_info' => $this->getEmployerInfo(),
        ];
    }

    /**
     * Download SSS R-3 as PDF.
     */
    public function downloadPDF($data, $year, $month)
    {
        $pdf = Pdf::loadView('government-forms.pdf.sss-r3', compact('data', 'year', 'month'));
        $filename = "SSS_R3_{$year}_{$month}.pdf";
        
        return $pdf->download($filename);
    }

    /**
     * Download SSS R-3 as Excel.
     */
    public function downloadExcel($data, $year, $month)
    {
        $filename = "SSS_R3_{$year}_{$month}.xlsx";
        
        return Excel::download(new SSSR3Export($data), $filename);
    }

    /**
     * Get SSS Salary Credit based on gross pay.
     */
    private function getSSSalaryCredit($grossPay)
    {
        // SSS Salary Credit Table (2025 rates)
        $salaryBrackets = [
            ['min' => 0, 'max' => 3250, 'credit' => 3000],
            ['min' => 3250, 'max' => 3750, 'credit' => 3500],
            ['min' => 3750, 'max' => 4250, 'credit' => 4000],
            ['min' => 4250, 'max' => 4750, 'credit' => 4500],
            ['min' => 4750, 'max' => 5250, 'credit' => 5000],
            ['min' => 5250, 'max' => 5750, 'credit' => 5500],
            ['min' => 5750, 'max' => 6250, 'credit' => 6000],
            ['min' => 6250, 'max' => 6750, 'credit' => 6500],
            ['min' => 6750, 'max' => 7250, 'credit' => 7000],
            ['min' => 7250, 'max' => 7750, 'credit' => 7500],
            ['min' => 7750, 'max' => 8250, 'credit' => 8000],
            ['min' => 8250, 'max' => 8750, 'credit' => 8500],
            ['min' => 8750, 'max' => 9250, 'credit' => 9000],
            ['min' => 9250, 'max' => 9750, 'credit' => 9500],
            ['min' => 9750, 'max' => 10250, 'credit' => 10000],
            ['min' => 10250, 'max' => 10750, 'credit' => 10500],
            ['min' => 10750, 'max' => 11250, 'credit' => 11000],
            ['min' => 11250, 'max' => 11750, 'credit' => 11500],
            ['min' => 11750, 'max' => 12250, 'credit' => 12000],
            ['min' => 12250, 'max' => 12750, 'credit' => 12500],
            ['min' => 12750, 'max' => 13250, 'credit' => 13000],
            ['min' => 13250, 'max' => 13750, 'credit' => 13500],
            ['min' => 13750, 'max' => 14250, 'credit' => 14000],
            ['min' => 14250, 'max' => 14750, 'credit' => 14500],
            ['min' => 14750, 'max' => 15250, 'credit' => 15000],
            ['min' => 15250, 'max' => 15750, 'credit' => 15500],
            ['min' => 15750, 'max' => 16250, 'credit' => 16000],
            ['min' => 16250, 'max' => 16750, 'credit' => 16500],
            ['min' => 16750, 'max' => 17250, 'credit' => 17000],
            ['min' => 17250, 'max' => 17750, 'credit' => 17500],
            ['min' => 17750, 'max' => 18250, 'credit' => 18000],
            ['min' => 18250, 'max' => 18750, 'credit' => 18500],
            ['min' => 18750, 'max' => 19250, 'credit' => 19000],
            ['min' => 19250, 'max' => 19750, 'credit' => 19500],
            ['min' => 19750, 'max' => 999999, 'credit' => 20000], // Maximum
        ];

        foreach ($salaryBrackets as $bracket) {
            if ($grossPay >= $bracket['min'] && $grossPay <= $bracket['max']) {
                return $bracket['credit'];
            }
        }

        return 20000; // Maximum salary credit
    }

    /**
     * Calculate SSS contributions based on salary credit.
     */
    private function calculateSSSContributions($salaryCredit)
    {
        // SSS contribution rate: 12% (4.5% employee, 7.5% employer)
        $totalRate = 0.12;
        $employeeRate = 0.045;
        $employerRate = 0.075;

        $totalContribution = $salaryCredit * $totalRate;
        $employeeContribution = $salaryCredit * $employeeRate;
        $employerContribution = $salaryCredit * $employerRate;

        return [
            'employee' => round($employeeContribution, 2),
            'employer' => round($employerContribution, 2),
            'total' => round($totalContribution, 2),
        ];
    }

    /**
     * Get employer information.
     */
    private function getEmployerInfo()
    {
        return [
            'sss_number' => config('company.sss_number', '00-0000000-0'),
            'company_name' => config('company.name', 'Your Company Name'),
            'address' => config('company.address', 'Your Company Address'),
            'contact_number' => config('company.contact_number', '(02) 000-0000'),
        ];
    }

    /**
     * Generate annual SSS summary.
     */
    public function generateAnnualSummary($year)
    {
        $monthlyData = [];
        $annualTotals = [
            'total_salary_credit' => 0,
            'employee_contribution' => 0,
            'employer_contribution' => 0,
            'total_contribution' => 0,
        ];

        for ($month = 1; $month <= 12; $month++) {
            $monthData = $this->generateR3Data($year, $month);
            $monthlyData[$month] = $monthData;
            
            $annualTotals['total_salary_credit'] += $monthData['totals']['total_salary_credit'];
            $annualTotals['employee_contribution'] += $monthData['totals']['employee_contribution'];
            $annualTotals['employer_contribution'] += $monthData['totals']['employer_contribution'];
            $annualTotals['total_contribution'] += $monthData['totals']['total_contribution'];
        }

        return [
            'year' => $year,
            'monthly_data' => $monthlyData,
            'annual_totals' => $annualTotals,
            'employer_info' => $this->getEmployerInfo(),
        ];
    }
}
