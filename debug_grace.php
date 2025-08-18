<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\GracePeriodSetting;

$gracePeriod = GracePeriodSetting::current();

if ($gracePeriod) {
    echo "Grace Period Settings:\n";
    echo "Late Grace Minutes: " . $gracePeriod->late_grace_minutes . "\n";
    echo "Undertime Grace Minutes: " . $gracePeriod->undertime_grace_minutes . "\n";
    echo "Overtime Threshold Minutes: " . $gracePeriod->overtime_threshold_minutes . "\n";
    echo "Overtime Threshold Hours: " . ($gracePeriod->overtime_threshold_minutes / 60) . "\n";
} else {
    echo "No grace period settings found\n";
}
