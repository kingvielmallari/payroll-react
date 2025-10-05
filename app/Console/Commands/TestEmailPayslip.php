<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use App\Models\PayrollDetail;
use App\Models\Payroll;
use App\Mail\PayslipMail;

class TestEmailPayslip extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payslip:test-email {payroll_detail_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test sending payslip email with PDF attachment';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Payslip Email System...');

        // Check mail configuration
        $this->info('Checking mail configuration...');
        $this->line('MAIL_MAILER: ' . config('mail.default'));
        $this->line('MAIL_HOST: ' . config('mail.mailers.smtp.host'));
        $this->line('MAIL_PORT: ' . config('mail.mailers.smtp.port'));
        $this->line('MAIL_USERNAME: ' . config('mail.mailers.smtp.username'));
        $this->line('MAIL_FROM_ADDRESS: ' . config('mail.from.address'));
        $this->line('MAIL_FROM_NAME: ' . config('mail.from.name'));

        if (empty(config('mail.mailers.smtp.username')) || empty(config('mail.mailers.smtp.password'))) {
            $this->error('Gmail credentials not configured in .env file!');
            $this->info('Please configure MAIL_USERNAME and MAIL_PASSWORD in your .env file.');
            $this->info('See GMAIL_SETUP.md for detailed instructions.');
            return 1;
        }

        // Get payroll detail to test with
        $payrollDetailId = $this->argument('payroll_detail_id');

        if ($payrollDetailId) {
            $payrollDetail = PayrollDetail::find($payrollDetailId);
        } else {
            // Find first approved payroll detail with user email
            $payrollDetail = PayrollDetail::whereHas('payroll', function ($q) {
                $q->where('status', 'approved');
            })
                ->whereHas('employee.user', function ($q) {
                    $q->whereNotNull('email');
                })
                ->with(['payroll', 'employee.user', 'employee.department', 'employee.position'])
                ->first();
        }

        if (!$payrollDetail) {
            $this->error('No approved payroll detail found with valid employee email!');
            $this->info('Create an approved payroll with an employee that has a user email address.');
            return 1;
        }

        $this->info("Testing with payroll detail ID: {$payrollDetail->id}");
        $this->info("Employee: {$payrollDetail->employee->full_name}");
        $this->info("Email: {$payrollDetail->employee->user->email}");
        $this->info("Payroll: {$payrollDetail->payroll->payroll_number}");
        $this->info("Period: {$payrollDetail->payroll->period_start->format('M d')} - {$payrollDetail->payroll->period_end->format('M d, Y')}");

        if (!$this->confirm('Send test payslip email to this employee?')) {
            $this->info('Test cancelled.');
            return 0;
        }

        try {
            // Get snapshot data for accurate amounts
            $snapshot = null;
            if ($payrollDetail->payroll->status !== 'draft') {
                $snapshot = $payrollDetail->payroll->snapshots()
                    ->where('employee_id', $payrollDetail->employee_id)
                    ->first();
            }

            $this->info('Sending payslip email...');

            Mail::to($payrollDetail->employee->user->email)
                ->send(new PayslipMail($payrollDetail, $snapshot));

            $this->info('✅ Payslip email sent successfully!');
            $this->info('Check the recipient\'s inbox for the payslip PDF.');
        } catch (\Exception $e) {
            $this->error('❌ Failed to send payslip email:');
            $this->error($e->getMessage());

            if (str_contains($e->getMessage(), 'authentication')) {
                $this->info('💡 This looks like an authentication error. Check:');
                $this->info('   - Gmail app password is correct');
                $this->info('   - 2-step verification is enabled');
                $this->info('   - Username and password are set in .env');
            }

            return 1;
        }

        return 0;
    }
}
