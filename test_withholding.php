<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';

use App\Models\WithholdingTaxTable;

// Test the example: Taxable Income = ₱13,624.10 (weekly frequency)
$taxableIncome = 13624.10;
$payFrequency = 'weekly';

echo 'Testing Withholding Tax Calculation:' . PHP_EOL;
echo 'Taxable Income: ₱' . number_format($taxableIncome, 2) . PHP_EOL;
echo 'Pay Frequency: ' . $payFrequency . PHP_EOL;
echo '---' . PHP_EOL;

$withholdingTax = WithholdingTaxTable::calculateWithholdingTax($taxableIncome, $payFrequency);
echo 'Calculated Withholding Tax: ₱' . number_format($withholdingTax, 2) . PHP_EOL;
echo 'Expected: ₱1,619.02' . PHP_EOL;

// Show the bracket used
$bracket = WithholdingTaxTable::where('pay_frequency', $payFrequency)
    ->where('range_start', '<=', $taxableIncome)
    ->where(function ($query) use ($taxableIncome) {
        $query->where('range_end', '>=', $taxableIncome)
            ->orWhereNull('range_end');
    })
    ->first();

if ($bracket) {
    echo 'Used Bracket: ' . $bracket->bracket . PHP_EOL;
    echo 'Range: ₱' . number_format($bracket->range_start, 2) . ' - ₱' . number_format($bracket->range_end, 2) . PHP_EOL;
    echo 'Base Tax: ₱' . number_format($bracket->base_tax, 2) . PHP_EOL;
    echo 'Tax Rate: ' . $bracket->tax_rate . '%' . PHP_EOL;
    echo 'Excess Over: ₱' . number_format($bracket->excess_over, 2) . PHP_EOL;

    // Manual calculation verification
    $excess = $taxableIncome - $bracket->excess_over;
    $taxOnExcess = $excess * ($bracket->tax_rate / 100);
    $totalTax = $bracket->base_tax + $taxOnExcess;

    echo '---' . PHP_EOL;
    echo 'Manual Calculation Verification:' . PHP_EOL;
    echo 'Excess over lower limit: ' . number_format($taxableIncome, 2) . ' - ' . number_format($bracket->excess_over, 2) . ' = ' . number_format($excess, 2) . PHP_EOL;
    echo 'Tax on excess: ' . number_format($excess, 2) . ' × ' . $bracket->tax_rate . '% = ' . number_format($taxOnExcess, 2) . PHP_EOL;
    echo 'Total: ' . number_format($bracket->base_tax, 2) . ' + ' . number_format($taxOnExcess, 2) . ' = ' . number_format($totalTax, 2) . PHP_EOL;
}
