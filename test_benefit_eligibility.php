<?php

// Test script to verify benefit eligibility implementation
// Run with: php test_benefit_eligibility.php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Employee;
use App\Models\DeductionTaxSetting;
use App\Models\AllowanceBonusSetting;
use App\Models\PaidLeaveSetting;

echo "Testing Benefit Eligibility Implementation\n";
echo "==========================================\n\n";

// Test 1: Deduction Settings
echo "1. Testing Deduction Settings\n";
$deductions = DeductionTaxSetting::all();
foreach ($deductions as $deduction) {
    echo "- {$deduction->name}: Apply to {$deduction->benefit_eligibility}\n";
}

echo "\n2. Testing Allowance Settings\n";
$allowances = AllowanceBonusSetting::all();
foreach ($allowances as $allowance) {
    echo "- {$allowance->name}: Apply to {$allowance->benefit_eligibility}\n";
}

echo "\n3. Testing Leave Settings\n";
$leaves = PaidLeaveSetting::all();
foreach ($leaves as $leave) {
    echo "- {$leave->name}: Apply to {$leave->benefit_eligibility}\n";
}

echo "\n4. Testing Filter by Benefit Status\n";
$withBenefitsDeductions = DeductionTaxSetting::forBenefitStatus('with_benefits')->count();
$withoutBenefitsDeductions = DeductionTaxSetting::forBenefitStatus('without_benefits')->count();
echo "- Deductions for 'with_benefits': {$withBenefitsDeductions}\n";
echo "- Deductions for 'without_benefits': {$withoutBenefitsDeductions}\n";

echo "\nTest completed!\n";
