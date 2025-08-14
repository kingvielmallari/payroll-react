<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Finding Time Logs with 11:00 AM - 1:00 PM ===" . PHP_EOL;

// Find time logs around this period
$timeLogs = App\Models\TimeLog::where('employee_id', 1)
    ->whereDate('log_date', '>=', '2025-08-01')
    ->whereDate('log_date', '<=', '2025-08-10')
    ->get();

foreach ($timeLogs as $log) {
    if ($log->time_in && $log->time_out) {
        echo $log->log_date . " - " . $log->time_in . " to " . $log->time_out . " = " . $log->total_hours . "h" . PHP_EOL;

        // Check if this is the 11:00 AM - 1:00 PM entry
        if ($log->time_in == '11:00:00' && $log->time_out == '13:00:00') {
            echo ">>> FOUND THE PROBLEMATIC ENTRY <<<" . PHP_EOL;
            echo "Break In: " . ($log->break_in ?? 'NULL') . PHP_EOL;
            echo "Break Out: " . ($log->break_out ?? 'NULL') . PHP_EOL;

            // Test the calculation manually
            $timeIn = \Carbon\Carbon::parse($log->log_date . ' ' . $log->time_in);
            $timeOut = \Carbon\Carbon::parse($log->log_date . ' ' . $log->time_out);
            $totalMinutes = $timeOut->diffInMinutes($timeIn);

            echo "Raw work time: " . $totalMinutes . " minutes (" . ($totalMinutes / 60) . " hours)" . PHP_EOL;

            // Check break deduction
            $breakMinutesDeducted = 0;

            if ($log->break_in && $log->break_out) {
                echo "Using recorded break times" . PHP_EOL;
                $breakIn = \Carbon\Carbon::parse($log->log_date . ' ' . $log->break_in);
                $breakOut = \Carbon\Carbon::parse($log->log_date . ' ' . $log->break_out);
                $breakMinutesDeducted = $breakOut->diffInMinutes($breakIn);
            } else {
                echo "No recorded break times, checking standard break overlap" . PHP_EOL;
                $standardBreakStart = \Carbon\Carbon::parse($log->log_date . ' 12:00:00');
                $standardBreakEnd = \Carbon\Carbon::parse($log->log_date . ' 13:00:00');

                if ($timeIn->lt($standardBreakEnd) && $timeOut->gt($standardBreakStart)) {
                    $overlapStart = max($timeIn->timestamp, $standardBreakStart->timestamp);
                    $overlapEnd = min($timeOut->timestamp, $standardBreakEnd->timestamp);

                    if ($overlapEnd > $overlapStart) {
                        $breakMinutesDeducted = ($overlapEnd - $overlapStart) / 60;
                        echo "Break overlap: " . date('H:i', $overlapStart) . " to " . date('H:i', $overlapEnd) . PHP_EOL;
                    }
                }
            }

            echo "Break minutes deducted: " . $breakMinutesDeducted . PHP_EOL;
            $finalMinutes = $totalMinutes - $breakMinutesDeducted;
            echo "Final work time: " . $finalMinutes . " minutes (" . ($finalMinutes / 60) . " hours)" . PHP_EOL;
        }
    }
}
