<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SystemAdministratorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create system administrator role if it doesn't exist
        $adminRole = Role::firstOrCreate(['name' => 'System Administrator']);
        
        // Create all permissions if they don't exist
        $permissions = [
            // User management
            'view users', 'create users', 'edit users', 'delete users',
            
            // Employee management  
            'view employees', 'create employees', 'edit employees', 'delete employees',
            
            // Department management
            'view departments', 'create departments', 'edit departments', 'delete departments',
            
            // Position management
            'view positions', 'create positions', 'edit positions', 'delete positions',
            
            // Payroll management
            'view payrolls', 'create payrolls', 'edit payrolls', 'delete payrolls', 'approve payrolls',
            
            // System Settings
            'manage pay schedules', 'manage deductions', 'manage allowances', 
            'manage leave settings', 'manage holidays', 'manage no work settings',
            
            // Reports
            'view reports', 'export reports',
            
            // DTR management
            'view dtr', 'create dtr', 'edit dtr', 'delete dtr', 'approve dtr',
        ];
        
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        
        // Assign all permissions to admin role
        $adminRole->syncPermissions(Permission::all());
        
        // Create system administrator user (not an employee)
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@payrollsystem.com'],
            [
                'name' => 'System Administrator',
                'password' => bcrypt('admin123'),
                'email_verified_at' => now(),
            ]
        );
        
        // Assign admin role to user
        $adminUser->assignRole($adminRole);
        
        $this->command->info('System Administrator user created successfully!');
        $this->command->info('Email: admin@payrollsystem.com');
        $this->command->info('Password: admin123');
    }
}
