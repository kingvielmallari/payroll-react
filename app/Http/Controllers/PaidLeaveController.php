<?php

namespace App\Http\Controllers;

use App\Models\PaidLeave;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PaidLeaveController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        // Middleware will be handled by routes
    }

    /**
     * Display a listing of paid leaves.
     */
    public function index(Request $request)
    {
        $this->authorize('view paid leaves');

        $query = PaidLeave::with(['employee', 'requestedBy', 'approvedBy']);

        // Filter by name search (employee name)
        if ($request->filled('name_search')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $searchTerm = $request->name_search;
                $q->where(DB::raw("CONCAT(first_name, ' ', middle_name, ' ', last_name)"), 'LIKE', "%{$searchTerm}%")
                    ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$searchTerm}%")
                    ->orWhere('first_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('last_name', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by leave type
        if ($request->filled('leave_type')) {
            $query->where('leave_type', $request->leave_type);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('start_date', [$request->start_date, $request->end_date]);
        }

        // Order by latest first
        $query->orderBy('created_at', 'desc');

        $paidLeaves = $query->paginate(10);

        // Calculate summary statistics
        $totalApprovedAmount = PaidLeave::where('status', 'approved')->sum('total_amount');
        $totalPendingAmount = PaidLeave::where('status', 'pending')->sum('total_amount');
        $totalRequests = PaidLeave::count();

        return view('paid-leaves.index', compact('paidLeaves', 'totalApprovedAmount', 'totalPendingAmount', 'totalRequests'));
    }

    /**
     * Show the form for creating a new paid leave.
     */
    public function create()
    {
        $this->authorize('create paid leaves');

        $employee = null;

        // If employee user, get their employee record
        if (Auth::user()->hasRole('Employee')) {
            $employee = Auth::user()->employee;
            if (!$employee) {
                return redirect()->back()->with('error', 'Employee profile not found.');
            }
        }

        $employees = Employee::active()->orderBy('last_name')->get();

        // Get active leave settings - we'll pass these to the view for JavaScript
        $leaveSettings = \App\Models\PaidLeaveSetting::active()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'total_days', 'limit_quantity', 'limit_period', 'pay_rule', 'pay_applicable_to']);

        return view('paid-leaves.create', compact('employees', 'employee', 'leaveSettings'));
    }

    /**
     * Calculate leave balances for an employee
     */
    public function getEmployeeLeaveBalances(Request $request)
    {
        $employee = Employee::find($request->employee_id);
        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        $leaveSettings = \App\Models\PaidLeaveSetting::active()->get();
        $balances = [];

        foreach ($leaveSettings as $leaveSetting) {
            // Check if employee is eligible for this leave type
            $isEligible = $this->isEmployeeEligibleForLeave($employee, $leaveSetting);

            if ($isEligible) {
                $usedLeaves = $this->calculateUsedLeaves($employee->id, $leaveSetting->id);
                $availableLeaves = $leaveSetting->limit_quantity - $usedLeaves;

                $balances[] = [
                    'leave_setting_id' => $leaveSetting->id,
                    'name' => $leaveSetting->name,
                    'code' => $leaveSetting->code,
                    'total_days' => $leaveSetting->total_days,
                    'limit_quantity' => $leaveSetting->limit_quantity,
                    'limit_period' => $leaveSetting->limit_period,
                    'used_leaves' => $usedLeaves,
                    'available_leaves' => max(0, $availableLeaves),
                    'pay_rule' => $leaveSetting->pay_rule,
                    'pay_applicable_to' => $leaveSetting->pay_applicable_to,
                    'pay_percentage' => $leaveSetting->pay_rule === 'full' ? 100 : 50
                ];
            }
        }

        return response()->json(['balances' => $balances]);
    }

    /**
     * Check if employee is eligible for a leave type
     */
    private function isEmployeeEligibleForLeave($employee, $leaveSetting)
    {
        // Check benefit eligibility
        if ($leaveSetting->pay_applicable_to === 'with_benefits' && !$employee->has_benefits) {
            return false;
        }
        if ($leaveSetting->pay_applicable_to === 'without_benefits' && $employee->has_benefits) {
            return false;
        }
        // 'all' means all employees are eligible

        return true;
    }

    /**
     * Calculate used leaves for employee and leave type in current period
     */
    private function calculateUsedLeaves($employeeId, $leaveSettingId)
    {
        // Get current period based on leave setting limit_period
        $leaveSetting = \App\Models\PaidLeaveSetting::find($leaveSettingId);
        $now = now();

        switch ($leaveSetting->limit_period) {
            case 'monthly':
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                break;
            case 'quarterly':
                $quarter = ceil($now->month / 3);
                $startDate = $now->copy()->month(($quarter - 1) * 3 + 1)->startOfMonth();
                $endDate = $startDate->copy()->addMonths(2)->endOfMonth();
                break;
            case 'annually':
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfYear();
                break;
            default:
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
        }

        return PaidLeave::where('employee_id', $employeeId)
            ->whereHas('leaveSetting', function ($q) use ($leaveSettingId) {
                $q->where('id', $leaveSettingId);
            })
            ->whereBetween('start_date', [$startDate, $endDate])
            ->where('status', '!=', 'rejected')
            ->sum('total_days');
    }

    /**
     * Store a newly created paid leave.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_setting_id' => 'required|exists:paid_leave_settings,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
            'supporting_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        // Get leave setting and employee
        $leaveSetting = \App\Models\PaidLeaveSetting::findOrFail($validatedData['leave_setting_id']);
        $employee = Employee::findOrFail($validatedData['employee_id']);

        // Verify employee is eligible for this leave type
        if (!$this->isEmployeeEligibleForLeave($employee, $leaveSetting)) {
            return back()->withErrors(['leave_setting_id' => 'Employee is not eligible for this leave type.']);
        }

        // Check if employee has sufficient leave balance
        $usedLeaves = $this->calculateUsedLeaves($employee->id, $leaveSetting->id);
        $availableLeaves = $leaveSetting->limit_quantity - $usedLeaves;

        if ($availableLeaves < 1) {
            return back()->withErrors(['leave_setting_id' => 'Insufficient leave balance for this leave type.']);
        }

        // Calculate total days
        $startDate = \Carbon\Carbon::parse($validatedData['start_date']);
        $endDate = \Carbon\Carbon::parse($validatedData['end_date']);
        $totalDays = $startDate->diffInDays($endDate) + 1;

        // Verify total days matches leave setting
        if ($totalDays != $leaveSetting->total_days) {
            return back()->withErrors(['end_date' => "This leave type allows only {$leaveSetting->total_days} day(s) per request."]);
        }

        // Get employee's daily rate and calculate amount based on pay rule
        $dailyRate = $employee->basic_salary ? ($employee->basic_salary / 22) : 0; // Assuming 22 working days per month
        $payPercentage = $leaveSetting->pay_rule === 'full' ? 100 : 50;
        $payRate = ($payPercentage / 100) * $dailyRate;
        $totalAmount = $payRate * $totalDays;

        $paidLeaveData = array_merge($validatedData, [
            'leave_type' => strtolower(str_replace(' ', '_', $leaveSetting->name)), // Convert name to snake_case
            'total_days' => $totalDays,
            'daily_rate' => $payRate, // Use the adjusted rate based on pay percentage
            'total_amount' => $totalAmount,
            'requested_by' => Auth::id(),
            'requested_date' => now(),
        ]);

        // Handle file upload
        if ($request->hasFile('supporting_document')) {
            $paidLeaveData['supporting_document'] = $request->file('supporting_document')->store('paid-leaves', 'public');
        }

        $paidLeave = PaidLeave::create($paidLeaveData);

        return redirect()->route('paid-leaves.index')->with('success', 'Paid leave request submitted successfully.');
    }

    /**
     * Display the specified paid leave.
     */
    public function show(PaidLeave $paidLeave)
    {
        $this->authorize('view paid leaves');

        $paidLeave->load(['employee', 'requestedBy', 'approvedBy']);

        return view('paid-leaves.show', compact('paidLeave'));
    }

    /**
     * Approve a paid leave request.
     */
    public function approve(Request $request, PaidLeave $paidLeave)
    {
        $this->authorize('approve paid leaves');

        $request->validate([
            'remarks' => 'nullable|string|max:500',
        ]);

        $paidLeave->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_date' => now(),
            'remarks' => $request->remarks,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Paid leave approved successfully.',
        ]);
    }

    /**
     * Reject a paid leave request.
     */
    public function reject(Request $request, PaidLeave $paidLeave)
    {
        $this->authorize('approve paid leaves');

        $request->validate([
            'remarks' => 'required|string|max:500',
        ]);

        $paidLeave->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_date' => now(),
            'remarks' => $request->remarks,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Paid leave rejected.',
        ]);
    }

    /**
     * Check employee eligibility (AJAX)
     */
    public function checkEligibility(Request $request)
    {
        // Implement eligibility checking logic
        return response()->json(['eligible' => true]);
    }

    /**
     * Get employee payroll periods (AJAX)
     */
    public function getEmployeePayrollPeriods(Request $request)
    {
        // Return available payroll periods for the employee
        return response()->json([]);
    }

    /**
     * Get employee pay schedule (AJAX)
     */
    public function getEmployeePaySchedule(Request $request)
    {
        // Return employee's pay schedule
        return response()->json([]);
    }

    /**
     * Check employee active leaves (AJAX)
     */
    public function checkEmployeeActiveLeaves(Request $request)
    {
        $employeeId = $request->employee_id;

        $activeLeave = PaidLeave::where('employee_id', $employeeId)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($query) {
                $query->where('end_date', '>=', now()->toDateString())
                    ->orWhereNull('end_date');
            })
            ->first();

        if ($activeLeave) {
            return response()->json([
                'has_active' => true,
                'active_leave' => [
                    'reference_number' => $activeLeave->reference_number,
                    'status' => $activeLeave->status,
                    'leave_type' => $activeLeave->leave_type_display,
                    'start_date' => $activeLeave->start_date->format('M d, Y'),
                    'end_date' => $activeLeave->end_date->format('M d, Y'),
                ]
            ]);
        }

        return response()->json(['has_active' => false]);
    }

    /**
     * Generate summary report (AJAX)
     */
    public function generateSummary(Request $request)
    {
        $this->authorize('view paid leaves');

        // Implement summary generation logic
        return response()->json(['message' => 'Summary generated successfully']);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PaidLeave $paidLeave)
    {
        // Only allow editing pending requests
        if ($paidLeave->status !== 'pending') {
            return redirect()->route('paid-leaves.show', $paidLeave)
                ->with('error', 'Only pending paid leave requests can be edited.');
        }

        $employees = collect();
        $employee = null;

        if (Auth::user()->can('manage employees')) {
            $employees = Employee::select('id', 'first_name', 'middle_name', 'last_name', 'employee_number')
                ->where('employment_status', 'active')
                ->get()
                ->transform(function ($emp) {
                    $emp->setAttribute('full_name', trim($emp->first_name . ' ' . ($emp->middle_name ? $emp->middle_name . ' ' : '') . $emp->last_name));
                    return $emp;
                });
        } else {
            $employee = $paidLeave->employee;
        }

        return view('paid-leaves.edit', compact('paidLeave', 'employees', 'employee'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PaidLeave $paidLeave)
    {
        // Only allow updating pending requests
        if ($paidLeave->status !== 'pending') {
            return redirect()->route('paid-leaves.show', $paidLeave)
                ->with('error', 'Only pending paid leave requests can be updated.');
        }

        $validatedData = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type' => 'required|in:sick_leave,vacation_leave,emergency_leave,maternity_leave,paternity_leave,bereavement_leave',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
            'supporting_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        // Recalculate totals
        $startDate = \Carbon\Carbon::parse($validatedData['start_date']);
        $endDate = \Carbon\Carbon::parse($validatedData['end_date']);
        $totalDays = $startDate->diffInDays($endDate) + 1;

        $employee = Employee::findOrFail($validatedData['employee_id']);
        $dailyRate = $employee->basic_salary ? ($employee->basic_salary / 22) : 0;
        $totalAmount = $dailyRate * $totalDays;

        $updateData = array_merge($validatedData, [
            'total_days' => $totalDays,
            'daily_rate' => $dailyRate,
            'total_amount' => $totalAmount,
        ]);

        // Handle file upload
        if ($request->hasFile('supporting_document')) {
            $updateData['supporting_document'] = $request->file('supporting_document')->store('paid-leaves', 'public');
        }

        $paidLeave->update($updateData);

        return redirect()->route('paid-leaves.show', $paidLeave)
            ->with('success', 'Paid leave request updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaidLeave $paidLeave)
    {
        // Only allow deleting pending requests
        if ($paidLeave->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Only pending paid leave requests can be deleted.');
        }

        $paidLeave->delete();

        return redirect()->route('paid-leaves.index')
            ->with('success', 'Paid leave request deleted successfully.');
    }
}
