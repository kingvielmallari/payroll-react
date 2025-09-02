<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PhilHealthTaxTable;
use Carbon\Carbon;

class PhilHealthTaxTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        PhilHealthTaxTable::truncate();

        // Current PhilHealth rates as of 2025
        PhilHealthTaxTable::create([
            'min_salary' => 10000.00,
            'max_salary' => 100000.00,
            'ee_percentage' => 2.50, // 2.5% employee share
            'er_percentage' => 2.50, // 2.5% employer share
            'min_contribution' => 500.00, // Minimum contribution
            'max_contribution' => 5000.00, // Maximum contribution
            'is_active' => true,
            'effective_date' => Carbon::now()->startOfYear(), // Effective from start of current year
            'description' => 'Standard PhilHealth contribution rates for Monthly Basic Salary ₱10,000 - ₱100,000'
        ]);

        // For salaries above 100,000 (unlimited range)
        PhilHealthTaxTable::create([
            'min_salary' => 100000.01,
            'max_salary' => null, // Unlimited
            'ee_percentage' => 2.50, // 2.5% employee share
            'er_percentage' => 2.50, // 2.5% employer share
            'min_contribution' => 5000.00, // Fixed at maximum
            'max_contribution' => 5000.00, // Fixed at maximum
            'is_active' => true,
            'effective_date' => Carbon::now()->startOfYear(),
            'description' => 'PhilHealth contribution rates for Monthly Basic Salary above ₱100,000 (capped at ₱5,000)'
        ]);
    }
}
