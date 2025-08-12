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

        return view('payslips.show', compact('payrollDetail'));
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

        $pdf = Pdf::loadView('payslips.pdf', compact('payrollDetail'));
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
        $this->authorize('email payslips');

        $payrollDetail->load([
            'payroll',
            'employee.user',
            'employee.department',
            'employee.position'
        ]);

        if (!$payrollDetail->employee->user || !$payrollDetail->employee->user->email) {
            return back()->with('error', 'Employee does not have a valid email address.');
        }

        try {
            Mail::to($payrollDetail->employee->user->email)
                ->send(new PayslipMail($payrollDetail));

            return back()->with('success', 'Payslip sent successfully to ' . $payrollDetail->employee->user->email);
        } catch (\Exception $e) {
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

            try {
                Mail::to($payrollDetail->employee->user->email)
                    ->send(new PayslipMail($payrollDetail));
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
            $query->whereHas('payroll', function($q) use ($request) {
                $q->whereYear('period_start', $request->year);
            });
        }

        // Filter by payroll type
        if ($request->filled('payroll_type')) {
            $query->whereHas('payroll', function($q) use ($request) {
                $q->where('payroll_type', $request->payroll_type);
            });
        }

        $payslips = $query->whereHas('payroll', function($q) {
                           $q->where('status', 'approved');
                       })
                       ->orderBy('created_at', 'desc')
                       ->paginate(12);

        // Get available years
        $years = PayrollDetail::whereHas('payroll', function($q) use ($employee) {
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
}
