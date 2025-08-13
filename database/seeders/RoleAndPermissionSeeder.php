<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Dashboard
            'view dashboard',

            // Employee Management
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',
            'manage employee documents',

            // User Management
            'view users',
            'create users',
            'edit users',
            'delete users',
            'assign roles',

            // Payroll Management
            'view payrolls',
            'create payrolls',
            'edit payrolls',
            'delete payrolls',
            'delete approved payrolls',
            'approve payrolls',
            'process payrolls',
            'generate payslips',
            'send payslips',

            // Time Management
            'view time logs',
            'create time logs',
            'edit time logs',
            'delete time logs',
            'approve time logs',
            'import time logs',
            'view own time logs',
            'edit own time logs',

            // Leave Management
            'view leave requests',
            'create leave requests',
            'edit leave requests',
            'delete leave requests',
            'approve leave requests',
            'view own leave requests',
            'create own leave requests',

            // Deduction Management
            'view deductions',
            'create deductions',
            'edit deductions',
            'delete deductions',

            // Cash Advance Management
            'view cash advances',
            'create cash advances',
            'edit cash advances',
            'delete cash advances',
            'approve cash advances',
            'view own cash advances',
            'create own cash advances',

            // Schedule Management
            'view schedules',
            'create schedules',
            'edit schedules',
            'delete schedules',

            // Holiday Management
            'view holidays',
            'create holidays',
            'edit holidays',
            'delete holidays',

            // Department Management
            'view departments',
            'create departments',
            'edit departments',
            'delete departments',

            // Position Management
            'view positions',
            'create positions',
            'edit positions',
            'delete positions',

            // Reports
            'view reports',
            'export reports',
            'generate reports',
            'view payroll reports',
            'view employee reports',
            'view financial reports',

            // Government Forms
            'view government forms',
            'generate government forms',
            'export government forms',
            'generate bir forms',
            'generate sss forms',
            'generate philhealth forms',
            'generate pagibig forms',

            // Settings
            'view settings',
            'edit settings',

            // Activity History
            'view activity logs',

            // Own Profile
            'view own profile',
            'edit own profile',
            'view own payslips',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions

        // System Admin - Full access
        $systemAdmin = Role::firstOrCreate(['name' => 'System Admin']);
        $systemAdmin->syncPermissions(Permission::all());

        // HR Head - Full access to employees, deductions, holidays, approvals, settings
        $hrHead = Role::firstOrCreate(['name' => 'HR Head']);
        $hrHead->syncPermissions([
            'view dashboard',
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',
            'manage employee documents',
            'view payrolls',
            'create payrolls',
            'edit payrolls',
            'delete payrolls',
            'delete approved payrolls',
            'approve payrolls',
            'process payrolls',
            'generate payslips',
            'send payslips',
            'view time logs',
            'create time logs',
            'edit time logs',
            'approve time logs',
            'delete time logs',
            'import time logs',
            'view leave requests',
            'edit leave requests',
            'approve leave requests',
            'view deductions',
            'create deductions',
            'edit deductions',
            'delete deductions',
            'view cash advances',
            'create cash advances',
            'edit cash advances',
            'delete cash advances',
            'approve cash advances',
            'view schedules',
            'create schedules',
            'edit schedules',
            'delete schedules',
            'view holidays',
            'create holidays',
            'edit holidays',
            'delete holidays',
            'view departments',
            'create departments',
            'edit departments',
            'delete departments',
            'view positions',
            'create positions',
            'edit positions',
            'delete positions',
            'view reports',
            'export reports',
            'generate reports',
            'view payroll reports',
            'view employee reports',
            'view financial reports',
            'view government forms',
            'generate government forms',
            'export government forms',
            'generate bir forms',
            'generate sss forms',
            'generate philhealth forms',
            'generate pagibig forms',
            'view settings',
            'edit settings',
            'view activity logs',
            'view own profile',
            'edit own profile',
            'view own payslips',
        ]);

        // HR Staff - Can create/send payroll and import time logs
        $hrStaff = Role::firstOrCreate(['name' => 'HR Staff']);
        $hrStaff->syncPermissions([
            'view dashboard',
            'view employees',
            'create employees',
            'edit employees',
            'manage employee documents',
            'view payrolls',
            'create payrolls',
            'edit payrolls',
            'process payrolls',
            'generate payslips',
            'send payslips',
            'view time logs',
            'create time logs',
            'edit time logs',
            'import time logs',
            'view leave requests',
            'view deductions',
            'view cash advances',
            'create cash advances',
            'edit cash advances',
            'delete cash advances',
            'view schedules',
            'view holidays',
            'view departments',
            'view positions',
            'view reports',
            'export reports',
            'view payroll reports',
            'view employee reports',
            'view own profile',
            'edit own profile',
            'view own payslips',
        ]);

        // Employee - Can view DTR, payslip, request leave
        $employee = Role::firstOrCreate(['name' => 'Employee']);
        $employee->syncPermissions([
            'view dashboard',
            'view own time logs',
            'edit own time logs',
            'view own leave requests',
            'create own leave requests',
            'view own cash advances',
            'create own cash advances',
            'view own profile',
            'edit own profile',
            'view own payslips',
        ]);

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Created roles: System Admin, HR Head, HR Staff, Employee');
        $this->command->info('Total permissions: ' . count($permissions));
    }
}
