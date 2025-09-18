<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\AllowanceBonusSetting;

class TestBonusCalculation extends Command
{
    protected $signature = 'test:bonus-calc {employee_id?}';
    protected $description = 'Test specific bonus calculations';

    public function handle()
    {
        $employeeId = $this->argument('employee_id') ?? 15;
        $employee = Employee::find($employeeId);

        if (!$employee) {
            $this->error("Employee {$employeeId} not found");
            return;
        }

        $this->info("Employee: " . ($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));
        $this->info("Basic Salary: ₱" . ($employee->basic_salary ?? 0));
        $this->info("Benefits Status: " . $employee->benefits_status);

        // Test each bonus individually
        $bonuses = AllowanceBonusSetting::where('is_active', true)
            ->where('type', 'bonus')
            ->forBenefitStatus($employee->benefits_status)
            ->get();

        $this->info("\n=== INDIVIDUAL BONUS CALCULATIONS ===");

        foreach ($bonuses as $bonus) {
            $this->info("\nBonus: {$bonus->name}");
            $this->info("  Type: {$bonus->calculation_type}");
            $this->info("  Frequency: {$bonus->frequency}");

            if ($bonus->calculation_type === 'percentage') {
                $this->info("  Rate: {$bonus->rate_percentage}%");
                $calculatedAmount = ($employee->basic_salary * $bonus->rate_percentage) / 100;
                $this->info("  Calculation: ₱{$employee->basic_salary} × {$bonus->rate_percentage}% = ₱{$calculatedAmount}");
            } else {
                $this->info("  Fixed Amount: ₱{$bonus->fixed_amount}");
                $calculatedAmount = $bonus->fixed_amount;
            }

            $this->info("  Final Amount: ₱{$calculatedAmount}");
        }
    }
}
