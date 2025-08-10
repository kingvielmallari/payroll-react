<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\DTR;
use App\Models\TimeLog;
use App\Models\Holiday;
use App\Models\PayrollScheduleSetting;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class DTRController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display DTR management interface with period selection
     */
    public function index(Request $request)
    {
        $this->authorize('view time logs');

        // Get payroll settings
        $payrollSettings = PayrollScheduleSetting::first();
        if (!$payrollSettings) {
            return redirect()->back()->with('error', 'Payroll schedule settings not configured.');
        }

        $currentPeriod = $this->getCurrentPayrollPeriod($payrollSettings);
        $availablePeriods = $this->getAvailablePeriods($payrollSettings);

        // If no specific period requested, show period selection
        if (!$request->has('period')) {
            return view('dtr.index', compact('currentPeriod', 'availablePeriods'));
        }

        // Get selected period details
        $selectedPeriod = $this->getPeriodByKey($request->get('period'), $payrollSettings);
        if (!$selectedPeriod) {
            return redirect()->route('dtr.index')->with('error', 'Invalid period selected.');
        }

        // Get employees for the selected period
        $employees = Employee::with(['user', 'department'])
            ->where('status', 'active')
            ->get();

        return view('dtr.period-employees', compact('employees', 'selectedPeriod', 'payrollSettings'));
    }

    /**
     * Show DTR record
     */
    public function show(DTR $dtr)
    {
        $this->authorize('view time logs');
        
        $dtr->load(['employee.user', 'employee.department']);
        
        // Create array of all dates in the DTR period for easy iteration
        $periodDates = [];
        $current = $dtr->period_start->copy();
        
        while ($current->lte($dtr->period_end)) {
            $periodDates[] = [
                'date' => $current->format('Y-m-d'),
                'day_name' => $current->format('l'),
                'day_short' => $current->format('D'),
                'is_weekend' => $current->isWeekend(),
                'formatted' => $current->format('M d'),
                'carbon' => $current->copy()
            ];
            $current->addDay();
        }
        
        return view('dtr.show', compact('dtr', 'periodDates'));
    }

    /**
     * Edit DTR record
     */
    public function edit(DTR $dtr)
    {
        $this->authorize('edit time logs');
        
        $dtr->load(['employee.user', 'employee.department']);
        
        // Create array of all dates in the DTR period for easy iteration
        $periodDates = [];
        $current = $dtr->period_start->copy();
        
        while ($current->lte($dtr->period_end)) {
            $periodDates[] = [
                'date' => $current->format('Y-m-d'),
                'day_name' => $current->format('l'),
                'day_short' => $current->format('D'),
                'is_weekend' => $current->isWeekend(),
                'formatted' => $current->format('M d'),
                'carbon' => $current->copy()
            ];
            $current->addDay();
        }
        
        return view('dtr.edit', compact('dtr', 'periodDates'));
    }

    /**
     * Create DTR instantly for employees in a payroll period - SIMPLIFIED VERSION
     */
    public function createInstant(Request $request)
    {
        try {
            // Get the payroll ID from the request
            $payrollId = $request->input('payroll_id');
            $periodStart = $request->input('period_start');
            $periodEnd = $request->input('period_end');
            
            if (!$payrollId || !$periodStart || !$periodEnd) {
                return redirect()->back()->with('error', 'Missing required parameters for DTR creation.');
            }
            
            // Get the payroll with employees
            $payroll = \App\Models\Payroll::with('payrollDetails.employee')->find($payrollId);
            
            if (!$payroll) {
                return redirect()->back()->with('error', 'Payroll not found.');
            }
            
            // Check if we have employees
            if ($payroll->payrollDetails->isEmpty()) {
                return redirect()->back()->with('error', 'No employees found in this payroll.');
            }
            
            $createdCount = 0;
            $firstDTRId = null;
            
            // Create DTR for each employee
            foreach ($payroll->payrollDetails as $detail) {
                $employee = $detail->employee;
                
                // Check if DTR already exists
                $existing = DB::table('d_t_r_s')->where([
                    'employee_id' => $employee->id,
                    'payroll_id' => $payroll->id,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd
                ])->first();
                
                if ($existing) {
                    if (!$firstDTRId) {
                        $firstDTRId = $existing->id;
                    }
                    continue; // Skip if already exists
                }
                
                // Create simple DTR record using DB query to avoid model issues
                $dtrId = DB::table('d_t_r_s')->insertGetId([
                    'employee_id' => $employee->id,
                    'payroll_id' => $payroll->id,
                    'period_type' => 'semi_monthly',
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'month_year' => date('Y-m', strtotime($periodStart)),
                    'regular_days' => 0,
                    'saturday_count' => 0,
                    'dtr_data' => '{}', // Empty JSON
                    'total_regular_hours' => 0.00,
                    'total_overtime_hours' => 0.00,
                    'total_late_hours' => 0.00,
                    'total_undertime_hours' => 0.00,
                    'status' => 'draft',
                    'created_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                if (!$firstDTRId) {
                    $firstDTRId = $dtrId;
                }
                
                $createdCount++;
            }
            
            if ($createdCount > 0) {
                $message = "Successfully created {$createdCount} DTR records!";
                return redirect()->route('dtr.show', $firstDTRId)->with('success', $message);
            } else {
                return redirect()->back()->with('info', 'DTR records already exist for this payroll period.');
            }
            
        } catch (\Exception $e) {
            // Log the actual error for debugging
            Log::error('DTR Creation Error: ' . $e->getMessage());
            Log::error('DTR Creation Stack: ' . $e->getTraceAsString());
            
            return redirect()->back()->with('error', 'Failed to create DTR: ' . $e->getMessage());
        }
    }

    /**
     * Test DTR creation - debug method
     */
    public function testCreate()
    {
        try {
            // Get first payroll
            $payroll = \App\Models\Payroll::with('payrollDetails.employee')->first();
            
            if (!$payroll) {
                return response()->json(['error' => 'No payroll found']);
            }
            
            $employee = $payroll->payrollDetails->first()->employee;
            
            // Test DTR creation using raw DB insert
            $dtrId = DB::table('d_t_r_s')->insertGetId([
                'employee_id' => $employee->id,
                'payroll_id' => $payroll->id,
                'period_type' => 'semi_monthly',
                'period_start' => $payroll->period_start,
                'period_end' => $payroll->period_end,
                'month_year' => Carbon::parse($payroll->period_start)->format('Y-m'),
                'regular_days' => 0,
                'saturday_count' => 0,
                'dtr_data' => '{}',
                'total_regular_hours' => 0,
                'total_overtime_hours' => 0,
                'total_late_hours' => 0,
                'total_undertime_hours' => 0,
                'status' => 'draft',
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'dtr_id' => $dtrId,
                'employee' => $employee->first_name . ' ' . $employee->last_name
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
        }
    }

    // Helper methods
    private function getCurrentPayrollPeriod($payrollSettings)
    {
        $today = Carbon::now();
        
        if ($payrollSettings->frequency === 'semi_monthly') {
            if ($today->day <= 15) {
                $startDate = $today->copy()->startOfMonth();
                $endDate = $today->copy()->day(15);
                $payDate = $today->copy()->day(25);
            } else {
                $startDate = $today->copy()->day(16);
                $endDate = $today->copy()->endOfMonth();
                $payDate = $today->copy()->addMonth()->day(10);
            }
        } else {
            $startDate = $today->copy()->startOfMonth();
            $endDate = $today->copy()->endOfMonth();
            $payDate = $today->copy()->addMonth()->day(15);
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'pay_date' => $payDate,
            'period_label' => $startDate->format('M d') . ' - ' . $endDate->format('M d, Y'),
            'pay_label' => 'Pay Date: ' . $payDate->format('M d, Y'),
        ];
    }

    private function getAvailablePeriods($payrollSettings)
    {
        // Implementation would go here
        return [];
    }

    private function getPeriodByKey($key, $payrollSettings)
    {
        // Implementation would go here
        return null;
    }
}
