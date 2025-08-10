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
        
        // Base query for payroll details
        $query = PayrollDetail::join('payrolls', 'payroll_details.payroll_id', '=', 'payrolls.id')
            ->whereYear('payrolls.period_start', $year);
        
        // Apply month filter if provided
        if ($month) {
            $query->whereMonth('payrolls.period_start', $month);
        }
        
        // Get government deduction settings that are shared with employer
        $sharedDeductions = DeductionTaxSetting::where('share_with_employer', true)
            ->whereIn('tax_table_type', ['sss', 'philhealth', 'pagibig'])
            ->get();
        
        // Calculate totals for each shared deduction type
        $shareData = collect();
        
        foreach ($sharedDeductions as $deduction) {
            $columnName = match($deduction->name) {
                'SSS' => 'sss_contribution',
                'PhilHealth' => 'philhealth_contribution',
                'Pag-IBIG' => 'pagibig_contribution',
                default => null
            };
            
            if ($columnName) {
                $totals = $query->clone()
                    ->selectRaw("
                        SUM({$columnName}) as total_ee_share,
                        COUNT(DISTINCT payroll_details.payroll_id) as payroll_count,
                        COUNT(payroll_details.id) as employee_count
                    ")
                    ->first();
                
                $shareData->push((object)[
                    'id' => $deduction->id,
                    'name' => $deduction->name,
                    'total_ee_share' => $totals->total_ee_share ?: 0,
                    'total_er_share' => $totals->total_ee_share ?: 0, // Equal sharing
                    'total_combined' => ($totals->total_ee_share ?: 0) * 2,
                    'share_percentage' => 50,
                    'payroll_count' => $totals->payroll_count ?: 0,
                    'employee_count' => $totals->employee_count ?: 0,
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
