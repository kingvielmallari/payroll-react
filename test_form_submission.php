<?php

// Simple test to check what happens when form is submitted
// This will help debug the cash advance form submission

echo "Testing form submission to cash-advances/store\n";

// Test with minimal data
$testData = [
    'employee_id' => 1, // Assuming employee ID 1 exists
    'requested_amount' => 1000,
    'deduction_frequency' => 'per_payroll',
    'installments' => 2,
    'interest_rate' => 0,
    'reason' => 'Test cash advance',
    'starting_payroll_period' => 1
];

echo "Test data:\n";
print_r($testData);

// Check if the required fields match what the controller expects
echo "\nChecking validation rules...\n";

$requiredFields = [
    'employee_id',
    'requested_amount',
    'deduction_frequency',
    'reason',
    'starting_payroll_period'
];

foreach ($requiredFields as $field) {
    if (isset($testData[$field])) {
        echo "✓ $field: " . $testData[$field] . "\n";
    } else {
        echo "✗ Missing: $field\n";
    }
}

echo "\nFor per_payroll frequency, also need:\n";
echo "✓ installments: " . ($testData['installments'] ?? 'MISSING') . "\n";
