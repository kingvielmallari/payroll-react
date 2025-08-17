<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

use App\Models\Employee;

$employee = Employee::with('timeSchedule')->find(2);

if ($employee) {
    echo "Employee: " . $employee->first_name . " " . $employee->last_name . "\n";

    if ($employee->timeSchedule) {
        echo "Time Schedule: " . $employee->timeSchedule->name . "\n";
        echo "Time In: " . $employee->timeSchedule->time_in->format('H:i') . "\n";
        echo "Time Out: " . $employee->timeSchedule->time_out->format('H:i') . "\n";

        if ($employee->timeSchedule->break_start) {
            echo "Break Start: " . $employee->timeSchedule->break_start->format('H:i') . "\n";
        } else {
            echo "Break Start: Not set\n";
        }

        if ($employee->timeSchedule->break_end) {
            echo "Break End: " . $employee->timeSchedule->break_end->format('H:i') . "\n";
        } else {
            echo "Break End: Not set\n";
        }
    } else {
        echo "No time schedule assigned\n";
    }
} else {
    echo "Employee not found\n";
}
