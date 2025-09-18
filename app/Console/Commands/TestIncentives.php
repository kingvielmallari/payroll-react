<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AllowanceBonusSetting;
use App\Models\Employee;

class TestIncentives extends Command
{
    protected $signature = 'test:incentives {employee_id?}';
    protected $description = 'Test incentives query';

    public function handle()
    {
        try {
            $this->info("Checking incentives settings...");

            $incentives = AllowanceBonusSetting::where('type', 'incentives')->get();
            $this->info("Found " . $incentives->count() . " incentive settings");

            foreach ($incentives as $incentive) {
                $this->info("- {$incentive->name} (Active: " . ($incentive->is_active ? 'YES' : 'NO') .
                    ", Perfect Attendance: " . ($incentive->requires_perfect_attendance ? 'YES' : 'NO') . ")");
                $this->info("  Benefit Eligibility: {$incentive->benefit_eligibility}");
                $this->info("  Calculation Type: {$incentive->calculation_type}");
                $this->info("  Amount: {$incentive->fixed_amount}");
            }

            $employeeId = $this->argument('employee_id') ?? 15;
            $employee = Employee::find($employeeId);

            if (!$employee) {
                $this->error("Employee {$employeeId} not found");
                return;
            }

            $this->info("\nEmployee: " . ($employee->name ?? 'NO NAME') . " (Benefits: {$employee->benefits_status})");

            // Test filtered incentives
            $filtered = AllowanceBonusSetting::where('is_active', true)
                ->where('type', 'incentives')
                ->forBenefitStatus($employee->benefits_status)
                ->get();

            $this->info("Filtered incentives for this employee: " . $filtered->count());
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }
    }
}
