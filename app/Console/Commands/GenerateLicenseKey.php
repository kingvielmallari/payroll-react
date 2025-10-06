<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;

class GenerateLicenseKey extends Command
{
    protected $signature = 'license:generate 
                           {--employees=100 : Maximum number of employees allowed}
                           {--price=2999 : Price in Philippine Pesos}
                           {--duration=30 : License duration in days}
                           {--customer= : Customer name}';

    protected $description = 'Generate a license key with subscription plan information';

    public function handle()
    {
        $employees = (int) $this->option('employees');
        $price = (float) $this->option('price');
        $duration = (int) $this->option('duration');
        $customer = $this->option('customer');

        // Validate input
        if ($employees <= 0) {
            $this->error('Employee limit must be greater than 0');
            return 1;
        }

        if ($price <= 0) {
            $this->error('Price must be greater than 0');
            return 1;
        }

        if ($duration <= 0) {
            $this->error('Duration must be greater than 0 days');
            return 1;
        }

        // Generate license key with subscription plan information
        $licenseData = [
            'max_employees' => $employees,
            'price' => $price,
            'duration_days' => $duration,
            'currency' => 'PHP',
            'issued_at' => Carbon::now()->timestamp,
            'expires_at' => Carbon::now()->addDays($duration)->timestamp,
            'customer' => $customer,
            'features' => [
                'payroll_management',
                'employee_management',
                'time_tracking',
                'reports',
                'email_notifications'
            ],
            'version' => '2.0'
        ];

        $licenseKey = $this->generateLicenseKey($licenseData);

        // Display results
        $this->info('License Key Generated Successfully!');
        $this->line('');
        $this->line('Customer: ' . ($customer ?: 'N/A'));
        $this->line('Max Employees: ' . $employees);
        $this->line('Price: ₱' . number_format($price, 2));
        $this->line('Duration: ' . $duration . ' days');
        $this->line('Valid Until: ' . Carbon::createFromTimestamp($licenseData['expires_at'])->format('Y-m-d H:i:s'));
        $this->line('');
        $this->line('LICENSE KEY:');
        $this->line('================================================================================');
        $this->info($licenseKey);
        $this->line('================================================================================');

        // Save to file for record keeping
        $this->saveLicenseRecord($licenseKey, $licenseData);

        return 0;
    }

    private function generateLicenseKey(array $data)
    {
        // Encode data
        $payload = base64_encode(json_encode($data));

        // Generate signature
        $secret = config('app.license_secret', config('app.key'));
        $signature = hash_hmac('sha256', $payload, $secret);

        // Combine
        return $payload . '.' . $signature;
    }

    private function saveLicenseRecord($licenseKey, $data)
    {
        $record = [
            'license_key' => $licenseKey,
            'generated_at' => Carbon::now()->toISOString(),
            'data' => $data
        ];

        $filename = 'licenses_' . date('Y-m') . '.json';
        $filepath = storage_path('app/licenses/' . $filename);

        // Ensure directory exists
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        // Load existing records
        $records = [];
        if (file_exists($filepath)) {
            $records = json_decode(file_get_contents($filepath), true) ?: [];
        }

        // Add new record
        $records[] = $record;

        // Save
        file_put_contents($filepath, json_encode($records, JSON_PRETTY_PRINT));

        $this->line("License record saved to: {$filepath}");
    }
}
