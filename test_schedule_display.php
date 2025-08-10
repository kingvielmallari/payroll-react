<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Schedule Display ===\n\n";

// Test TimeSchedule
$timeSchedules = App\Models\TimeSchedule::take(3)->get();
echo "Time Schedules:\n";
foreach ($timeSchedules as $schedule) {
    echo "- {$schedule->name}: {$schedule->time_range_display}\n";
}

echo "\nDay Schedules:\n";
$daySchedules = App\Models\DaySchedule::take(3)->get();
foreach ($daySchedules as $schedule) {
    echo "- {$schedule->name}: {$schedule->days_display}\n";
}

echo "\nEmployee Schedule Display:\n";
$employee = App\Models\Employee::with(['daySchedule', 'timeSchedule', 'user'])->first();
if ($employee) {
    echo "Employee: {$employee->user->name}\n";
    echo "Schedule: {$employee->schedule_display}\n";
    echo "Hourly Rate: ₱" . number_format($employee->hourly_rate, 2) . "/hr\n";
} else {
    echo "No employee found.\n";
}
