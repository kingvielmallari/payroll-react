<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\TimeLog;
use App\Models\Employee;

$timeLog = TimeLog::whereHas('employee', function ($q) {
    $q->where('first_name', 'Maria');
})->first();

if ($timeLog) {
    echo "Time Log found for: " . $timeLog->employee->first_name . " " . $timeLog->employee->last_name . "\n";
    echo "Date: " . $timeLog->log_date . "\n";
    echo "Time In: " . $timeLog->time_in . "\n";
    echo "Time Out: " . $timeLog->time_out . "\n";
    echo "Regular OT Hours: " . ($timeLog->dynamic_regular_overtime_hours ?? 'null') . "\n";
    echo "Night Diff OT Hours: " . ($timeLog->dynamic_night_diff_overtime_hours ?? 'null') . "\n";
    echo "Total OT Hours: " . ($timeLog->dynamic_overtime_hours ?? 'null') . "\n";

    echo "\n--- Time Period Breakdown ---\n";
    $breakdown = $timeLog->getTimePeriodBreakdown();
    echo "Breakdown count: " . count($breakdown) . "\n";

    foreach ($breakdown as $period) {
        echo $period['type'] . ': ' . $period['start_time'] . ' - ' . $period['end_time'] . ' (' . $period['hours'] . 'h)' . "\n";
    }
} else {
    echo "No time log found for Maria\n";
}
