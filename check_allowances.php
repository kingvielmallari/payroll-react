<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

use App\Models\AllowanceBonusSetting;

echo "All Allowance/Bonus Settings:\n";
foreach (AllowanceBonusSetting::all() as $setting) {
    echo "ID: {$setting->id} | Name: {$setting->name} | Type: {$setting->type} | Active: " . ($setting->is_active ? 'YES' : 'NO') . " | Taxable: " . ($setting->is_taxable ? 'YES' : 'NO') . "\n";
}
