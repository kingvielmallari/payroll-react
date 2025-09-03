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

        // Get ALL government deduction settings (regardless of sharing)
        $allDeductions = DeductionTaxSetting::whereIn('name', ['SSS', 'PhilHealth', 'Pag-IBIG', 'BIR'])
            ->get();

        // Calculate totals for each deduction type
        $shareData = collect();

        foreach ($allDeductions as $deduction) {
            $columnName = match ($deduction->name) {
                'SSS' => 'sss_contribution',
                'PhilHealth' => 'philhealth_contribution',
                'Pag-IBIG' => 'pagibig_contribution',
                'BIR' => 'withholding_tax',
                default => null
            };

            if ($columnName) {
                // Get employee share from payroll snapshots
                $eeShareQuery = $query->clone()
                    ->selectRaw("
                        SUM({$columnName}) as total_ee_share,
                        COUNT(DISTINCT payroll_snapshots.payroll_id) as payroll_count,
                        COUNT(payroll_snapshots.id) as employee_count
                    ")
                    ->first();

                $eeShare = $eeShareQuery->total_ee_share ?: 0;
                
                // Calculate employer share from snapshots' employer_deductions_breakdown
                $erShare = 0;
                if ($deduction->share_with_employer) {
                    $snapshots = $query->clone()
                        ->select('payroll_snapshots.employer_deductions_breakdown')
                        ->get();
                    
                    foreach ($snapshots as $snapshot) {
                        if ($snapshot->employer_deductions_breakdown) {
                            $employerBreakdown = is_string($snapshot->employer_deductions_breakdown) 
                                ? json_decode($snapshot->employer_deductions_breakdown, true) 
                                : $snapshot->employer_deductions_breakdown;
                            
                            if (is_array($employerBreakdown)) {
                                foreach ($employerBreakdown as $breakdown) {
                                    // Match by deduction name or code
                                    if (isset($breakdown['name']) && 
                                        (strcasecmp($breakdown['name'], $deduction->name) === 0 ||
                                         strcasecmp($breakdown['name'], str_replace('-', '', $deduction->name)) === 0)) {
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
                    'total_ee_share' => $eeShare,
                    'total_er_share' => round($erShare, 2),
                    'total_combined' => $eeShare + $erShare,
                    'share_percentage' => $sharePercentage,
                    'payroll_count' => $eeShareQuery->payroll_count ?: 0,
                    'employee_count' => $eeShareQuery->employee_count ?: 0,
                ]);
            }
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
