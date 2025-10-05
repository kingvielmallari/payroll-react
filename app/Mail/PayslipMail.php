<?php

namespace App\Mail;

use App\Models\PayrollDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;
use Barryvdh\DomPDF\Facade\Pdf;

class PayslipMail extends Mailable
{
    use Queueable, SerializesModels;

    public $payrollDetail;
    public $snapshot;

    /**
     * Create a new message instance.
     */
    public function __construct(PayrollDetail $payrollDetail, $snapshot = null)
    {
        $this->payrollDetail = $payrollDetail;
        $this->snapshot = $snapshot;
        $this->payrollDetail->load([
            'payroll',
            'employee.user',
            'employee.department',
            'employee.position'
        ]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Payslip for ' . $this->payrollDetail->payroll->period_start->format('F d') . ' - ' . $this->payrollDetail->payroll->period_end->format('F d, Y'),
            from: config('mail.from.address', 'noreply@company.com'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $employeeName = $this->payrollDetail->employee->first_name . ' ' . $this->payrollDetail->employee->last_name;
        $period = $this->payrollDetail->payroll->period_start->format('M d') . ' - ' . $this->payrollDetail->payroll->period_end->format('M d, Y');

        $emailContent = "Hello {$employeeName},\n\nYour payslip for {$period} is attached to this email.\n\nBest regards,\nHR Department";

        return new Content(
            text: 'emails.payslip-plain',
            with: [
                'emailContent' => $emailContent,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        // Use the same format as the web payslip view - create a temporary payroll object
        // that contains only this employee's details for consistency with the web view
        $payroll = $this->payrollDetail->payroll;

        // Ensure we have the same data structure as the payslip view
        $isDynamic = $payroll->status === 'draft';

        // Apply snapshot logic if needed (same as payslip controller)
        if (!$isDynamic && $this->snapshot) {
            // Override detail values with snapshot values for consistency
            $this->payrollDetail->basic_salary = $this->snapshot->basic_salary;
            $this->payrollDetail->daily_rate = $this->snapshot->daily_rate;
            $this->payrollDetail->hourly_rate = $this->snapshot->hourly_rate;
            $this->payrollDetail->days_worked = $this->snapshot->days_worked;
            $this->payrollDetail->regular_hours = $this->snapshot->regular_hours;
            $this->payrollDetail->overtime_hours = $this->snapshot->overtime_hours;
            $this->payrollDetail->holiday_hours = $this->snapshot->holiday_hours;
            $this->payrollDetail->regular_pay = $this->snapshot->regular_pay;
            $this->payrollDetail->overtime_pay = $this->snapshot->overtime_pay;
            $this->payrollDetail->holiday_pay = $this->snapshot->holiday_pay;
            $this->payrollDetail->allowances = $this->snapshot->allowances_total;
            $this->payrollDetail->bonuses = $this->snapshot->bonuses_total;
            $this->payrollDetail->incentives = $this->snapshot->incentives_total;
            $this->payrollDetail->gross_pay = $this->snapshot->gross_pay;
            $this->payrollDetail->sss_contribution = $this->snapshot->sss_contribution;
            $this->payrollDetail->philhealth_contribution = $this->snapshot->philhealth_contribution;
            $this->payrollDetail->pagibig_contribution = $this->snapshot->pagibig_contribution;
            $this->payrollDetail->withholding_tax = $this->snapshot->withholding_tax;
            $this->payrollDetail->late_deductions = $this->snapshot->late_deductions;
            $this->payrollDetail->undertime_deductions = $this->snapshot->undertime_deductions;
            $this->payrollDetail->cash_advance_deductions = $this->snapshot->cash_advance_deductions;
            $this->payrollDetail->other_deductions = $this->snapshot->other_deductions;
            $this->payrollDetail->total_deductions = $this->snapshot->total_deductions;
            $this->payrollDetail->net_pay = $this->snapshot->net_pay;

            // Set breakdown data from snapshots
            if ($this->snapshot->allowances_breakdown) {
                $this->payrollDetail->earnings_breakdown = json_encode([
                    'allowances' => is_string($this->snapshot->allowances_breakdown)
                        ? json_decode($this->snapshot->allowances_breakdown, true)
                        : $this->snapshot->allowances_breakdown
                ]);
            }

            if ($this->snapshot->bonuses_breakdown) {
                $this->payrollDetail->bonuses_breakdown = is_string($this->snapshot->bonuses_breakdown)
                    ? json_decode($this->snapshot->bonuses_breakdown, true)
                    : $this->snapshot->bonuses_breakdown;
            }

            if ($this->snapshot->deductions_breakdown) {
                $this->payrollDetail->deduction_breakdown = is_string($this->snapshot->deductions_breakdown)
                    ? json_decode($this->snapshot->deductions_breakdown, true)
                    : $this->snapshot->deductions_breakdown;
            }
        }

        // Create a collection containing only this employee's payroll detail
        $singleEmployeePayroll = clone $payroll;
        $singleEmployeePayroll->setRelation('payrollDetails', collect([$this->payrollDetail]));

        // Get the same supporting data as the payslip view
        $employerSettings = \App\Models\EmployerSetting::first();
        $activeDeductions = \App\Models\DeductionTaxSetting::active()->orderBy('name')->get();

        $company = (object)[
            'name' => $employerSettings->registered_business_name ?? 'Payroll-System',
            'address' => $employerSettings->registered_address ?? 'Company Address, City, Province',
            'phone' => $employerSettings->landline_mobile ?? '+63 (000) 000-0000',
            'email' => $employerSettings->office_business_email ?? 'hr@company.com'
        ];

        // Generate PDF using the same format as the web payslip
        $pdf = Pdf::loadView('payrolls.payslip-pdf', [
            'payroll' => $singleEmployeePayroll,
            'company' => $company,
            'isDynamic' => $isDynamic,
            'employerSettings' => $employerSettings,
            'activeDeductions' => $activeDeductions
        ]);

        // Set paper size to Letter (8.5" x 11" - short bond paper size)
        $pdf->setPaper('letter', 'portrait');

        $filename = 'payslip_' . $this->payrollDetail->employee->employee_number . '_' .
            $this->payrollDetail->payroll->period_start->format('Y-m') . '.pdf';

        return [
            Attachment::fromData(
                fn() => $pdf->output(),
                $filename
            )->withMime('application/pdf'),
        ];
    }
}
