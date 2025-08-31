<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\Employee;
use App\Models\TimeLog;
use App\Models\PayScheduleSetting;
use App\Models\CashAdvance;
use App\Models\CashAdvancePayment;
use App\Http\Controllers\TimeLogController;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display all payrolls from all periods and schedules.
     */
    public function indexAll(Request $request)
    {
        $this->authorize('view payrolls');

        // Get all payrolls with filters
        $query = Payroll::with(['creator', 'approver', 'payrollDetails.employee'])
            ->withCount('payrollDetails')
            ->orderBy('created_at', 'desc');

        // Filter by schedule
        if ($request->filled('schedule')) {
            $query->where('pay_schedule', $request->schedule);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payroll type
        if ($request->filled('type')) {
            $query->where('payroll_type', $request->type);
        }

        // Filter by date range
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('period_start', [$request->date_from, $request->date_to]);
        }

        $payrolls = $query->paginate(15)->withQueryString();

        // Get available schedule settings for filter options
        $scheduleSettings = \App\Models\PayScheduleSetting::systemDefaults()
            ->orderBy('sort_order')
            ->get();

        return view('payrolls.index-all', compact('payrolls', 'scheduleSettings'));
    }

    /**
     * Display a listing of payrolls (original method for compatibility).
     */
    public function index(Request $request)
    {
        $this->authorize('view payrolls');

        // Handle AJAX request for pay periods
        if ($request->ajax() && $request->input('action') === 'get_periods') {
            return $this->getPayPeriods($request);
        }

        // Show all payrolls with filters
        $query = Payroll::with(['creator', 'approver', 'payrollDetails.employee'])
            ->withCount('payrollDetails')
            ->orderBy('created_at', 'desc');

        // Filter by pay schedule
        if ($request->filled('pay_schedule')) {
            $query->where('pay_schedule', $request->pay_schedule);
        }

        // Filter by status - allow processing, approved, and paid
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'paid') {
                // Filter for payrolls that are marked as paid
                $query->where('is_paid', true);
            } elseif (in_array($status, ['processing', 'approved'])) {
                $query->where('status', $status);
                // If filtering for approved, also exclude paid ones unless specifically requested
                if ($status === 'approved') {
                    $query->where('is_paid', false);
                }
            }
        } else {
            // Default to only processing and approved payrolls (exclude drafts)
            $query->whereIn('status', ['processing', 'approved']);
        }

        // Filter by payroll type - only allow automated and manual
        if ($request->filled('type')) {
            $type = $request->type;
            if (in_array($type, ['automated', 'manual'])) {
                $query->where('payroll_type', $type);
            }
        }

        // Filter by pay period
        if ($request->filled('pay_period')) {
            $periodDates = explode('|', $request->pay_period);
            if (count($periodDates) === 2) {
                $startDate = Carbon::parse($periodDates[0]);
                $endDate = Carbon::parse($periodDates[1]);
                $query->where('period_start', $startDate)
                    ->where('period_end', $endDate);
            }
        }

        $payrolls = $query->paginate(15)->withQueryString();

        return view('payrolls.index', compact('payrolls'));
    }

    /**
     * Get pay periods for AJAX request
     */
    private function getPayPeriods(Request $request)
    {
        $schedule = $request->input('schedule');
        if (!$schedule) {
            return response()->json(['periods' => []]);
        }

        // Get the schedule setting
        $scheduleSetting = \App\Models\PayScheduleSetting::systemDefaults()
            ->where('code', $schedule)
            ->first();

        if (!$scheduleSetting) {
            return response()->json(['periods' => []]);
        }

        // Get existing payroll periods from database for this schedule
        $existingPeriods = Payroll::where('pay_schedule', $schedule)
            ->whereIn('status', ['processing', 'approved'])
            ->select('period_start', 'period_end')
            ->distinct()
            ->orderBy('period_start', 'desc')
            ->limit(12) // Show last 12 periods
            ->get();

        $periods = [];
        foreach ($existingPeriods as $period) {
            $label = $period->period_start->format('M j') . ' - ' . $period->period_end->format('M j, Y');
            $value = $period->period_start->format('Y-m-d') . '|' . $period->period_end->format('Y-m-d');

            $periods[] = [
                'label' => $label,
                'value' => $value
            ];
        }

        return response()->json(['periods' => $periods]);
    }

    /**
     * Generate payroll summary
     */
    public function generateSummary(Request $request)
    {
        $this->authorize('view payrolls');

        $format = $request->input('export', 'pdf');

        // Build query based on filters
        $query = Payroll::with(['payrollDetails.employee', 'snapshots'])
            ->whereIn('status', ['processing', 'approved']);

        // Apply filters
        if ($request->filled('pay_schedule')) {
            $query->where('pay_schedule', $request->pay_schedule);
        }

        if ($request->filled('status') && in_array($request->status, ['processing', 'approved'])) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type') && in_array($request->type, ['automated', 'manual'])) {
            $query->where('payroll_type', $request->type);
        }

        if ($request->filled('pay_period')) {
            $periodDates = explode('|', $request->pay_period);
            if (count($periodDates) === 2) {
                $startDate = Carbon::parse($periodDates[0]);
                $endDate = Carbon::parse($periodDates[1]);
                $query->where('period_start', $startDate)
                    ->where('period_end', $endDate);
            }
        }

        $payrolls = $query->orderBy('created_at', 'desc')->get();

        if ($format === 'excel') {
            return $this->exportPayrollSummaryExcel($payrolls);
        } else {
            return $this->exportPayrollSummaryPDF($payrolls);
        }
    }

    /**
     * Export payroll summary as Excel
     */
    private function exportPayrollSummaryExcel($payrolls)
    {
        $fileName = 'payroll_summary_' . date('Y-m-d_H-i-s') . '.csv';

        // Create CSV content with proper headers
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Cache-Control' => 'max-age=0',
        ];

        return response()->streamDownload(function () use ($payrolls) {
            $output = fopen('php://output', 'w');

            // Initialize totals for Excel
            $totalBasicExcel = 0;
            $totalHolidayExcel = 0;
            $totalRestExcel = 0;
            $totalOvertimeExcel = 0;
            $totalAllowancesExcel = 0;
            $totalBonusesExcel = 0;
            $totalGrossExcel = 0;
            $totalDeductionsExcel = 0;
            $totalNetExcel = 0;

            // Headers
            fputcsv($output, [
                'Payroll #',
                'Employee',
                'Period',
                'Basic Pay',
                'Holiday Pay',
                'Rest Pay',
                'Overtime Pay',
                'Allowances',
                'Bonuses',
                'Total Gross',
                'Total Deductions',
                'Total Net'
            ]);

            // Data rows - use snapshots for accurate data
            foreach ($payrolls as $payroll) {
                $snapshots = $payroll->snapshots; // Use snapshots instead of payrollDetails

                if ($snapshots->isEmpty()) {
                    // Fallback to payroll details if no snapshots
                    foreach ($payroll->payrollDetails as $detail) {
                        // Calculate correct Holiday, Rest, and Overtime pay from breakdown data
                        $correctHolidayPay = $this->calculateCorrectHolidayPay($detail, $payroll);
                        $correctRestPay = $this->calculateCorrectRestPay($detail, $payroll);
                        $correctOvertimePay = $this->calculateCorrectOvertimePay($detail, $payroll);

                        $basicPay = $detail->basic_pay ?? 0;
                        $allowances = $detail->allowances_total ?? 0;
                        $bonuses = $detail->bonuses_total ?? 0;
                        $grossPay = $detail->gross_pay ?? 0;
                        $deductions = $detail->total_deductions ?? 0;
                        $netPay = $detail->net_pay ?? 0;

                        // Add to totals
                        $totalBasicExcel += $basicPay;
                        $totalHolidayExcel += $correctHolidayPay;
                        $totalRestExcel += $correctRestPay;
                        $totalOvertimeExcel += $correctOvertimePay;
                        $totalAllowancesExcel += $allowances;
                        $totalBonusesExcel += $bonuses;
                        $totalGrossExcel += $grossPay;
                        $totalDeductionsExcel += $deductions;
                        $totalNetExcel += $netPay;

                        fputcsv($output, [
                            $payroll->payroll_number,
                            $detail->employee->full_name,
                            $payroll->period_start->format('M j') . ' - ' . $payroll->period_end->format('M j, Y'),
                            number_format($basicPay, 2),
                            number_format($correctHolidayPay, 2),
                            number_format($correctRestPay, 2),
                            number_format($correctOvertimePay, 2),
                            number_format($allowances, 2),
                            number_format($bonuses, 2),
                            number_format($grossPay, 2),
                            number_format($deductions, 2),
                            number_format($netPay, 2)
                        ]);
                    }
                } else {
                    foreach ($snapshots as $snapshot) {
                        // Calculate correct Holiday, Rest, and Overtime pay from breakdown data
                        $correctHolidayPay = $this->calculateCorrectHolidayPayFromSnapshot($snapshot);
                        $correctOvertimePay = $this->calculateCorrectOvertimePayFromSnapshot($snapshot);
                        $correctRestPay = $this->calculateCorrectRestPayFromSnapshot($snapshot);

                        // Calculate basic pay from breakdown data to match payroll view
                        $basicPay = 0;
                        if ($snapshot->basic_breakdown) {
                            $basicBreakdown = is_string($snapshot->basic_breakdown) ?
                                json_decode($snapshot->basic_breakdown, true) :
                                $snapshot->basic_breakdown;
                            if (is_array($basicBreakdown)) {
                                foreach ($basicBreakdown as $type => $data) {
                                    $basicPay += $data['amount'] ?? 0;
                                }
                            }
                        } else {
                            $basicPay = $snapshot->regular_pay ?? 0;
                        }

                        $allowances = $snapshot->allowances_total ?? 0;
                        $bonuses = $snapshot->bonuses_total ?? 0;
                        $deductions = $snapshot->total_deductions ?? 0;

                        // Calculate gross pay and net pay from corrected component values
                        $grossPay = $basicPay + $correctHolidayPay + $correctRestPay + $correctOvertimePay + $allowances + $bonuses;
                        $netPay = $grossPay - $deductions;

                        // Add to totals
                        $totalBasicExcel += $basicPay;
                        $totalHolidayExcel += $correctHolidayPay;
                        $totalRestExcel += $correctRestPay;
                        $totalOvertimeExcel += $correctOvertimePay;
                        $totalAllowancesExcel += $allowances;
                        $totalBonusesExcel += $bonuses;
                        $totalGrossExcel += $grossPay;
                        $totalDeductionsExcel += $deductions;
                        $totalNetExcel += $netPay;

                        fputcsv($output, [
                            $payroll->payroll_number,
                            $snapshot->employee_name,
                            $payroll->period_start->format('M j') . ' - ' . $payroll->period_end->format('M j, Y'),
                            number_format($basicPay, 2),
                            number_format($correctHolidayPay, 2),
                            number_format($correctRestPay, 2),
                            number_format($correctOvertimePay, 2),
                            number_format($allowances, 2),
                            number_format($bonuses, 2),
                            number_format($grossPay, 2),
                            number_format($deductions, 2),
                            number_format($netPay, 2)
                        ]);
                    }
                }
            }

            // Add totals row
            fputcsv($output, [
                'TOTAL',
                '',
                '',
                number_format($totalBasicExcel, 2),
                number_format($totalHolidayExcel, 2),
                number_format($totalRestExcel, 2),
                number_format($totalOvertimeExcel, 2),
                number_format($totalAllowancesExcel, 2),
                number_format($totalBonusesExcel, 2),
                number_format($totalGrossExcel, 2),
                number_format($totalDeductionsExcel, 2),
                number_format($totalNetExcel, 2)
            ]);

            fclose($output);
        }, $fileName, $headers);
    }

    /**
     * Export payroll summary as PDF
     */
    private function exportPayrollSummaryPDF($payrolls)
    {
        $fileName = 'payroll_summary_' . date('Y-m-d_H-i-s') . '.pdf';

        // Create HTML content for PDF
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <title>Payroll Summary</title>
            <style>
                body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; margin: 20px; }
                .header { text-align: center; margin-bottom: 20px; }
                .header h1 { margin: 0; color: #333; font-size: 18px; }
                .header p { margin: 5px 0; color: #666; font-size: 12px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 9px; }
                th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
                th { background-color: #f8f9fa; font-weight: bold; }
                .text-right { text-align: right; }
                .total-row { background-color: #f8f9fa; font-weight: bold; }
                .currency { text-align: right; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Payroll Summary Report</h1>
                <p>Generated on: ' . date('F j, Y g:i A') . '</p>
                <p>Total Payrolls: ' . $payrolls->count() . '</p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Payroll #</th>
                        <th>Employee</th>
                        <th>Period</th>
                        <th class="currency">Basic Pay</th>
                        <th class="currency">Holiday Pay</th>
                        <th class="currency">Rest Pay</th>
                        <th class="currency">Overtime</th>
                        <th class="currency">Allowances</th>
                        <th class="currency">Bonuses</th>
                        <th class="currency">Gross Pay</th>
                        <th class="currency">Deductions</th>
                        <th class="currency">Net Pay</th>
                    </tr>
                </thead>
                <tbody>';

        $totalBasic = 0;
        $totalHoliday = 0;
        $totalRest = 0;
        $totalOvertime = 0;
        $totalAllowances = 0;
        $totalBonuses = 0;
        $totalGross = 0;
        $totalDeductions = 0;
        $totalNet = 0;

        foreach ($payrolls as $payroll) {
            $snapshots = $payroll->snapshots;

            if ($snapshots->isEmpty()) {
                // Fallback to payroll details
                foreach ($payroll->payrollDetails as $detail) {
                    $basicPay = $detail->basic_pay ?? 0;
                    $holidayPay = $this->calculateCorrectHolidayPay($detail, $payroll);
                    $restPay = $this->calculateCorrectRestPay($detail, $payroll);
                    $overtimePay = $this->calculateCorrectOvertimePay($detail, $payroll);
                    $allowances = $detail->allowances_total ?? 0;
                    $bonuses = $detail->bonuses_total ?? 0;
                    $grossPay = $detail->gross_pay ?? 0;
                    $deductions = $detail->total_deductions ?? 0;
                    $netPay = $detail->net_pay ?? 0;

                    $totalBasic += $basicPay;
                    $totalHoliday += $holidayPay;
                    $totalRest += $restPay;
                    $totalOvertime += $overtimePay;
                    $totalAllowances += $allowances;
                    $totalBonuses += $bonuses;
                    $totalGross += $grossPay;
                    $totalDeductions += $deductions;
                    $totalNet += $netPay;

                    $html .= '
                        <tr>
                            <td>' . $payroll->payroll_number . '</td>
                            <td>' . $detail->employee->full_name . '</td>
                            <td>' . $payroll->period_start->format('M j') . ' - ' . $payroll->period_end->format('M j, Y') . '</td>
                            <td class="currency">' . number_format($basicPay, 2) . '</td>
                            <td class="currency">' . number_format($holidayPay, 2) . '</td>
                            <td class="currency">' . number_format($restPay, 2) . '</td>
                            <td class="currency">' . number_format($overtimePay, 2) . '</td>
                            <td class="currency">' . number_format($allowances, 2) . '</td>
                            <td class="currency">' . number_format($bonuses, 2) . '</td>
                            <td class="currency">' . number_format($grossPay, 2) . '</td>
                            <td class="currency">' . number_format($deductions, 2) . '</td>
                            <td class="currency">' . number_format($netPay, 2) . '</td>
                        </tr>';
                }
            } else {
                foreach ($snapshots as $snapshot) {
                    // Calculate basic pay from breakdown data to match payroll view
                    $basicPay = 0;
                    if ($snapshot->basic_breakdown) {
                        $basicBreakdown = is_string($snapshot->basic_breakdown) ?
                            json_decode($snapshot->basic_breakdown, true) :
                            $snapshot->basic_breakdown;
                        if (is_array($basicBreakdown)) {
                            foreach ($basicBreakdown as $type => $data) {
                                $basicPay += $data['amount'] ?? 0;
                            }
                        }
                    } else {
                        $basicPay = $snapshot->regular_pay ?? 0;
                    }

                    $holidayPay = $this->calculateCorrectHolidayPayFromSnapshot($snapshot);
                    $restPay = $this->calculateCorrectRestPayFromSnapshot($snapshot);
                    $overtimePay = $this->calculateCorrectOvertimePayFromSnapshot($snapshot);
                    $allowances = $snapshot->allowances_total ?? 0;
                    $bonuses = $snapshot->bonuses_total ?? 0;
                    $deductions = $snapshot->total_deductions ?? 0;

                    // Calculate gross pay and net pay from corrected component values
                    $grossPay = $basicPay + $holidayPay + $restPay + $overtimePay + $allowances + $bonuses;
                    $netPay = $grossPay - $deductions;

                    $totalBasic += $basicPay;
                    $totalHoliday += $holidayPay;
                    $totalRest += $restPay;
                    $totalOvertime += $overtimePay;
                    $totalAllowances += $allowances;
                    $totalBonuses += $bonuses;
                    $totalGross += $grossPay;
                    $totalDeductions += $deductions;
                    $totalNet += $netPay;

                    $html .= '
                        <tr>
                            <td>' . $payroll->payroll_number . '</td>
                            <td>' . $snapshot->employee_name . '</td>
                            <td>' . $payroll->period_start->format('M j') . ' - ' . $payroll->period_end->format('M j, Y') . '</td>
                            <td class="currency">' . number_format($basicPay, 2) . '</td>
                            <td class="currency">' . number_format($holidayPay, 2) . '</td>
                            <td class="currency">' . number_format($restPay, 2) . '</td>
                            <td class="currency">' . number_format($overtimePay, 2) . '</td>
                            <td class="currency">' . number_format($allowances, 2) . '</td>
                            <td class="currency">' . number_format($bonuses, 2) . '</td>
                            <td class="currency">' . number_format($grossPay, 2) . '</td>
                            <td class="currency">' . number_format($deductions, 2) . '</td>
                            <td class="currency">' . number_format($netPay, 2) . '</td>
                        </tr>';
                }
            }
        }

        $html .= '
                    <tr class="total-row">
                        <td colspan="3"><strong>TOTAL</strong></td>
                        <td class="currency"><strong>' . number_format($totalBasic, 2) . '</strong></td>
                        <td class="currency"><strong>' . number_format($totalHoliday, 2) . '</strong></td>
                        <td class="currency"><strong>' . number_format($totalRest, 2) . '</strong></td>
                        <td class="currency"><strong>' . number_format($totalOvertime, 2) . '</strong></td>
                        <td class="currency"><strong>' . number_format($totalAllowances, 2) . '</strong></td>
                        <td class="currency"><strong>' . number_format($totalBonuses, 2) . '</strong></td>
                        <td class="currency"><strong>' . number_format($totalGross, 2) . '</strong></td>
                        <td class="currency"><strong>' . number_format($totalDeductions, 2) . '</strong></td>
                        <td class="currency"><strong>' . number_format($totalNet, 2) . '</strong></td>
                    </tr>
                </tbody>
            </table>
        </body>
        </html>';

        // Use DomPDF to generate proper PDF
        try {
            $pdf = app('dompdf.wrapper');
            $pdf->loadHTML($html);
            $pdf->setPaper('A4', 'landscape');

            return $pdf->download($fileName);
        } catch (\Exception $e) {
            // Fallback to simple HTML if DomPDF is not available
            return response($html, 200, [
                'Content-Type' => 'text/html',
                'Content-Disposition' => 'attachment; filename="' . str_replace('.pdf', '_report.html', $fileName) . '"',
            ]);
        }
    }

    /**
     * Show the form for creating a new payroll.
     */
    public function create(Request $request)
    {
        $this->authorize('create payrolls');

        // Get the selected pay schedule filter
        $selectedSchedule = $request->input('schedule');

        // Get all payroll schedule settings (both active and inactive for display)
        $scheduleSettings = \App\Models\PayScheduleSetting::systemDefaults()
            ->orderBy('sort_order')
            ->get();

        // If no schedule is selected, show all available schedules for selection
        if (!$selectedSchedule) {
            // Calculate current periods for each schedule to display
            foreach ($scheduleSettings as $setting) {
                if ($setting->is_active) {
                    $currentPeriods = $this->getCurrentPeriodDisplayForSchedule($setting);
                    $setting->current_period_display = $currentPeriods;
                }
            }

            return view('payrolls.create', [
                'scheduleSettings' => $scheduleSettings,
                'selectedSchedule' => null,
                'availablePeriods' => [],
                'selectedPeriod' => null,
                'employees' => collect()
            ]);
        }

        // Get the specific schedule setting
        $scheduleSetting = $scheduleSettings->firstWhere('code', $selectedSchedule);
        if (!$scheduleSetting) {
            return redirect()->route('payrolls.create')
                ->withErrors(['schedule' => 'Invalid pay schedule selected.']);
        }

        // Get current month periods only for the selected schedule
        $availablePeriods = $this->getCurrentMonthPeriodsForSchedule($scheduleSetting);

        // Get selected period if provided, or auto-select current period
        $selectedPeriodId = $request->input('period');
        $selectedPeriod = null;
        $employees = collect();

        if ($selectedPeriodId) {
            $selectedPeriod = collect($availablePeriods)->firstWhere('id', $selectedPeriodId);
        } else {
            // Auto-select current period if none specified
            $selectedPeriod = collect($availablePeriods)->firstWhere('is_current', true);
            if (!$selectedPeriod && count($availablePeriods) > 0) {
                // If no current period, select the first available period
                $selectedPeriod = $availablePeriods[0];
            }
        }

        if ($selectedPeriod) {
            // Get employees for this schedule - handle different naming conventions
            $payScheduleVariations = $this->getPayScheduleVariations($selectedPeriod['pay_schedule']);

            $employees = Employee::with(['user', 'department', 'position'])
                ->where('employment_status', 'active')
                ->whereIn('pay_schedule', $payScheduleVariations)
                ->orderBy('first_name')
                ->get();
        }

        return view('payrolls.create', compact(
            'scheduleSettings',
            'selectedSchedule',
            'availablePeriods',
            'selectedPeriod',
            'employees'
        ));
    }

    /**
     * Store a newly created payroll.
     */
    public function store(Request $request)
    {
        $this->authorize('create payrolls');

        $validated = $request->validate([
            'selected_period' => 'required|string',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        // Parse the selected period data
        $periodData = json_decode(base64_decode($validated['selected_period']), true);

        if (!$periodData) {
            return back()->withErrors(['selected_period' => 'Invalid period selection.'])->withInput();
        }

        // Validate that selected employees match the pay schedule
        $selectedEmployees = Employee::whereIn('id', $validated['employee_ids'])->get();
        $invalidEmployees = $selectedEmployees->where('pay_schedule', '!=', $periodData['pay_schedule']);

        if ($invalidEmployees->count() > 0) {
            return back()->withErrors([
                'employee_ids' => 'All selected employees must have the same pay schedule as the selected period.'
            ])->withInput();
        }

        DB::beginTransaction();
        try {
            // Create payroll (always regular type now)
            $payroll = Payroll::create([
                'payroll_number' => Payroll::generatePayrollNumber('regular'),
                'period_start' => $periodData['period_start'],
                'period_end' => $periodData['period_end'],
                'pay_date' => $periodData['pay_date'],
                'payroll_type' => 'regular',
                'pay_schedule' => $periodData['pay_schedule'],
                'description' => 'Manual payroll for ' . $periodData['period_display'],
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            $totalGross = 0;
            $totalDeductions = 0;
            $totalNet = 0;
            $processedEmployees = 0;

            // Create payroll details for each employee
            foreach ($validated['employee_ids'] as $employeeId) {
                try {
                    $employee = Employee::find($employeeId);

                    if (!$employee) {
                        Log::warning("Employee with ID {$employeeId} not found");
                        continue;
                    }

                    // Calculate payroll details
                    $payrollDetail = $this->calculateEmployeePayroll($employee, $payroll);

                    $totalGross += $payrollDetail->gross_pay;
                    $totalDeductions += $payrollDetail->total_deductions;
                    $totalNet += $payrollDetail->net_pay;
                    $processedEmployees++;
                } catch (\Exception $e) {
                    Log::error("Failed to process employee {$employeeId}: " . $e->getMessage());
                    // Continue with other employees rather than failing the entire payroll
                    continue;
                }
            }

            if ($processedEmployees === 0) {
                throw new \Exception('No employees could be processed for payroll.');
            }

            // Update payroll totals
            $payroll->update([
                'total_gross' => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_net' => $totalNet,
            ]);

            DB::commit();

            return redirect()->route('payrolls.show', $payroll)
                ->with('success', "Payroll created successfully! {$processedEmployees} employees processed.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create payroll: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to create payroll: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Automation Payroll - Show schedule selection
     */
    public function automationIndex()
    {
        $this->authorize('view payrolls');

        // Get only active payroll schedule settings for selection
        $scheduleSettings = \App\Models\PayScheduleSetting::systemDefaults()
            ->active()
            ->orderBy('sort_order')
            ->get();

        // Calculate current periods and employee counts for each schedule
        foreach ($scheduleSettings as $setting) {
            // Get current period display
            $currentPeriods = $this->getCurrentPeriodDisplayForSchedule($setting);
            $setting->current_period_display = $currentPeriods;

            // Calculate current pay period (not next)
            $setting->next_period = $this->calculateCurrentPayPeriod($setting);

            // Count active employees for this schedule
            $setting->active_employees_count = \App\Models\Employee::where('pay_schedule', $setting->code)
                ->where('employment_status', 'active')
                ->count();

            // Get last payroll date if exists
            $lastPayroll = \App\Models\Payroll::where('pay_schedule', $setting->code)
                ->orderBy('pay_date', 'desc')
                ->first();

            if ($lastPayroll) {
                $setting->last_payroll_date = $lastPayroll->pay_date;
            }
        }

        return view('payrolls.automation.index', [
            'scheduleSettings' => $scheduleSettings
        ]);
    }
    /**
     * Automation Payroll - Create payroll for selected schedule
     */
    public function automationCreate(Request $request)
    {
        Log::info('Automation Create called with: ' . json_encode($request->all()));

        $this->authorize('create payrolls');

        // Get the selected pay schedule
        $scheduleCode = $request->input('schedule');

        Log::info('Schedule Code: ' . $scheduleCode);

        if (!$scheduleCode) {
            Log::warning('No schedule code provided');
            return redirect()->route('payrolls.automation.index')
                ->with('error', 'Please select a pay schedule.');
        }

        // Get schedule setting
        $selectedSchedule = \App\Models\PayScheduleSetting::systemDefaults()
            ->where('code', $scheduleCode)
            ->first();

        if (!$selectedSchedule) {
            return redirect()->route('payrolls.automation.index')
                ->with('error', 'Invalid pay schedule selected.');
        }

        // Redirect directly to the draft automation list instead of creating payrolls
        return redirect()->route('payrolls.automation.list', $scheduleCode)
            ->with('success', 'Viewing draft payrolls for ' . $selectedSchedule->name . '. Click on individual employees to view details.');
    }

    /**
     * Show list of automated payrolls for a specific schedule
     * If no payrolls exist, show draft mode. If payrolls exist, show real payrolls.
     */
    public function automationList(Request $request, $schedule)
    {
        $this->authorize('view payrolls');

        // Get schedule setting
        $selectedSchedule = \App\Models\PayScheduleSetting::systemDefaults()
            ->where('code', $schedule)
            ->first();

        if (!$selectedSchedule) {
            return redirect()->route('payrolls.automation.index')
                ->with('error', 'Invalid pay schedule selected.');
        }

        // Calculate current period to filter payrolls
        $currentPeriod = $this->calculateCurrentPayPeriod($selectedSchedule);

        // Check if payrolls exist for this period
        $existingPayrolls = Payroll::with(['creator', 'approver', 'payrollDetails.employee'])
            ->withCount('payrollDetails')
            ->where('pay_schedule', $schedule)
            ->where('payroll_type', 'automated')
            ->where('period_start', $currentPeriod['start'])
            ->where('period_end', $currentPeriod['end'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Filter to only show DRAFT payrolls
        $draftPayrolls = $existingPayrolls->filter(function ($payroll) {
            return $payroll->status === 'draft';
        });

        // Always show draft mode for employees without payroll records
        // This will include dynamic draft payrolls for employees who don't have records yet
        return $this->showDraftMode($selectedSchedule, $currentPeriod, $schedule);
    }

    /**
     * Show draft mode with dynamic calculations (not saved to DB)
     */
    private function showDraftMode($selectedSchedule, $currentPeriod, $schedule)
    {
        // Get employees who already have payroll records for this period
        $employeesWithPayrolls = Payroll::whereHas('payrollDetails')
            ->where('pay_schedule', $schedule)
            ->where('period_start', $currentPeriod['start'])
            ->where('period_end', $currentPeriod['end'])
            ->where('payroll_type', 'automated')
            ->with('payrollDetails')
            ->get()
            ->pluck('payrollDetails.*.employee_id')
            ->flatten()
            ->unique()
            ->toArray();

        // Get active employees for this schedule, excluding those who already have payrolls
        $employees = Employee::with(['user', 'department', 'position'])
            ->where('pay_schedule', $schedule)
            ->where('employment_status', 'active')
            ->whereNotIn('id', $employeesWithPayrolls)
            ->orderBy('first_name')
            ->get();

        if ($employees->isEmpty()) {
            // If no employees are available for draft payrolls, show the automation page with a message
            // This could mean all employees already have payrolls or no active employees exist
            $allActiveEmployees = Employee::where('pay_schedule', $schedule)
                ->where('employment_status', 'active')
                ->count();

            if ($allActiveEmployees == 0) {
                return redirect()->route('payrolls.automation.index')
                    ->withErrors(['employees' => 'No active employees found for this pay schedule.']);
            }

            // All active employees already have payrolls - show empty state with information
            $mockPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
                collect(),
                0,
                15,
                1,
                ['path' => request()->url()]
            );

            return view('payrolls.automation.list', compact(
                'selectedSchedule',
                'currentPeriod'
            ) + [
                'scheduleCode' => $schedule,
                'isDraft' => true,
                'payrolls' => $mockPaginator,
                'hasPayrolls' => false,
                'allApproved' => false,
                'allEmployeesHavePayrolls' => true,
                'totalActiveEmployees' => $allActiveEmployees,
                'draftTotals' => [
                    'gross' => 0,
                    'deductions' => 0,
                    'net' => 0,
                    'count' => 0
                ]
            ]);
        }

        // Calculate dynamic payroll data for each employee (not saved to DB)
        $payrollPreviews = collect();
        $totalGross = 0;
        $totalDeductions = 0;
        $totalNet = 0;

        foreach ($employees as $employee) {
            $payrollCalculation = $this->calculateEmployeePayrollForPeriod($employee, $currentPeriod['start'], $currentPeriod['end']);

            // Create a temporary Payroll model instance (not saved to DB)
            $mockPayroll = new Payroll();
            // Use employee ID directly for draft mode
            $mockPayroll->id = $employee->id;
            $mockPayroll->payroll_number = 'DRAFT-' . $employee->employee_number;
            $mockPayroll->period_start = $currentPeriod['start'];
            $mockPayroll->period_end = $currentPeriod['end'];
            $mockPayroll->pay_date = $currentPeriod['pay_date'];
            $mockPayroll->status = 'draft';
            $mockPayroll->payroll_type = 'automated';
            $mockPayroll->total_net = $payrollCalculation['net_pay'] ?? 0;
            $mockPayroll->total_gross = $payrollCalculation['gross_pay'] ?? 0;
            $mockPayroll->total_deductions = $payrollCalculation['total_deductions'] ?? 0;
            $mockPayroll->created_at = now();

            // Set fake relationships
            $fakeCreator = (object) ['name' => 'System (Draft)', 'id' => 0];
            $mockPayroll->setRelation('creator', $fakeCreator);
            $mockPayroll->setRelation('approver', null);

            // Mock payroll details collection with single employee
            $mockPayrollDetail = (object) [
                'employee' => $employee,
                'employee_id' => $employee->id,
                'gross_pay' => $payrollCalculation['gross_pay'] ?? 0,
                'total_deductions' => $payrollCalculation['total_deductions'] ?? 0,
                'net_pay' => $payrollCalculation['net_pay'] ?? 0,
            ];
            $mockPayroll->setRelation('payrollDetails', collect([$mockPayrollDetail]));
            $mockPayroll->payroll_details_count = 1;

            $payrollPreviews->push($mockPayroll);

            $totalGross += $payrollCalculation['gross_pay'] ?? 0;
            $totalDeductions += $payrollCalculation['total_deductions'] ?? 0;
            $totalNet += $payrollCalculation['net_pay'] ?? 0;
        }

        // Create paginator for mock data
        $mockPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $payrollPreviews,
            $payrollPreviews->count(),
            15,
            1,
            ['path' => request()->url()]
        );

        // Return draft payrolls that work with existing view
        return view('payrolls.automation.list', compact(
            'selectedSchedule',
            'currentPeriod'
        ) + [
            'scheduleCode' => $schedule,
            'isDraft' => true,
            'payrolls' => $mockPaginator,
            'hasPayrolls' => false,
            'allApproved' => false,
            'draftTotals' => [
                'gross' => $totalGross,
                'deductions' => $totalDeductions,
                'net' => $totalNet,
                'count' => $payrollPreviews->count()
            ]
        ]);
    }

    /**
     * Auto-create individual payrolls for each employee in the period
     */
    private function autoCreatePayrollForPeriod($scheduleSetting, $period, $employees, $status = 'draft')
    {
        DB::beginTransaction();

        try {
            Log::info("Starting individual payroll creation for {$employees->count()} employees");

            $createdPayrolls = [];

            // Create individual payroll for each employee
            foreach ($employees as $employee) {
                Log::info("Creating payroll for employee: {$employee->id} - {$employee->first_name} {$employee->last_name}");

                try {
                    // Generate unique payroll number for this employee
                    $payrollNumber = $this->generatePayrollNumber($scheduleSetting->code);

                    // Calculate payroll details for this employee
                    $payrollCalculation = $this->calculateEmployeePayrollForPeriod($employee, $period['start'], $period['end']);

                    // Log processing calculation for comparison with draft
                    Log::info('Processing Payroll Calculation for Employee ' . $employee->id, $payrollCalculation);

                    // Create individual payroll for this employee
                    $payroll = Payroll::create([
                        'payroll_number' => $payrollNumber,
                        'pay_schedule' => $scheduleSetting->code,
                        'period_start' => $period['start'],
                        'period_end' => $period['end'],
                        'pay_date' => $period['pay_date'],
                        'status' => $status,
                        'payroll_type' => 'automated',
                        'created_by' => Auth::id() ?? 1,
                        'notes' => 'Automatically created payroll for ' . $employee->first_name . ' ' . $employee->last_name . ' (' . $scheduleSetting->name . ' schedule)',
                        'total_gross' => $payrollCalculation['gross_pay'] ?? 0,
                        'total_deductions' => $payrollCalculation['total_deductions'] ?? 0,
                        'total_net' => $payrollCalculation['net_pay'] ?? 0,
                    ]);

                    Log::info("Created payroll {$payrollNumber} for employee {$employee->id}");

                    // Create payroll detail for this employee
                    $payrollDetail = PayrollDetail::create([
                        'payroll_id' => $payroll->id,
                        'employee_id' => $employee->id,
                        'basic_salary' => $employee->basic_salary ?? 0,
                        'daily_rate' => $employee->daily_rate ?? 0,
                        'hourly_rate' => $employee->hourly_rate ?? 0,
                        'days_worked' => $payrollCalculation['days_worked'] ?? 0,
                        'regular_hours' => $payrollCalculation['hours_worked'] ?? 0,
                        'overtime_hours' => $payrollCalculation['overtime_hours'] ?? 0,
                        'holiday_hours' => $payrollCalculation['holiday_hours'] ?? 0,
                        'rest_day_hours' => $payrollCalculation['rest_day_hours'] ?? 0,
                        'regular_pay' => $payrollCalculation['regular_pay'] ?? 0, // Use the calculated basic pay
                        'overtime_pay' => $payrollCalculation['overtime_pay'] ?? 0,
                        'holiday_pay' => $payrollCalculation['holiday_pay'] ?? 0,
                        'rest_day_pay' => $payrollCalculation['rest_day_pay'] ?? 0,
                        'allowances' => $payrollCalculation['allowances'] ?? 0,
                        'bonuses' => $payrollCalculation['bonuses'] ?? 0,
                        'gross_pay' => $payrollCalculation['gross_pay'] ?? 0,
                        'sss_contribution' => $payrollCalculation['sss_deduction'] ?? 0,
                        'philhealth_contribution' => $payrollCalculation['philhealth_deduction'] ?? 0,
                        'pagibig_contribution' => $payrollCalculation['pagibig_deduction'] ?? 0,
                        'withholding_tax' => $payrollCalculation['tax_deduction'] ?? 0,
                        'late_deductions' => $payrollCalculation['late_deductions'] ?? 0,
                        'undertime_deductions' => $payrollCalculation['undertime_deductions'] ?? 0,
                        'cash_advance_deductions' => $payrollCalculation['cash_advance_deductions'] ?? 0,
                        'other_deductions' => $payrollCalculation['other_deductions'] ?? 0,
                        'total_deductions' => $payrollCalculation['total_deductions'] ?? 0,
                        'net_pay' => $payrollCalculation['net_pay'] ?? 0,
                        'earnings_breakdown' => json_encode([
                            'allowances' => $payrollCalculation['allowances_details'] ?? [],
                            'bonuses' => $payrollCalculation['bonuses_details'] ?? [],
                        ]),
                        'deduction_breakdown' => json_encode($payrollCalculation['deductions_details'] ?? []),
                    ]);

                    Log::info("Created payroll detail for employee {$employee->id}: Gross: {$payrollCalculation['gross_pay']}, Net: {$payrollCalculation['net_pay']}");

                    $createdPayrolls[] = $payroll;
                } catch (\Exception $e) {
                    Log::warning("Failed to create payroll for employee {$employee->id}: " . $e->getMessage());
                    // Continue with other employees
                }
            }

            DB::commit();

            // Create snapshots for processing payrolls
            if ($status === 'processing') {
                foreach ($createdPayrolls as $payroll) {
                    try {
                        Log::info("Creating snapshots for payroll {$payroll->id}");
                        $this->createPayrollSnapshots($payroll);
                    } catch (\Exception $e) {
                        Log::error("Failed to create snapshots for payroll {$payroll->id}: " . $e->getMessage());
                        // Don't fail the entire process, but log the error
                    }
                }
            }

            Log::info("Successfully created " . count($createdPayrolls) . " individual payrolls for {$scheduleSetting->name} schedule");

            // Return the first payroll (we'll redirect to the list instead)
            return $createdPayrolls[0] ?? null;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to auto-create payrolls: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculate payroll details for an employee for a specific period
     */
    private function calculateEmployeePayrollForPeriod($employee, $periodStart, $periodEnd, $payroll = null)
    {
        // Basic salary calculation based on employee's salary and period
        $basicSalary = $employee->basic_salary ?? 0;

        // Get time logs for the payroll period
        $timeLogs = TimeLog::where('employee_id', $employee->id)
            ->whereBetween('log_date', [$periodStart, $periodEnd])
            ->get();

        $hoursWorked = 0;
        $daysWorked = 0;
        $regularHours = 0;
        $overtimeHours = 0;
        $holidayHours = 0;
        $lateHours = 0;
        $undertimeHours = 0;

        // For draft payrolls, calculate dynamically using current grace periods
        // For approved payrolls, use stored values (snapshots)
        $isDraftMode = $payroll === null || $payroll->status === 'draft';

        // Calculate total hours and days worked
        foreach ($timeLogs as $timeLog) {
            if ($isDraftMode && $timeLog->time_in && $timeLog->time_out) {
                // Calculate dynamically using current grace periods and employee schedules
                $dynamicCalculation = $this->calculateTimeLogHoursDynamically($timeLog);

                $hoursWorked += $dynamicCalculation['total_hours'];
                $regularHours += $dynamicCalculation['regular_hours'];
                $overtimeHours += $dynamicCalculation['overtime_hours'];
                $lateHours += $dynamicCalculation['late_hours'];
                $undertimeHours += $dynamicCalculation['undertime_hours'];

                if ($dynamicCalculation['total_hours'] > 0) {
                    $daysWorked++;
                }

                // Handle holiday hours
                if ($timeLog->is_holiday && $dynamicCalculation['total_hours'] > 0) {
                    $holidayHours += $dynamicCalculation['total_hours'];
                }
            } else {
                // Use stored values for approved payrolls (snapshot mode)
                $hoursWorked += $timeLog->total_hours ?? 0;
                $regularHours += $timeLog->regular_hours ?? 0;
                $overtimeHours += $timeLog->overtime_hours ?? 0;
                $lateHours += $timeLog->late_hours ?? 0;
                $undertimeHours += $timeLog->undertime_hours ?? 0;

                if (($timeLog->total_hours ?? 0) > 0) {
                    $daysWorked++;
                }

                // Handle holiday hours from stored values
                if ($timeLog->is_holiday && ($timeLog->total_hours ?? 0) > 0) {
                    $holidayHours += $timeLog->total_hours ?? 0;
                }
            }
        }

        // Calculate gross pay using rate multipliers from time logs
        $grossPayData = $this->calculateGrossPayWithRateMultipliersDetailed($employee, $basicSalary, $timeLogs, $hoursWorked, $daysWorked, $periodStart, $periodEnd);
        $grossPay = $grossPayData['total_gross'];

        // Calculate allowances using dynamic settings
        $allowancesData = $this->calculateAllowances($employee, $basicSalary, $daysWorked, $hoursWorked);
        $allowancesTotal = $allowancesData['total'];

        // Calculate bonuses using dynamic settings
        $bonusesData = $this->calculateBonuses($employee, $basicSalary, $daysWorked, $hoursWorked);
        $bonusesTotal = $bonusesData['total'];

        // Calculate overtime pay (simplified for now)
        $overtimePay = 0; // TODO: Implement detailed overtime calculation based on time logs

        // Total gross pay including allowances and bonuses
        $totalGrossPay = $grossPay + $allowancesTotal + $bonusesTotal + $overtimePay;

        // Calculate late and undertime deductions based on dynamic calculations
        $lateDeductions = $this->calculateLateDeductions($employee, $lateHours);
        $undertimeDeductions = $this->calculateUndertimeDeductions($employee, $undertimeHours);

        // Calculate cash advance deductions for this period
        $cashAdvanceData = CashAdvance::calculateDeductionForPeriod($employee->id, $periodStart, $periodEnd);
        $cashAdvanceDeductions = $cashAdvanceData['total'];

        // Calculate deductions using dynamic settings
        // For SSS/government deductions, use taxable income (basic + holiday + rest + taxable allowances/bonuses)
        // The grossPay already includes basic + holiday + rest + overtime
        $taxableIncomeForDeductions = $grossPay; // This includes basic + holiday + rest + overtime

        $deductions = $this->calculateDeductions($employee, $totalGrossPay, $taxableIncomeForDeductions, $overtimePay, $allowancesTotal, $bonusesTotal);

        $netPay = $totalGrossPay - $deductions['total'] - $lateDeductions - $undertimeDeductions - $cashAdvanceDeductions;

        return [
            'basic_salary' => $basicSalary,  // Employee's base salary
            'regular_pay' => $grossPayData['basic_pay'],      // Basic pay for regular work
            'overtime_pay' => $grossPayData['overtime_pay'],
            'holiday_pay' => $grossPayData['holiday_pay'],
            'rest_day_pay' => $grossPayData['rest_day_pay'] ?? 0,
            'allowances' => $allowancesTotal,
            'allowances_details' => $allowancesData['details'],
            'bonuses' => $bonusesTotal,
            'bonuses_details' => $bonusesData['details'],
            'gross_pay' => $totalGrossPay,
            'tax_deduction' => $deductions['tax'],
            'sss_deduction' => $deductions['sss'],
            'philhealth_deduction' => $deductions['philhealth'],
            'pagibig_deduction' => $deductions['pagibig'],
            'other_deductions' => $deductions['other'],
            'deductions_details' => $deductions['details'],
            'total_deductions' => $deductions['total'] + $lateDeductions + $undertimeDeductions + $cashAdvanceDeductions,
            'net_pay' => $netPay,
            'hours_worked' => $hoursWorked,
            'days_worked' => $daysWorked,
            'regular_hours' => $regularHours,
            'overtime_hours' => $overtimeHours,
            'holiday_hours' => $holidayHours,
            'rest_day_hours' => $grossPayData['rest_day_hours'] ?? 0,
            'late_hours' => $lateHours,
            'undertime_hours' => $undertimeHours,
            'late_deductions' => $lateDeductions,
            'undertime_deductions' => $undertimeDeductions,
            'cash_advance_deductions' => $cashAdvanceDeductions,
            'cash_advance_details' => $cashAdvanceData['details'],
        ];
    }

    /**
     * Calculate gross pay based on schedule type and actual time worked
     */
    private function calculateGrossPay($employee, $basicSalary, $hoursWorked, $daysWorked, $periodStart, $periodEnd)
    {
        $paySchedule = $employee->pay_schedule;

        // If no time worked, no pay (except for manual adjustments)
        if ($hoursWorked <= 0 && $daysWorked <= 0) {
            return 0;
        }

        // Calculate based on actual time worked, not full salary
        switch ($paySchedule) {
            case 'daily':
                // For daily, basic salary is daily rate
                return $basicSalary * $daysWorked;

            case 'weekly':
                // Calculate hourly rate from weekly salary and pay based on hours worked
                $hourlyRate = $basicSalary / 40; // Assuming 40 hours per week
                return $hourlyRate * $hoursWorked;

            case 'semi_monthly':
                // Calculate hourly rate from semi-monthly salary and pay based on hours worked
                $hourlyRate = $basicSalary / 86.67; // Assuming ~86.67 hours per semi-month
                return $hourlyRate * $hoursWorked;

            case 'monthly':
                // Calculate hourly rate from monthly salary and pay based on hours worked
                $hourlyRate = $basicSalary / 173.33; // Assuming ~173.33 hours per month
                return $hourlyRate * $hoursWorked;

            default:
                // Default to hourly calculation
                $hourlyRate = $employee->hourly_rate ?? ($basicSalary / 173.33);
                return $hourlyRate * $hoursWorked;
        }
    }

    /**
     * Calculate gross pay using rate multipliers from time logs
     */
    /**
     * Calculate gross pay with detailed breakdown by pay type
     */
    private function calculateGrossPayWithRateMultipliersDetailed($employee, $basicSalary, $timeLogs, $hoursWorked, $daysWorked, $periodStart, $periodEnd)
    {
        // If no time worked and no time logs, fallback to basic calculation
        if ($timeLogs->isEmpty()) {
            $basicPay = $this->calculateGrossPay($employee, $basicSalary, $hoursWorked, $daysWorked, $periodStart, $periodEnd);
            return [
                'total_gross' => $basicPay,
                'basic_pay' => $basicPay,
                'holiday_pay' => 0,
                'rest_day_pay' => 0,
                'overtime_pay' => 0,
                'regular_hours' => $hoursWorked,
                'overtime_hours' => 0,
                'holiday_hours' => 0,
                'rest_day_hours' => 0,
                'pay_breakdown' => [],
                'overtime_breakdown' => [],
                'holiday_breakdown' => [],
                'rest_day_breakdown' => [],
            ];
        }

        // Calculate hourly rate based on pay schedule
        $hourlyRate = $this->calculateHourlyRate($employee, $basicSalary);

        $totalGrossPay = 0;
        $basicPay = 0;
        $holidayPay = 0;
        $restDayPay = 0;
        $overtimePay = 0;
        $regularHours = 0;
        $overtimeHours = 0;
        $holidayHours = 0;
        $restDayHours = 0;

        // Detailed breakdowns
        $payBreakdown = [];
        $overtimeBreakdown = [];
        $holidayBreakdown = [];
        $restDayBreakdown = [];

        // Process each time log with its rate multiplier (exclude incomplete records)
        foreach ($timeLogs as $timeLog) {
            // Skip incomplete records - those marked as incomplete or missing time in/out
            if ($timeLog->remarks === 'Incomplete Time Record' || !$timeLog->time_in || !$timeLog->time_out) {
                continue;
            }

            if ($timeLog->total_hours <= 0) {
                continue;
            }

            // Calculate pay for this time log using rate configuration
            $payAmounts = $timeLog->calculatePayAmount($hourlyRate);
            $totalGrossPay += $payAmounts['total_amount'];

            // Add to hour totals
            $regularHours += $timeLog->regular_hours ?? 0;
            $overtimeHours += $timeLog->overtime_hours ?? 0;

            // Get rate configuration for breakdown display
            $rateConfig = $timeLog->getRateConfiguration();
            $displayName = $rateConfig ? $rateConfig->display_name : 'Regular Day';

            // Categorize pay by log type
            $logType = $timeLog->log_type;
            $regularAmount = $payAmounts['regular_amount'] ?? 0;
            $overtimeAmount = $payAmounts['overtime_amount'] ?? 0;

            // Track regular pay by category
            if ($regularAmount > 0) {
                if (!isset($payBreakdown[$displayName])) {
                    $payBreakdown[$displayName] = [
                        'hours' => 0,
                        'amount' => 0,
                        'rate' => $hourlyRate * ($rateConfig ? $rateConfig->regular_rate_multiplier : 1.0),
                    ];
                }
                $payBreakdown[$displayName]['hours'] += $timeLog->regular_hours ?? 0;
                $payBreakdown[$displayName]['amount'] += $regularAmount;
            }

            // Track overtime pay by category
            if ($overtimeAmount > 0) {
                $overtimeDisplayName = $displayName . ' OT';
                if (!isset($overtimeBreakdown[$overtimeDisplayName])) {
                    $overtimeBreakdown[$overtimeDisplayName] = [
                        'hours' => 0,
                        'amount' => 0,
                        'rate' => $hourlyRate * ($rateConfig ? $rateConfig->overtime_rate_multiplier : 1.25),
                    ];
                }
                $overtimeBreakdown[$overtimeDisplayName]['hours'] += $timeLog->overtime_hours ?? 0;
                $overtimeBreakdown[$overtimeDisplayName]['amount'] += $overtimeAmount;
            }

            // All overtime pay goes to overtime_pay regardless of day type
            $overtimePay += $overtimeAmount;

            // Categorize regular pay by day type
            if (str_contains($logType, 'holiday')) {
                $holidayPay += $regularAmount;
                $holidayHours += $timeLog->regular_hours ?? 0;

                // Track holiday breakdown
                if ($regularAmount > 0) {
                    if (!isset($holidayBreakdown[$displayName])) {
                        $holidayBreakdown[$displayName] = [
                            'hours' => 0,
                            'amount' => 0,
                            'rate' => $hourlyRate * ($rateConfig ? $rateConfig->regular_rate_multiplier : 1.0),
                        ];
                    }
                    $holidayBreakdown[$displayName]['hours'] += $timeLog->regular_hours ?? 0;
                    $holidayBreakdown[$displayName]['amount'] += $regularAmount;
                }
            } elseif (str_contains($logType, 'rest_day')) {
                // Rest day work is separate category
                $restDayPay += $regularAmount;
                $restDayHours += $timeLog->regular_hours ?? 0;

                // Track rest day breakdown
                if ($regularAmount > 0) {
                    if (!isset($restDayBreakdown[$displayName])) {
                        $restDayBreakdown[$displayName] = [
                            'hours' => 0,
                            'amount' => 0,
                            'rate' => $hourlyRate * ($rateConfig ? $rateConfig->regular_rate_multiplier : 1.0),
                        ];
                    }
                    $restDayBreakdown[$displayName]['hours'] += $timeLog->regular_hours ?? 0;
                    $restDayBreakdown[$displayName]['amount'] += $regularAmount;
                }
            } else {
                // Regular workday and other types
                $basicPay += $regularAmount;
            }
        }

        return [
            'total_gross' => $totalGrossPay,
            'basic_pay' => $basicPay,
            'holiday_pay' => $holidayPay,
            'rest_day_pay' => $restDayPay,
            'overtime_pay' => $overtimePay,
            'regular_hours' => $regularHours,
            'overtime_hours' => $overtimeHours,
            'holiday_hours' => $holidayHours,
            'rest_day_hours' => $restDayHours,
            'pay_breakdown' => $payBreakdown,
            'overtime_breakdown' => $overtimeBreakdown,
            'holiday_breakdown' => $holidayBreakdown,
            'rest_day_breakdown' => $restDayBreakdown,
        ];
    }

    private function calculateGrossPayWithRateMultipliers($employee, $basicSalary, $timeLogs, $hoursWorked, $daysWorked, $periodStart, $periodEnd)
    {
        // If no time worked and no time logs, fallback to basic calculation
        if ($timeLogs->isEmpty()) {
            return $this->calculateGrossPay($employee, $basicSalary, $hoursWorked, $daysWorked, $periodStart, $periodEnd);
        }

        // Calculate hourly rate based on pay schedule
        $hourlyRate = $this->calculateHourlyRate($employee, $basicSalary);

        $totalGrossPay = 0;
        $payBreakdown = [];

        // Process each time log with its rate multiplier (exclude incomplete records)
        foreach ($timeLogs as $timeLog) {
            // Skip incomplete records - those marked as incomplete or missing time in/out
            if ($timeLog->remarks === 'Incomplete Time Record' || !$timeLog->time_in || !$timeLog->time_out) {
                continue;
            }

            if ($timeLog->total_hours <= 0) {
                continue;
            }

            // Calculate pay for this time log using rate configuration
            $payAmounts = $timeLog->calculatePayAmount($hourlyRate);

            $totalGrossPay += $payAmounts['total_amount'];

            // Store breakdown for reporting
            $logTypeName = $timeLog->getRateConfiguration()->display_name ?? $timeLog->log_type;
            if (!isset($payBreakdown[$logTypeName])) {
                $payBreakdown[$logTypeName] = [
                    'regular_hours' => 0,
                    'regular_amount' => 0,
                    'overtime_hours' => 0,
                    'overtime_amount' => 0,
                    'total_amount' => 0,
                ];
            }

            $payBreakdown[$logTypeName]['regular_hours'] += $timeLog->regular_hours;
            $payBreakdown[$logTypeName]['regular_amount'] += $payAmounts['regular_amount'];
            $payBreakdown[$logTypeName]['overtime_hours'] += $timeLog->overtime_hours;
            $payBreakdown[$logTypeName]['overtime_amount'] += $payAmounts['overtime_amount'];
            $payBreakdown[$logTypeName]['total_amount'] += $payAmounts['total_amount'];
        }

        // Store pay breakdown for later use (can be saved to payroll details)
        // Removed currentPayBreakdown property assignment as it's not defined

        return $totalGrossPay;
    }

    /**
     * Calculate hourly rate based on employee's pay schedule and basic salary
     */
    private function calculateHourlyRate($employee, $basicSalary)
    {
        // If employee has an explicit hourly rate, use it
        if ($employee->hourly_rate && $employee->hourly_rate > 0) {
            return $employee->hourly_rate;
        }

        // Calculate hourly rate based on pay schedule
        switch ($employee->pay_schedule) {
            case 'daily':
                // For daily, basic salary is already daily rate, convert to hourly
                return $basicSalary / 8; // Assuming 8 hours per day

            case 'weekly':
                // Convert weekly salary to hourly
                return $basicSalary / 40; // Assuming 40 hours per week

            case 'semi_monthly':
                // Convert semi-monthly salary to hourly
                return $basicSalary / 86.67; // Assuming ~86.67 hours per semi-month

            case 'monthly':
                // Convert monthly salary to hourly
                return $basicSalary / 173.33; // Assuming ~173.33 hours per month

            default:
                // Default calculation
                return $basicSalary / 173.33;
        }
    }

    /**
     * Calculate deductions for an employee using dynamic settings
     */
    private function calculateDeductions($employee, $grossPay, $basicPay = null, $overtimePay = 0, $allowances = 0, $bonuses = 0)
    {
        $basicPay = $basicPay ?? $grossPay;
        $deductions = [];
        $total = 0;

        // Get active deduction settings that apply to this employee's benefit status
        $deductionSettings = \App\Models\DeductionTaxSetting::active()
            ->where('type', 'government')
            ->forBenefitStatus($employee->benefits_status)
            ->orderBy('sort_order')
            ->get();

        $governmentTotal = 0;

        // Calculate government deductions (SSS, PhilHealth, Pag-IBIG)
        foreach ($deductionSettings as $setting) {
            if ($setting->tax_table_type !== 'withholding_tax') {
                $amount = $setting->calculateDeduction($basicPay, $overtimePay, $bonuses, $allowances, $grossPay);

                if ($amount > 0) {
                    $deductions[$setting->code] = $amount;
                    $total += $amount;
                    $governmentTotal += $amount;
                }
            }
        }

        // Calculate taxable income (gross pay minus government deductions)
        $taxableIncome = $grossPay - $governmentTotal;

        // Calculate withholding tax based on taxable income
        $taxSettings = \App\Models\DeductionTaxSetting::active()
            ->where('type', 'government')
            ->where('tax_table_type', 'withholding_tax')
            ->forBenefitStatus($employee->benefits_status)
            ->get();

        foreach ($taxSettings as $setting) {
            $amount = $setting->calculateDeduction($basicPay, $overtimePay, $bonuses, $allowances, $grossPay, $taxableIncome);

            if ($amount > 0) {
                $deductions[$setting->code] = $amount;
                $total += $amount;
            }
        }

        // Get other custom deductions
        $customDeductions = \App\Models\DeductionSetting::where('is_active', true)
            ->where('type', 'custom')
            ->get();

        foreach ($customDeductions as $setting) {
            $amount = $this->calculateCustomDeduction($setting, $employee, $basicPay, $grossPay);

            if ($amount > 0) {
                $deductions[$setting->code] = $amount;
                $total += $amount;
            }
        }

        // Return standard structure for backward compatibility
        return [
            'sss' => $deductions['sss'] ?? 0,
            'philhealth' => $deductions['philhealth'] ?? 0,
            'pagibig' => $deductions['pagibig'] ?? 0,
            'tax' => $deductions['withholding_tax'] ?? 0,
            'other' => array_sum(array_filter($deductions, function ($key) {
                return !in_array($key, ['sss', 'philhealth', 'pagibig', 'withholding_tax']);
            }, ARRAY_FILTER_USE_KEY)),
            'total' => $total,
            'details' => $deductions
        ];
    }

    /**
     * Calculate custom deduction amount
     */
    private function calculateCustomDeduction($setting, $employee, $basicPay, $grossPay)
    {
        switch ($setting->calculation_type) {
            case 'percentage':
                return ($grossPay * $setting->rate) / 100;

            case 'fixed':
                return $setting->fixed_amount;

            case 'tiered':
                // Implement tiered calculation based on salary thresholds
                if ($setting->salary_threshold && $grossPay >= $setting->salary_threshold) {
                    return $setting->fixed_amount;
                }
                return 0;

            case 'table_based':
                // Implement table-based calculation using rate_table
                if ($setting->rate_table) {
                    foreach ($setting->rate_table as $tier) {
                        if ($grossPay >= $tier['min'] && $grossPay <= $tier['max']) {
                            if (isset($tier['rate'])) {
                                return ($grossPay * $tier['rate']) / 100;
                            } elseif (isset($tier['amount'])) {
                                return $tier['amount'];
                            }
                        }
                    }
                }
                return 0;

            default:
                return 0;
        }
    }

    /**
     * Calculate allowances for an employee using dynamic settings
     */
    private function calculateAllowances($employee, $basicPay, $daysWorked = 0, $hoursWorked = 0)
    {
        $total = 0;
        $details = [];

        // Get active allowance settings that apply to this employee's benefit status
        $allowanceSettings = \App\Models\AllowanceBonusSetting::where('is_active', true)
            ->where('type', 'allowance')
            ->forBenefitStatus($employee->benefits_status)
            ->orderBy('sort_order')
            ->get();

        foreach ($allowanceSettings as $setting) {
            $amount = $this->calculateAllowanceBonusAmount($setting, $employee, $basicPay, $daysWorked, $hoursWorked);

            if ($amount > 0) {
                $details[$setting->code] = [
                    'name' => $setting->name,
                    'amount' => $amount,
                    'is_taxable' => $setting->is_taxable
                ];
                $total += $amount;
            }
        }

        return [
            'total' => $total,
            'details' => $details
        ];
    }

    /**
     * Calculate bonuses for an employee using dynamic settings
     */
    private function calculateBonuses($employee, $basicPay, $daysWorked = 0, $hoursWorked = 0)
    {
        $total = 0;
        $details = [];

        // Get active bonus settings that apply to this employee's benefit status
        $bonusSettings = \App\Models\AllowanceBonusSetting::where('is_active', true)
            ->where('type', 'bonus')
            ->forBenefitStatus($employee->benefits_status)
            ->orderBy('sort_order')
            ->get();

        foreach ($bonusSettings as $setting) {
            $amount = $this->calculateAllowanceBonusAmount($setting, $employee, $basicPay, $daysWorked, $hoursWorked);

            if ($amount > 0) {
                $details[$setting->code] = [
                    'name' => $setting->name,
                    'amount' => $amount,
                    'is_taxable' => $setting->is_taxable
                ];
                $total += $amount;
            }
        }

        return [
            'total' => $total,
            'details' => $details
        ];
    }

    /**
     * Calculate allowance/bonus amount based on setting configuration
     */
    private function calculateAllowanceBonusAmount($setting, $employee, $basicPay, $daysWorked, $hoursWorked)
    {
        $amount = 0;

        switch ($setting->calculation_type) {
            case 'percentage':
                $amount = ($basicPay * $setting->rate_percentage) / 100;
                break;

            case 'fixed_amount':
                $amount = $setting->fixed_amount;

                // Apply frequency multiplier
                if ($setting->frequency === 'daily' && $daysWorked > 0) {
                    $maxDays = $setting->max_days_per_period ?? $daysWorked;
                    $amount = $amount * min($daysWorked, $maxDays);
                }
                break;

            case 'daily_rate_multiplier':
                if ($employee->daily_rate) {
                    $amount = $employee->daily_rate * ($setting->multiplier ?? 1);

                    if ($setting->frequency === 'daily' && $daysWorked > 0) {
                        $maxDays = $setting->max_days_per_period ?? $daysWorked;
                        $amount = $amount * min($daysWorked, $maxDays);
                    }
                }
                break;
        }

        // Apply minimum and maximum constraints
        if ($setting->minimum_amount && $amount < $setting->minimum_amount) {
            $amount = $setting->minimum_amount;
        }

        if ($setting->maximum_amount && $amount > $setting->maximum_amount) {
            $amount = $setting->maximum_amount;
        }

        return $amount;
    }

    /**
     * Calculate simplified tax (placeholder) - kept for backward compatibility
     */
    private function calculateTax($grossPay)
    {
        // Very simplified tax calculation
        if ($grossPay <= 20833) {
            return 0; // Tax exempt
        }

        return ($grossPay - 20833) * 0.20; // 20% on excess
    }

    /**
     * Store automation payroll - now redirects to draft list instead of creating payrolls
     */
    public function automationStore(Request $request)
    {
        $this->authorize('create payrolls');

        $validated = $request->validate([
            'selected_period' => 'required|string',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        // Extract schedule from selected_period (format: "schedule_code|start_date|end_date")
        $periodParts = explode('|', $validated['selected_period']);
        $scheduleCode = $periodParts[0] ?? 'semi_monthly';

        // Redirect to draft list instead of creating payrolls
        return redirect()->route('payrolls.automation.list', $scheduleCode)
            ->with('success', 'Viewing draft payrolls. Review and process individual employees as needed.');
    }

    /**
     * Manual Payroll - Show schedule selection
     */
    public function manualIndex()
    {
        $this->authorize('view payrolls');

        // Get all payroll schedule settings for selection (including disabled ones)
        $scheduleSettings = \App\Models\PayScheduleSetting::systemDefaults()
            ->orderBy('sort_order')
            ->get();

        // Calculate current periods and employee counts for each schedule
        foreach ($scheduleSettings as $setting) {
            // Get current period display
            $currentPeriods = $this->getCurrentPeriodDisplayForSchedule($setting);
            $setting->current_period_display = $currentPeriods;

            // Calculate next pay period (use current for manual too)
            $setting->next_period = $this->calculateCurrentPayPeriod($setting);

            // Count all employees for this schedule (active and inactive)
            $setting->total_employees_count = \App\Models\Employee::where('pay_schedule', $setting->code)
                ->count();

            // Count active employees for this schedule
            $setting->active_employees_count = \App\Models\Employee::where('pay_schedule', $setting->code)
                ->where('employment_status', 'active')
                ->count();

            // Get last payroll date if exists
            $lastPayroll = \App\Models\Payroll::where('pay_schedule', $setting->code)
                ->orderBy('pay_date', 'desc')
                ->first();

            if ($lastPayroll) {
                $setting->last_payroll_date = $lastPayroll->pay_date;
            }
        }

        return view('payrolls.manual.index', [
            'scheduleSettings' => $scheduleSettings
        ]);
    }

    /**
     * Manual Payroll - Show employee selection
     */
    public function manualCreate(Request $request)
    {
        $this->authorize('create payrolls');

        // Get the selected pay schedule
        $scheduleCode = $request->input('schedule');

        if (!$scheduleCode) {
            return redirect()->route('payrolls.manual.index')
                ->with('error', 'Please select a pay schedule.');
        }

        // Get schedule setting
        $selectedSchedule = \App\Models\PayScheduleSetting::systemDefaults()
            ->where('code', $scheduleCode)
            ->first();

        if (!$selectedSchedule) {
            return redirect()->route('payrolls.manual.index')
                ->with('error', 'Invalid pay schedule selected.');
        }

        // Get suggested pay period (current period)
        $suggestedPeriod = $this->calculateCurrentPayPeriod($selectedSchedule);

        // Get suggested payroll number
        $suggestedPayrollNumber = $this->generatePayrollNumber($scheduleCode);

        // Get employees for this schedule (both active and inactive for manual selection)
        $employees = Employee::with(['user', 'department', 'position'])
            ->where('pay_schedule', $scheduleCode)
            ->orderByRaw("CASE WHEN employment_status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('first_name')
            ->get();

        // Get departments for filtering
        $departments = \App\Models\Department::orderBy('name')->get();

        return view('payrolls.manual.create', compact(
            'selectedSchedule',
            'suggestedPeriod',
            'suggestedPayrollNumber',
            'employees',
            'departments'
        ));
    }

    /**
     * Store manual payroll
     */
    public function manualStore(Request $request)
    {
        $this->authorize('create payrolls');

        $validated = $request->validate([
            'selected_period' => 'required|string',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        // Use the same store logic but mark as manual
        return $this->processPayrollCreation($validated, 'manual');
    }

    /**
     * Process payroll creation (shared logic)
     */
    private function processPayrollCreation($validated, $type = 'regular')
    {
        // Parse the selected period data
        $periodData = json_decode(base64_decode($validated['selected_period']), true);

        if (!$periodData) {
            return back()->withErrors(['selected_period' => 'Invalid period selection.'])->withInput();
        }

        // Validate that selected employees match the pay schedule
        $selectedEmployees = Employee::whereIn('id', $validated['employee_ids'])->get();
        $invalidEmployees = $selectedEmployees->where('pay_schedule', '!=', $periodData['pay_schedule']);

        if ($invalidEmployees->count() > 0) {
            return back()->withErrors([
                'employee_ids' => 'All selected employees must have the same pay schedule as the selected period.'
            ])->withInput();
        }

        DB::beginTransaction();
        try {
            // Create payroll 
            $payroll = Payroll::create([
                'payroll_number' => Payroll::generatePayrollNumber($type),
                'period_start' => $periodData['period_start'],
                'period_end' => $periodData['period_end'],
                'pay_date' => $periodData['pay_date'],
                'payroll_type' => $type,
                'pay_schedule' => $periodData['pay_schedule'],
                'description' => ucfirst($type) . ' payroll for ' . $periodData['period_display'],
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            $totalGross = 0;
            $totalDeductions = 0;
            $totalNet = 0;
            $processedEmployees = 0;

            // Create payroll details for each employee
            foreach ($validated['employee_ids'] as $employeeId) {
                try {
                    $employee = Employee::find($employeeId);

                    if (!$employee) {
                        Log::warning("Employee with ID {$employeeId} not found");
                        continue;
                    }

                    // Calculate payroll details
                    $payrollDetail = $this->calculateEmployeePayroll($employee, $payroll);

                    $totalGross += $payrollDetail->gross_pay;
                    $totalDeductions += $payrollDetail->total_deductions;
                    $totalNet += $payrollDetail->net_pay;
                    $processedEmployees++;
                } catch (\Exception $e) {
                    Log::error("Failed to process employee {$employeeId}: " . $e->getMessage());
                    continue;
                }
            }

            if ($processedEmployees === 0) {
                throw new \Exception('No employees could be processed for payroll.');
            }

            // Update payroll totals
            $payroll->update([
                'total_gross' => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_net' => $totalNet,
            ]);

            DB::commit();

            return redirect()->route('payrolls.show', $payroll)
                ->with('success', "Payroll created successfully! {$processedEmployees} employees processed.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create payroll: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to create payroll: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified payroll.
     */
    public function show($payroll)
    {
        $this->authorize('view payrolls');

        // Handle direct employee ID access - redirect to unified automation view
        if (is_numeric($payroll) && !Payroll::where('id', $payroll)->exists()) {
            // This is likely an employee ID, redirect to unified automation view
            $employee = Employee::find($payroll);
            if ($employee) {
                return redirect()->route('payrolls.automation.show', [
                    'schedule' => $employee->pay_schedule,
                    'employee' => $payroll
                ]);
            }
        }

        // Handle regular payroll
        if (!($payroll instanceof Payroll)) {
            $payroll = Payroll::findOrFail($payroll);
        }

        // For automated payrolls with single employee, redirect to new URL format
        if ($payroll->payroll_type === 'automated' && $payroll->payrollDetails->count() === 1) {
            $employeeId = $payroll->payrollDetails->first()->employee_id;
            return redirect()->route('payrolls.automation.show', [
                'schedule' => $payroll->pay_schedule,
                'employee' => $employeeId
            ]);
        }

        // Auto-recalculate if needed (for draft payrolls)
        $this->autoRecalculateIfNeeded($payroll);

        $payroll->load([
            'payrollDetails.employee.user',
            'payrollDetails.employee.department',
            'payrollDetails.employee.position',
            'payrollDetails.employee.daySchedule',
            'payrollDetails.employee.timeSchedule',
            'creator',
            'approver'
        ]);

        // Get DTR data for all employees in the payroll period
        $employeeIds = $payroll->payrollDetails->pluck('employee_id');

        // Create array of all dates in the payroll period
        $startDate = \Carbon\Carbon::parse($payroll->period_start);
        $endDate = \Carbon\Carbon::parse($payroll->period_end);
        $periodDates = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $periodDates[] = $date->format('Y-m-d');
        }

        // Get all time logs for this payroll period
        $timeLogs = TimeLog::whereIn('employee_id', $employeeIds)
            ->whereBetween('log_date', [$payroll->period_start, $payroll->period_end])
            ->orderBy('log_date')
            ->get()
            ->groupBy(['employee_id', function ($item) {
                return \Carbon\Carbon::parse($item->log_date)->format('Y-m-d');
            }]);

        // Organize DTR data by employee and date
        $dtrData = [];
        $timeBreakdowns = []; // New: detailed time breakdown by type

        foreach ($payroll->payrollDetails as $detail) {
            $employeeTimeLogs = $timeLogs->get($detail->employee_id, collect());
            $employeeDtr = [];

            // Initialize breakdown tracking
            $employeeBreakdown = [];

            foreach ($periodDates as $date) {
                $timeLog = $employeeTimeLogs->get($date, collect())->first();

                // For ALL payrolls, add dynamic calculation to time log object for DTR display
                // This ensures DTR Summary always shows the correct times with grace periods applied
                if ($timeLog && $timeLog->time_in && $timeLog->time_out && $timeLog->remarks !== 'Incomplete Time Record') {
                    $dynamicCalculation = $this->calculateTimeLogHoursDynamically($timeLog);
                    $timeLog->dynamic_regular_hours = $dynamicCalculation['regular_hours'];
                    $timeLog->dynamic_overtime_hours = $dynamicCalculation['overtime_hours'];
                    $timeLog->dynamic_regular_overtime_hours = $dynamicCalculation['regular_overtime_hours'] ?? 0;
                    $timeLog->dynamic_night_diff_overtime_hours = $dynamicCalculation['night_diff_overtime_hours'] ?? 0;
                    $timeLog->dynamic_total_hours = $dynamicCalculation['total_hours'];

                    // Debug logging for processing payrolls
                    if ($payroll->status === 'processing') {
                        Log::info("Setting dynamic properties for processing payroll", [
                            'employee_id' => $detail->employee_id,
                            'date' => $date,
                            'stored_regular_hours' => $timeLog->regular_hours,
                            'dynamic_regular_hours' => $timeLog->dynamic_regular_hours,
                            'time_in' => $timeLog->time_in,
                            'time_out' => $timeLog->time_out
                        ]);
                    }
                }

                $employeeDtr[$date] = $timeLog;

                // Track time breakdown by type (exclude incomplete records)
                if ($timeLog && !($timeLog->remarks === 'Incomplete Time Record' || (!$timeLog->time_in || !$timeLog->time_out))) {
                    $logType = $timeLog->log_type;
                    if (!isset($employeeBreakdown[$logType])) {
                        $employeeBreakdown[$logType] = [
                            'regular_hours' => 0,
                            'overtime_hours' => 0,
                            'regular_overtime_hours' => 0,
                            'night_diff_overtime_hours' => 0,
                            'night_diff_regular_hours' => 0, // ADD: Missing night differential regular hours
                            'total_hours' => 0,
                            'days_count' => 0,
                            'display_name' => '',
                            'rate_config' => null
                        ];
                    }

                    // Always calculate dynamically using current grace periods for consistency
                    // This ensures the breakdown matches what's shown in DTR and used in calculations
                    $dynamicCalculation = $this->calculateTimeLogHoursDynamically($timeLog);
                    $regularHours = $dynamicCalculation['regular_hours'];
                    $overtimeHours = $dynamicCalculation['overtime_hours'];
                    $regularOvertimeHours = $dynamicCalculation['regular_overtime_hours'] ?? 0;
                    $nightDiffOvertimeHours = $dynamicCalculation['night_diff_overtime_hours'] ?? 0;
                    $nightDiffRegularHours = $dynamicCalculation['night_diff_regular_hours'] ?? 0; // ADD: Extract night diff regular hours
                    $totalHours = $dynamicCalculation['total_hours'];

                    $employeeBreakdown[$logType]['regular_hours'] += $regularHours;
                    $employeeBreakdown[$logType]['overtime_hours'] += $overtimeHours;
                    $employeeBreakdown[$logType]['regular_overtime_hours'] += $regularOvertimeHours;
                    $employeeBreakdown[$logType]['night_diff_overtime_hours'] += $nightDiffOvertimeHours;
                    $employeeBreakdown[$logType]['night_diff_regular_hours'] += $nightDiffRegularHours; // ADD: Store night diff regular hours
                    $employeeBreakdown[$logType]['total_hours'] += $totalHours;
                    $employeeBreakdown[$logType]['days_count']++;

                    // Get rate configuration for this type
                    $rateConfig = $timeLog->getRateConfiguration();
                    if ($rateConfig) {
                        $employeeBreakdown[$logType]['display_name'] = $rateConfig->display_name;
                        $employeeBreakdown[$logType]['rate_config'] = $rateConfig;
                    }
                }
            }

            $dtrData[$detail->employee_id] = $employeeDtr;
            $timeBreakdowns[$detail->employee_id] = $employeeBreakdown;
        }

        // Determine if payroll uses dynamic calculations (needed for breakdown logic)
        $isDynamic = $payroll->isDynamic();

        // Calculate separate basic pay and holiday pay for each employee
        $payBreakdownByEmployee = [];
        foreach ($payroll->payrollDetails as $detail) {
            // For processing/approved/locked payrolls, ALWAYS use snapshot data if available
            if (in_array($payroll->status, ['processing', 'approved', 'locked'])) {
                $snapshot = $payroll->snapshots()->where('employee_id', $detail->employee_id)->first();
                if ($snapshot) {
                    // Check if detailed pay breakdown is available in settings_snapshot
                    $settingsSnapshot = is_string($snapshot->settings_snapshot)
                        ? json_decode($snapshot->settings_snapshot, true)
                        : $snapshot->settings_snapshot;

                    if (isset($settingsSnapshot['pay_breakdown'])) {
                        // Use detailed pay breakdown from snapshot
                        $payBreakdown = $settingsSnapshot['pay_breakdown'];
                        $payBreakdownByEmployee[$detail->employee_id] = [
                            'basic_pay' => $payBreakdown['basic_pay'] ?? 0,
                            'holiday_pay' => $payBreakdown['holiday_pay'] ?? 0,
                            'rest_day_pay' => $payBreakdown['rest_day_pay'] ?? 0,
                            'overtime_pay' => $payBreakdown['overtime_pay'] ?? 0,
                        ];
                    } else {
                        // Fallback to individual snapshot fields
                        $payBreakdownByEmployee[$detail->employee_id] = [
                            'basic_pay' => $snapshot->regular_pay ?? 0,
                            'holiday_pay' => $snapshot->holiday_pay ?? 0,
                            'rest_day_pay' => 0, // Not available in old snapshots
                            'overtime_pay' => $snapshot->overtime_pay ?? 0,
                        ];
                    }

                    // Log for debugging
                    Log::info("Using snapshot pay breakdown for employee {$detail->employee_id}", [
                        'basic_pay' => $payBreakdownByEmployee[$detail->employee_id]['basic_pay'],
                        'holiday_pay' => $payBreakdownByEmployee[$detail->employee_id]['holiday_pay'],
                        'rest_day_pay' => $payBreakdownByEmployee[$detail->employee_id]['rest_day_pay'],
                        'overtime_pay' => $payBreakdownByEmployee[$detail->employee_id]['overtime_pay'],
                        'snapshot_id' => $snapshot->id,
                        'payroll_status' => $payroll->status,
                        'employee_name' => $detail->employee->first_name . ' ' . $detail->employee->last_name
                    ]);

                    // CRITICAL: Force continue to skip dynamic calculation for processing payrolls
                    continue;
                }
            }

            // For draft payrolls or when no snapshot available, calculate dynamically
            $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
            $hourlyRate = $detail->employee->hourly_rate ?? 0;

            $basicPay = 0; // Regular workday pay only
            $holidayPay = 0; // All holiday-related pay
            $restPay = 0; // Rest day pay
            $overtimePay = 0; // Overtime pay

            foreach ($employeeBreakdown as $logType => $breakdown) {
                $rateConfig = $breakdown['rate_config'];
                if (!$rateConfig) continue;

                // Calculate pay amounts using rate multipliers
                $regularMultiplier = $rateConfig->regular_rate_multiplier ?? 1.0;
                $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 1.25;

                $regularPayAmount = $breakdown['regular_hours'] * $hourlyRate * $regularMultiplier;

                // Calculate overtime pay with night differential breakdown
                $overtimePayAmount = 0;
                $regularOvertimeHours = $breakdown['regular_overtime_hours'] ?? 0;
                $nightDiffOvertimeHours = $breakdown['night_diff_overtime_hours'] ?? 0;

                if ($regularOvertimeHours > 0 || $nightDiffOvertimeHours > 0) {
                    // Use breakdown calculation

                    // Regular overtime pay
                    if ($regularOvertimeHours > 0) {
                        $overtimePayAmount += $regularOvertimeHours * $hourlyRate * $overtimeMultiplier;
                    }

                    // Night differential overtime pay (overtime rate + night differential bonus)
                    if ($nightDiffOvertimeHours > 0) {
                        // Get night differential setting
                        $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
                        $nightDiffMultiplier = $nightDiffSetting ? $nightDiffSetting->rate_multiplier : 1.10;

                        // Combined rate: base overtime rate + night differential bonus
                        $combinedMultiplier = $overtimeMultiplier + ($nightDiffMultiplier - 1);
                        $overtimePayAmount += $nightDiffOvertimeHours * $hourlyRate * $combinedMultiplier;
                    }
                } else {
                    // Fallback to simple calculation if no breakdown available
                    $overtimePayAmount = $breakdown['overtime_hours'] * $hourlyRate * $overtimeMultiplier;
                }

                if ($logType === 'regular_workday') {
                    $basicPay += $regularPayAmount; // Only regular pay to basic pay
                    $overtimePay += $overtimePayAmount; // Overtime pay separate
                } elseif ($logType === 'rest_day') {
                    $restPay += ($regularPayAmount + $overtimePayAmount); // Rest day pay includes both
                } elseif (in_array($logType, ['special_holiday', 'regular_holiday', 'rest_day_regular_holiday', 'rest_day_special_holiday'])) {
                    $holidayPay += ($regularPayAmount + $overtimePayAmount); // Holiday pay includes both
                }
            }

            $payBreakdownByEmployee[$detail->employee_id] = [
                'basic_pay' => $basicPay,
                'holiday_pay' => $holidayPay,
                'rest_day_pay' => $restPay,
                'overtime_pay' => $overtimePay,
            ];
        }

        // Load current dynamic settings for display
        $allowanceSettings = collect();
        $bonusSettings = collect();
        $deductionSettings = collect();

        if ($isDynamic) {
            // Get current active settings for draft payrolls
            $allowanceSettings = \App\Models\AllowanceBonusSetting::where('is_active', true)
                ->where('type', 'allowance')
                ->orderBy('sort_order')
                ->get();
            $bonusSettings = \App\Models\AllowanceBonusSetting::where('is_active', true)
                ->where('type', 'bonus')
                ->orderBy('sort_order')
                ->get();
            $deductionSettings = \App\Models\DeductionTaxSetting::active()
                ->orderBy('sort_order')
                ->get();
        } else {
            // For processing/approved payrolls, load and use snapshot data
            $snapshots = $payroll->snapshots()->get();

            if ($snapshots->isNotEmpty()) {
                // Update payroll details with snapshot values to ensure consistency
                foreach ($payroll->payrollDetails as $detail) {
                    $snapshot = $snapshots->where('employee_id', $detail->employee_id)->first();
                    if ($snapshot) {
                        // Override detail values with snapshot values for display consistency
                        $detail->basic_salary = $snapshot->basic_salary;
                        $detail->daily_rate = $snapshot->daily_rate;
                        $detail->hourly_rate = $snapshot->hourly_rate;
                        $detail->days_worked = $snapshot->days_worked;
                        $detail->regular_hours = $snapshot->regular_hours;
                        $detail->overtime_hours = $snapshot->overtime_hours;
                        $detail->holiday_hours = $snapshot->holiday_hours;
                        $detail->regular_pay = $snapshot->regular_pay;
                        $detail->overtime_pay = $snapshot->overtime_pay;
                        $detail->holiday_pay = $snapshot->holiday_pay;
                        $detail->allowances = $snapshot->allowances_total;
                        $detail->bonuses = $snapshot->bonuses_total;
                        $detail->gross_pay = $snapshot->gross_pay;
                        $detail->sss_contribution = $snapshot->sss_contribution;
                        $detail->philhealth_contribution = $snapshot->philhealth_contribution;
                        $detail->pagibig_contribution = $snapshot->pagibig_contribution;
                        $detail->withholding_tax = $snapshot->withholding_tax;
                        $detail->late_deductions = $snapshot->late_deductions;
                        $detail->undertime_deductions = $snapshot->undertime_deductions;
                        $detail->cash_advance_deductions = $snapshot->cash_advance_deductions;
                        $detail->other_deductions = $snapshot->other_deductions;
                        $detail->total_deductions = $snapshot->total_deductions;
                        $detail->net_pay = $snapshot->net_pay;

                        // CRITICAL: Set breakdown data from snapshots for display consistency
                        if ($snapshot->allowances_breakdown) {
                            $detail->earnings_breakdown = json_encode([
                                'allowances' => is_string($snapshot->allowances_breakdown)
                                    ? json_decode($snapshot->allowances_breakdown, true)
                                    : $snapshot->allowances_breakdown
                            ]);
                        }

                        // CRITICAL: Ensure deduction breakdown is properly set from snapshot
                        if ($snapshot->deductions_breakdown) {
                            $deductionBreakdown = is_string($snapshot->deductions_breakdown)
                                ? json_decode($snapshot->deductions_breakdown, true)
                                : $snapshot->deductions_breakdown;

                            // Set as a property that can be accessed in the view
                            $detail->deduction_breakdown = $deductionBreakdown;

                            // Log for debugging
                            Log::info("Setting deduction breakdown for employee {$detail->employee_id}", [
                                'payroll_id' => $payroll->id,
                                'breakdown' => $deductionBreakdown
                            ]);
                        }
                    }
                }

                // Get settings from snapshot for display
                $firstSnapshot = $snapshots->first();
                if ($firstSnapshot && $firstSnapshot->settings_snapshot) {
                    $settingsSnapshot = is_string($firstSnapshot->settings_snapshot)
                        ? json_decode($firstSnapshot->settings_snapshot, true)
                        : $firstSnapshot->settings_snapshot;

                    if (isset($settingsSnapshot['allowance_settings'])) {
                        $allowanceSettings = collect($settingsSnapshot['allowance_settings']);
                    }
                    if (isset($settingsSnapshot['bonus_settings'])) {
                        $bonusSettings = collect($settingsSnapshot['bonus_settings']);
                    }
                    if (isset($settingsSnapshot['deduction_settings'])) {
                        $deductionSettings = collect($settingsSnapshot['deduction_settings']);
                    }
                }
            } else {
                // No snapshots found - this shouldn't happen for processing/approved payrolls
                Log::warning("No snapshots found for non-dynamic payroll", [
                    'payroll_id' => $payroll->id,
                    'status' => $payroll->status
                ]);
            }
        }

        // Calculate totals for summary
        $totalBasicPay = array_sum(array_column($payBreakdownByEmployee, 'basic_pay'));
        $totalHolidayPay = array_sum(array_column($payBreakdownByEmployee, 'holiday_pay'));
        $totalRestDayPay = array_sum(array_column($payBreakdownByEmployee, 'rest_day_pay'));
        $totalOvertimePay = array_sum(array_column($payBreakdownByEmployee, 'overtime_pay'));

        return view('payrolls.show', compact(
            'payroll',
            'dtrData',
            'periodDates',
            'allowanceSettings',
            'bonusSettings',
            'deductionSettings',
            'isDynamic',
            'timeBreakdowns',
            'payBreakdownByEmployee',
            'totalBasicPay',
            'totalHolidayPay',
            'totalRestDayPay',
            'totalOvertimePay'
        ));
    }

    /**
     * Show the payslip view for all employees in the payroll.
     */
    public function payslip(Payroll $payroll)
    {
        $this->authorize('view payrolls');

        // Load necessary relationships
        $payroll->load([
            'payrollDetails.employee.user',
            'payrollDetails.employee.department',
            'payrollDetails.employee.position',
            'payrollDetails.employee.daySchedule',
            'payrollDetails.employee.timeSchedule',
            'creator',
            'approver'
        ]);

        // Apply the same snapshot logic as show method for approved/processing payrolls
        $isDynamic = $payroll->status === 'draft';

        if (!$isDynamic) {
            // For processing/approved payrolls, use snapshot data
            $snapshots = $payroll->snapshots()->get();
            if ($snapshots->isNotEmpty()) {
                // Update payroll details with snapshot values to ensure consistency
                foreach ($payroll->payrollDetails as $detail) {
                    $snapshot = $snapshots->where('employee_id', $detail->employee_id)->first();
                    if ($snapshot) {
                        // Override detail values with snapshot values
                        $detail->basic_salary = $snapshot->basic_salary;
                        $detail->daily_rate = $snapshot->daily_rate;
                        $detail->hourly_rate = $snapshot->hourly_rate;
                        $detail->days_worked = $snapshot->days_worked;
                        $detail->regular_hours = $snapshot->regular_hours;
                        $detail->overtime_hours = $snapshot->overtime_hours;
                        $detail->holiday_hours = $snapshot->holiday_hours;
                        $detail->regular_pay = $snapshot->regular_pay;
                        $detail->overtime_pay = $snapshot->overtime_pay;
                        $detail->holiday_pay = $snapshot->holiday_pay;
                        $detail->allowances = $snapshot->allowances_total;
                        $detail->bonuses = $snapshot->bonuses_total;
                        $detail->gross_pay = $snapshot->gross_pay;
                        $detail->sss_contribution = $snapshot->sss_contribution;
                        $detail->philhealth_contribution = $snapshot->philhealth_contribution;
                        $detail->pagibig_contribution = $snapshot->pagibig_contribution;
                        $detail->withholding_tax = $snapshot->withholding_tax;
                        $detail->late_deductions = $snapshot->late_deductions;
                        $detail->undertime_deductions = $snapshot->undertime_deductions;
                        $detail->cash_advance_deductions = $snapshot->cash_advance_deductions;
                        $detail->other_deductions = $snapshot->other_deductions;
                        $detail->total_deductions = $snapshot->total_deductions;
                        $detail->net_pay = $snapshot->net_pay;

                        // Set breakdown data from snapshots
                        if ($snapshot->allowances_breakdown) {
                            $detail->earnings_breakdown = json_encode([
                                'allowances' => is_string($snapshot->allowances_breakdown)
                                    ? json_decode($snapshot->allowances_breakdown, true)
                                    : $snapshot->allowances_breakdown
                            ]);
                        }

                        if ($snapshot->deductions_breakdown) {
                            $detail->deduction_breakdown = is_string($snapshot->deductions_breakdown)
                                ? json_decode($snapshot->deductions_breakdown, true)
                                : $snapshot->deductions_breakdown;
                        }
                    }
                }
            }
        }

        // Get company information (you may need to create a Company model or use settings)
        $company = (object)[
            'name' => config('app.name', 'Your Company Name'),
            'address' => 'Company Address, City, Province',
            'phone' => '+63 (000) 000-0000',
            'email' => 'hr@company.com'
        ];

        return view('payrolls.payslip', compact('payroll', 'company', 'isDynamic'));
    }

    /**
     * Show the form for editing the specified payroll.
     */
    public function edit(Payroll $payroll)
    {
        $this->authorize('edit payrolls');

        if (!$payroll->canBeEdited()) {
            return redirect()->route('payrolls.show', $payroll)
                ->with('error', 'This payroll cannot be edited.');
        }

        $payroll->load([
            'payrollDetails.employee.user',
            'payrollDetails.employee.department',
            'payrollDetails.employee.position'
        ]);

        return view('payrolls.edit', compact('payroll'));
    }

    /**
     * Update the specified payroll.
     */
    public function update(Request $request, Payroll $payroll)
    {
        $this->authorize('edit payrolls');

        if (!$payroll->canBeEdited()) {
            return redirect()->route('payrolls.show', $payroll)
                ->with('error', 'This payroll cannot be edited.');
        }

        $validated = $request->validate([
            'pay_date' => 'required|date|after_or_equal:period_end',
            'description' => 'nullable|string|max:1000',
            'payroll_details' => 'required|array',
            'payroll_details.*.allowances' => 'numeric|min:0',
            'payroll_details.*.bonuses' => 'numeric|min:0',
            'payroll_details.*.other_earnings' => 'numeric|min:0',
            'payroll_details.*.other_deductions' => 'numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Update payroll basic info
            $payroll->update([
                'pay_date' => $validated['pay_date'],
                'description' => $validated['description'],
            ]);

            $totalGross = 0;
            $totalDeductions = 0;
            $totalNet = 0;

            // Update payroll details
            foreach ($validated['payroll_details'] as $detailId => $detailData) {
                $detail = PayrollDetail::find($detailId);
                if ($detail && $detail->payroll_id == $payroll->id) {
                    $detail->update([
                        'allowances' => $detailData['allowances'] ?? 0,
                        'bonuses' => $detailData['bonuses'] ?? 0,
                        'other_earnings' => $detailData['other_earnings'] ?? 0,
                        'other_deductions' => $detailData['other_deductions'] ?? 0,
                    ]);

                    // Recalculate totals
                    $detail->gross_pay = $detail->regular_pay + $detail->overtime_pay +
                        $detail->holiday_pay + $detail->night_differential_pay +
                        $detail->allowances + $detail->bonuses + $detail->other_earnings;

                    $detail->calculateGovernmentContributions();
                    $detail->calculateWithholdingTax();
                    $detail->calculateTotalDeductions();
                    $detail->calculateNetPay();
                    $detail->save();

                    $totalGross += $detail->gross_pay;
                    $totalDeductions += $detail->total_deductions;
                    $totalNet += $detail->net_pay;
                }
            }

            // Update payroll totals
            $payroll->update([
                'total_gross' => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_net' => $totalNet,
            ]);

            DB::commit();

            return redirect()->route('payrolls.show', $payroll)
                ->with('success', 'Payroll updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update payroll: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified payroll.
     */
    public function destroy(Payroll $payroll)
    {
        // Temporarily disabled for testing
        // $this->authorize('delete payrolls');

        // Check if user can delete approved payrolls (temporarily allow all)
        $canDeleteApproved = true; // Auth::user()->can('delete approved payrolls');

        // If payroll is approved and user doesn't have permission to delete approved payrolls
        if ($payroll->status === 'approved' && !$canDeleteApproved) {
            return redirect()->route('payrolls.index')
                ->with('error', 'You do not have permission to delete approved payrolls.');
        }

        // If payroll is not approved, use the standard canBeEdited check (temporarily disabled)
        // if ($payroll->status !== 'approved' && !$payroll->canBeEdited()) {
        //     return redirect()->route('payrolls.index')
        //         ->with('error', 'This payroll cannot be deleted.');
        // }

        // Log the deletion for audit purposes
        Log::info('Payroll deleted', [
            'payroll_id' => $payroll->id,
            'payroll_number' => $payroll->payroll_number,
            'status' => $payroll->status,
            'deleted_by' => Auth::id(),
            'deleted_by_name' => Auth::user()->name
        ]);

        $payroll->delete();

        return redirect()->route('payrolls.index')
            ->with('success', 'Payroll deleted successfully!');
    }

    /**
     * Approve the specified payroll.
     */
    public function approve(Payroll $payroll)
    {
        $this->authorize('approve payrolls');

        if ($payroll->status !== 'processing') {
            return redirect()->route('payrolls.show', $payroll)
                ->with('error', 'Only processing payrolls can be approved.');
        }

        DB::beginTransaction();
        try {
            $payroll->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('payrolls.show', $payroll)
                ->with('success', 'Payroll approved successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve payroll', [
                'payroll_id' => $payroll->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('payrolls.show', $payroll)
                ->with('error', 'Failed to approve payroll: ' . $e->getMessage());
        }
    }

    /**
     * Process the specified payroll.
     */
    public function process(Payroll $payroll)
    {
        $this->authorize('process payrolls');

        Log::info("Process method called", [
            'payroll_id' => $payroll->id,
            'current_status' => $payroll->status,
            'user_id' => Auth::id()
        ]);

        if ($payroll->status !== 'draft') {
            Log::warning("Attempted to process non-draft payroll", [
                'payroll_id' => $payroll->id,
                'status' => $payroll->status
            ]);
            return redirect()->route('payrolls.show', $payroll)
                ->with('error', 'Only draft payrolls can be processed.');
        }

        DB::beginTransaction();
        try {
            Log::info("Starting payroll processing", [
                'payroll_id' => $payroll->id,
                'payroll_number' => $payroll->payroll_number,
                'employee_count' => $payroll->payrollDetails->count()
            ]);

            // Create snapshots for all payroll details (capture exact draft state)
            $this->createPayrollSnapshots($payroll);

            // Update payroll status
            $payroll->update([
                'status' => 'processing',
                'processing_started_at' => now(),
                'processing_by' => Auth::id(),
            ]);

            DB::commit();

            Log::info("Successfully processed payroll", [
                'payroll_id' => $payroll->id,
                'new_status' => $payroll->fresh()->status,
                'snapshot_count' => $payroll->snapshots()->count()
            ]);

            return redirect()->route('payrolls.show', $payroll)
                ->with('success', 'Payroll submitted for processing! Data has been locked as snapshots and will display the same calculations as when it was in draft mode.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process payroll', [
                'payroll_id' => $payroll->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('payrolls.show', $payroll)
                ->with('error', 'Failed to process payroll: ' . $e->getMessage());
        }
    }

    /**
     * Show dynamic payroll settings test page
     */
    public function testDynamic()
    {
        $this->authorize('view payrolls');

        // Get active deduction settings
        $deductionSettings = \App\Models\DeductionTaxSetting::active()
            ->orderBy('type')
            ->orderBy('sort_order')
            ->get();

        // Get active allowance/bonus settings
        $allowanceSettings = \App\Models\AllowanceBonusSetting::active()
            ->orderBy('type')
            ->orderBy('sort_order')
            ->get();

        return view('payrolls.test-dynamic', compact('deductionSettings', 'allowanceSettings'));
    }

    /**
     * Calculate employee payroll details based on approved DTR records
     */
    private function calculateEmployeePayroll(Employee $employee, Payroll $payroll)
    {
        // Check if payroll is in processing/approved state and has snapshots
        if ($payroll->usesSnapshot()) {
            return $this->getEmployeePayrollFromSnapshot($employee, $payroll);
        }

        // For draft payrolls, calculate dynamically
        return $this->calculateEmployeePayrollDynamic($employee, $payroll);
    }

    /**
     * Get employee payroll data from snapshot (for processing/approved payrolls)
     */
    private function getEmployeePayrollFromSnapshot(Employee $employee, Payroll $payroll)
    {
        $snapshot = $payroll->snapshots()->where('employee_id', $employee->id)->first();

        if (!$snapshot) {
            throw new \Exception("No snapshot found for employee {$employee->employee_number} in payroll {$payroll->payroll_number}");
        }

        // Create or update PayrollDetail from snapshot data
        $payrollDetail = PayrollDetail::updateOrCreate(
            [
                'payroll_id' => $payroll->id,
                'employee_id' => $employee->id,
            ],
            [
                'basic_salary' => $snapshot->basic_salary,
                'daily_rate' => $snapshot->daily_rate,
                'hourly_rate' => $snapshot->hourly_rate,
                'days_worked' => $snapshot->days_worked,
                'regular_hours' => $snapshot->regular_hours,
                'overtime_hours' => $snapshot->overtime_hours,
                'holiday_hours' => $snapshot->holiday_hours,
                'night_differential_hours' => $snapshot->night_differential_hours,
                'regular_pay' => $snapshot->regular_pay,
                'overtime_pay' => $snapshot->overtime_pay,
                'holiday_pay' => $snapshot->holiday_pay,
                'night_differential_pay' => $snapshot->night_differential_pay,
                'allowances' => $snapshot->allowances_total,
                'bonuses' => $snapshot->bonuses_total,
                'other_earnings' => $snapshot->other_earnings,
                'gross_pay' => $snapshot->gross_pay,
                'sss_contribution' => $snapshot->sss_contribution,
                'philhealth_contribution' => $snapshot->philhealth_contribution,
                'pagibig_contribution' => $snapshot->pagibig_contribution,
                'withholding_tax' => $snapshot->withholding_tax,
                'late_deductions' => $snapshot->late_deductions,
                'undertime_deductions' => $snapshot->undertime_deductions,
                'cash_advance_deductions' => $snapshot->cash_advance_deductions,
                'other_deductions' => $snapshot->other_deductions,
                'total_deductions' => $snapshot->total_deductions,
                'net_pay' => $snapshot->net_pay,
            ]
        );

        return $payrollDetail;
    }

    /**
     * Calculate employee payroll dynamically (for draft payrolls)
     */
    private function calculateEmployeePayrollDynamic(Employee $employee, Payroll $payroll)
    {
        // Get time logs for this payroll period
        $timeLogs = TimeLog::where('employee_id', $employee->id)
            ->whereBetween('log_date', [$payroll->period_start, $payroll->period_end])
            ->get();        // Initialize counters
        $daysWorked = 0;
        $regularHours = 0;
        $overtimeHours = 0;
        $holidayHours = 0;
        $nightDifferentialRegularHours = 0;
        $nightDifferentialOvertimeHours = 0;
        $lateHours = 0;
        $undertimeHours = 0;

        // Process each time log if available
        foreach ($timeLogs as $timeLog) {
            $daysWorked++;
            $regularHours += $timeLog->regular_hours ?? 0;
            $overtimeHours += $timeLog->overtime_hours ?? 0;
            $lateHours += $timeLog->late_hours ?? 0;
            $undertimeHours += $timeLog->undertime_hours ?? 0;

            // Add night differential hours
            $nightDifferentialRegularHours += $timeLog->night_diff_regular_hours ?? 0;
            $nightDifferentialOvertimeHours += $timeLog->night_diff_overtime_hours ?? 0;

            // Check if it's a holiday or rest day for premium calculations
            if ($timeLog->is_holiday) {
                $holidayHours += $timeLog->total_hours ?? 0;
            }
        }

        // Use employee's hourly rate directly, or calculate from basic salary if not set
        $hourlyRate = $employee->hourly_rate;
        if (!$hourlyRate && $employee->basic_salary) {
            // Calculate hourly rate based on pay schedule
            switch ($employee->pay_schedule) {
                case 'weekly':
                    $weeklyRate = $employee->weekly_rate ?? ($employee->basic_salary / 4.33);
                    $hourlyRate = $weeklyRate / 40; // 40 hours per week
                    break;
                case 'semi_monthly':
                    $semiMonthlyRate = $employee->semi_monthly_rate ?? ($employee->basic_salary / 2);
                    $hourlyRate = $semiMonthlyRate / 86.67; // ~86.67 hours per semi-month
                    break;
                default: // monthly
                    $hourlyRate = $employee->basic_salary / 173.33; // ~173.33 hours per month
                    break;
            }
        } elseif (!$hourlyRate) {
            // If no hourly rate and no basic salary, use a default or throw error
            throw new \Exception("Employee {$employee->employee_number} has no hourly rate or basic salary defined.");
        }

        $dailyRate = $hourlyRate * 8; // 8 hours per day

        // If no DTR records, set basic pay to zero (only pay for actual recorded hours)
        if ($timeLogs->isEmpty()) {
            $daysWorked = 0;
            $regularHours = 0; // No DTR records = no basic pay
        }

        // Calculate pay components
        $regularPay = $regularHours * $hourlyRate;
        $overtimePay = $overtimeHours * $hourlyRate * 1.25; // 25% overtime premium
        $holidayPay = $holidayHours * $hourlyRate * 2.0; // 100% holiday premium

        // Calculate night differential pay using dynamic rate
        $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
        $nightDiffMultiplier = $nightDiffSetting ? $nightDiffSetting->rate_multiplier : 1.10;
        $nightDiffBonus = $nightDiffMultiplier - 1; // e.g., 1.10 - 1 = 0.10 (10% bonus)

        $nightDifferentialPay = ($nightDifferentialRegularHours + $nightDifferentialOvertimeHours) * $hourlyRate * $nightDiffBonus;
        $totalNightDifferentialHours = $nightDifferentialRegularHours + $nightDifferentialOvertimeHours;

        // Calculate late and undertime deductions
        $lateDeductions = $lateHours * $hourlyRate;
        $undertimeDeductions = $undertimeHours * $hourlyRate;

        // Calculate allowances and bonuses from settings
        $allowancesData = $this->calculateEmployeeAllowances($employee, $payroll, $regularHours, $overtimeHours, $holidayHours);
        $bonusesData = $this->calculateEmployeeBonuses($employee, $payroll, $regularHours, $overtimeHours, $holidayHours);

        // Calculate cash advance deductions
        $cashAdvanceDeductions = $this->calculateCashAdvanceDeductions($employee, $payroll);

        // Create or update payroll detail
        $payrollDetail = PayrollDetail::updateOrCreate(
            [
                'payroll_id' => $payroll->id,
                'employee_id' => $employee->id,
            ],
            [
                'basic_salary' => $employee->basic_salary,
                'daily_rate' => $dailyRate,
                'hourly_rate' => $hourlyRate,
                'days_worked' => $daysWorked,
                'regular_hours' => $regularHours,
                'overtime_hours' => $overtimeHours,
                'holiday_hours' => $holidayHours,
                'night_differential_hours' => $totalNightDifferentialHours,
                'regular_pay' => $regularPay,
                'overtime_pay' => $overtimePay,
                'holiday_pay' => $holidayPay,
                'night_differential_pay' => $nightDifferentialPay,
                'allowances' => $allowancesData['total'],
                'bonuses' => $bonusesData['total'],
                'other_earnings' => 0,
                'late_deductions' => $lateDeductions,
                'undertime_deductions' => $undertimeDeductions,
                'cash_advance_deductions' => $cashAdvanceDeductions,
                'other_deductions' => 0,
                'earnings_breakdown' => json_encode([
                    'allowances' => $allowancesData['details'],
                    'bonuses' => $bonusesData['details'],
                ]),
            ]
        );

        // Calculate gross pay
        $payrollDetail->gross_pay = $payrollDetail->regular_pay +
            $payrollDetail->overtime_pay +
            $payrollDetail->holiday_pay +
            $payrollDetail->night_differential_pay +
            $payrollDetail->allowances +
            $payrollDetail->bonuses +
            $payrollDetail->other_earnings;

        // Calculate deductions using the PayrollDetail model methods with employer sharing
        $payrollDetail->calculateGovernmentContributionsWithSharing();
        $payrollDetail->calculateWithholdingTax();
        $payrollDetail->calculateTotalDeductions();
        $payrollDetail->calculateNetPay();

        $payrollDetail->save();

        return $payrollDetail;
    }

    /**
     * Calculate employee allowances based on active settings
     */
    private function calculateEmployeeAllowances(Employee $employee, Payroll $payroll, $regularHours, $overtimeHours, $holidayHours)
    {
        // Get current active allowance settings that apply to this employee's benefit status
        $allowanceSettings = \App\Models\AllowanceBonusSetting::where('is_active', true)
            ->where('type', 'allowance')
            ->forBenefitStatus($employee->benefits_status)
            ->orderBy('sort_order')
            ->get();

        $totalAllowances = 0;
        $allowanceDetails = [];

        foreach ($allowanceSettings as $setting) {
            $allowanceAmount = $this->calculateAllowanceBonusAmountForPayroll(
                $setting,
                $employee,
                $payroll,
                $regularHours,
                $overtimeHours,
                $holidayHours
            );

            if ($allowanceAmount > 0) {
                $allowanceDetails[$setting->code] = [
                    'name' => $setting->name,
                    'amount' => $allowanceAmount,
                    'is_taxable' => $setting->is_taxable
                ];
                $totalAllowances += $allowanceAmount;
            }
        }

        return [
            'total' => $totalAllowances,
            'details' => $allowanceDetails
        ];
    }

    /**
     * Calculate employee bonuses based on active settings
     */
    private function calculateEmployeeBonuses(Employee $employee, Payroll $payroll, $regularHours, $overtimeHours, $holidayHours)
    {
        // Get current active bonus settings that apply to this employee's benefit status
        $bonusSettings = \App\Models\AllowanceBonusSetting::where('is_active', true)
            ->where('type', 'bonus')
            ->forBenefitStatus($employee->benefits_status)
            ->orderBy('sort_order')
            ->get();

        $totalBonuses = 0;
        $bonusDetails = [];

        foreach ($bonusSettings as $setting) {
            $bonusAmount = $this->calculateAllowanceBonusAmountForPayroll(
                $setting,
                $employee,
                $payroll,
                $regularHours,
                $overtimeHours,
                $holidayHours
            );

            if ($bonusAmount > 0) {
                $bonusDetails[$setting->code] = [
                    'name' => $setting->name,
                    'amount' => $bonusAmount,
                    'is_taxable' => $setting->is_taxable
                ];
                $totalBonuses += $bonusAmount;
            }
        }

        return [
            'total' => $totalBonuses,
            'details' => $bonusDetails
        ];
    }

    /**
     * Calculate individual allowance or bonus amount for payroll
     */
    private function calculateAllowanceBonusAmountForPayroll($setting, $employee, $payroll, $regularHours, $overtimeHours, $holidayHours)
    {
        $amount = 0;

        switch ($setting->calculation_type) {
            case 'fixed_amount':
                $amount = $setting->fixed_amount ?? 0;

                // Apply frequency-based calculation
                if ($setting->frequency === 'daily') {
                    $daysWorked = $this->calculateDaysWorked($employee, $payroll);
                    $maxDays = $setting->max_days_per_period ?? $daysWorked;
                    $amount = $amount * min($daysWorked, $maxDays);
                } elseif ($setting->frequency === 'weekly') {
                    $weeksInPeriod = $this->calculateWeeksInPeriod($payroll);
                    $amount = $amount * $weeksInPeriod;
                }
                break;

            case 'percentage':
                // Calculate percentage of basic salary
                $baseAmount = $employee->basic_salary ?? 0;
                $amount = $baseAmount * (($setting->rate_percentage ?? 0) / 100);
                break;

            case 'per_day':
                // Calculate based on actual days worked
                $daysWorked = $this->calculateDaysWorked($employee, $payroll);
                $amount = ($setting->fixed_amount ?? 0) * $daysWorked;
                break;

            case 'per_hour':
                // Calculate based on hours worked
                $totalHours = $regularHours;

                if ($setting->apply_to_overtime ?? false) {
                    $totalHours += $overtimeHours;
                }

                if ($setting->apply_to_holidays ?? false) {
                    $totalHours += $holidayHours;
                }

                $amount = ($setting->fixed_amount ?? 0) * $totalHours;
                break;

            case 'multiplier':
                // Calculate as multiplier of hourly rate
                $hourlyRate = $employee->hourly_rate ?? ($employee->basic_salary / 173.33); // Default monthly to hourly
                $amount = $hourlyRate * ($setting->multiplier ?? 0) * $regularHours;
                break;

            case 'basic_salary_multiplier':
                // Calculate as multiplier of basic salary
                $amount = ($employee->basic_salary ?? 0) * ($setting->multiplier ?? 0);
                break;
        }

        // Apply minimum and maximum limits
        if ($setting->minimum_amount && $amount < $setting->minimum_amount) {
            $amount = $setting->minimum_amount;
        }

        if ($setting->maximum_amount && $amount > $setting->maximum_amount) {
            $amount = $setting->maximum_amount;
        }

        return round($amount, 2);
    }

    /**
     * Calculate actual days worked by employee in payroll period
     */
    private function calculateDaysWorked(Employee $employee, Payroll $payroll)
    {
        $timeLogs = TimeLog::where('employee_id', $employee->id)
            ->whereBetween('log_date', [$payroll->period_start, $payroll->period_end])
            ->where('regular_hours', '>', 0)
            ->count();

        return $timeLogs;
    }

    /**
     * Calculate number of weeks in payroll period
     */
    private function calculateWeeksInPeriod(Payroll $payroll)
    {
        $startDate = Carbon::parse($payroll->period_start);
        $endDate = Carbon::parse($payroll->period_end);

        return ceil($startDate->diffInDays($endDate) / 7);
    }

    /**
     * Calculate cash advance deductions for the employee (calculation only, no payment recording)
     */
    private function calculateCashAdvanceDeductions(Employee $employee, Payroll $payroll)
    {
        try {
            // Get active cash advances for this employee that should start deduction
            $cashAdvances = CashAdvance::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->where('outstanding_balance', '>', 0)
                ->get();

            $totalDeductions = 0;

            foreach ($cashAdvances as $cashAdvance) {
                // Check if this cash advance should be deducted in this payroll period
                if (!$this->shouldDeductCashAdvance($cashAdvance, $employee, $payroll)) {
                    continue;
                }

                // Calculate deduction amount based on frequency
                $deductionAmount = $this->calculateCashAdvanceDeductionAmount($cashAdvance, $employee, $payroll);

                // Ensure we don't deduct more than outstanding balance
                $deductionAmount = min($deductionAmount, $cashAdvance->outstanding_balance);

                if ($deductionAmount > 0) {
                    $totalDeductions += $deductionAmount;

                    // Note: Payment recording is only done when payroll is marked as paid
                    // This method only calculates the deduction amount for display purposes
                }
            }

            return $totalDeductions;
        } catch (\Exception $e) {
            // If there's an error (like missing table), return 0
            Log::warning('Cash advance calculation failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Check if a cash advance should be deducted in this payroll period
     */
    private function shouldDeductCashAdvance(CashAdvance $cashAdvance, Employee $employee, Payroll $payroll)
    {
        // Check if enough payroll periods have passed since the cash advance was approved
        if (!$this->hasReachedStartingPayrollPeriod($cashAdvance, $employee, $payroll)) {
            return false;
        }

        // For per_payroll frequency, deduct every payroll after reaching the starting period
        if ($cashAdvance->deduction_frequency === 'per_payroll') {
            return true;
        }

        // For monthly frequency, check timing based on employee pay schedule
        if ($cashAdvance->deduction_frequency === 'monthly') {
            return $this->isCorrectMonthlyPayrollForDeduction($cashAdvance, $employee, $payroll);
        }

        // Default to old behavior for backward compatibility
        if ($employee->pay_schedule === 'semi_monthly') {
            // Check if this is the last cutoff of the month
            return $payroll->pay_period_end->day >= 28 || $payroll->pay_period_end->isLastOfMonth();
        }

        return true;
    }

    /**
     * Check if enough payroll periods have passed to start deductions
     */
    private function hasReachedStartingPayrollPeriod(CashAdvance $cashAdvance, Employee $employee, Payroll $payroll)
    {
        // If no starting_payroll_period is set, use the old logic
        if (!$cashAdvance->starting_payroll_period) {
            return true; // Default to allowing deductions
        }

        // Special case: If starting_payroll_period is 1 (current), 
        // allow deduction immediately once approved
        if ($cashAdvance->starting_payroll_period == 1) {
            return true;
        }

        // Get the approval date of the cash advance
        $approvalDate = $cashAdvance->approved_date ?? $cashAdvance->requested_date;

        // Count how many payroll periods have occurred since approval for this employee
        $payrollsSinceApproval = \App\Models\Payroll::whereHas('payrollDetails', function ($query) use ($employee) {
            $query->where('employee_id', $employee->id);
        })
            ->where('period_start', '>', $approvalDate)
            ->where('period_start', '<=', $payroll->period_start)
            ->count();

        // Check if we've reached the starting payroll period
        // (starting_payroll_period: 1=current, 2=next, 3=2nd next, 4=3rd next)
        // For periods > 1, we need to skip (starting_payroll_period - 1) periods
        return $payrollsSinceApproval >= ($cashAdvance->starting_payroll_period - 1);
    }
    /**
     * Check if this is the correct payroll for monthly cash advance deduction
     */
    private function isCorrectMonthlyPayrollForDeduction(CashAdvance $cashAdvance, Employee $employee, Payroll $payroll)
    {
        $payPeriodEnd = Carbon::parse($payroll->pay_period_end);
        $payPeriodStart = Carbon::parse($payroll->pay_period_start);

        // For monthly employees, there's only one payroll per month
        if ($employee->pay_schedule === 'monthly') {
            return true;
        }

        // For semi-monthly employees
        if ($employee->pay_schedule === 'semi_monthly') {
            if ($cashAdvance->monthly_deduction_timing === 'first_payroll') {
                // First cutoff: payroll that starts in first half of month (1st-15th)
                return $payPeriodStart->day <= 15;
            } else {
                // Last cutoff: payroll that ends in second half of month (16th-end)
                return $payPeriodEnd->day >= 16;
            }
        }

        // For weekly employees
        if ($employee->pay_schedule === 'weekly') {
            if ($cashAdvance->monthly_deduction_timing === 'first_payroll') {
                // First payroll of month: payroll that includes the 1st day of the month
                return $payPeriodStart->day <= 7;
            } else {
                // Last payroll of month: payroll that includes the last week of the month
                $lastDayOfMonth = $payPeriodEnd->copy()->endOfMonth();
                return $payPeriodEnd->diffInDays($lastDayOfMonth) <= 6;
            }
        }

        return true; // Default behavior
    }

    /**
     * Calculate the deduction amount for a cash advance
     */
    private function calculateCashAdvanceDeductionAmount(CashAdvance $cashAdvance, Employee $employee, Payroll $payroll)
    {
        if ($cashAdvance->deduction_frequency === 'monthly') {
            // For monthly deductions, use total amount divided by monthly installments
            $monthlyInstallments = $cashAdvance->monthly_installments ?? 1;
            return $cashAdvance->total_amount / $monthlyInstallments;
        } else {
            // For per_payroll deductions, use the regular installment amount
            return $cashAdvance->installment_amount ?? 0;
        }
    }

    /**
     * Calculate night differential hours for a time log
     */
    private function calculateNightDifferential(TimeLog $timeLog)
    {
        if (!$timeLog->time_in || !$timeLog->time_out) {
            return 0;
        }

        try {
            $nightStart = Carbon::createFromFormat('H:i', '22:00');
            $nightEnd = Carbon::createFromFormat('H:i', '06:00')->addDay();

            // Get just the time part for comparison
            $timeInStr = is_string($timeLog->time_in) ? $timeLog->time_in : $timeLog->time_in->format('H:i:s');
            $timeOutStr = is_string($timeLog->time_out) ? $timeLog->time_out : $timeLog->time_out->format('H:i:s');

            $timeIn = Carbon::parse($timeLog->log_date->format('Y-m-d') . ' ' . $timeInStr);
            $timeOut = Carbon::parse($timeLog->log_date->format('Y-m-d') . ' ' . $timeOutStr);

            // If time out is earlier than time in, it means work continued to next day
            if ($timeOut->lessThan($timeIn)) {
                $timeOut->addDay();
            }

            $nightDifferentialHours = 0;

            // Check overlap with night hours (10PM - 6AM)
            $nightStartDate = Carbon::parse($timeLog->log_date->format('Y-m-d') . ' 22:00');
            $nightEndDate = Carbon::parse($timeLog->log_date->format('Y-m-d') . ' 06:00')->addDay();

            $overlapStart = $timeIn->greaterThan($nightStartDate) ? $timeIn : $nightStartDate;
            $overlapEnd = $timeOut->lessThan($nightEndDate) ? $timeOut : $nightEndDate;

            if ($overlapStart->lessThan($overlapEnd)) {
                $nightDifferentialHours = $overlapEnd->diffInHours($overlapStart, true);
            }

            return $nightDifferentialHours;
        } catch (\Exception $e) {
            // If there's an error parsing times, return 0 night differential
            Log::warning('Error calculating night differential for time log ' . $timeLog->id . ': ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Generate payroll from approved DTR records
     */
    public function generateFromDTR(Request $request)
    {
        $this->authorize('create payrolls');

        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'pay_date' => 'required|date|after_or_equal:period_end',
            'payroll_type' => 'required|in:regular,special,13th_month,bonus',
            'description' => 'nullable|string|max:1000',
        ]);

        // Get employees with DTR records in the period
        $employeesWithDTR = Employee::whereHas('timeLogs', function ($query) use ($validated) {
            $query->whereBetween('log_date', [$validated['period_start'], $validated['period_end']]);
        })
            ->with(['user', 'department', 'position'])
            ->where('employment_status', 'active')
            ->get();

        if ($employeesWithDTR->isEmpty()) {
            return back()->withErrors(['error' => 'No employees with approved DTR records found for the selected period.']);
        }

        DB::beginTransaction();
        try {
            // Create payroll
            $payroll = Payroll::create([
                'payroll_number' => Payroll::generatePayrollNumber($validated['payroll_type']),
                'period_start' => $validated['period_start'],
                'period_end' => $validated['period_end'],
                'pay_date' => $validated['pay_date'],
                'payroll_type' => $validated['payroll_type'],
                'description' => $validated['description'] ?: 'Generated from approved DTR records',
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            $totalGross = 0;
            $totalDeductions = 0;
            $totalNet = 0;

            // Create payroll details for each employee with approved DTR
            foreach ($employeesWithDTR as $employee) {
                $payrollDetail = $this->calculateEmployeePayroll($employee, $payroll);

                $totalGross += $payrollDetail->gross_pay;
                $totalDeductions += $payrollDetail->total_deductions;
                $totalNet += $payrollDetail->net_pay;
            }

            // Update payroll totals
            $payroll->update([
                'total_gross' => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_net' => $totalNet,
            ]);

            DB::commit();

            return redirect()->route('payrolls.show', $payroll)
                ->with('success', "Payroll generated successfully from DTR records! {$employeesWithDTR->count()} employees included.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to generate payroll from DTR: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to generate payroll from DTR: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Calculate automatic payroll period based on current date and schedule settings
     */
    private function calculateAutomaticPayrollPeriod($scheduleType = 'weekly')
    {
        $today = \Carbon\Carbon::now();

        // Get the specific schedule setting for the requested type
        $setting = \App\Models\PayrollScheduleSetting::where('pay_type', $scheduleType)->first();

        if ($setting) {
            $period = $this->calculatePeriodForSchedule($setting, $today);
            if ($period) {
                return [
                    'schedule_type' => $setting->pay_type,
                    'period_start' => $period['start'],
                    'period_end' => $period['end'],
                    'pay_date' => $period['pay_date'],
                    'period_name' => $period['name'],
                    'cut_off_day' => $setting->cutoff_start_day,
                    'pay_day' => $setting->payday_offset_days
                ];
            }
        }

        // Fallback calculation based on schedule type
        return $this->getFallbackPeriod($scheduleType, $today);
    }

    /**
     * Get available periods for a schedule setting
     */
    private function getAvailablePeriodsForSchedule($setting)
    {
        $today = \Carbon\Carbon::now();
        $periods = [];

        switch ($setting->pay_type) {
            case 'weekly':
                // Get current week and next 2 weeks
                for ($i = 0; $i < 3; $i++) {
                    $weekStart = $today->copy()->addWeeks($i)->startOfWeek(\Carbon\Carbon::MONDAY);
                    $weekEnd = $weekStart->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
                    $payDate = $weekEnd->copy()->addDays($setting->payday_offset_days);

                    $periods[] = [
                        'id' => $setting->pay_type . '_' . $weekStart->format('Y_m_d'),
                        'pay_schedule' => $setting->pay_type,
                        'period_display' => $weekStart->format('M j') . ' - ' . $weekEnd->format('M j, Y'),
                        'pay_date_display' => $payDate->format('M d, Y'),
                        'period_start' => $weekStart->format('Y-m-d'),
                        'period_end' => $weekEnd->format('Y-m-d'),
                        'pay_date' => $payDate->format('Y-m-d'),
                        'setting_id' => $setting->id
                    ];
                }
                break;

            case 'semi_monthly':
                // Get current and next 2 semi-monthly periods
                for ($i = 0; $i < 3; $i++) {
                    $baseDate = $today->copy()->addMonths(floor($i / 2));
                    $isSecondHalf = ($i % 2 === 1);

                    if ($isSecondHalf || ($i === 0 && $today->day > 15)) {
                        // Second half of month (16th to end)
                        $periodStart = $baseDate->copy()->startOfMonth()->addDays(15); // 16th
                        $periodEnd = $baseDate->copy()->endOfMonth();
                        $payDate = $baseDate->copy()->addMonth()->startOfMonth()->addDays(4); // 5th of next month
                        $period = $periodStart->format('M j') . ' - ' . $periodEnd->format('M j, Y');
                    } else {
                        // First half of month (1st to 15th)
                        $periodStart = $baseDate->copy()->startOfMonth();
                        $periodEnd = $baseDate->copy()->startOfMonth()->addDays(14); // 15th
                        $payDate = $baseDate->copy()->startOfMonth()->addDays(19); // 20th of same month
                        $period = $periodStart->format('M j') . ' - ' . $periodEnd->format('j, Y');
                    }

                    $periods[] = [
                        'id' => $setting->pay_type . '_' . $periodStart->format('Y_m_d'),
                        'pay_schedule' => $setting->pay_type,
                        'period_display' => $period,
                        'pay_date_display' => $payDate->format('M d, Y'),
                        'period_start' => $periodStart->format('Y-m-d'),
                        'period_end' => $periodEnd->format('Y-m-d'),
                        'pay_date' => $payDate->format('Y-m-d'),
                        'setting_id' => $setting->id
                    ];
                }
                break;

            case 'monthly':
                // Get current and next 2 months
                for ($i = 0; $i < 3; $i++) {
                    $monthDate = $today->copy()->addMonths($i);
                    $periodStart = $monthDate->copy()->startOfMonth();
                    $periodEnd = $monthDate->copy()->endOfMonth();
                    $payDate = $monthDate->copy()->addMonth()->startOfMonth()->addDays(4); // 5th of next month

                    $periods[] = [
                        'id' => $setting->pay_type . '_' . $periodStart->format('Y_m'),
                        'pay_schedule' => $setting->pay_type,
                        'period_display' => $periodStart->format('M Y'),
                        'pay_date_display' => $payDate->format('M d, Y'),
                        'period_start' => $periodStart->format('Y-m-d'),
                        'period_end' => $periodEnd->format('Y-m-d'),
                        'pay_date' => $payDate->format('Y-m-d'),
                        'setting_id' => $setting->id
                    ];
                }
                break;
        }

        return $periods;
    }

    /**
     * Get variations of pay schedule naming to handle different database values
     */
    private function getPayScheduleVariations($paySchedule)
    {
        $variations = [$paySchedule]; // Include the original

        switch (strtolower($paySchedule)) {
            case 'weekly':
                $variations = ['weekly', 'Weekly', 'WEEKLY'];
                break;
            case 'semi_monthly':
                $variations = ['semi_monthly', 'Semi-monthly', 'semi-monthly', 'Semi Monthly', 'SEMI_MONTHLY', 'SEMI-MONTHLY'];
                break;
            case 'monthly':
                $variations = ['monthly', 'Monthly', 'MONTHLY'];
                break;
        }

        return array_unique($variations);
    }

    /**
     * Get current and upcoming payroll periods for a specific schedule setting
     */
    private function getCurrentMonthPeriodsForSchedule($setting)
    {
        $today = \Carbon\Carbon::now();
        $periods = [];

        switch ($setting->code) {
            case 'weekly':
                // Get weekly configuration from cutoff_periods
                $weeklyConfig = $setting->cutoff_periods[0] ?? [
                    'start_day' => 'monday',
                    'end_day' => 'friday',
                    'pay_day' => 'friday'
                ];

                $startDayNum = $this->getDayOfWeekNumber($weeklyConfig['start_day'] ?? 'monday');
                $endDayNum = $this->getDayOfWeekNumber($weeklyConfig['end_day'] ?? 'friday');

                // Find current week period
                $currentWeekStart = $today->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
                while ($currentWeekStart->dayOfWeek !== $startDayNum) {
                    $currentWeekStart->addDay();
                    if ($currentWeekStart->gt($today)) {
                        $currentWeekStart->subWeek();
                        break;
                    }
                }

                // Generate only current period initially, then next periods if current date has passed
                for ($i = 0; $i < 4; $i++) {
                    $weekStart = $currentWeekStart->copy()->addWeeks($i);
                    $weekEnd = $weekStart->copy();

                    // Calculate end day of week
                    $daysToAdd = ($endDayNum - $startDayNum);
                    if ($daysToAdd < 0) $daysToAdd += 7; // Handle week wrap-around
                    $weekEnd->addDays($daysToAdd);

                    $isCurrent = $today->between($weekStart, $weekEnd);
                    $isPast = $today->gt($weekEnd);

                    // Show current period always, future periods only if current period has ended
                    if ($isCurrent || ($i > 0 && !$this->hasCurrentPeriod($periods))) {
                        // Calculate pay date
                        $payDayNum = $this->getDayOfWeekNumber($weeklyConfig['pay_day'] ?? 'friday');
                        $payDate = $weekStart->copy();
                        while ($payDate->dayOfWeek !== $payDayNum) {
                            $payDate->addDay();
                        }

                        // Adjust for holidays if configured
                        if ($setting->move_if_holiday || $setting->move_if_weekend) {
                            $payDate = $this->adjustDateForHolidays($setting, $payDate);
                        }

                        $periods[] = [
                            'id' => $setting->code . '_' . $weekStart->format('Y_m_d'),
                            'pay_schedule' => $setting->code,
                            'period_display' => $weekStart->format('M j') . '–' . $weekEnd->format('j, Y'),
                            'pay_date_display' => $payDate->format('M d, Y'),
                            'period_start' => $weekStart->format('Y-m-d'),
                            'period_end' => $weekEnd->format('Y-m-d'),
                            'pay_date' => $payDate->format('Y-m-d'),
                            'setting_id' => $setting->id,
                            'is_current' => $isCurrent
                        ];

                        // If this is current period, we're done
                        if ($isCurrent) break;
                    }
                }
                break;

            case 'semi_monthly':
                // Get semi-monthly configuration from cutoff_periods
                $semiConfig = is_string($setting->cutoff_periods)
                    ? json_decode($setting->cutoff_periods, true)
                    : $setting->cutoff_periods;

                if (is_array($semiConfig) && count($semiConfig) >= 2) {
                    // Determine current period
                    $currentDay = $today->day;
                    $showFirstPeriod = $currentDay <= 15;
                    $showSecondPeriod = $currentDay >= 16;

                    // First period (1st-15th) - show if we're in it or if it's future
                    if ($showFirstPeriod) {
                        $firstPeriod = $semiConfig[0];
                        $firstStart = $this->setDayOfMonth($today->copy(), $firstPeriod['start_day'] ?? 1);
                        $firstEnd = $this->setDayOfMonth($today->copy(), $firstPeriod['end_day'] ?? 15);

                        // Calculate pay date for first period
                        $payDay = $firstPeriod['pay_day'] ?? 15;
                        if ($payDay === -1 || $payDay === 'last') {
                            $firstPayDate = $today->copy()->endOfMonth();
                        } else {
                            $firstPayDate = $this->setDayOfMonth($today->copy(), $payDay);
                        }

                        if ($setting->move_if_holiday || $setting->move_if_weekend) {
                            $firstPayDate = $this->adjustDateForHolidays($setting, $firstPayDate);
                        }

                        $periods[] = [
                            'id' => $setting->code . '_' . $firstStart->format('Y_m') . '_1',
                            'pay_schedule' => $setting->code,
                            'period_display' => $firstStart->format('M j') . '–' . $firstEnd->format('j, Y'),
                            'pay_date_display' => $firstPayDate->format('M d, Y'),
                            'period_start' => $firstStart->format('Y-m-d'),
                            'period_end' => $firstEnd->format('Y-m-d'),
                            'pay_date' => $firstPayDate->format('Y-m-d'),
                            'setting_id' => $setting->id,
                            'is_current' => $today->between($firstStart, $firstEnd)
                        ];
                    }

                    // Second period (16th-end of month) - show if we're in it or if first period has passed
                    if ($showSecondPeriod) {
                        $secondPeriod = $semiConfig[1];
                        $secondStart = $this->setDayOfMonth($today->copy(), $secondPeriod['start_day'] ?? 16);

                        // Handle end day
                        $endDay = $secondPeriod['end_day'] ?? -1;
                        if ($endDay === -1 || $endDay === 'last') {
                            $secondEnd = $today->copy()->endOfMonth();
                        } else {
                            $secondEnd = $this->setDayOfMonth($today->copy(), $endDay);
                        }

                        // Calculate pay date for second period
                        $payDay = $secondPeriod['pay_day'] ?? -1;
                        if ($payDay === -1 || $payDay === 'last') {
                            $secondPayDate = $today->copy()->endOfMonth();
                        } else {
                            $secondPayDate = $this->setDayOfMonth($today->copy(), $payDay);
                        }

                        if ($setting->move_if_holiday || $setting->move_if_weekend) {
                            $secondPayDate = $this->adjustDateForHolidays($setting, $secondPayDate);
                        }

                        $periods[] = [
                            'id' => $setting->code . '_' . $secondStart->format('Y_m') . '_2',
                            'pay_schedule' => $setting->code,
                            'period_display' => $secondStart->format('M j') . '–' . $secondEnd->format('j, Y'),
                            'pay_date_display' => $secondPayDate->format('M d, Y'),
                            'period_start' => $secondStart->format('Y-m-d'),
                            'period_end' => $secondEnd->format('Y-m-d'),
                            'pay_date' => $secondPayDate->format('Y-m-d'),
                            'setting_id' => $setting->id,
                            'is_current' => $today->between($secondStart, $secondEnd)
                        ];
                    }
                }
                break;

            case 'monthly':
                // Get monthly configuration from cutoff_periods - always show current month
                $monthlyConfig = $setting->cutoff_periods[0] ?? [
                    'start_day' => 1,
                    'end_day' => 'last',
                    'pay_day' => 'last'
                ];

                // Full month period using configured start/end days
                $startDay = $monthlyConfig['start_day'] ?? 1;
                $periodStart = $this->setDayOfMonth($today->copy(), $startDay);

                $endDay = $monthlyConfig['end_day'] ?? 'last';
                if ($endDay === 'last' || $endDay === -1) {
                    $periodEnd = $today->copy()->endOfMonth();
                } else {
                    $periodEnd = $this->setDayOfMonth($today->copy(), $endDay);
                }

                // Calculate pay date
                $payDay = $monthlyConfig['pay_day'] ?? 'last';
                if ($payDay === 'last' || $payDay === -1) {
                    $payDate = $today->copy()->endOfMonth();
                } else {
                    $payDate = $this->setDayOfMonth($today->copy(), $payDay);
                }

                if ($setting->move_if_holiday || $setting->move_if_weekend) {
                    $payDate = $this->adjustDateForHolidays($setting, $payDate);
                }

                $periods[] = [
                    'id' => $setting->code . '_' . $periodStart->format('Y_m'),
                    'pay_schedule' => $setting->code,
                    'period_display' => $periodStart->format('M j') . '–' . $periodEnd->format('j, Y'),
                    'pay_date_display' => $payDate->format('M d, Y'),
                    'period_start' => $periodStart->format('Y-m-d'),
                    'period_end' => $periodEnd->format('Y-m-d'),
                    'pay_date' => $payDate->format('Y-m-d'),
                    'setting_id' => $setting->id,
                    'is_current' => $today->between($periodStart, $periodEnd)
                ];
                break;
        }

        return $periods;
    }

    /**
     * Check if periods array has a current period
     */
    private function hasCurrentPeriod($periods)
    {
        foreach ($periods as $period) {
            if (isset($period['is_current']) && $period['is_current']) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get current period display for schedule selection page
     */
    private function getCurrentPeriodDisplayForSchedule($setting)
    {
        $today = \Carbon\Carbon::now();

        switch ($setting->code) {
            case 'weekly':
                // Find current week period using cutoff_periods configuration
                $weeklyConfig = $setting->cutoff_periods[0] ?? [
                    'start_day' => 'monday',
                    'end_day' => 'friday',
                    'pay_day' => 'friday'
                ];

                $startDayNum = $this->getDayOfWeekNumber($weeklyConfig['start_day'] ?? 'monday');
                $endDayNum = $this->getDayOfWeekNumber($weeklyConfig['end_day'] ?? 'friday');

                // Find the start of current week containing today
                $weekStart = $today->copy();

                // Go backward to find the correct start day
                while ($weekStart->dayOfWeek !== $startDayNum) {
                    $weekStart->subDay();
                }

                // Calculate the end day
                $weekEnd = $weekStart->copy();
                $daysToAdd = ($endDayNum - $startDayNum);
                if ($daysToAdd < 0) $daysToAdd += 7;
                $weekEnd->addDays($daysToAdd);

                // Make sure today falls within this period, if not adjust
                if (!$today->between($weekStart, $weekEnd)) {
                    if ($today->lt($weekStart)) {
                        // Move back one week
                        $weekStart->subWeek();
                        $weekEnd->subWeek();
                    } else {
                        // Move forward one week
                        $weekStart->addWeek();
                        $weekEnd->addWeek();
                    }
                }

                return $weekStart->format('M j') . '–' . $weekEnd->format('j');

            case 'semi_monthly':
                // Use cutoff_periods configuration for semi-monthly periods
                $semiConfig = $setting->cutoff_periods ?? [];
                $semiConfig = is_string($semiConfig)
                    ? json_decode($semiConfig, true)
                    : $semiConfig;

                if (is_array($semiConfig) && count($semiConfig) >= 2) {
                    $currentDay = $today->day;

                    // Check which period we're currently in based on configured cutoff dates
                    $firstPeriod = $semiConfig[0];
                    $secondPeriod = $semiConfig[1];

                    $firstStart = $firstPeriod['start_day'] ?? 1;
                    $firstEnd = $firstPeriod['end_day'] ?? 15;
                    $secondStart = $secondPeriod['start_day'] ?? 16;
                    $secondEnd = $secondPeriod['end_day'] ?? 'last';

                    // Determine if we're in first or second period
                    if ($currentDay >= $firstStart && $currentDay <= $firstEnd) {
                        // First period
                        return $today->format('M') . ' ' . $firstStart . '–' . $firstEnd;
                    } else {
                        // Second period
                        $endDisplay = ($secondEnd === 'last') ? $today->copy()->endOfMonth()->format('d') : $secondEnd;
                        return $today->format('M') . ' ' . $secondStart . '–' . $endDisplay;
                    }
                } else {
                    // Fallback to default 1-15, 16-end if no configuration
                    if ($today->day <= 15) {
                        return $today->format('M') . ' 1–15';
                    } else {
                        return $today->format('M') . ' 16–' . $today->copy()->endOfMonth()->format('j');
                    }
                }

            case 'monthly':
                // Use cutoff_periods configuration for monthly period
                $monthlyConfig = $setting->cutoff_periods[0] ?? [
                    'start_day' => 1,
                    'end_day' => 'last'
                ];

                $startDay = $monthlyConfig['start_day'] ?? 1;
                $endDay = $monthlyConfig['end_day'] ?? 'last';

                $endDisplay = ($endDay === 'last') ? $today->copy()->endOfMonth()->format('d') : $endDay;

                return $today->format('M') . ' ' . $startDay . '–' . $endDisplay;

            default:
                return 'Current Period';
        }
    }

    /**
     * Calculate pay date for weekly schedule
     */
    private function calculatePayDateForWeekly($setting, $weekEnd)
    {
        $weeklyConfig = $setting->cutoff_periods[0] ?? [
            'start_day' => 'monday',
            'end_day' => 'friday',
            'pay_day' => 'friday'
        ];

        $payDayName = $weeklyConfig['pay_day'];
        $payDayNum = $this->getDayOfWeekNumber($payDayName);

        // Find the pay day in the same week as week end
        $payDate = $weekEnd->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
        while ($payDate->dayOfWeek !== $payDayNum) {
            $payDate->addDay();
        }

        // If pay day is before week end, it might be next week
        if ($payDate->lt($weekEnd->copy()->startOfWeek())) {
            $payDate->addWeek();
        }

        return $this->adjustDateForHolidays($setting, $payDate);
    }

    /**
     * Calculate pay date based on schedule setting and cutoff rules (legacy method for backward compatibility)
     */
    private function calculatePayDate($setting, $cutoffEnd, $period = null)
    {
        // Use new methods for better accuracy
        if ($setting->code === 'weekly') {
            return $this->calculatePayDateForWeekly($setting, $cutoffEnd);
        }

        // Default pay date calculation for semi-monthly and monthly
        switch ($setting->code) {
            case 'semi_monthly':
                if ($period === 'first_half') {
                    $semiConfig = $setting->semi_monthly_config;
                    $payDay = $semiConfig['first_period']['pay_day'];
                    if ($payDay === -1 || $payDay === 'last') {
                        $payDate = $cutoffEnd->copy()->endOfMonth();
                    } else {
                        $payDate = $this->setDayOfMonth($cutoffEnd->copy(), $payDay);
                    }
                } else {
                    $semiConfig = $setting->semi_monthly_config;
                    $payDay = $semiConfig['second_period']['pay_day'];
                    if ($payDay === -1 || $payDay === 'last') {
                        $payDate = $cutoffEnd->copy(); // Last day of month
                    } else {
                        $payDate = $this->setDayOfMonth($cutoffEnd->copy(), $payDay);
                    }
                }
                return $setting->adjustDateForHolidays($payDate);

            case 'monthly':
                if ($setting->monthly_pay_day === -1 || $setting->monthly_pay_day === 'last') {
                    $payDate = $cutoffEnd->copy(); // Last day of month
                } else {
                    $payDate = $this->setDayOfMonth($cutoffEnd->copy(), $setting->monthly_pay_day);
                }
                return $setting->adjustDateForHolidays($payDate);

            default:
                return $cutoffEnd->copy()->addDays($setting->payday_offset_days ?? 0);
        }
    }

    /**
     * Convert day name to day of week number (0=Sunday, 1=Monday, ... 6=Saturday) - Carbon standard
     */
    private function getDayOfWeekNumber($dayName)
    {
        $days = [
            'sunday' => 0,
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6
        ];

        return $days[strtolower($dayName)] ?? 5; // Default to Friday
    }
    private function getFallbackPeriod($scheduleType, $today)
    {
        switch ($scheduleType) {
            case 'weekly':
                $startOfWeek = $today->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
                $endOfWeek = $today->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
                return [
                    'schedule_type' => 'weekly',
                    'period_start' => $startOfWeek->format('Y-m-d'),
                    'period_end' => $endOfWeek->format('Y-m-d'),
                    'pay_date' => $endOfWeek->addDays(3)->format('Y-m-d'), // Pay on Wednesday after week ends
                    'period_name' => $startOfWeek->format('M j') . ' - ' . $endOfWeek->format('M j, Y')
                ];

            case 'semi_monthly':
                $day = $today->day;
                if ($day <= 15) {
                    $start = $today->copy()->startOfMonth();
                    $end = $today->copy()->startOfMonth()->addDays(14);
                    $payDate = $end->copy()->addDays(3);
                } else {
                    $start = $today->copy()->startOfMonth()->addDays(15);
                    $end = $today->copy()->endOfMonth();
                    $payDate = $end->copy()->addDays(3);
                }
                return [
                    'schedule_type' => 'semi_monthly',
                    'period_start' => $start->format('Y-m-d'),
                    'period_end' => $end->format('Y-m-d'),
                    'pay_date' => $payDate->format('Y-m-d'),
                    'period_name' => $start->format('M j') . ' - ' . $end->format('M j, Y')
                ];

            case 'monthly':
            default:
                $start = $today->copy()->startOfMonth();
                $end = $today->copy()->endOfMonth();
                return [
                    'schedule_type' => 'monthly',
                    'period_start' => $start->format('Y-m-d'),
                    'period_end' => $end->format('Y-m-d'),
                    'pay_date' => $end->addDays(5)->format('Y-m-d'), // Pay 5 days after month ends
                    'period_name' => $start->format('M Y')
                ];
        }
    }

    /**
     * Calculate the appropriate period for a specific schedule type
     */
    private function calculatePeriodForSchedule($setting, $currentDate)
    {
        switch ($setting->pay_type) {
            case 'weekly':
                return $this->calculateWeeklyPeriod($setting, $currentDate);
            case 'semi_monthly':
                return $this->calculateSemiMonthlyPeriod($setting, $currentDate);
            case 'monthly':
                return $this->calculateMonthlyPeriod($setting, $currentDate);
            default:
                return null;
        }
    }

    /**
     * Calculate weekly period
     */
    private function calculateWeeklyPeriod($setting, $currentDate)
    {
        // Find the current week period
        $startOfWeek = $currentDate->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
        $endOfWeek = $currentDate->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);

        $payDate = $endOfWeek->copy();
        if ($setting->payday_offset_days) {
            $payDate = $endOfWeek->copy()->addDays($setting->payday_offset_days);
        }

        return [
            'start' => $startOfWeek->format('Y-m-d'),
            'end' => $endOfWeek->format('Y-m-d'),
            'pay_date' => $payDate->format('Y-m-d'),
            'name' => $startOfWeek->format('M j') . ' - ' . $endOfWeek->format('M j, Y')
        ];
    }

    /**
     * Calculate semi-monthly period
     */
    private function calculateSemiMonthlyPeriod($setting, $currentDate)
    {
        $day = $currentDate->day;
        $month = $currentDate->month;
        $year = $currentDate->year;

        if ($day <= 15) {
            // First half of the month (1st to 15th)
            $start = \Carbon\Carbon::create($year, $month, 1);
            $end = \Carbon\Carbon::create($year, $month, 15);
            $periodName = $start->format('M') . ' 1-15';
        } else {
            // Second half of the month (16th to end)
            $start = \Carbon\Carbon::create($year, $month, 16);
            $end = \Carbon\Carbon::create($year, $month)->endOfMonth();
            $periodName = $start->format('M') . ' 16-' . $end->day;
        }

        $payDate = $end->copy();
        if ($setting->payday_offset_days) {
            $payDate = $end->copy()->addDays($setting->payday_offset_days);
        }

        return [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'pay_date' => $payDate->format('Y-m-d'),
            'name' => $periodName
        ];
    }

    /**
     * Calculate monthly period
     */
    private function calculateMonthlyPeriod($setting, $currentDate)
    {
        $start = $currentDate->copy()->startOfMonth();
        $end = $currentDate->copy()->endOfMonth();

        $payDate = $end->copy();
        if ($setting->payday_offset_days) {
            $payDate = $end->copy()->addDays($setting->payday_offset_days);
        }

        return [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'pay_date' => $payDate->format('Y-m-d'),
            'name' => $start->format('M Y')
        ];
    }

    /**
     * Safe way to set day of month avoiding Carbon 3.x type issues
     */
    private function setDayOfMonth($carbon, $day)
    {
        if ($day === 'last' || $day === -1) {
            return $carbon->endOfMonth();
        }

        // Ensure day is integer and within valid range
        $dayInt = (int) $day;
        $dayInt = max(1, min(31, $dayInt));

        return $carbon->startOfMonth()->addDays($dayInt - 1);
    }

    /**
     * Adjust date for holidays and weekends
     */
    private function adjustDateForHolidays($setting, $date)
    {
        // If it's weekend and move_if_weekend is enabled
        if ($setting->move_if_weekend && ($date->isWeekend())) {
            if ($setting->move_direction === 'before') {
                // Move to previous Friday
                while ($date->isWeekend()) {
                    $date->subDay();
                }
            } else {
                // Move to next Monday  
                while ($date->isWeekend()) {
                    $date->addDay();
                }
            }
        }

        // Additional holiday checking could be implemented here
        // if ($setting->move_if_holiday) {
        //     // Check against holiday table and adjust
        // }

        return $date;
    }

    /**
     * Calculate the current pay period for a given schedule setting (not next)
     */
    private function calculateCurrentPayPeriod($scheduleSetting)
    {
        $today = Carbon::now();

        switch ($scheduleSetting->code) {
            case 'weekly':
                return $this->calculateCurrentWeeklyPayPeriod($scheduleSetting, $today);

            case 'semi_monthly':
                return $this->calculateCurrentSemiMonthlyPayPeriod($scheduleSetting, $today);

            case 'monthly':
                return $this->calculateCurrentMonthlyPayPeriod($scheduleSetting, $today);

            case 'daily':
                return $this->calculateCurrentDailyPayPeriod($scheduleSetting, $today);

            default:
                // Fallback to weekly if unknown
                return $this->calculateCurrentWeeklyPayPeriod($scheduleSetting, $today);
        }
    }

    /**
     * Calculate current weekly pay period based on settings
     */
    private function calculateCurrentWeeklyPayPeriod($scheduleSetting, $currentDate)
    {
        $cutoffPeriods = $scheduleSetting->cutoff_periods;
        if (is_string($cutoffPeriods)) {
            $cutoffPeriods = json_decode($cutoffPeriods, true);
        }
        if (empty($cutoffPeriods) || !isset($cutoffPeriods[0]) || !is_array($cutoffPeriods[0])) {
            // Fallback to Monday-Friday if no settings
            $cutoffPeriods = [['start_day' => 'monday', 'end_day' => 'friday', 'pay_day' => 'friday']];
        }

        $cutoff = $cutoffPeriods[0];
        $startDay = $cutoff['start_day'];
        $endDay = $cutoff['end_day'];
        $payDay = $cutoff['pay_day'];

        // Find the current period that contains today's date
        $periodStart = $this->getWeekStartForDay($currentDate, $startDay);
        $periodEnd = $this->getWeekDayForDate($periodStart, $endDay);

        // Check if current date is within this period
        if ($currentDate->lt($periodStart)) {
            // We're before the current period, move back one week
            $periodStart = $periodStart->subWeek();
            $periodEnd = $this->getWeekDayForDate($periodStart, $endDay);
        } elseif ($currentDate->gt($periodEnd)) {
            // We're after the current period end, but before next period start
            // Check if the next period has started
            $nextPeriodStart = $periodStart->copy()->addWeek();
            if ($currentDate->gte($nextPeriodStart)) {
                // Next period has started
                $periodStart = $nextPeriodStart;
                $periodEnd = $this->getWeekDayForDate($periodStart, $endDay);
            }
            // Otherwise, stay with current period (we're in between periods)
        }

        $payDate = $this->getWeekDayForDate($periodStart, $payDay);

        // Adjust pay date if it's before period end
        if ($payDate->lt($periodEnd)) {
            $payDate = $payDate->addWeek();
        }

        return [
            'start' => $periodStart->format('Y-m-d'),
            'end' => $periodEnd->format('Y-m-d'),
            'pay_date' => $payDate->format('Y-m-d'),
        ];
    }

    /**
     * Calculate current semi-monthly pay period based on settings
     */
    private function calculateCurrentSemiMonthlyPayPeriod($scheduleSetting, $currentDate)
    {
        $cutoffPeriods = $scheduleSetting->cutoff_periods;
        if (is_string($cutoffPeriods)) {
            $cutoffPeriods = json_decode($cutoffPeriods, true);
        }
        if (empty($cutoffPeriods) || !isset($cutoffPeriods[0]) || !is_array($cutoffPeriods[0])) {
            // Fallback to 1-15 and 16-31
            $cutoffPeriods = [
                ['start_day' => 1, 'end_day' => 15, 'pay_date' => 16],
                ['start_day' => 16, 'end_day' => 31, 'pay_date' => 5]
            ];
        }

        $currentDay = $currentDate->day;

        // Parse cutoff periods to get numeric days
        $firstPeriodStart = $this->parseDayNumber($cutoffPeriods[0]['start_day']);
        $firstPeriodEnd = $this->parseDayNumber($cutoffPeriods[0]['end_day']);
        $secondPeriodStart = $this->parseDayNumber($cutoffPeriods[1]['start_day']);
        $secondPeriodEnd = $this->parseDayNumber($cutoffPeriods[1]['end_day']);

        // Determine which period we're currently in
        if ($currentDay >= $firstPeriodStart && $currentDay <= $firstPeriodEnd) {
            // We're in the first period
            $periodStart = $currentDate->copy()->startOfMonth()->day($firstPeriodStart);
            $periodEnd = $currentDate->copy()->startOfMonth()->day($firstPeriodEnd);
            $payDay = $this->parseDayNumber($cutoffPeriods[0]['pay_date'] ?? $firstPeriodEnd);
        } else {
            // We're in the second period
            $periodStart = $currentDate->copy()->startOfMonth()->day($secondPeriodStart);
            if ($secondPeriodEnd == 31) {
                $periodEnd = $currentDate->copy()->endOfMonth();
            } else {
                $periodEnd = $currentDate->copy()->startOfMonth()->day($secondPeriodEnd);
            }
            $payDay = $this->parseDayNumber($cutoffPeriods[1]['pay_date'] ?? $secondPeriodEnd);
        }

        // Set pay date
        if ($payDay == 31) {
            $payDate = $periodEnd->copy()->endOfMonth();
        } else {
            $payDate = $currentDate->copy()->startOfMonth()->day($payDay);
            // If pay date is before period end, it might be in next month
            if ($payDate->lt($periodEnd)) {
                $payDate = $payDate->addMonth();
            }
        }

        return [
            'start' => $periodStart->format('Y-m-d'),
            'end' => $periodEnd->format('Y-m-d'),
            'pay_date' => $payDate->format('Y-m-d'),
        ];
    }

    /**
     * Calculate current monthly pay period based on settings
     */
    private function calculateCurrentMonthlyPayPeriod($scheduleSetting, $currentDate)
    {
        $cutoffPeriods = $scheduleSetting->cutoff_periods;
        if (is_string($cutoffPeriods)) {
            $cutoffPeriods = json_decode($cutoffPeriods, true);
        }
        if (empty($cutoffPeriods) || !isset($cutoffPeriods[0]) || !is_array($cutoffPeriods[0])) {
            // Fallback to 1-31
            $cutoffPeriods = [['start_day' => 1, 'end_day' => 31, 'pay_date' => 31]];
        }

        $cutoff = $cutoffPeriods[0];
        $startDay = $this->parseDayNumber($cutoff['start_day']);
        $endDay = $this->parseDayNumber($cutoff['end_day']);
        $payDay = $this->parseDayNumber($cutoff['pay_date'] ?? $endDay);

        $periodStart = $currentDate->copy()->startOfMonth()->day($startDay);

        if ($endDay == 31) {
            $periodEnd = $currentDate->copy()->endOfMonth();
        } else {
            $periodEnd = $currentDate->copy()->startOfMonth()->day($endDay);
        }

        if ($payDay == 31) {
            $payDate = $periodEnd->copy()->endOfMonth();
        } else {
            $payDate = $currentDate->copy()->startOfMonth()->day($payDay);
        }

        return [
            'start' => $periodStart->format('Y-m-d'),
            'end' => $periodEnd->format('Y-m-d'),
            'pay_date' => $payDate->format('Y-m-d'),
        ];
    }

    /**
     * Calculate current daily pay period based on settings
     */
    private function calculateCurrentDailyPayPeriod($scheduleSetting, $currentDate)
    {
        $periodStart = $currentDate->copy();
        $periodEnd = $currentDate->copy();
        $payDate = $currentDate->copy()->addDay();

        return [
            'start' => $periodStart->format('Y-m-d'),
            'end' => $periodEnd->format('Y-m-d'),
            'pay_date' => $payDate->format('Y-m-d'),
        ];
    }

    /**
     * Calculate the next pay period for a given schedule setting based on actual settings
     */
    private function calculateNextPayPeriod($scheduleSetting)
    {
        $today = Carbon::now();

        switch ($scheduleSetting->code) {
            case 'weekly':
                return $this->calculateWeeklyPayPeriod($scheduleSetting, $today);

            case 'semi_monthly':
                return $this->calculateSemiMonthlyPayPeriod($scheduleSetting, $today);

            case 'monthly':
                return $this->calculateMonthlyPayPeriod($scheduleSetting, $today);

            case 'daily':
                return $this->calculateDailyPayPeriod($scheduleSetting, $today);

            default:
                // Fallback to weekly if unknown
                return $this->calculateWeeklyPayPeriod($scheduleSetting, $today);
        }
    }

    /**
     * Calculate weekly pay period based on settings
     */
    private function calculateWeeklyPayPeriod($scheduleSetting, $currentDate)
    {
        $cutoffPeriods = $scheduleSetting->cutoff_periods;
        if (is_string($cutoffPeriods)) {
            $cutoffPeriods = json_decode($cutoffPeriods, true);
        }
        if (empty($cutoffPeriods) || !isset($cutoffPeriods[0]) || !is_array($cutoffPeriods[0])) {
            // Fallback to Monday-Friday if no settings
            $cutoffPeriods = [['start_day' => 'monday', 'end_day' => 'friday', 'pay_day' => 'friday']];
        }

        $cutoff = $cutoffPeriods[0];
        $startDay = $cutoff['start_day'];
        $endDay = $cutoff['end_day'];
        $payDay = $cutoff['pay_day'];


        // Get last payroll to determine next period
        $lastPayroll = \App\Models\Payroll::where('pay_schedule', 'weekly')
            ->orderBy('period_end', 'desc')
            ->first();

        if ($lastPayroll) {
            // Start from the day after the last payroll period ended
            $periodStart = Carbon::parse($lastPayroll->period_end)->addDay();
        } else {
            // No previous payroll - find the current or next period
            $periodStart = $this->getWeekStartForDay($currentDate, $startDay);

            // If we're past the end day of current week, move to next week
            $periodEnd = $this->getWeekDayForDate($periodStart, $endDay);
            if ($currentDate->gt($periodEnd)) {
                $periodStart = $periodStart->addWeek();
            }
        }

        $periodEnd = $this->getWeekDayForDate($periodStart, $endDay);
        $payDate = $this->getWeekDayForDate($periodStart, $payDay);

        // If pay day is before period end, move to next week
        if ($payDate->lt($periodEnd)) {
            $payDate = $payDate->addWeek();
        }

        return [
            'start' => $periodStart->format('Y-m-d'),
            'end' => $periodEnd->format('Y-m-d'),
            'pay_date' => $payDate->format('Y-m-d'),
        ];
    }

    /**
     * Calculate semi-monthly pay period based on settings
     */
    private function calculateSemiMonthlyPayPeriod($scheduleSetting, $currentDate)
    {
        $cutoffPeriods = $scheduleSetting->cutoff_periods;
        // Fix: Decode JSON if string
        if (is_string($cutoffPeriods)) {
            $cutoffPeriods = json_decode($cutoffPeriods, true);
        }
        if (empty($cutoffPeriods) || !isset($cutoffPeriods[0]) || !is_array($cutoffPeriods[0])) {
            // Fallback to 1-7 and 8-31
            $cutoffPeriods = [
                ['start_day' => '1st', 'end_day' => '7th', 'pay_day' => '7th'],
                ['start_day' => '8th', 'end_day' => '31st', 'pay_day' => '31st']
            ];
        }

        // Get last payroll to determine next period
        $lastPayroll = \App\Models\Payroll::where('pay_schedule', 'semi_monthly')
            ->orderBy('period_end', 'desc')
            ->first();

        $currentDay = $currentDate->day;

        // Parse cutoff periods to get numeric days
        $firstPeriodStart = $this->parseDayNumber($cutoffPeriods[0]['start_day']);
        $firstPeriodEnd = $this->parseDayNumber($cutoffPeriods[0]['end_day']);
        $secondPeriodStart = $this->parseDayNumber($cutoffPeriods[1]['start_day']);
        $secondPeriodEnd = $this->parseDayNumber($cutoffPeriods[1]['end_day']);

        // Determine which period we're in based on current date
        // Note: periods can overlap (e.g., 1-7 and 7-31), so we use <= for first period check
        // Use both 'pay_day' and 'pay_date' keys for compatibility
        if ($currentDay >= $firstPeriodStart && $currentDay <= $firstPeriodEnd && $currentDay < $secondPeriodStart) {
            // We're in the first period (e.g., 1-7)
            $periodStart = $currentDate->copy()->startOfMonth()->day($firstPeriodStart);
            $periodEnd = $currentDate->copy()->startOfMonth()->day($firstPeriodEnd);
            $payDay = $this->parseDayNumber($cutoffPeriods[0]['pay_date'] ?? $cutoffPeriods[0]['pay_day'] ?? $firstPeriodEnd);
        } else {
            // We're in the second period (e.g., 7-31)
            $periodStart = $currentDate->copy()->startOfMonth()->day($secondPeriodStart);
            if ($secondPeriodEnd == 31) {
                $periodEnd = $currentDate->copy()->endOfMonth();
            } else {
                $periodEnd = $currentDate->copy()->startOfMonth()->day($secondPeriodEnd);
            }
            $payDay = $this->parseDayNumber($cutoffPeriods[1]['pay_date'] ?? $cutoffPeriods[1]['pay_day'] ?? $secondPeriodEnd);
        }

        // Set pay date
        if ($payDay == 31) {
            $payDate = $periodEnd->copy()->endOfMonth();
        } else {
            $payDate = $currentDate->copy()->startOfMonth()->day($payDay);
        }

        return [
            'start' => $periodStart->format('Y-m-d'),
            'end' => $periodEnd->format('Y-m-d'),
            'pay_date' => $payDate->format('Y-m-d'),
        ];
    }

    /**
     * Parse day number from string (1st, 2nd, etc.) or simple number
     */
    private function parseDayNumber($dayString)
    {
        if (is_numeric($dayString)) {
            return (int) $dayString;
        }
        if ($dayString === '31st' || $dayString === '31') {
            return 31;
        }
        return (int) preg_replace('/[^0-9]/', '', $dayString);
    }

    /**
     * Calculate monthly pay period based on settings
     */
    private function calculateMonthlyPayPeriod($scheduleSetting, $currentDate)
    {
        // Get last payroll to determine next period
        $lastPayroll = \App\Models\Payroll::where('pay_schedule', 'monthly')
            ->orderBy('period_end', 'desc')
            ->first();

        if ($lastPayroll) {
            $periodStart = Carbon::parse($lastPayroll->period_end)->addDay();
        } else {
            $periodStart = $currentDate->copy()->startOfMonth();
        }

        $periodEnd = $periodStart->copy()->endOfMonth();
        $payDate = $periodEnd->copy()->addDays($scheduleSetting->pay_day_offset ?? 0);

        return [
            'start' => $periodStart->format('Y-m-d'),
            'end' => $periodEnd->format('Y-m-d'),
            'pay_date' => $payDate->format('Y-m-d'),
        ];
    }

    /**
     * Calculate daily pay period based on settings
     */
    private function calculateDailyPayPeriod($scheduleSetting, $currentDate)
    {
        // Get last payroll to determine next period
        $lastPayroll = \App\Models\Payroll::where('pay_schedule', 'daily')
            ->orderBy('period_end', 'desc')
            ->first();

        if ($lastPayroll) {
            $periodStart = Carbon::parse($lastPayroll->period_end)->addDay();
        } else {
            $periodStart = $currentDate->copy();
        }

        $periodEnd = $periodStart->copy();
        $payDate = $periodEnd->copy()->addDays($scheduleSetting->pay_day_offset ?? 0);

        return [
            'start' => $periodStart->format('Y-m-d'),
            'end' => $periodEnd->format('Y-m-d'),
            'pay_date' => $payDate->format('Y-m-d'),
        ];
    }

    /**
     * Get the start of week for a specific day (monday, tuesday, etc.)
     */
    private function getWeekStartForDay($date, $dayName)
    {
        $dayOfWeek = $this->getDayOfWeekNumber($dayName);
        $currentDayOfWeek = $date->dayOfWeek;

        // Adjust Sunday from 0 to 7 for easier calculation
        if ($currentDayOfWeek === 0) $currentDayOfWeek = 7;

        $daysToSubtract = $currentDayOfWeek - $dayOfWeek;
        if ($daysToSubtract < 0) {
            $daysToSubtract += 7;
        }

        return $date->copy()->subDays($daysToSubtract);
    }

    /**
     * Get a specific weekday for a given week
     */
    private function getWeekDayForDate($weekStart, $dayName)
    {
        $dayOfWeek = $this->getDayOfWeekNumber($dayName);
        $startDayOfWeek = $weekStart->dayOfWeek;

        // Adjust Sunday from 0 to 7 for easier calculation
        if ($startDayOfWeek === 0) $startDayOfWeek = 7;

        $daysToAdd = $dayOfWeek - $startDayOfWeek;
        if ($daysToAdd < 0) {
            $daysToAdd += 7;
        }

        return $weekStart->copy()->addDays($daysToAdd);
    }

    /**
     * Generate a suggested payroll number
     */
    private function generatePayrollNumber($paySchedule)
    {
        $today = Carbon::now();
        $year = $today->format('Y');
        $month = $today->format('m');

        // Get the count of payrolls for this schedule in the current year
        $count = \App\Models\Payroll::where('pay_schedule', $paySchedule)
            ->whereYear('created_at', $year)
            ->count() + 1;

        // Format: SCHEDULE-YEAR-MONTH-COUNT
        $scheduleCode = strtoupper(str_replace('_', '', $paySchedule));

        return sprintf('%s-%s%s-%03d', $scheduleCode, $year, $month, $count);
    }

    /**
     * Recalculate payroll based on current settings and data
     * This deletes the current payroll and recreates it fresh
     */
    public function recalculate(Payroll $payroll)
    {
        $this->authorize('edit payrolls');

        if (!$payroll->canBeEdited()) {
            return redirect()->route('payrolls.show', $payroll)
                ->with('error', 'This payroll cannot be recalculated as it has been processed.');
        }

        try {
            DB::beginTransaction();

            Log::info('Starting payroll recalculation via delete/recreate', [
                'payroll_id' => $payroll->id,
                'payroll_number' => $payroll->payroll_number
            ]);

            // Store the original payroll data
            $originalData = [
                'period_start' => $payroll->period_start,
                'period_end' => $payroll->period_end,
                'pay_date' => $payroll->pay_date,
                'payroll_type' => $payroll->payroll_type,
                'pay_schedule' => $payroll->pay_schedule,
                'description' => $payroll->description,
                'created_by' => $payroll->created_by,
            ];

            // Get all employee IDs that were in the original payroll
            $employeeIds = $payroll->payrollDetails->pluck('employee_id')->toArray();

            // Delete the old payroll (this will cascade delete payroll details)
            $payroll->delete();

            // Create new payroll with the same data
            $newPayroll = Payroll::create([
                'payroll_number' => Payroll::generatePayrollNumber($originalData['payroll_type']),
                'period_start' => $originalData['period_start'],
                'period_end' => $originalData['period_end'],
                'pay_date' => $originalData['pay_date'],
                'payroll_type' => $originalData['payroll_type'],
                'pay_schedule' => $originalData['pay_schedule'],
                'description' => $originalData['description'] . ' (Recalculated)',
                'status' => 'draft',
                'created_by' => $originalData['created_by'],
            ]);

            $totalGross = 0;
            $totalDeductions = 0;
            $totalNet = 0;
            $processedEmployees = 0;

            // Recreate payroll details for each employee with current data
            foreach ($employeeIds as $employeeId) {
                try {
                    $employee = Employee::find($employeeId);

                    if (!$employee || $employee->employment_status !== 'active') {
                        Log::warning("Employee with ID {$employeeId} not found or not active, skipping");
                        continue;
                    }

                    // Calculate payroll details with current settings
                    $payrollDetail = $this->calculateEmployeePayroll($employee, $newPayroll);

                    $totalGross += $payrollDetail->gross_pay;
                    $totalDeductions += $payrollDetail->total_deductions;
                    $totalNet += $payrollDetail->net_pay;
                    $processedEmployees++;
                } catch (\Exception $e) {
                    Log::error("Failed to process employee {$employeeId} during recalculation: " . $e->getMessage());
                    continue;
                }
            }

            if ($processedEmployees === 0) {
                throw new \Exception('No employees could be processed for payroll recalculation.');
            }

            DB::commit();

            Log::info('Payroll recalculation completed', [
                'old_payroll_id' => 'deleted',
                'new_payroll_id' => $newPayroll->id,
                'new_payroll_number' => $newPayroll->payroll_number,
                'total_employees' => $processedEmployees
            ]);

            return redirect()->route('payrolls.show', $newPayroll)
                ->with('success', "Payroll has been recalculated! Created new payroll #{$newPayroll->payroll_number} with {$processedEmployees} employees processed.");
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Payroll recalculation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('payrolls.index')
                ->with('error', 'Failed to recalculate payroll: ' . $e->getMessage());
        }
    }
    /**
     * Auto-recalculate payroll when viewing if it's still in draft status
     */
    private function autoRecalculateIfNeeded(Payroll $payroll)
    {
        // Only auto-recalculate if payroll is in draft status
        if ($payroll->status !== 'draft') {
            return;
        }

        try {
            // Always perform full recalculation to reflect current data
            Log::info('Auto-recalculating payroll on view', ['payroll_id' => $payroll->id]);

            // First, recalculate all time log hours for this payroll period
            $this->recalculateTimeLogsForPayroll($payroll);

            $totalGross = 0;
            $totalDeductions = 0;
            $totalNet = 0;

            // Recalculate each payroll detail completely with current settings
            foreach ($payroll->payrollDetails as $detail) {
                $employee = Employee::find($detail->employee_id);

                if (!$employee) continue;

                // Full recalculation using current dynamic settings
                $updatedDetail = $this->calculateEmployeePayrollDynamic($employee, $payroll);

                $totalGross += $updatedDetail->gross_pay;
                $totalDeductions += $updatedDetail->total_deductions;
                $totalNet += $updatedDetail->net_pay;
            }

            // Update payroll totals
            $payroll->update([
                'total_gross' => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_net' => $totalNet,
            ]);
        } catch (\Exception $e) {
            Log::warning('Auto-recalculation failed', [
                'payroll_id' => $payroll->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Recalculate time log hours for all employees in a payroll period
     */
    private function recalculateTimeLogsForPayroll(Payroll $payroll)
    {
        $employeeIds = $payroll->payrollDetails->pluck('employee_id');

        $timeLogs = TimeLog::whereIn('employee_id', $employeeIds)
            ->whereBetween('log_date', [$payroll->period_start, $payroll->period_end])
            ->get();

        $timeLogController = app(TimeLogController::class);

        foreach ($timeLogs as $timeLog) {
            // Skip if incomplete record
            if (!$timeLog->time_in || !$timeLog->time_out) {
                continue;
            }

            // Recalculate hours using the dynamic calculation method
            $dynamicCalculation = $this->calculateTimeLogHoursDynamically($timeLog);

            // Update the stored values with the new calculations
            $timeLog->regular_hours = $dynamicCalculation['regular_hours'];
            $timeLog->overtime_hours = $dynamicCalculation['overtime_hours'];
            $timeLog->regular_overtime_hours = $dynamicCalculation['regular_overtime_hours'] ?? 0;
            $timeLog->night_diff_overtime_hours = $dynamicCalculation['night_diff_overtime_hours'] ?? 0;
            $timeLog->total_hours = $dynamicCalculation['total_hours'];
            $timeLog->late_hours = $dynamicCalculation['late_hours'];
            $timeLog->undertime_hours = $dynamicCalculation['undertime_hours'];
            $timeLog->save();
        }

        Log::info('Recalculated time logs for payroll', [
            'payroll_id' => $payroll->id,
            'time_logs_count' => $timeLogs->count()
        ]);
    }

    /**
     * Move payroll back to draft status (only from processing)
     */
    public function backToDraft(Payroll $payroll)
    {
        $this->authorize('edit payrolls');

        if ($payroll->status !== 'processing') {
            return back()->withErrors(['status' => 'Only processing payrolls can be moved back to draft.']);
        }

        DB::beginTransaction();
        try {
            // Delete all snapshots
            $payroll->snapshots()->delete();

            // Update payroll status
            $payroll->update([
                'status' => 'draft',
                'processing_started_at' => null,
                'processing_by' => null,
            ]);

            DB::commit();

            return redirect()->route('payrolls.show', $payroll)
                ->with('success', 'Payroll moved back to draft. Snapshots have been cleared.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to move payroll back to draft', [
                'payroll_id' => $payroll->id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['error' => 'Failed to move payroll back to draft: ' . $e->getMessage()]);
        }
    }

    /**
     * Create snapshots for all employees in the payroll
     */
    private function createPayrollSnapshots(Payroll $payroll)
    {
        Log::info("Creating snapshots for payroll {$payroll->id}");

        // Delete existing snapshots first
        $payroll->snapshots()->delete();

        // Get all payroll details
        $payrollDetails = $payroll->payrollDetails()->with('employee')->get();

        if ($payrollDetails->isEmpty()) {
            throw new \Exception('No payroll details found to create snapshots.');
        }

        // Get employee IDs from payroll details
        $employeeIds = $payrollDetails->pluck('employee_id');

        // Create array of all dates in the payroll period
        $startDate = \Carbon\Carbon::parse($payroll->period_start);
        $endDate = \Carbon\Carbon::parse($payroll->period_end);
        $periodDates = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $periodDates[] = $date->format('Y-m-d');
        }

        // Get all time logs for this payroll period
        $timeLogs = TimeLog::whereIn('employee_id', $employeeIds)
            ->whereBetween('log_date', [$payroll->period_start, $payroll->period_end])
            ->orderBy('log_date')
            ->get()
            ->groupBy(['employee_id', function ($item) {
                return \Carbon\Carbon::parse($item->log_date)->format('Y-m-d');
            }]);

        // Calculate time breakdowns for all employees (to get only regular workday hours)
        $timeBreakdownsByEmployee = [];
        foreach ($employeeIds as $employeeId) {
            $employeeTimeLogs = $timeLogs->get($employeeId, collect());
            $employeeBreakdown = [];

            foreach ($periodDates as $date) {
                $timeLog = $employeeTimeLogs->get($date, collect())->first();

                // Track time breakdown by type (exclude incomplete records)
                if ($timeLog && !($timeLog->remarks === 'Incomplete Time Record' || (!$timeLog->time_in || !$timeLog->time_out))) {
                    $logType = $timeLog->log_type;
                    if (!isset($employeeBreakdown[$logType])) {
                        $employeeBreakdown[$logType] = [
                            'regular_hours' => 0,
                            'night_diff_regular_hours' => 0,
                            'overtime_hours' => 0,
                            'regular_overtime_hours' => 0,
                            'night_diff_overtime_hours' => 0,
                            'total_hours' => 0,
                            'days_count' => 0,
                            'display_name' => '',
                            'rate_config' => null
                        ];
                    }

                    // Calculate dynamically using current grace periods
                    $dynamicCalculation = $this->calculateTimeLogHoursDynamically($timeLog);
                    $regularHours = $dynamicCalculation['regular_hours'];
                    $nightDiffRegularHours = $dynamicCalculation['night_diff_regular_hours'] ?? 0;
                    $overtimeHours = $dynamicCalculation['overtime_hours'];
                    $regularOvertimeHours = $dynamicCalculation['regular_overtime_hours'] ?? 0;
                    $nightDiffOvertimeHours = $dynamicCalculation['night_diff_overtime_hours'] ?? 0;
                    $totalHours = $dynamicCalculation['total_hours'];

                    $employeeBreakdown[$logType]['regular_hours'] += $regularHours;
                    $employeeBreakdown[$logType]['night_diff_regular_hours'] += $nightDiffRegularHours;
                    $employeeBreakdown[$logType]['overtime_hours'] += $overtimeHours;
                    $employeeBreakdown[$logType]['regular_overtime_hours'] += $regularOvertimeHours;
                    $employeeBreakdown[$logType]['night_diff_overtime_hours'] += $nightDiffOvertimeHours;
                    $employeeBreakdown[$logType]['total_hours'] += $totalHours;
                    $employeeBreakdown[$logType]['days_count']++;

                    // Get rate configuration for this type (same as draft payroll)
                    $rateConfig = $timeLog->getRateConfiguration();
                    if ($rateConfig) {
                        $employeeBreakdown[$logType]['display_name'] = $rateConfig->display_name;
                        $employeeBreakdown[$logType]['rate_config'] = $rateConfig;
                    }
                }
            }

            $timeBreakdownsByEmployee[$employeeId] = $employeeBreakdown;
        }

        foreach ($payrollDetails as $detail) {
            $employee = $detail->employee;

            // For automated payrolls, calculate the earnings using THE EXACT SAME logic as draft mode display
            // This ensures 100% consistency between draft UI and snapshot data

            // Calculate using the same logic as the show method for draft payrolls
            $employeeBreakdown = $timeBreakdownsByEmployee[$employee->id] ?? [];
            $hourlyRate = $employee->hourly_rate ?? 0;

            $basicPay = 0; // Regular workday pay only
            $holidayPay = 0; // All holiday-related pay
            $restPay = 0; // Rest day pay
            $overtimePay = 0; // Overtime pay

            foreach ($employeeBreakdown as $logType => $breakdown) {
                $rateConfig = $breakdown['rate_config'];
                if (!$rateConfig) continue;

                // Calculate pay amounts using rate multipliers with PER-MINUTE precision (same as draft)
                $regularMultiplier = $rateConfig->regular_rate_multiplier ?? 1.0;
                $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 1.25;

                // PER-MINUTE CALCULATION for exact draft payroll matching
                $actualMinutes = $breakdown['regular_hours'] * 60;
                $roundedMinutes = round($actualMinutes);
                $ratePerMinute = $hourlyRate / 60;
                $regularPayAmount = $roundedMinutes * $ratePerMinute * $regularMultiplier;

                // Calculate night differential regular hours pay separately
                $nightDiffRegularPayAmount = 0;
                $nightDiffRegularHours = $breakdown['night_diff_regular_hours'] ?? 0;
                if ($nightDiffRegularHours > 0) {
                    $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
                    $nightDiffMultiplier = $nightDiffSetting ? $nightDiffSetting->rate_multiplier : 1.10;
                    $nightDiffRegularMinutes = round($nightDiffRegularHours * 60);
                    $nightDiffRegularPayAmount = $nightDiffRegularMinutes * $ratePerMinute * $nightDiffMultiplier;
                }

                // Calculate overtime pay with night differential breakdown using PER-MINUTE precision
                $overtimePayAmount = 0;
                $regularOvertimeHours = $breakdown['regular_overtime_hours'] ?? 0;
                $nightDiffOvertimeHours = $breakdown['night_diff_overtime_hours'] ?? 0;

                if ($regularOvertimeHours > 0 || $nightDiffOvertimeHours > 0) {
                    // Regular overtime pay (per-minute calculation)
                    if ($regularOvertimeHours > 0) {
                        $overtimeMinutes = round($regularOvertimeHours * 60);
                        $overtimePayAmount += $overtimeMinutes * $ratePerMinute * $overtimeMultiplier;
                    }

                    // Night differential overtime pay (per-minute calculation)
                    if ($nightDiffOvertimeHours > 0) {
                        $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
                        $nightDiffMultiplier = $nightDiffSetting ? $nightDiffSetting->rate_multiplier : 1.10;
                        $combinedMultiplier = $overtimeMultiplier + ($nightDiffMultiplier - 1);
                        $nightDiffOvertimeMinutes = round($nightDiffOvertimeHours * 60);
                        $overtimePayAmount += $nightDiffOvertimeMinutes * $ratePerMinute * $combinedMultiplier;
                    }
                } else {
                    // Fallback to simple calculation with per-minute precision
                    $overtimeMinutes = round($breakdown['overtime_hours'] * 60);
                    $overtimePayAmount = $overtimeMinutes * $ratePerMinute * $overtimeMultiplier;
                }

                if ($logType === 'regular_workday') {
                    $basicPay += $regularPayAmount; // Only regular pay to basic pay
                    $overtimePay += $overtimePayAmount; // Overtime pay separate
                } elseif ($logType === 'rest_day') {
                    $restPay += ($regularPayAmount + $overtimePayAmount); // Rest day pay includes both
                } elseif (in_array($logType, ['special_holiday', 'regular_holiday', 'rest_day_regular_holiday', 'rest_day_special_holiday'])) {
                    $holidayPay += ($regularPayAmount + $overtimePayAmount); // Holiday pay includes both
                }
            }

            // Round to exactly match the draft display (ensure consistency to the cent)
            $basicPay = round($basicPay, 2);
            $holidayPay = round($holidayPay, 2);
            $restPay = round($restPay, 2);
            $overtimePay = round($overtimePay, 2);

            // Get deductions and other components using the existing payroll calculation method
            $payrollCalculation = $this->calculateEmployeePayrollForPeriod(
                $employee,
                $payroll->period_start,
                $payroll->period_end,
                null // Pass null to force draft mode calculations
            );

            // Get the time breakdown for this employee to extract ONLY regular workday hours
            $employeeTimeBreakdown = $timeBreakdownsByEmployee[$employee->id] ?? [];

            // Extract only regular workday hours for basic pay calculation
            $regularWorkdayHours = $employeeTimeBreakdown['regular_workday']['regular_hours'] ?? 0;
            $regularWorkdayOvertimeHours = $employeeTimeBreakdown['regular_workday']['overtime_hours'] ?? 0;

            // Calculate total hours for all day types (for verification)
            $totalRegularHours = 0;
            $totalOvertimeHours = 0;
            $totalHolidayHours = 0;

            foreach ($employeeTimeBreakdown as $logType => $breakdown) {
                $totalRegularHours += $breakdown['regular_hours'];
                $totalOvertimeHours += $breakdown['overtime_hours'];
                if (in_array($logType, ['special_holiday', 'regular_holiday', 'rest_day_regular_holiday', 'rest_day_special_holiday'])) {
                    $totalHolidayHours += $breakdown['regular_hours'] + $breakdown['overtime_hours'];
                }
            }

            // Create detailed breakdowns for Basic, Holiday, Rest, and Overtime columns
            $basicBreakdown = $this->createBasicPayBreakdown($employeeTimeBreakdown, $employee);
            $holidayBreakdown = $this->createHolidayPayBreakdown($employeeTimeBreakdown, $employee);
            $restBreakdown = $this->createRestPayBreakdown($employeeTimeBreakdown, $employee);
            $overtimeBreakdown = $this->createOvertimePayBreakdown($employeeTimeBreakdown, $employee);

            // Use the SAME calculated amounts from the draft mode display logic
            // This ensures 100% consistency between draft and processing/locked payroll displays
            // $basicPay, $holidayPay, $restPay, $overtimePay are already calculated above using exact same logic as draft

            // Get breakdown data for allowances and bonuses (exactly as they are in draft mode)
            $allowancesBreakdown = $this->getEmployeeAllowancesBreakdown($employee, $payroll);
            $bonusesBreakdown = $this->getEmployeeBonusesBreakdown($employee, $payroll);
            $deductionsBreakdown = $this->getEmployeeDeductionsBreakdown($employee, $detail);

            // Log the calculated values for debugging
            Log::info("Snapshot calculation for employee {$employee->id}", [
                'basic_pay' => $basicPay,
                'holiday_pay' => $holidayPay,
                'overtime_pay' => $overtimePay,
                'rest_pay' => $restPay,
                'regular_workday_hours' => $regularWorkdayHours,
                'regular_workday_overtime_hours' => $regularWorkdayOvertimeHours,
                'total_regular_hours_all_types' => $totalRegularHours,
                'total_overtime_hours_all_types' => $totalOvertimeHours,
                'total_holiday_hours' => $totalHolidayHours,
                'gross_pay' => $payrollCalculation['gross_pay'] ?? 0,
            ]);

            // Get current settings snapshot
            $settingsSnapshot = $this->getCurrentSettingsSnapshot($employee);

            // Calculate total deductions exactly as they would be in draft mode
            // Use the calculated total deductions from the payroll calculation
            $totalDeductions = $payrollCalculation['total_deductions'] ?? 0;

            // Calculate net pay exactly as it would be in draft mode
            $grossPay = $payrollCalculation['gross_pay'] ?? 0;
            $netPay = $grossPay - $totalDeductions;

            // Create pay breakdown for snapshot
            $payBreakdown = [
                'basic_pay' => $basicPay,
                'holiday_pay' => $holidayPay,
                'rest_day_pay' => $restPay,
                'overtime_pay' => $overtimePay,
                'total_calculated' => $basicPay + $holidayPay + $restPay + $overtimePay
            ];

            // Calculate taxable income using the same logic as PayrollDetail.getTaxableIncomeAttribute()
            // This ensures consistency between dynamic and snapshot calculations
            $taxableIncome = $basicPay + $holidayPay + $restPay + $overtimePay;

            // Add only taxable allowances and bonuses
            $allowanceSettings = \App\Models\AllowanceBonusSetting::where('type', 'allowance')
                ->where('is_active', true)
                ->get();
            $bonusSettings = \App\Models\AllowanceBonusSetting::where('type', 'bonus')
                ->where('is_active', true)
                ->get();

            $allSettings = $allowanceSettings->merge($bonusSettings);

            // Log what settings we're processing
            Log::info("Processing allowance/bonus settings for taxable income", [
                'employee_id' => $employee->id,
                'allowance_settings' => $allowanceSettings->map(function ($s) {
                    return ['code' => $s->code, 'name' => $s->name, 'is_taxable' => $s->is_taxable, 'type' => $s->type];
                }),
                'bonus_settings' => $bonusSettings->map(function ($s) {
                    return ['code' => $s->code, 'name' => $s->name, 'is_taxable' => $s->is_taxable, 'type' => $s->type];
                })
            ]);

            foreach ($allSettings as $setting) {
                // Only add if this setting is taxable
                if (!$setting->is_taxable) {
                    Log::info("Skipping non-taxable setting: {$setting->code} ({$setting->type})");
                    continue;
                }

                Log::info("Processing taxable setting: {$setting->code} ({$setting->type})");

                $calculatedAmount = 0;                // Calculate the amount based on the setting type
                if ($setting->calculation_type === 'percentage') {
                    $calculatedAmount = ($basicPay * $setting->rate_percentage) / 100;
                } elseif ($setting->calculation_type === 'fixed_amount') {
                    $calculatedAmount = $setting->fixed_amount;

                    // Apply frequency-based calculation for daily allowances
                    if ($setting->frequency === 'daily') {
                        $workingDays = 0;

                        // Count working days from time breakdown
                        foreach ($employeeTimeBreakdown as $logType => $breakdown) {
                            if ($breakdown['regular_hours'] > 0) {
                                $workingDays += $breakdown['days_count'] ?? 0;
                            }
                        }

                        $maxDays = $setting->max_days_per_period ?? $workingDays;
                        $applicableDays = min($workingDays, $maxDays);

                        $calculatedAmount = $setting->fixed_amount * $applicableDays;
                    }
                } elseif ($setting->calculation_type === 'daily_rate_multiplier') {
                    $dailyRate = $employee->daily_rate ?? 0;
                    $multiplier = $setting->multiplier ?? 1;
                    $calculatedAmount = $dailyRate * $multiplier;
                }

                // Apply limits
                if ($setting->minimum_amount && $calculatedAmount < $setting->minimum_amount) {
                    $calculatedAmount = $setting->minimum_amount;
                }
                if ($setting->maximum_amount && $calculatedAmount > $setting->maximum_amount) {
                    $calculatedAmount = $setting->maximum_amount;
                }

                // Add taxable allowance/bonus to taxable income
                $taxableIncome += $calculatedAmount;

                Log::info("Added taxable amount", [
                    'setting_code' => $setting->code,
                    'setting_type' => $setting->type,
                    'calculated_amount' => $calculatedAmount,
                    'running_taxable_income' => $taxableIncome
                ]);
            }

            $taxableIncome = max(0, $taxableIncome);

            // Log taxable income calculation for debugging
            Log::info("Taxable income calculation for employee {$employee->id}", [
                'basic_pay' => $basicPay,
                'holiday_pay' => $holidayPay,
                'rest_pay' => $restPay,
                'overtime_pay' => $overtimePay,
                'base_total' => $basicPay + $holidayPay + $restPay + $overtimePay,
                'gross_pay' => $grossPay,
                'taxable_income_final' => $taxableIncome,
                'allowance_settings_count' => $allowanceSettings->count(),
                'bonus_settings_count' => $bonusSettings->count(),
            ]);

            // Create snapshot with exact draft mode calculations
            $snapshot = \App\Models\PayrollSnapshot::create([
                'payroll_id' => $payroll->id,
                'employee_id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'employee_name' => $employee->first_name . ' ' . $employee->last_name,
                'department' => $employee->department->name ?? 'N/A',
                'position' => $employee->position->title ?? 'N/A',
                'basic_salary' => $employee->basic_salary ?? 0,
                'daily_rate' => $employee->daily_rate ?? 0,
                'hourly_rate' => $employee->hourly_rate ?? 0,
                'days_worked' => $payrollCalculation['days_worked'] ?? 0,
                'regular_hours' => $regularWorkdayHours, // Only regular workday hours, not all regular hours
                'overtime_hours' => $regularWorkdayOvertimeHours, // Only regular workday overtime hours
                'holiday_hours' => $totalHolidayHours, // Total holiday hours (regular + overtime)
                'night_differential_hours' => $payrollCalculation['night_differential_hours'] ?? 0,
                'regular_pay' => $basicPay, // Use calculated basic pay
                'overtime_pay' => $overtimePay, // Use calculated overtime pay
                'holiday_pay' => $holidayPay, // Use calculated holiday pay
                'rest_day_pay' => $restPay, // Use calculated rest day pay
                'night_differential_pay' => $payrollCalculation['night_differential_pay'] ?? 0,
                'basic_breakdown' => $basicBreakdown,
                'holiday_breakdown' => $holidayBreakdown,
                'rest_breakdown' => $restBreakdown,
                'overtime_breakdown' => $overtimeBreakdown,
                'allowances_breakdown' => $allowancesBreakdown,
                'allowances_total' => $payrollCalculation['allowances'] ?? 0,
                'bonuses_breakdown' => $bonusesBreakdown,
                'bonuses_total' => $payrollCalculation['bonuses'] ?? 0,
                'other_earnings' => $payrollCalculation['other_earnings'] ?? 0,
                'gross_pay' => $grossPay, // Use calculated gross pay
                'deductions_breakdown' => $deductionsBreakdown,
                'sss_contribution' => $payrollCalculation['sss_contribution'] ?? 0,
                'philhealth_contribution' => $payrollCalculation['philhealth_contribution'] ?? 0,
                'pagibig_contribution' => $payrollCalculation['pagibig_contribution'] ?? 0,
                'withholding_tax' => $payrollCalculation['withholding_tax'] ?? 0,
                'late_deductions' => 0, // Set to 0 as late/undertime are already accounted for in hours
                'undertime_deductions' => 0, // Set to 0 as late/undertime are already accounted for in hours
                'cash_advance_deductions' => $payrollCalculation['cash_advance_deductions'] ?? 0,
                'other_deductions' => $payrollCalculation['other_deductions'] ?? 0,
                'total_deductions' => $totalDeductions, // Use calculated total
                'net_pay' => $netPay, // Use calculated net pay
                'taxable_income' => $taxableIncome, // Store calculated taxable income
                'settings_snapshot' => array_merge($settingsSnapshot, ['pay_breakdown' => $payBreakdown]),
                'remarks' => 'Snapshot created at ' . now()->format('Y-m-d H:i:s') . ' - Captures exact draft calculations',
            ]);

            // IMPORTANT: Also update the payroll_details table to match the snapshot values
            // This ensures consistency between snapshot data and payroll_details fallback
            $payrollDetail = $payroll->payrollDetails()->where('employee_id', $employee->id)->first();
            if ($payrollDetail) {
                $payrollDetail->update([
                    'regular_pay' => $basicPay, // Update with the exact snapshot value
                    'holiday_pay' => $holidayPay,
                    'overtime_pay' => $overtimePay,
                    'rest_day_pay' => $restPay,
                    'gross_pay' => $grossPay,
                    'total_deductions' => $totalDeductions,
                    'net_pay' => $netPay,
                ]);

                Log::info("Updated payroll_details for employee {$employee->id} to match snapshot", [
                    'regular_pay' => $basicPay,
                    'holiday_pay' => $holidayPay,
                    'overtime_pay' => $overtimePay,
                    'gross_pay' => $grossPay
                ]);
            }

            Log::info("Created snapshot for employee {$employee->id} with calculated values", [
                'payroll_id' => $payroll->id,
                'employee_id' => $employee->id,
                'basic_pay' => $basicPay,
                'holiday_pay' => $holidayPay,
                'overtime_pay' => $overtimePay,
                'rest_pay' => $restPay,
                'gross_pay' => $grossPay,
                'total_deductions' => $totalDeductions,
                'net_pay' => $netPay,
                'deductions_count' => count($deductionsBreakdown),
            ]);
        }

        Log::info("Successfully created " . count($payrollDetails) . " snapshots for payroll {$payroll->id}");
    }

    /**
     * Debug method to view payroll snapshots (for testing/troubleshooting)
     */
    public function debugSnapshots(Payroll $payroll)
    {
        $this->authorize('view payrolls');

        $snapshots = $payroll->snapshots()->get();

        return response()->json([
            'payroll_id' => $payroll->id,
            'payroll_status' => $payroll->status,
            'payroll_number' => $payroll->payroll_number,
            'snapshot_count' => $snapshots->count(),
            'snapshots' => $snapshots->map(function ($snapshot) {
                return [
                    'employee_id' => $snapshot->employee_id,
                    'employee_name' => $snapshot->employee_name,
                    'gross_pay' => $snapshot->gross_pay,
                    'total_deductions' => $snapshot->total_deductions,
                    'net_pay' => $snapshot->net_pay,
                    'deductions_breakdown' => $snapshot->deductions_breakdown,
                    'deductions_breakdown_count' => is_array($snapshot->deductions_breakdown) ? count($snapshot->deductions_breakdown) : 0,
                    'created_at' => $snapshot->created_at,
                ];
            })
        ], 200, [], JSON_PRETTY_PRINT);
    }

    /**
     * Get allowances breakdown for employee
     */
    private function getEmployeeAllowancesBreakdown(Employee $employee, Payroll $payroll)
    {
        $breakdown = [];

        // Get active allowance settings that apply to this employee's benefit status
        $allowanceSettings = \App\Models\AllowanceBonusSetting::where('is_active', true)
            ->where('type', 'allowance')
            ->forBenefitStatus($employee->benefits_status)
            ->orderBy('sort_order')
            ->get();

        foreach ($allowanceSettings as $setting) {
            // Calculate hours data for this employee
            $timeLogs = TimeLog::where('employee_id', $employee->id)
                ->whereBetween('log_date', [$payroll->period_start, $payroll->period_end])
                ->get();

            $regularHours = $timeLogs->sum('regular_hours') ?? 0;
            $overtimeHours = $timeLogs->sum('overtime_hours') ?? 0;
            $holidayHours = $timeLogs->sum('holiday_hours') ?? 0;

            $amount = $this->calculateAllowanceBonusAmountForPayroll(
                $setting,
                $employee,
                $payroll,
                $regularHours,
                $overtimeHours,
                $holidayHours
            );

            if ($amount > 0) {
                $breakdown[] = [
                    'name' => $setting->name,
                    'code' => $setting->code ?? $setting->name,
                    'amount' => $amount,
                    'is_taxable' => $setting->is_taxable ?? true,
                    'calculation_type' => $setting->calculation_type,
                    'description' => $setting->description ?? ''
                ];
            }
        }

        return $breakdown;
    }

    /**
     * Get bonuses breakdown for employee
     */
    private function getEmployeeBonusesBreakdown(Employee $employee, Payroll $payroll)
    {
        $breakdown = [];

        // Get active bonus settings that apply to this employee's benefit status
        $bonusSettings = \App\Models\AllowanceBonusSetting::where('is_active', true)
            ->where('type', 'bonus')
            ->forBenefitStatus($employee->benefits_status)
            ->orderBy('sort_order')
            ->get();

        foreach ($bonusSettings as $setting) {
            // Calculate hours data for this employee
            $timeLogs = TimeLog::where('employee_id', $employee->id)
                ->whereBetween('log_date', [$payroll->period_start, $payroll->period_end])
                ->get();

            $regularHours = $timeLogs->sum('regular_hours') ?? 0;
            $overtimeHours = $timeLogs->sum('overtime_hours') ?? 0;
            $holidayHours = $timeLogs->sum('holiday_hours') ?? 0;

            $amount = $this->calculateAllowanceBonusAmountForPayroll(
                $setting,
                $employee,
                $payroll,
                $regularHours,
                $overtimeHours,
                $holidayHours
            );

            if ($amount > 0) {
                $breakdown[] = [
                    'name' => $setting->name,
                    'code' => $setting->code ?? $setting->name,
                    'amount' => $amount,
                    'is_taxable' => $setting->is_taxable ?? true,
                    'calculation_type' => $setting->calculation_type,
                    'description' => $setting->description ?? ''
                ];
            }
        }

        return $breakdown;
    }

    /**
     * Get deductions breakdown for employee
     */
    private function getEmployeeDeductionsBreakdown(Employee $employee, PayrollDetail $detail)
    {
        $breakdown = [];

        // Get active deduction settings that apply to this employee's benefit status
        $deductionSettings = \App\Models\DeductionTaxSetting::active()
            ->forBenefitStatus($employee->benefits_status)
            ->orderBy('sort_order')
            ->get();

        if ($deductionSettings->isNotEmpty()) {
            // Use dynamic calculation for active deduction settings
            // For SSS/government deductions, use taxable income (basic + holiday + rest + taxable allowances/bonuses)
            $taxableIncomeForDeductions = ($detail->regular_pay ?? 0) + ($detail->holiday_pay ?? 0) + ($detail->rest_day_pay ?? 0);

            foreach ($deductionSettings as $setting) {
                $amount = $setting->calculateDeduction(
                    $taxableIncomeForDeductions, // Use taxable income instead of just regular_pay
                    $detail->overtime_pay ?? 0,
                    $detail->bonuses ?? 0,
                    $detail->allowances ?? 0,
                    $detail->gross_pay ?? 0
                );

                if ($amount > 0) {
                    $breakdown[] = [
                        'name' => $setting->name,
                        'code' => $setting->code ?? strtolower(str_replace(' ', '_', $setting->name)),
                        'amount' => $amount,
                        'type' => $setting->type ?? 'government',
                        'calculation_type' => $setting->calculation_type
                    ];
                }
            }
        } else {
            // Fallback to traditional static deductions if no active settings
            if ($detail->sss_contribution > 0) {
                $breakdown[] = [
                    'name' => 'SSS Contribution',
                    'code' => 'sss',
                    'amount' => $detail->sss_contribution,
                    'type' => 'government'
                ];
            }

            if ($detail->philhealth_contribution > 0) {
                $breakdown[] = [
                    'name' => 'PhilHealth Contribution',
                    'code' => 'philhealth',
                    'amount' => $detail->philhealth_contribution,
                    'type' => 'government'
                ];
            }

            if ($detail->pagibig_contribution > 0) {
                $breakdown[] = [
                    'name' => 'Pag-IBIG Contribution',
                    'code' => 'pagibig',
                    'amount' => $detail->pagibig_contribution,
                    'type' => 'government'
                ];
            }

            if ($detail->withholding_tax > 0) {
                $breakdown[] = [
                    'name' => 'Withholding Tax',
                    'code' => 'withholding_tax',
                    'amount' => $detail->withholding_tax,
                    'type' => 'tax'
                ];
            }
        }

        // Always include other deductions (excluding late/undertime as they're already accounted for in hours)
        if ($detail->cash_advance_deductions > 0) {
            $breakdown[] = [
                'name' => 'Cash Advance',
                'code' => 'cash_advance',
                'amount' => $detail->cash_advance_deductions,
                'type' => 'loan'
            ];
        }

        if ($detail->other_deductions > 0) {
            $breakdown[] = [
                'name' => 'Other Deductions',
                'code' => 'other',
                'amount' => $detail->other_deductions,
                'type' => 'other'
            ];
        }

        return $breakdown;
    }

    /**
     * Get current settings snapshot for employee
     */
    private function getCurrentSettingsSnapshot(Employee $employee)
    {
        return [
            'benefit_status' => $employee->benefits_status,
            'pay_schedule' => $employee->pay_schedule,
            'allowance_settings' => \App\Models\AllowanceBonusSetting::where('is_active', true)
                ->where('type', 'allowance')
                ->forBenefitStatus($employee->benefits_status)
                ->select('id', 'name', 'calculation_type', 'fixed_amount', 'rate_percentage')
                ->get()
                ->toArray(),
            'bonus_settings' => \App\Models\AllowanceBonusSetting::where('is_active', true)
                ->where('type', 'bonus')
                ->forBenefitStatus($employee->benefits_status)
                ->select('id', 'name', 'calculation_type', 'fixed_amount', 'rate_percentage')
                ->get()
                ->toArray(),
            'deduction_settings' => \App\Models\DeductionTaxSetting::active()
                ->forBenefitStatus($employee->benefits_status)
                ->select('id', 'name', 'calculation_type', 'fixed_amount', 'rate_percentage')
                ->get()
                ->toArray(),
            'captured_at' => now()->toISOString(),
        ];
    }

    /**
     * Show draft payroll for a specific employee (dynamic calculations)
     */
    public function showDraftPayroll(Request $request, $schedule, $employee)
    {
        $this->authorize('view payrolls');

        $employee = Employee::findOrFail($employee);
        $selectedSchedule = \App\Models\PayScheduleSetting::systemDefaults()
            ->where('code', $schedule)
            ->first();

        if (!$selectedSchedule) {
            return redirect()->route('payrolls.automation.index')
                ->with('error', 'Invalid pay schedule selected.');
        }

        $currentPeriod = $this->calculateCurrentPayPeriod($selectedSchedule);
        $payrollCalculation = $this->calculateEmployeePayrollForPeriod($employee, $currentPeriod['start'], $currentPeriod['end']);

        // Debug logging
        Log::info('Draft Payroll Calculation for Employee ' . $employee->id, [
            'allowances' => $payrollCalculation['allowances'] ?? 'NOT SET',
            'allowances_details' => $payrollCalculation['allowances_details'] ?? 'NOT SET',
            'deductions_details' => $payrollCalculation['deductions_details'] ?? 'NOT SET',
        ]);

        // Create mock payroll for display
        $draftPayroll = new Payroll();
        $draftPayroll->id = $employee->id;
        $draftPayroll->payroll_number = 'DRAFT-' . $employee->employee_number;
        $draftPayroll->period_start = $currentPeriod['start'];
        $draftPayroll->period_end = $currentPeriod['end'];
        $draftPayroll->pay_date = $currentPeriod['pay_date'];
        $draftPayroll->status = 'draft';
        $draftPayroll->payroll_type = 'automated';
        $draftPayroll->total_gross = $payrollCalculation['gross_pay'] ?? 0;
        $draftPayroll->total_deductions = $payrollCalculation['total_deductions'] ?? 0;
        $draftPayroll->total_net = $payrollCalculation['net_pay'] ?? 0;
        $draftPayroll->created_at = now();

        // Set relationships
        $fakeCreator = (object) ['name' => 'System (Draft)', 'id' => 0];
        $draftPayroll->setRelation('creator', $fakeCreator);
        $draftPayroll->setRelation('approver', null);

        // Create mock payroll detail
        $draftPayrollDetail = new PayrollDetail();
        $draftPayrollDetail->employee_id = $employee->id;
        $draftPayrollDetail->basic_salary = $employee->basic_salary ?? 0;
        $draftPayrollDetail->daily_rate = $employee->daily_rate ?? 0;
        $draftPayrollDetail->hourly_rate = $employee->hourly_rate ?? 0;
        $draftPayrollDetail->days_worked = $payrollCalculation['days_worked'] ?? 0;
        $draftPayrollDetail->regular_hours = $payrollCalculation['regular_hours'] ?? 0;
        $draftPayrollDetail->overtime_hours = $payrollCalculation['overtime_hours'] ?? 0;
        $draftPayrollDetail->holiday_hours = $payrollCalculation['holiday_hours'] ?? 0;
        $draftPayrollDetail->regular_pay = $payrollCalculation['basic_salary'] ?? 0;
        $draftPayrollDetail->overtime_pay = $payrollCalculation['overtime_pay'] ?? 0;
        $draftPayrollDetail->holiday_pay = $payrollCalculation['holiday_pay'] ?? 0;
        $draftPayrollDetail->allowances = $payrollCalculation['allowances'] ?? 0;
        $draftPayrollDetail->bonuses = $payrollCalculation['bonuses'] ?? 0;
        $draftPayrollDetail->gross_pay = $payrollCalculation['gross_pay'] ?? 0;
        $draftPayrollDetail->sss_contribution = $payrollCalculation['sss_deduction'] ?? 0;
        $draftPayrollDetail->philhealth_contribution = $payrollCalculation['philhealth_deduction'] ?? 0;
        $draftPayrollDetail->pagibig_contribution = $payrollCalculation['pagibig_deduction'] ?? 0;
        $draftPayrollDetail->withholding_tax = $payrollCalculation['tax_deduction'] ?? 0;
        $draftPayrollDetail->late_deductions = 0; // Not calculated in this method yet
        $draftPayrollDetail->undertime_deductions = 0; // Not calculated in this method yet
        $draftPayrollDetail->cash_advance_deductions = 0; // Not calculated in this method yet
        $draftPayrollDetail->other_deductions = $payrollCalculation['other_deductions'] ?? 0;
        $draftPayrollDetail->total_deductions = $payrollCalculation['total_deductions'] ?? 0;
        $draftPayrollDetail->net_pay = $payrollCalculation['net_pay'] ?? 0;

        // Set earnings and deduction breakdowns
        if (isset($payrollCalculation['allowances_details'])) {
            $draftPayrollDetail->earnings_breakdown = json_encode([
                'allowances' => $payrollCalculation['allowances_details']
            ]);
        }
        if (isset($payrollCalculation['deductions_details'])) {
            $draftPayrollDetail->deduction_breakdown = json_encode($payrollCalculation['deductions_details']);
        }

        // Set employee relationship
        $draftPayrollDetail->setRelation('employee', $employee->load(['user', 'department', 'position', 'daySchedule', 'timeSchedule']));

        // Set payroll details collection
        $draftPayroll->setRelation('payrollDetails', collect([$draftPayrollDetail]));

        // Create period dates array
        $startDate = \Carbon\Carbon::parse($currentPeriod['start']);
        $endDate = \Carbon\Carbon::parse($currentPeriod['end']);
        $periodDates = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $periodDates[] = $date->format('Y-m-d');
        }

        // Get DTR data
        $timeLogs = TimeLog::where('employee_id', $employee->id)
            ->whereBetween('log_date', [$currentPeriod['start'], $currentPeriod['end']])
            ->orderBy('log_date')
            ->get()
            ->groupBy(function ($item) {
                return \Carbon\Carbon::parse($item->log_date)->format('Y-m-d');
            });

        // Build DTR data structure matching working version
        $employeeDtr = [];
        foreach ($periodDates as $date) {
            $timeLog = $timeLogs->get($date, collect())->first();
            $employeeDtr[$date] = $timeLog;
        }
        $dtrData = [$employee->id => $employeeDtr];

        // Create time breakdowns similar to regular show method
        $timeBreakdowns = [$employee->id => []];
        $employeeBreakdown = [];

        foreach ($periodDates as $date) {
            $timeLog = $timeLogs->get($date, collect())->first();
            if ($timeLog && !($timeLog->remarks === 'Incomplete Time Record' || (!$timeLog->time_in || !$timeLog->time_out))) {
                $logType = $timeLog->log_type;
                if (!isset($employeeBreakdown[$logType])) {
                    $employeeBreakdown[$logType] = [
                        'regular_hours' => 0,
                        'overtime_hours' => 0,
                        'total_hours' => 0,
                        'days_count' => 0,
                        'display_name' => '',
                        'rate_config' => null
                    ];
                }

                $employeeBreakdown[$logType]['regular_hours'] += $timeLog->regular_hours ?? 0;
                $employeeBreakdown[$logType]['overtime_hours'] += $timeLog->overtime_hours ?? 0;
                $employeeBreakdown[$logType]['total_hours'] += $timeLog->total_hours ?? 0;
                $employeeBreakdown[$logType]['days_count']++;

                // Get rate configuration for this type
                $rateConfig = $timeLog->getRateConfiguration();
                if ($rateConfig) {
                    $employeeBreakdown[$logType]['display_name'] = $rateConfig->display_name;
                    $employeeBreakdown[$logType]['rate_config'] = $rateConfig;
                }
            }
        }

        $timeBreakdowns[$employee->id] = $employeeBreakdown;

        // Calculate pay breakdown by employee
        $hourlyRate = $employee->hourly_rate ?? 0;
        $basicPay = 0;
        $holidayPay = 0;

        foreach ($employeeBreakdown as $logType => $breakdown) {
            $rateConfig = $breakdown['rate_config'];
            if (!$rateConfig) continue;

            $regularMultiplier = $rateConfig->regular_rate_multiplier ?? 1.0;
            $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 1.25;

            $regularPay = $breakdown['regular_hours'] * $hourlyRate * $regularMultiplier;
            $overtimePay = $breakdown['overtime_hours'] * $hourlyRate * $overtimeMultiplier;

            if ($logType === 'regular_workday') {
                $basicPay += $regularPay;
            } elseif (in_array($logType, ['special_holiday', 'regular_holiday', 'rest_day_regular_holiday', 'rest_day_special_holiday'])) {
                $holidayPay += ($regularPay + $overtimePay);
            }
        }

        $payBreakdownByEmployee = [
            $employee->id => [
                'basic_pay' => $basicPay,
                'holiday_pay' => $holidayPay,
            ]
        ];

        // Get settings
        $allowanceSettings = \App\Models\AllowanceBonusSetting::where('is_active', true)
            ->where('type', 'allowance')
            ->orderBy('sort_order')
            ->get();
        $bonusSettings = \App\Models\AllowanceBonusSetting::where('is_active', true)
            ->where('type', 'bonus')
            ->orderBy('sort_order')
            ->get();
        $deductionSettings = \App\Models\DeductionTaxSetting::active()
            ->orderBy('sort_order')
            ->get();

        $totalHolidayPay = $holidayPay;

        return view('payrolls.show', compact(
            'draftPayroll',
            'dtrData',
            'periodDates',
            'allowanceSettings',
            'bonusSettings',
            'deductionSettings',
            'timeBreakdowns',
            'payBreakdownByEmployee',
            'totalHolidayPay'
        ) + [
            'payroll' => $draftPayroll,
            'isDraft' => true,
            'isDynamic' => true,
            'schedule' => $schedule,
            'employee' => $employee->id
        ]);
    }

    /**
     * Process draft payroll (save to database)
     */
    public function processDraftPayroll(Request $request, $schedule, $employee)
    {
        $this->authorize('create payrolls');

        $employee = Employee::findOrFail($employee);
        $selectedSchedule = \App\Models\PayScheduleSetting::systemDefaults()
            ->where('code', $schedule)
            ->first();

        if (!$selectedSchedule) {
            return redirect()->route('payrolls.automation.index')
                ->with('error', 'Invalid pay schedule selected.');
        }

        $currentPeriod = $this->calculateCurrentPayPeriod($selectedSchedule);

        // Check if payroll already exists
        $existingPayroll = Payroll::whereHas('payrollDetails', function ($query) use ($employee) {
            $query->where('employee_id', $employee->id);
        })
            ->where('pay_schedule', $schedule)
            ->where('period_start', $currentPeriod['start'])
            ->where('period_end', $currentPeriod['end'])
            ->where('payroll_type', 'automated')
            ->first();

        if ($existingPayroll) {
            return redirect()->route('payrolls.automation.processing.show', [
                'schedule' => $schedule,
                'employee' => $employee->id
            ])->with('info', 'This payroll is already processed.');
        }

        try {
            // Create payroll with snapshot and "processing" status
            $employees = collect([$employee]);
            $createdPayroll = $this->autoCreatePayrollForPeriod($selectedSchedule, $currentPeriod, $employees, 'processing');

            return redirect()->route('payrolls.automation.processing.show', [
                'schedule' => $schedule,
                'employee' => $employee->id
            ])->with('success', 'Payroll processed and saved to database with data snapshot.');
        } catch (\Exception $e) {
            Log::error('Failed to process draft payroll: ' . $e->getMessage());
            return redirect()->route('payrolls.automation.draft.show', [
                'schedule' => $schedule,
                'employee' => $employee->id
            ])->with('error', 'Failed to process payroll: ' . $e->getMessage());
        }
    }

    /**
     * Show processing payroll (saved to database)
     */
    public function showProcessingPayroll(Request $request, $schedule, $employee)
    {
        $this->authorize('view payrolls');

        $employee = Employee::findOrFail($employee);
        $selectedSchedule = \App\Models\PayScheduleSetting::systemDefaults()
            ->where('code', $schedule)
            ->first();

        if (!$selectedSchedule) {
            return redirect()->route('payrolls.automation.index')
                ->with('error', 'Invalid pay schedule selected.');
        }

        $currentPeriod = $this->calculateCurrentPayPeriod($selectedSchedule);

        // Find existing payroll
        $payroll = Payroll::whereHas('payrollDetails', function ($query) use ($employee) {
            $query->where('employee_id', $employee->id);
        })
            ->where('pay_schedule', $schedule)
            ->where('period_start', $currentPeriod['start'])
            ->where('period_end', $currentPeriod['end'])
            ->where('payroll_type', 'automated')
            ->first();

        if (!$payroll) {
            return redirect()->route('payrolls.automation.draft.show', [
                'schedule' => $schedule,
                'employee' => $employee->id
            ])->with('info', 'No processed payroll found. Showing draft mode.');
        }

        // Call the regular show method but pass additional data for automation context
        $originalShow = $this->show($payroll);

        // Add automation-specific data to the view data
        $viewData = $originalShow->getData();
        $viewData['schedule'] = $schedule;
        $viewData['employee'] = $employee->id;

        return $originalShow->with($viewData);
    }

    /**
     * Back to draft for single employee
     */
    public function backToDraftSingle(Request $request, $schedule, $employee)
    {
        $this->authorize('delete payrolls');

        $employee = Employee::findOrFail($employee);
        $selectedSchedule = \App\Models\PayScheduleSetting::systemDefaults()
            ->where('code', $schedule)
            ->first();

        if (!$selectedSchedule) {
            return redirect()->route('payrolls.automation.index')
                ->with('error', 'Invalid pay schedule selected.');
        }

        $currentPeriod = $this->calculateCurrentPayPeriod($selectedSchedule);

        // Find existing payroll
        $existingPayroll = Payroll::whereHas('payrollDetails', function ($query) use ($employee) {
            $query->where('employee_id', $employee->id);
        })
            ->where('pay_schedule', $schedule)
            ->where('period_start', $currentPeriod['start'])
            ->where('period_end', $currentPeriod['end'])
            ->where('payroll_type', 'automated')
            ->first();

        if (!$existingPayroll) {
            return redirect()->route('payrolls.automation.draft.show', [
                'schedule' => $schedule,
                'employee' => $employee->id
            ])->with('info', 'No saved payroll found. Already in draft mode.');
        }

        if ($existingPayroll->status === 'approved') {
            return redirect()->route('payrolls.automation.processing.show', [
                'schedule' => $schedule,
                'employee' => $employee->id
            ])->with('error', 'Cannot return to draft mode. This payroll is already approved.');
        }

        try {
            DB::beginTransaction();

            // Delete payroll details and payroll
            PayrollDetail::where('payroll_id', $existingPayroll->id)->delete();
            $existingPayroll->delete();

            DB::commit();

            return redirect()->route('payrolls.automation.draft.show', [
                'schedule' => $schedule,
                'employee' => $employee->id
            ])->with('success', 'Successfully deleted saved payroll. Returned to draft mode.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to return payroll to draft: ' . $e->getMessage());
            return redirect()->route('payrolls.automation.processing.show', [
                'schedule' => $schedule,
                'employee' => $employee->id
            ])->with('error', 'Failed to return to draft mode: ' . $e->getMessage());
        }
    }

    /**
     * Show payroll with additional data for automation context
     */
    private function showPayrollWithAdditionalData($payroll, $additionalData = [])
    {
        // Load all required relationships
        $payroll->load([
            'payrollDetails.employee.user',
            'payrollDetails.employee.department',
            'payrollDetails.employee.position',
            'creator',
            'approver'
        ]);

        // Get all the data needed for the show view (simplified version)
        $employeeIds = $payroll->payrollDetails->pluck('employee_id');

        $startDate = \Carbon\Carbon::parse($payroll->period_start);
        $endDate = \Carbon\Carbon::parse($payroll->period_end);
        $periodDates = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $periodDates[] = $date->format('Y-m-d');
        }

        $timeLogs = TimeLog::whereIn('employee_id', $employeeIds)
            ->whereBetween('log_date', [$payroll->period_start, $payroll->period_end])
            ->orderBy('log_date')
            ->get()
            ->groupBy(['employee_id']);

        $dtrData = [];
        foreach ($payroll->payrollDetails as $detail) {
            $employeeLogs = $timeLogs->get($detail->employee_id, collect());
            $employeeLogsByDate = $employeeLogs->groupBy(function ($item) {
                return \Carbon\Carbon::parse($item->log_date)->format('Y-m-d');
            });

            // Build DTR data structure matching automation format
            $employeeDtr = [];
            foreach ($periodDates as $date) {
                $timeLog = $employeeLogsByDate->get($date, collect())->first();
                $employeeDtr[$date] = $timeLog;
            }
            $dtrData[$detail->employee_id] = $employeeDtr;
        }

        $allowanceSettings = \App\Models\AllowanceBonusSetting::where('is_active', true)
            ->where('type', 'allowance')
            ->orderBy('sort_order')
            ->get();
        $deductionSettings = \App\Models\DeductionTaxSetting::active()
            ->orderBy('sort_order')
            ->get();

        // Calculate time breakdowns and pay breakdowns like the regular show method
        $timeBreakdowns = [];
        foreach ($payroll->payrollDetails as $detail) {
            $employeeLogs = $timeLogs->get($detail->employee_id, collect());
            $employeeBreakdown = [];

            foreach ($periodDates as $date) {
                $dayLogs = $employeeLogs->where('log_date', $date);
                if ($dayLogs->isNotEmpty()) {
                    $timeLog = $dayLogs->first();
                    if ($timeLog && !($timeLog->remarks === 'Incomplete Time Record' || (!$timeLog->time_in || !$timeLog->time_out))) {
                        $logType = $timeLog->log_type;
                        if (!isset($employeeBreakdown[$logType])) {
                            $employeeBreakdown[$logType] = [
                                'regular_hours' => 0,
                                'overtime_hours' => 0,
                                'total_hours' => 0,
                                'days_count' => 0,
                                'display_name' => '',
                                'rate_config' => null
                            ];
                        }

                        $employeeBreakdown[$logType]['regular_hours'] += $timeLog->regular_hours ?? 0;
                        $employeeBreakdown[$logType]['overtime_hours'] += $timeLog->overtime_hours ?? 0;
                        $employeeBreakdown[$logType]['total_hours'] += $timeLog->total_hours ?? 0;
                        $employeeBreakdown[$logType]['days_count']++;

                        // Get rate configuration for this type
                        $rateConfig = $timeLog->getRateConfiguration();
                        if ($rateConfig) {
                            $employeeBreakdown[$logType]['display_name'] = $rateConfig->display_name;
                            $employeeBreakdown[$logType]['rate_config'] = $rateConfig;
                        }
                    }
                }
            }

            $timeBreakdowns[$detail->employee_id] = $employeeBreakdown;
        }

        // Calculate separate basic pay and holiday pay for each employee
        $payBreakdownByEmployee = [];
        foreach ($payroll->payrollDetails as $detail) {
            // For processing/approved payrolls, use stored values from PayrollDetail
            if ($payroll->status !== 'draft') {
                $payBreakdownByEmployee[$detail->employee_id] = [
                    'basic_pay' => $detail->regular_pay ?? 0,
                    'holiday_pay' => $detail->holiday_pay ?? 0,
                    'rest_day_pay' => $detail->rest_day_pay ?? 0,
                    'overtime_pay' => $detail->overtime_pay ?? 0,
                ];
            } else {
                // For draft payrolls, calculate dynamically from time logs
                $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
                $hourlyRate = $detail->employee->hourly_rate ?? 0;

                $basicPay = 0; // Regular workday pay only
                $holidayPay = 0; // All holiday-related pay
                $restDayPay = 0; // Rest day pay
                $overtimePay = 0; // All overtime pay

                foreach ($employeeBreakdown as $logType => $breakdown) {
                    $rateConfig = $breakdown['rate_config'];
                    if (!$rateConfig) continue;

                    // Calculate pay amounts using rate multipliers
                    $regularMultiplier = $rateConfig->regular_rate_multiplier ?? 1.0;
                    $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 1.25;

                    $regularPay = $breakdown['regular_hours'] * $hourlyRate * $regularMultiplier;

                    // Calculate overtime pay with night differential breakdown
                    $overtimePayAmount = 0;
                    $regularOvertimeHours = $breakdown['regular_overtime_hours'] ?? 0;
                    $nightDiffOvertimeHours = $breakdown['night_diff_overtime_hours'] ?? 0;

                    if ($regularOvertimeHours > 0 || $nightDiffOvertimeHours > 0) {
                        // Use breakdown calculation

                        // Regular overtime pay
                        if ($regularOvertimeHours > 0) {
                            $overtimePayAmount += $regularOvertimeHours * $hourlyRate * $overtimeMultiplier;
                        }

                        // Night differential overtime pay (overtime rate + night differential bonus)
                        if ($nightDiffOvertimeHours > 0) {
                            // Get night differential setting
                            $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
                            $nightDiffMultiplier = $nightDiffSetting ? $nightDiffSetting->rate_multiplier : 1.10;

                            // Combined rate: base overtime rate + night differential bonus
                            $combinedMultiplier = $overtimeMultiplier + ($nightDiffMultiplier - 1);
                            $overtimePayAmount += $nightDiffOvertimeHours * $hourlyRate * $combinedMultiplier;
                        }
                    } else {
                        // Fallback to simple calculation if no breakdown available
                        $overtimePayAmount = $breakdown['overtime_hours'] * $hourlyRate * $overtimeMultiplier;
                    }

                    // All overtime goes to overtime column regardless of day type
                    $overtimePay += $overtimePayAmount;

                    if ($logType === 'regular_workday') {
                        $basicPay += $regularPay; // Only regular hours pay to basic pay
                    } elseif (in_array($logType, ['special_holiday', 'regular_holiday'])) {
                        $holidayPay += $regularPay; // Only regular hours pay to holiday pay
                    } elseif (in_array($logType, ['rest_day_regular_holiday', 'rest_day_special_holiday'])) {
                        $holidayPay += $regularPay; // Rest day holidays count as holiday pay
                    } elseif ($logType === 'rest_day') {
                        $restDayPay += $regularPay; // Only regular hours pay to rest day pay
                    }
                }

                $payBreakdownByEmployee[$detail->employee_id] = [
                    'basic_pay' => $basicPay,
                    'holiday_pay' => $holidayPay,
                    'rest_day_pay' => $restDayPay,
                    'overtime_pay' => $overtimePay,
                ];
            }
        }

        $totalHolidayPay = array_sum(array_column($payBreakdownByEmployee, 'holiday_pay'));
        $totalRestDayPay = array_sum(array_column($payBreakdownByEmployee, 'rest_day_pay'));
        $totalOvertimePay = array_sum(array_column($payBreakdownByEmployee, 'overtime_pay'));

        // Check if payroll has snapshots (processing/approved status)
        $snapshots = $payroll->snapshots()->get();
        if ($snapshots->isNotEmpty()) {
            // For processing/approved payrolls, use snapshot data for breakdowns
            foreach ($payroll->payrollDetails as $detail) {
                $snapshot = $snapshots->where('employee_id', $detail->employee_id)->first();
                if ($snapshot) {
                    // Set breakdown data from snapshots
                    $detail->earnings_breakdown = json_encode([
                        'allowances' => $snapshot->allowances_breakdown ?? []
                    ]);
                    $detail->deductions_breakdown = json_encode([
                        'deductions' => $snapshot->deductions_breakdown ?? []
                    ]);
                }
            }
        } else {
            // For draft payrolls, calculate allowance breakdowns dynamically
            foreach ($payroll->payrollDetails as $detail) {
                // Calculate allowances breakdown dynamically
                $allowancesData = $this->calculateAllowances($detail->employee, $detail->basic_salary, $detail->days_worked, $detail->regular_hours);

                $detail->earnings_breakdown = json_encode([
                    'allowances' => $allowancesData['breakdown'] ?? []
                ]);
            }
        }

        return view('payrolls.show', compact(
            'payroll',
            'dtrData',
            'periodDates',
            'allowanceSettings',
            'deductionSettings',
            'timeBreakdowns',
            'payBreakdownByEmployee',
            'totalHolidayPay',
            'totalRestDayPay',
            'totalOvertimePay'
        ) + [
            'isDynamic' => false
        ] + $additionalData);
    }

    // ===== UNIFIED AUTOMATION PAYROLL METHODS =====

    /**
     * Show unified payroll view (handles draft, processing, approved statuses)
     */
    public function showUnifiedPayroll(Request $request, $schedule, $employee)
    {
        $this->authorize('view payrolls');

        $employee = Employee::findOrFail($employee);
        $selectedSchedule = \App\Models\PayScheduleSetting::systemDefaults()
            ->where('code', $schedule)
            ->first();

        if (!$selectedSchedule) {
            return redirect()->route('payrolls.automation.index')
                ->with('error', 'Invalid pay schedule selected.');
        }

        $currentPeriod = $this->calculateCurrentPayPeriod($selectedSchedule);

        // Check if a saved payroll exists for this employee and period
        $existingPayroll = Payroll::whereHas('payrollDetails', function ($query) use ($employee) {
            $query->where('employee_id', $employee->id);
        })
            ->where('pay_schedule', $schedule)
            ->where('period_start', $currentPeriod['start'])
            ->where('period_end', $currentPeriod['end'])
            ->where('payroll_type', 'automated')
            ->first();

        if ($existingPayroll) {
            // Show saved payroll (processing or approved)
            return $this->showSavedPayroll($existingPayroll, $schedule, $employee->id);
        } else {
            // Show draft payroll (dynamic calculations)
            return $this->showDraftPayrollUnified($schedule, $employee, $currentPeriod);
        }
    }

    /**
     * Process unified payroll (draft to processing)
     */
    public function processUnifiedPayroll(Request $request, $schedule, $employee)
    {
        $this->authorize('create payrolls');

        $employee = Employee::findOrFail($employee);
        $selectedSchedule = \App\Models\PayScheduleSetting::systemDefaults()
            ->where('code', $schedule)
            ->first();

        if (!$selectedSchedule) {
            return redirect()->route('payrolls.automation.index')
                ->with('error', 'Invalid pay schedule selected.');
        }

        $currentPeriod = $this->calculateCurrentPayPeriod($selectedSchedule);

        // Check if payroll already exists
        $existingPayroll = Payroll::whereHas('payrollDetails', function ($query) use ($employee) {
            $query->where('employee_id', $employee->id);
        })
            ->where('pay_schedule', $schedule)
            ->where('period_start', $currentPeriod['start'])
            ->where('period_end', $currentPeriod['end'])
            ->where('payroll_type', 'automated')
            ->first();

        if ($existingPayroll) {
            return redirect()->route('payrolls.automation.show', [
                'schedule' => $schedule,
                'employee' => $employee->id
            ])->with('info', 'This payroll is already processed.');
        }

        try {
            // Create payroll with snapshots
            $employees = collect([$employee]);
            $createdPayroll = $this->autoCreatePayrollForPeriod($selectedSchedule, $currentPeriod, $employees, 'processing');

            return redirect()->route('payrolls.automation.show', [
                'schedule' => $schedule,
                'employee' => $employee->id
            ])->with('success', 'Payroll processed and saved to database with data snapshot.');
        } catch (\Exception $e) {
            Log::error('Failed to process unified payroll: ' . $e->getMessage());
            return redirect()->route('payrolls.automation.show', [
                'schedule' => $schedule,
                'employee' => $employee->id
            ])->with('error', 'Failed to process payroll: ' . $e->getMessage());
        }
    }

    /**
     * Back to draft from unified payroll
     */
    public function backToUnifiedDraft(Request $request, $schedule, $employee)
    {
        $this->authorize('edit payrolls');

        $employee = Employee::findOrFail($employee);
        $selectedSchedule = \App\Models\PayScheduleSetting::systemDefaults()
            ->where('code', $schedule)
            ->first();

        if (!$selectedSchedule) {
            return redirect()->route('payrolls.automation.index')
                ->with('error', 'Invalid pay schedule selected.');
        }

        $currentPeriod = $this->calculateCurrentPayPeriod($selectedSchedule);

        try {
            DB::beginTransaction();

            // Delete payroll and related data
            $payrolls = Payroll::whereHas('payrollDetails', function ($query) use ($employee) {
                $query->where('employee_id', $employee->id);
            })
                ->where('pay_schedule', $schedule)
                ->where('period_start', $currentPeriod['start'])
                ->where('period_end', $currentPeriod['end'])
                ->where('payroll_type', 'automated')
                ->get();

            foreach ($payrolls as $payroll) {
                // Delete snapshots
                $payroll->snapshots()->delete();
                // Delete payroll details
                $payroll->payrollDetails()->delete();
                // Delete payroll
                $payroll->delete();
            }

            DB::commit();

            return redirect()->route('payrolls.automation.show', [
                'schedule' => $schedule,
                'employee' => $employee->id
            ])->with('success', 'Successfully deleted saved payroll. Returned to draft mode.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to return unified payroll to draft: ' . $e->getMessage());
            return redirect()->route('payrolls.automation.show', [
                'schedule' => $schedule,
                'employee' => $employee->id
            ])->with('error', 'Failed to return to draft: ' . $e->getMessage());
        }
    }

    /**
     * Approve unified payroll
     */
    public function approveUnifiedPayroll(Request $request, $schedule, $employee)
    {
        $this->authorize('approve payrolls');

        $employee = Employee::findOrFail($employee);
        $selectedSchedule = \App\Models\PayScheduleSetting::systemDefaults()
            ->where('code', $schedule)
            ->first();

        if (!$selectedSchedule) {
            return redirect()->route('payrolls.automation.index')
                ->with('error', 'Invalid pay schedule selected.');
        }

        $currentPeriod = $this->calculateCurrentPayPeriod($selectedSchedule);

        // Find existing payroll
        $payroll = Payroll::whereHas('payrollDetails', function ($query) use ($employee) {
            $query->where('employee_id', $employee->id);
        })
            ->where('pay_schedule', $schedule)
            ->where('period_start', $currentPeriod['start'])
            ->where('period_end', $currentPeriod['end'])
            ->where('payroll_type', 'automated')
            ->first();

        if (!$payroll) {
            return redirect()->route('payrolls.automation.show', [
                'schedule' => $schedule,
                'employee' => $employee->id
            ])->with('error', 'No payroll found to approve.');
        }

        try {
            $payroll->status = 'approved';
            $payroll->approved_by = Auth::id();
            $payroll->approved_at = now();
            $payroll->save();

            return redirect()->route('payrolls.automation.show', [
                'schedule' => $schedule,
                'employee' => $employee->id
            ])->with('success', 'Payroll approved successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to approve unified payroll: ' . $e->getMessage());
            return redirect()->route('payrolls.automation.show', [
                'schedule' => $schedule,
                'employee' => $employee->id
            ])->with('error', 'Failed to approve payroll: ' . $e->getMessage());
        }
    }

    /**
     * Show draft payroll for unified view
     */
    private function showDraftPayrollUnified($schedule, $employee, $currentPeriod)
    {
        $payrollCalculation = $this->calculateEmployeePayrollForPeriod($employee, $currentPeriod['start'], $currentPeriod['end']);

        // Log what we're getting
        Log::info('Draft Payroll Calculation for Employee ' . $employee->id, $payrollCalculation);

        // Create mock payroll for display
        $draftPayroll = new Payroll();
        $draftPayroll->id = $employee->id;
        $draftPayroll->payroll_number = 'DRAFT-' . $employee->employee_number;
        $draftPayroll->period_start = $currentPeriod['start'];
        $draftPayroll->period_end = $currentPeriod['end'];
        $draftPayroll->pay_date = $currentPeriod['pay_date'];
        $draftPayroll->status = 'draft';
        $draftPayroll->payroll_type = 'automated';
        $draftPayroll->total_gross = $payrollCalculation['gross_pay'] ?? 0;
        $draftPayroll->total_deductions = $payrollCalculation['total_deductions'] ?? 0;
        $draftPayroll->total_net = $payrollCalculation['net_pay'] ?? 0;
        $draftPayroll->created_at = now();

        // Set relationships
        $fakeCreator = (object) ['name' => 'System (Draft)', 'id' => 0];
        $draftPayroll->setRelation('creator', $fakeCreator);
        $draftPayroll->setRelation('approver', null);

        // Create mock payroll detail
        $draftPayrollDetail = new PayrollDetail();
        $draftPayrollDetail->employee_id = $employee->id;
        $draftPayrollDetail->basic_salary = $employee->basic_salary ?? 0;
        $draftPayrollDetail->daily_rate = $employee->daily_rate ?? 0;
        $draftPayrollDetail->hourly_rate = $employee->hourly_rate ?? 0;
        $draftPayrollDetail->days_worked = $payrollCalculation['days_worked'] ?? 0;
        $draftPayrollDetail->regular_hours = $payrollCalculation['regular_hours'] ?? 0;
        $draftPayrollDetail->overtime_hours = $payrollCalculation['overtime_hours'] ?? 0;
        $draftPayrollDetail->holiday_hours = $payrollCalculation['holiday_hours'] ?? 0;
        $draftPayrollDetail->rest_day_hours = $payrollCalculation['rest_day_hours'] ?? 0;
        $draftPayrollDetail->regular_pay = $payrollCalculation['regular_pay'] ?? 0;
        $draftPayrollDetail->overtime_pay = $payrollCalculation['overtime_pay'] ?? 0;
        $draftPayrollDetail->holiday_pay = $payrollCalculation['holiday_pay'] ?? 0;
        $draftPayrollDetail->rest_day_pay = $payrollCalculation['rest_day_pay'] ?? 0;
        $draftPayrollDetail->allowances = $payrollCalculation['allowances'] ?? 0;
        $draftPayrollDetail->bonuses = $payrollCalculation['bonuses'] ?? 0;
        $draftPayrollDetail->gross_pay = $payrollCalculation['gross_pay'] ?? 0;
        $draftPayrollDetail->sss_contribution = $payrollCalculation['sss_deduction'] ?? 0;
        $draftPayrollDetail->philhealth_contribution = $payrollCalculation['philhealth_deduction'] ?? 0;
        $draftPayrollDetail->pagibig_contribution = $payrollCalculation['pagibig_deduction'] ?? 0;
        $draftPayrollDetail->withholding_tax = $payrollCalculation['tax_deduction'] ?? 0;
        $draftPayrollDetail->late_deductions = $payrollCalculation['late_deductions'] ?? 0;
        $draftPayrollDetail->undertime_deductions = $payrollCalculation['undertime_deductions'] ?? 0;
        $draftPayrollDetail->cash_advance_deductions = $payrollCalculation['cash_advance_deductions'] ?? 0;
        $draftPayrollDetail->other_deductions = $payrollCalculation['other_deductions'] ?? 0;
        $draftPayrollDetail->total_deductions = $payrollCalculation['total_deductions'] ?? 0;
        $draftPayrollDetail->net_pay = $payrollCalculation['net_pay'] ?? 0;

        // Set earnings and deduction breakdowns
        if (isset($payrollCalculation['allowances_details'])) {
            $draftPayrollDetail->earnings_breakdown = json_encode([
                'allowances' => $payrollCalculation['allowances_details']
            ]);
        }
        if (isset($payrollCalculation['deductions_details'])) {
            $draftPayrollDetail->deduction_breakdown = json_encode($payrollCalculation['deductions_details']);
        }

        // Set employee relationship
        $draftPayrollDetail->setRelation('employee', $employee->load(['user', 'department', 'position', 'daySchedule', 'timeSchedule']));

        // Set payroll details collection
        $draftPayroll->setRelation('payrollDetails', collect([$draftPayrollDetail]));

        // Create period dates array
        $startDate = \Carbon\Carbon::parse($currentPeriod['start']);
        $endDate = \Carbon\Carbon::parse($currentPeriod['end']);
        $periodDates = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $periodDates[] = $date->format('Y-m-d');
        }

        // Get DTR data
        $timeLogs = TimeLog::where('employee_id', $employee->id)
            ->whereBetween('log_date', [$currentPeriod['start'], $currentPeriod['end']])
            ->orderBy('log_date')
            ->get()
            ->groupBy(function ($item) {
                return \Carbon\Carbon::parse($item->log_date)->format('Y-m-d');
            });

        // Build DTR data structure matching working version
        $employeeDtr = [];
        foreach ($periodDates as $date) {
            $timeLog = $timeLogs->get($date, collect())->first();

            // For draft payrolls, add dynamic calculation to time log object for DTR display
            if ($timeLog && $timeLog->time_in && $timeLog->time_out && $timeLog->remarks !== 'Incomplete Time Record') {
                $dynamicCalculation = $this->calculateTimeLogHoursDynamically($timeLog);
                $timeLog->dynamic_regular_hours = $dynamicCalculation['regular_hours'];
                $timeLog->dynamic_overtime_hours = $dynamicCalculation['overtime_hours'];
                $timeLog->dynamic_regular_overtime_hours = $dynamicCalculation['regular_overtime_hours'] ?? 0;
                $timeLog->dynamic_night_diff_overtime_hours = $dynamicCalculation['night_diff_overtime_hours'] ?? 0;
                $timeLog->dynamic_total_hours = $dynamicCalculation['total_hours'];
            }

            $employeeDtr[$date] = $timeLog;
        }
        $dtrData = [$employee->id => $employeeDtr];

        // Create time breakdowns similar to regular show method
        $timeBreakdowns = [$employee->id => []];
        $employeeBreakdown = [];

        foreach ($periodDates as $date) {
            $timeLog = $timeLogs->get($date, collect())->first();
            if ($timeLog && !($timeLog->remarks === 'Incomplete Time Record' || (!$timeLog->time_in || !$timeLog->time_out))) {
                $logType = $timeLog->log_type;
                if (!isset($employeeBreakdown[$logType])) {
                    $employeeBreakdown[$logType] = [
                        'regular_hours' => 0,
                        'overtime_hours' => 0,
                        'regular_overtime_hours' => 0,
                        'night_diff_overtime_hours' => 0,
                        'night_diff_regular_hours' => 0, // ADD: Missing night differential regular hours
                        'total_hours' => 0,
                        'days_count' => 0,
                        'display_name' => '',
                        'rate_config' => null
                    ];
                }

                // Use dynamic calculation for draft payroll
                $dynamicCalculation = $this->calculateTimeLogHoursDynamically($timeLog);
                $regularHours = $dynamicCalculation['regular_hours'];
                $overtimeHours = $dynamicCalculation['overtime_hours'];
                $regularOvertimeHours = $dynamicCalculation['regular_overtime_hours'] ?? 0;
                $nightDiffOvertimeHours = $dynamicCalculation['night_diff_overtime_hours'] ?? 0;
                $nightDiffRegularHours = $dynamicCalculation['night_diff_regular_hours'] ?? 0; // ADD: Extract night diff regular hours
                $totalHours = $dynamicCalculation['total_hours'];

                $employeeBreakdown[$logType]['regular_hours'] += $regularHours;
                $employeeBreakdown[$logType]['overtime_hours'] += $overtimeHours;
                $employeeBreakdown[$logType]['regular_overtime_hours'] += $regularOvertimeHours;
                $employeeBreakdown[$logType]['night_diff_overtime_hours'] += $nightDiffOvertimeHours;
                $employeeBreakdown[$logType]['night_diff_regular_hours'] += $nightDiffRegularHours; // ADD: Store night diff regular hours
                $employeeBreakdown[$logType]['total_hours'] += $totalHours;
                $employeeBreakdown[$logType]['days_count']++;

                // Get rate configuration for this type
                $rateConfig = $timeLog->getRateConfiguration();
                if ($rateConfig) {
                    $employeeBreakdown[$logType]['display_name'] = $rateConfig->display_name;
                    $employeeBreakdown[$logType]['rate_config'] = $rateConfig;
                }
            }
        }

        $timeBreakdowns[$employee->id] = $employeeBreakdown;

        // Calculate pay breakdown by employee
        $hourlyRate = $employee->hourly_rate ?? 0;
        $basicPay = 0;
        $holidayPay = 0;
        $restDayPay = 0;
        $overtimePay = 0;

        foreach ($employeeBreakdown as $logType => $breakdown) {
            $rateConfig = $breakdown['rate_config'];
            if (!$rateConfig) continue;

            $regularMultiplier = $rateConfig->regular_rate_multiplier ?? 1.0;
            $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 1.25;

            $regularPay = $breakdown['regular_hours'] * $hourlyRate * $regularMultiplier;

            // Calculate overtime pay with night differential breakdown
            $overtimePayAmount = 0;
            $regularOvertimeHours = $breakdown['regular_overtime_hours'] ?? 0;
            $nightDiffOvertimeHours = $breakdown['night_diff_overtime_hours'] ?? 0;

            if ($regularOvertimeHours > 0 || $nightDiffOvertimeHours > 0) {
                // Use breakdown calculation

                // Regular overtime pay
                if ($regularOvertimeHours > 0) {
                    $overtimePayAmount += $regularOvertimeHours * $hourlyRate * $overtimeMultiplier;
                }

                // Night differential overtime pay (overtime rate + night differential bonus)
                if ($nightDiffOvertimeHours > 0) {
                    // Get night differential setting
                    $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
                    $nightDiffMultiplier = $nightDiffSetting ? $nightDiffSetting->rate_multiplier : 1.10;

                    // Combined rate: base overtime rate + night differential bonus
                    $combinedMultiplier = $overtimeMultiplier + ($nightDiffMultiplier - 1);
                    $overtimePayAmount += $nightDiffOvertimeHours * $hourlyRate * $combinedMultiplier;
                }
            } else {
                // Fallback to simple calculation if no breakdown available
                $overtimePayAmount = $breakdown['overtime_hours'] * $hourlyRate * $overtimeMultiplier;
            }

            // All overtime goes to overtime column regardless of day type
            $overtimePay += $overtimePayAmount;

            if ($logType === 'regular_workday') {
                $basicPay += $regularPay; // Only regular hours pay to basic pay
            } elseif (in_array($logType, ['special_holiday', 'regular_holiday'])) {
                $holidayPay += $regularPay; // Only regular hours pay to holiday pay
            } elseif (in_array($logType, ['rest_day_regular_holiday', 'rest_day_special_holiday'])) {
                $holidayPay += $regularPay; // Rest day holidays count as holiday pay
            } elseif ($logType === 'rest_day') {
                $restDayPay += $regularPay; // Only regular hours pay to rest day pay
            }
        }

        $payBreakdownByEmployee = [
            $employee->id => [
                'basic_pay' => $basicPay,
                'holiday_pay' => $holidayPay,
                'rest_day_pay' => $restDayPay,
                'overtime_pay' => $overtimePay,
            ]
        ];

        // Get settings
        $allowanceSettings = \App\Models\AllowanceBonusSetting::where('is_active', true)
            ->where('type', 'allowance')
            ->orderBy('sort_order')
            ->get();
        $bonusSettings = \App\Models\AllowanceBonusSetting::where('is_active', true)
            ->where('type', 'bonus')
            ->orderBy('sort_order')
            ->get();
        $deductionSettings = \App\Models\DeductionTaxSetting::active()
            ->orderBy('sort_order')
            ->get();

        $totalHolidayPay = $holidayPay;
        $totalRestDayPay = $restDayPay;
        $totalOvertimePay = $overtimePay;

        return view('payrolls.show', compact(
            'draftPayroll',
            'dtrData',
            'periodDates',
            'allowanceSettings',
            'bonusSettings',
            'deductionSettings',
            'timeBreakdowns',
            'payBreakdownByEmployee',
            'totalHolidayPay',
            'totalRestDayPay',
            'totalOvertimePay'
        ) + [
            'payroll' => $draftPayroll,
            'isDraft' => true,
            'isDynamic' => true,
            'schedule' => $schedule,
            'employee' => $employee->id
        ]);
    }

    /**
     * Show saved payroll for unified view
     */
    private function showSavedPayroll($payroll, $schedule, $employeeId)
    {
        return $this->showPayrollWithAdditionalData($payroll, [
            'schedule' => $schedule,
            'employee' => $employeeId
        ]);
    }

    /**
     * Link existing time logs to a payroll based on period and employee
     */

    /**
     * Calculate time log hours dynamically using current grace periods and employee schedules
     * This is used for draft payrolls to get real-time calculations
     */
    private function calculateTimeLogHoursDynamically(TimeLog $timeLog)
    {
        // Use the same dynamic calculation method from TimeLogController
        $timeLogController = app(\App\Http\Controllers\TimeLogController::class);

        // Use reflection to access the private method
        $reflection = new \ReflectionClass($timeLogController);
        $method = $reflection->getMethod('calculateDynamicWorkingHours');
        $method->setAccessible(true);

        try {
            return $method->invoke($timeLogController, $timeLog);
        } catch (\Exception $e) {
            // Fallback to stored values if calculation fails
            Log::error('Dynamic time log calculation failed: ' . $e->getMessage());

            return [
                'total_hours' => $timeLog->total_hours ?? 0,
                'regular_hours' => $timeLog->regular_hours ?? 0,
                'night_diff_regular_hours' => $timeLog->night_diff_regular_hours ?? 0,
                'overtime_hours' => $timeLog->overtime_hours ?? 0,
                'regular_overtime_hours' => $timeLog->regular_overtime_hours ?? 0,
                'night_diff_overtime_hours' => $timeLog->night_diff_overtime_hours ?? 0,
                'late_hours' => $timeLog->late_hours ?? 0,
                'undertime_hours' => $timeLog->undertime_hours ?? 0,
            ];
        }
    }

    /**
     * Calculate late deductions based on late hours
     */
    private function calculateLateDeductions($employee, $lateHours)
    {
        if ($lateHours <= 0) {
            return 0;
        }

        // Calculate deduction based on hourly rate
        $hourlyRate = $employee->hourly_rate ?? 0;
        return $lateHours * $hourlyRate;
    }

    /**
     * Calculate undertime deductions based on undertime hours
     */
    private function calculateUndertimeDeductions($employee, $undertimeHours)
    {
        if ($undertimeHours <= 0) {
            return 0;
        }

        // Calculate deduction based on hourly rate
        $hourlyRate = $employee->hourly_rate ?? 0;
        return $undertimeHours * $hourlyRate;
    }

    /**
     * Create Basic Pay breakdown for snapshot
     */
    private function createBasicPayBreakdown($timeBreakdown, $employee)
    {
        $breakdown = [];
        $hourlyRate = $employee->hourly_rate ?? 0;

        // Only include regular workday breakdown for basic pay
        if (isset($timeBreakdown['regular_workday'])) {
            $regularData = $timeBreakdown['regular_workday'];
            $regularHours = $regularData['regular_hours'];
            $nightDiffRegularHours = $regularData['night_diff_regular_hours'] ?? 0;

            // Regular Workday (without night differential)
            if ($regularHours > 0) {
                // Calculate per-minute amount with rounding (same as draft payroll)
                $actualMinutes = $regularHours * 60;
                $roundedMinutes = round($actualMinutes);
                $ratePerMinute = $hourlyRate / 60;
                $amount = $roundedMinutes * $ratePerMinute;

                $breakdown['Regular Workday'] = [
                    'hours' => $regularHours,
                    'rate' => $hourlyRate,
                    'multiplier' => 1.0,
                    'amount' => $amount
                ];
            }

            // Regular Workday + Night Differential
            if ($nightDiffRegularHours > 0) {
                // Get night differential settings for rate calculation
                $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
                $nightDiffMultiplier = $nightDiffSetting ? $nightDiffSetting->rate_multiplier : 1.10;

                // Calculate per-minute amount with rounding (same as draft payroll)
                $actualMinutes = $nightDiffRegularHours * 60;
                $roundedMinutes = round($actualMinutes);
                $ratePerMinute = ($hourlyRate * $nightDiffMultiplier) / 60;
                $amount = $roundedMinutes * $ratePerMinute;

                $breakdown['Regular Workday+ND'] = [
                    'hours' => $nightDiffRegularHours,
                    'rate' => $hourlyRate,
                    'multiplier' => $nightDiffMultiplier,
                    'amount' => $amount
                ];
            }
        }

        return $breakdown;
    }

    /**
     * Create Holiday Pay breakdown for snapshot
     */
    private function createHolidayPayBreakdown($timeBreakdown, $employee)
    {
        $breakdown = [];
        $hourlyRate = $employee->hourly_rate ?? 0;

        // Get dynamic rate configurations from database settings (same as draft payroll)
        // Order matches the expected display order: Regular Holiday first, then Special Holiday
        $holidayTypes = [
            'regular_holiday' => 'Regular Holiday',
            'special_holiday' => 'Special Holiday',
            'rest_day_regular_holiday' => 'Rest Day Regular Holiday',
            'rest_day_special_holiday' => 'Rest Day Special Holiday'
        ];

        foreach ($holidayTypes as $type => $name) {
            if (isset($timeBreakdown[$type])) {
                $data = $timeBreakdown[$type];
                $regularHours = $data['regular_hours']; // Regular hours for holiday pay
                $nightDiffRegularHours = $data['night_diff_regular_hours'] ?? 0; // Night differential hours for holiday pay

                // Get rate config from the time breakdown (same as draft calculation)
                $rateConfig = $data['rate_config'] ?? null;

                // If rate config is not available, fetch from database as fallback
                if (!$rateConfig) {
                    $rateConfig = \App\Models\PayrollRateConfiguration::where('type_name', $type)
                        ->where('is_active', true)
                        ->first();
                }

                if ($rateConfig) {
                    $multiplier = $rateConfig->regular_rate_multiplier ?? 1.0;

                    // Regular holiday hours (without ND)
                    if ($regularHours > 0) {
                        // Calculate per-minute amount with rounding (same as draft payroll)
                        $actualMinutes = $regularHours * 60;
                        $roundedMinutes = round($actualMinutes);
                        $ratePerMinute = ($hourlyRate * $multiplier) / 60;
                        $amount = $roundedMinutes * $ratePerMinute;

                        $breakdown[$name] = [
                            'hours' => $regularHours,
                            'rate' => number_format($hourlyRate, 2),
                            'multiplier' => $multiplier,
                            'amount' => $amount
                        ];
                    }

                    // Holiday hours + Night Differential
                    if ($nightDiffRegularHours > 0) {
                        // Get night differential settings for rate calculation
                        $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
                        $nightDiffMultiplier = $nightDiffSetting ? $nightDiffSetting->rate_multiplier : 1.10;

                        // Combined rate: holiday rate + night differential bonus
                        $combinedMultiplier = $multiplier + ($nightDiffMultiplier - 1);

                        // Calculate per-minute amount with rounding (same as draft payroll)
                        $actualMinutes = $nightDiffRegularHours * 60;
                        $roundedMinutes = round($actualMinutes);
                        $ratePerMinute = ($hourlyRate * $combinedMultiplier) / 60;
                        $amount = $roundedMinutes * $ratePerMinute;

                        $breakdown[$name . '+ND'] = [
                            'hours' => $nightDiffRegularHours,
                            'rate' => number_format($hourlyRate, 2),
                            'multiplier' => $combinedMultiplier,
                            'amount' => $amount
                        ];
                    }
                } else {
                    // Ultimate fallback to hardcoded multipliers if no config found
                    $fallbackMultipliers = [
                        'special_holiday' => 1.3,
                        'regular_holiday' => 2.0,
                        'rest_day_special_holiday' => 1.5,
                        'rest_day_regular_holiday' => 2.6
                    ];
                    $multiplier = $fallbackMultipliers[$type] ?? 1.0;

                    // Regular holiday hours (without ND)
                    if ($regularHours > 0) {
                        // Calculate per-minute amount with rounding (same as draft payroll)
                        $actualMinutes = $regularHours * 60;
                        $roundedMinutes = round($actualMinutes);
                        $ratePerMinute = ($hourlyRate * $multiplier) / 60;
                        $amount = $roundedMinutes * $ratePerMinute;

                        $breakdown[$name] = [
                            'hours' => $regularHours,
                            'rate' => number_format($hourlyRate, 2),
                            'multiplier' => $multiplier,
                            'amount' => $amount
                        ];
                    }

                    // Holiday hours + Night Differential
                    if ($nightDiffRegularHours > 0) {
                        // Combined rate: holiday rate + night differential bonus (10%)
                        $combinedMultiplier = $multiplier + 0.10;

                        // Calculate per-minute amount with rounding (same as draft payroll)
                        $actualMinutes = $nightDiffRegularHours * 60;
                        $roundedMinutes = round($actualMinutes);
                        $ratePerMinute = ($hourlyRate * $combinedMultiplier) / 60;
                        $amount = $roundedMinutes * $ratePerMinute;

                        $breakdown[$name . '+ND'] = [
                            'hours' => $nightDiffRegularHours,
                            'rate' => number_format($hourlyRate, 2),
                            'multiplier' => $combinedMultiplier,
                            'amount' => $amount
                        ];
                    }
                }
            }
        }

        return $breakdown;
    }

    /**
     * Create Rest Pay breakdown for snapshot
     */
    private function createRestPayBreakdown($timeBreakdown, $employee)
    {
        $breakdown = [];
        $hourlyRate = $employee->hourly_rate ?? 0;

        // Only include rest day breakdown
        if (isset($timeBreakdown['rest_day'])) {
            $restData = $timeBreakdown['rest_day'];
            $regularHours = $restData['regular_hours']; // Regular hours for rest day pay
            $nightDiffRegularHours = $restData['night_diff_regular_hours'] ?? 0; // Night differential hours for rest day pay

            // Get rate config from the time breakdown (same as draft calculation)
            $rateConfig = $restData['rate_config'] ?? null;

            // If rate config is not available, fetch from database as fallback
            if (!$rateConfig) {
                $rateConfig = \App\Models\PayrollRateConfiguration::where('type_name', 'rest_day')
                    ->where('is_active', true)
                    ->first();
            }

            if ($rateConfig) {
                $multiplier = $rateConfig->regular_rate_multiplier ?? 1.0;

                // Regular rest day hours (without ND)
                if ($regularHours > 0) {
                    // Calculate per-minute amount with rounding (same as draft payroll)
                    $actualMinutes = $regularHours * 60;
                    $roundedMinutes = round($actualMinutes);
                    $ratePerMinute = ($hourlyRate * $multiplier) / 60;
                    $amount = $roundedMinutes * $ratePerMinute;

                    $breakdown['Rest Day'] = [
                        'hours' => $regularHours,
                        'rate' => number_format($hourlyRate, 2),
                        'multiplier' => $multiplier,
                        'amount' => $amount
                    ];
                }

                // Rest day hours + Night Differential
                if ($nightDiffRegularHours > 0) {
                    // Get night differential settings for rate calculation
                    $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
                    $nightDiffMultiplier = $nightDiffSetting ? $nightDiffSetting->rate_multiplier : 1.10;

                    // Combined rate: rest day rate + night differential bonus
                    $combinedMultiplier = $multiplier + ($nightDiffMultiplier - 1);

                    // Calculate per-minute amount with rounding (same as draft payroll)
                    $actualMinutes = $nightDiffRegularHours * 60;
                    $roundedMinutes = round($actualMinutes);
                    $ratePerMinute = ($hourlyRate * $combinedMultiplier) / 60;
                    $amount = $roundedMinutes * $ratePerMinute;

                    $breakdown['Rest Day+ND'] = [
                        'hours' => $nightDiffRegularHours,
                        'rate' => number_format($hourlyRate, 2),
                        'multiplier' => $combinedMultiplier,
                        'amount' => $amount
                    ];
                }
            } else {
                // Ultimate fallback to hardcoded multiplier if no config found

                // Regular rest day hours (without ND)
                if ($regularHours > 0) {
                    // Calculate per-minute amount with rounding (same as draft payroll)
                    $actualMinutes = $regularHours * 60;
                    $roundedMinutes = round($actualMinutes);
                    $ratePerMinute = ($hourlyRate * 1.3) / 60;
                    $amount = $roundedMinutes * $ratePerMinute;

                    $breakdown['Rest Day'] = [
                        'hours' => $regularHours,
                        'rate' => number_format($hourlyRate, 2),
                        'multiplier' => 1.3,
                        'amount' => $amount
                    ];
                }

                // Rest day hours + Night Differential
                if ($nightDiffRegularHours > 0) {
                    // Combined rate: rest day rate + night differential bonus (10%)
                    $combinedMultiplier = 1.3 + 0.10; // 1.4

                    // Calculate per-minute amount with rounding (same as draft payroll)
                    $actualMinutes = $nightDiffRegularHours * 60;
                    $roundedMinutes = round($actualMinutes);
                    $ratePerMinute = ($hourlyRate * $combinedMultiplier) / 60;
                    $amount = $roundedMinutes * $ratePerMinute;

                    $breakdown['Rest Day+ND'] = [
                        'hours' => $nightDiffRegularHours,
                        'rate' => number_format($hourlyRate, 2),
                        'multiplier' => $combinedMultiplier,
                        'amount' => $amount
                    ];
                }
            }
        }

        return $breakdown;
    }

    /**
     * Create Overtime Pay breakdown for snapshot
     */
    private function createOvertimePayBreakdown($timeBreakdown, $employee)
    {
        $breakdown = [];
        $hourlyRate = $employee->hourly_rate ?? 0;

        // Regular workday overtime - SPLIT into regular OT and OT+ND
        if (isset($timeBreakdown['regular_workday'])) {
            $regularData = $timeBreakdown['regular_workday'];
            $regularOvertimeHours = $regularData['regular_overtime_hours'] ?? 0;
            $nightDiffOvertimeHours = $regularData['night_diff_overtime_hours'] ?? 0;
            // Get rate config from the time breakdown (same as draft calculation)
            $rateConfig = $regularData['rate_config'] ?? null;

            // If rate config is not available, fetch from database as fallback
            if (!$rateConfig) {
                $rateConfig = \App\Models\PayrollRateConfiguration::where('type_name', 'regular_workday')
                    ->where('is_active', true)
                    ->first();
            }

            if ($rateConfig) {
                $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 1.25;

                // Get night differential settings for dynamic rate
                $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
                $nightDiffMultiplier = $nightDiffSetting ? $nightDiffSetting->rate_multiplier : 1.10;

                // Regular Workday OT (without ND)
                if ($regularOvertimeHours > 0) {
                    // Calculate per-minute amount with rounding (same as draft payroll)
                    $actualMinutes = $regularOvertimeHours * 60;
                    $roundedMinutes = round($actualMinutes);
                    $ratePerMinute = ($hourlyRate * $overtimeMultiplier) / 60;
                    $amount = $roundedMinutes * $ratePerMinute;

                    $breakdown['Regular Workday OT'] = [
                        'hours' => $regularOvertimeHours,
                        'rate' => number_format($hourlyRate, 2),
                        'multiplier' => $overtimeMultiplier,
                        'amount' => $amount
                    ];
                }

                // Regular Workday OT + ND
                if ($nightDiffOvertimeHours > 0) {
                    // Combined rate: overtime rate + night differential bonus
                    $combinedMultiplier = $overtimeMultiplier + ($nightDiffMultiplier - 1);
                    // Calculate per-minute amount with rounding (same as draft payroll)
                    $actualMinutes = $nightDiffOvertimeHours * 60;
                    $roundedMinutes = round($actualMinutes);
                    $ratePerMinute = ($hourlyRate * $combinedMultiplier) / 60;
                    $amount = $roundedMinutes * $ratePerMinute;

                    $breakdown['Regular Workday OT+ND'] = [
                        'hours' => $nightDiffOvertimeHours,
                        'rate' => number_format($hourlyRate, 2),
                        'multiplier' => $combinedMultiplier,
                        'amount' => $amount
                    ];
                }
            } else {
                // Ultimate fallback to hardcoded multipliers if no config found
                // Regular Workday OT (without ND)
                if ($regularOvertimeHours > 0) {
                    $actualMinutes = $regularOvertimeHours * 60;
                    $roundedMinutes = round($actualMinutes);
                    $ratePerMinute = ($hourlyRate * 1.25) / 60;
                    $amount = $roundedMinutes * $ratePerMinute;

                    $breakdown['Regular Workday OT'] = [
                        'hours' => $regularOvertimeHours,
                        'rate' => number_format($hourlyRate, 2),
                        'multiplier' => 1.25,
                        'amount' => $amount
                    ];
                }

                // Regular Workday OT + ND
                if ($nightDiffOvertimeHours > 0) {
                    // Combined rate: 1.25 (OT) + 0.10 (ND) = 1.35
                    $combinedMultiplier = 1.25 + 0.10;
                    $actualMinutes = $nightDiffOvertimeHours * 60;
                    $roundedMinutes = round($actualMinutes);
                    $ratePerMinute = ($hourlyRate * $combinedMultiplier) / 60;
                    $amount = $roundedMinutes * $ratePerMinute;

                    $breakdown['Regular Workday OT+ND'] = [
                        'hours' => $nightDiffOvertimeHours,
                        'rate' => number_format($hourlyRate, 2),
                        'multiplier' => $combinedMultiplier,
                        'amount' => $amount
                    ];
                }
            }
        }

        // Special holiday overtime - SPLIT into regular OT and OT+ND
        if (isset($timeBreakdown['special_holiday'])) {
            $specialData = $timeBreakdown['special_holiday'];
            $regularOvertimeHours = $specialData['regular_overtime_hours'] ?? 0;
            $nightDiffOvertimeHours = $specialData['night_diff_overtime_hours'] ?? 0;
            // Get rate config from the time breakdown (same as draft calculation)
            $rateConfig = $specialData['rate_config'] ?? null;

            // If rate config is not available, fetch from database as fallback
            if (!$rateConfig) {
                $rateConfig = \App\Models\PayrollRateConfiguration::where('type_name', 'special_holiday')
                    ->where('is_active', true)
                    ->first();
            }

            if ($rateConfig) {
                $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 1.69;

                // Get night differential settings for dynamic rate
                $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
                $nightDiffMultiplier = $nightDiffSetting ? $nightDiffSetting->rate_multiplier : 1.10;

                // Special Holiday OT (without ND)
                if ($regularOvertimeHours > 0) {
                    $actualMinutes = $regularOvertimeHours * 60;
                    $roundedMinutes = round($actualMinutes);
                    $ratePerMinute = ($hourlyRate * $overtimeMultiplier) / 60;
                    $amount = $roundedMinutes * $ratePerMinute;

                    $breakdown['Special Holiday OT'] = [
                        'hours' => $regularOvertimeHours,
                        'rate' => number_format($hourlyRate, 2),
                        'multiplier' => $overtimeMultiplier,
                        'amount' => $amount
                    ];
                }

                // Special Holiday OT + ND
                if ($nightDiffOvertimeHours > 0) {
                    // Combined rate: overtime rate + night differential bonus
                    $combinedMultiplier = $overtimeMultiplier + ($nightDiffMultiplier - 1);
                    $actualMinutes = $nightDiffOvertimeHours * 60;
                    $roundedMinutes = round($actualMinutes);
                    $ratePerMinute = ($hourlyRate * $combinedMultiplier) / 60;
                    $amount = $roundedMinutes * $ratePerMinute;

                    $breakdown['Special Holiday OT+ND'] = [
                        'hours' => $nightDiffOvertimeHours,
                        'rate' => number_format($hourlyRate, 2),
                        'multiplier' => $combinedMultiplier,
                        'amount' => $amount
                    ];
                }
            } else {
                // Ultimate fallback to hardcoded multipliers if no config found
                // Special Holiday OT (without ND)
                if ($regularOvertimeHours > 0) {
                    $actualMinutes = $regularOvertimeHours * 60;
                    $roundedMinutes = round($actualMinutes);
                    $ratePerMinute = ($hourlyRate * 1.69) / 60;
                    $amount = $roundedMinutes * $ratePerMinute;

                    $breakdown['Special Holiday OT'] = [
                        'hours' => $regularOvertimeHours,
                        'rate' => number_format($hourlyRate, 2),
                        'multiplier' => 1.69,
                        'amount' => $amount
                    ];
                }

                // Special Holiday OT + ND
                if ($nightDiffOvertimeHours > 0) {
                    // Combined rate: 1.69 (OT) + 0.10 (ND) = 1.79
                    $combinedMultiplier = 1.69 + 0.10;
                    $actualMinutes = $nightDiffOvertimeHours * 60;
                    $roundedMinutes = round($actualMinutes);
                    $ratePerMinute = ($hourlyRate * $combinedMultiplier) / 60;
                    $amount = $roundedMinutes * $ratePerMinute;

                    $breakdown['Special Holiday OT+ND'] = [
                        'hours' => $nightDiffOvertimeHours,
                        'rate' => number_format($hourlyRate, 2),
                        'multiplier' => $combinedMultiplier,
                        'amount' => $amount
                    ];
                }
            }
        }

        // Regular holiday overtime - SPLIT into regular OT and OT+ND
        if (isset($timeBreakdown['regular_holiday'])) {
            $regularHolidayData = $timeBreakdown['regular_holiday'];
            $regularOvertimeHours = $regularHolidayData['regular_overtime_hours'] ?? 0;
            $nightDiffOvertimeHours = $regularHolidayData['night_diff_overtime_hours'] ?? 0;
            // Get rate config from the time breakdown (same as draft calculation)
            $rateConfig = $regularHolidayData['rate_config'] ?? null;

            // If rate config is not available, fetch from database as fallback
            if (!$rateConfig) {
                $rateConfig = \App\Models\PayrollRateConfiguration::where('type_name', 'regular_holiday')
                    ->where('is_active', true)
                    ->first();
            }

            if ($rateConfig) {
                $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 2.6;

                // Get night differential settings for dynamic rate
                $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
                $nightDiffMultiplier = $nightDiffSetting ? $nightDiffSetting->rate_multiplier : 1.10;

                // Regular Holiday OT (without ND)
                if ($regularOvertimeHours > 0) {
                    $actualMinutes = $regularOvertimeHours * 60;
                    $roundedMinutes = round($actualMinutes);
                    $ratePerMinute = ($hourlyRate * $overtimeMultiplier) / 60;
                    $amount = $roundedMinutes * $ratePerMinute;

                    $breakdown['Regular Holiday OT'] = [
                        'hours' => $regularOvertimeHours,
                        'rate' => number_format($hourlyRate, 2),
                        'multiplier' => $overtimeMultiplier,
                        'amount' => $amount
                    ];
                }

                // Regular Holiday OT + ND
                if ($nightDiffOvertimeHours > 0) {
                    // Combined rate: overtime rate + night differential bonus
                    $combinedMultiplier = $overtimeMultiplier + ($nightDiffMultiplier - 1);
                    $actualMinutes = $nightDiffOvertimeHours * 60;
                    $roundedMinutes = round($actualMinutes);
                    $ratePerMinute = ($hourlyRate * $combinedMultiplier) / 60;
                    $amount = $roundedMinutes * $ratePerMinute;

                    $breakdown['Regular Holiday OT+ND'] = [
                        'hours' => $nightDiffOvertimeHours,
                        'rate' => number_format($hourlyRate, 2),
                        'multiplier' => $combinedMultiplier,
                        'amount' => $amount
                    ];
                }
            } else {
                // Ultimate fallback to hardcoded multipliers if no config found
                // Regular Holiday OT (without ND)
                if ($regularOvertimeHours > 0) {
                    $actualMinutes = $regularOvertimeHours * 60;
                    $roundedMinutes = round($actualMinutes);
                    $ratePerMinute = ($hourlyRate * 2.6) / 60;
                    $amount = $roundedMinutes * $ratePerMinute;

                    $breakdown['Regular Holiday OT'] = [
                        'hours' => $regularOvertimeHours,
                        'rate' => number_format($hourlyRate, 2),
                        'multiplier' => 2.6,
                        'amount' => $amount
                    ];
                }

                // Regular Holiday OT + ND
                if ($nightDiffOvertimeHours > 0) {
                    // Combined rate: 2.6 (OT) + 0.10 (ND) = 2.7
                    $combinedMultiplier = 2.6 + 0.10;
                    $actualMinutes = $nightDiffOvertimeHours * 60;
                    $roundedMinutes = round($actualMinutes);
                    $ratePerMinute = ($hourlyRate * $combinedMultiplier) / 60;
                    $amount = $roundedMinutes * $ratePerMinute;

                    $breakdown['Regular Holiday OT+ND'] = [
                        'hours' => $nightDiffOvertimeHours,
                        'rate' => number_format($hourlyRate, 2),
                        'multiplier' => $combinedMultiplier,
                        'amount' => $amount
                    ];
                }
            }
        }

        // Rest day overtime - SPLIT into regular OT and OT+ND
        if (isset($timeBreakdown['rest_day'])) {
            $restData = $timeBreakdown['rest_day'];
            $regularOvertimeHours = $restData['regular_overtime_hours'] ?? 0;
            $nightDiffOvertimeHours = $restData['night_diff_overtime_hours'] ?? 0;
            // Get rate config from the time breakdown (same as draft calculation)
            $rateConfig = $restData['rate_config'] ?? null;

            // If rate config is not available, fetch from database as fallback
            if (!$rateConfig) {
                $rateConfig = \App\Models\PayrollRateConfiguration::where('type_name', 'rest_day')
                    ->where('is_active', true)
                    ->first();
            }

            if ($rateConfig) {
                $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 1.69;

                // Get night differential settings for dynamic rate
                $nightDiffSetting = \App\Models\NightDifferentialSetting::current();
                $nightDiffMultiplier = $nightDiffSetting ? $nightDiffSetting->rate_multiplier : 1.10;

                // Rest Day OT (without ND)
                if ($regularOvertimeHours > 0) {
                    $actualMinutes = $regularOvertimeHours * 60;
                    $roundedMinutes = round($actualMinutes);
                    $ratePerMinute = ($hourlyRate * $overtimeMultiplier) / 60;
                    $amount = $roundedMinutes * $ratePerMinute;

                    $breakdown['Rest Day OT'] = [
                        'hours' => $regularOvertimeHours,
                        'rate' => number_format($hourlyRate, 2),
                        'multiplier' => $overtimeMultiplier,
                        'amount' => $amount
                    ];
                }

                // Rest Day OT + ND
                if ($nightDiffOvertimeHours > 0) {
                    // Combined rate: overtime rate + night differential bonus
                    $combinedMultiplier = $overtimeMultiplier + ($nightDiffMultiplier - 1);
                    $actualMinutes = $nightDiffOvertimeHours * 60;
                    $roundedMinutes = round($actualMinutes);
                    $ratePerMinute = ($hourlyRate * $combinedMultiplier) / 60;
                    $amount = $roundedMinutes * $ratePerMinute;

                    $breakdown['Rest Day OT+ND'] = [
                        'hours' => $nightDiffOvertimeHours,
                        'rate' => number_format($hourlyRate, 2),
                        'multiplier' => $combinedMultiplier,
                        'amount' => $amount
                    ];
                }
            } else {
                // Ultimate fallback to hardcoded multipliers if no config found
                // Rest Day OT (without ND)
                if ($regularOvertimeHours > 0) {
                    $actualMinutes = $regularOvertimeHours * 60;
                    $roundedMinutes = round($actualMinutes);
                    $ratePerMinute = ($hourlyRate * 1.69) / 60;
                    $amount = $roundedMinutes * $ratePerMinute;

                    $breakdown['Rest Day OT'] = [
                        'hours' => $regularOvertimeHours,
                        'rate' => number_format($hourlyRate, 2),
                        'multiplier' => 1.69,
                        'amount' => $amount
                    ];
                }

                // Rest Day OT + ND
                if ($nightDiffOvertimeHours > 0) {
                    // Combined rate: 1.69 (OT) + 0.10 (ND) = 1.79
                    $combinedMultiplier = 1.69 + 0.10;
                    $actualMinutes = $nightDiffOvertimeHours * 60;
                    $roundedMinutes = round($actualMinutes);
                    $ratePerMinute = ($hourlyRate * $combinedMultiplier) / 60;
                    $amount = $roundedMinutes * $ratePerMinute;

                    $breakdown['Rest Day OT+ND'] = [
                        'hours' => $nightDiffOvertimeHours,
                        'rate' => number_format($hourlyRate, 2),
                        'multiplier' => $combinedMultiplier,
                        'amount' => $amount
                    ];
                }
            }
        }

        // Rest day + holiday overtime - use total overtime hours
        $restHolidayOvertimeTypes = [
            'rest_day_special_holiday' => 'Rest Day Special Holiday OT',
            'rest_day_regular_holiday' => 'Rest Day Regular Holiday OT'
        ];

        foreach ($restHolidayOvertimeTypes as $type => $name) {
            if (isset($timeBreakdown[$type]) && $timeBreakdown[$type]['overtime_hours'] > 0) {
                $data = $timeBreakdown[$type];
                $overtimeHours = $data['overtime_hours'];
                // Get rate config from the time breakdown (same as draft calculation)
                $rateConfig = $data['rate_config'] ?? null;

                // If rate config is not available, fetch from database as fallback
                if (!$rateConfig) {
                    $rateConfig = \App\Models\PayrollRateConfiguration::where('type_name', $type)
                        ->where('is_active', true)
                        ->first();
                }

                if ($rateConfig) {
                    $multiplier = $rateConfig->overtime_rate_multiplier ?? 1.25;
                    // Calculate per-minute amount with rounding (same as draft payroll)
                    $actualMinutes = $overtimeHours * 60;
                    $roundedMinutes = round($actualMinutes);
                    $ratePerMinute = ($hourlyRate * $multiplier) / 60;
                    $amount = $roundedMinutes * $ratePerMinute;

                    $breakdown[$name] = [
                        'hours' => $overtimeHours,
                        'rate' => number_format($hourlyRate, 2),
                        'multiplier' => $multiplier,
                        'amount' => $amount
                    ];
                } else {
                    // Ultimate fallback to hardcoded multipliers if no config found
                    $fallbackMultipliers = [
                        'rest_day_special_holiday' => 1.95,
                        'rest_day_regular_holiday' => 3.38
                    ];
                    $multiplier = $fallbackMultipliers[$type] ?? 1.25;
                    // Calculate per-minute amount with rounding (same as draft payroll)
                    $actualMinutes = $overtimeHours * 60;
                    $roundedMinutes = round($actualMinutes);
                    $ratePerMinute = ($hourlyRate * $multiplier) / 60;
                    $amount = $roundedMinutes * $ratePerMinute;

                    $breakdown[$name] = [
                        'hours' => $overtimeHours,
                        'rate' => number_format($hourlyRate, 2),
                        'multiplier' => $multiplier,
                        'amount' => $amount
                    ];
                }
            }
        }

        return $breakdown;
    }

    /**
                    'rate' => number_format($hourlyRate, 2),
                    'multiplier' => 1.69,
                    'amount' => $amount
                ];
            }
        }

        return $breakdown;
    }

    /**
     * Calculate correct Holiday Pay from PayrollDetail
     */
    private function calculateCorrectHolidayPay($detail, $payroll)
    {
        // Get the breakdown data that matches the payroll summary calculation
        if (isset($detail->earnings_breakdown)) {
            $earningsBreakdown = is_string($detail->earnings_breakdown)
                ? json_decode($detail->earnings_breakdown, true)
                : $detail->earnings_breakdown;

            if (is_array($earningsBreakdown) && isset($earningsBreakdown['holiday'])) {
                $holidayTotal = 0;
                foreach ($earningsBreakdown['holiday'] as $holidayData) {
                    $holidayTotal += is_array($holidayData) ? ($holidayData['amount'] ?? $holidayData) : $holidayData;
                }
                return $holidayTotal;
            }
        }

        // Fallback to regular holiday_pay if no breakdown
        return $detail->holiday_pay ?? 0;
    }

    /**
     * Calculate correct Overtime Pay from PayrollDetail
     */
    private function calculateCorrectOvertimePay($detail, $payroll)
    {
        // Get the breakdown data that matches the payroll summary calculation
        if (isset($detail->earnings_breakdown)) {
            $earningsBreakdown = is_string($detail->earnings_breakdown)
                ? json_decode($detail->earnings_breakdown, true)
                : $detail->earnings_breakdown;

            if (is_array($earningsBreakdown) && isset($earningsBreakdown['overtime'])) {
                $overtimeTotal = 0;
                foreach ($earningsBreakdown['overtime'] as $overtimeData) {
                    $overtimeTotal += is_array($overtimeData) ? ($overtimeData['amount'] ?? $overtimeData) : $overtimeData;
                }
                return $overtimeTotal;
            }
        }

        // Fallback to regular overtime_pay if no breakdown
        return $detail->overtime_pay ?? 0;
    }

    /**
     * Calculate correct Holiday Pay from PayrollSnapshot
     */
    private function calculateCorrectHolidayPayFromSnapshot($snapshot)
    {
        // Get the breakdown data from snapshot
        if (isset($snapshot->holiday_breakdown)) {
            $holidayBreakdown = is_string($snapshot->holiday_breakdown)
                ? json_decode($snapshot->holiday_breakdown, true)
                : $snapshot->holiday_breakdown;

            if (is_array($holidayBreakdown)) {
                $holidayTotal = 0;
                foreach ($holidayBreakdown as $type => $data) {
                    $holidayTotal += $data['amount'] ?? 0;
                }
                return $holidayTotal;
            }
        }

        // Fallback to regular holiday_pay if no breakdown
        return $snapshot->holiday_pay ?? 0;
    }

    /**
     * Calculate correct Rest Pay from PayrollDetail
     */
    private function calculateCorrectRestPay($detail, $payroll)
    {
        // Get the breakdown data that matches the payroll summary calculation
        if (isset($detail->earnings_breakdown)) {
            $earningsBreakdown = is_string($detail->earnings_breakdown)
                ? json_decode($detail->earnings_breakdown, true)
                : $detail->earnings_breakdown;

            if (is_array($earningsBreakdown) && isset($earningsBreakdown['rest'])) {
                $restTotal = 0;
                foreach ($earningsBreakdown['rest'] as $restData) {
                    $restTotal += is_array($restData) ? ($restData['amount'] ?? $restData) : $restData;
                }
                return $restTotal;
            }
        }

        // Fallback to regular rest_day_pay if no breakdown
        return $detail->rest_day_pay ?? 0;
    }

    /**
     * Calculate correct Rest Pay from PayrollSnapshot
     */
    private function calculateCorrectRestPayFromSnapshot($snapshot)
    {
        // Get the breakdown data from snapshot
        if (isset($snapshot->rest_breakdown)) {
            $restBreakdown = is_string($snapshot->rest_breakdown)
                ? json_decode($snapshot->rest_breakdown, true)
                : $snapshot->rest_breakdown;

            if (is_array($restBreakdown)) {
                $restTotal = 0;
                foreach ($restBreakdown as $restData) {
                    $restTotal += $restData['amount'] ?? 0;
                }
                return $restTotal;
            }
        }

        // Fallback to regular rest_day_pay if no breakdown
        return $snapshot->rest_day_pay ?? 0;
    }

    /**
     * Calculate correct Overtime Pay from PayrollSnapshot
     */
    private function calculateCorrectOvertimePayFromSnapshot($snapshot)
    {
        // Get the breakdown data from snapshot
        if (isset($snapshot->overtime_breakdown)) {
            $overtimeBreakdown = is_string($snapshot->overtime_breakdown)
                ? json_decode($snapshot->overtime_breakdown, true)
                : $snapshot->overtime_breakdown;

            if (is_array($overtimeBreakdown)) {
                $overtimeTotal = 0;
                foreach ($overtimeBreakdown as $type => $data) {
                    $overtimeTotal += $data['amount'] ?? 0;
                }
                return $overtimeTotal;
            }
        }

        // Fallback to regular overtime_pay if no breakdown
        return $snapshot->overtime_pay ?? 0;
    }

    /**
     * Mark payroll as paid with optional proof upload
     */
    public function markAsPaid(Request $request, Payroll $payroll)
    {
        $this->authorize('mark payrolls as paid');

        if (!$payroll->canBeMarkedAsPaid()) {
            return redirect()->back()->with('error', 'This payroll cannot be marked as paid.');
        }

        $request->validate([
            'payment_notes' => 'nullable|string|max:1000',
            'payment_proof.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240', // Max 10MB per file
        ]);

        try {
            DB::beginTransaction();

            $paymentProofFiles = [];

            // Handle file uploads
            if ($request->hasFile('payment_proof')) {
                foreach ($request->file('payment_proof') as $file) {
                    if ($file->isValid()) {
                        $originalName = $file->getClientOriginalName();
                        $fileName = time() . '_' . str_replace(' ', '_', $originalName);
                        $filePath = $file->storeAs('payroll_proofs', $fileName, 'public');

                        $paymentProofFiles[] = [
                            'original_name' => $originalName,
                            'file_name' => $fileName,
                            'file_path' => $filePath,
                            'file_size' => $file->getSize(),
                            'mime_type' => $file->getMimeType(),
                            'uploaded_at' => now()->toISOString(),
                        ];
                    }
                }
            }

            // Update payroll
            $payroll->update([
                'is_paid' => true,
                'payment_proof_files' => $paymentProofFiles,
                'payment_notes' => $request->payment_notes,
                'marked_paid_by' => Auth::id(),
                'marked_paid_at' => now(),
            ]);

            // Process cash advance deductions now that payroll is marked as paid
            $this->processCashAdvanceDeductions($payroll);

            // Update employee shares calculations
            $this->updateEmployeeSharesCalculations($payroll);

            DB::commit();

            Log::info('Payroll marked as paid', [
                'payroll_id' => $payroll->id,
                'payroll_number' => $payroll->payroll_number,
                'marked_by' => Auth::id(),
                'proof_files_count' => count($paymentProofFiles),
            ]);

            return redirect()->back()->with('success', 'Payroll marked as paid successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to mark payroll as paid', [
                'payroll_id' => $payroll->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Failed to mark payroll as paid: ' . $e->getMessage());
        }
    }

    /**
     * Unmark payroll as paid (undo)
     */
    public function unmarkAsPaid(Payroll $payroll)
    {
        $this->authorize('mark payrolls as paid');

        if (!$payroll->canBeUnmarkedAsPaid()) {
            return redirect()->back()->with('error', 'This payroll cannot be unmarked as paid.');
        }

        try {
            DB::beginTransaction();

            // Reverse cash advance deductions
            $this->reverseCashAdvanceDeductions($payroll);

            // Reverse employee shares calculations
            $this->reverseEmployeeSharesCalculations($payroll);

            // Update payroll
            $payroll->update([
                'is_paid' => false,
                'payment_proof_files' => null,
                'payment_notes' => null,
                'marked_paid_by' => null,
                'marked_paid_at' => null,
            ]);

            DB::commit();

            Log::info('Payroll unmarked as paid', [
                'payroll_id' => $payroll->id,
                'payroll_number' => $payroll->payroll_number,
                'unmarked_by' => Auth::id(),
            ]);

            return redirect()->back()->with('success', 'Payroll unmarked as paid successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to unmark payroll as paid', [
                'payroll_id' => $payroll->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Failed to unmark payroll as paid: ' . $e->getMessage());
        }
    }

    /**
     * Process cash advance deductions when payroll is marked as paid
     */
    private function processCashAdvanceDeductions(Payroll $payroll)
    {
        foreach ($payroll->payrollDetails as $detail) {
            if ($detail->cash_advance_deductions > 0) {
                // Get the cash advance calculation for this employee and period
                $cashAdvanceData = CashAdvance::calculateDeductionForPeriod(
                    $detail->employee_id,
                    $payroll->period_start,
                    $payroll->period_end
                );

                $remainingDeduction = $detail->cash_advance_deductions;

                foreach ($cashAdvanceData['details'] as $deductionDetail) {
                    if ($remainingDeduction <= 0) break;

                    $cashAdvance = CashAdvance::find($deductionDetail['cash_advance_id']);
                    if ($cashAdvance) {
                        $deductionAmount = min($deductionDetail['amount'], $remainingDeduction);

                        if ($deductionAmount > 0) {
                            // Record the payment
                            $cashAdvance->recordPayment(
                                $deductionAmount,
                                $payroll->id,
                                $detail->id,
                                'Payroll deduction for period ' . $payroll->period_start->format('M d') . ' - ' . $payroll->period_end->format('d, Y')
                            );

                            $remainingDeduction -= $deductionAmount;
                        }
                    }
                }
            }
        }
    }

    /**
     * Reverse cash advance deductions when payroll is unmarked as paid
     */
    private function reverseCashAdvanceDeductions(Payroll $payroll)
    {
        // Find and reverse payments made from this payroll
        $payments = CashAdvancePayment::where('payroll_id', $payroll->id)->get();

        foreach ($payments as $payment) {
            $cashAdvance = $payment->cashAdvance;
            if ($cashAdvance) {
                // Restore the outstanding balance
                $cashAdvance->increment('outstanding_balance', $payment->payment_amount ?? $payment->amount);

                // If cash advance was marked as completed, revert to approved
                if ($cashAdvance->status === 'completed' && $cashAdvance->outstanding_balance > 0) {
                    $cashAdvance->update(['status' => 'approved']);
                }
            }

            // Delete the payment record
            $payment->delete();
        }
    }

    /**
     * Update employee shares calculations (SSS, PhilHealth, Pag-IBIG)
     */
    private function updateEmployeeSharesCalculations(Payroll $payroll)
    {
        // This method would trigger recalculation of government contribution reports
        // For now, we'll just log the action as the reports are generated on-demand
        Log::info('Employee shares calculations updated for paid payroll', [
            'payroll_id' => $payroll->id,
            'payroll_number' => $payroll->payroll_number,
        ]);

        // In a real implementation, you might want to:
        // 1. Update cached totals for government reports
        // 2. Trigger notifications to accounting
        // 3. Update dashboard metrics
    }

    /**
     * Reverse employee shares calculations
     */
    private function reverseEmployeeSharesCalculations(Payroll $payroll)
    {
        // This method would reverse the employee shares calculations
        // For now, we'll just log the action
        Log::info('Employee shares calculations reversed for unpaid payroll', [
            'payroll_id' => $payroll->id,
            'payroll_number' => $payroll->payroll_number,
        ]);
    }
}
