<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$logs = App\Models\TimeLog::where('employee_id', 2)
    ->whereBetween('log_date', ['2025-08-16', '2025-08-31'])
    ->get(['log_date', 'log_type']);

foreach ($logs as $log) {
    echo $log->log_date->format('M d') . ': ' . $log->log_type . PHP_EOL;
}
