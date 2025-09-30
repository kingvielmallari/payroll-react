<?php

namespace App\Services;

use App\Models\SystemLicense;
use App\Models\SubscriptionPlan;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LicenseService
{
    public static function generateServerFingerprint()
    {
        $data = [
            'server_name' => gethostname(),
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? '',
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? '',
            'php_version' => PHP_VERSION,
        ];

        return hash('sha256', serialize($data));
    }

    public static function validateLicense()
    {
        $license = SystemLicense::current();

        if (!$license) {
            return ['valid' => false, 'reason' => 'No active license found'];
        }

        if ($license->isExpired()) {
            return ['valid' => false, 'reason' => 'License expired'];
        }

        // Check server fingerprint (can be disabled for development)
        if (config('app.env') === 'production') {
            if ($license->server_fingerprint !== self::generateServerFingerprint()) {
                return ['valid' => false, 'reason' => 'License not valid for this server'];
            }
        }

        // Check employee limit
        $employeeCount = Employee::count();
        if (
            $license->subscriptionPlan->max_employees != -1 &&
            $employeeCount > $license->subscriptionPlan->max_employees
        ) {
            return ['valid' => false, 'reason' => 'Employee limit exceeded'];
        }

        return ['valid' => true, 'license' => $license];
    }

    public static function activateLicense($licenseKey)
    {
        // Remove formatting (dashes, spaces)
        $cleanKey = preg_replace('/[^A-Za-z0-9+\/=.]/', '', $licenseKey);

        // Decode and validate license key
        $decoded = self::decodeLicenseKey($cleanKey);

        if (!$decoded) {
            return ['success' => false, 'message' => 'Invalid license key format'];
        }

        // Check expiry
        if ($decoded['expires_at'] < time()) {
            return ['success' => false, 'message' => 'License key has expired'];
        }

        // Check if license key already exists
        if (SystemLicense::where('license_key', $cleanKey)->exists()) {
            return ['success' => false, 'message' => 'License key already activated'];
        }

        // Find the plan
        $plan = SubscriptionPlan::find($decoded['plan_id']);
        if (!$plan) {
            return ['success' => false, 'message' => 'Invalid subscription plan'];
        }

        // Deactivate existing licenses
        SystemLicense::where('is_active', true)->update(['is_active' => false]);

        // Create new license
        $license = SystemLicense::create([
            'license_key' => $cleanKey,
            'server_fingerprint' => self::generateServerFingerprint(),
            'subscription_plan_id' => $decoded['plan_id'],
            'activated_at' => Carbon::now(),
            'expires_at' => Carbon::createFromTimestamp($decoded['expires_at']),
            'is_active' => true,
            'system_info' => [
                'activated_by' => Auth::check() ? Auth::user()->email : 'system',
                'server_info' => $_SERVER['HTTP_HOST'] ?? 'localhost',
                'license_data' => $decoded,
                'activation_ip' => request()->ip(),
            ]
        ]);

        return ['success' => true, 'license' => $license, 'data' => $decoded];
    }

    public static function decodeLicenseKey($licenseKey)
    {
        try {
            $parts = explode('.', $licenseKey);
            if (count($parts) !== 2) return false;

            [$payload, $signature] = $parts;

            // Verify signature
            $secret = config('app.license_secret', config('app.key'));
            $expectedSignature = hash_hmac('sha256', $payload, $secret);

            if (!hash_equals($expectedSignature, $signature)) {
                return false;
            }

            $decoded = json_decode(base64_decode($payload), true);

            // Validate required fields
            $required = ['plan_id', 'expires_at', 'issued_at'];
            foreach ($required as $field) {
                if (!isset($decoded[$field])) {
                    return false;
                }
            }

            return $decoded;
        } catch (\Exception $e) {
            Log::error('License decode error: ' . $e->getMessage());
            return false;
        }
    }

    public static function hasFeature($feature)
    {
        $license = SystemLicense::current();
        return $license && $license->subscriptionPlan->hasFeature($feature);
    }

    public static function getLicenseInfo($licenseKey)
    {
        $cleaned = preg_replace('/[^A-Za-z0-9+\/=.]/', '', $licenseKey);
        return self::decodeLicenseKey($cleaned);
    }
}
