<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class SyncCrmData extends Command
{
    // The name and signature of the console command.
    protected $signature = 'crm:sync {--tenant= : The ID of the tenant}';

    // The console command description.
    protected $description = 'Sync newly updated sales and customers to the external CRM';

    public function handle()
    {
        $tenantId = $this->option('tenant');
        
        if (!$tenantId) {
            $this->error('You must specify a tenant ID (e.g., --tenant=lxd)');
            return Command::FAILURE;
        }

        // 1. Initialize the Tenant Environment
        // (Assuming you are using stancl/tenancy)
        tenancy()->initialize($tenantId);
        
        $this->info("Starting CRM sync for tenant: {$tenantId}");

        // 2. Determine the "Updated Since" timestamp
        // We store the last successful sync time in the tenant's site_settings table
        $lastSyncStr = DB::table('site_settings')->where('key', 'crm_last_sync')->value('value');
        
        // If we've never synced before, default to 1 hour ago to catch recent stuff
        $lastSyncUtc = $lastSyncStr ? Carbon::parse($lastSyncStr) : now()->subHour()->utc();
        
        $this->info("Fetching records updated since: " . $lastSyncUtc->toIso8601String());

        // 3. Fetch the updated records
        $sales = Sale::with(['items.productItem', 'payments', 'customer'])
            ->where('updated_at', '>=', $lastSyncUtc)
            ->get();

        $customers = Customer::where('updated_at', '>=', $lastSyncUtc)->get();

        if ($sales->isEmpty() && $customers->isEmpty()) {
            $this->info("No new updates found.");
            return Command::SUCCESS;
        }

        $this->info("Found {$sales->count()} sales and {$customers->count()} customers to sync.");

        // 4. Format the payload for your specific CRM
        $payload = [
            'store_id'      => $tenantId,
            'sync_datetime' => now()->utc()->toIso8601String(),
            'data'          => [
                'sales'     => $sales,
                'customers' => $customers,
            ]
        ];

        // 5. Send the data to your CRM
        try {
            // TODO: Replace this with your actual CRM API endpoint and logic
            // Example for a generic webhook/REST API:
            /*
            $response = Http::withToken(config('services.crm.token'))
                ->post(config('services.crm.endpoint'), $payload);

            if (!$response->successful()) {
                $this->error("CRM API rejected the payload: " . $response->body());
                return Command::FAILURE;
            }
            */
            
            $this->info("Simulated Push: Payload sent to CRM successfully.");

            // 6. Record the successful sync time so we don't send duplicates next time
            DB::table('site_settings')->updateOrInsert(
                ['key' => 'crm_last_sync'],
                ['value' => now()->utc()->toIso8601String(), 'updated_at' => now()]
            );

        } catch (\Exception $e) {
            $this->error("Failed to connect to CRM: " . $e->getMessage());
            return Command::FAILURE;
        }

        $this->info("Sync complete!");
        return Command::SUCCESS;
    }
}