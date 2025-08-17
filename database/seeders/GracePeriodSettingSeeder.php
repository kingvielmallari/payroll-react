<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\GracePeriodSetting;

class GracePeriodSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure only one grace period setting record exists with default zeros
        GracePeriodSetting::query()->delete(); // Clear any existing records

        GracePeriodSetting::create([
            'id' => 1,
            'late_grace_minutes' => 0,
            'undertime_grace_minutes' => 0,
            'overtime_threshold_minutes' => 0,
            'is_active' => true,
        ]);
    }
}
