<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollDetail;
use App\Models\Payroll;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class PhilHealthReportService
{
    /**
     * Generate PhilHealth RF-1 report data.
     */
    public function generateRF1Data($year, $month)
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
            'total_premium' => 0,
            'employee_share' => 0,
            'employer_share' => 0,
            'employee_count' => 0,
        ];

        foreach ($payrolls as $payroll) {
            foreach ($payroll->payrollDetails as $detail) {
                $employee = $detail->employee;

                if (!$employee || !$employee->philhealth_number) continue;

                $employeeId = $employee->id;

                if (!isset($employeeData[$employeeId])) {
                    $premiumData = $this->calculatePhilHealthPremium($detail->gross_pay);

                    $employeeData[$employeeId] = [
                        'employee' => $employee,
                        'philhealth_number' => $employee->philhealth_number,
                        'monthly_basic_salary' => $detail->gross_pay,
                        'premium_contribution' => $premiumData['total_premium'],
                        'employee_share' => $premiumData['employee_share'],
                        'employer_share' => $premiumData['employer_share'],
                    ];

                    $totals['total_premium'] += $premiumData['total_premium'];
                    $totals['employee_share'] += $premiumData['employee_share'];
                    $totals['employer_share'] += $premiumData['employer_share'];
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
     * Download PhilHealth RF-1 as PDF.
     */
    public function downloadPDF($data, $year, $month)
    {
        $pdf = Pdf::loadView('government-forms.pdf.philhealth-rf1', compact('data', 'year', 'month'));
        $filename = "PhilHealth_RF1_{$year}_{$month}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Download PhilHealth RF-1 as Excel.
     */
    public function downloadExcel($data, $year, $month)
    {
        $filename = "PhilHealth_RF1_{$year}_{$month}.xlsx";

        return Excel::download(new PhilHealthRF1Export($data), $filename);
    }

    /**
     * Calculate PhilHealth premium based on monthly basic salary.
     */
    private function calculatePhilHealthPremium($monthlyBasicSalary)
    {
        // PhilHealth Premium Rates (2025)
        $minimumPremium = 10000; // Minimum monthly basic salary for premium calculation
        $maximumPremium = 100000; // Maximum monthly basic salary for premium calculation
        $premiumRate = 0.04; // 4% total premium rate

        // Determine the premium base
        $premiumBase = max($minimumPremium, min($monthlyBasicSalary, $maximumPremium));

        // Calculate total premium
        $totalPremium = $premiumBase * $premiumRate;

        // Split between employee and employer (50/50)
        $employeeShare = $totalPremium / 2;
        $employerShare = $totalPremium / 2;

        return [
            'premium_base' => $premiumBase,
            'total_premium' => round($totalPremium, 2),
            'employee_share' => round($employeeShare, 2),
            'employer_share' => round($employerShare, 2),
        ];
    }

    /**
     * Get employer information.
     */
    private function getEmployerInfo()
    {
        return [
            'pen' => config('company.philhealth_pen', '00000000000'),
            'company_name' => config('company.name', 'Your Company Name'),
            'address' => config('company.address', 'Your Company Address'),
            'contact_number' => config('company.contact_number', '(02) 000-0000'),
            'email' => config('company.email', 'hr@company.com'),
        ];
    }

    /**
     * Generate annual PhilHealth summary.
     */
    public function generateAnnualSummary($year)
    {
        $monthlyData = [];
        $annualTotals = [
            'total_premium' => 0,
            'employee_share' => 0,
            'employer_share' => 0,
        ];

        for ($month = 1; $month <= 12; $month++) {
            $monthData = $this->generateRF1Data($year, $month);
            $monthlyData[$month] = $monthData;

            $annualTotals['total_premium'] += $monthData['totals']['total_premium'];
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
     * Generate individual employee PhilHealth contribution history.
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
        $totalAnnualPremium = 0;

        for ($month = 1; $month <= 12; $month++) {
            $monthlyDetails = $payrollDetails->filter(function ($detail) use ($month) {
                return $detail->payroll->pay_period_start->month == $month;
            });

            if ($monthlyDetails->isNotEmpty()) {
                $averageGrossPay = $monthlyDetails->avg('gross_pay');
                $premiumData = $this->calculatePhilHealthPremium($averageGrossPay);

                $monthlyContributions[$month] = [
                    'month' => Carbon::create(2025, $month)->format('F'),
                    'gross_pay' => $averageGrossPay,
                    'premium_data' => $premiumData,
                ];

                $totalAnnualPremium += $premiumData['total_premium'];
            } else {
                $monthlyContributions[$month] = [
                    'month' => Carbon::create(2025, $month)->format('F'),
                    'gross_pay' => 0,
                    'premium_data' => [
                        'premium_base' => 0,
                        'total_premium' => 0,
                        'employee_share' => 0,
                        'employer_share' => 0,
                    ],
                ];
            }
        }

        return [
            'employee' => $employee,
            'year' => $year,
            'monthly_contributions' => $monthlyContributions,
            'annual_totals' => [
                'total_premium' => $totalAnnualPremium,
                'employee_share' => $totalAnnualPremium / 2,
                'employer_share' => $totalAnnualPremium / 2,
            ],
        ];
    }
}
