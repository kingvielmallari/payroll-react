<?php

require_once 'vendor/autoload.php';

// Bootstrap the application
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\TimeSchedule;

echo "Time Schedule 2 (Morning Shift) details:\n";
$schedule = TimeSchedule::find(2);
if ($schedule) {
    echo "Name: {$schedule->name}\n";
    echo "Time In: {$schedule->time_in->format('H:i')}\n";
    echo "Time Out: {$schedule->time_out->format('H:i')}\n";
    echo "Break Start: " . ($schedule->break_start ? $schedule->break_start->format('H:i') : 'Not set') . "\n";
    echo "Break End: " . ($schedule->break_end ? $schedule->break_end->format('H:i') : 'Not set') . "\n";
}
