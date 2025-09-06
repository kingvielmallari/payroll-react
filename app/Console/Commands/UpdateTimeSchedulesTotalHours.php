<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TimeSchedule;

class UpdateTimeSchedulesTotalHours extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'time-schedules:update-total-hours';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update total_hours for all existing time schedules';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating total hours for existing time schedules...');

        $timeSchedules = TimeSchedule::all();
        $updated = 0;

        foreach ($timeSchedules as $schedule) {
            $oldTotalHours = $schedule->total_hours;
            $schedule->total_hours = $schedule->calculateTotalHours();
            $schedule->save();

            $this->line("Updated '{$schedule->name}': {$oldTotalHours} → {$schedule->total_hours} hours");
            $updated++;
        }

        $this->info("Successfully updated {$updated} time schedules.");
        return 0;
    }
}
