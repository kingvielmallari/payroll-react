<?php

// Test break time handling
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use Carbon\Carbon;

echo "Testing Break Time Parsing Fix\n";
echo "=============================\n\n";

// Test 1: Empty break times should be converted to null
echo "Test 1: Empty break time handling\n";
$timeLog = new App\Models\TimeLog();
$timeLog->break_in = '';
$timeLog->break_out = '00:00:00';

echo "Empty string break_in: " . ($timeLog->break_in === null ? 'NULL (✓)' : 'NOT NULL (✗)') . "\n";
echo "00:00:00 break_out: " . ($timeLog->break_out === null ? 'NULL (✓)' : 'NOT NULL (✗)') . "\n\n";

// Test 2: Valid break times should be preserved
echo "Test 2: Valid break time handling\n";
$timeLog2 = new App\Models\TimeLog();
$timeLog2->break_in = '12:00';
$timeLog2->break_out = '13:00';

echo "Valid break_in (12:00): " . ($timeLog2->break_in !== null ? 'PRESERVED (✓)' : 'NULL (✗)') . "\n";
echo "Valid break_out (13:00): " . ($timeLog2->break_out !== null ? 'PRESERVED (✓)' : 'NULL (✗)') . "\n\n";

echo "All tests completed!\n";
