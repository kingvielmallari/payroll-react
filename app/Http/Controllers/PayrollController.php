<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\Employee;
use App\Models\TimeLog;
use App\Models\PayScheduleSetting;
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

        // Get the selected pay schedule filter
        $selectedSchedule = $request->input('schedule');

        // If no schedule is selected, show schedule selection page
        if (!$selectedSchedule) {
            // Get all payroll schedule settings for selection (including disabled ones)
            $scheduleSettings = \App\Models\PayScheduleSetting::systemDefaults()
                ->orderBy('sort_order')
                ->get();

            // Calculate current periods for each schedule to display
            foreach ($scheduleSettings as $setting) {
                $currentPeriods = $this->getCurrentPeriodDisplayForSchedule($setting);
                $setting->current_period_display = $currentPeriods;
            }

            return view('payrolls.schedule-selection', [
                'scheduleSettings' => $scheduleSettings
            ]);
        }

        // Show payrolls for selected schedule
        $query = Payroll::with(['creator', 'approver', 'payrollDetails.employee'])
            ->withCount('payrollDetails')
            ->where('pay_schedule', $selectedSchedule)
            ->orderBy('created_at', 'desc');

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

        // Get the schedule setting for display
        $scheduleSetting = \App\Models\PayScheduleSetting::systemDefaults()
            ->where('code', $selectedSchedule)
            ->first();

        return view('payrolls.index', compact('payrolls', 'selectedSchedule', 'scheduleSetting'));
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
                        'regular_pay' => $payrollCalculation['regular_pay'] ?? 0, // Use the calculated basic pay
                        'overtime_pay' => $payrollCalculation['overtime_pay'] ?? 0,
                        'holiday_pay' => $payrollCalculation['holiday_pay'] ?? 0,
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

        // Calculate deductions using dynamic settings
        $deductions = $this->calculateDeductions($employee, $totalGrossPay, $basicSalary, $overtimePay, $allowancesTotal, $bonusesTotal);

        $netPay = $totalGrossPay - $deductions['total'] - $lateDeductions - $undertimeDeductions;

        return [
            'basic_salary' => $basicSalary,  // Employee's base salary
            'regular_pay' => $grossPayData['basic_pay'],      // Basic pay for regular work
            'overtime_pay' => $grossPayData['overtime_pay'],
            'holiday_pay' => $grossPayData['holiday_pay'],
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
            'total_deductions' => $deductions['total'],
            'net_pay' => $netPay,
            'hours_worked' => $hoursWorked,
            'days_worked' => $daysWorked,
            'regular_hours' => $regularHours,
            'overtime_hours' => $overtimeHours,
            'holiday_hours' => $holidayHours,
            'late_hours' => $lateHours,
            'undertime_hours' => $undertimeHours,
            'late_deductions' => $lateDeductions,
            'undertime_deductions' => $undertimeDeductions,
            'cash_advance_deductions' => 0,  // TODO: Get from cash advances
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
                'overtime_pay' => 0,
                'regular_hours' => $hoursWorked,
                'overtime_hours' => 0,
                'holiday_hours' => 0,
                'pay_breakdown' => [],
                'overtime_breakdown' => [],
                'holiday_breakdown' => [],
            ];
        }

        // Calculate hourly rate based on pay schedule
        $hourlyRate = $this->calculateHourlyRate($employee, $basicSalary);

        $totalGrossPay = 0;
        $basicPay = 0;
        $holidayPay = 0;
        $overtimePay = 0;
        $regularHours = 0;
        $overtimeHours = 0;
        $holidayHours = 0;

        // Detailed breakdowns
        $payBreakdown = [];
        $overtimeBreakdown = [];
        $holidayBreakdown = [];

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
                // Rest day work is separate category but for now add to basic
                $basicPay += $regularAmount;
            } else {
                // Regular workday and other types
                $basicPay += $regularAmount;
            }
        }

        return [
            'total_gross' => $totalGrossPay,
            'basic_pay' => $basicPay,
            'holiday_pay' => $holidayPay,
            'overtime_pay' => $overtimePay,
            'regular_hours' => $regularHours,
            'overtime_hours' => $overtimeHours,
            'holiday_hours' => $holidayHours,
            'pay_breakdown' => $payBreakdown,
            'overtime_breakdown' => $overtimeBreakdown,
            'holiday_breakdown' => $holidayBreakdown,
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

                // For draft payrolls, add dynamic calculation to time log object for DTR display
                if ($timeLog && $payroll->status === 'draft' && $timeLog->time_in && $timeLog->time_out && $timeLog->remarks !== 'Incomplete Time Record') {
                    $dynamicCalculation = $this->calculateTimeLogHoursDynamically($timeLog);
                    $timeLog->dynamic_regular_hours = $dynamicCalculation['regular_hours'];
                    $timeLog->dynamic_overtime_hours = $dynamicCalculation['overtime_hours'];
                    $timeLog->dynamic_total_hours = $dynamicCalculation['total_hours'];
                }

                $employeeDtr[$date] = $timeLog;

                // Track time breakdown by type (exclude incomplete records)
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

                    // For draft payrolls, calculate dynamically using current grace periods
                    // For approved payrolls, use stored values (snapshots)
                    if ($payroll->status === 'draft') {
                        $dynamicCalculation = $this->calculateTimeLogHoursDynamically($timeLog);
                        $regularHours = $dynamicCalculation['regular_hours'];
                        $overtimeHours = $dynamicCalculation['overtime_hours'];
                        $totalHours = $dynamicCalculation['total_hours'];
                    } else {
                        $regularHours = $timeLog->regular_hours ?? 0;
                        $overtimeHours = $timeLog->overtime_hours ?? 0;
                        $totalHours = $timeLog->total_hours ?? 0;
                    }

                    $employeeBreakdown[$logType]['regular_hours'] += $regularHours;
                    $employeeBreakdown[$logType]['overtime_hours'] += $overtimeHours;
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
            if (!$isDynamic) {
                // For processing/approved payrolls, use snapshot data if available
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
                            'rest_pay' => $payBreakdown['rest_pay'] ?? 0,
                            'overtime_pay' => $payBreakdown['overtime_pay'] ?? 0,
                        ];
                    } else {
                        // Fallback to individual snapshot fields
                        $payBreakdownByEmployee[$detail->employee_id] = [
                            'basic_pay' => $snapshot->regular_pay ?? 0,
                            'holiday_pay' => $snapshot->holiday_pay ?? 0,
                            'rest_pay' => 0, // Not available in old snapshots
                            'overtime_pay' => $snapshot->overtime_pay ?? 0,
                        ];
                    }

                    // Log for debugging
                    Log::info("Using snapshot pay breakdown for employee {$detail->employee_id}", [
                        'basic_pay' => $payBreakdownByEmployee[$detail->employee_id]['basic_pay'],
                        'holiday_pay' => $payBreakdownByEmployee[$detail->employee_id]['holiday_pay'],
                        'rest_pay' => $payBreakdownByEmployee[$detail->employee_id]['rest_pay'],
                        'overtime_pay' => $payBreakdownByEmployee[$detail->employee_id]['overtime_pay']
                    ]);
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
                $overtimePayAmount = $breakdown['overtime_hours'] * $hourlyRate * $overtimeMultiplier;

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
                'rest_pay' => $restPay,
                'overtime_pay' => $overtimePay,
            ];
        }

        // Load current dynamic settings for display
        $allowanceSettings = collect();
        $deductionSettings = collect();

        if ($isDynamic) {
            // Get current active settings for draft payrolls
            $allowanceSettings = \App\Models\AllowanceBonusSetting::where('is_active', true)
                ->where('type', 'allowance')
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

        // Calculate total holiday pay for summary
        $totalHolidayPay = array_sum(array_column($payBreakdownByEmployee, 'holiday_pay'));

        return view('payrolls.show', compact(
            'payroll',
            'dtrData',
            'periodDates',
            'allowanceSettings',
            'deductionSettings',
            'isDynamic',
            'timeBreakdowns',
            'payBreakdownByEmployee',
            'totalHolidayPay'
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
        $this->authorize('delete payrolls');

        // Check if user can delete approved payrolls
        $canDeleteApproved = Auth::user()->can('delete approved payrolls');

        // If payroll is approved and user doesn't have permission to delete approved payrolls
        if ($payroll->status === 'approved' && !$canDeleteApproved) {
            return redirect()->route('payrolls.index')
                ->with('error', 'You do not have permission to delete approved payrolls.');
        }

        // If payroll is not approved, use the standard canBeEdited check
        if ($payroll->status !== 'approved' && !$payroll->canBeEdited()) {
            return redirect()->route('payrolls.index')
                ->with('error', 'This payroll cannot be deleted.');
        }

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

        if ($payroll->status !== 'draft') {
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
        $nightDifferentialHours = 0;
        $lateHours = 0;
        $undertimeHours = 0;

        // Process each time log if available
        foreach ($timeLogs as $timeLog) {
            $daysWorked++;
            $regularHours += $timeLog->regular_hours ?? 0;
            $overtimeHours += $timeLog->overtime_hours ?? 0;
            $lateHours += $timeLog->late_hours ?? 0;
            $undertimeHours += $timeLog->undertime_hours ?? 0;

            // Check if it's a holiday or rest day for premium calculations
            if ($timeLog->is_holiday) {
                $holidayHours += $timeLog->total_hours ?? 0;
            }

            // Night differential calculation (10PM - 6AM)
            if ($timeLog->time_in && $timeLog->time_out) {
                $nightDifferentialHours += $this->calculateNightDifferential($timeLog);
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
        $nightDifferentialPay = $nightDifferentialHours * $hourlyRate * 0.10; // 10% night differential

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
                'night_differential_hours' => $nightDifferentialHours,
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
     * Calculate cash advance deductions for the employee
     */
    private function calculateCashAdvanceDeductions(Employee $employee, Payroll $payroll)
    {
        try {
            // For semi-monthly employees, only deduct on the last cutoff period (usually 2nd cutoff)
            if ($employee->pay_schedule === 'semi_monthly') {
                // Check if this is the last cutoff of the month
                $isLastCutoff = $payroll->pay_period_end->day >= 28 || $payroll->pay_period_end->isLastOfMonth();

                if (!$isLastCutoff) {
                    return 0; // Don't deduct on first cutoff
                }
            }

            // Get active cash advances for this employee that should start deduction
            $cashAdvances = \App\Models\CashAdvance::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->where('outstanding_balance', '>', 0)
                ->where(function ($query) use ($payroll) {
                    // Check if this payroll period matches the selected payroll_id for cash advance
                    $query->where('payroll_id', $payroll->id)
                        ->orWhere(function ($q) use ($payroll) {
                            // Fallback for older cash advances without payroll_id
                            $q->whereNull('payroll_id')
                                ->where('first_deduction_date', '<=', $payroll->pay_period_end);
                        });
                })
                ->get();

            $totalDeductions = 0;

            foreach ($cashAdvances as $cashAdvance) {
                // Use installment amount which includes interest
                $deductionAmount = min(
                    $cashAdvance->installment_amount ?? 0,
                    $cashAdvance->outstanding_balance
                );

                if ($deductionAmount > 0) {
                    $totalDeductions += $deductionAmount;

                    // Use the recordPayment method from the CashAdvance model
                    $cashAdvance->recordPayment(
                        $payroll->id,
                        null, // payroll_detail_id will be set later
                        $deductionAmount,
                        "Payroll deduction for period {$payroll->pay_period_start->format('M d')} - {$payroll->pay_period_end->format('M d, Y')}"
                    );
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

        foreach ($payrollDetails as $detail) {
            $employee = $detail->employee;

            // For automated payrolls, we need to calculate the earnings exactly as in draft mode
            // Get the exact calculation used in draft mode
            $payrollCalculation = $this->calculateEmployeePayrollForPeriod(
                $employee,
                $payroll->period_start,
                $payroll->period_end,
                $payroll
            );

            // Use the calculated values from the same method used in draft mode
            $basicPay = $payrollCalculation['basic_salary'] ?? 0;
            $holidayPay = $payrollCalculation['holiday_pay'] ?? 0;
            $overtimePay = $payrollCalculation['overtime_pay'] ?? 0;
            $restPay = $payrollCalculation['rest_pay'] ?? 0; // If available in calculation

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
                'rest_pay' => $restPay,
                'overtime_pay' => $overtimePay,
                'total_calculated' => $basicPay + $holidayPay + $restPay + $overtimePay
            ];

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
                'regular_hours' => $payrollCalculation['regular_hours'] ?? 0,
                'overtime_hours' => $payrollCalculation['overtime_hours'] ?? 0,
                'holiday_hours' => $payrollCalculation['holiday_hours'] ?? 0,
                'night_differential_hours' => $payrollCalculation['night_differential_hours'] ?? 0,
                'regular_pay' => $basicPay, // Use calculated basic pay
                'overtime_pay' => $overtimePay, // Use calculated overtime pay
                'holiday_pay' => $holidayPay, // Use calculated holiday pay
                'night_differential_pay' => $payrollCalculation['night_differential_pay'] ?? 0,
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
                'settings_snapshot' => array_merge($settingsSnapshot, ['pay_breakdown' => $payBreakdown]),
                'remarks' => 'Snapshot created at ' . now()->format('Y-m-d H:i:s') . ' - Captures exact draft calculations',
            ]);

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
            foreach ($deductionSettings as $setting) {
                $amount = $setting->calculateDeduction(
                    $detail->regular_pay ?? 0,
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
        $deductionSettings = \App\Models\DeductionTaxSetting::active()
            ->orderBy('sort_order')
            ->get();

        $totalHolidayPay = $holidayPay;

        return view('payrolls.show', compact(
            'draftPayroll',
            'dtrData',
            'periodDates',
            'allowanceSettings',
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
                    'overtime_pay' => $detail->overtime_pay ?? 0,
                    'rest_day_pay' => 0, // You may need to add this field to PayrollDetail if needed
                ];
            } else {
                // For draft payrolls, calculate dynamically from time logs
                $employeeBreakdown = $timeBreakdowns[$detail->employee_id] ?? [];
                $hourlyRate = $detail->employee->hourly_rate ?? 0;

                $basicPay = 0; // Regular workday pay only
                $holidayPay = 0; // All holiday-related pay

                foreach ($employeeBreakdown as $logType => $breakdown) {
                    $rateConfig = $breakdown['rate_config'];
                    if (!$rateConfig) continue;

                    // Calculate pay amounts using rate multipliers
                    $regularMultiplier = $rateConfig->regular_rate_multiplier ?? 1.0;
                    $overtimeMultiplier = $rateConfig->overtime_rate_multiplier ?? 1.25;

                    $regularPay = $breakdown['regular_hours'] * $hourlyRate * $regularMultiplier;
                    $overtimePay = $breakdown['overtime_hours'] * $hourlyRate * $overtimeMultiplier;

                    if ($logType === 'regular_workday') {
                        $basicPay += $regularPay; // Only add regular pay to basic pay, not overtime
                    } elseif (in_array($logType, ['special_holiday', 'regular_holiday', 'rest_day_regular_holiday', 'rest_day_special_holiday'])) {
                        $holidayPay += ($regularPay + $overtimePay); // Holiday pay can include both regular and OT
                    }
                }

                $payBreakdownByEmployee[$detail->employee_id] = [
                    'basic_pay' => $basicPay,
                    'holiday_pay' => $holidayPay,
                ];
            }
        }

        $totalHolidayPay = array_sum(array_column($payBreakdownByEmployee, 'holiday_pay'));

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
            'totalHolidayPay'
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
        $draftPayrollDetail->regular_pay = $payrollCalculation['regular_pay'] ?? 0;
        $draftPayrollDetail->overtime_pay = $payrollCalculation['overtime_pay'] ?? 0;
        $draftPayrollDetail->holiday_pay = $payrollCalculation['holiday_pay'] ?? 0;
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
                $totalHours = $dynamicCalculation['total_hours'];

                $employeeBreakdown[$logType]['regular_hours'] += $regularHours;
                $employeeBreakdown[$logType]['overtime_hours'] += $overtimeHours;
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
        $deductionSettings = \App\Models\DeductionTaxSetting::active()
            ->orderBy('sort_order')
            ->get();

        $totalHolidayPay = $holidayPay;

        return view('payrolls.show', compact(
            'draftPayroll',
            'dtrData',
            'periodDates',
            'allowanceSettings',
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
                'overtime_hours' => $timeLog->overtime_hours ?? 0,
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
}
