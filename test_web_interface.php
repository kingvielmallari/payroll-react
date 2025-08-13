<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Bootstrap Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a fake request to test if routes are loaded
$request = Request::create('/admin/payroll-rate-configurations', 'GET');

try {
    // Test if the route exists
    $route = app('router')->getRoutes()->match($request);
    echo "✅ Rate configuration routes are properly registered!\n";
    echo "Route Name: " . $route->getName() . "\n";
    echo "Route Action: " . $route->getActionName() . "\n";
} catch (Exception $e) {
    echo "❌ Route not found: " . $e->getMessage() . "\n";
}

// Check if TimeLog model has dynamic types available
echo "\n📋 Testing TimeLog dynamic types:\n";
try {
    $logTypes = \App\Models\TimeLog::getAvailableLogTypes();
    foreach ($logTypes as $key => $label) {
        echo "  • $key: $label\n";
    }
    echo "\n✅ Dynamic log types are working!\n";
} catch (Exception $e) {
    echo "❌ Error getting log types: " . $e->getMessage() . "\n";
}

// Test rate configuration count
echo "\n💰 Testing Rate Configurations:\n";
try {
    $configs = \App\Models\PayrollRateConfiguration::active()->count();
    echo "  Found $configs active rate configurations\n";
    echo "✅ Rate configurations are accessible!\n";
} catch (Exception $e) {
    echo "❌ Error accessing rate configurations: " . $e->getMessage() . "\n";
}

echo "\n🎉 Web interface test completed!\n";
