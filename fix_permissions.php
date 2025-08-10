<?php
// Fix System Administrator permissions
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

// Get System Administrator role
$adminRole = Role::where('name', 'System Administrator')->first();

if ($adminRole) {
    // Get all permissions (System Administrator should have all)
    $allPermissions = Permission::all();
    $adminRole->syncPermissions($allPermissions);
    
    echo "System Administrator role updated with ALL permissions (" . $allPermissions->count() . " permissions)\n";
    
    // Verify specific payroll permissions
    echo "Has 'view payrolls': " . ($adminRole->hasPermissionTo('view payrolls') ? 'YES' : 'NO') . "\n";
    echo "Has 'create payrolls': " . ($adminRole->hasPermissionTo('create payrolls') ? 'YES' : 'NO') . "\n";
} else {
    echo "System Administrator role not found\n";
}
