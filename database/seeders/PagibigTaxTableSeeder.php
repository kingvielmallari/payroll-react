<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PagibigTaxTable;

class PagibigTaxTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        PagibigTaxTable::truncate();

        // Pag-IBIG Tax Table for 2025
        // Based on the requirement:
        // 0.00 - 10,000.00: 2% employee, 2% employer, 4% total, max 200
        // 10,000.01 - above: 2% employee, 2% employer, 4% total, max 200

        $pagibigRates = [
            [
                'range_start' => 0.00,
                'range_end' => 10000.00,
                'employee_share' => 2.00, // 2%
                'employer_share' => 2.00, // 2%
                'total_contribution' => 4.00, // 4%
                'min_contribution' => 0.00, // No minimum
                'max_contribution' => 200.00, // Max ₱200
                'is_active' => true,
            ],
            [
                'range_start' => 10000.01,
                'range_end' => null, // Above 10,000 (NULL for "above")
                'employee_share' => 2.00, // 2%
                'employer_share' => 2.00, // 2%
                'total_contribution' => 4.00, // 4%
                'min_contribution' => 0.00, // No minimum
                'max_contribution' => 200.00, // Max ₱200
                'is_active' => true,
            ]
        ];

        foreach ($pagibigRates as $rate) {
            PagibigTaxTable::create($rate);
        }

        $this->command->info('Pag-IBIG Tax Table seeded successfully!');
    }
}
