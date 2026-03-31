<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use App\Models\Tenant;

class BackupAllTenants extends Command
{
    // Keeping your exact signature so your console.php schedules keep working!
    protected $signature = 'backup:tenants {--tenant= : Backup a specific tenant by ID}';
    protected $description = 'Export tenant databases to CSV and upload to S3';

    public function handle()
    {
        $tenantId = $this->option('tenant');

        if ($tenantId) {
            $tenants = Tenant::where('id', $tenantId)->get();

            if ($tenants->isEmpty()) {
                $this->error("Tenant '{$tenantId}' not found.");
                return;
            }
        } else {
            $tenants = Tenant::all();
        }

        // 🚀 Add or remove table names here depending on what you want exported
        $tablesToExport = [
            'customers', 
            'sales', 
            'sale_items', 
            'product_items', 
            'repairs', 
            'custom_orders', 
            'payments'
        ];

        foreach ($tenants as $tenant) {
            $this->info("Exporting CSVs for tenant: {$tenant->id}");

            try {
                tenancy()->initialize($tenant);

                foreach ($tablesToExport as $table) {
                    if (!Schema::hasTable($table)) continue;

                    $this->line(" - Exporting {$table}...");

                    // 1. Create a temporary local CSV file
                    $tempPath = storage_path("app/private/{$tenant->id}_{$table}.csv");
                    $file = fopen($tempPath, 'w');

                    // 2. Stream data in chunks so the server doesn't crash on large databases
                    $query = DB::table($table)->orderBy('id');
                    $isFirstRow = true;
                    
                    $query->chunk(1000, function ($records) use ($file, &$isFirstRow) {
                        foreach ($records as $record) {
                            $recordArray = (array) $record;
                            
                            // Write headers on the very first row
                            if ($isFirstRow) {
                                fputcsv($file, array_keys($recordArray));
                                $isFirstRow = false;
                            }
                            
                            // Write the data row
                            fputcsv($file, array_values($recordArray));
                        }
                    });

                    fclose($file);

                    // 3. Upload to S3 in the exact structure: jeweltag/{tenant}/{table}.csv
                    // This overwrites yesterday's file, meaning the CSV is ALWAYS up-to-date with all data.
                    $s3Path = "jeweltag/{$tenant->id}/{$table}.csv";
                    
                    Storage::disk('s3')->put(
                        $s3Path, 
                        file_get_contents($tempPath)
                    );

                    // Clean up the local temp file
                    unlink($tempPath);
                }

                tenancy()->end();
                $this->info("✓ Done: {$tenant->id}");

            } catch (\Exception $e) {
                $this->error("✗ Failed {$tenant->id}: " . $e->getMessage());
                tenancy()->end();
            }
        }
    }
}