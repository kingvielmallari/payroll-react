<?php

require_once 'vendor/autoload.php';

// Bootstrap the application
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\TimeSchedule;
use App\Models\Employee;

echo "Time Schedules in database:\n";
$timeSchedules = TimeSchedule::all();
foreach ($timeSchedules as $schedule) {
    echo "ID: {$schedule->id}, Name: {$schedule->name}, Time: {$schedule->time_in->format('H:i')} - {$schedule->time_out->format('H:i')}\n";
}

echo "\nEmployee 2 details:\n";
$employee = Employee::with('timeSchedule')->find(2);
if ($employee) {
    echo "Name: {$employee->first_name} {$employee->last_name}\n";
    echo "Time Schedule ID: " . ($employee->time_schedule_id ?? 'null') . "\n";

    if ($employee->timeSchedule) {
        echo "Schedule Name: {$employee->timeSchedule->name}\n";
        echo "Schedule Times: {$employee->timeSchedule->time_in->format('H:i')} - {$employee->timeSchedule->time_out->format('H:i')}\n";
    } else {
        echo "No time schedule relationship found\n";
    }
}
