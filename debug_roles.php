<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

echo "🔍 Debugging role names...\n";

// Get all roles
$roles = Role::all();
echo "Available roles:\n";
foreach ($roles as $role) {
    echo "  • '{$role->name}'\n";
}

// Find the System Administrator user
$systemAdmin = User::where('name', 'System Administrator')->first();
if ($systemAdmin) {
    echo "\nSystem Administrator user roles:\n";
    foreach ($systemAdmin->roles as $role) {
        echo "  • '{$role->name}'\n";
    }
    
    $roleName = $systemAdmin->roles->first()->name;
    echo "\nUpdating role: '{$roleName}'\n";
    
    $role = Role::where('name', $roleName)->first();
    if ($role) {
        $role->syncPermissions(Permission::all());
        echo "✅ Role '{$roleName}' updated with all permissions!\n";
        
        // Verify the permission
        $systemAdmin->refresh();
        echo "Can delete approved payrolls: " . ($systemAdmin->can('delete approved payrolls') ? 'Yes' : 'No') . "\n";
    }
}
