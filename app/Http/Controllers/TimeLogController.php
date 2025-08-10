<?php

namespace App\Http\Controllers;

use App\Models\TimeLog;
use App\Models\Employee;
use App\Models\User;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DTRImport;
use Carbon\Carbon;

class TimeLogController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of DTR batches.
     */
    public function index(Request $request)
    {
        $this->authorize('view time logs');

        // Build query for DTR batches with relationships
        $query = DB::table('d_t_r_s as dtr')
                   ->join('employees as e', 'dtr.employee_id', '=', 'e.id')
                   ->join('users as u', 'e.user_id', '=', 'u.id')
                   ->leftJoin('departments as d', 'e.department_id', '=', 'd.id')
                   ->leftJoin('payrolls as p', 'dtr.payroll_id', '=', 'p.id')
                   ->select([
                       'dtr.id',
                       'dtr.employee_id',
                       'dtr.payroll_id',
                       'dtr.period_start',
                       'dtr.period_end',
                       'dtr.total_regular_hours',
                       'dtr.total_overtime_hours',
                       'dtr.total_late_hours',
                       'dtr.regular_days',
                       'dtr.status',
                       'dtr.created_at',
                       'dtr.updated_at',
                       'e.first_name',
                       'e.last_name',
                       'e.employee_number',
                       'u.name as user_name',
                       'u.email',
                       'd.name as department_name',
                       'p.payroll_type',
                       'p.period_label'
                   ])
                   ->orderBy('dtr.period_start', 'desc')
                   ->orderBy('dtr.created_at', 'desc');

        // Filter by employee if specified
        if ($request->filled('employee_id')) {
            $query->where('dtr.employee_id', $request->employee_id);
        }

        // Filter by date range if specified
        if ($request->filled('start_date')) {
            $query->where('dtr.period_start', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('dtr.period_end', '<=', $request->end_date);
        }

        // Filter by department if specified
        if ($request->filled('department_id')) {
            $query->where('e.department_id', $request->department_id);
        }

        // Paginate results
        $dtrBatches = $query->paginate(20)->withQueryString();

        // Get employees for filter dropdown
        $employees = Employee::with('user')
                            ->where('employment_status', 'active')
                            ->orderBy('first_name')
                            ->get();

        // Get departments for filter dropdown
        $departments = DB::table('departments')->orderBy('name')->get();

        // Get statistics
        $totalDTRBatches = DB::table('d_t_r_s')->count();
        $totalEmployeesWithDTR = DB::table('d_t_r_s')->distinct('employee_id')->count();
        $totalRegularHours = DB::table('d_t_r_s')->sum('total_regular_hours');

        return view('time-logs.index', compact('dtrBatches', 'employees', 'departments', 'totalDTRBatches', 'totalEmployeesWithDTR', 'totalRegularHours'));
    }

    /**
     * Show the form for creating a new time log.
     */
    public function create()
    {
        $this->authorize('create time logs');

        $employees = Employee::with('user')
                            ->where('employment_status', 'active')
                            ->orderBy('first_name')
                            ->get();

        return view('time-logs.create', compact('employees'));
    }

    /**
     * Store a newly created time log.
     */
    public function store(Request $request)
    {
        $this->authorize('create time logs');

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'log_date' => 'required|date|before_or_equal:today',
            'time_in' => 'required|date_format:H:i',
            'time_out' => 'nullable|date_format:H:i|after:time_in',
            'break_in' => 'nullable|date_format:H:i',
            'break_out' => 'nullable|date_format:H:i|after:break_in',
            'log_type' => 'required|in:regular,overtime,holiday,rest_day',
            'remarks' => 'nullable|string|max:500',
            'is_holiday' => 'boolean',
            'is_rest_day' => 'boolean',
        ]);

        // Check if time log already exists for this employee and date
        $existingLog = TimeLog::where('employee_id', $validated['employee_id'])
                             ->where('log_date', $validated['log_date'])
                             ->first();

        if ($existingLog) {
            return back()->withErrors(['error' => 'Time log already exists for this employee on this date.'])
                        ->withInput();
        }

        // Calculate hours
        $timeIn = Carbon::createFromFormat('H:i', $validated['time_in']);
        $timeOut = $validated['time_out'] ? Carbon::createFromFormat('H:i', $validated['time_out']) : null;
        
        $breakIn = $validated['break_in'] ? Carbon::createFromFormat('H:i', $validated['break_in']) : null;
        $breakOut = $validated['break_out'] ? Carbon::createFromFormat('H:i', $validated['break_out']) : null;

        $totalHours = 0;
        $regularHours = 0;
        $overtimeHours = 0;
        $lateHours = 0;
        $undertimeHours = 0;

        if ($timeOut) {
            $totalMinutes = $timeIn->diffInMinutes($timeOut);
            
            // Subtract break time if both break_in and break_out are provided
            if ($breakIn && $breakOut) {
                $breakMinutes = $breakIn->diffInMinutes($breakOut);
                $totalMinutes -= $breakMinutes;
            }

            $totalHours = $totalMinutes / 60;

            // Calculate regular and overtime hours
            $standardHours = 8; // Standard 8-hour workday
            if ($totalHours <= $standardHours) {
                $regularHours = $totalHours;
            } else {
                $regularHours = $standardHours;
                $overtimeHours = $totalHours - $standardHours;
            }

            // Calculate late hours (if time_in is after 8:00 AM)
            $standardTimeIn = Carbon::createFromFormat('H:i', '08:00');
            if ($timeIn->greaterThan($standardTimeIn)) {
                $lateHours = $standardTimeIn->diffInMinutes($timeIn) / 60;
            }

            // Calculate undertime hours (if total hours < 8)
            if ($totalHours < $standardHours) {
                $undertimeHours = $standardHours - $totalHours;
            }
        }

        $timeLog = TimeLog::create([
            'employee_id' => $validated['employee_id'],
            'log_date' => $validated['log_date'],
            'time_in' => $validated['time_in'],
            'time_out' => $validated['time_out'],
            'break_in' => $validated['break_in'],
            'break_out' => $validated['break_out'],
            'total_hours' => $totalHours,
            'regular_hours' => $regularHours,
            'overtime_hours' => $overtimeHours,
            'late_hours' => $lateHours,
            'undertime_hours' => $undertimeHours,
            'log_type' => $validated['log_type'],
            'remarks' => $validated['remarks'],
            'is_holiday' => $validated['is_holiday'] ?? false,
            'is_rest_day' => $validated['is_rest_day'] ?? false,
            'status' => 'pending',
        ]);

        return redirect()->route('time-logs.show', $timeLog)
                        ->with('success', 'Time log created successfully!');
    }

    /**
     * Display the specified DTR batch.
     */
    public function show(TimeLog $timeLog)
    {
        $this->authorize('view time logs');

        $timeLog->load(['employee.user', 'employee.department', 'approver']);

        return view('time-logs.show', compact('timeLog'));
    }

    /**
     * Display detailed DTR records for a specific batch.
     */
    public function showDTRBatch($dtrId)
    {
        $this->authorize('view time logs');

        // Get DTR batch details
        $dtrBatch = DB::table('d_t_r_s as dtr')
                      ->join('employees as e', 'dtr.employee_id', '=', 'e.id')
                      ->join('users as u', 'e.user_id', '=', 'u.id')
                      ->leftJoin('departments as d', 'e.department_id', '=', 'd.id')
                      ->leftJoin('payrolls as p', 'dtr.payroll_id', '=', 'p.id')
                      ->select([
                          'dtr.*',
                          'e.first_name',
                          'e.last_name',
                          'e.employee_number',
                          'u.name as user_name',
                          'u.email',
                          'd.name as department_name',
                          'p.payroll_type',
                          'p.period_label'
                      ])
                      ->where('dtr.id', $dtrId)
                      ->first();

        if (!$dtrBatch) {
            return redirect()->route('time-logs.index')->with('error', 'DTR batch not found.');
        }

        // Parse DTR data
        $dtrData = json_decode($dtrBatch->dtr_data, true) ?? [];

        // Get individual time logs for the period (if any exist)
        $timeLogs = TimeLog::where('employee_id', $dtrBatch->employee_id)
                           ->whereBetween('log_date', [$dtrBatch->period_start, $dtrBatch->period_end])
                           ->orderBy('log_date')
                           ->get();

        // Create period dates array for display
        $periodDates = [];
        $current = Carbon::parse($dtrBatch->period_start);
        $end = Carbon::parse($dtrBatch->period_end);
        
        while ($current->lte($end)) {
            $dateStr = $current->format('Y-m-d');
            $dayData = $dtrData[$dateStr] ?? [
                'date' => $dateStr,
                'day_name' => $current->format('l'),
                'is_weekend' => $current->isWeekend(),
                'time_in' => null,
                'time_out' => null,
                'break_start' => null,
                'break_end' => null,
                'regular_hours' => 0,
                'overtime_hours' => 0,
                'late_minutes' => 0,
                'status' => 'no_record',
                'remarks' => null
            ];
            
            $periodDates[] = array_merge($dayData, [
                'carbon' => $current->copy(),
                'formatted' => $current->format('M d')
            ]);
            
            $current->addDay();
        }

        return view('time-logs.dtr-batch', compact('dtrBatch', 'periodDates', 'timeLogs'));
    }

    /**
     * Show the form for editing the specified time log.
     */
    public function edit(TimeLog $timeLog)
    {
        $this->authorize('edit time logs');

        if ($timeLog->status === 'approved') {
            return redirect()->route('time-logs.show', $timeLog)
                           ->with('error', 'Approved time logs cannot be edited.');
        }

        $employees = Employee::with('user')
                            ->where('employment_status', 'active')
                            ->orderBy('first_name')
                            ->get();

        return view('time-logs.edit', compact('timeLog', 'employees'));
    }

    /**
     * Update the specified time log.
     */
    public function update(Request $request, TimeLog $timeLog)
    {
        $this->authorize('edit time logs');

        if ($timeLog->status === 'approved') {
            return redirect()->route('time-logs.show', $timeLog)
                           ->with('error', 'Approved time logs cannot be edited.');
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'log_date' => 'required|date|before_or_equal:today',
            'time_in' => 'required|date_format:H:i',
            'time_out' => 'nullable|date_format:H:i|after:time_in',
            'break_in' => 'nullable|date_format:H:i',
            'break_out' => 'nullable|date_format:H:i|after:break_in',
            'log_type' => 'required|in:regular,overtime,holiday,rest_day',
            'remarks' => 'nullable|string|max:500',
            'is_holiday' => 'boolean',
            'is_rest_day' => 'boolean',
        ]);

        // Recalculate hours (same logic as store method)
        $timeIn = Carbon::createFromFormat('H:i', $validated['time_in']);
        $timeOut = $validated['time_out'] ? Carbon::createFromFormat('H:i', $validated['time_out']) : null;
        
        $breakIn = $validated['break_in'] ? Carbon::createFromFormat('H:i', $validated['break_in']) : null;
        $breakOut = $validated['break_out'] ? Carbon::createFromFormat('H:i', $validated['break_out']) : null;

        $totalHours = 0;
        $regularHours = 0;
        $overtimeHours = 0;
        $lateHours = 0;
        $undertimeHours = 0;

        if ($timeOut) {
            $totalMinutes = $timeIn->diffInMinutes($timeOut);
            
            if ($breakIn && $breakOut) {
                $breakMinutes = $breakIn->diffInMinutes($breakOut);
                $totalMinutes -= $breakMinutes;
            }

            $totalHours = $totalMinutes / 60;

            $standardHours = 8;
            if ($totalHours <= $standardHours) {
                $regularHours = $totalHours;
            } else {
                $regularHours = $standardHours;
                $overtimeHours = $totalHours - $standardHours;
            }

            $standardTimeIn = Carbon::createFromFormat('H:i', '08:00');
            if ($timeIn->greaterThan($standardTimeIn)) {
                $lateHours = $standardTimeIn->diffInMinutes($timeIn) / 60;
            }

            if ($totalHours < $standardHours) {
                $undertimeHours = $standardHours - $totalHours;
            }
        }

        $timeLog->update([
            'employee_id' => $validated['employee_id'],
            'log_date' => $validated['log_date'],
            'time_in' => $validated['time_in'],
            'time_out' => $validated['time_out'],
            'break_in' => $validated['break_in'],
            'break_out' => $validated['break_out'],
            'total_hours' => $totalHours,
            'regular_hours' => $regularHours,
            'overtime_hours' => $overtimeHours,
            'late_hours' => $lateHours,
            'undertime_hours' => $undertimeHours,
            'log_type' => $validated['log_type'],
            'remarks' => $validated['remarks'],
            'is_holiday' => $validated['is_holiday'] ?? false,
            'is_rest_day' => $validated['is_rest_day'] ?? false,
        ]);

        return redirect()->route('time-logs.show', $timeLog)
                        ->with('success', 'Time log updated successfully!');
    }

    /**
     * Remove the specified time log.
     */
    public function destroy(TimeLog $timeLog)
    {
        $this->authorize('delete time logs');

        // Only System Admin and HR Head can delete approved time logs
        $user = Auth::user();
        if ($timeLog->status === 'approved' && !$user->hasRole(['System Admin', 'HR Head'])) {
            return redirect()->route('time-logs.index')
                           ->with('error', 'Only System Admin or HR Head can delete approved time logs.');
        }

        $timeLog->delete();

        return redirect()->route('time-logs.index')
                        ->with('success', 'Time log deleted successfully!');
    }

    /**
     * Remove the specified DTR batch.
     */
    public function destroyDTRBatch($dtrId)
    {
        $this->authorize('delete time logs');

        $dtrBatch = DB::table('d_t_r_s')->where('id', $dtrId)->first();
        
        if (!$dtrBatch) {
            return redirect()->route('time-logs.index')->with('error', 'DTR batch not found.');
        }

        // Delete related time logs first
        TimeLog::where('employee_id', $dtrBatch->employee_id)
                ->whereBetween('log_date', [$dtrBatch->period_start, $dtrBatch->period_end])
                ->delete();

        // Delete DTR batch
        DB::table('d_t_r_s')->where('id', $dtrId)->delete();

        return redirect()->route('time-logs.index')->with('success', 'DTR batch deleted successfully!');
    }

    /**
     * Show payroll for the DTR batch.
     */
    public function showPayroll($dtrId)
    {
        $this->authorize('view payrolls');

        $dtrBatch = DB::table('d_t_r_s')->where('id', $dtrId)->first();
        
        if (!$dtrBatch || !$dtrBatch->payroll_id) {
            return redirect()->route('time-logs.index')->with('error', 'Payroll not found for this DTR batch.');
        }

        return redirect()->route('payrolls.show', $dtrBatch->payroll_id);
    }

    /**
     * Show DTR import form.
     */
    public function importForm()
    {
        $this->authorize('import time logs');
        
        return view('time-logs.import');
    }

    /**
     * Import DTR from Excel/CSV file.
     */
    public function import(Request $request)
    {
        $this->authorize('import time logs');

        $request->validate([
            'dtr_file' => 'required|file|mimes:xlsx,xls,csv|max:2048',
            'overwrite_existing' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $import = new DTRImport($request->boolean('overwrite_existing'));
            Excel::import($import, $request->file('dtr_file'));

            DB::commit();

            $importedCount = $import->getImportedCount();
            $skippedCount = $import->getSkippedCount();
            $errorCount = $import->getErrorCount();

            $message = "DTR import completed! Imported: {$importedCount}, Skipped: {$skippedCount}, Errors: {$errorCount}";
            
            if ($errorCount > 0) {
                $errors = $import->getErrors();
                return redirect()->route('time-logs.index')
                               ->with('warning', $message)
                               ->with('import_errors', $errors);
            }

            return redirect()->route('time-logs.index')
                           ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('DTR Import Error: ' . $e->getMessage());
            
            return back()->withErrors(['error' => 'Failed to import DTR: ' . $e->getMessage()])
                        ->withInput();
        }
    }

    /**
     * Export DTR template.
     */
    public function exportTemplate()
    {
        $this->authorize('import time logs');

        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="dtr_template.xlsx"',
        ];

        return Excel::download(new \App\Exports\DTRTemplateExport(), 'dtr_template.xlsx', \Maatwebsite\Excel\Excel::XLSX, $headers);
    }

    /**
     * Show employee's own time logs.
     */
    public function myTimeLogs(Request $request)
    {
        $this->authorize('view own time logs');

        $employee = Employee::where('user_id', Auth::id())->first();
        
        if (!$employee) {
            return redirect()->route('dashboard')
                           ->with('error', 'Employee profile not found.');
        }

        $query = TimeLog::where('employee_id', $employee->id);

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('log_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('log_date', '<=', $request->end_date);
        }

        $timeLogs = $query->orderBy('log_date', 'desc')
                          ->paginate(20);

        return view('time-logs.my-time-logs', compact('timeLogs', 'employee'));
    }

    /**
     * Store employee's own time log.
     */
    public function storeMyTimeLog(Request $request)
    {
        $this->authorize('create own time logs');

        $employee = Employee::where('user_id', Auth::id())->first();
        
        if (!$employee) {
            return redirect()->route('dashboard')
                           ->with('error', 'Employee profile not found.');
        }

        $validated = $request->validate([
            'log_date' => 'required|date|before_or_equal:today',
            'time_in' => 'required|date_format:H:i',
            'time_out' => 'nullable|date_format:H:i|after:time_in',
            'break_in' => 'nullable|date_format:H:i',
            'break_out' => 'nullable|date_format:H:i|after:break_in',
            'remarks' => 'nullable|string|max:500',
        ]);

        // Check if time log already exists
        $existingLog = TimeLog::where('employee_id', $employee->id)
                             ->where('log_date', $validated['log_date'])
                             ->first();

        if ($existingLog) {
            return back()->withErrors(['error' => 'Time log already exists for this date.'])
                        ->withInput();
        }

        // Calculate hours (same logic as store method)
        $timeIn = Carbon::createFromFormat('H:i', $validated['time_in']);
        $timeOut = $validated['time_out'] ? Carbon::createFromFormat('H:i', $validated['time_out']) : null;
        
        $breakIn = $validated['break_in'] ? Carbon::createFromFormat('H:i', $validated['break_in']) : null;
        $breakOut = $validated['break_out'] ? Carbon::createFromFormat('H:i', $validated['break_out']) : null;

        $totalHours = 0;
        $regularHours = 0;
        $overtimeHours = 0;
        $lateHours = 0;
        $undertimeHours = 0;

        if ($timeOut) {
            $totalMinutes = $timeIn->diffInMinutes($timeOut);
            
            if ($breakIn && $breakOut) {
                $breakMinutes = $breakIn->diffInMinutes($breakOut);
                $totalMinutes -= $breakMinutes;
            }

            $totalHours = $totalMinutes / 60;

            $standardHours = 8;
            if ($totalHours <= $standardHours) {
                $regularHours = $totalHours;
            } else {
                $regularHours = $standardHours;
                $overtimeHours = $totalHours - $standardHours;
            }

            $standardTimeIn = Carbon::createFromFormat('H:i', '08:00');
            if ($timeIn->greaterThan($standardTimeIn)) {
                $lateHours = $standardTimeIn->diffInMinutes($timeIn) / 60;
            }

            if ($totalHours < $standardHours) {
                $undertimeHours = $standardHours - $totalHours;
            }
        }

        TimeLog::create([
            'employee_id' => $employee->id,
            'log_date' => $validated['log_date'],
            'time_in' => $validated['time_in'],
            'time_out' => $validated['time_out'],
            'break_in' => $validated['break_in'],
            'break_out' => $validated['break_out'],
            'total_hours' => $totalHours,
            'regular_hours' => $regularHours,
            'overtime_hours' => $overtimeHours,
            'late_hours' => $lateHours,
            'undertime_hours' => $undertimeHours,
            'log_type' => 'regular',
            'remarks' => $validated['remarks'],
            'status' => 'pending',
        ]);

        return redirect()->route('my-time-logs')
                        ->with('success', 'Time log submitted successfully!');
    }

    /**
     * Show DTR for a specific employee and period
     */
    public function showDTR(Request $request, Employee $employee)
    {
        $this->authorize('view time logs');

        // Get payroll settings to determine current period
        $payrollSettings = \App\Models\PayrollScheduleSetting::first();
        
        if (!$payrollSettings) {
            return redirect()->back()->with('error', 'Payroll schedule settings not configured.');
        }

        $currentPeriod = $this->getCurrentPayrollPeriod($payrollSettings);
        $dtrData = $this->generateDTRData($employee, $currentPeriod, $payrollSettings);

        return view('time-logs.show-dtr', compact('employee', 'dtrData', 'currentPeriod', 'payrollSettings'));
    }

    /**
     * Show simple DTR interface with draggable clock.
     */
    public function simpleDTR(Request $request, Employee $employee)
    {
        $this->authorize('view time logs');

        // Get payroll settings to determine current period
        $payrollSettings = \App\Models\PayrollScheduleSetting::first();
        
        if (!$payrollSettings) {
            return redirect()->back()->with('error', 'Payroll schedule settings not configured.');
        }

        $currentPeriod = $this->getCurrentPayrollPeriod($payrollSettings);
        $dtrData = $this->generateDTRData($employee, $currentPeriod, $payrollSettings);

        return view('time-logs.simple-dtr', compact('employee', 'dtrData', 'currentPeriod', 'payrollSettings'));
    }

    /**
     * Update or create time entry via AJAX
     */
    public function updateTimeEntry(Request $request)
    {
        $this->authorize('create time logs');

        try {
            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'log_date' => 'required|date',
                'time_in' => 'nullable|date_format:H:i',
                'time_out' => 'nullable|date_format:H:i',
                'break_in' => 'nullable|date_format:H:i',
                'break_out' => 'nullable|date_format:H:i',
                'remarks' => 'nullable|string|max:500',
                'log_type' => 'required|in:regular,overtime,holiday,rest_day',
                'is_holiday' => 'boolean',
                'is_rest_day' => 'boolean',
            ]);

            // Find existing time log or create new one
            $timeLog = TimeLog::updateOrCreate(
                [
                    'employee_id' => $validated['employee_id'],
                    'log_date' => $validated['log_date'],
                ],
                [
                    'time_in' => $validated['time_in'],
                    'time_out' => $validated['time_out'],
                    'break_in' => $validated['break_in'],
                    'break_out' => $validated['break_out'],
                    'log_type' => $validated['log_type'],
                    'remarks' => $validated['remarks'],
                    'is_holiday' => $validated['is_holiday'] ?? false,
                    'is_rest_day' => $validated['is_rest_day'] ?? false,
                    'status' => 'approved', // Auto-approve DTR entries
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                ]
            );

            // Calculate hours
            $this->calculateHours($timeLog);

            return response()->json(['success' => true, 'message' => 'Time entry updated successfully']);

        } catch (\Exception $e) {
            Log::error('Error updating time entry: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error updating time entry'], 500);
        }
    }

    /**
     * Get current payroll period
     */
    private function getCurrentPayrollPeriod($payrollSettings)
    {
        $today = Carbon::now();
        
        if ($payrollSettings->frequency === 'semi_monthly') {
            $day = $today->day;
            
            if ($day <= 15) {
                // First half of the month
                $startDate = $today->copy()->startOfMonth();
                $endDate = $today->copy()->startOfMonth()->addDays(14);
                $payDate = $today->copy()->startOfMonth()->addDays(19); // 20th
            } else {
                // Second half of the month
                $startDate = $today->copy()->startOfMonth()->addDays(15);
                $endDate = $today->copy()->endOfMonth();
                $payDate = $today->copy()->addMonth()->startOfMonth()->addDays(4); // 5th of next month
            }
        } else {
            // Monthly
            $startDate = $today->copy()->startOfMonth();
            $endDate = $today->copy()->endOfMonth();
            $payDate = $today->copy()->addMonth()->startOfMonth()->addDays(4); // 5th of next month
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'pay_date' => $payDate,
            'period_label' => $startDate->format('M d') . ' - ' . $endDate->format('M d, Y'),
            'pay_label' => 'Pay Date: ' . $payDate->format('M d, Y'),
        ];
    }

    /**
     * Generate DTR data for the period
     */
    private function generateDTRData(Employee $employee, $currentPeriod, $payrollSettings)
    {
        $startDate = $currentPeriod['start_date'];
        $endDate = $currentPeriod['end_date'];
        
        // Get all time logs for the period
        $timeLogs = TimeLog::where('employee_id', $employee->id)
            ->whereBetween('log_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->keyBy('log_date');

        // Get holidays for the period
        $holidays = \App\Models\Holiday::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->keyBy('date');

        $dtrData = [];
        
        // Generate data for each day in the period
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->format('Y-m-d');
            $timeLog = $timeLogs->get($dateStr);
            $holiday = $holidays->get($dateStr);
            
            $isWeekend = $currentDate->isWeekend();
            
            $dayData = [
                'date' => $currentDate->copy(),
                'day' => $currentDate->format('d'),
                'day_name' => $currentDate->format('l'),
                'is_weekend' => $isWeekend,
                'is_holiday' => $holiday ? $holiday->name : null,
                'time_log' => $timeLog,
                'time_in' => $timeLog ? $timeLog->time_in : null,
                'time_out' => $timeLog ? $timeLog->time_out : null,
                'break_in' => $timeLog ? $timeLog->break_in : null,
                'break_out' => $timeLog ? $timeLog->break_out : null,
                'remarks' => $timeLog ? $timeLog->remarks : null,
                'regular_hours' => $timeLog ? $timeLog->regular_hours : 0,
                'overtime_hours' => $timeLog ? $timeLog->overtime_hours : 0,
                'late_hours' => $timeLog ? $timeLog->late_hours : 0,
                'total_hours' => $timeLog ? $timeLog->total_hours : 0,
            ];
            
            $dtrData[] = $dayData;
            $currentDate->addDay();
        }
        
        return $dtrData;
    }

    /**
     * Generate DTR data for a specific date range (used by payroll DTR creation)
     */
    private function generateDTRDataForPeriod(Employee $employee, $startDateStr, $endDateStr)
    {
        $startDate = Carbon::parse($startDateStr);
        $endDate = Carbon::parse($endDateStr);
        
        Log::info('Generating DTR data for period', [
            'employee_id' => $employee->id,
            'employee_name' => $employee->user->name,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d')
        ]);
        
        // Get all time logs for the period - ensure we include all required fields
        $timeLogs = TimeLog::where('employee_id', $employee->id)
            ->whereBetween('log_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('log_date')
            ->get()
            ->keyBy(function ($timeLog) {
                return Carbon::parse($timeLog->log_date)->format('Y-m-d');
            });

        Log::info('Found existing time logs', [
            'count' => $timeLogs->count(),
            'dates' => $timeLogs->keys()->toArray()
        ]);

        // Get holidays for the period
        $holidays = \App\Models\Holiday::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->keyBy('date');

        $dtrData = [];
        
        // Generate data for each day in the period
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->format('Y-m-d');
            $timeLog = $timeLogs->get($dateStr);
            $holiday = $holidays->get($dateStr);
            
            $isWeekend = $currentDate->isWeekend();
            
            // Ensure time values are properly formatted
            $timeIn = null;
            $timeOut = null;
            $breakIn = null;
            $breakOut = null;
            
            if ($timeLog) {
                $timeIn = $timeLog->time_in ? Carbon::parse($timeLog->time_in) : null;
                $timeOut = $timeLog->time_out ? Carbon::parse($timeLog->time_out) : null;
                $breakIn = $timeLog->break_in ? Carbon::parse($timeLog->break_in) : null;
                $breakOut = $timeLog->break_out ? Carbon::parse($timeLog->break_out) : null;
                
                Log::debug('Time log found for date', [
                    'date' => $dateStr,
                    'time_in' => $timeIn ? $timeIn->format('H:i') : null,
                    'time_out' => $timeOut ? $timeOut->format('H:i') : null,
                    'break_in' => $breakIn ? $breakIn->format('H:i') : null,
                    'break_out' => $breakOut ? $breakOut->format('H:i') : null,
                ]);
            }
            
            $dayData = [
                'date' => $currentDate->copy(),
                'day' => $currentDate->format('d'),
                'day_name' => $currentDate->format('l'),
                'is_weekend' => $isWeekend,
                'is_holiday' => $holiday ? $holiday->name : null,
                'time_log' => $timeLog,
                'time_in' => $timeIn,
                'time_out' => $timeOut,
                'break_in' => $breakIn,
                'break_out' => $breakOut,
                'remarks' => $timeLog ? $timeLog->remarks : null,
                'regular_hours' => $timeLog ? ($timeLog->regular_hours ?? 0) : 0,
                'overtime_hours' => $timeLog ? ($timeLog->overtime_hours ?? 0) : 0,
                'late_hours' => $timeLog ? ($timeLog->late_hours ?? 0) : 0,
                'total_hours' => $timeLog ? ($timeLog->total_hours ?? 0) : 0,
            ];
            
            $dtrData[] = $dayData;
            $currentDate->addDay();
        }
        
        Log::info('Generated DTR data', [
            'total_days' => count($dtrData),
            'days_with_time_logs' => count(array_filter($dtrData, function($day) { return $day['time_log'] !== null; }))
        ]);
        
        return $dtrData;
    }

    /**
     * Calculate hours for a time log entry
     */
    private function calculateHours(TimeLog $timeLog)
    {
        if (!$timeLog->time_in || !$timeLog->time_out) {
            $timeLog->update([
                'regular_hours' => 0,
                'overtime_hours' => 0,
                'late_hours' => 0,
                'total_hours' => 0,
            ]);
            return;
        }

        $timeIn = Carbon::parse($timeLog->log_date . ' ' . $timeLog->time_in);
        $timeOut = Carbon::parse($timeLog->log_date . ' ' . $timeLog->time_out);
        
        // Handle next day time out
        if ($timeOut->lt($timeIn)) {
            $timeOut->addDay();
        }

        $totalMinutes = $timeOut->diffInMinutes($timeIn);
        
        // Subtract break time if both break_in and break_out are provided
        if ($timeLog->break_in && $timeLog->break_out) {
            $breakIn = Carbon::parse($timeLog->log_date . ' ' . $timeLog->break_in);
            $breakOut = Carbon::parse($timeLog->log_date . ' ' . $timeLog->break_out);
            
            if ($breakOut->gt($breakIn)) {
                $breakMinutes = $breakOut->diffInMinutes($breakIn);
                $totalMinutes -= $breakMinutes;
            }
        }

        $totalHours = $totalMinutes / 60;
        
        // Standard work hours (8 hours)
        $standardHours = 8;
        
        $regularHours = min($totalHours, $standardHours);
        $overtimeHours = max(0, $totalHours - $standardHours);
        
        // Calculate late hours (assuming standard start time is 8:00 AM)
        $standardStartTime = Carbon::parse($timeLog->log_date . ' 08:00:00');
        $lateMinutes = max(0, $timeIn->diffInMinutes($standardStartTime));
        $lateHours = $lateMinutes / 60;

        $timeLog->update([
            'regular_hours' => round($regularHours, 2),
            'overtime_hours' => round($overtimeHours, 2),
            'late_hours' => round($lateHours, 2),
            'total_hours' => round($totalHours, 2),
        ]);
    }

    /**
     * Show bulk time log creation form for an employee's payroll period.
     */
    public function createBulk(Request $request)
    {
        $this->authorize('create time logs');

        $employees = Employee::with('user')
                            ->where('employment_status', 'active')
                            ->orderBy('first_name')
                            ->get();

        $selectedEmployee = null;
        $dtrData = [];
        $currentPeriod = null;

        if ($request->filled('employee_id')) {
            Log::info('Bulk creation: Employee ID selected', ['employee_id' => $request->employee_id]);
            
            $selectedEmployee = Employee::with('user')->findOrFail($request->employee_id);
            Log::info('Bulk creation: Employee found', ['employee' => $selectedEmployee->first_name . ' ' . $selectedEmployee->last_name]);
            
            // Get payroll settings to determine current period
            $payrollSettings = \App\Models\PayrollScheduleSetting::first();
            Log::info('Bulk creation: PayrollSettings check', ['has_settings' => $payrollSettings ? 'yes' : 'no']);
            
            if ($payrollSettings) {
                $currentPeriod = $this->getCurrentPayrollPeriod($payrollSettings);
                $dtrData = $this->generateDTRData($selectedEmployee, $currentPeriod, $payrollSettings);
            } else {
                // Fallback: Use current semi-monthly period if no settings exist
                Log::info('Bulk creation: Using fallback period generation');
                $currentPeriod = $this->getDefaultPayrollPeriod();
                $dtrData = $this->generateDTRDataWithoutSettings($selectedEmployee, $currentPeriod);
            }
            
            Log::info('Bulk creation: Generated data', [
                'period' => $currentPeriod['period_label'] ?? 'none',
                'dtr_data_count' => count($dtrData)
            ]);
        }

        return view('time-logs.create-bulk', compact('employees', 'selectedEmployee', 'dtrData', 'currentPeriod'));
    }

    /**
     * Show bulk time log creation form for a specific employee (from payroll context)
     */
    public function createBulkForEmployee(Request $request, $employee_id)
    {
        $this->authorize('create time logs');

        $selectedEmployee = Employee::with(['user', 'daySchedule', 'timeSchedule'])->findOrFail($employee_id);
        
        // Get period data from request (passed from payroll)
        $periodStart = $request->input('period_start');
        $periodEnd = $request->input('period_end');
        $payrollId = $request->input('payroll_id');
        
        // Generate DTR data for the specific period
        $currentPeriod = [
            'start' => $periodStart,
            'end' => $periodEnd,
            'period_label' => date('M d', strtotime($periodStart)) . ' - ' . date('M d, Y', strtotime($periodEnd))
        ];
        
        $dtrData = $this->generateDTRDataForPeriod($selectedEmployee, $periodStart, $periodEnd);
        
        return view('time-logs.create-bulk-employee', compact(
            'selectedEmployee', 
            'dtrData', 
            'currentPeriod',
            'payrollId',
            'periodStart',
            'periodEnd'
        ));
    }

    /**
     * Store bulk time logs for an employee's payroll period.
     */
    public function storeBulk(Request $request)
    {
        Log::info('Bulk time log storage started', [
            'employee_id' => $request->employee_id,
            'time_logs_count' => $request->has('time_logs') ? count($request->time_logs) : 0
        ]);

        $this->authorize('create time logs');

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'time_logs' => 'required|array',
            'time_logs.*.log_date' => 'required|date',
            'time_logs.*.time_in' => 'nullable|date_format:H:i',
            'time_logs.*.time_out' => 'nullable|date_format:H:i',
            'time_logs.*.break_in' => 'nullable|date_format:H:i',
            'time_logs.*.break_out' => 'nullable|date_format:H:i',
            'time_logs.*.log_type' => 'required|in:regular,overtime,holiday,rest_day',
            'time_logs.*.remarks' => 'nullable|string|max:500',
            'time_logs.*.is_holiday' => 'boolean',
            'time_logs.*.is_rest_day' => 'boolean',
        ]);

        Log::info('Bulk time log validation passed', [
            'validated_time_logs_count' => count($validated['time_logs'])
        ]);

        try {
            DB::beginTransaction();

            $createdCount = 0;
            $updatedCount = 0;
            $skippedCount = 0;

            foreach ($validated['time_logs'] as $logData) {
                // Skip if no time_in is provided
                if (empty($logData['time_in'])) {
                    $skippedCount++;
                    continue;
                }

                // Find existing time log or create new one
                $existingLog = TimeLog::where('employee_id', $validated['employee_id'])
                                    ->where('log_date', $logData['log_date'])
                                    ->first();

                // Calculate hours
                $timeIn = Carbon::createFromFormat('H:i', $logData['time_in']);
                $timeOut = !empty($logData['time_out']) ? Carbon::createFromFormat('H:i', $logData['time_out']) : null;
                
                $breakIn = !empty($logData['break_in']) ? Carbon::createFromFormat('H:i', $logData['break_in']) : null;
                $breakOut = !empty($logData['break_out']) ? Carbon::createFromFormat('H:i', $logData['break_out']) : null;

                $totalHours = 0;
                $regularHours = 0;
                $overtimeHours = 0;
                $lateHours = 0;
                $undertimeHours = 0;

                if ($timeOut) {
                    $totalMinutes = $timeIn->diffInMinutes($timeOut);
                    
                    if ($breakIn && $breakOut) {
                        $breakMinutes = $breakIn->diffInMinutes($breakOut);
                        $totalMinutes -= $breakMinutes;
                    }

                    $totalHours = $totalMinutes / 60;

                    $standardHours = 8;
                    if ($totalHours <= $standardHours) {
                        $regularHours = $totalHours;
                    } else {
                        $regularHours = $standardHours;
                        $overtimeHours = $totalHours - $standardHours;
                    }

                    $standardTimeIn = Carbon::createFromFormat('H:i', '08:00');
                    if ($timeIn->greaterThan($standardTimeIn)) {
                        $lateHours = $standardTimeIn->diffInMinutes($timeIn) / 60;
                    }

                    if ($totalHours < $standardHours) {
                        $undertimeHours = $standardHours - $totalHours;
                    }
                }

                $timeLogData = [
                    'employee_id' => $validated['employee_id'],
                    'log_date' => $logData['log_date'],
                    'time_in' => $logData['time_in'],
                    'time_out' => $logData['time_out'],
                    'break_in' => $logData['break_in'],
                    'break_out' => $logData['break_out'],
                    'total_hours' => $totalHours,
                    'regular_hours' => $regularHours,
                    'overtime_hours' => $overtimeHours,
                    'late_hours' => $lateHours,
                    'undertime_hours' => $undertimeHours,
                    'log_type' => $logData['log_type'],
                    'remarks' => $logData['remarks'],
                    'is_holiday' => $logData['is_holiday'] ?? false,
                    'is_rest_day' => $logData['is_rest_day'] ?? false,
                    'status' => 'pending',
                ];

                if ($existingLog) {
                    $existingLog->update($timeLogData);
                    $updatedCount++;
                } else {
                    TimeLog::create($timeLogData);
                    $createdCount++;
                }
            }

            DB::commit();

            $message = "Bulk time logs processed! Created: {$createdCount}, Updated: {$updatedCount}, Skipped: {$skippedCount}";
            
            // Check if we should redirect back to payroll
            if ($request->filled('redirect_to_payroll') && $request->filled('payroll_id')) {
                // Get date range from time logs to refresh payroll calculations
                $dates = collect($validated['time_logs'])->pluck('log_date');
                $startDate = $dates->min();
                $endDate = $dates->max();
                
                // Refresh payroll calculations by recalculating the specific employee's payroll
                $this->refreshPayrollCalculations($validated['employee_id'], $startDate, $endDate);
                
                return redirect()->route('payrolls.show', $request->payroll_id)
                               ->with('success', $message . ' Payroll calculations updated.');
            }
            
            return redirect()->route('time-logs.index')
                           ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk Time Log Creation Error: ' . $e->getMessage());
            
            return back()->withErrors(['error' => 'Failed to create bulk time logs: ' . $e->getMessage()])
                        ->withInput();
        }
    }

    /**
     * Get default payroll period when no settings are configured (fallback)
     */
    private function getDefaultPayrollPeriod()
    {
        $today = Carbon::now();
        
        // Default to semi-monthly: 1st-15th or 16th-end of month
        $day = $today->day;
        
        if ($day <= 15) {
            // First half of the month
            $startDate = $today->copy()->startOfMonth();
            $endDate = $today->copy()->startOfMonth()->addDays(14);
            $payDate = $today->copy()->startOfMonth()->addDays(19); // 20th
        } else {
            // Second half of the month
            $startDate = $today->copy()->startOfMonth()->addDays(15);
            $endDate = $today->copy()->endOfMonth();
            $payDate = $today->copy()->addMonth()->startOfMonth()->addDays(4); // 5th of next month
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'pay_date' => $payDate,
            'period_label' => $startDate->format('M d') . ' - ' . $endDate->format('M d, Y'),
            'pay_label' => 'Pay Date: ' . $payDate->format('M d, Y'),
        ];
    }

    /**
     * Generate DTR data without payroll settings (fallback)
     */
    private function generateDTRDataWithoutSettings(Employee $employee, $currentPeriod)
    {
        $startDate = $currentPeriod['start_date'];
        $endDate = $currentPeriod['end_date'];
        
        // Get all time logs for the period
        $timeLogs = TimeLog::where('employee_id', $employee->id)
            ->whereBetween('log_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->keyBy('log_date');

        // Get holidays for the period (if Holiday model exists)
        $holidays = collect();
        if (class_exists(\App\Models\Holiday::class)) {
            $holidays = \App\Models\Holiday::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->get()
                ->keyBy('date');
        }

        $dtrData = [];
        
        // Generate data for each day in the period
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->format('Y-m-d');
            $timeLog = $timeLogs->get($dateStr);
            $holiday = $holidays->get($dateStr);
            
            $isWeekend = $currentDate->isWeekend();
            
            $dayData = [
                'date' => $currentDate->copy(),
                'day' => $currentDate->format('d'),
                'day_name' => $currentDate->format('l'),
                'is_weekend' => $isWeekend,
                'is_holiday' => $holiday ? $holiday->name : null,
                'time_log' => $timeLog,
                'time_in' => $timeLog ? $timeLog->time_in : null,
                'time_out' => $timeLog ? $timeLog->time_out : null,
                'break_in' => $timeLog ? $timeLog->break_in : null,
                'break_out' => $timeLog ? $timeLog->break_out : null,
                'remarks' => $timeLog ? $timeLog->remarks : null,
                'regular_hours' => $timeLog ? $timeLog->regular_hours : 0,
                'overtime_hours' => $timeLog ? $timeLog->overtime_hours : 0,
                'late_hours' => $timeLog ? $timeLog->late_hours : 0,
                'total_hours' => $timeLog ? $timeLog->total_hours : 0,
            ];
            
            $dtrData[] = $dayData;
            $currentDate->addDay();
        }
        
        return $dtrData;
    }

    /**
     * Refresh payroll calculations after DTR changes
     */
    private function refreshPayrollCalculations($employeeId, $startDate, $endDate)
    {
        try {
            // Get employee
            $employee = Employee::findOrFail($employeeId);
            
            // Update payroll totals for this employee and period
            $this->updatePayrollTotals($employeeId, $startDate, $endDate);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to refresh payroll calculations: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update payroll totals after DTR changes
     */
    private function updatePayrollTotals($employeeId, $startDate, $endDate)
    {
        try {
            // Find the payroll record for this employee and period
            $payroll = Payroll::where('employee_id', $employeeId)
                ->whereDate('period_start', $startDate)
                ->whereDate('period_end', $endDate)
                ->first();

            if ($payroll) {
                // Get time logs for the period
                $timeLogs = TimeLog::where('employee_id', $employeeId)
                    ->whereBetween('log_date', [$startDate, $endDate])
                    ->get();

                // Calculate totals
                $totalHours = $timeLogs->sum('total_hours');
                $totalRegularHours = $timeLogs->sum('regular_hours');
                $totalOvertimeHours = $timeLogs->sum('overtime_hours');
                $totalLateHours = $timeLogs->sum('late_hours');

                // Update payroll with new totals
                $payroll->update([
                    'total_hours' => $totalHours,
                    'regular_hours' => $totalRegularHours,
                    'overtime_hours' => $totalOvertimeHours,
                    'late_hours' => $totalLateHours,
                    'updated_at' => now()
                ]);

                // Recalculate pay based on new hours
                $this->recalculatePayrollAmounts($payroll);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update payroll totals: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Recalculate payroll amounts based on updated hours
     */
    private function recalculatePayrollAmounts($payroll)
    {
        try {
            $employee = $payroll->employee;
            
            // Get hourly rate
            $hourlyRate = $employee->hourly_rate ?? 0;
            $overtimeRate = $hourlyRate * 1.25; // 25% overtime

            // Calculate basic pay
            $basicPay = $payroll->regular_hours * $hourlyRate;
            $overtimePay = $payroll->overtime_hours * $overtimeRate;
            
            // Calculate gross pay
            $grossPay = $basicPay + $overtimePay + ($payroll->allowances ?? 0);
            
            // Calculate net pay (gross - deductions)
            $netPay = $grossPay - ($payroll->deductions ?? 0);

            // Update payroll amounts
            $payroll->update([
                'basic_pay' => $basicPay,
                'overtime_pay' => $overtimePay,
                'gross_pay' => $grossPay,
                'net_pay' => $netPay
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to recalculate payroll amounts: ' . $e->getMessage());
            return false;
        }
    }
}
