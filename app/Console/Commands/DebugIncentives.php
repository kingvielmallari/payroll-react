<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\AllowanceBonusSetting;
use App\Models\Payroll;

class DebugIncentives extends Command
{
    protected $signature = 'debug:incentives {employee_id?}';
    protected $description = 'Debug incentives calculation for an employee';

    public function handle()
    {
        $employeeId = $this->argument('employee_id') ?? 15;

        $employee = Employee::find($employeeId);
        if (!$employee) {
            $this->error("Employee with ID {$employeeId} not found");
            return;
        }

        $this->info("Employee: " . ($employee->name ?? 'NO NAME') . " (ID: {$employee->id})");
        $this->info("Benefits Status: " . ($employee->benefits_status ?? 'NO STATUS'));

        // Get latest payroll
        $latestPayroll = Payroll::latest()->first();
        if (!$latestPayroll) {
            $this->error("No payroll found");

            // Check if there are any payrolls at all
            $payrollCount = Payroll::count();
            $this->info("Total payrolls in database: {$payrollCount}");

            if ($payrollCount > 0) {
                $this->info("Payrolls available:");
                $payrolls = Payroll::orderBy('period_start', 'desc')->limit(5)->get();
                foreach ($payrolls as $p) {
                    $this->info("  ID: {$p->id} | Period: {$p->period_start} to {$p->period_end}");
                }
            }
            return;
        }

        $this->info("Payroll Period: {$latestPayroll->period_start} to {$latestPayroll->period_end}");

        // Check all incentive settings
        $this->info("\n=== ALL INCENTIVE SETTINGS ===");
        $allIncentives = AllowanceBonusSetting::where('type', 'incentives')->get();
        $this->info("Total incentives found: " . $allIncentives->count());

        foreach ($allIncentives as $incentive) {
            $this->info("ID: {$incentive->id} | Name: {$incentive->name} | Active: " . ($incentive->is_active ? 'YES' : 'NO'));
            $this->info("  Perfect Attendance Required: " . ($incentive->requires_perfect_attendance ? 'YES' : 'NO'));
            $this->info("  Benefit Eligibility: {$incentive->benefit_eligibility}");
        }

        // Check filtered incentives for this employee
        $this->info("\n=== FILTERED INCENTIVES FOR THIS EMPLOYEE ===");
        $filteredIncentives = AllowanceBonusSetting::where('is_active', true)
            ->where('type', 'incentives')
            ->forBenefitStatus($employee->benefits_status)
            ->get();

        $this->info("Filtered incentives count: " . $filteredIncentives->count());

        foreach ($filteredIncentives as $incentive) {
            $this->info("ID: {$incentive->id} | Name: {$incentive->name}");
            $this->info("  Perfect Attendance Required: " . ($incentive->requires_perfect_attendance ? 'YES' : 'NO'));

            if ($incentive->requires_perfect_attendance) {
                try {
                    $hasPerfectAttendance = $incentive->hasPerfectAttendance($employee, $latestPayroll->period_start, $latestPayroll->period_end);
                    $this->info("  Has Perfect Attendance: " . ($hasPerfectAttendance ? 'YES' : 'NO'));
                } catch (\Exception $e) {
                    $this->error("  Error checking perfect attendance: " . $e->getMessage());
                }
            }
        }
    }
}
