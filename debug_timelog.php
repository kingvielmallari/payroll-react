<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$timeLog = \App\Models\TimeLog::first();

if ($timeLog) {
    echo "Class: " . get_class($timeLog) . PHP_EOL;
    echo "Log Type: " . ($timeLog->log_type ?? 'null') . PHP_EOL;
    echo "Has getRateConfiguration method: " . (method_exists($timeLog, 'getRateConfiguration') ? 'yes' : 'no') . PHP_EOL;
    echo "Serialized: " . json_encode($timeLog) . PHP_EOL;
} else {
    echo "No TimeLog found" . PHP_EOL;
}
