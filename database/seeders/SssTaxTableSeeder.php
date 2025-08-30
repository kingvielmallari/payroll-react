<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SssTaxTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing records
        DB::table('sss_tax_table')->truncate();

        // Insert 2025 SSS Contribution Table values (correct official values)
        $contributions = [
            ['range_start' => 0, 'range_end' => 5249.99, 'employee_share' => 250.00, 'employer_share' => 510.00, 'total_contribution' => 760.00],
            ['range_start' => 5250, 'range_end' => 5749.99, 'employee_share' => 275.00, 'employer_share' => 560.00, 'total_contribution' => 835.00],
            ['range_start' => 5750, 'range_end' => 6249.99, 'employee_share' => 300.00, 'employer_share' => 610.00, 'total_contribution' => 910.00],
            ['range_start' => 6250, 'range_end' => 6749.99, 'employee_share' => 325.00, 'employer_share' => 660.00, 'total_contribution' => 985.00],
            ['range_start' => 6750, 'range_end' => 7249.99, 'employee_share' => 350.00, 'employer_share' => 710.00, 'total_contribution' => 1060.00],
            ['range_start' => 7250, 'range_end' => 7749.99, 'employee_share' => 375.00, 'employer_share' => 760.00, 'total_contribution' => 1135.00],
            ['range_start' => 7750, 'range_end' => 8249.99, 'employee_share' => 400.00, 'employer_share' => 810.00, 'total_contribution' => 1210.00],
            ['range_start' => 8250, 'range_end' => 8749.99, 'employee_share' => 425.00, 'employer_share' => 860.00, 'total_contribution' => 1285.00],
            ['range_start' => 8750, 'range_end' => 9249.99, 'employee_share' => 450.00, 'employer_share' => 910.00, 'total_contribution' => 1360.00],
            ['range_start' => 9250, 'range_end' => 9749.99, 'employee_share' => 475.00, 'employer_share' => 960.00, 'total_contribution' => 1435.00],
            ['range_start' => 9750, 'range_end' => 10249.99, 'employee_share' => 500.00, 'employer_share' => 1010.00, 'total_contribution' => 1510.00],
            ['range_start' => 10250, 'range_end' => 10749.99, 'employee_share' => 525.00, 'employer_share' => 1060.00, 'total_contribution' => 1585.00],
            ['range_start' => 10750, 'range_end' => 11249.99, 'employee_share' => 550.00, 'employer_share' => 1110.00, 'total_contribution' => 1660.00],
            ['range_start' => 11250, 'range_end' => 11749.99, 'employee_share' => 575.00, 'employer_share' => 1160.00, 'total_contribution' => 1735.00],
            ['range_start' => 11750, 'range_end' => 12249.99, 'employee_share' => 600.00, 'employer_share' => 1210.00, 'total_contribution' => 1810.00],
            ['range_start' => 12250, 'range_end' => 12749.99, 'employee_share' => 625.00, 'employer_share' => 1260.00, 'total_contribution' => 1885.00],
            ['range_start' => 12750, 'range_end' => 13249.99, 'employee_share' => 650.00, 'employer_share' => 1310.00, 'total_contribution' => 1960.00],
            ['range_start' => 13250, 'range_end' => 13749.99, 'employee_share' => 675.00, 'employer_share' => 1360.00, 'total_contribution' => 2035.00],
            ['range_start' => 13750, 'range_end' => 14249.99, 'employee_share' => 700.00, 'employer_share' => 1410.00, 'total_contribution' => 2110.00],
            ['range_start' => 14250, 'range_end' => 14749.99, 'employee_share' => 725.00, 'employer_share' => 1460.00, 'total_contribution' => 2185.00],
            ['range_start' => 14750, 'range_end' => 15249.99, 'employee_share' => 750.00, 'employer_share' => 1530.00, 'total_contribution' => 2280.00],
            ['range_start' => 15250, 'range_end' => 15749.99, 'employee_share' => 775.00, 'employer_share' => 1580.00, 'total_contribution' => 2355.00],
            ['range_start' => 15750, 'range_end' => 16249.99, 'employee_share' => 800.00, 'employer_share' => 1630.00, 'total_contribution' => 2430.00],
            ['range_start' => 16250, 'range_end' => 16749.99, 'employee_share' => 825.00, 'employer_share' => 1680.00, 'total_contribution' => 2505.00],
            ['range_start' => 16750, 'range_end' => 17249.99, 'employee_share' => 850.00, 'employer_share' => 1730.00, 'total_contribution' => 2580.00],
            ['range_start' => 17250, 'range_end' => 17749.99, 'employee_share' => 875.00, 'employer_share' => 1780.00, 'total_contribution' => 2655.00],
            ['range_start' => 17750, 'range_end' => 18249.99, 'employee_share' => 900.00, 'employer_share' => 1830.00, 'total_contribution' => 2730.00],
            ['range_start' => 18250, 'range_end' => 18749.99, 'employee_share' => 925.00, 'employer_share' => 1880.00, 'total_contribution' => 2805.00],
            ['range_start' => 18750, 'range_end' => 19249.99, 'employee_share' => 950.00, 'employer_share' => 1930.00, 'total_contribution' => 2880.00],
            ['range_start' => 19250, 'range_end' => 19749.99, 'employee_share' => 975.00, 'employer_share' => 1980.00, 'total_contribution' => 2955.00],
            ['range_start' => 19750, 'range_end' => 20249.99, 'employee_share' => 1000.00, 'employer_share' => 2030.00, 'total_contribution' => 3030.00],
            ['range_start' => 20250, 'range_end' => 20749.99, 'employee_share' => 1025.00, 'employer_share' => 2080.00, 'total_contribution' => 3105.00],
            ['range_start' => 20750, 'range_end' => 21249.99, 'employee_share' => 1050.00, 'employer_share' => 2130.00, 'total_contribution' => 3180.00],
            ['range_start' => 21250, 'range_end' => 21749.99, 'employee_share' => 1075.00, 'employer_share' => 2180.00, 'total_contribution' => 3255.00],
            ['range_start' => 21750, 'range_end' => 22249.99, 'employee_share' => 1100.00, 'employer_share' => 2230.00, 'total_contribution' => 3330.00],
            ['range_start' => 22250, 'range_end' => 22749.99, 'employee_share' => 1125.00, 'employer_share' => 2280.00, 'total_contribution' => 3405.00],
            ['range_start' => 22750, 'range_end' => 23249.99, 'employee_share' => 1150.00, 'employer_share' => 2330.00, 'total_contribution' => 3480.00],
            ['range_start' => 23250, 'range_end' => 23749.99, 'employee_share' => 1175.00, 'employer_share' => 2380.00, 'total_contribution' => 3555.00],
            ['range_start' => 23750, 'range_end' => 24249.99, 'employee_share' => 1200.00, 'employer_share' => 2430.00, 'total_contribution' => 3630.00],
            ['range_start' => 24250, 'range_end' => 24749.99, 'employee_share' => 1225.00, 'employer_share' => 2480.00, 'total_contribution' => 3705.00],
            ['range_start' => 24750, 'range_end' => 25249.99, 'employee_share' => 1250.00, 'employer_share' => 2530.00, 'total_contribution' => 3780.00],
            ['range_start' => 25250, 'range_end' => 25749.99, 'employee_share' => 1275.00, 'employer_share' => 2580.00, 'total_contribution' => 3855.00],
            ['range_start' => 25750, 'range_end' => 26249.99, 'employee_share' => 1300.00, 'employer_share' => 2630.00, 'total_contribution' => 3930.00],
            ['range_start' => 26250, 'range_end' => 26749.99, 'employee_share' => 1325.00, 'employer_share' => 2680.00, 'total_contribution' => 4005.00],
            ['range_start' => 26750, 'range_end' => 27249.99, 'employee_share' => 1350.00, 'employer_share' => 2730.00, 'total_contribution' => 4080.00],
            ['range_start' => 27250, 'range_end' => 27749.99, 'employee_share' => 1375.00, 'employer_share' => 2780.00, 'total_contribution' => 4155.00],
            ['range_start' => 27750, 'range_end' => 28249.99, 'employee_share' => 1400.00, 'employer_share' => 2830.00, 'total_contribution' => 4230.00],
            ['range_start' => 28250, 'range_end' => 28749.99, 'employee_share' => 1425.00, 'employer_share' => 2880.00, 'total_contribution' => 4305.00],
            ['range_start' => 28750, 'range_end' => 29249.99, 'employee_share' => 1450.00, 'employer_share' => 2930.00, 'total_contribution' => 4380.00],
            ['range_start' => 29250, 'range_end' => 29749.99, 'employee_share' => 1475.00, 'employer_share' => 2980.00, 'total_contribution' => 4455.00],
            ['range_start' => 29750, 'range_end' => 30249.99, 'employee_share' => 1500.00, 'employer_share' => 3030.00, 'total_contribution' => 4530.00],
            ['range_start' => 30250, 'range_end' => 30749.99, 'employee_share' => 1525.00, 'employer_share' => 3080.00, 'total_contribution' => 4605.00],
            ['range_start' => 30750, 'range_end' => 31249.99, 'employee_share' => 1550.00, 'employer_share' => 3130.00, 'total_contribution' => 4680.00],
            ['range_start' => 31250, 'range_end' => 31749.99, 'employee_share' => 1575.00, 'employer_share' => 3180.00, 'total_contribution' => 4755.00],
            ['range_start' => 31750, 'range_end' => 32249.99, 'employee_share' => 1600.00, 'employer_share' => 3230.00, 'total_contribution' => 4830.00],
            ['range_start' => 32250, 'range_end' => 32749.99, 'employee_share' => 1625.00, 'employer_share' => 3280.00, 'total_contribution' => 4905.00],
            ['range_start' => 32750, 'range_end' => 33249.99, 'employee_share' => 1650.00, 'employer_share' => 3330.00, 'total_contribution' => 4980.00],
            ['range_start' => 33250, 'range_end' => 33749.99, 'employee_share' => 1675.00, 'employer_share' => 3380.00, 'total_contribution' => 5055.00],
            ['range_start' => 33750, 'range_end' => 34249.99, 'employee_share' => 1700.00, 'employer_share' => 3430.00, 'total_contribution' => 5130.00],
            ['range_start' => 34250, 'range_end' => 34749.99, 'employee_share' => 1725.00, 'employer_share' => 3480.00, 'total_contribution' => 5205.00],
            ['range_start' => 34750, 'range_end' => null, 'employee_share' => 1750.00, 'employer_share' => 3530.00, 'total_contribution' => 5280.00], // "Above" range
        ];

        foreach ($contributions as $contribution) {
            DB::table('sss_tax_table')->insert([
                'range_start' => $contribution['range_start'],
                'range_end' => $contribution['range_end'],
                'employee_share' => $contribution['employee_share'],
                'employer_share' => $contribution['employer_share'],
                'total_contribution' => $contribution['total_contribution'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
