<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DeductionTaxSetting;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function employerShares(Request $request)
    {
        // Get filter parameters
        $payrollPeriod = $request->input('payroll_period', 'all');
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', null);

        // Base query for payroll snapshots - only include PAID payrolls
        $query = \App\Models\PayrollSnapshot::join('payrolls', 'payroll_snapshots.payroll_id', '=', 'payrolls.id')
            ->whereYear('payrolls.period_start', $year)
            ->where('payrolls.is_paid', true) // Only include paid payrolls
            ->whereNotNull('payroll_snapshots.employer_deductions_breakdown'); // Only include snapshots with employer breakdown

        // Apply month filter if provided
        if ($month) {
            $query->whereMonth('payrolls.period_start', $month);
        }

        // Get ALL government deduction settings and common deductions
        $allDeductions = DeductionTaxSetting::whereIn('name', ['SSS', 'PhilHealth', 'Pag-IBIG', 'BIR', 'Withholding Tax'])
            ->get();

        // If we don't have Withholding Tax in settings, create a virtual one for display
        $withholdingTaxExists = $allDeductions->where('name', 'Withholding Tax')->first() || $allDeductions->where('name', 'BIR')->first();
        if (!$withholdingTaxExists) {
            $virtualWithholdingTax = new \stdClass();
            $virtualWithholdingTax->id = 'withholding_tax';
            $virtualWithholdingTax->name = 'Withholding Tax';
            $virtualWithholdingTax->share_with_employer = false;
            $allDeductions->push($virtualWithholdingTax);
        }

        // Calculate totals for each deduction type
        $shareData = collect();

        foreach ($allDeductions as $deduction) {
            // Get employee share from deductions_breakdown JSON field
            $eeShare = 0;
            $payrollCount = 0;
            $employeeCount = 0;

            $snapshots = $query->clone()
                ->select('payroll_snapshots.deductions_breakdown', 'payroll_snapshots.payroll_id')
                ->whereNotNull('payroll_snapshots.deductions_breakdown')
                ->get();

            $uniquePayrolls = collect();

            foreach ($snapshots as $snapshot) {
                if ($snapshot->deductions_breakdown) {
                    $deductionsBreakdown = is_string($snapshot->deductions_breakdown)
                        ? json_decode($snapshot->deductions_breakdown, true)
                        : $snapshot->deductions_breakdown;

                    if (is_array($deductionsBreakdown)) {
                        foreach ($deductionsBreakdown as $breakdown) {
                            // Improved matching logic for deduction names and codes
                            $isMatch = false;

                            if (isset($breakdown['name']) && isset($breakdown['code'])) {
                                // Direct name match
                                if (strcasecmp($breakdown['name'], $deduction->name) === 0) {
                                    $isMatch = true;
                                }
                                // Code-based matching
                                elseif (strcasecmp($breakdown['code'], strtolower(str_replace(['-', ' '], '', $deduction->name))) === 0) {
                                    $isMatch = true;
                                }
                                // Special cases for common deduction name variations
                                elseif (
                                    (strcasecmp($deduction->name, 'BIR') === 0 && strcasecmp($breakdown['name'], 'Withholding Tax') === 0) ||
                                    (strcasecmp($deduction->name, 'Withholding Tax') === 0 && strcasecmp($breakdown['code'], 'withholding_tax') === 0) ||
                                    (strcasecmp($deduction->name, 'Pag-IBIG') === 0 && strcasecmp($breakdown['code'], 'pagibig') === 0) ||
                                    (strcasecmp($deduction->name, 'PhilHealth') === 0 && strcasecmp($breakdown['code'], 'philhealth') === 0) ||
                                    (strcasecmp($deduction->name, 'SSS') === 0 && strcasecmp($breakdown['code'], 'sss') === 0)
                                ) {
                                    $isMatch = true;
                                }
                            }

                            if ($isMatch) {
                                $eeShare += $breakdown['amount'] ?? 0;
                                $employeeCount++;

                                // Track unique payrolls
                                if (!$uniquePayrolls->contains($snapshot->payroll_id)) {
                                    $uniquePayrolls->push($snapshot->payroll_id);
                                }
                            }
                        }
                    }
                }
            }

            $payrollCount = $uniquePayrolls->count();

            // Calculate employer share from snapshots' employer_deductions_breakdown
            $erShare = 0;
            if ($deduction->share_with_employer) {
                $erSnapshots = $query->clone()
                    ->select('payroll_snapshots.employer_deductions_breakdown')
                    ->whereNotNull('payroll_snapshots.employer_deductions_breakdown')
                    ->get();

                foreach ($erSnapshots as $snapshot) {
                    if ($snapshot->employer_deductions_breakdown) {
                        $employerBreakdown = is_string($snapshot->employer_deductions_breakdown)
                            ? json_decode($snapshot->employer_deductions_breakdown, true)
                            : $snapshot->employer_deductions_breakdown;

                        if (is_array($employerBreakdown)) {
                            foreach ($employerBreakdown as $breakdown) {
                                // Use the same improved matching logic
                                $isMatch = false;

                                if (isset($breakdown['name'])) {
                                    // Direct name match
                                    if (strcasecmp($breakdown['name'], $deduction->name) === 0) {
                                        $isMatch = true;
                                    }
                                    // Special cases for common deduction name variations
                                    elseif (
                                        (strcasecmp($deduction->name, 'BIR') === 0 && strcasecmp($breakdown['name'], 'Withholding Tax') === 0) ||
                                        (strcasecmp($deduction->name, 'Withholding Tax') === 0 && strcasecmp($breakdown['name'], 'Withholding Tax') === 0) ||
                                        (strcasecmp($deduction->name, 'Pag-IBIG') === 0 && strcasecmp($breakdown['name'], 'Pag-IBIG') === 0) ||
                                        (strcasecmp($deduction->name, 'PhilHealth') === 0 && strcasecmp($breakdown['name'], 'PhilHealth') === 0) ||
                                        (strcasecmp($deduction->name, 'SSS') === 0 && strcasecmp($breakdown['name'], 'SSS') === 0)
                                    ) {
                                        $isMatch = true;
                                    }
                                }

                                if ($isMatch) {
                                    $erShare += $breakdown['amount'] ?? 0;
                                }
                            }
                        }
                    }
                }
            }

            $sharePercentage = $deduction->share_with_employer ? 50 : 0;

            $shareData->push((object)[
                'id' => $deduction->id,
                'name' => $deduction->name,
                'total_ee_share' => round($eeShare, 2),
                'total_er_share' => round($erShare, 2),
                'total_combined' => round($eeShare + $erShare, 2),
                'share_percentage' => $sharePercentage,
                'payroll_count' => $payrollCount,
                'employee_count' => $employeeCount,
                'is_shared' => $deduction->share_with_employer ?? false,
                'share_status' => ($deduction->share_with_employer ?? false) ? 'Shared' : 'Not Shared',
            ]);
        }

        // Calculate grand totals
        $grandTotals = [
            'total_ee_share' => $shareData->sum('total_ee_share'),
            'total_er_share' => $shareData->sum('total_er_share'),
            'total_combined' => $shareData->sum('total_combined'),
            'total_payrolls' => $shareData->max('payroll_count'),
            'total_employees' => $shareData->sum('employee_count')
        ];

        // Get available years for filter
        $availableYears = Payroll::selectRaw('YEAR(period_start) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        return view('reports.employer-shares', compact(
            'shareData',
            'grandTotals',
            'payrollPeriod',
            'year',
            'month',
            'availableYears'
        ));
    }
}
