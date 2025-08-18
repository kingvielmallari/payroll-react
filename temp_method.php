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