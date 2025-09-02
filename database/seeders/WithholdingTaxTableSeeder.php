<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\WithholdingTaxTable;

class WithholdingTaxTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        WithholdingTaxTable::truncate();

        // DAILY Tax Brackets
        $dailyBrackets = [
            [
                'pay_frequency' => 'daily',
                'bracket' => 1,
                'range_start' => 0,
                'range_end' => 685,
                'base_tax' => 0.00,
                'tax_rate' => 0.00,
                'excess_over' => 0
            ],
            [
                'pay_frequency' => 'daily',
                'bracket' => 2,
                'range_start' => 685,
                'range_end' => 1095,
                'base_tax' => 0.00,
                'tax_rate' => 15.00,
                'excess_over' => 685
            ],
            [
                'pay_frequency' => 'daily',
                'bracket' => 3,
                'range_start' => 1096,
                'range_end' => 2191,
                'base_tax' => 61.65,
                'tax_rate' => 20.00,
                'excess_over' => 1096
            ],
            [
                'pay_frequency' => 'daily',
                'bracket' => 4,
                'range_start' => 2192,
                'range_end' => 5478,
                'base_tax' => 280.85,
                'tax_rate' => 25.00,
                'excess_over' => 2192
            ],
            [
                'pay_frequency' => 'daily',
                'bracket' => 5,
                'range_start' => 5479,
                'range_end' => 21917,
                'base_tax' => 1102.60,
                'tax_rate' => 30.00,
                'excess_over' => 5479
            ],
            [
                'pay_frequency' => 'daily',
                'bracket' => 6,
                'range_start' => 21918,
                'range_end' => null,
                'base_tax' => 6034.30,
                'tax_rate' => 35.00,
                'excess_over' => 21918
            ]
        ];

        // WEEKLY Tax Brackets
        $weeklyBrackets = [
            [
                'pay_frequency' => 'weekly',
                'bracket' => 1,
                'range_start' => 0,
                'range_end' => 4808,
                'base_tax' => 0.00,
                'tax_rate' => 0.00,
                'excess_over' => 0
            ],
            [
                'pay_frequency' => 'weekly',
                'bracket' => 2,
                'range_start' => 4808,
                'range_end' => 7691,
                'base_tax' => 0.00,
                'tax_rate' => 15.00,
                'excess_over' => 4808
            ],
            [
                'pay_frequency' => 'weekly',
                'bracket' => 3,
                'range_start' => 7692,
                'range_end' => 15384,
                'base_tax' => 432.60,
                'tax_rate' => 20.00,
                'excess_over' => 7692
            ],
            [
                'pay_frequency' => 'weekly',
                'bracket' => 4,
                'range_start' => 15385,
                'range_end' => 38461,
                'base_tax' => 1971.20,
                'tax_rate' => 25.00,
                'excess_over' => 15385
            ],
            [
                'pay_frequency' => 'weekly',
                'bracket' => 5,
                'range_start' => 38462,
                'range_end' => 153845,
                'base_tax' => 7740.45,
                'tax_rate' => 30.00,
                'excess_over' => 38462
            ],
            [
                'pay_frequency' => 'weekly',
                'bracket' => 6,
                'range_start' => 153846,
                'range_end' => null,
                'base_tax' => 42355.65,
                'tax_rate' => 35.00,
                'excess_over' => 153846
            ]
        ];

        // SEMI-MONTHLY Tax Brackets
        $semiMonthlyBrackets = [
            [
                'pay_frequency' => 'semi_monthly',
                'bracket' => 1,
                'range_start' => 0,
                'range_end' => 10417,
                'base_tax' => 0.00,
                'tax_rate' => 0.00,
                'excess_over' => 0
            ],
            [
                'pay_frequency' => 'semi_monthly',
                'bracket' => 2,
                'range_start' => 10417,
                'range_end' => 16666,
                'base_tax' => 0.00,
                'tax_rate' => 15.00,
                'excess_over' => 10417
            ],
            [
                'pay_frequency' => 'semi_monthly',
                'bracket' => 3,
                'range_start' => 16667,
                'range_end' => 33332,
                'base_tax' => 937.50,
                'tax_rate' => 20.00,
                'excess_over' => 16667
            ],
            [
                'pay_frequency' => 'semi_monthly',
                'bracket' => 4,
                'range_start' => 33333,
                'range_end' => 83332,
                'base_tax' => 4270.70,
                'tax_rate' => 25.00,
                'excess_over' => 33333
            ],
            [
                'pay_frequency' => 'semi_monthly',
                'bracket' => 5,
                'range_start' => 83333,
                'range_end' => 333332,
                'base_tax' => 16770.70,
                'tax_rate' => 30.00,
                'excess_over' => 83333
            ],
            [
                'pay_frequency' => 'semi_monthly',
                'bracket' => 6,
                'range_start' => 333333,
                'range_end' => null,
                'base_tax' => 91770.70,
                'tax_rate' => 35.00,
                'excess_over' => 333333
            ]
        ];

        // MONTHLY Tax Brackets
        $monthlyBrackets = [
            [
                'pay_frequency' => 'monthly',
                'bracket' => 1,
                'range_start' => 0,
                'range_end' => 20833,
                'base_tax' => 0.00,
                'tax_rate' => 0.00,
                'excess_over' => 0
            ],
            [
                'pay_frequency' => 'monthly',
                'bracket' => 2,
                'range_start' => 20833,
                'range_end' => 33332,
                'base_tax' => 0.00,
                'tax_rate' => 15.00,
                'excess_over' => 20833
            ],
            [
                'pay_frequency' => 'monthly',
                'bracket' => 3,
                'range_start' => 33333,
                'range_end' => 66666,
                'base_tax' => 1875.00,
                'tax_rate' => 20.00,
                'excess_over' => 33333
            ],
            [
                'pay_frequency' => 'monthly',
                'bracket' => 4,
                'range_start' => 66667,
                'range_end' => 166666,
                'base_tax' => 8541.80,
                'tax_rate' => 25.00,
                'excess_over' => 66667
            ],
            [
                'pay_frequency' => 'monthly',
                'bracket' => 5,
                'range_start' => 166667,
                'range_end' => 666666,
                'base_tax' => 33541.80,
                'tax_rate' => 30.00,
                'excess_over' => 166667
            ],
            [
                'pay_frequency' => 'monthly',
                'bracket' => 6,
                'range_start' => 666667,
                'range_end' => null,
                'base_tax' => 183541.80,
                'tax_rate' => 35.00,
                'excess_over' => 666667
            ]
        ];

        // Insert all brackets
        $allBrackets = array_merge($dailyBrackets, $weeklyBrackets, $semiMonthlyBrackets, $monthlyBrackets);

        foreach ($allBrackets as $bracket) {
            WithholdingTaxTable::create($bracket);
        }

        $this->command->info('Withholding Tax Table seeded successfully!');
    }
}
