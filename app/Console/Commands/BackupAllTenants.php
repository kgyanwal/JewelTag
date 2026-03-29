<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackupAllTenants extends Command
{
    protected $signature = 'backup:tenants {--tenant= : Backup a specific tenant by ID}';
    protected $description = 'Run database backup for all tenants (or a specific one)';

    public function handle()
    {
        $tenantId = $this->option('tenant');

        if ($tenantId) {
            $tenants = \App\Models\Tenant::where('id', $tenantId)->get();

            if ($tenants->isEmpty()) {
                $this->error("Tenant '{$tenantId}' not found.");
                return;
            }
        } else {
            $tenants = \App\Models\Tenant::all();
        }

        foreach ($tenants as $tenant) {
            $this->info("Backing up tenant: {$tenant->id}");

            try {
                tenancy()->initialize($tenant);

                config([
                    'backup.backup.name' => $tenant->id,
                    'backup.backup.destination.filename_prefix' => 'backup-',
                ]);

                $this->call('backup:run', ['--only-db' => true]);

                tenancy()->end();
                $this->info("✓ Done: {$tenant->id}");

            } catch (\Exception $e) {
                $this->error("✗ Failed {$tenant->id}: " . $e->getMessage());
                tenancy()->end();
            }
        }
    }
}