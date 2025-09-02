<?php

namespace App\Http\Controllers;

use App\Models\CashAdvance;
use App\Models\Employee;
use App\Models\PayrollSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CashAdvanceController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        // $this->middleware('auth'); // Will be handled by route middleware
    }

    /**
     * Display a listing of cash advances.
     */
    public function index(Request $request)
    {
        $this->authorize('view cash advances');

        $query = CashAdvance::with(['employee', 'requestedBy', 'approvedBy']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by employee (for HR/Admin)
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('requested_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('requested_date', '<=', $request->date_to);
        }

        // If employee user, only show their own cash advances
        if (Auth::user()->hasRole('employee')) {
            $employee = Auth::user()->employee;
            if ($employee) {
                $query->where('employee_id', $employee->id);
            }
        }

        $cashAdvances = $query->orderByDesc('created_at')->paginate(20);

        $employees = Employee::active()->orderBy('last_name')->get();

        return view('cash-advances.index', compact('cashAdvances', 'employees'));
    }

    /**
     * Show the form for creating a new cash advance.
     */
    public function create()
    {
        $this->authorize('create cash advances');

        $employee = null;

        // If employee user, get their employee record
        if (Auth::user()->hasRole('employee')) {
            $employee = Auth::user()->employee;
            if (!$employee) {
                return redirect()->back()->with('error', 'Employee profile not found.');
            }
        }

        $employees = Employee::active()->orderBy('last_name')->get();

        return view('cash-advances.create', compact('employees', 'employee'));
    }

    /**
     * Get employee pay schedule information (AJAX endpoint)
     */
    public function getEmployeePaySchedule(Request $request)
    {
        $employeeId = $request->input('employee_id');

        if (!$employeeId) {
            return response()->json(['error' => 'Employee ID is required'], 400);
        }

        $employee = Employee::find($employeeId);
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        return response()->json([
            'pay_schedule' => $employee->pay_schedule, // weekly, semi_monthly, monthly
            'full_name' => $employee->full_name,
            'basic_salary' => $employee->basic_salary,
        ]);
    }

    /**
     * Get payroll periods for an employee (AJAX endpoint)
     */
    public function getEmployeePayrollPeriods(Request $request)
    {
        $employeeId = $request->input('employee_id');

        if (!$employeeId) {
            return response()->json(['error' => 'Employee ID is required'], 400);
        }

        $employee = Employee::find($employeeId);
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        // Get the schedule setting for this employee's pay schedule
        $scheduleSetting = \App\Models\PayScheduleSetting::where('code', $employee->pay_schedule)
            ->where('is_active', true)
            ->first();

        if (!$scheduleSetting) {
            return response()->json(['error' => 'Pay schedule setting not found'], 404);
        }

        // Get timing preference for semi-monthly employees with monthly frequency
        $monthlyTiming = $request->input('monthly_deduction_timing');
        $deductionFrequency = $request->input('deduction_frequency');

        // Calculate the next 3 payroll periods for this employee
        $periods = $this->calculateNextPayrollPeriods($scheduleSetting, 3, $monthlyTiming, $deductionFrequency);

        return response()->json([
            'periods' => $periods,
            'employee_pay_schedule' => $employee->pay_schedule
        ]);
    }

    /**
     * Calculate the next N payroll periods for a given schedule
     */
    private function calculateNextPayrollPeriods($scheduleSetting, $count = 3, $monthlyTiming = null, $deductionFrequency = null)
    {
        $periods = [];
        $currentDate = \Carbon\Carbon::now();

        for ($i = 0; $i < $count; $i++) {
            $periodData = $this->calculatePayrollPeriodForOffset($scheduleSetting, $currentDate, $i, $monthlyTiming, $deductionFrequency);

            $periods[] = [
                'value' => $i + 1,
                'label' => $periodData['display'],
                'description' => "Pay period: {$periodData['display']}",
                'is_default' => $i === 0
            ];
        }

        return $periods;
    }
    /**
     * Calculate payroll period for a specific offset from current date
     */
    private function calculatePayrollPeriodForOffset($scheduleSetting, $baseDate, $offset = 0, $monthlyTiming = null, $deductionFrequency = null)
    {
        switch ($scheduleSetting->code) {
            case 'semi_monthly':
                return $this->calculateSemiMonthlyPeriodForOffset($scheduleSetting, $baseDate, $offset, $monthlyTiming, $deductionFrequency);
            case 'weekly':
                return $this->calculateWeeklyPeriodForOffset($scheduleSetting, $baseDate, $offset);
            case 'monthly':
                return $this->calculateMonthlyPeriodForOffset($scheduleSetting, $baseDate, $offset);
            default:
                return $this->calculateSemiMonthlyPeriodForOffset($scheduleSetting, $baseDate, $offset, $monthlyTiming, $deductionFrequency);
        }
    }

    /**
     * Calculate semi-monthly periods with offset
     */
    private function calculateSemiMonthlyPeriodForOffset($scheduleSetting, $baseDate, $offset, $monthlyTiming = null, $deductionFrequency = null)
    {
        $cutoffPeriods = $scheduleSetting->cutoff_periods;
        if (is_string($cutoffPeriods)) {
            $cutoffPeriods = json_decode($cutoffPeriods, true);
        }
        if (empty($cutoffPeriods) || count($cutoffPeriods) < 2) {
            // Default semi-monthly cutoffs
            $cutoffPeriods = [
                ['start_day' => 1, 'end_day' => 15],
                ['start_day' => 16, 'end_day' => 31]
            ];
        }

        $currentDay = $baseDate->day;
        $currentMonth = $baseDate->copy();

        // Determine the ACTUAL current period based on today's date
        $isFirstHalf = $currentDay <= 15;

        if ($deductionFrequency === 'monthly' && $monthlyTiming) {
            // For monthly frequency with timing preference
            if ($monthlyTiming === 'first_payroll') {
                // Show only 1st cutoff periods across months
                $preferredPeriodIndex = 0;
                $targetMonth = $currentMonth->copy();

                // If we're currently in 2nd half and user wants 1st cutoff, start from next month
                if (!$isFirstHalf) {
                    $targetMonth->addMonth();
                }

                // Apply offset by adding months (stay on same cutoff type)
                $targetMonth->addMonths($offset);
                $targetPeriodIndex = $preferredPeriodIndex;
            } else {
                // Show only 2nd cutoff periods across months
                $preferredPeriodIndex = 1;
                $targetMonth = $currentMonth->copy();

                // If we're currently in 1st half and user wants 2nd cutoff, use current month's 2nd cutoff
                if ($isFirstHalf) {
                    // Stay in current month for 2nd cutoff (current month's Aug 16-31)
                } else {
                    // If in 2nd half, this IS the current "last payroll", so start here
                    // No need to move to next month for offset 0
                }

                // Apply offset by adding months (stay on same cutoff type)
                $targetMonth->addMonths($offset);
                $targetPeriodIndex = $preferredPeriodIndex;
            }
        } else {
            // For per-payroll frequency, show both cutoffs alternating
            // Start from the CURRENT active period
            $targetPeriodIndex = $isFirstHalf ? 0 : 1;
            $targetMonth = $currentMonth->copy();

            // Apply offset with alternating cutoffs
            for ($i = 0; $i < $offset; $i++) {
                $targetPeriodIndex++;
                if ($targetPeriodIndex >= 2) {
                    $targetPeriodIndex = 0;
                    $targetMonth->addMonth();
                }
            }
        }

        $cutoff = $cutoffPeriods[$targetPeriodIndex];
        $startDay = (int) $cutoff['start_day'];
        $endDay = (int) $cutoff['end_day'];

        $startDate = $targetMonth->copy()->day($startDay);
        $endDate = $endDay == 31 ? $targetMonth->copy()->endOfMonth() : $targetMonth->copy()->day($endDay);

        return [
            'start' => $startDate,
            'end' => $endDate,
            'display' => $startDate->format('M d') . ' - ' . $endDate->format('d, Y')
        ];
    }
    /**
     * Calculate weekly periods with offset
     */
    private function calculateWeeklyPeriodForOffset($scheduleSetting, $baseDate, $offset)
    {
        $cutoffPeriods = $scheduleSetting->cutoff_periods;
        if (is_string($cutoffPeriods)) {
            $cutoffPeriods = json_decode($cutoffPeriods, true);
        }
        if (empty($cutoffPeriods)) {
            $cutoffPeriods = [['start_day' => 'monday', 'end_day' => 'friday']];
        }

        $cutoff = $cutoffPeriods[0];
        $startDayName = $cutoff['start_day'];

        // Get the start of current week based on start day
        $startDate = $baseDate->copy()->startOfWeek();
        if ($startDayName !== 'monday') {
            // Adjust for different start days
            $dayMap = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6];
            $targetDay = $dayMap[$startDayName] ?? 1;
            $startDate = $baseDate->copy()->startOfWeek()->addDays($targetDay - 1);
        }

        // Apply offset in weeks
        $startDate->addWeeks($offset);
        $endDate = $startDate->copy()->addDays(6); // 7-day week

        return [
            'start' => $startDate,
            'end' => $endDate,
            'display' => $startDate->format('M d') . ' - ' . $endDate->format('d, Y')
        ];
    }

    /**
     * Calculate monthly periods with offset
     */
    private function calculateMonthlyPeriodForOffset($scheduleSetting, $baseDate, $offset)
    {
        $targetMonth = $baseDate->copy()->addMonths($offset);
        $startDate = $targetMonth->copy()->startOfMonth();
        $endDate = $targetMonth->copy()->endOfMonth();

        return [
            'start' => $startDate,
            'end' => $endDate,
            'display' => $startDate->format('M d') . ' - ' . $endDate->format('d, Y')
        ];
    }
    /**
     * Store a newly created cash advance.
     */
    public function store(Request $request)
    {
        $this->authorize('create cash advances');

        // Base validation rules
        $validationRules = [
            'employee_id' => 'required|exists:employees,id',
            'requested_amount' => 'required|numeric|min:100|max:50000',
            'deduction_frequency' => 'required|in:per_payroll,monthly',
            'monthly_deduction_timing' => 'nullable|in:first_payroll,last_payroll',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
            'reason' => 'required|string|max:500',
            'starting_payroll_period' => 'required|integer|min:1|max:4',
        ];

        // Add frequency-specific validation rules
        if ($request->deduction_frequency === 'monthly') {
            $validationRules['monthly_installments'] = 'required|integer|min:1|max:12';
            $validationRules['installments'] = 'nullable|integer|min:1|max:12';
            $validationRules['monthly_deduction_timing'] = 'required|in:first_payroll,last_payroll';
        } else {
            $validationRules['installments'] = 'required|integer|min:1|max:12';
            $validationRules['monthly_installments'] = 'nullable|integer|min:1|max:12';
        }

        $validated = $request->validate($validationRules);

        // Clean up monthly_deduction_timing - convert empty string to null for per_payroll frequency
        if ($validated['deduction_frequency'] === 'per_payroll') {
            $validated['monthly_deduction_timing'] = null;
        } elseif (isset($validated['monthly_deduction_timing']) && $validated['monthly_deduction_timing'] === '') {
            $validated['monthly_deduction_timing'] = null;
        }

        // Check if employee already has an active cash advance (applies to all users)
        $existingAdvance = CashAdvance::where('employee_id', $validated['employee_id'])
            ->whereIn('status', ['pending', 'approved'])
            ->where('outstanding_balance', '>', 0)
            ->first();

        if ($existingAdvance) {
            $employee = Employee::find($validated['employee_id']);
            $employeeName = $employee ? $employee->full_name : 'Employee';
            return redirect()->back()
                ->withInput()
                ->with('error', $employeeName . ' already has an active cash advance (Reference: ' . $existingAdvance->reference_number . '). Please wait until it is fully paid before creating a new one.');
        }

        // Additional validation for employee users
        if (Auth::user()->hasRole('employee')) {
            $employee = Auth::user()->employee;
            if (!$employee || $employee->id != $validated['employee_id']) {
                return redirect()->back()->with('error', 'You can only request cash advances for yourself.');
            }
        }

        try {
            DB::beginTransaction();

            // Calculate first deduction date based on starting payroll period
            // Get the actual payroll period dates for the selected starting period
            $employee = Employee::findOrFail($validated['employee_id']);
            $payrollSetting = PayrollSetting::getDefault();

            $monthlyTiming = $validated['monthly_deduction_timing'] ?? null;
            $deductionFrequency = $validated['deduction_frequency'];

            // Calculate the actual payroll period for the selected starting period
            $periodOffset = $validated['starting_payroll_period'] - 1; // Convert to 0-based offset

            // Use the SAME calculation that generates the dropdown options
            $periodData = $this->calculatePayrollPeriodForOffset($payrollSetting, \Carbon\Carbon::now(), $periodOffset, $monthlyTiming, $deductionFrequency);
            $firstDeductionDate = $periodData['start'];
            $firstDeductionPeriodEnd = $periodData['end'];

            // Determine installments value based on frequency
            $installmentsValue = ($validated['deduction_frequency'] === 'monthly')
                ? ($validated['monthly_installments'] ?? 1)
                : ($validated['installments'] ?? 1);

            $cashAdvance = CashAdvance::create([
                'employee_id' => $validated['employee_id'],
                'reference_number' => CashAdvance::generateReferenceNumber(),
                'requested_amount' => $validated['requested_amount'],
                'installments' => $installmentsValue,
                'monthly_installments' => $validated['monthly_installments'] ?? null,
                'deduction_frequency' => $validated['deduction_frequency'],
                'monthly_deduction_timing' => $validated['deduction_frequency'] === 'monthly' ? ($validated['monthly_deduction_timing'] ?? null) : null,
                'starting_payroll_period' => $validated['starting_payroll_period'],
                'interest_rate' => $validated['interest_rate'] ?? 0,
                'reason' => $validated['reason'],
                'requested_date' => now(),
                'first_deduction_date' => $firstDeductionDate,
                'first_deduction_period_start' => $periodData['start'],
                'first_deduction_period_end' => $periodData['end'],
                'payroll_id' => null, // No longer tied to specific payroll
                'requested_by' => Auth::id(),
                'status' => 'pending',
            ]);

            // Calculate interest and total amounts
            $cashAdvance->updateCalculations();
            $cashAdvance->save();

            DB::commit();

            return redirect()->route('cash-advances.show', $cashAdvance)
                ->with('success', 'Cash advance request submitted successfully! Reference: ' . $cashAdvance->reference_number);
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to submit cash advance request: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified cash advance.
     */
    public function show(CashAdvance $cashAdvance)
    {
        // Check if user can view cash advances or their own cash advances
        if (Auth::user()->hasRole('employee')) {
            $employee = Auth::user()->employee;
            if (!$employee || $employee->id !== $cashAdvance->employee_id) {
                $this->authorize('view cash advances'); // This will fail for employees viewing others'
            }
        } else {
            $this->authorize('view cash advances');
        }

        $cashAdvance->load(['employee', 'requestedBy', 'approvedBy', 'payments.payroll']);

        return view('cash-advances.show', compact('cashAdvance'));
    }

    /**
     * Approve a cash advance.
     */
    public function approve(Request $request, CashAdvance $cashAdvance)
    {
        $this->authorize('approve cash advances');

        if ($cashAdvance->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending cash advances can be approved.');
        }

        $validated = $request->validate([
            'approved_amount' => 'required|numeric|min:100|max:' . $cashAdvance->requested_amount,
            'installments' => 'required|integer|min:1|max:12',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
            'remarks' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $cashAdvance->approve(
                $validated['approved_amount'],
                $validated['installments'],
                Auth::id(),
                $validated['remarks'],
                $validated['interest_rate'] ?? $cashAdvance->interest_rate
            );

            DB::commit();

            return redirect()->route('cash-advances.show', $cashAdvance)
                ->with('success', 'Cash advance approved successfully!');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Failed to approve cash advance: ' . $e->getMessage());
        }
    }

    /**
     * Reject a cash advance.
     */
    public function reject(Request $request, CashAdvance $cashAdvance)
    {
        $this->authorize('approve cash advances');

        if ($cashAdvance->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending cash advances can be rejected.');
        }

        $validated = $request->validate([
            'remarks' => 'required|string|max:500',
        ]);

        $cashAdvance->reject($validated['remarks'], Auth::id());

        return redirect()->route('cash-advances.index')
            ->with('success', 'Cash advance rejected successfully.');
    }

    /**
     * Get employee's cash advance eligibility.
     */
    public function checkEligibility(Request $request)
    {
        $employeeId = $request->get('employee_id');

        if (!$employeeId) {
            return response()->json(['eligible' => false, 'reason' => 'Employee not specified']);
        }

        $employee = Employee::find($employeeId);
        if (!$employee) {
            return response()->json(['eligible' => false, 'reason' => 'Employee not found']);
        }

        // Check if employee has active cash advance
        $hasActive = CashAdvance::where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'approved'])
            ->where('outstanding_balance', '>', 0)
            ->exists();

        if ($hasActive) {
            return response()->json([
                'eligible' => false,
                'reason' => 'Employee has an active or pending cash advance'
            ]);
        }

        // Calculate maximum eligible amount (e.g., 50% of monthly salary)
        $monthlySalary = $employee->basic_salary;
        $maxEligible = $monthlySalary * 0.5;

        return response()->json([
            'eligible' => true,
            'max_amount' => $maxEligible,
            'monthly_salary' => $monthlySalary,
        ]);
    }

    /**
     * Remove the specified cash advance from storage.
     */
    public function destroy(CashAdvance $cashAdvance)
    {
        $this->authorize('delete cash advances');

        // Check if cash advance can be deleted
        if ($cashAdvance->status === 'approved' && $cashAdvance->outstanding_balance < $cashAdvance->total_amount) {
            return redirect()->route('cash-advances.index')
                ->with('error', 'Cannot delete cash advance that has been partially paid.');
        }

        // If approved, remove associated deductions
        if ($cashAdvance->status === 'approved') {
            // Remove automatic deductions associated with this cash advance
            DB::table('deductions')
                ->where('employee_id', $cashAdvance->employee_id)
                ->where('type', 'cash_advance')
                ->where('description', 'like', "%{$cashAdvance->reference_number}%")
                ->delete();
        }

        $reference = $cashAdvance->reference_number;
        $cashAdvance->delete();

        return redirect()->route('cash-advances.index')
            ->with('success', "Cash advance {$reference} has been deleted successfully.");
    }

    /**
     * Check if employee has existing active cash advances (AJAX endpoint)
     */
    public function checkEmployeeActiveAdvances(Request $request)
    {
        $employeeId = $request->input('employee_id');

        if (!$employeeId) {
            return response()->json(['error' => 'Employee ID is required'], 400);
        }

        $employee = Employee::find($employeeId);
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        // Check for active cash advances
        $activeAdvance = CashAdvance::where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'approved'])
            ->where('outstanding_balance', '>', 0)
            ->with(['employee'])
            ->first();

        if ($activeAdvance) {
            return response()->json([
                'has_active_advance' => true,
                'active_advance' => [
                    'reference_number' => $activeAdvance->reference_number,
                    'status' => $activeAdvance->status,
                    'outstanding_balance' => number_format($activeAdvance->outstanding_balance, 2),
                    'requested_amount' => number_format($activeAdvance->requested_amount, 2),
                ]
            ]);
        }

        return response()->json(['has_active_advance' => false]);
    }
}
