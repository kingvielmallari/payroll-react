<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Employee;
use App\Models\TimeLog;
use App\Http\Controllers\TimeLogController;

$employee = Employee::where('first_name', 'Carlos')->first();
$timeSchedule = $employee->timeSchedule;

echo "=== DEBUGGING OVERTIME START TIME ===\n";
echo "Employee: {$employee->first_name} {$employee->last_name}\n";
echo "Schedule: {$timeSchedule->time_in->format('H:i')} - {$timeSchedule->time_out->format('H:i')}\n";
echo "Break: {$timeSchedule->break_start->format('H:i')} - {$timeSchedule->break_end->format('H:i')}\n\n";

// Calculate standard work minutes
$schedStart = \Carbon\Carbon::parse('2025-08-19 ' . $timeSchedule->time_in->format('H:i'));
$schedEnd = \Carbon\Carbon::parse('2025-08-19 ' . $timeSchedule->time_out->format('H:i'));
$standardWorkMinutes = $schedStart->diffInMinutes($schedEnd);

if ($timeSchedule->break_start && $timeSchedule->break_end) {
    $scheduledBreakMinutes = $timeSchedule->break_start->diffInMinutes($timeSchedule->break_end);
    $standardWorkMinutes -= $scheduledBreakMinutes;
}

echo "Standard work minutes: {$standardWorkMinutes} (= " . ($standardWorkMinutes / 60) . " hours)\n";

// Get grace period settings
$gracePeriodSettings = \App\Models\GracePeriodSetting::current();
$lateGracePeriodMinutes = $gracePeriodSettings ? $gracePeriodSettings->late_grace_minutes : 0;
echo "Late grace period: {$lateGracePeriodMinutes} minutes\n\n";

// Test Aug 19
echo "=== AUG 19 TEST ===\n";
$actualTimeIn19 = \Carbon\Carbon::parse('2025-08-19 08:15:00');
$schedStart19 = \Carbon\Carbon::parse('2025-08-19 08:00:00');

// Apply grace period logic
$workStartTime19 = $schedStart19; // Default to scheduled start
if ($actualTimeIn19->gt($schedStart19)) {
    $lateMinutes19 = $schedStart19->diffInMinutes($actualTimeIn19);
    echo "Late by: {$lateMinutes19} minutes\n";
    if ($lateMinutes19 > $lateGracePeriodMinutes) {
        $workStartTime19 = $actualTimeIn19; // Beyond grace period
        echo "Beyond grace period → work starts at actual time: {$workStartTime19->format('H:i')}\n";
    } else {
        echo "Within grace period → work starts at scheduled time: {$workStartTime19->format('H:i')}\n";
    }
}

$actualRegularWorkEndTime19 = $workStartTime19->copy()->addMinutes($standardWorkMinutes);
if ($timeSchedule->break_start && $timeSchedule->break_end) {
    $scheduledBreakMinutes = $timeSchedule->break_start->diffInMinutes($timeSchedule->break_end);
    $actualRegularWorkEndTime19->addMinutes($scheduledBreakMinutes);
}

echo "Regular work should end at: {$actualRegularWorkEndTime19->format('H:i')}\n";
echo "Expected OT start: {$actualRegularWorkEndTime19->format('H:i')}\n";
echo "OT period: {$actualRegularWorkEndTime19->format('H:i')} - 22:00 = " . $actualRegularWorkEndTime19->diffInHours(\Carbon\Carbon::parse('2025-08-19 22:00')) . " hours\n\n";

// Test Aug 20
echo "=== AUG 20 TEST ===\n";
$actualTimeIn20 = \Carbon\Carbon::parse('2025-08-20 08:16:00');
$schedStart20 = \Carbon\Carbon::parse('2025-08-20 08:00:00');

// Apply grace period logic
$workStartTime20 = $schedStart20; // Default to scheduled start
if ($actualTimeIn20->gt($schedStart20)) {
    $lateMinutes20 = $schedStart20->diffInMinutes($actualTimeIn20);
    echo "Late by: {$lateMinutes20} minutes\n";
    if ($lateMinutes20 > $lateGracePeriodMinutes) {
        $workStartTime20 = $actualTimeIn20; // Beyond grace period
        echo "Beyond grace period → work starts at actual time: {$workStartTime20->format('H:i')}\n";
    } else {
        echo "Within grace period → work starts at scheduled time: {$workStartTime20->format('H:i')}\n";
    }
}

$actualRegularWorkEndTime20 = $workStartTime20->copy()->addMinutes($standardWorkMinutes);
if ($timeSchedule->break_start && $timeSchedule->break_end) {
    $scheduledBreakMinutes = $timeSchedule->break_start->diffInMinutes($timeSchedule->break_end);
    $actualRegularWorkEndTime20->addMinutes($scheduledBreakMinutes);
}

echo "Regular work should end at: {$actualRegularWorkEndTime20->format('H:i')}\n";
echo "Expected OT start: {$actualRegularWorkEndTime20->format('H:i')}\n";
echo "OT period: {$actualRegularWorkEndTime20->format('H:i')} - 22:00 = " . $actualRegularWorkEndTime20->diffInHours(\Carbon\Carbon::parse('2025-08-20 22:00')) . " hours\n\n";

// Now let's see what the DTR Summary display shows
echo "=== DTR SUMMARY DISPLAY ===\n";
$log19 = TimeLog::where('employee_id', $employee->id)->where('log_date', '2025-08-19')->first();
$log20 = TimeLog::where('employee_id', $employee->id)->where('log_date', '2025-08-20')->first();

if ($log19) {
    $breakdown19 = $log19->getTimePeriodBreakdown();
    echo "Aug 19 breakdown:\n";
    foreach ($breakdown19 as $period) {
        echo "  {$period['type']}: {$period['start_time']} - {$period['end_time']} ({$period['hours']}h)\n";
    }
}

if ($log20) {
    $breakdown20 = $log20->getTimePeriodBreakdown();
    echo "\nAug 20 breakdown:\n";
    foreach ($breakdown20 as $period) {
        echo "  {$period['type']}: {$period['start_time']} - {$period['end_time']} ({$period['hours']}h)\n";
    }
}
