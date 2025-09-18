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
            subject: 'Payslip for ' . $this->payrollDetail->payroll->period_start->format('F Y'),
            from: config('mail.from.address', 'noreply@company.com'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payslip',
            with: [
                'payrollDetail' => $this->payrollDetail,
                'snapshot' => $this->snapshot,
                'employee' => $this->payrollDetail->employee,
                'payroll' => $this->payrollDetail->payroll,
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
        // Generate PDF attachment
        $pdf = Pdf::loadView('payslips.pdf', [
            'payrollDetail' => $this->payrollDetail,
            'snapshot' => $this->snapshot
        ]);
        $pdf->setPaper('A4', 'portrait');

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
