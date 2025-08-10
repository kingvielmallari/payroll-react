<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaidLeaveSetting;

class PaidLeaveSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $leaveTypes = [
            [
                'name' => 'Vacation Leave',
                'code' => 'VL',
                'description' => 'Annual vacation leave for rest and recreation',
                'days_per_year' => 15,
                'accrual_method' => 'monthly',
                'accrual_rate' => 1.25, // 15 days / 12 months
                'minimum_service_months' => 12,
                'prorated_first_year' => true,
                'minimum_days_usage' => 1,
                'maximum_days_usage' => 5,
                'notice_days_required' => 3,
                'can_carry_over' => true,
                'max_carry_over_days' => 5,
                'expires_annually' => true,
                'expiry_month' => 12,
                'can_convert_to_cash' => true,
                'cash_conversion_rate' => 1.0,
                'max_convertible_days' => 10,
                'is_active' => true,
                'is_system_default' => true,
            ],
            [
                'name' => 'Sick Leave',
                'code' => 'SL',
                'description' => 'Leave for medical reasons and health recovery',
                'days_per_year' => 15,
                'accrual_method' => 'monthly',
                'accrual_rate' => 1.25,
                'minimum_service_months' => 6,
                'prorated_first_year' => true,
                'minimum_days_usage' => 1,
                'maximum_days_usage' => 0, // No limit
                'notice_days_required' => 0, // No advance notice required for sick leave
                'can_carry_over' => false,
                'max_carry_over_days' => 0,
                'expires_annually' => true,
                'expiry_month' => 12,
                'can_convert_to_cash' => false,
                'is_active' => true,
                'is_system_default' => true,
            ],
            [
                'name' => 'Maternity Leave',
                'code' => 'ML',
                'description' => 'Leave for female employees during childbirth (105 days)',
                'days_per_year' => 105,
                'accrual_method' => 'yearly',
                'accrual_rate' => 105,
                'minimum_service_months' => 0,
                'prorated_first_year' => false,
                'minimum_days_usage' => 60, // Mandatory period
                'maximum_days_usage' => 105,
                'notice_days_required' => 30,
                'can_carry_over' => false,
                'expires_annually' => false,
                'can_convert_to_cash' => false,
                'applicable_gender' => ['female'],
                'is_active' => true,
                'is_system_default' => true,
            ],
            [
                'name' => 'Paternity Leave',
                'code' => 'PL',
                'description' => 'Leave for male employees during spouse childbirth (7 days)',
                'days_per_year' => 7,
                'accrual_method' => 'yearly',
                'accrual_rate' => 7,
                'minimum_service_months' => 0,
                'prorated_first_year' => false,
                'minimum_days_usage' => 7,
                'maximum_days_usage' => 7,
                'notice_days_required' => 30,
                'can_carry_over' => false,
                'expires_annually' => false,
                'can_convert_to_cash' => false,
                'applicable_gender' => ['male'],
                'is_active' => true,
                'is_system_default' => true,
            ],
            [
                'name' => 'Emergency Leave',
                'code' => 'EL',
                'description' => 'Leave for family emergencies and unforeseen circumstances',
                'days_per_year' => 5,
                'accrual_method' => 'yearly',
                'accrual_rate' => 5,
                'minimum_service_months' => 6,
                'prorated_first_year' => true,
                'minimum_days_usage' => 1,
                'maximum_days_usage' => 3,
                'notice_days_required' => 0,
                'can_carry_over' => false,
                'expires_annually' => true,
                'expiry_month' => 12,
                'can_convert_to_cash' => false,
                'is_active' => true,
                'is_system_default' => false,
            ],
            [
                'name' => 'Bereavement Leave',
                'code' => 'BL',
                'description' => 'Leave for death in the family',
                'days_per_year' => 3,
                'accrual_method' => 'yearly',
                'accrual_rate' => 3,
                'minimum_service_months' => 0,
                'prorated_first_year' => false,
                'minimum_days_usage' => 1,
                'maximum_days_usage' => 3,
                'notice_days_required' => 0,
                'can_carry_over' => false,
                'expires_annually' => true,
                'expiry_month' => 12,
                'can_convert_to_cash' => false,
                'is_active' => true,
                'is_system_default' => false,
            ],
        ];
        
        foreach ($leaveTypes as $leave) {
            PaidLeaveSetting::firstOrCreate(
                ['code' => $leave['code']],
                $leave
            );
        }
        
        $this->command->info('Paid leave settings seeded successfully!');
    }
}
