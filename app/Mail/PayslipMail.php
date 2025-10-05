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
        // Generate PDF attachment using the same format as the web payslip
        $pdf = Pdf::loadView('payslips.pdf-single', [
            'payrollDetail' => $this->payrollDetail,
            'snapshot' => $this->snapshot
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
