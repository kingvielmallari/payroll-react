<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SystemLicense;

class DeleteLicenseKey extends Command
{
    protected $signature = 'license:delete 
                           {id : The license ID to delete}
                           {--force : Force deletion without confirmation}';

    protected $description = 'Delete a license key from the system';

    public function handle()
    {
        $licenseId = $this->argument('id');
        $force = $this->option('force');

        $license = SystemLicense::find($licenseId);

        if (!$license) {
            $this->error("License with ID {$licenseId} not found.");
            return 1;
        }

        $planInfo = $license->plan_info ?? [];

        // Display license information
        $this->info('License Information:');
        $this->line('ID: ' . $license->id);
        $this->line('Customer: ' . ($planInfo['customer'] ?? 'N/A'));
        $this->line('Max Employees: ' . ($planInfo['max_employees'] ?? 'N/A'));
        $this->line('Price: ' . (isset($planInfo['price']) ? '₱' . number_format($planInfo['price'], 2) : 'N/A'));
        $this->line('Status: ' . ($license->is_active ? 'Active' : 'Inactive'));
        $this->line('Activated At: ' . ($license->activated_at ? $license->activated_at->format('Y-m-d H:i:s') : 'Not Activated'));
        $this->line('Expires At: ' . ($license->expires_at ? $license->expires_at->format('Y-m-d H:i:s') : 'N/A'));
        $this->line('');

        // Confirm deletion
        if (!$force && !$this->confirm('Are you sure you want to delete this license?')) {
            $this->info('License deletion cancelled.');
            return 0;
        }

        // Delete the license
        $license->delete();

        $this->info("License with ID {$licenseId} has been deleted successfully.");

        return 0;
    }
}
