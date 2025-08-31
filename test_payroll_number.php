<?php

require_once 'vendor/autoload.php';

use App\Models\Payroll;

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing payroll number generation with gap filling...\n";

// Check existing payrolls with SEMIMONTHLY prefix
$existing = Payroll::where('payroll_number', 'like', 'SEMIMONTHLY-202508%')
    ->orderBy('payroll_number')
    ->get(['id', 'payroll_number', 'status']);

echo "Existing SEMIMONTHLY payrolls:\n";
foreach ($existing as $p) {
    echo "  ID: {$p->id} | {$p->payroll_number} ({$p->status})\n";
}

// Test what happens when we simulate missing numbers by creating a test scenario
echo "\nTesting gap-filling logic...\n";

// Manual test - let's simulate having payrolls 002, 003, 004 but missing 001
$testNumbers = ['SEMIMONTHLY-202508-002', 'SEMIMONTHLY-202508-003', 'SEMIMONTHLY-202508-004'];
echo "Simulating scenario with payrolls: " . implode(', ', $testNumbers) . "\n";

// Extract sequence numbers like our algorithm does
$sequenceNumbers = [];
foreach ($testNumbers as $payrollNumber) {
    $parts = explode('-', $payrollNumber);
    $sequenceNumbers[] = (int) end($parts);
}

echo "Sequence numbers found: " . implode(', ', $sequenceNumbers) . "\n";

// Find first gap
$newNumber = 1;
while (in_array($newNumber, $sequenceNumbers)) {
    $newNumber++;
}

echo "First available number would be: " . str_pad($newNumber, 3, '0', STR_PAD_LEFT) . "\n";

echo "Done!\n";
