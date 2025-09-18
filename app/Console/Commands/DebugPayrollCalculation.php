<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AllowanceBonusSetting;
use App\Models\Employee;
use App\Models\Payroll;

class DebugPayrollCalculation extends Command
{
    protected $signature = 'debug:payroll-calc {employee_id?}';
    protected $description = 'Debug payroll calculation for allowances vs incentives';

    public function handle()
    {
        $employeeId = $this->argument('employee_id') ?? 15;
        $employee = Employee::find($employeeId);

        if (!$employee) {
            $this->error("Employee {$employeeId} not found");
            return;
        }

        $this->info("=== Employee Info ===");
        $this->info("ID: {$employee->id}");
        $this->info("Name: " . ($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
        $this->info("Benefits Status: {$employee->benefits_status}");

        // Get any available payroll (since we see one exists in the UI)
        $payroll = Payroll::latest()->first();

        if (!$payroll) {
            $this->error("No payroll found in database");

            // List all payrolls
            $payrolls = Payroll::all();
            $this->info("Total payrolls: " . $payrolls->count());
            foreach ($payrolls as $p) {
                $this->info("  ID: {$p->id} Period: {$p->period_start} to {$p->period_end}");
            }
            return;
        }

        $this->info("\n=== Payroll Info ===");
        $this->info("Period: {$payroll->period_start} to {$payroll->period_end}");

        // Test allowances
        $this->info("\n=== ALLOWANCES ===");
        $allowances = AllowanceBonusSetting::where('is_active', true)
            ->where('type', 'allowance')
            ->forBenefitStatus($employee->benefits_status)
            ->get();

        foreach ($allowances as $allowance) {
            $this->info("- {$allowance->name} (₱{$allowance->fixed_amount})");
            $this->info("  Perfect Attendance Required: " . ($allowance->requires_perfect_attendance ? 'YES' : 'NO'));

            if ($allowance->requires_perfect_attendance) {
                try {
                    $hasPerfectAttendance = $allowance->hasPerfectAttendance($employee, $payroll->period_start, $payroll->period_end);
                    $this->info("  Employee Has Perfect Attendance: " . ($hasPerfectAttendance ? 'YES' : 'NO'));
                } catch (\Exception $e) {
                    $this->error("  Error checking perfect attendance: " . $e->getMessage());
                }
            }
        }

        // Test incentives
        $this->info("\n=== INCENTIVES ===");
        $incentives = AllowanceBonusSetting::where('is_active', true)
            ->where('type', 'incentives')
            ->forBenefitStatus($employee->benefits_status)
            ->get();

        foreach ($incentives as $incentive) {
            $this->info("- {$incentive->name} (₱{$incentive->fixed_amount})");
            $this->info("  Perfect Attendance Required: " . ($incentive->requires_perfect_attendance ? 'YES' : 'NO'));

            if ($incentive->requires_perfect_attendance) {
                try {
                    $hasPerfectAttendance = $incentive->hasPerfectAttendance($employee, $payroll->period_start, $payroll->period_end);
                    $this->info("  Employee Has Perfect Attendance: " . ($hasPerfectAttendance ? 'YES' : 'NO'));
                } catch (\Exception $e) {
                    $this->error("  Error checking perfect attendance: " . $e->getMessage());
                }
            }
        }

        // Check DTR records for this period
        $this->info("\n=== DTR RECORDS FOR PERIOD ===");
        $startDate = new \DateTime($payroll->period_start);
        $endDate = new \DateTime($payroll->period_end);

        for ($date = $startDate; $date <= $endDate; $date->modify('+1 day')) {
            $dateStr = $date->format('Y-m-d');
            $dtr = \App\Models\DTR::where('employee_id', $employee->id)
                ->where('date', $dateStr)
                ->first();

            if ($dtr) {
                $this->info("  {$dateStr}: Late: {$dtr->late_minutes}min, Undertime: {$dtr->undertime_minutes}min, Absent: " . ($dtr->is_absent ? 'YES' : 'NO'));
            } else {
                $this->info("  {$dateStr}: No DTR record");
            }
        }
    }
}
