<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DeductionTaxSetting;
use App\Models\AllowanceBonusSetting;
use App\Models\PaidLeaveSetting;

class UpdateBenefitEligibilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Updating benefit eligibility for existing settings...');

        // Update DeductionTaxSettings
        $deductionsUpdated = DeductionTaxSetting::whereNull('benefit_eligibility')
            ->orWhere('benefit_eligibility', '')
            ->update(['benefit_eligibility' => 'with_benefits']);
            
        $this->command->info("Updated {$deductionsUpdated} deduction tax settings");

        // Update AllowanceBonusSettings  
        $allowancesUpdated = AllowanceBonusSetting::whereNull('benefit_eligibility')
            ->orWhere('benefit_eligibility', '')
            ->update(['benefit_eligibility' => 'both']);
            
        $this->command->info("Updated {$allowancesUpdated} allowance bonus settings");

        // Update PaidLeaveSettings
        $leavesUpdated = PaidLeaveSetting::whereNull('benefit_eligibility')
            ->orWhere('benefit_eligibility', '')
            ->update(['benefit_eligibility' => 'with_benefits']);
            
        $this->command->info("Updated {$leavesUpdated} paid leave settings");

        $this->command->info('Benefit eligibility update completed!');
    }
}
