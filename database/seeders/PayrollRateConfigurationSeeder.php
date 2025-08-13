<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PayrollRateConfiguration;

class PayrollRateConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configurations = PayrollRateConfiguration::getDefaults();

        foreach ($configurations as $config) {
            PayrollRateConfiguration::updateOrCreate(
                ['type_name' => $config['type_name']],
                $config
            );
        }
    }
}
