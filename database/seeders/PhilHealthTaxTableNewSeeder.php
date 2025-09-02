<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PhilHealthTaxTable;

class PhilHealthTaxTableNewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        PhilHealthTaxTable::truncate();

        // PhilHealth rates for 2024-2025
        $philHealthRates = [
            [
                'range_start' => 0,
                'range_end' => 9999.99,
                'employee_share' => 0,
                'employer_share' => 0,
                'total_contribution' => 0,
                'min_contribution' => 0,
                'max_contribution' => 0,
                'is_active' => true,
            ],
            [
                'range_start' => 10000.00,
                'range_end' => 10000.00,
                'employee_share' => 2.5,
                'employer_share' => 2.5,
                'total_contribution' => 5.0,
                'min_contribution' => 500,
                'max_contribution' => 5000,
                'is_active' => true,
            ],
            [
                'range_start' => 10000.01,
                'range_end' => 99999.99,
                'employee_share' => 2.5,
                'employer_share' => 2.5,
                'total_contribution' => 5.0,
                'min_contribution' => 500,
                'max_contribution' => 5000,
                'is_active' => true,
            ],
            [
                'range_start' => 100000.00,
                'range_end' => null, // null means unlimited/above
                'employee_share' => 2.5,
                'employer_share' => 2.5,
                'total_contribution' => 5.0,
                'min_contribution' => 500,
                'max_contribution' => 5000,
                'is_active' => true,
            ],
        ];

        foreach ($philHealthRates as $rate) {
            PhilHealthTaxTable::create($rate);
        }

        $this->command->info('PhilHealth tax table seeded successfully with new structure.');
    }
}
