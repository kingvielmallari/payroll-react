<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\TimeLog;
use App\Models\CashAdvance;
use App\Models\Department;
use App\Models\Position;
use App\Models\PayScheduleSetting;
use App\Models\DeductionTaxSetting;
use App\Models\AllowanceBonusSetting;
use App\Models\Holiday;
use App\Models\NoWorkSuspendedSetting;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index()
    {
        $user = Auth::user();

        // Get dashboard statistics based on user role
        $stats = $this->getDashboardStats($user);

        // Get recent activities
        $recentActivities = $this->getRecentActivities($user);

        // Get notifications
        $notifications = $this->getNotifications($user);

        // Get advanced dashboard data for System Administrator
        $dashboardData = [];

        if ($user->hasAnyRole(['System Administrator', 'HR Head', 'HR Staff'])) {
            $dashboardData = $this->getAdvancedDashboardData();
        }

        return view('dashboard', compact('stats', 'recentActivities', 'notifications', 'dashboardData'));
    }

    /**
     * Get advanced dashboard data for admin users
     */
    private function getAdvancedDashboardData()
    {
        $currentMonth = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();

        // Employee Statistics - All Status Types
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('employment_status', 'active')->count();
        $inactiveEmployees = Employee::where('employment_status', 'inactive')->count();
        $terminatedEmployees = Employee::where('employment_status', 'terminated')->count();
        $resignedEmployees = Employee::where('employment_status', 'resigned')->count();
        $newEmployeesThisMonth = Employee::whereMonth('hire_date', $currentMonth->month)
            ->whereYear('hire_date', $currentMonth->year)
            ->count();
        $newEmployeesLastMonth = Employee::whereMonth('hire_date', $lastMonth->month)
            ->whereYear('hire_date', $lastMonth->year)
            ->count();

        // Payroll Statistics - All Status Types
        $totalPayrolls = Payroll::count();
        $draftPayrolls = Payroll::where('status', 'draft')->count();
        $processingPayrolls = Payroll::where('status', 'processing')->count();
        $approvedPayrolls = Payroll::where('status', 'approved')->count();
        $paidPayrolls = Payroll::where('is_paid', true)->count();
        $totalPayrollAmount = Payroll::where('is_paid', true)->sum('total_gross');

        // Cash Advance Statistics - All Status Types
        $totalCashAdvances = CashAdvance::count();
        $pendingCashAdvances = CashAdvance::where('status', 'pending')->count();
        $approvedCashAdvances = CashAdvance::where('status', 'approved')->count();
        $rejectedCashAdvances = CashAdvance::where('status', 'rejected')->count();
        $completedCashAdvances = CashAdvance::where('status', 'completed')->count();
        $totalCashAdvanceAmount = CashAdvance::where('status', 'approved')->sum('approved_amount');
        $outstandingCashAdvances = CashAdvance::where('status', 'approved')->sum('outstanding_balance');

        // Active Pay Schedules
        $activePaySchedules = PayScheduleSetting::where('is_active', true)->count();

        // Active Deductions & Tax
        $activeDeductionsTax = DeductionTaxSetting::where('is_active', true)->count();

        // Active Allowances & Bonus
        $activeAllowancesBonus = AllowanceBonusSetting::where('is_active', true)->count();

        // Active Holidays
        $activeHolidays = Holiday::where('is_active', true)
            ->whereYear('date', Carbon::now()->year)
            ->count();

        // Active Suspensions (using NoWorkSuspendedSetting)
        $activeSuspensions = NoWorkSuspendedSetting::where('status', 'active')->count();

        // Monthly Payroll Totals
        $currentMonthPayroll = Payroll::where('is_paid', true)
            ->whereMonth('period_start', $currentMonth->month)
            ->whereYear('period_start', $currentMonth->year)
            ->sum('total_gross');

        $lastMonthPayroll = Payroll::where('is_paid', true)
            ->whereMonth('period_start', $lastMonth->month)
            ->whereYear('period_start', $lastMonth->year)
            ->sum('total_gross');

        // Department Statistics
        $departmentStats = Department::withCount(['employees' => function ($query) {
            $query->where('employment_status', 'active');
        }])->get();

        // Employment Type Distribution
        $employmentTypes = Employee::where('employment_status', 'active')
            ->selectRaw('employment_type, COUNT(*) as count')
            ->groupBy('employment_type')
            ->get();

        // Pay Schedule Distribution
        $paySchedules = Employee::where('employment_status', 'active')
            ->selectRaw('pay_schedule, COUNT(*) as count')
            ->groupBy('pay_schedule')
            ->get();

        // Monthly Trends (last 6 months)
        $monthlyTrends = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthlyTrends[] = [
                'month' => $month->format('M Y'),
                'employees_hired' => Employee::whereMonth('hire_date', $month->month)
                    ->whereYear('hire_date', $month->year)
                    ->count(),
                'payrolls_processed' => Payroll::where('is_paid', true)
                    ->whereMonth('period_start', $month->month)
                    ->whereYear('period_start', $month->year)
                    ->count(),
                'total_paid' => Payroll::where('is_paid', true)
                    ->whereMonth('period_start', $month->month)
                    ->whereYear('period_start', $month->year)
                    ->sum('total_net'),
            ];
        }

        // Calculate growth rates
        $employeeGrowthRate = $newEmployeesLastMonth > 0
            ? (($newEmployeesThisMonth - $newEmployeesLastMonth) / $newEmployeesLastMonth) * 100
            : ($newEmployeesThisMonth > 0 ? 100 : 0);

        $payrollGrowthRate = $lastMonthPayroll > 0
            ? (($currentMonthPayroll - $lastMonthPayroll) / $lastMonthPayroll) * 100
            : ($currentMonthPayroll > 0 ? 100 : 0);

        // Cash advance growth rate calculation
        $currentMonthCashAdvances = CashAdvance::whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->count();
        $lastMonthCashAdvances = CashAdvance::whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->count();

        $cashAdvanceGrowthRate = $lastMonthCashAdvances > 0
            ? (($currentMonthCashAdvances - $lastMonthCashAdvances) / $lastMonthCashAdvances) * 100
            : ($currentMonthCashAdvances > 0 ? 100 : 0);

        return [
            'employee_stats' => [
                'total' => $totalEmployees,
                'active' => $activeEmployees,
                'inactive' => $inactiveEmployees,
                'terminated' => $terminatedEmployees,
                'resigned' => $resignedEmployees,
                'growth_rate' => round($employeeGrowthRate, 1),
            ],
            'payroll_stats' => [
                'total' => $totalPayrolls,
                'draft' => $draftPayrolls,
                'processing' => $processingPayrolls,
                'approved' => $approvedPayrolls,
                'paid' => $paidPayrolls,
                'total_amount' => $totalPayrollAmount,
                'growth_rate' => round($payrollGrowthRate, 1),
            ],
            'cash_advance_stats' => [
                'total_requests' => $totalCashAdvances,
                'pending_requests' => $pendingCashAdvances,
                'approved_requests' => $approvedCashAdvances,
                'rejected_requests' => $rejectedCashAdvances,
                'completed_requests' => $completedCashAdvances,
                'total_amount' => $totalCashAdvanceAmount,
                'outstanding_balance' => $outstandingCashAdvances,
                'growth_rate' => round($cashAdvanceGrowthRate, 1),
            ],
            'other_stats' => [
                'active_pay_schedules' => $activePaySchedules,
                'deductions_tax' => $activeDeductionsTax,
                'allowances_bonus' => $activeAllowancesBonus,
                'holidays' => $activeHolidays,
                'suspensions' => $activeSuspensions,
            ],
        ];
    }

    /**
     * Get dashboard statistics based on user role.
     */
    private function getDashboardStats($user)
    {
        $stats = [];

        if ($user->hasAnyRole(['System Admin', 'HR Head', 'HR Staff'])) {
            // Admin/HR stats
            $stats['total_employees'] = Employee::where('employment_status', 'active')->count();
            $stats['pending_payrolls'] = Payroll::where('status', 'processing')->count();
            $stats['pending_cash_advances'] = CashAdvance::where('status', 'pending')->count();
            $stats['active_payrolls'] = Payroll::whereIn('status', ['draft', 'processing'])->count();

            // Current month payroll totals
            $currentMonth = Carbon::now()->format('Y-m');
            $monthlyPayroll = Payroll::where('is_paid', true)
                ->whereRaw("DATE_FORMAT(period_start, '%Y-%m') = ?", [$currentMonth])
                ->sum('total_net');
            $stats['monthly_payroll'] = $monthlyPayroll;

            // Cash advance statistics
            $stats['total_cash_advances'] = CashAdvance::count();
            $stats['outstanding_advances'] = CashAdvance::where('status', 'approved')->sum('outstanding_balance');
        } else {
            // Employee stats
            $employee = $user->employee;
            if ($employee) {
                $stats['my_time_logs'] = TimeLog::where('employee_id', $employee->id)
                    ->whereMonth('log_date', Carbon::now()->month)
                    ->count();
                $stats['my_cash_advances'] = CashAdvance::where('employee_id', $employee->id)
                    ->whereYear('created_at', Carbon::now()->year)
                    ->count();
                $stats['pending_advances'] = CashAdvance::where('employee_id', $employee->id)
                    ->where('status', 'pending')
                    ->count();

                // Latest payslip
                $latestPayroll = Payroll::whereHas('payrollDetails', function ($query) use ($employee) {
                    $query->where('employee_id', $employee->id);
                })->latest('period_end')->first();

                $stats['latest_payroll'] = $latestPayroll ? $latestPayroll->period_end->format('M Y') : 'N/A';
            }
        }

        return $stats;
    }

    /**
     * Get recent activities based on user role.
     */
    private function getRecentActivities($user)
    {
        $activities = [];

        if ($user->hasAnyRole(['System Administrator', 'HR Head', 'HR Staff'])) {
            // Recent payrolls
            $recentPayrolls = Payroll::with('creator')
                ->latest()
                ->take(3)
                ->get();

            foreach ($recentPayrolls as $payroll) {
                $activities[] = [
                    'type' => 'payroll',
                    'message' => "Payroll {$payroll->payroll_number} was {$payroll->status}",
                    'date' => $payroll->created_at,
                    'user' => $payroll->creator->name ?? 'System',
                ];
            }

            // Recent cash advances
            $recentCashAdvances = CashAdvance::with(['employee.user', 'requestedBy'])
                ->latest()
                ->take(3)
                ->get();

            foreach ($recentCashAdvances as $advance) {
                $activities[] = [
                    'type' => 'cash_advance',
                    'message' => "Cash advance {$advance->reference_number} for {$advance->employee->full_name} - {$advance->status}",
                    'date' => $advance->created_at,
                    'user' => $advance->requestedBy->name ?? $advance->employee->user->name,
                ];
            }

            // Recent employee additions
            $recentEmployees = Employee::with('user')
                ->latest()
                ->take(2)
                ->get();

            foreach ($recentEmployees as $employee) {
                $activities[] = [
                    'type' => 'employee',
                    'message' => "New employee {$employee->full_name} added",
                    'date' => $employee->created_at,
                    'user' => 'HR System',
                ];
            }
        } else {
            // Employee's own activities
            $employee = $user->employee;
            if ($employee) {
                // Recent time logs
                $recentTimeLogs = TimeLog::where('employee_id', $employee->id)
                    ->latest()
                    ->take(3)
                    ->get();

                foreach ($recentTimeLogs as $timeLog) {
                    $activities[] = [
                        'type' => 'time_log',
                        'message' => "Time log for " . $timeLog->log_date->format('M d, Y'),
                        'date' => $timeLog->created_at,
                        'user' => 'You',
                    ];
                }

                // Recent cash advances
                $recentAdvances = CashAdvance::where('employee_id', $employee->id)
                    ->latest()
                    ->take(3)
                    ->get();

                foreach ($recentAdvances as $advance) {
                    $activities[] = [
                        'type' => 'cash_advance',
                        'message' => "Cash advance {$advance->reference_number} - {$advance->status}",
                        'date' => $advance->created_at,
                        'user' => 'You',
                    ];
                }
            }
        }

        // Sort activities by date
        usort($activities, function ($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        return array_slice($activities, 0, 8);
    }

    /**
     * Get notifications for the user.
     */
    private function getNotifications($user)
    {
        $notifications = [];

        // System Administrator gets no notifications
        if ($user->hasRole('System Administrator')) {
            return $notifications; // Return empty array
        } elseif ($user->hasRole('HR Head')) {
            // Processing payrolls
            $pendingPayrolls = Payroll::where('status', 'processing')->count();
            if ($pendingPayrolls > 0) {
                $notifications[] = [
                    'type' => 'warning',
                    'message' => "{$pendingPayrolls} payrolls are processing",
                    'link' => route('payrolls.index', ['status' => 'processing']),
                ];
            }

            // Pending cash advance approvals
            $pendingAdvances = CashAdvance::where('status', 'pending')->count();
            if ($pendingAdvances > 0) {
                $notifications[] = [
                    'type' => 'info',
                    'message' => "{$pendingAdvances} cash advances need approval",
                    'link' => route('cash-advances.index', ['status' => 'pending']),
                ];
            }

            // Pending paid leave requests
            $pendingLeaves = \App\Models\PaidLeave::where('status', 'pending')->count();
            if ($pendingLeaves > 0) {
                $notifications[] = [
                    'type' => 'purple',
                    'message' => "{$pendingLeaves} paid leaves need approval",
                    'link' => route('paid-leaves.index', ['status' => 'pending']),
                ];
            }
        } else {
            // Employee notifications
            $employee = $user->employee;
            if ($employee) {
                // Pending cash advances
                $pendingAdvances = CashAdvance::where('employee_id', $employee->id)
                    ->where('status', 'pending')
                    ->count();
                if ($pendingAdvances > 0) {
                    $notifications[] = [
                        'type' => 'info',
                        'message' => "You have {$pendingAdvances} pending cash advance request(s)",
                        'link' => route('cash-advances.index'),
                    ];
                }

                // Overdue advances - Check based on first_deduction_date and if still has outstanding balance
                $overdueAdvances = CashAdvance::where('employee_id', $employee->id)
                    ->where('status', 'approved')
                    ->where('first_deduction_date', '<', Carbon::now())
                    ->where('outstanding_balance', '>', 0)
                    ->count();
                if ($overdueAdvances > 0) {
                    $notifications[] = [
                        'type' => 'warning',
                        'message' => "You have {$overdueAdvances} overdue cash advance(s)",
                        'link' => route('cash-advances.index'),
                    ];
                }
            }
        }

        return $notifications;
    }
}
