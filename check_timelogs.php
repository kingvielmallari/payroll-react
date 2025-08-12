<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TimeLog;

echo "Time Logs Summary\n";
echo "================\n\n";

$timeLogs = TimeLog::where('payroll_id', 93)->get();

echo "Count: " . $timeLogs->count() . "\n";
echo "Total Regular Hours: " . $timeLogs->sum('regular_hours') . "\n";
echo "Total Overtime Hours: " . $timeLogs->sum('overtime_hours') . "\n";
echo "Total Total Hours: " . $timeLogs->sum('total_hours') . "\n\n";

echo "Individual Time Logs:\n";
foreach ($timeLogs as $log) {
    echo "Date: {$log->log_date->format('Y-m-d')}, Regular: {$log->regular_hours}, OT: {$log->overtime_hours}, Total: {$log->total_hours}\n";
}
