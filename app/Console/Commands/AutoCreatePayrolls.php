<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\PayScheduleSetting;
use App\Models\TimeLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoCreatePayrolls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payroll:auto-create {--schedule=all : Specific pay schedule to process (weekly, semi_monthly, monthly, or all)} {--dry-run : Show what would be created without actually creating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically create payrolls for all employees when payroll periods start';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $scheduleFilter = $this->option('schedule');
        $dryRun = $this->option('dry-run');
        
        $this->info('Starting automatic payroll creation...');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No payrolls will be created');
        }
        
        // Get active pay schedule settings
        $schedules = PayScheduleSetting::systemDefaults()
                    ->where('is_active', true)
                    ->when($scheduleFilter !== 'all', function($query) use ($scheduleFilter) {
                        return $query->where('code', $scheduleFilter);
                    })
                    ->get();
        
        if ($schedules->isEmpty()) {
            $this->error('No active pay schedule settings found');
            return 1;
        }
        
        $totalCreated = 0;
        
        foreach ($schedules as $schedule) {
            $this->line("\nProcessing {$schedule->name} payroll...");
            
            $created = $this->processSchedule($schedule, $dryRun);
            $totalCreated += $created;
            
            if ($created > 0) {
                $this->info("Created {$created} payroll(s) for {$schedule->name}");
            } else {
                $this->comment("No payrolls needed for {$schedule->name}");
            }
        }
        
        $this->line("\n" . str_repeat('=', 50));
        if ($dryRun) {
            $this->info("Would create {$totalCreated} total payroll(s)");
        } else {
            $this->info("Successfully created {$totalCreated} total payroll(s)");
        }
        
        return 0;
    }
    
    /**
     * Process a specific pay schedule
     */
    private function processSchedule(PayScheduleSetting $schedule, bool $dryRun): int
    {
        $created = 0;
        $today = Carbon::today();
        
        // Get the current periods that should have payrolls
        $periods = $this->getCurrentPayrollPeriods($schedule);
        
        foreach ($periods as $period) {
            // Check if payroll already exists for this period
            $existingPayroll = Payroll::where('pay_schedule', $schedule->code)
                                    ->where('period_start', $period['start_date'])
                                    ->where('period_end', $period['end_date'])
                                    ->first();
            
            if ($existingPayroll) {
                $this->comment("  Payroll already exists for {$period['period_name']}");
                continue;
            }
            
            // Get employees with this pay schedule
            $employees = Employee::where('pay_schedule', $schedule->code)
                               ->where('employment_status', 'active')
                               ->get();
            
            if ($employees->isEmpty()) {
                $this->comment("  No employees found with {$schedule->code} pay schedule");
                continue;
            }
            
            if (!$dryRun) {
                $payroll = $this->createPayroll($schedule, $period, $employees);
                if ($payroll) {
                    $created++;
                    $this->info("  Created payroll: {$payroll->payroll_number} for {$period['period_name']}");
                }
            } else {
                $created++;
                $this->info("  Would create payroll for {$period['period_name']} with {$employees->count()} employees");
            }
        }
        
        return $created;
    }
    
    /**
     * Get current payroll periods that should have payrolls
     */
    private function getCurrentPayrollPeriods(PayScheduleSetting $schedule): array
    {
        $periods = [];
        $today = Carbon::today();
        
        switch ($schedule->code) {
            case 'weekly':
                // Create payroll for the current week
                $startOfWeek = $today->copy()->startOfWeek(Carbon::MONDAY);
                $endOfWeek = $startOfWeek->copy()->endOfWeek(Carbon::SATURDAY);
                
                $periods[] = [
                    'period_name' => $startOfWeek->format('M d') . ' - ' . $endOfWeek->format('M d, Y'),
                    'start_date' => $startOfWeek->format('Y-m-d'),
                    'end_date' => $endOfWeek->format('Y-m-d'),
                    'pay_date' => $endOfWeek->format('Y-m-d')
                ];
                break;
                
            case 'semi_monthly':
                $currentMonth = $today->month;
                $currentYear = $today->year;
                
                // First period: 1st-15th
                $periods[] = [
                    'period_name' => Carbon::create($currentYear, $currentMonth, 1)->format('M 1') . ' - ' . Carbon::create($currentYear, $currentMonth, 15)->format('15, Y'),
                    'start_date' => Carbon::create($currentYear, $currentMonth, 1)->format('Y-m-d'),
                    'end_date' => Carbon::create($currentYear, $currentMonth, 15)->format('Y-m-d'),
                    'pay_date' => Carbon::create($currentYear, $currentMonth, 15)->format('Y-m-d')
                ];
                
                // Second period: 16th-end of month
                $endOfMonth = Carbon::create($currentYear, $currentMonth)->endOfMonth();
                $periods[] = [
                    'period_name' => Carbon::create($currentYear, $currentMonth, 16)->format('M 16') . ' - ' . $endOfMonth->format('d, Y'),
                    'start_date' => Carbon::create($currentYear, $currentMonth, 16)->format('Y-m-d'),
                    'end_date' => $endOfMonth->format('Y-m-d'),
                    'pay_date' => $endOfMonth->format('Y-m-d')
                ];
                break;
                
            case 'monthly':
                $currentMonth = $today->month;
                $currentYear = $today->year;
                
                $startOfMonth = Carbon::create($currentYear, $currentMonth, 1);
                $endOfMonth = $startOfMonth->copy()->endOfMonth();
                
                $periods[] = [
                    'period_name' => $startOfMonth->format('M 1') . ' - ' . $endOfMonth->format('d, Y'),
                    'start_date' => $startOfMonth->format('Y-m-d'),
                    'end_date' => $endOfMonth->format('Y-m-d'),
                    'pay_date' => $endOfMonth->format('Y-m-d')
                ];
                break;
        }
        
        return $periods;
    }
    
    /**
     * Create a payroll record
     */
    private function createPayroll(PayScheduleSetting $schedule, array $period, $employees)
    {
        try {
            DB::beginTransaction();
            
            // Generate payroll number
            $payrollNumber = $this->generatePayrollNumber($schedule->code);
            
            // Create payroll
            $payroll = Payroll::create([
                'payroll_number' => $payrollNumber,
                'pay_schedule' => $schedule->code,
                'payroll_type' => 'regular',
                'period_start' => $period['start_date'],
                'period_end' => $period['end_date'],
                'pay_date' => $period['pay_date'],
                'status' => 'draft',
                'total_gross' => 0,
                'total_deductions' => 0,
                'total_net' => 0,
                'created_by' => 1 // System user
            ]);
            
            $totalGross = 0;
            $totalNet = 0;
            
            // Create payroll details for each employee
            foreach ($employees as $employee) {
                $payrollDetail = $this->createPayrollDetail($payroll, $employee, $period);
                $totalGross += $payrollDetail->gross_pay;
                $totalNet += $payrollDetail->net_pay;
            }
            
            // Update payroll totals
            $payroll->update([
                'total_gross' => $totalGross,
                'total_net' => $totalNet
            ]);
            
            DB::commit();
            
            Log::info("Auto-created payroll: {$payrollNumber} for {$schedule->name}");
            
            return $payroll;
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create payroll for {$schedule->name}: " . $e->getMessage());
            $this->error("  Failed to create payroll: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create payroll detail for an employee
     */
    private function createPayrollDetail(Payroll $payroll, Employee $employee, array $period)
    {
        // Calculate basic pay based on working days
        $workingDays = $employee->getWorkingDaysForMonth(
            Carbon::parse($period['start_date'])->year,
            Carbon::parse($period['start_date'])->month
        );
        
        $dailyRate = $employee->calculateDailyRate();
        $basicPay = $dailyRate * $workingDays;
        
        // Create payroll detail
        return PayrollDetail::create([
            'payroll_id' => $payroll->id,
            'employee_id' => $employee->id,
            'basic_pay' => $basicPay,
            'overtime_pay' => 0,
            'holiday_pay' => 0,
            'allowances' => 0,
            'bonuses' => 0,
            'gross_pay' => $basicPay,
            'sss_contribution' => 0,
            'philhealth_contribution' => 0,
            'pagibig_contribution' => 0,
            'withholding_tax' => 0,
            'other_deductions' => 0,
            'total_deductions' => 0,
            'net_pay' => $basicPay,
            'working_days' => $workingDays,
            'days_absent' => 0,
            'overtime_hours' => 0,
            'is_processed' => false
        ]);
    }
    
    /**
     * Generate unique payroll number
     */
    private function generatePayrollNumber(string $scheduleCode): string
    {
        $prefix = match($scheduleCode) {
            'weekly' => 'WEK',
            'semi_monthly' => 'SEM', 
            'monthly' => 'MON',
            default => 'REG'
        };
        
        $date = Carbon::now()->format('YmdHis');
        $random = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        return $prefix . '-' . $date . $random;
    }
}
