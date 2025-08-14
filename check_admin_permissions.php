<?php

require_once 'vendor/autoload.php';
require_once 'bootstrap/app.php';

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

// Check roles
$roles = Role::whereIn('name', ['System Administrator', 'System Admin'])->get();
echo "Available System Admin roles:\n";
foreach ($roles as $role) {
    echo "- {$role->name} (ID: {$role->id})\n";
    echo "  Permissions: " . $role->permissions->pluck('name')->join(', ') . "\n";
}

// Check permission
$permission = Permission::where('name', 'delete approved payrolls')->first();
echo "\nDelete approved payrolls permission: " . ($permission ? 'EXISTS' : 'NOT FOUND') . "\n";

if ($permission) {
    echo "Permission ID: {$permission->id}\n";

    // Assign to all system admin roles
    foreach ($roles as $role) {
        if (!$role->hasPermissionTo($permission)) {
            $role->givePermissionTo($permission);
            echo "Added 'delete approved payrolls' permission to {$role->name}\n";
        } else {
            echo "{$role->name} already has 'delete approved payrolls' permission\n";
        }
    }
}
