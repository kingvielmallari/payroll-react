<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Mail\PayslipMail;

class PayslipController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display payslip for a specific payroll detail.
     */
    public function show(PayrollDetail $payrollDetail)
    {
        $this->authorize('view payslips');

        $payrollDetail->load([
            'payroll',
            'employee.user',
            'employee.department',
            'employee.position'
        ]);

        // Check if user can view this payslip  
        if (!Auth::user()->can('view all payslips')) {
            $employee = Employee::where('user_id', Auth::id())->first();
            if (!$employee || $payrollDetail->employee_id !== $employee->id) {
                abort(403, 'You can only view your own payslip.');
            }
        }

        // For locked/approved payrolls, get snapshot data for accurate amounts
        $snapshot = null;
        if ($payrollDetail->payroll->status !== 'draft') {
            $snapshot = $payrollDetail->payroll->snapshots()
                ->where('employee_id', $payrollDetail->employee_id)
                ->first();
        }

        return view('payslips.show', compact('payrollDetail', 'snapshot'));
    }

    /**
     * Download payslip as PDF.
     */
    public function download(PayrollDetail $payrollDetail)
    {
        $this->authorize('download payslips');

        $payrollDetail->load([
            'payroll',
            'employee.user',
            'employee.department',
            'employee.position'
        ]);

        // Check if user can download this payslip
        if (!Auth::user()->can('download all payslips')) {
            $employee = Employee::where('user_id', Auth::id())->first();
            if (!$employee || $payrollDetail->employee_id !== $employee->id) {
                abort(403, 'You can only download your own payslip.');
            }
        }

        // For locked/approved payrolls, get snapshot data for accurate amounts
        $snapshot = null;
        if ($payrollDetail->payroll->status !== 'draft') {
            $snapshot = $payrollDetail->payroll->snapshots()
                ->where('employee_id', $payrollDetail->employee_id)
                ->first();
        }

        $pdf = Pdf::loadView('payslips.pdf', compact('payrollDetail', 'snapshot'));
        $pdf->setPaper('A4', 'portrait');

        $filename = 'payslip_' . $payrollDetail->employee->employee_number . '_' .
            $payrollDetail->payroll->period_start->format('Y-m') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Send payslip via email.
     */
    public function email(PayrollDetail $payrollDetail)
    {
        $this->authorize('email payslip');

        $payrollDetail->load([
            'payroll',
            'employee.user',
            'employee.department',
            'employee.position'
        ]);

        if (!$payrollDetail->employee->user || !$payrollDetail->employee->user->email) {
            return back()->with('error', 'Employee does not have a valid email address.');
        }

        // For locked/approved payrolls, get snapshot data for accurate amounts
        $snapshot = null;
        if ($payrollDetail->payroll->status !== 'draft') {
            $snapshot = $payrollDetail->payroll->snapshots()
                ->where('employee_id', $payrollDetail->employee_id)
                ->first();
        }

        try {
            Mail::to($payrollDetail->employee->user->email)
                ->send(new PayslipMail($payrollDetail, $snapshot));

            // Check if this is an AJAX request
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payslip sent successfully to ' . $payrollDetail->employee->user->email
                ]);
            }

            return back()->with('success', 'Payslip sent successfully to ' . $payrollDetail->employee->user->email);
        } catch (\Exception $e) {
            // Check if this is an AJAX request
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send payslip: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to send payslip: ' . $e->getMessage());
        }
    }

    /**
     * Send payslips to all employees for a specific payroll.
     */
    public function emailAll(Payroll $payroll)
    {
        $this->authorize('email all payslips');

        $payroll->load(['payrollDetails.employee.user']);

        $sent = 0;
        $failed = 0;
        $errors = [];

        foreach ($payroll->payrollDetails as $payrollDetail) {
            if (!$payrollDetail->employee->user || !$payrollDetail->employee->user->email) {
                $failed++;
                $errors[] = $payrollDetail->employee->first_name . ' ' . $payrollDetail->employee->last_name . ' - No email address';
                continue;
            }

            // Get snapshot data for accurate amounts if payroll is not draft
            $snapshot = null;
            if ($payroll->status !== 'draft') {
                $snapshot = $payroll->snapshots()
                    ->where('employee_id', $payrollDetail->employee_id)
                    ->first();
            }

            try {
                Mail::to($payrollDetail->employee->user->email)
                    ->send(new PayslipMail($payrollDetail, $snapshot));
                $sent++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = $payrollDetail->employee->first_name . ' ' . $payrollDetail->employee->last_name . ' - ' . $e->getMessage();
            }
        }

        $message = "Payslips sent: {$sent}, Failed: {$failed}";

        if ($failed > 0) {
            return back()->with('warning', $message)->with('email_errors', $errors);
        }

        return back()->with('success', $message);
    }

    /**
     * Download all payslips for a payroll as a ZIP file.
     */
    public function downloadAll(Payroll $payroll)
    {
        $this->authorize('download all payslips');

        $payroll->load([
            'payrollDetails.employee.user',
            'payrollDetails.employee.department',
            'payrollDetails.employee.position'
        ]);

        $zip = new \ZipArchive();
        $zipFileName = 'payslips_' . $payroll->payroll_number . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        // Create temp directory if it doesn't exist
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        if ($zip->open($zipPath, \ZipArchive::CREATE) !== TRUE) {
            return back()->with('error', 'Failed to create ZIP file.');
        }

        foreach ($payroll->payrollDetails as $payrollDetail) {
            $pdf = Pdf::loadView('payslips.pdf', compact('payrollDetail'));
            $pdf->setPaper('A4', 'portrait');

            $filename = 'payslip_' . $payrollDetail->employee->employee_number . '_' .
                $payroll->period_start->format('Y-m') . '.pdf';

            $pdfContent = $pdf->output();
            $zip->addFromString($filename, $pdfContent);
        }

        $zip->close();

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    /**
     * Show employee's own payslips.
     */
    public function myPayslips(Request $request)
    {
        $this->authorize('view own payslips');

        $employee = Employee::where('user_id', Auth::id())->first();

        if (!$employee) {
            return redirect()->route('dashboard')
                ->with('error', 'Employee profile not found.');
        }

        $query = PayrollDetail::with(['payroll', 'employee.user'])
            ->where('employee_id', $employee->id);

        // Filter by year
        if ($request->filled('year')) {
            $query->whereHas('payroll', function ($q) use ($request) {
                $q->whereYear('period_start', $request->year);
            });
        }

        // Filter by payroll type
        if ($request->filled('payroll_type')) {
            $query->whereHas('payroll', function ($q) use ($request) {
                $q->where('payroll_type', $request->payroll_type);
            });
        }

        $payslips = $query->whereHas('payroll', function ($q) {
            $q->where('status', 'approved');
        })
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        // Get available years
        $years = PayrollDetail::whereHas('payroll', function ($q) use ($employee) {
            $q->where('status', 'approved');
        })
            ->where('employee_id', $employee->id)
            ->join('payrolls', 'payroll_details.payroll_id', '=', 'payrolls.id')
            ->selectRaw('YEAR(payrolls.period_start) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        return view('payslips.my-payslips', compact('payslips', 'employee', 'years'));
    }

    /**
     * Send payslips to all employees with approved payrolls (bulk send for filtered results).
     */
    public function bulkEmailApproved(Request $request)
    {
        $this->authorize('email all payslips');

        // Get all approved payrolls that match the current filters
        $query = Payroll::with(['payrollDetails.employee.user'])
            ->where('status', 'approved');

        // Apply the same filters as the payroll index
        if ($request->filled('pay_schedule')) {
            $query->where('pay_schedule', $request->pay_schedule);
        }

        if ($request->filled('name_search')) {
            $query->whereHas('payrollDetails.employee', function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->name_search . '%')
                    ->orWhere('last_name', 'like', '%' . $request->name_search . '%');
            });
        }

        if ($request->filled('type')) {
            $query->where('payroll_type', $request->type);
        }

        $payrolls = $query->get();

        $sent = 0;
        $failed = 0;
        $errors = [];

        foreach ($payrolls as $payroll) {
            foreach ($payroll->payrollDetails as $payrollDetail) {
                if (!$payrollDetail->employee->user || !$payrollDetail->employee->user->email) {
                    $failed++;
                    $errors[] = $payrollDetail->employee->first_name . ' ' . $payrollDetail->employee->last_name . ' - No email address';
                    continue;
                }

                // Get snapshot data for accurate amounts
                $snapshot = $payroll->snapshots()
                    ->where('employee_id', $payrollDetail->employee_id)
                    ->first();

                try {
                    Mail::to($payrollDetail->employee->user->email)
                        ->send(new PayslipMail($payrollDetail, $snapshot));
                    $sent++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = $payrollDetail->employee->first_name . ' ' . $payrollDetail->employee->last_name . ' - ' . $e->getMessage();
                }
            }
        }

        $message = "Bulk payslips sent: {$sent}, Failed: {$failed}";

        // Check if this is an AJAX request
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'sent' => $sent,
                'failed' => $failed,
                'errors' => $errors
            ]);
        }

        if ($failed > 0) {
            return back()->with('warning', $message)->with('email_errors', $errors);
        }

        return back()->with('success', $message);
    }

    /**
     * Send individual payslip email by payroll ID (for single-employee payrolls or specific employee).
     */
    public function emailIndividual(Payroll $payroll, Request $request)
    {
        $this->authorize('email payslip');

        $payroll->load(['payrollDetails.employee.user']);

        // Check if this is for a specific employee (via employee_id parameter)
        $employeeId = $request->input('employee_id');

        if ($employeeId) {
            // Find the specific payroll detail for this employee
            $payrollDetail = $payroll->payrollDetails()
                ->where('employee_id', $employeeId)
                ->with(['employee.user', 'employee.department', 'employee.position'])
                ->first();

            if (!$payrollDetail) {
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Employee not found in this payroll'
                    ], 404);
                }
                return back()->with('error', 'Employee not found in this payroll');
            }

            $payrollDetailsToEmail = [$payrollDetail];
        } else {
            // Send to all employees in the payroll (fallback to emailAll behavior)
            $payrollDetailsToEmail = $payroll->payrollDetails;
        }

        $sent = 0;
        $failed = 0;
        $errors = [];

        foreach ($payrollDetailsToEmail as $payrollDetail) {
            if (!$payrollDetail->employee->user || !$payrollDetail->employee->user->email) {
                $failed++;
                $errors[] = $payrollDetail->employee->first_name . ' ' . $payrollDetail->employee->last_name . ' - No email address';
                continue;
            }

            // Get snapshot data for accurate amounts
            $snapshot = null;
            if ($payroll->status !== 'draft') {
                $snapshot = $payroll->snapshots()
                    ->where('employee_id', $payrollDetail->employee_id)
                    ->first();
            }

            try {
                Mail::to($payrollDetail->employee->user->email)
                    ->send(new PayslipMail($payrollDetail, $snapshot));
                $sent++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = $payrollDetail->employee->first_name . ' ' . $payrollDetail->employee->last_name . ' - ' . $e->getMessage();
            }
        }

        $message = $employeeId
            ? "Individual payslip sent: {$sent}, Failed: {$failed}"
            : "Payslips sent: {$sent}, Failed: {$failed}";

        // Check if this is an AJAX request
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'sent' => $sent,
                'failed' => $failed,
                'errors' => $errors
            ]);
        }

        if ($failed > 0) {
            return back()->with('warning', $message)->with('email_errors', $errors);
        }

        return back()->with('success', $message);
    }
}
