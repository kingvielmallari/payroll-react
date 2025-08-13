<?php

namespace App\Http\Controllers;

use App\Models\CashAdvance;
use App\Models\Employee;
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
     * Get payroll periods for an employee (AJAX endpoint)
     */
    public function getEmployeePayrollPeriods(Request $request)
    {
        $employeeId = $request->input('employee_id');

        if (!$employeeId) {
            return response()->json(['error' => 'Employee ID is required'], 400);
        }

        // Get current and next payroll periods for this employee
        $currentPayroll = \App\Models\Payroll::where('employee_id', $employeeId)
            ->whereIn('status', ['draft', 'in_progress'])
            ->orderBy('pay_period_end', 'desc')
            ->first();

        // Get next payroll period (if any)
        $nextPayroll = \App\Models\Payroll::where('employee_id', $employeeId)
            ->where('status', 'draft')
            ->where('pay_period_start', '>', $currentPayroll?->pay_period_end ?? now())
            ->orderBy('pay_period_start', 'asc')
            ->first();

        $periods = [];

        if ($currentPayroll) {
            $periods[] = [
                'value' => 'current',
                'label' => 'Current Payroll Period (' . $currentPayroll->pay_period_start->format('M d') . ' - ' . $currentPayroll->pay_period_end->format('M d, Y') . ')',
                'start_date' => $currentPayroll->pay_period_start->format('Y-m-d'),
                'payroll_id' => $currentPayroll->id
            ];
        }

        if ($nextPayroll) {
            $periods[] = [
                'value' => 'next',
                'label' => 'Next Payroll Period (' . $nextPayroll->pay_period_start->format('M d') . ' - ' . $nextPayroll->pay_period_end->format('M d, Y') . ')',
                'start_date' => $nextPayroll->pay_period_start->format('Y-m-d'),
                'payroll_id' => $nextPayroll->id
            ];
        }

        return response()->json(['periods' => $periods]);
    }

    /**
     * Store a newly created cash advance.
     */
    public function store(Request $request)
    {
        $this->authorize('create cash advances');

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'requested_amount' => 'required|numeric|min:100|max:50000',
            'installments' => 'required|integer|min:1|max:12',
            'interest_rate' => 'nullable|numeric|min:0|max:100',
            'reason' => 'required|string|max:500',
            'first_deduction_date' => 'nullable|date',
            'deduction_period' => 'required|in:current,next',
            'payroll_id' => 'required|exists:payrolls,id',
        ]);

        // Additional validation for employee users
        if (Auth::user()->hasRole('employee')) {
            $employee = Auth::user()->employee;
            if (!$employee || $employee->id != $validated['employee_id']) {
                return redirect()->back()->with('error', 'You can only request cash advances for yourself.');
            }

            // Check if employee has pending cash advance
            $pendingAdvance = CashAdvance::where('employee_id', $employee->id)
                ->whereIn('status', ['pending', 'approved'])
                ->where('outstanding_balance', '>', 0)
                ->exists();

            if ($pendingAdvance) {
                return redirect()->back()->with('error', 'You already have a pending or active cash advance.');
            }
        }

        try {
            DB::beginTransaction();

            // Calculate first deduction date based on deduction period
            $firstDeductionDate = $validated['first_deduction_date'] ?? now();
            if ($validated['deduction_period'] === 'next') {
                $firstDeductionDate = now()->addMonth();
            }

            $cashAdvance = CashAdvance::create([
                'employee_id' => $validated['employee_id'],
                'reference_number' => CashAdvance::generateReferenceNumber(),
                'requested_amount' => $validated['requested_amount'],
                'installments' => $validated['installments'],
                'interest_rate' => $validated['interest_rate'] ?? 0,
                'reason' => $validated['reason'],
                'requested_date' => now(),
                'first_deduction_date' => $validated['first_deduction_date'],
                'deduction_period' => $validated['deduction_period'],
                'payroll_id' => $validated['payroll_id'], // Store the associated payroll ID
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

        return redirect()->route('cash-advances.show', $cashAdvance)
            ->with('success', 'Cash advance rejected.');
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
}
