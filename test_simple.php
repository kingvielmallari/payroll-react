<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$newNumber = App\Models\Payroll::generatePayrollNumber('semimonthly');
echo 'Generated number: ' . $newNumber . PHP_EOL;
