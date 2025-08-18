<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NightDifferentialSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('night_differential_settings')->updateOrInsert(
            ['id' => 1],
            [
                'start_time' => '22:00:00',
                'end_time' => '05:00:00',
                'rate_multiplier' => 1.10,
                'description' => 'Standard night differential (10 PM - 5 AM)',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
