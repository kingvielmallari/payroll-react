<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\PayrollRateConfiguration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add Night Differential configurations
        $configs = [
            [
                'type_name' => 'night_differential',
                'display_name' => 'Night Differential',
                'regular_rate_multiplier' => 1.10,  // 10% night diff
                'overtime_rate_multiplier' => 1.375,  // 10% of 1.25 OT = 1.375
                'description' => 'Night differential for work between 10 PM and 6 AM',
                'is_active' => true,
                'sort_order' => 100
            ],
            [
                'type_name' => 'rest_day_night_differential',
                'display_name' => 'Rest Day + Night Diff',
                'regular_rate_multiplier' => 1.43,  // 30% rest + 10% night = 1.43
                'overtime_rate_multiplier' => 1.859,  // 69% OT + 10% night = 1.859
                'description' => 'Rest day work with night differential',
                'is_active' => true,
                'sort_order' => 101
            ],
            [
                'type_name' => 'special_holiday_night_differential',
                'display_name' => 'SPE Holiday + Night Diff',
                'regular_rate_multiplier' => 1.43,  // 30% SPE holiday + 10% night = 1.43
                'overtime_rate_multiplier' => 1.859,  // 69% OT + 10% night = 1.859
                'description' => 'Special holiday work with night differential',
                'is_active' => true,
                'sort_order' => 102
            ],
            [
                'type_name' => 'regular_holiday_night_differential',
                'display_name' => 'REG Holiday + Night Diff',
                'regular_rate_multiplier' => 2.20,  // 100% REG holiday + 10% night = 2.20
                'overtime_rate_multiplier' => 2.86,  // 160% OT + 10% night = 2.86
                'description' => 'Regular holiday work with night differential',
                'is_active' => true,
                'sort_order' => 103
            ]
        ];

        foreach ($configs as $config) {
            PayrollRateConfiguration::updateOrCreate(
                ['type_name' => $config['type_name']],
                $config
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove night differential configurations
        PayrollRateConfiguration::whereIn('type_name', [
            'night_differential',
            'rest_day_night_differential',
            'special_holiday_night_differential',
            'regular_holiday_night_differential'
        ])->delete();
    }
};
