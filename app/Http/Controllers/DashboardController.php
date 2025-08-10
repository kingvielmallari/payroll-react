<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\TimeLog;
// use App\Models\LeaveRequest; // Commented out until leave system is implemented
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

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
        
        return view('dashboard', compact('stats', 'recentActivities', 'notifications'));
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
            // $stats['pending_leave_requests'] = LeaveRequest::where('status', 'pending')->count();
            $stats['pending_leave_requests'] = 0; // Placeholder until leave system is implemented
            $stats['active_payrolls'] = Payroll::whereIn('status', ['draft', 'processing'])->count();
            
            // Current month payroll totals
            $currentMonth = Carbon::now()->format('Y-m');
            $monthlyPayroll = Payroll::where('status', 'approved')
                                   ->whereRaw("DATE_FORMAT(period_start, '%Y-%m') = ?", [$currentMonth])
                                   ->sum('total_net');
            $stats['monthly_payroll'] = $monthlyPayroll;
        } else {
            // Employee stats
            $employee = $user->employee;
            if ($employee) {
                $stats['my_time_logs'] = TimeLog::where('employee_id', $employee->id)
                                               ->whereMonth('log_date', Carbon::now()->month)
                                               ->count();
                // $stats['my_leave_requests'] = LeaveRequest::where('employee_id', $employee->id)
                //                                         ->whereYear('created_at', Carbon::now()->year)
                //                                         ->count();
                // $stats['pending_leaves'] = LeaveRequest::where('employee_id', $employee->id)
                //                                       ->where('status', 'pending')
                //                                       ->count();
                $stats['my_leave_requests'] = 0; // Placeholder
                $stats['pending_leaves'] = 0; // Placeholder
                
                // Latest payslip
                $latestPayroll = Payroll::whereHas('payrollDetails', function($query) use ($employee) {
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
        
        if ($user->hasAnyRole(['System Admin', 'HR Head', 'HR Staff'])) {
            // Recent payrolls
            $recentPayrolls = Payroll::with('creator')
                                   ->latest()
                                   ->take(5)
                                   ->get();
            
            foreach ($recentPayrolls as $payroll) {
                $activities[] = [
                    'type' => 'payroll',
                    'message' => "Payroll {$payroll->payroll_number} was {$payroll->status}",
                    'date' => $payroll->created_at,
                    'user' => $payroll->creator->name,
                ];
            }
            
            // Recent leave requests - commented out until leave system is implemented
            // $recentLeaves = LeaveRequest::with(['employee.user'])
            //                           ->latest()
            //                           ->take(5)
            //                           ->get();
            
            // foreach ($recentLeaves as $leave) {
            //     $activities[] = [
            //         'type' => 'leave',
            //         'message' => "{$leave->employee->user->name} requested {$leave->leave_type} leave",
            //         'date' => $leave->created_at,
            //         'user' => $leave->employee->user->name,
            //     ];
            // }
        } else {
            // Employee's own activities
            $employee = $user->employee;
            if ($employee) {
                // Recent time logs
                $recentTimeLogs = TimeLog::where('employee_id', $employee->id)
                                        ->latest()
                                        ->take(5)
                                        ->get();
                
                foreach ($recentTimeLogs as $timeLog) {
                    $activities[] = [
                        'type' => 'time_log',
                        'message' => "Time log for " . $timeLog->log_date->format('M d, Y'),
                        'date' => $timeLog->created_at,
                        'user' => 'You',
                    ];
                }
                
                // Recent leave requests - commented out until leave system is implemented
                // $recentLeaves = LeaveRequest::where('employee_id', $employee->id)
                //                           ->latest()
                //                           ->take(3)
                //                           ->get();
                
                // foreach ($recentLeaves as $leave) {
                //     $activities[] = [
                //         'type' => 'leave',
                //         'message' => "Leave request for {$leave->leave_type} - {$leave->status}",
                //         'date' => $leave->created_at,
                //         'user' => 'You',
                //     ];
                // }
            }
        }
        
        // Sort activities by date
        usort($activities, function($a, $b) {
            return $b['date'] <=> $a['date'];
        });
        
        return array_slice($activities, 0, 10);
    }
    
    /**
     * Get notifications for the user.
     */
    private function getNotifications($user)
    {
        $notifications = [];
        
        if ($user->hasAnyRole(['System Admin', 'HR Head'])) {
            // Pending payroll approvals
            $pendingPayrolls = Payroll::where('status', 'processing')->count();
            if ($pendingPayrolls > 0) {
                $notifications[] = [
                    'type' => 'warning', 
                    'message' => "{$pendingPayrolls} payrolls need approval",
                    'link' => route('payrolls.index'),
                ];
            }
            
            // Leave requests notification - commented out until leave system routes are implemented
            // $pendingLeaves = LeaveRequest::where('status', 'pending')->count();
            // if ($pendingLeaves > 0) {
            //     $notifications[] = [
            //         'type' => 'info',
            //         'message' => "{$pendingLeaves} leave requests need approval",
            //         'link' => route('leave-requests.index'),
            //     ];
            // }
        }
        
        return $notifications;
    }
}
