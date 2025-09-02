<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use App\Models\TimeSchedule;
use App\Models\DaySchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EmployeeController extends Controller
{
    use AuthorizesRequests;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('view employees');

        $query = Employee::with(['user', 'department', 'position']);

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('department')) {
            $query->where('department_id', $request->department);
        }

        if ($request->filled('employment_status')) {
            $query->where('employment_status', $request->employment_status);
        }

        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->employment_type);
        }

        // Apply sorting
        if ($request->filled('sort_name')) {
            if ($request->sort_name === 'asc') {
                $query->orderBy('first_name', 'asc')->orderBy('last_name', 'asc');
            } elseif ($request->sort_name === 'desc') {
                $query->orderBy('first_name', 'desc')->orderBy('last_name', 'desc');
            }
        } elseif ($request->filled('sort_hire_date')) {
            $query->orderBy('hire_date', $request->sort_hire_date);
        } else {
            // Default sorting - latest records first
            $query->latest();
        }

        // Paginate with configurable records per page (default 10)
        $perPage = $request->get('per_page', 10);
        $employees = $query->paginate($perPage)->withQueryString();
        $departments = Department::active()->get();

        // Get performance data for current month (based on DTR)
        $currentMonth = \Carbon\Carbon::now();
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        // Calculate performance metrics for active employees
        $performanceData = Employee::where('employment_status', 'active')
            ->with(['timeLogs' => function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('log_date', [$startOfMonth, $endOfMonth]);
            }])
            ->get()
            ->map(function ($employee) {
                $totalHours = $employee->timeLogs->sum('regular_hours');
                $hourlyRate = $employee->hourly_rate ?: ($employee->basic_salary / 22 / 8); // fallback calculation
                $calculatedSalary = $totalHours * $hourlyRate;

                return [
                    'employee' => $employee,
                    'total_hours' => $totalHours,
                    'calculated_salary' => $calculatedSalary,
                    'avg_daily_hours' => $employee->timeLogs->count() > 0 ? $totalHours / $employee->timeLogs->count() : 0,
                ];
            })
            ->filter(function ($data) {
                return $data['total_hours'] > 0; // Only include employees with DTR records
            });

        // Top 5 performers (highest calculated salary)
        $topPerformers = $performanceData->sortByDesc('calculated_salary')->take(5);

        // Least 5 performers (lowest calculated salary but still have some hours)
        $leastPerformers = $performanceData->sortBy('calculated_salary')->take(5);

        return view('employees.index', compact('employees', 'departments', 'topPerformers', 'leastPerformers', 'currentMonth'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create employees');

        $departments = Department::active()->get();
        $positions = Position::active()->get();
        $timeSchedules = TimeSchedule::active()->get();
        $daySchedules = DaySchedule::active()->get();
        $roles = Role::whereIn('name', ['HR Head', 'HR Staff', 'Employee'])->get();
        $paySchedules = \App\Models\PayScheduleSetting::all();

        // Get employee default settings
        $employeeSettings = [
            'employee_number_prefix' => Cache::get('employee_setting_employee_number_prefix', 'EMP'),
            'auto_generate_employee_number' => Cache::get('employee_setting_auto_generate_employee_number', true),
            'default_department_id' => Cache::get('employee_setting_default_department_id'),
            'default_position_id' => Cache::get('employee_setting_default_position_id'),
            'default_employment_type' => Cache::get('employee_setting_default_employment_type', 'regular'),
            'default_employment_status' => Cache::get('employee_setting_default_employment_status', 'active'),
            'default_time_schedule_id' => Cache::get('employee_setting_default_time_schedule_id'),
            'default_day_schedule' => Cache::get('employee_setting_default_day_schedule', 'monday_to_friday'),
            'default_pay_schedule' => Cache::get('employee_setting_default_pay_schedule'),
            'default_paid_leaves' => Cache::get('employee_setting_default_paid_leaves', 15),
        ];

        // Get active deduction settings for salary calculation preview
        $deductionSettings = \App\Models\DeductionSetting::active()->get();

        return view('employees.create', compact('departments', 'positions', 'timeSchedules', 'daySchedules', 'roles', 'deductionSettings', 'paySchedules', 'employeeSettings'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create employees');

        // Create conditional validation rules for paid_leaves
        $paidLeavesRule = $request->benefits_status === 'without_benefits'
            ? 'nullable|integer|min:0|max:365'
            : 'required|integer|min:0|max:365';

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'suffix' => 'nullable|string|max:10',
            'email' => 'required|email|unique:users,email',
            'employee_number' => 'required|string|unique:employees,employee_number',
            'department_id' => 'required|exists:departments,id',
            'position_id' => 'required|exists:positions,id',
            'birth_date' => 'required|date|before:today',
            'gender' => 'required|in:male,female',
            'civil_status' => 'required|in:single,married,divorced,widowed',
            'phone' => 'nullable|string|max:20',
            'address' => 'required|string',
            'hire_date' => 'required|date',
            'paid_leaves' => $paidLeavesRule,
            'benefits_status' => 'required|in:with_benefits,without_benefits',
            'employment_type' => 'required|in:regular,probationary,contractual,part_time',
            'employment_status' => 'required|in:active,inactive,terminated,resigned',
            'pay_schedule' => 'required|in:monthly,semi_monthly,weekly',
            'basic_salary' => 'required|numeric|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'daily_rate' => 'nullable|numeric|min:0',
            'weekly_rate' => 'nullable|numeric|min:0',
            'semi_monthly_rate' => 'nullable|numeric|min:0',
            'sss_number' => 'nullable|string|max:20',
            'philhealth_number' => 'nullable|string|max:20',
            'pagibig_number' => 'nullable|string|max:20',
            'tin_number' => 'nullable|string|max:20',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_relationship' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:255',
            'role' => 'required|exists:roles,name',
            'time_schedule_id' => 'required|exists:time_schedules,id',
            'day_schedule_id' => 'required|exists:day_schedules,id',
        ]);

        try {
            // Map employment status to user status
            $userStatusMap = [
                'active' => 'active',
                'inactive' => 'inactive',
                'terminated' => 'inactive',
                'resigned' => 'inactive'
            ];

            $userStatus = $userStatusMap[$validated['employment_status']] ?? 'active';

            // Create user account
            $user = User::create([
                'name' => trim("{$validated['first_name']} {$validated['last_name']}"),
                'email' => $validated['email'],
                'password' => Hash::make($validated['employee_number']), // Use employee number as default password
                'employee_id' => $validated['employee_number'],
                'status' => $userStatus,
                'email_verified_at' => now(),
            ]);

            // Assign role to user
            $user->assignRole($validated['role']);

            // Create employee record
            $employeeData = collect($validated)->except(['email', 'role'])->toArray();
            $employeeData['user_id'] = $user->id;

            // Set paid_leaves to null if benefits_status is without_benefits and paid_leaves is empty
            if ($employeeData['benefits_status'] === 'without_benefits' && empty($employeeData['paid_leaves'])) {
                $employeeData['paid_leaves'] = null;
            }

            $employee = Employee::create($employeeData);

            return redirect()->route('employees.show', $employee)
                ->with('success', "Employee created successfully! Default password is: {$validated['employee_number']}");
        } catch (\Exception $e) {
            // Log the detailed error for debugging
            Log::error('Employee creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $request->except(['password']),
                'user_id' => $user->id ?? null
            ]);

            // If user was created but employee creation failed, clean up the user
            if (isset($user) && $user->exists) {
                $user->delete();
            }

            return back()->withInput()
                ->withErrors(['error' => 'Failed to create employee: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        $this->authorize('view employees');

        $employee->load(['user.roles', 'department', 'position', 'timeSchedule', 'daySchedule', 'timeLogs', 'payrollDetails', 'deductions', 'leaveRequests']);

        return view('employees.show', compact('employee'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        $this->authorize('edit employees');

        $employee->load(['user.roles', 'timeSchedule', 'daySchedule']);
        $departments = Department::active()->get();
        $positions = Position::active()->get();
        $timeSchedules = TimeSchedule::active()->get();
        $daySchedules = DaySchedule::active()->get();
        $roles = Role::whereIn('name', ['HR Head', 'HR Staff', 'Employee'])->get();

        return view('employees.edit', compact('employee', 'departments', 'positions', 'timeSchedules', 'daySchedules', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        $this->authorize('edit employees');

        // Create conditional validation rules for paid_leaves
        $paidLeavesRule = $request->benefits_status === 'without_benefits'
            ? 'nullable|integer|min:0|max:365'
            : 'required|integer|min:0|max:365';

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'suffix' => 'nullable|string|max:10',
            'email' => ['required', 'email', Rule::unique('users')->ignore($employee->user_id)],
            'employee_number' => ['required', 'string', Rule::unique('employees')->ignore($employee->id)],
            'department_id' => 'required|exists:departments,id',
            'position_id' => 'required|exists:positions,id',
            'birth_date' => 'required|date|before:today',
            'gender' => 'required|in:male,female',
            'civil_status' => 'required|in:single,married,divorced,widowed',
            'phone' => 'nullable|string|max:20',
            'address' => 'required|string',
            'hire_date' => 'required|date',
            'paid_leaves' => $paidLeavesRule,
            'benefits_status' => 'required|in:with_benefits,without_benefits',
            'employment_type' => 'required|in:regular,probationary,contractual,part_time',
            'employment_status' => 'required|in:active,inactive,terminated,resigned',
            'pay_schedule' => 'required|in:monthly,semi_monthly,weekly',
            'basic_salary' => 'required|numeric|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'daily_rate' => 'nullable|numeric|min:0',
            'weekly_rate' => 'nullable|numeric|min:0',
            'semi_monthly_rate' => 'nullable|numeric|min:0',
            'sss_number' => 'nullable|string|max:20',
            'philhealth_number' => 'nullable|string|max:20',
            'pagibig_number' => 'nullable|string|max:20',
            'tin_number' => 'nullable|string|max:20',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_relationship' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:255',
            'role' => 'required|exists:roles,name',
            'time_schedule_id' => 'required|exists:time_schedules,id',
            'day_schedule_id' => 'required|exists:day_schedules,id',
        ]);

        try {
            Log::info('Employee update attempt', [
                'employee_id' => $employee->id,
                'validated_data' => $validated
            ]);

            // Map employment status to user status
            $userStatusMap = [
                'active' => 'active',
                'inactive' => 'inactive',
                'terminated' => 'inactive',
                'resigned' => 'inactive'
            ];

            $userStatus = $userStatusMap[$validated['employment_status']] ?? 'active';

            // Update user account
            $employee->user->update([
                'name' => trim("{$validated['first_name']} {$validated['last_name']}"),
                'email' => $validated['email'],
                'employee_id' => $validated['employee_number'],
                'status' => $userStatus,
            ]);

            // Update user role
            $employee->user->syncRoles([$validated['role']]);

            // Update employee record
            $employeeData = collect($validated)->except(['email', 'role'])->toArray();

            // Set paid_leaves to null if benefits_status is without_benefits and paid_leaves is empty
            if ($employeeData['benefits_status'] === 'without_benefits' && empty($employeeData['paid_leaves'])) {
                $employeeData['paid_leaves'] = null;
            }

            $employee->update($employeeData);

            Log::info('Employee updated successfully', [
                'employee_id' => $employee->id,
                'new_employment_status' => $employee->fresh()->employment_status,
                'user_status' => $userStatus
            ]);

            return redirect()->route('employees.show', $employee)
                ->with('success', 'Employee updated successfully!');
        } catch (\Exception $e) {
            return back()->withInput()
                ->withErrors(['error' => 'Failed to update employee: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        $this->authorize('delete employees');

        try {
            // Prevent deletion of System Administrator
            if ($employee->user && $employee->user->hasRole('System Admin')) {
                return redirect()->route('employees.index')
                    ->with('error', 'Cannot delete System Administrator account.');
            }

            // Prevent users from deleting their own employee record
            if (Auth::user()->employee && Auth::user()->employee->id === $employee->id) {
                return redirect()->route('employees.index')
                    ->with('error', 'You cannot delete your own employee record.');
            }

            $employeeName = $employee->full_name;

            // Delete the user account (this will cascade delete the employee)
            $employee->user->delete();

            return redirect()->route('employees.index')
                ->with('success', "Employee {$employeeName} deleted successfully!");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete employee: ' . $e->getMessage()]);
        }
    }

    /**
     * Generate a unique employee number.
     */
    private function generateEmployeeNumber()
    {
        $year = date('Y');
        $lastEmployee = Employee::where('employee_number', 'like', "EMP-{$year}-%")
            ->orderBy('employee_number', 'desc')
            ->first();

        if ($lastEmployee) {
            $lastNumber = (int) substr($lastEmployee->employee_number, -4);
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return "EMP-{$year}-{$newNumber}";
    }

    /**
     * Calculate deductions for salary preview
     */
    public function calculateDeductions(Request $request)
    {
        $salary = (float) $request->input('salary', 0);
        $benefitsStatus = $request->input('benefits_status');
        $paySchedule = $request->input('pay_schedule');

        if ($salary <= 0) {
            return response()->json(['deductions' => [], 'total_deductions' => 0, 'net_pay' => 0]);
        }

        $deductions = [];
        $totalDeductions = 0;

        // Calculate basic pay components
        $basicPay = $salary; // Assuming salary input is basic pay
        $overtime = 0;
        $bonus = 0;
        $allowances = 0;
        $grossPay = $basicPay + $overtime + $bonus + $allowances;

        // Only calculate deductions if employee has benefits
        if ($benefitsStatus === 'with_benefits') {
            // Convert pay schedule to pay frequency
            $payFrequency = $paySchedule ?? 'semi_monthly';

            // Get active government deductions (SSS, PhilHealth, Pag-IBIG)
            $governmentDeductions = \App\Models\DeductionTaxSetting::active()
                ->where('type', 'government')
                ->get();

            $governmentDeductionTotal = 0;

            foreach ($governmentDeductions as $setting) {
                $amount = $setting->calculateDeduction($basicPay, $overtime, $bonus, $allowances, $grossPay, null, null, $salary, $payFrequency);

                if ($amount > 0) {
                    $deductions[] = [
                        'name' => $setting->name,
                        'amount' => $amount,
                        'formatted_amount' => '₱' . number_format($amount, 2),
                        'type' => $setting->type
                    ];
                    $totalDeductions += $amount;
                    $governmentDeductionTotal += $amount;
                }
            }

            // Calculate taxable income (gross pay minus government deductions)
            $taxableIncome = $grossPay - $governmentDeductionTotal;

            // Get withholding tax deductions
            $taxDeductions = \App\Models\DeductionTaxSetting::active()
                ->where('type', 'government')
                ->where('tax_table_type', 'withholding_tax')
                ->get();

            foreach ($taxDeductions as $setting) {
                $amount = $setting->calculateDeduction($basicPay, $overtime, $bonus, $allowances, $grossPay, $taxableIncome, null, $salary, $payFrequency);

                if ($amount > 0) {
                    $deductions[] = [
                        'name' => $setting->name,
                        'amount' => $amount,
                        'formatted_amount' => '₱' . number_format($amount, 2),
                        'type' => $setting->type
                    ];
                    $totalDeductions += $amount;
                }
            }
        }

        $netPay = $grossPay - $totalDeductions;

        return response()->json([
            'deductions' => $deductions,
            'total_deductions' => $totalDeductions,
            'formatted_total_deductions' => '₱' . number_format($totalDeductions, 2),
            'net_pay' => $netPay,
            'formatted_net_pay' => '₱' . number_format($netPay, 2),
            'gross_pay' => $grossPay,
            'formatted_gross_pay' => '₱' . number_format($grossPay, 2),
            'basic_pay' => $basicPay,
            'formatted_basic_pay' => '₱' . number_format($basicPay, 2),
            'taxable_income' => $taxableIncome ?? 0,
            'formatted_taxable_income' => '₱' . number_format($taxableIncome ?? 0, 2)
        ]);
    }

    /**
     * Check if employee number already exists
     */
    public function checkDuplicate(Request $request)
    {
        $request->validate([
            'employee_number' => 'required|string'
        ]);

        $exists = Employee::where('employee_number', $request->employee_number)->exists();

        return response()->json(['exists' => $exists]);
    }
}
