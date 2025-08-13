<?php

require_once 'vendor/autoload.php';

// Initialize Laravel application context
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Initializing Default Rate Configurations\n";
echo "=======================================\n\n";

try {
    App\Models\PayrollRateConfiguration::createDefaults();

    echo "✅ Default rate configurations created successfully!\n\n";

    $configs = App\Models\PayrollRateConfiguration::ordered()->get();

    echo "📋 Rate Configurations:\n";
    foreach ($configs as $config) {
        echo "  • {$config->display_name}\n";
        echo "    Regular: {$config->regular_rate_multiplier}x | OT: {$config->overtime_rate_multiplier}x\n";
        echo "    Status: " . ($config->is_active ? 'Active' : 'Inactive') . "\n\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
