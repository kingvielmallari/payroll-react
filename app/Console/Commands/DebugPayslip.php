<?php

namespace App\Console\Commands;

use App\Models\Payroll;
use Illuminate\Console\Command;

class DebugPayslip extends Command
{
    protected $signature = 'debug:payslip {payroll_id}';
    protected $description = 'Debug payslip data';

    public function handle()
    {
        $payrollId = $this->argument('payroll_id');
        $payroll = Payroll::find($payrollId);

        if (!$payroll) {
            $this->error("Payroll {$payrollId} not found");
            return;
        }

        $this->info("Payroll: " . $payroll->payroll_number);
        $this->info("Status: " . $payroll->status);

        $snapshot = $payroll->snapshots()->first();
        if ($snapshot) {
            $this->info("Snapshot found: " . $snapshot->id);
            $this->info("regular_pay: " . $snapshot->regular_pay);
            $this->info("incentives_total: " . $snapshot->incentives_total);
            $this->info("allowances_total: " . $snapshot->allowances_total);
            $this->info("bonuses_total: " . $snapshot->bonuses_total);
        } else {
            $this->error("No snapshot found for this payroll");
        }

        $detail = $payroll->payrollDetails()->first();
        if ($detail) {
            $this->info("PayrollDetail fallback values:");
            $this->info("regular_pay: " . $detail->regular_pay);
            $this->info("incentives: " . $detail->incentives);
            $this->info("allowances: " . $detail->allowances);
            $this->info("bonuses: " . $detail->bonuses);
        }
    }
}
