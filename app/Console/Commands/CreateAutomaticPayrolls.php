<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\PayrollScheduleSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateAutomaticPayrolls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payroll:auto-create {--dry-run : Show what would be created without actually creating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically create payrolls for employees based on their pay schedules and current date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if automatic payroll creation is enabled in settings
        $autoPayrollEnabled = config('app.auto_payroll_enabled', false);
        
        if (!$autoPayrollEnabled) {
            $this->info('Automatic payroll creation is disabled in settings.');
            return 0;
        }

        $today = Carbon::now();
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('DRY RUN MODE - No payrolls will be created');
        }

        $this->info("Checking for automatic payroll creation on {$today->format('Y-m-d')}");

        // Get all pay schedules that should be processed today
        $schedulesToProcess = $this->getSchedulesToProcess($today);

        if (empty($schedulesToProcess)) {
            $this->info('No pay schedules due for processing today.');
            return 0;
        }

        $totalCreated = 0;

        foreach ($schedulesToProcess as $scheduleType => $periodInfo) {
            $this->line("Processing {$scheduleType} payroll...");
            
            // Get employees for this schedule
            $employees = Employee::where('employment_status', 'active')
                               ->where('pay_schedule', $scheduleType)
                               ->get();

            if ($employees->isEmpty()) {
                $this->warn("No active employees found for {$scheduleType} schedule.");
                continue;
            }

            // Check if payroll already exists for this period and schedule
            $existingPayroll = Payroll::where('pay_schedule', $scheduleType)
                                    ->where('period_start', $periodInfo['period_start'])
                                    ->where('period_end', $periodInfo['period_end'])
                                    ->first();

            if ($existingPayroll) {
                $this->warn("Payroll already exists for {$scheduleType} period {$periodInfo['period_start']} to {$periodInfo['period_end']}");
                continue;
            }

            if (!$isDryRun) {
                $payroll = $this->createPayroll($scheduleType, $periodInfo, $employees);
                if ($payroll) {
                    $totalCreated++;
                    $this->info("Created {$scheduleType} payroll #{$payroll->payroll_number} with {$employees->count()} employees");
                }
            } else {
                $this->info("Would create {$scheduleType} payroll for {$employees->count()} employees");
                $this->line("  Period: {$periodInfo['period_start']} to {$periodInfo['period_end']}");
                $this->line("  Pay Date: {$periodInfo['pay_date']}");
            }
        }

        if (!$isDryRun) {
            $this->info("Successfully created {$totalCreated} automatic payrolls.");
        }

        return 0;
    }

    /**
     * Get schedules that should be processed today
     */
    private function getSchedulesToProcess($today)
    {
        $schedules = [];

        // Check weekly schedules
        if ($this->shouldProcessWeekly($today)) {
            $schedules['weekly'] = $this->getWeeklyPeriod($today);
        }

        // Check semi-monthly schedules
        if ($this->shouldProcessSemiMonthly($today)) {
            $schedules['semi_monthly'] = $this->getSemiMonthlyPeriod($today);
        }

        // Check monthly schedules
        if ($this->shouldProcessMonthly($today)) {
            $schedules['monthly'] = $this->getMonthlyPeriod($today);
        }

        return $schedules;
    }

    /**
     * Check if we should process weekly payrolls today
     */
    private function shouldProcessWeekly($today)
    {
        // Process weekly payrolls every Monday (start of new work week)
        return $today->isMonday();
    }

    /**
     * Check if we should process semi-monthly payrolls today
     */
    private function shouldProcessSemiMonthly($today)
    {
        // Process on 1st and 16th of each month
        return in_array($today->day, [1, 16]);
    }

    /**
     * Check if we should process monthly payrolls today
     */
    private function shouldProcessMonthly($today)
    {
        // Process on the 1st of each month
        return $today->day === 1;
    }

    /**
     * Get weekly period information
     */
    private function getWeeklyPeriod($today)
    {
        $startOfWeek = $today->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $today->copy()->endOfWeek(Carbon::SUNDAY);
        $payDate = $endOfWeek->copy()->addDays(3); // Pay on Wednesday after week ends

        return [
            'period_start' => $startOfWeek->format('Y-m-d'),
            'period_end' => $endOfWeek->format('Y-m-d'),
            'pay_date' => $payDate->format('Y-m-d'),
            'description' => 'Auto-generated weekly payroll for ' . $startOfWeek->format('M d') . ' - ' . $endOfWeek->format('M d, Y')
        ];
    }

    /**
     * Get semi-monthly period information
     */
    private function getSemiMonthlyPeriod($today)
    {
        if ($today->day === 1) {
            // Previous month's second half (16th to end)
            $prevMonth = $today->copy()->subMonth();
            $start = $prevMonth->copy()->day(16);
            $end = $prevMonth->copy()->endOfMonth();
            $payDate = $today->copy()->addDays(3);
        } else { // day === 16
            // Current month's first half (1st to 15th)
            $start = $today->copy()->startOfMonth();
            $end = $today->copy()->day(15);
            $payDate = $today->copy()->addDays(3);
        }

        return [
            'period_start' => $start->format('Y-m-d'),
            'period_end' => $end->format('Y-m-d'),
            'pay_date' => $payDate->format('Y-m-d'),
            'description' => 'Auto-generated semi-monthly payroll for ' . $start->format('M d') . ' - ' . $end->format('M d, Y')
        ];
    }

    /**
     * Get monthly period information
     */
    private function getMonthlyPeriod($today)
    {
        // Previous month
        $prevMonth = $today->copy()->subMonth();
        $start = $prevMonth->copy()->startOfMonth();
        $end = $prevMonth->copy()->endOfMonth();
        $payDate = $today->copy()->addDays(5);

        return [
            'period_start' => $start->format('Y-m-d'),
            'period_end' => $end->format('Y-m-d'),
            'pay_date' => $payDate->format('Y-m-d'),
            'description' => 'Auto-generated monthly payroll for ' . $start->format('M Y')
        ];
    }

    /**
     * Create payroll for the given schedule and employees
     */
    private function createPayroll($scheduleType, $periodInfo, $employees)
    {
        return DB::transaction(function () use ($scheduleType, $periodInfo, $employees) {
            // Create payroll
            $payroll = Payroll::create([
                'payroll_number' => Payroll::generatePayrollNumber('automatic'),
                'period_start' => $periodInfo['period_start'],
                'period_end' => $periodInfo['period_end'],
                'pay_date' => $periodInfo['pay_date'],
                'payroll_type' => 'automatic',
                'pay_schedule' => $scheduleType,
                'description' => $periodInfo['description'],
                'status' => 'draft',
                'created_by' => 1, // System user
            ]);

            $totalGross = 0;
            $totalDeductions = 0;

            // Create payroll details for each employee
            foreach ($employees as $employee) {
                $calculation = $this->calculateEmployeePayroll($employee, $periodInfo['period_start'], $periodInfo['period_end']);

                $payrollDetail = PayrollDetail::create([
                    'payroll_id' => $payroll->id,
                    'employee_id' => $employee->id,
                    'basic_pay' => $calculation['basic_pay'],
                    'overtime_pay' => $calculation['overtime_pay'],
                    'allowances' => $calculation['allowances'],
                    'gross_pay' => $calculation['gross_pay'],
                    'deductions' => $calculation['deductions'],
                    'net_pay' => $calculation['net_pay'],
                    'days_worked' => $calculation['days_worked'],
                    'hours_worked' => $calculation['hours_worked'],
                    'overtime_hours' => $calculation['overtime_hours'],
                ]);

                $totalGross += $calculation['gross_pay'];
                $totalDeductions += $calculation['deductions'];
            }

            // Update payroll totals
            $payroll->update([
                'total_gross' => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_net' => $totalGross - $totalDeductions,
            ]);

            return $payroll;
        });
    }

    /**
     * Calculate employee payroll (simplified version)
     */
    private function calculateEmployeePayroll($employee, $periodStart, $periodEnd)
    {
        // This is a simplified calculation - you may want to use the same logic
        // as in your PayrollController's calculateEmployeePayroll method
        
        $basicPay = 0;
        switch ($employee->pay_schedule) {
            case 'weekly':
                $basicPay = $employee->weekly_rate ?? ($employee->basic_salary / 4.33);
                break;
            case 'semi_monthly':
                $basicPay = $employee->semi_monthly_rate ?? ($employee->basic_salary / 2);
                break;
            case 'monthly':
                $basicPay = $employee->basic_salary;
                break;
        }

        // For automatic payroll, we'll use basic calculation
        // You can enhance this with DTR records, overtime, etc.
        $overtimePay = 0;
        $allowances = 0;
        $grossPay = $basicPay + $overtimePay + $allowances;
        
        // Simple deduction calculation (you may want to include actual deductions)
        $deductions = 0;
        $netPay = $grossPay - $deductions;

        return [
            'basic_pay' => $basicPay,
            'overtime_pay' => $overtimePay,
            'allowances' => $allowances,
            'gross_pay' => $grossPay,
            'deductions' => $deductions,
            'net_pay' => $netPay,
            'days_worked' => $this->getWorkingDaysInPeriod($periodStart, $periodEnd),
            'hours_worked' => 0,
            'overtime_hours' => 0,
        ];
    }

    /**
     * Get working days in period
     */
    private function getWorkingDaysInPeriod($startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        $workingDays = 0;
        while ($start->lte($end)) {
            if ($start->isWeekday()) {
                $workingDays++;
            }
            $start->addDay();
        }
        
        return $workingDays;
    }
}
