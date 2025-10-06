<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SystemLicense;
use Carbon\Carbon;

class ListLicenseKeys extends Command
{
    protected $signature = 'license:list {--active : Show only active licenses}';

    protected $description = 'List all license keys in the system';

    public function handle()
    {
        $activeOnly = $this->option('active');

        $query = SystemLicense::query();

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        $licenses = $query->orderBy('created_at', 'desc')->get();

        if ($licenses->isEmpty()) {
            $this->info('No license keys found.');
            return 0;
        }

        $this->info('License Keys:');
        $this->line('');

        $headers = ['ID', 'Customer', 'Max Employees', 'Price', 'Status', 'Activated At', 'Expires At'];
        $rows = [];

        foreach ($licenses as $license) {
            $planInfo = $license->plan_info ?? [];

            $rows[] = [
                $license->id,
                $planInfo['customer'] ?? 'N/A',
                $planInfo['max_employees'] ?? 'N/A',
                isset($planInfo['price']) ? '₱' . number_format($planInfo['price'], 2) : 'N/A',
                $this->getStatusLabel($license),
                $license->activated_at ? $license->activated_at->format('Y-m-d H:i') : 'Not Activated',
                $license->expires_at ? $license->expires_at->format('Y-m-d H:i') : 'N/A'
            ];
        }

        $this->table($headers, $rows);

        return 0;
    }

    private function getStatusLabel($license)
    {
        if (!$license->is_active) {
            return '<fg=red>Inactive</>';
        }

        if ($license->isExpired()) {
            return '<fg=red>Expired</>';
        }

        return '<fg=green>Active</>';
    }
}
