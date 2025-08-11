<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Payroll;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

echo "🔧 Fixing System Admin permissions...\n";

// Update System Admin to have all permissions
$systemAdmin = Role::where('name', 'System Admin')->first();
if ($systemAdmin) {
    $systemAdmin->syncPermissions(Permission::all());
    echo "✅ System Admin role updated with all permissions!\n";
}

echo "\n🧪 Testing Payroll Deletion Permissions\n";
echo "=======================================\n\n";

// Get users with their roles
$users = User::with('roles')->get();

echo "👥 Users and their roles:\n";
foreach ($users as $user) {
    $roles = $user->roles->pluck('name')->join(', ');
    echo "  • {$user->name} - {$roles}\n";
}

echo "\n📋 Payroll statuses:\n";
$payrollCounts = [
    'draft' => Payroll::where('status', 'draft')->count(),
    'processing' => Payroll::where('status', 'processing')->count(),
    'approved' => Payroll::where('status', 'approved')->count(),
];

foreach ($payrollCounts as $status => $count) {
    echo "  • {$status}: {$count} payrolls\n";
}

echo "\n🔑 Permission checks:\n";

// Check permissions for each user
foreach ($users as $user) {
    echo "\n👤 {$user->name}:\n";
    echo "  • Can delete payrolls: " . ($user->can('delete payrolls') ? 'Yes' : 'No') . "\n";
    echo "  • Can delete approved payrolls: " . ($user->can('delete approved payrolls') ? 'Yes' : 'No') . "\n";
}

echo "\n✅ Test completed!\n";
