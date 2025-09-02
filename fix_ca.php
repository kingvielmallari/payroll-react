<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\CashAdvance;

$ca = CashAdvance::where('reference_number', 'CA-2025090001')->first();

if ($ca) {
    $payments = $ca->payments()->sum('amount');
    echo "Reference: {$ca->reference_number}\n";
    echo "Total amount: {$ca->total_amount}\n";
    echo "Total payments: {$payments}\n";
    echo "Current outstanding: {$ca->outstanding_balance}\n";

    $correctOutstanding = $ca->total_amount - $payments;
    echo "Should be outstanding: {$correctOutstanding}\n";

    $ca->outstanding_balance = $correctOutstanding;

    if ($ca->outstanding_balance > 0) {
        $ca->status = 'approved';
        echo "Status changed back to 'approved'\n";
    } else {
        $ca->status = 'fully_paid';
        echo "Status remains 'fully_paid'\n";
    }

    $ca->save();
    echo "Cash advance updated successfully!\n";
} else {
    echo "Cash advance not found!\n";
}
