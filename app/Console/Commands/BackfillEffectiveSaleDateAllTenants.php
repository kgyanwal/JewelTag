<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class BackfillEffectiveSaleDateAllTenants extends Command
{
    protected $signature = 'sales:backfill-effective-date-all {--chunk=500}';
    protected $description = 'Runs the effective_sale_date backfill across every tenant automatically';

    public function handle(): int
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->error('No tenants found.');
            return self::FAILURE;
        }

        $this->info("Found {$tenants->count()} tenant(s). Starting backfill for each...");
        $this->newLine();

        foreach ($tenants as $tenant) {
            $this->info("─────────────────────────────────────");
            $this->info("Tenant: {$tenant->id}");
            $this->info("─────────────────────────────────────");

            try {
                tenancy()->initialize($tenant);

                $this->call('sales:backfill-effective-date', [
                    '--chunk' => $this->option('chunk'),
                ]);
            } catch (\Exception $e) {
                $this->error("Failed for tenant {$tenant->id}: " . $e->getMessage());
            } finally {
                tenancy()->end();
            }

            $this->newLine();
        }

        $this->info('✅ All tenants processed.');
        return self::SUCCESS;
    }
}