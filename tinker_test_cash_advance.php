<?php

// Test Enhanced Cash Advance System

// Run this in artisan tinker: php artisan tinker

/*
// 1. Test creating a cash advance with interest
$employee = App\Models\Employee::first();
$user = App\Models\User::first();

$cashAdvance = App\Models\CashAdvance::create([
    'employee_id' => $employee->id,
    'reference_number' => 'CA-TEST-' . date('Y-m-d-His'),
    'requested_amount' => 10000.00,
    'installments' => 5,
    'interest_rate' => 2.5,
    'reason' => 'Medical emergency - testing enhanced system',
    'requested_date' => now(),
    'first_deduction_date' => now()->addMonth(),
    'requested_by' => $user->id,
    'status' => 'pending',
]);

// Calculate interest
$cashAdvance->updateCalculations();
$cashAdvance->save();

echo "Cash Advance Created:\n";
echo "Reference: " . $cashAdvance->reference_number . "\n";
echo "Principal: ₱" . number_format($cashAdvance->requested_amount, 2) . "\n";
echo "Interest Rate: " . $cashAdvance->interest_rate . "%\n";
echo "Interest Amount: ₱" . number_format($cashAdvance->interest_amount, 2) . "\n";
echo "Total Amount: ₱" . number_format($cashAdvance->total_amount, 2) . "\n";
echo "Monthly Installment: ₱" . number_format($cashAdvance->installment_amount, 2) . "\n";

// 2. Test approval
$cashAdvance->approve(
    $cashAdvance->requested_amount,
    $cashAdvance->installments,
    $user->id,
    'Approved for testing',
    $cashAdvance->interest_rate
);

echo "\nAfter Approval:\n";
echo "Status: " . $cashAdvance->status . "\n";
echo "Outstanding Balance: ₱" . number_format($cashAdvance->outstanding_balance, 2) . "\n";

// 3. Verify calculations
$expectedInterest = ($cashAdvance->approved_amount * $cashAdvance->interest_rate) / 100;
$expectedTotal = $cashAdvance->approved_amount + $expectedInterest;
$expectedInstallment = $expectedTotal / $cashAdvance->installments;

echo "\nCalculation Verification:\n";
echo "Expected Interest: ₱" . number_format($expectedInterest, 2) . "\n";
echo "Calculated Interest: ₱" . number_format($cashAdvance->interest_amount, 2) . "\n";
echo "Match: " . ($expectedInterest == $cashAdvance->interest_amount ? "✅" : "❌") . "\n";

echo "Expected Total: ₱" . number_format($expectedTotal, 2) . "\n";
echo "Calculated Total: ₱" . number_format($cashAdvance->total_amount, 2) . "\n";
echo "Match: " . ($expectedTotal == $cashAdvance->total_amount ? "✅" : "❌") . "\n";

echo "Expected Installment: ₱" . number_format($expectedInstallment, 2) . "\n";
echo "Calculated Installment: ₱" . number_format($cashAdvance->installment_amount, 2) . "\n";
echo "Match: " . (abs($expectedInstallment - $cashAdvance->installment_amount) < 0.01 ? "✅" : "❌") . "\n";

*/
