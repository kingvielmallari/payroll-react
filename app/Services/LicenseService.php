<?php

namespace App\Services;

use App\Models\SystemLicense;
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
        if ($license->hasReachedEmployeeLimit()) {
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

        // Note: We don't check expiry during activation since the license starts counting down from activation

        // Check if license key already exists
        if (SystemLicense::where('license_key', $cleanKey)->exists()) {
            return ['success' => false, 'message' => 'This license key has already been activated. Contact your system administrator if you need assistance.'];
        }

        // Deactivate existing licenses
        SystemLicense::where('is_active', true)->update(['is_active' => false]);

        // Create new license with plan information embedded
        $license = SystemLicense::create([
            'license_key' => $cleanKey,
            'server_fingerprint' => self::generateServerFingerprint(),
            'plan_info' => [
                'max_employees' => $decoded['max_employees'] ?? 100,
                'price' => $decoded['price'] ?? 0,
                'duration_days' => $decoded['duration_days'] ?? 30,
                'currency' => $decoded['currency'] ?? 'PHP',
                'customer' => $decoded['customer'] ?? null,
                'features' => $decoded['features'] ?? [],
            ],
            'activated_at' => Carbon::now(),
            'countdown_started_at' => Carbon::now(), // Start countdown immediately
            'expires_at' => Carbon::now()->addDays($decoded['duration_days'] ?? 30),
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

    private static function decodeLicenseKey($licenseKey)
    {
        try {
            $parts = explode('.', $licenseKey);
            if (count($parts) !== 2) {
                return false;
            }

            $payloadEncoded = $parts[0]; // Keep base64 encoded for signature verification
            $signature = $parts[1];

            // Verify signature using the base64-encoded payload (same as generation)
            $secret = config('app.license_secret', config('app.key'));
            $expectedSignature = hash_hmac('sha256', $payloadEncoded, $secret);
            if (!hash_equals($expectedSignature, $signature)) {
                return false;
            }

            // Decode after signature verification
            $payload = base64_decode($payloadEncoded);
            $decoded = json_decode($payload, true);
            if (!$decoded || !isset($decoded['max_employees']) || !isset($decoded['duration_days'])) {
                return false;
            }

            return $decoded;
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function hasFeature($feature)
    {
        $license = SystemLicense::current();

        if (!$license || !$license->plan_info) {
            return false;
        }

        $features = $license->plan_info['features'] ?? [];

        // If features is empty, assume basic features are available
        if (empty($features)) {
            return true;
        }

        return in_array($feature, $features);
    }

    public static function getLicenseInfo($licenseKey)
    {
        $cleaned = preg_replace('/[^A-Za-z0-9+\/=.]/', '', $licenseKey);
        return self::decodeLicenseKey($cleaned);
    }
}
