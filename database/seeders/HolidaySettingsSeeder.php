<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Holiday;

class HolidaySettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $holidays2025 = [
            [
                'name' => 'New Year\'s Day',
                'date' => '2025-01-01',
                'type' => 'regular',
                'rate_multiplier' => 2.00,
                'is_double_pay' => true,
                'double_pay_rate' => 2.00,
                'pay_rule' => 'double_pay',
                'description' => 'New Year celebration',
                'is_recurring' => true,
                'is_active' => true,
                'year' => 2025,
            ],
            [
                'name' => 'Chinese New Year',
                'date' => '2025-01-29',
                'type' => 'special_non_working',
                'rate_multiplier' => 1.30,
                'is_double_pay' => false,
                'double_pay_rate' => 1.30,
                'pay_rule' => 'holiday_rate',
                'description' => 'Chinese New Year celebration',
                'is_recurring' => false,
                'is_active' => true,
                'year' => 2025,
            ],
            [
                'name' => 'People Power Anniversary',
                'date' => '2025-02-25',
                'type' => 'special_non_working',
                'rate_multiplier' => 1.30,
                'is_double_pay' => false,
                'double_pay_rate' => 1.30,
                'pay_rule' => 'holiday_rate',
                'description' => 'EDSA People Power Revolution Anniversary',
                'is_recurring' => true,
                'is_active' => true,
                'year' => 2025,
            ],
            [
                'name' => 'Maundy Thursday',
                'date' => '2025-04-17',
                'type' => 'regular',
                'rate_multiplier' => 2.00,
                'is_double_pay' => true,
                'double_pay_rate' => 2.00,
                'pay_rule' => 'double_pay',
                'description' => 'Holy Thursday',
                'is_recurring' => false,
                'is_active' => true,
                'year' => 2025,
            ],
            [
                'name' => 'Good Friday',
                'date' => '2025-04-18',
                'type' => 'regular',
                'rate_multiplier' => 2.00,
                'is_double_pay' => true,
                'double_pay_rate' => 2.00,
                'pay_rule' => 'double_pay',
                'description' => 'Good Friday observance',
                'is_recurring' => false,
                'is_active' => true,
                'year' => 2025,
            ],
            [
                'name' => 'Araw ng Kagitingan',
                'date' => '2025-04-09',
                'type' => 'regular',
                'rate_multiplier' => 2.00,
                'is_double_pay' => true,
                'double_pay_rate' => 2.00,
                'pay_rule' => 'double_pay',
                'description' => 'Day of Valor / Bataan and Corregidor Day',
                'is_recurring' => true,
                'is_active' => true,
                'year' => 2025,
            ],
            [
                'name' => 'Labor Day',
                'date' => '2025-05-01',
                'type' => 'regular',
                'rate_multiplier' => 2.00,
                'is_double_pay' => true,
                'double_pay_rate' => 2.00,
                'pay_rule' => 'double_pay',
                'description' => 'International Labor Day',
                'is_recurring' => true,
                'is_active' => true,
                'year' => 2025,
            ],
            [
                'name' => 'Independence Day',
                'date' => '2025-06-12',
                'type' => 'regular',
                'rate_multiplier' => 2.00,
                'is_double_pay' => true,
                'double_pay_rate' => 2.00,
                'pay_rule' => 'double_pay',
                'description' => 'Philippine Independence Day',
                'is_recurring' => true,
                'is_active' => true,
                'year' => 2025,
            ],
            [
                'name' => 'National Heroes Day',
                'date' => '2025-08-25',
                'type' => 'regular',
                'rate_multiplier' => 2.00,
                'is_double_pay' => true,
                'double_pay_rate' => 2.00,
                'pay_rule' => 'double_pay',
                'description' => 'National Heroes Day',
                'is_recurring' => true,
                'is_active' => true,
                'year' => 2025,
            ],
            [
                'name' => 'All Saints\' Day',
                'date' => '2025-11-01',
                'type' => 'special_non_working',
                'rate_multiplier' => 1.30,
                'is_double_pay' => false,
                'double_pay_rate' => 1.30,
                'pay_rule' => 'holiday_rate',
                'description' => 'All Saints Day',
                'is_recurring' => true,
                'is_active' => true,
                'year' => 2025,
            ],
            [
                'name' => 'Bonifacio Day',
                'date' => '2025-11-30',
                'type' => 'regular',
                'rate_multiplier' => 2.00,
                'is_double_pay' => true,
                'double_pay_rate' => 2.00,
                'pay_rule' => 'double_pay',
                'description' => 'Andres Bonifacio Day',
                'is_recurring' => true,
                'is_active' => true,
                'year' => 2025,
            ],
            [
                'name' => 'Christmas Day',
                'date' => '2025-12-25',
                'type' => 'regular',
                'rate_multiplier' => 2.00,
                'is_double_pay' => true,
                'double_pay_rate' => 2.00,
                'pay_rule' => 'double_pay',
                'description' => 'Christmas Day celebration',
                'is_recurring' => true,
                'is_active' => true,
                'year' => 2025,
            ],
            [
                'name' => 'Rizal Day',
                'date' => '2025-12-30',
                'type' => 'regular',
                'rate_multiplier' => 2.00,
                'is_double_pay' => true,
                'double_pay_rate' => 2.00,
                'pay_rule' => 'double_pay',
                'description' => 'Dr. Jose Rizal Day',
                'is_recurring' => true,
                'is_active' => true,
                'year' => 2025,
            ],
            [
                'name' => 'New Year\'s Eve',
                'date' => '2025-12-31',
                'type' => 'special_non_working',
                'rate_multiplier' => 1.30,
                'is_double_pay' => false,
                'double_pay_rate' => 1.30,
                'pay_rule' => 'holiday_rate',
                'description' => 'Last Day of the Year',
                'is_recurring' => true,
                'is_active' => true,
                'year' => 2025,
            ],
        ];
        
        foreach ($holidays2025 as $holiday) {
            Holiday::firstOrCreate(
                [
                    'date' => $holiday['date'],
                    'year' => $holiday['year']
                ],
                $holiday
            );
        }
        
        $this->command->info('Holiday settings seeded successfully for 2025!');
    }
}
