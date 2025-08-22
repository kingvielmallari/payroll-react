<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\PayrollSnapshot;
use App\Models\Employee;
use App\Models\TimeLog;
use App\Models\PayScheduleSetting;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PayrollController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display all payrolls with filtering
     */
    public function index(Request $request)
    {
        $this->authorize('view payrolls');

        $query = Payroll::with(['creator', 'approver', 'payrollDetails.employee'])
            ->withCount('payrollDetails')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('schedule')) {
            $query->where('pay_schedule', $request->schedule);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('period_start', [$request->date_from, $request->date_to]);
        }

        $payrolls = $query->paginate(15)->withQueryString();

        $scheduleSettings = PayScheduleSetting::systemDefaults()
            ->orderBy('sort_order')
            ->get();

        return view('payrolls.index', compact('payrolls', 'scheduleSettings'));
    }

    /**
     * Show form for creating new payroll
     */
    public function create(Request $request)
    {
        $this->authorize('create payrolls');

        $selectedSchedule = $request->input('schedule');

        $scheduleSettings = PayScheduleSetting::systemDefaults()
            ->orderBy('sort_order')
            ->get();

        if (!$selectedSchedule) {
            return view('payrolls.create', [
                'scheduleSettings' => $scheduleSettings,
                'selectedSchedule' => null,
                'availablePeriods' => [],
                'employees' => collect()
            ]);
        }

        $scheduleSetting = $scheduleSettings->firstWhere('code', $selectedSchedule);
        if (!$scheduleSetting) {
            return redirect()->route('payrolls.create')
                ->withErrors(['schedule' => 'Invalid pay schedule selected.']);
        }

        $availablePeriods = $this->getAvailablePeriodsForSchedule($scheduleSetting);
        $employees = Employee::with(['user', 'department', 'position'])
            ->where('employment_status', 'active')
            ->where('pay_schedule', $selectedSchedule)
            ->orderBy('first_name')
            ->get();

        return view('payrolls.create', compact(
            'scheduleSettings',
            'selectedSchedule',
            'availablePeriods',
            'employees'
        ));
    }

    /**
     * Store newly created draft payroll
     */
    public function store(Request $request)
    {
        $this->authorize('create payrolls');

        $validated = $request->validate([
            'selected_period' => 'required|string',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        $periodData = json_decode(base64_decode($validated['selected_period']), true);
        if (!$periodData) {
            return back()->withErrors(['selected_period' => 'Invalid period selection.'])->withInput();
        }

        DB::beginTransaction();
        try {
            // Create draft payroll
            $payroll = Payroll::create([
                'payroll_number' => Payroll::generatePayrollNumber('regular'),
                'period_start' => $periodData['period_start'],
                'period_end' => $periodData['period_end'],
                'pay_date' => $periodData['pay_date'],
                'payroll_type' => 'regular',
                'pay_schedule' => $periodData['pay_schedule'],
                'description' => 'Payroll for ' . $periodData['period_display'],
                'status' => 'draft', // Always start as draft
                'created_by' => Auth::id(),
            ]);

            // Create payroll details for each employee (no calculations yet, just placeholders)
            foreach ($validated['employee_ids'] as $employeeId) {
                PayrollDetail::create([
                    'payroll_id' => $payroll->id,
                    'employee_id' => $employeeId,
                    'basic_salary' => 0, // Will be calculated dynamically in show()
                    'gross_pay' => 0,
                    'total_deductions' => 0,
                    'net_pay' => 0,
                ]);
            }

            DB::commit();

            return redirect()->route('payrolls.show', $payroll)
                ->with('success', 'Draft payroll created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create payroll: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to create payroll: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Show payroll details
     * - Draft payrolls: Calculate dynamically on-the-fly
     * - Processing payrolls: Show static data from snapshots
     */
    public function show(Payroll $payroll)
    {
        $this->authorize('view payrolls');

        $payroll->load([
            'payrollDetails.employee.user',
            'payrollDetails.employee.department',
            'payrollDetails.employee.position',
            'creator',
            'approver'
        ]);

        if ($payroll->status === 'draft') {
            // DRAFT MODE: Calculate everything dynamically
            $payrollData = $this->calculateDraftPayroll($payroll);
        } else {
            // PROCESSING/APPROVED MODE: Use static snapshot data
            $payrollData = $this->getProcessingPayrollData($payroll);
        }

        return view('payrolls.show', compact('payroll', 'payrollData'));
    }

    /**
     * Submit draft payroll to processing (creates snapshots)
     */
    public function submitToProcessing(Payroll $payroll)
    {
        $this->authorize('edit payrolls');

        if ($payroll->status !== 'draft') {
            return back()->withErrors(['error' => 'Only draft payrolls can be submitted to processing.']);
        }

        DB::beginTransaction();
        try {
            // Calculate final draft data and create snapshots
            $this->createPayrollSnapshots($payroll);

            // Update payroll status to processing
            $payroll->update([
                'status' => 'processing',
                'submitted_at' => now(),
                'submitted_by' => Auth::id(),
            ]);

            // Update payroll details with snapshot data
            $this->updatePayrollDetailsFromSnapshots($payroll);

            DB::commit();

            return redirect()->route('payrolls.show', $payroll)
                ->with('success', 'Payroll submitted to processing successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to submit payroll to processing: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to submit payroll: ' . $e->getMessage()]);
        }
    }

    /**
     * Approve processing payroll
     */
    public function approve(Payroll $payroll)
    {
        $this->authorize('approve payrolls');

        if ($payroll->status !== 'processing') {
            return back()->withErrors(['error' => 'Only processing payrolls can be approved.']);
        }

        $payroll->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => Auth::id(),
        ]);

        return redirect()->route('payrolls.show', $payroll)
            ->with('success', 'Payroll approved successfully!');
    }

    /**
     * Reject processing payroll (back to draft)
     */
    public function reject(Payroll $payroll)
    {
        $this->authorize('edit payrolls');

        if ($payroll->status !== 'processing') {
            return back()->withErrors(['error' => 'Only processing payrolls can be rejected.']);
        }

        DB::beginTransaction();
        try {
            // Delete snapshots
            PayrollSnapshot::where('payroll_id', $payroll->id)->delete();

            // Reset payroll status to draft
            $payroll->update([
                'status' => 'draft',
                'submitted_at' => null,
                'submitted_by' => null,
                'approved_at' => null,
                'approved_by' => null,
            ]);

            // Reset payroll details to minimal state
            foreach ($payroll->payrollDetails as $detail) {
                $detail->update([
                    'basic_salary' => 0,
                    'gross_pay' => 0,
                    'total_deductions' => 0,
                    'net_pay' => 0,
                ]);
            }

            DB::commit();

            return redirect()->route('payrolls.show', $payroll)
                ->with('success', 'Payroll rejected and sent back to draft mode.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reject payroll: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to reject payroll: ' . $e->getMessage()]);
        }
    }

    /**
     * Edit payroll (draft only)
     */
    public function edit(Payroll $payroll)
    {
        $this->authorize('edit payrolls');

        if ($payroll->status !== 'draft') {
            return back()->withErrors(['error' => 'Only draft payrolls can be edited.']);
        }

        $payroll->load(['payrollDetails.employee']);

        $scheduleSettings = PayScheduleSetting::systemDefaults()
            ->orderBy('sort_order')
            ->get();

        $availablePeriods = $this->getAvailablePeriodsForSchedule(
            $scheduleSettings->firstWhere('code', $payroll->pay_schedule)
        );

        $allEmployees = Employee::with(['user', 'department', 'position'])
            ->where('employment_status', 'active')
            ->where('pay_schedule', $payroll->pay_schedule)
            ->orderBy('first_name')
            ->get();

        return view('payrolls.edit', compact('payroll', 'scheduleSettings', 'availablePeriods', 'allEmployees'));
    }

    /**
     * Update payroll (draft only)
     */
    public function update(Request $request, Payroll $payroll)
    {
        $this->authorize('edit payrolls');

        if ($payroll->status !== 'draft') {
            return back()->withErrors(['error' => 'Only draft payrolls can be updated.']);
        }

        $validated = $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        DB::beginTransaction();
        try {
            // Update payroll details
            $payroll->payrollDetails()->delete();

            foreach ($validated['employee_ids'] as $employeeId) {
                PayrollDetail::create([
                    'payroll_id' => $payroll->id,
                    'employee_id' => $employeeId,
                    'basic_salary' => 0,
                    'gross_pay' => 0,
                    'total_deductions' => 0,
                    'net_pay' => 0,
                ]);
            }

            DB::commit();

            return redirect()->route('payrolls.show', $payroll)
                ->with('success', 'Payroll updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update payroll: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to update payroll: ' . $e->getMessage()]);
        }
    }

    /**
     * Generate payslip PDF (approved payrolls only)
     */
    public function payslip(Payroll $payroll)
    {
        $this->authorize('view payslips');

        if ($payroll->status !== 'approved') {
            return back()->withErrors(['error' => 'Only approved payrolls can generate payslips.']);
        }

        // Implementation for PDF generation would go here
        // For now, return a simple view
        $payrollData = $this->getProcessingPayrollData($payroll);

        return view('payrolls.payslip', compact('payroll', 'payrollData'));
    }

    /**
     * Delete payroll (draft only)
     */
    public function destroy(Payroll $payroll)
    {
        $this->authorize('delete payrolls');

        if ($payroll->status !== 'draft') {
            return back()->withErrors(['error' => 'Only draft payrolls can be deleted.']);
        }

        DB::beginTransaction();
        try {
            // Delete payroll details and the payroll itself
            $payroll->payrollDetails()->delete();
            $payroll->delete();

            DB::commit();

            return redirect()->route('payrolls.index')
                ->with('success', 'Draft payroll deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete payroll: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to delete payroll: ' . $e->getMessage()]);
        }
    }

    /**
     * Refresh calculations for draft payrolls (development/debug)
     */
    public function refreshCalculations(Payroll $payroll)
    {
        $this->authorize('edit payrolls');

        if ($payroll->status !== 'draft') {
            return back()->withErrors(['error' => 'Only draft payrolls can refresh calculations.']);
        }

        // This method can be used to force refresh calculations in development
        // In a real application, calculations are always dynamic for drafts

        return redirect()->route('payrolls.show', $payroll)
            ->with('success', 'Calculations refreshed!');
    }

    /**
     * Calculate draft payroll data dynamically
     */
    private function calculateDraftPayroll(Payroll $payroll)
    {
        $employeeData = [];
        $totalBasic = 0;
        $totalHoliday = 0;
        $totalRest = 0;
        $totalOvertime = 0;
        $totalAllowances = 0;
        $totalBonuses = 0;
        $totalGross = 0;
        $totalDeductions = 0;
        $totalNet = 0;

        foreach ($payroll->payrollDetails as $detail) {
            $employee = $detail->employee;

            // Get time logs for the period
            $timeLogs = TimeLog::where('employee_id', $employee->id)
                ->whereBetween('log_date', [$payroll->period_start, $payroll->period_end])
                ->get();

            // Calculate detailed breakdown dynamically
            $calculation = $this->calculateDetailedEmployeeEarnings($employee, $timeLogs);

            // Calculate deductions dynamically
            $deductions = $this->calculateEmployeeDeductions($employee, $calculation['gross_pay']);

            $netPay = $calculation['gross_pay'] - $deductions['total'];

            $employeeData[] = [
                'employee' => $employee,
                'time_logs' => $timeLogs,
                'basic_pay' => $calculation['basic_pay'],
                'holiday_pay' => $calculation['holiday_pay'],
                'rest_day_pay' => $calculation['rest_day_pay'],
                'overtime_pay' => $calculation['overtime_pay'],
                'allowances' => $calculation['allowances'],
                'bonuses' => $calculation['bonuses'],
                'gross_pay' => $calculation['gross_pay'],
                'deductions' => $deductions,
                'net_pay' => $netPay,
                'hours_breakdown' => $calculation['hours_breakdown'],
            ];

            $totalBasic += $calculation['basic_pay'];
            $totalHoliday += $calculation['holiday_pay'];
            $totalRest += $calculation['rest_day_pay'];
            $totalOvertime += $calculation['overtime_pay'];
            $totalAllowances += $calculation['allowances'];
            $totalBonuses += $calculation['bonuses'];
            $totalGross += $calculation['gross_pay'];
            $totalDeductions += $deductions['total'];
            $totalNet += $netPay;
        }

        return [
            'employees' => $employeeData,
            'totals' => [
                'basic' => $totalBasic,
                'holiday' => $totalHoliday,
                'rest' => $totalRest,
                'overtime' => $totalOvertime,
                'allowances' => $totalAllowances,
                'bonuses' => $totalBonuses,
                'gross' => $totalGross,
                'deductions' => $totalDeductions,
                'net' => $totalNet,
            ],
            'is_dynamic' => true,
        ];
    }

    /**
     * Get processing payroll data from snapshots (static)
     */
    private function getProcessingPayrollData(Payroll $payroll)
    {
        $employeeData = [];
        $totalBasic = 0;
        $totalHoliday = 0;
        $totalRest = 0;
        $totalOvertime = 0;
        $totalAllowances = 0;
        $totalBonuses = 0;
        $totalGross = 0;
        $totalDeductions = 0;
        $totalNet = 0;

        foreach ($payroll->payrollDetails as $detail) {
            $snapshot = PayrollSnapshot::where('payroll_id', $payroll->id)
                ->where('employee_id', $detail->employee_id)
                ->first();

            if (!$snapshot) {
                throw new \Exception("No snapshot found for employee {$detail->employee->employee_number}");
            }

            // Use the same structure as draft mode for consistency
            $employeeData[] = [
                'employee' => $detail->employee,
                'basic_pay' => $detail->regular_pay ?? 0,
                'holiday_pay' => $detail->holiday_pay ?? 0,
                'rest_day_pay' => $detail->rest_day_pay ?? 0,
                'overtime_pay' => $detail->overtime_pay ?? 0,
                'allowances' => $detail->allowances ?? 0,
                'bonuses' => $detail->bonuses ?? 0,
                'gross_pay' => $detail->gross_pay,
                'deductions' => [
                    'sss' => $detail->sss_contribution ?? 0,
                    'philhealth' => $detail->philhealth_contribution ?? 0,
                    'pagibig' => $detail->pagibig_contribution ?? 0,
                    'tax' => $detail->withholding_tax ?? 0,
                    'custom' => $detail->other_deductions ?? 0,
                    'total' => $detail->total_deductions,
                ],
                'net_pay' => $detail->net_pay,
                'hours_breakdown' => [
                    'regular_hours' => $detail->regular_hours ?? 0,
                    'overtime_hours' => $detail->overtime_hours ?? 0,
                    'holiday_hours' => $detail->holiday_hours ?? 0,
                    'total_hours' => ($detail->regular_hours ?? 0) + ($detail->overtime_hours ?? 0) + ($detail->holiday_hours ?? 0),
                ],
                'snapshot' => $snapshot,
                'detail' => $detail,
            ];

            $totalBasic += $detail->regular_pay ?? 0;
            $totalHoliday += $detail->holiday_pay ?? 0;
            $totalRest += $detail->rest_day_pay ?? 0;
            $totalOvertime += $detail->overtime_pay ?? 0;
            $totalAllowances += $detail->allowances ?? 0;
            $totalBonuses += $detail->bonuses ?? 0;
            $totalGross += $detail->gross_pay;
            $totalDeductions += $detail->total_deductions;
            $totalNet += $detail->net_pay;
        }

        return [
            'employees' => $employeeData,
            'totals' => [
                'basic' => $totalBasic,
                'holiday' => $totalHoliday,
                'rest' => $totalRest,
                'overtime' => $totalOvertime,
                'allowances' => $totalAllowances,
                'bonuses' => $totalBonuses,
                'gross' => $totalGross,
                'deductions' => $totalDeductions,
                'net' => $totalNet,
            ],
            'is_dynamic' => false,
        ];
    }

    /**
     * Calculate detailed employee earnings dynamically (matching your current structure)
     */
    private function calculateDetailedEmployeeEarnings(Employee $employee, $timeLogs)
    {
        $totalHours = 0;
        $regularHours = 0;
        $overtimeHours = 0;
        $holidayHours = 0;
        $restDayHours = 0;
        $nightDiffHours = 0;
        $daysWorked = 0;

        // Calculate hourly rate
        $hourlyRate = $this->calculateHourlyRate($employee);

        // Initialize pay components
        $basicPay = 0;
        $holidayPay = 0;
        $restDayPay = 0;
        $overtimePay = 0;

        // Process time logs by type
        foreach ($timeLogs as $timeLog) {
            if ($timeLog->time_in && $timeLog->time_out) {
                $daysWorked++;

                // Dynamic calculation using current grace period settings
                $calculation = $this->calculateTimeLogHoursDynamically($timeLog);

                $logRegularHours = $calculation['regular_hours'];
                $logOvertimeHours = $calculation['overtime_hours'];
                $logTotalHours = $calculation['total_hours'];

                $totalHours += $logTotalHours;

                // Categorize by log type
                switch ($timeLog->log_type) {
                    case 'regular_workday':
                        $regularHours += $logRegularHours;
                        $overtimeHours += $logOvertimeHours;
                        $basicPay += $logRegularHours * $hourlyRate;
                        $overtimePay += $logOvertimeHours * $hourlyRate * 1.25; // 25% overtime premium
                        break;

                    case 'special_holiday':
                    case 'regular_holiday':
                    case 'rest_day_regular_holiday':
                    case 'rest_day_special_holiday':
                        $holidayHours += $logTotalHours;
                        // Holiday premium (varies by type, using 2.0 as default)
                        $holidayMultiplier = $this->getHolidayMultiplier($timeLog->log_type);
                        $holidayPay += $logTotalHours * $hourlyRate * $holidayMultiplier;
                        break;

                    case 'rest_day':
                        $restDayHours += $logTotalHours;
                        // Rest day premium (130% = 1.3 multiplier)
                        $restDayPay += $logTotalHours * $hourlyRate * 1.3;
                        break;
                }

                // Night differential if applicable
                $nightDiffHours += $this->calculateNightDifferential($timeLog);
            }
        }

        // Calculate night differential pay
        $nightDiffPay = $nightDiffHours * $hourlyRate * 0.10; // 10% night differential

        // Calculate allowances and bonuses
        $allowances = $this->calculateAllowances($employee, $basicPay, $daysWorked, $totalHours);
        $bonuses = $this->calculateBonuses($employee, $basicPay, $daysWorked, $totalHours);

        $grossPay = $basicPay + $holidayPay + $restDayPay + $overtimePay + $nightDiffPay + $allowances + $bonuses;

        return [
            'basic_pay' => $basicPay,
            'holiday_pay' => $holidayPay,
            'rest_day_pay' => $restDayPay,
            'overtime_pay' => $overtimePay,
            'night_diff_pay' => $nightDiffPay,
            'allowances' => $allowances,
            'bonuses' => $bonuses,
            'gross_pay' => $grossPay,
            'hours_breakdown' => [
                'days_worked' => $daysWorked,
                'regular_hours' => $regularHours,
                'overtime_hours' => $overtimeHours,
                'holiday_hours' => $holidayHours,
                'rest_day_hours' => $restDayHours,
                'night_diff_hours' => $nightDiffHours,
                'total_hours' => $totalHours,
            ],
            'hourly_rate' => $hourlyRate,
        ];
    }

    /**
     * Get holiday multiplier based on log type
     */
    private function getHolidayMultiplier($logType)
    {
        switch ($logType) {
            case 'regular_holiday':
            case 'rest_day_regular_holiday':
                return 2.0; // 200%
            case 'special_holiday':
            case 'rest_day_special_holiday':
                return 1.3; // 130%
            default:
                return 2.0;
        }
    }

    /**
     * Calculate employee deductions dynamically
     */
    private function calculateEmployeeDeductions(Employee $employee, $grossPay)
    {
        $sss = $this->calculateSSSContribution($grossPay);
        $philhealth = $this->calculatePhilHealthContribution($grossPay);
        $pagibig = $this->calculatePagibigContribution($grossPay);
        $tax = $this->calculateWithholdingTax($grossPay, $sss, $philhealth, $pagibig);

        // Get custom deductions
        $customDeductions = $this->calculateCustomDeductions($employee, $grossPay);

        $totalDeductions = $sss + $philhealth + $pagibig + $tax + $customDeductions;

        return [
            'sss' => $sss,
            'philhealth' => $philhealth,
            'pagibig' => $pagibig,
            'tax' => $tax,
            'custom' => $customDeductions,
            'total' => $totalDeductions,
        ];
    }

    /**
     * Create payroll snapshots when submitting to processing
     */
    private function createPayrollSnapshots(Payroll $payroll)
    {
        foreach ($payroll->payrollDetails as $detail) {
            $employee = $detail->employee;

            // Calculate final values
            $timeLogs = TimeLog::where('employee_id', $employee->id)
                ->whereBetween('log_date', [$payroll->period_start, $payroll->period_end])
                ->get();

            $calculation = $this->calculateDetailedEmployeeEarnings($employee, $timeLogs);
            $deductions = $this->calculateEmployeeDeductions($employee, $calculation['gross_pay']);
            $netPay = $calculation['gross_pay'] - $deductions['total'];

            // Create snapshot
            PayrollSnapshot::create([
                'payroll_id' => $payroll->id,
                'employee_id' => $employee->id,
                'basic_salary' => $employee->basic_salary,
                'hourly_rate' => $calculation['hourly_rate'],
                'days_worked' => $calculation['hours_breakdown']['days_worked'],
                'regular_hours' => $calculation['hours_breakdown']['regular_hours'],
                'overtime_hours' => $calculation['hours_breakdown']['overtime_hours'],
                'holiday_hours' => $calculation['hours_breakdown']['holiday_hours'],
                'night_differential_hours' => $calculation['hours_breakdown']['night_diff_hours'],
                'regular_pay' => $calculation['basic_pay'],
                'overtime_pay' => $calculation['overtime_pay'],
                'holiday_pay' => $calculation['holiday_pay'],
                'night_differential_pay' => $calculation['night_diff_pay'],
                'allowances_total' => $calculation['allowances'],
                'bonuses_total' => $calculation['bonuses'],
                'gross_pay' => $calculation['gross_pay'],
                'sss_contribution' => $deductions['sss'],
                'philhealth_contribution' => $deductions['philhealth'],
                'pagibig_contribution' => $deductions['pagibig'],
                'withholding_tax' => $deductions['tax'],
                'other_deductions' => $deductions['custom'],
                'total_deductions' => $deductions['total'],
                'net_pay' => $netPay,
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Update payroll details from snapshots
     */
    private function updatePayrollDetailsFromSnapshots(Payroll $payroll)
    {
        foreach ($payroll->payrollDetails as $detail) {
            $snapshot = PayrollSnapshot::where('payroll_id', $payroll->id)
                ->where('employee_id', $detail->employee_id)
                ->first();

            if ($snapshot) {
                $detail->update([
                    'basic_salary' => $snapshot->basic_salary,
                    'hourly_rate' => $snapshot->hourly_rate,
                    'days_worked' => $snapshot->days_worked,
                    'regular_hours' => $snapshot->regular_hours,
                    'overtime_hours' => $snapshot->overtime_hours,
                    'holiday_hours' => $snapshot->holiday_hours,
                    'night_differential_hours' => $snapshot->night_differential_hours,
                    'regular_pay' => $snapshot->regular_pay,
                    'overtime_pay' => $snapshot->overtime_pay,
                    'holiday_pay' => $snapshot->holiday_pay,
                    'rest_day_pay' => 0, // Calculate rest day pay separately if needed
                    'night_differential_pay' => $snapshot->night_differential_pay,
                    'allowances' => $snapshot->allowances_total,
                    'bonuses' => $snapshot->bonuses_total,
                    'gross_pay' => $snapshot->gross_pay,
                    'sss_contribution' => $snapshot->sss_contribution,
                    'philhealth_contribution' => $snapshot->philhealth_contribution,
                    'pagibig_contribution' => $snapshot->pagibig_contribution,
                    'withholding_tax' => $snapshot->withholding_tax,
                    'other_deductions' => $snapshot->other_deductions,
                    'total_deductions' => $snapshot->total_deductions,
                    'net_pay' => $snapshot->net_pay,
                ]);
            }
        }

        // Update payroll totals
        $payroll->update([
            'total_gross' => $payroll->payrollDetails->sum('gross_pay'),
            'total_deductions' => $payroll->payrollDetails->sum('total_deductions'),
            'total_net' => $payroll->payrollDetails->sum('net_pay'),
        ]);
    }

    // HELPER METHODS

    private function getAvailablePeriodsForSchedule($scheduleSetting)
    {
        // Implementation for getting available periods
        // This would contain the logic to calculate current and upcoming periods
        // based on the schedule setting
        return [];
    }

    private function calculateTimeLogHoursDynamically($timeLog)
    {
        // Implementation for dynamic time calculation using current grace period settings
        // This would calculate regular hours, overtime, etc. based on current system settings
        return [
            'regular_hours' => 8.0,
            'overtime_hours' => 0.0,
            'total_hours' => 8.0,
        ];
    }

    private function calculateNightDifferential($timeLog)
    {
        // Implementation for night differential calculation (10PM - 6AM)
        return 0.0;
    }

    private function calculateHourlyRate($employee)
    {
        if ($employee->hourly_rate) {
            return $employee->hourly_rate;
        }

        // Calculate from basic salary based on pay schedule
        switch ($employee->pay_schedule) {
            case 'weekly':
                return ($employee->basic_salary / 4.33) / 40;
            case 'semi_monthly':
                return ($employee->basic_salary / 2) / 86.67;
            default: // monthly
                return $employee->basic_salary / 173.33;
        }
    }

    private function calculateAllowances($employee, $basicPay, $daysWorked, $hoursWorked)
    {
        // Implementation for allowances calculation
        return 0.0;
    }

    private function calculateBonuses($employee, $basicPay, $daysWorked, $hoursWorked)
    {
        // Implementation for bonuses calculation
        return 0.0;
    }

    private function calculateSSSContribution($grossPay)
    {
        // Implementation for SSS contribution calculation
        return 0.0;
    }

    private function calculatePhilHealthContribution($grossPay)
    {
        // Implementation for PhilHealth contribution calculation
        return 0.0;
    }

    private function calculatePagibigContribution($grossPay)
    {
        // Implementation for Pag-IBIG contribution calculation
        return 0.0;
    }

    private function calculateWithholdingTax($grossPay, $sss, $philhealth, $pagibig)
    {
        // Implementation for withholding tax calculation
        return 0.0;
    }

    private function calculateCustomDeductions($employee, $grossPay)
    {
        // Implementation for custom deductions calculation
        return 0.0;
    }
}
