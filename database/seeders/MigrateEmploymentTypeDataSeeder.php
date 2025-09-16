<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\EmploymentType;
use Illuminate\Support\Facades\DB;

class MigrateEmploymentTypeDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Mapping of old string values to new employment type names
        $mapping = [
            'regular' => 'Regular',
            'probationary' => 'Probationary',
            'contractual' => 'Contractual',
            'part_time' => 'Part Time',
            'casual' => 'Casual'
        ];

        foreach ($mapping as $oldValue => $newName) {
            // Find the employment type by name
            $employmentType = EmploymentType::where('name', $newName)->first();

            if ($employmentType) {
                // Update all employees with this employment type
                Employee::where('employment_type', $oldValue)
                    ->whereNull('employment_type_id')
                    ->update(['employment_type_id' => $employmentType->id]);

                $count = Employee::where('employment_type', $oldValue)->count();
                $this->command->info("Updated {$count} employees from '{$oldValue}' to '{$newName}' (ID: {$employmentType->id})");
            }
        }

        // Create 'Casual' employment type if it doesn't exist (it wasn't in our default seeder)
        if (!EmploymentType::where('name', 'Casual')->exists()) {
            $casualType = EmploymentType::create([
                'name' => 'Casual',
                'has_benefits' => false,
                'description' => 'Casual employee',
                'is_active' => true
            ]);

            // Update casual employees
            Employee::where('employment_type', 'casual')
                ->whereNull('employment_type_id')
                ->update(['employment_type_id' => $casualType->id]);

            $count = Employee::where('employment_type', 'casual')->count();
            $this->command->info("Created 'Casual' employment type and updated {$count} employees");
        }

        // Show summary
        $totalMigrated = Employee::whereNotNull('employment_type_id')->count();
        $totalUnmigrated = Employee::whereNull('employment_type_id')->count();

        $this->command->info("Migration summary:");
        $this->command->info("- Migrated: {$totalMigrated} employees");
        $this->command->info("- Unmigrated: {$totalUnmigrated} employees");
    }
}
