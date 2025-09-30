<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;

class GenerateLicenseKey extends Command
{
    protected $signature = 'license:generate 
                           {plan : The subscription plan slug}
                           {--months=12 : License duration in months}
                           {--customer= : Customer name}
                           {--employees= : Max employees override}';

    protected $description = 'Generate a license key for a subscription plan';

    public function handle()
    {
        $planSlug = $this->argument('plan');
        $months = (int) $this->option('months');
        $customer = $this->option('customer');
        $employeesOverride = $this->option('employees') ? (int) $this->option('employees') : null;

        // Find the plan
        $plan = SubscriptionPlan::where('slug', $planSlug)->first();

        if (!$plan) {
            $this->error("Plan '{$planSlug}' not found!");
            $this->info("Available plans:");
            SubscriptionPlan::where('is_active', true)->each(function ($p) {
                $this->line("  - {$p->slug} ({$p->name})");
            });
            return 1;
        }

        // Generate license key
        $licenseData = [
            'plan_id' => $plan->id,
            'plan_slug' => $plan->slug,
            'max_employees' => $employeesOverride ?: $plan->max_employees,
            'features' => $plan->features,
            'issued_at' => Carbon::now()->timestamp,
            'expires_at' => Carbon::now()->addMonths($months)->timestamp,
            'customer' => $customer,
            'version' => '1.0'
        ];

        $licenseKey = $this->generateLicenseKey($licenseData);

        // Display results
        $this->info('License Key Generated Successfully!');
        $this->line('');
        $this->line('Customer: ' . ($customer ?: 'N/A'));
        $this->line('Plan: ' . $plan->name);
        $this->line('Max Employees: ' . ($employeesOverride ?: $plan->max_employees));
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
