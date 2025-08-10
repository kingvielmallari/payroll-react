<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            FreshInstallationSeeder::class,
            DepartmentSeeder::class,
            PositionSeeder::class,
            TimeScheduleSeeder::class,
            DayScheduleSeeder::class,
            EmployeeSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('🎉 Fresh installation database seeded successfully!');
        $this->command->info('');
        $this->command->info('📋 Default accounts created:');
        $this->command->info('   System Admin: admin@payroll.com (password: password)');
        $this->command->info('   HR Head: hr.head@payroll.com (password: password)');
        $this->command->info('   HR Staff: hr.staff@payroll.com (password: password)');
        $this->command->info('');
        $this->command->info('🏢 4 Departments and 6 positions seeded');
        $this->command->info('👥 10 Sample employees created (password: password)');
        $this->command->info('⏰ 4 Time schedules and 4 day schedules seeded');
        $this->command->info('⚙️ Pay schedules seeded (all disabled - configure in settings)');
        $this->command->info('💰 Tax tables seeded with current Philippine rates');
        $this->command->info('🔧 Default payroll configuration applied');
    }
}
