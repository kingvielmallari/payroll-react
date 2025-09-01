<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$employee = App\Models\Employee::first();
if ($employee) {
    echo 'Employee found: ' . $employee->full_name . ' (ID: ' . $employee->id . ') - Status: ' . $employee->benefits_status . PHP_EOL;
} else {
    echo 'No employees found' . PHP_EOL;
}
