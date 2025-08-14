<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$timeLog = \App\Models\TimeLog::where('log_date', '2025-08-01')->first();

if ($timeLog) {
    echo "=== Original Time Log Data ===\n";
    echo "log_date: " . $timeLog->log_date . "\n";
    echo "time_in: " . $timeLog->time_in . "\n";
    echo "time_out: " . $timeLog->time_out . "\n";
    echo "break_in: " . $timeLog->break_in . "\n";
    echo "break_out: " . $timeLog->break_out . "\n";

    echo "\n=== Parsed Values ===\n";
    $logDate = \Carbon\Carbon::parse($timeLog->log_date)->format('Y-m-d');
    echo "logDate: " . $logDate . "\n";

    $timeIn = \Carbon\Carbon::parse($logDate . ' ' . \Carbon\Carbon::parse($timeLog->time_in)->format('H:i:s'));
    $timeOut = \Carbon\Carbon::parse($logDate . ' ' . \Carbon\Carbon::parse($timeLog->time_out)->format('H:i:s'));

    echo "timeIn: " . $timeIn . "\n";
    echo "timeOut: " . $timeOut . "\n";

    echo "\n=== Calculations ===\n";
    $totalMinutes = $timeOut->diffInMinutes($timeIn);
    echo "totalMinutes (timeOut->diffInMinutes(timeIn)): " . $totalMinutes . "\n";

    // Try the reverse
    $totalMinutesReverse = $timeIn->diffInMinutes($timeOut);
    echo "totalMinutes (timeIn->diffInMinutes(timeOut)): " . $totalMinutesReverse . "\n";

    // Check if timeOut is after timeIn
    echo "timeOut > timeIn: " . ($timeOut->gt($timeIn) ? 'true' : 'false') . "\n";
    echo "timeOut timestamp: " . $timeOut->timestamp . "\n";
    echo "timeIn timestamp: " . $timeIn->timestamp . "\n";
    echo "\n=== Schedule Calculations ===\n";
    $employee = $timeLog->employee;
    $timeSchedule = $employee->timeSchedule ?? null;

    echo "timeSchedule exists: " . ($timeSchedule ? 'yes' : 'no') . "\n";
    if ($timeSchedule) {
        echo "timeSchedule->start_time: '" . $timeSchedule->start_time . "'\n";
        echo "timeSchedule->end_time: '" . $timeSchedule->end_time . "'\n";
    }

    $scheduledStartTime = $timeSchedule ? $timeSchedule->start_time : '08:00';
    $scheduledEndTime = $timeSchedule ? $timeSchedule->end_time : '17:00';

    echo "scheduledStartTime (after fallback): '" . $scheduledStartTime . "'\n";
    echo "scheduledEndTime (after fallback): '" . $scheduledEndTime . "'\n";

    $schedStart = \Carbon\Carbon::parse($logDate . ' ' . $scheduledStartTime);
    $schedEnd = \Carbon\Carbon::parse($logDate . ' ' . $scheduledEndTime);

    echo "schedStart: " . $schedStart . "\n";
    echo "schedEnd: " . $schedEnd . "\n";

    $scheduledWorkMinutes = $schedStart->diffInMinutes($schedEnd);
    $standardHours = $scheduledWorkMinutes / 60;

    echo "scheduledWorkMinutes: " . $scheduledWorkMinutes . "\n";
    echo "standardHours: " . $standardHours . "\n";

    // After break deduction
    $breakMinutesDeducted = 60; // From above calculation
    $totalMinutes = 540 - $breakMinutesDeducted;
    $totalHours = $totalMinutes / 60;

    echo "totalMinutes after break: " . $totalMinutes . "\n";
    echo "totalHours after break: " . $totalHours . "\n";

    $regularHours = min($totalHours, $standardHours);
    echo "regularHours = min($totalHours, $standardHours): " . $regularHours . "\n";
} else {
    echo "No time log found\n";
}
