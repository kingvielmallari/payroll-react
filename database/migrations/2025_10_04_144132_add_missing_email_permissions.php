<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add missing email payslip permissions
        $permissions = [
            'email payslip',
            'email all payslips',
            'view payslips',
            'download payslips'
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign email permissions to appropriate roles
        $systemAdmin = \Spatie\Permission\Models\Role::where('name', 'System Administrator')->first();
        $hrHead = \Spatie\Permission\Models\Role::where('name', 'HR Head')->first();
        $hrStaff = \Spatie\Permission\Models\Role::where('name', 'HR Staff')->first();

        if ($systemAdmin) {
            $systemAdmin->givePermissionTo($permissions);
        }

        if ($hrHead) {
            $hrHead->givePermissionTo($permissions);
        }

        if ($hrStaff) {
            $hrStaff->givePermissionTo($permissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissions = [
            'email payslip',
            'email all payslips',
            'view payslips',
            'download payslips'
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::where('name', $permission)->delete();
        }
    }
};
