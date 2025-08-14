<?php

namespace App\Console\Commands;

use App\Models\TimeLog;
use Illuminate\Console\Command;
use Carbon\Carbon;

class RecalculateTimeLogHours extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'time-logs:recalculate {--date=} {--employee_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate time log hours with new break time logic';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->option('date');
        $employeeId = $this->option('employee_id');

        $query = TimeLog::query();

        if ($date) {
            $query->where('log_date', $date);
        }

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $timeLogs = $query->with('employee')->get();

        $this->info("Found {$timeLogs->count()} time logs to recalculate");

        foreach ($timeLogs as $timeLog) {
            $this->info("Processing: {$timeLog->employee->first_name} {$timeLog->employee->last_name} - {$timeLog->log_date}");
            $this->info("Before: Regular Hours: {$timeLog->regular_hours}, Total Hours: {$timeLog->total_hours}");

            // Call the private method via reflection (for testing purposes)
            $controller = new \App\Http\Controllers\TimeLogController();
            $reflection = new \ReflectionClass($controller);
            $method = $reflection->getMethod('calculateHours');
            $method->setAccessible(true);
            $method->invoke($controller, $timeLog);

            $timeLog->refresh();
            $this->info("After: Regular Hours: {$timeLog->regular_hours}, Total Hours: {$timeLog->total_hours}");
            $this->info("---");
        }

        $this->info("Recalculation completed!");
    }
}
