<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollDetail;
use App\Models\Payroll;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class PagibigReportService
{
    /**
     * Generate Pag-IBIG MCRF report data.
     */
    public function generateMCRFData($year, $month)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Get all PAID payrolls for the specified period
        $payrolls = Payroll::whereBetween('pay_period_start', [$startDate, $endDate])
            ->where('is_paid', true) // Only include paid payrolls
            ->with(['payrollDetails.employee'])
            ->get();

        $employeeData = [];
        $totals = [
            'total_contribution' => 0,
            'employee_share' => 0,
            'employer_share' => 0,
            'employee_count' => 0,
        ];

        foreach ($payrolls as $payroll) {
            foreach ($payroll->payrollDetails as $detail) {
                $employee = $detail->employee;

                if (!$employee || !$employee->pagibig_number) continue;

                $employeeId = $employee->id;

                if (!isset($employeeData[$employeeId])) {
                    $contributionData = $this->calculatePagibigContribution($detail->gross_pay);

                    $employeeData[$employeeId] = [
                        'employee' => $employee,
                        'pagibig_number' => $employee->pagibig_number,
                        'monthly_compensation' => $detail->gross_pay,
                        'employee_contribution' => $contributionData['employee_share'],
                        'employer_contribution' => $contributionData['employer_share'],
                        'total_contribution' => $contributionData['total_contribution'],
                    ];

                    $totals['total_contribution'] += $contributionData['total_contribution'];
                    $totals['employee_share'] += $contributionData['employee_share'];
                    $totals['employer_share'] += $contributionData['employer_share'];
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
     * Download Pag-IBIG MCRF as PDF.
     */
    public function downloadPDF($data, $year, $month)
    {
        $pdf = Pdf::loadView('government-forms.pdf.pagibig-mcrf', compact('data', 'year', 'month'));
        $filename = "PagIBIG_MCRF_{$year}_{$month}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Download Pag-IBIG MCRF as Excel.
     */
    public function downloadExcel($data, $year, $month)
    {
        $filename = "PagIBIG_MCRF_{$year}_{$month}.xlsx";

        return Excel::download(new PagibigMCRFExport($data), $filename);
    }

    /**
     * Calculate Pag-IBIG contribution based on monthly compensation.
     */
    private function calculatePagibigContribution($monthlyCompensation)
    {
        // Pag-IBIG Contribution Rates (2025)
        $employeeRate = 0.01; // 1% for employee (minimum)
        $employerRate = 0.02; // 2% for employer

        // Employee contribution: 1% of monthly compensation (min: 100, max: 100)
        $employeeContribution = max(100, min($monthlyCompensation * $employeeRate, 100));

        // Employer contribution: 2% of monthly compensation
        $employerContribution = $monthlyCompensation * $employerRate;

        // For employees earning 1,500 and below, both employee and employer contribute 1% each
        if ($monthlyCompensation <= 1500) {
            $employeeContribution = $monthlyCompensation * $employeeRate;
            $employerContribution = $monthlyCompensation * $employeeRate;
        }

        $totalContribution = $employeeContribution + $employerContribution;

        return [
            'monthly_compensation' => $monthlyCompensation,
            'employee_share' => round($employeeContribution, 2),
            'employer_share' => round($employerContribution, 2),
            'total_contribution' => round($totalContribution, 2),
        ];
    }

    /**
     * Get employer information.
     */
    private function getEmployerInfo()
    {
        return [
            'ern' => config('company.pagibig_ern', '000000000000'),
            'company_name' => config('company.name', 'Your Company Name'),
            'address' => config('company.address', 'Your Company Address'),
            'contact_number' => config('company.contact_number', '(02) 000-0000'),
            'email' => config('company.email', 'hr@company.com'),
        ];
    }

    /**
     * Generate annual Pag-IBIG summary.
     */
    public function generateAnnualSummary($year)
    {
        $monthlyData = [];
        $annualTotals = [
            'total_contribution' => 0,
            'employee_share' => 0,
            'employer_share' => 0,
        ];

        for ($month = 1; $month <= 12; $month++) {
            $monthData = $this->generateMCRFData($year, $month);
            $monthlyData[$month] = $monthData;

            $annualTotals['total_contribution'] += $monthData['totals']['total_contribution'];
            $annualTotals['employee_share'] += $monthData['totals']['employee_share'];
            $annualTotals['employer_share'] += $monthData['totals']['employer_share'];
        }

        return [
            'year' => $year,
            'monthly_data' => $monthlyData,
            'annual_totals' => $annualTotals,
            'employer_info' => $this->getEmployerInfo(),
        ];
    }

    /**
     * Generate individual employee Pag-IBIG contribution history.
     */
    public function generateEmployeeHistory(Employee $employee, $year)
    {
        $startDate = Carbon::create($year, 1, 1)->startOfYear();
        $endDate = $startDate->copy()->endOfYear();

        $payrollDetails = PayrollDetail::where('employee_id', $employee->id)
            ->whereHas('payroll', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('pay_period_start', [$startDate, $endDate]);
            })
            ->with('payroll')
            ->get();

        $monthlyContributions = [];
        $totalAnnualContribution = 0;

        for ($month = 1; $month <= 12; $month++) {
            $monthlyDetails = $payrollDetails->filter(function ($detail) use ($month) {
                return $detail->payroll->pay_period_start->month == $month;
            });

            if ($monthlyDetails->isNotEmpty()) {
                $averageGrossPay = $monthlyDetails->avg('gross_pay');
                $contributionData = $this->calculatePagibigContribution($averageGrossPay);

                $monthlyContributions[$month] = [
                    'month' => Carbon::create(2025, $month)->format('F'),
                    'gross_pay' => $averageGrossPay,
                    'contribution_data' => $contributionData,
                ];

                $totalAnnualContribution += $contributionData['total_contribution'];
            } else {
                $monthlyContributions[$month] = [
                    'month' => Carbon::create(2025, $month)->format('F'),
                    'gross_pay' => 0,
                    'contribution_data' => [
                        'monthly_compensation' => 0,
                        'employee_share' => 0,
                        'employer_share' => 0,
                        'total_contribution' => 0,
                    ],
                ];
            }
        }

        return [
            'employee' => $employee,
            'year' => $year,
            'monthly_contributions' => $monthlyContributions,
            'annual_totals' => [
                'total_contribution' => $totalAnnualContribution,
                'employee_share' => collect($monthlyContributions)->sum('contribution_data.employee_share'),
                'employer_share' => collect($monthlyContributions)->sum('contribution_data.employer_share'),
            ],
        ];
    }

    /**
     * Generate Pag-IBIG loan eligibility report.
     */
    public function generateLoanEligibilityReport(Employee $employee)
    {
        // Calculate total contributions for the last 24 months
        $startDate = now()->subMonths(24)->startOfMonth();
        $endDate = now()->endOfMonth();

        $payrollDetails = PayrollDetail::where('employee_id', $employee->id)
            ->whereHas('payroll', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('pay_period_start', [$startDate, $endDate]);
            })
            ->with('payroll')
            ->get();

        $monthlyContributions = [];
        $totalContributions = 0;
        $contributionMonths = 0;

        foreach ($payrollDetails as $detail) {
            $contributionData = $this->calculatePagibigContribution($detail->gross_pay);
            $monthlyContributions[] = [
                'month' => $detail->payroll->pay_period_start->format('F Y'),
                'contribution' => $contributionData['total_contribution'],
            ];

            $totalContributions += $contributionData['total_contribution'];
            $contributionMonths++;
        }

        // Loan eligibility criteria
        $minMonths = 24; // Must have contributed for at least 24 months
        $isEligible = $contributionMonths >= $minMonths;

        // Estimate loan amount (typically 80 times the monthly contribution)
        $averageMonthlyContribution = $contributionMonths > 0 ? $totalContributions / $contributionMonths : 0;
        $estimatedLoanAmount = $averageMonthlyContribution * 80;

        return [
            'employee' => $employee,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'contribution_summary' => [
                'total_contributions' => $totalContributions,
                'contribution_months' => $contributionMonths,
                'average_monthly_contribution' => $averageMonthlyContribution,
            ],
            'loan_eligibility' => [
                'is_eligible' => $isEligible,
                'minimum_months_required' => $minMonths,
                'estimated_loan_amount' => $estimatedLoanAmount,
            ],
            'monthly_contributions' => $monthlyContributions,
        ];
    }
}
