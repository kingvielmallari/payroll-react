<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LeaveRequest;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeaveRequestController extends Controller
{
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->hasRole('Employee')) {
            // Employees can only view their own leave requests
            $employee = $user->employee;
            if (!$employee) {
                return redirect()->route('dashboard')->with('error', 'Employee profile not found.');
            }

            $leaveRequests = LeaveRequest::where('employee_id', $employee->id)
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        } else {
            // HR can view all leave requests
            $this->authorize('view leave requests');
            $leaveRequests = LeaveRequest::with(['employee', 'approvedByUser'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        }

        return view('leave-requests.index', compact('leaveRequests'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();
        $employee = null;

        if ($user->hasRole('Employee')) {
            $employee = $user->employee;
            if (!$employee) {
                return redirect()->route('dashboard')->with('error', 'Employee profile not found.');
            }
        } else {
            $this->authorize('create leave requests');
        }

        $employees = Employee::active()->orderBy('last_name')->get();

        return view('leave-requests.create', compact('employees', 'employee'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'leave_type' => 'required|in:sick,vacation,emergency,maternity,paternity,bereavement,special',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
            'is_paid' => 'boolean',
        ]);

        $user = Auth::user();

        // If employee, ensure they can only create for themselves
        if ($user->hasRole('Employee')) {
            $employee = $user->employee;
            if (!$employee || $employee->id != $request->employee_id) {
                return redirect()->back()->with('error', 'You can only create leave requests for yourself.');
            }
        } else {
            $this->authorize('create leave requests');
        }

        // Calculate days requested
        $startDate = \Carbon\Carbon::parse($request->start_date);
        $endDate = \Carbon\Carbon::parse($request->end_date);
        $daysRequested = $startDate->diffInDays($endDate) + 1;

        DB::transaction(function () use ($request, $daysRequested) {
            LeaveRequest::create([
                'employee_id' => $request->employee_id,
                'leave_type' => $request->leave_type,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'days_requested' => $daysRequested,
                'reason' => $request->reason,
                'is_paid' => $request->boolean('is_paid', true),
                'status' => 'pending',
            ]);
        });

        return redirect()->route('leave-requests.index')->with('success', 'Leave request submitted successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(LeaveRequest $leaveRequest)
    {
        $user = Auth::user();

        if ($user->hasRole('Employee')) {
            $employee = $user->employee;
            if (!$employee || $employee->id !== $leaveRequest->employee_id) {
                abort(403, 'You can only view your own leave requests.');
            }
        } else {
            $this->authorize('view leave requests');
        }

        $leaveRequest->load(['employee', 'approvedByUser']);

        return view('leave-requests.show', compact('leaveRequest'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LeaveRequest $leaveRequest)
    {
        $user = Auth::user();

        if ($user->hasRole('Employee')) {
            $employee = $user->employee;
            if (!$employee || $employee->id !== $leaveRequest->employee_id) {
                abort(403, 'You can only edit your own leave requests.');
            }

            if ($leaveRequest->status !== 'pending') {
                return redirect()->back()->with('error', 'You can only edit pending leave requests.');
            }
        } else {
            $this->authorize('edit leave requests');
        }

        $employees = Employee::active()->orderBy('last_name')->get();

        return view('leave-requests.edit', compact('leaveRequest', 'employees'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        $user = Auth::user();

        if ($user->hasRole('Employee')) {
            $employee = $user->employee;
            if (!$employee || $employee->id !== $leaveRequest->employee_id) {
                abort(403, 'You can only update your own leave requests.');
            }

            if ($leaveRequest->status !== 'pending') {
                return redirect()->back()->with('error', 'You can only edit pending leave requests.');
            }
        } else {
            $this->authorize('edit leave requests');
        }

        $request->validate([
            'leave_type' => 'required|in:sick,vacation,emergency,maternity,paternity,bereavement,special',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
            'is_paid' => 'boolean',
        ]);

        // Calculate days requested
        $startDate = \Carbon\Carbon::parse($request->start_date);
        $endDate = \Carbon\Carbon::parse($request->end_date);
        $daysRequested = $startDate->diffInDays($endDate) + 1;

        $leaveRequest->update([
            'leave_type' => $request->leave_type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'days_requested' => $daysRequested,
            'reason' => $request->reason,
            'is_paid' => $request->boolean('is_paid', true),
        ]);

        return redirect()->route('leave-requests.index')->with('success', 'Leave request updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LeaveRequest $leaveRequest)
    {
        $user = Auth::user();

        if ($user->hasRole('Employee')) {
            $employee = $user->employee;
            if (!$employee || $employee->id !== $leaveRequest->employee_id) {
                abort(403, 'You can only delete your own leave requests.');
            }

            if ($leaveRequest->status !== 'pending') {
                return redirect()->back()->with('error', 'You can only delete pending leave requests.');
            }
        } else {
            $this->authorize('delete leave requests');
        }

        $leaveRequest->delete();

        return redirect()->route('leave-requests.index')->with('success', 'Leave request deleted successfully.');
    }
}
