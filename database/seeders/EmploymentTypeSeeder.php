<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EmploymentType;

class EmploymentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employmentTypes = [
            [
                'name' => 'Regular',
                'has_benefits' => true,
                'description' => 'Full-time regular employee with complete benefits',
                'is_active' => true
            ],
            [
                'name' => 'Probationary',
                'has_benefits' => false,
                'description' => 'Employee under probationary period',
                'is_active' => true
            ],
            [
                'name' => 'Contractual',
                'has_benefits' => false,
                'description' => 'Contract-based employee',
                'is_active' => true
            ],
            [
                'name' => 'Part Time',
                'has_benefits' => false,
                'description' => 'Part-time employee',
                'is_active' => true
            ]
        ];

        foreach ($employmentTypes as $type) {
            EmploymentType::create($type);
        }
    }
}
