<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ALL EMPLOYEES TIME LOGS FOR AUG 21-22 ===\n";
$logs = DB::table('time_logs')
    ->join('employees', 'time_logs.employee_id', '=', 'employees.id')
    ->whereIn('log_date', ['2025-08-21', '2025-08-22'])
    ->select('employees.first_name', 'employees.last_name', 'log_date', 'time_in', 'time_out', 'regular_hours', 'total_hours')
    ->orderBy('employees.first_name')
    ->orderBy('log_date')
    ->get();

foreach ($logs as $log) {
    echo "{$log->first_name} {$log->last_name} - {$log->log_date}: {$log->time_in} - {$log->time_out} (Regular: {$log->regular_hours}h)\n";
}

echo "\n=== CARLOS MENDOZA TIME LOGS ===\n";
$logs = DB::table('time_logs')
    ->join('employees', 'time_logs.employee_id', '=', 'employees.id')
    ->where('employees.first_name', 'Carlos')
    ->where('employees.last_name', 'Mendoza')
    ->whereIn('log_date', ['2025-08-21', '2025-08-22'])
    ->select('log_date', 'time_in', 'time_out', 'regular_hours', 'total_hours')
    ->orderBy('log_date')
    ->get();

foreach ($logs as $log) {
    echo "Date: {$log->log_date}, Time In: {$log->time_in}, Time Out: {$log->time_out}, Regular: {$log->regular_hours}h, Total: {$log->total_hours}h\n";
}

// Check Bernadette's time schedule
echo "\n=== BERNADETTE'S TIME SCHEDULE ===\n";
$bernadette = DB::table('employees')
    ->join('time_schedules', 'employees.time_schedule_id', '=', 'time_schedules.id')
    ->where('employees.first_name', 'Bernadette')
    ->where('employees.last_name', 'Mallari')
    ->select('time_schedules.time_in', 'time_schedules.time_out', 'time_schedules.break_start', 'time_schedules.break_end')
    ->first();

if ($bernadette) {
    echo "Bernadette schedule: {$bernadette->time_in} - {$bernadette->time_out}, Break: {$bernadette->break_start} - {$bernadette->break_end}\n";
} else {
    echo "No time schedule found for Bernadette\n";
}
