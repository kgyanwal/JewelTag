<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ExportTenantCsvAppend extends Command
{
    protected $signature   = 'export:tenant-csv {--tenant= : Specific tenant ID, or all if omitted} {--date= : Export specific date Y-m-d, defaults to yesterday}';
    protected $description = 'Append daily sales/payments data to each tenant\'s CSV on S3';

    // Tables to export and their date column
  protected array $tables = [
    'sales'         => 'created_at',
    'sale_items'    => 'created_at',
    'payments'      => 'paid_at',
    'customers'     => 'created_at',
    'repairs'       => 'created_at',
    'custom_orders' => 'created_at',
    'laybuys'       => 'created_at',
    'product_items' => 'created_at',
    'users'         => 'created_at',
];

    public function handle(): void
    {
        $tenantId = $this->option('tenant');

        $tenants = $tenantId
            ? \App\Models\Tenant::where('id', $tenantId)->get()
            : \App\Models\Tenant::all();

        if ($tenants->isEmpty()) {
            $this->error("No tenants found.");
            return;
        }

        foreach ($tenants as $tenant) {
            $this->info("📦 Exporting tenant: {$tenant->id}");

            try {
                tenancy()->initialize($tenant);
                $this->exportTenant($tenant->id);
                tenancy()->end();
                $this->info("✅ Done: {$tenant->id}");
            } catch (\Exception $e) {
                $this->error("❌ Failed {$tenant->id}: " . $e->getMessage());
                tenancy()->end();
            }
        }
    }

  protected function exportTenant(string $tenantId): void
    {
        // 1. Fetch this specific tenant's AWS settings from their database
        $settings = DB::table('site_settings')
            ->whereIn('key', ['aws_access_key_id', 'aws_secret_access_key', 'aws_default_region', 'aws_bucket'])
            ->pluck('value', 'key');

        $key    = $settings['aws_access_key_id'] ?? null;
        $secret = $settings['aws_secret_access_key'] ?? null;
        $region = $settings['aws_default_region'] ?? 'us-east-1';
        $bucket = $settings['aws_bucket'] ?? null;

        // 2. Safety Check: If they haven't filled out their settings, skip them nicely
        if (empty($key) || empty($secret) || empty($bucket)) {
            $this->warn("  ⚠ Missing AWS credentials or bucket in Settings for tenant {$tenantId}. Skipping.");
            return;
        }

        // 3. 🚀 Dynamically configure Laravel's S3 disk for this specific tenant
        config([
            'filesystems.disks.s3.key'    => $key,
            'filesystems.disks.s3.secret' => $secret,
            'filesystems.disks.s3.region' => $region,
            'filesystems.disks.s3.bucket' => $bucket,
        ]);

        // 4. CRITICAL: Clear Laravel's cached disk instance so it uses the new credentials
        Storage::forgetDisk('s3');

        $dateOption = $this->option('date');
        $exportDate = $dateOption ? Carbon::parse($dateOption) : Carbon::yesterday();
        $dateLabel  = $exportDate->format('Y-m-d');

        $this->line("  📅 Exporting date: {$dateLabel} to bucket: {$bucket}");

        foreach ($this->tables as $table => $dateColumn) {
            $this->exportTable($tenantId, $table, $dateColumn, $exportDate, $dateLabel);
        }
    }

   protected function exportTable(
        string $tenantId,
        string $table,
        string $dateColumn,
        Carbon $date,
        string $dateLabel
    ): void {
        $s3Path = "jeweltag/{$tenantId}/{$table}.csv";

        // 1. Check if table exists
        if (!DB::getSchemaBuilder()->hasTable($table)) {
            $this->warn("  ⚠ Table {$table} not found, skipping.");
            return;
        }

        // 2. Get All Columns from Schema (Guarantees created_at/updated_at/etc.)
        $columns = DB::getSchemaBuilder()->getColumnListing($table);
        
        if (!in_array($dateColumn, $columns)) {
            $this->warn("  ⚠ Column {$dateColumn} not found in {$table}, skipping.");
            return;
        }

        // 3. Query for specific day
        $query = DB::table($table)->whereDate($dateColumn, $date->toDateString());
        if (in_array('deleted_at', $columns)) {
            $query->whereNull('deleted_at');
        }

        $rows = $query->get();

        if ($rows->isEmpty()) {
            $this->line("  — No rows in {$table} for {$dateLabel}");
            return;
        }

        // 4. Load existing content or initialize with Headers
        $fileExists = Storage::disk('s3')->exists($s3Path);
        $existingContent = $fileExists ? Storage::disk('s3')->get($s3Path) : '';

       // Build the expected header line
$headerRow     = $columns;
$headerRow[]   = '_export_date';
$expectedHeader = $this->toCsvLine($headerRow);

if (!$fileExists || empty(trim($existingContent))) {
    // New file or completely empty — initialize with headers
    $existingContent = $expectedHeader . "\n";
} elseif (!str_starts_with(trim($existingContent), $expectedHeader)) {
    // File exists but is missing the header (corrupted/truncated) — prepend it
    $existingContent = $expectedHeader . "\n" . ltrim($existingContent);
    $this->warn("  ⚠ {$table}: Header was missing — prepended to existing content.");
}

        // 5. Build CSV lines for NEW rows only
        $newCsvLines = [];
        $skippedCount = 0;

        foreach ($rows as $row) {
            // Check if this specific record ID already exists in the CSV to prevent duplicates
            // We search for ",ID_NUMBER," to be precise
            if ($fileExists && str_contains($existingContent, ',' . $row->id . ',')) {
                $skippedCount++;
                continue;
            }

            $dataRow = [];
            foreach ($columns as $col) {
                // Ensure we handle the object correctly (DB::table returns objects)
                $dataRow[] = $row->{$col} ?? '';
            }
            $dataRow[] = now()->toDateTimeString(); // _export_date
            $newCsvLines[] = $this->toCsvLine($dataRow);
        }

        if (empty($newCsvLines)) {
            $this->line("  ⏭ {$table}: All {$rows->count()} rows already exist in S3. Skipping.");
            return;
        }

        // 6. Append new lines to existing content and upload
        $updatedContent = rtrim($existingContent) . "\n" . implode("\n", $newCsvLines) . "\n";
        Storage::disk('s3')->put($s3Path, $updatedContent);

        $this->line("  ✓ {$table}: Added " . count($newCsvLines) . " new rows (Skipped {$skippedCount} duplicates) → S3");
    }

    protected function toCsvLine(array $values): string
    {
        return implode(',', array_map(function ($value) {
            if ($value === null) return '';
            $value = (string) $value;
            $value = str_replace('"', '""', $value);
            if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
                return '"' . $value . '"';
            }
            return $value;
        }, $values));
    }
}