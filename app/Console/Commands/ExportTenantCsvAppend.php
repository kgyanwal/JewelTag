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
        // Allow --date override for testing, defaults to yesterday
        $dateOption = $this->option('date');
        $exportDate = $dateOption ? Carbon::parse($dateOption) : Carbon::yesterday();
        $dateLabel  = $exportDate->format('Y-m-d');

        $this->line("  📅 Exporting date: {$dateLabel}");

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

        // Check if table exists
        if (!DB::getSchemaBuilder()->hasTable($table)) {
            $this->warn("  ⚠ Table {$table} not found, skipping.");
            return;
        }

        // Check if dateColumn exists
        if (!DB::getSchemaBuilder()->hasColumn($table, $dateColumn)) {
            $this->warn("  ⚠ Column {$dateColumn} not found in {$table}, skipping.");
            return;
        }

        // Build query — only apply deleted_at filter if column exists
        $query = DB::table($table)->whereDate($dateColumn, $date->toDateString());

        if (DB::getSchemaBuilder()->hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $rows = $query->get();

        if ($rows->isEmpty()) {
            $this->line("  — No rows in {$table} for {$dateLabel}");
            return;
        }

        $rowCount = $rows->count();
        $rows     = $rows->map(fn($r) => (array) $r)->toArray();
        $headers  = array_keys($rows[0]);

        $csvLines    = [];
        $fileExists  = Storage::disk('s3')->exists($s3Path);

      if ($fileExists) {
    $existing = Storage::disk('s3')->get($s3Path);
    // Check if date appears as a DATA date (created_at), not just export date
    // Look for the date in the actual data columns, not _export_date
    $lines = explode("\n", $existing);
    foreach ($lines as $line) {
        // Skip header and empty lines
        if (empty($line) || str_starts_with($line, 'id,')) continue;
        // The data date appears at position before _export_date
        // Check if line contains dateLabel NOT at the very end (export date position)
        $withoutExportDate = preg_replace('/,' . preg_quote(now()->format('Y-m-d'), '/') . '[^,\n]*$/', '', $line);
        if (str_contains($withoutExportDate, $dateLabel)) {
            $this->line("  ⏭ {$table}: {$dateLabel} already exported, skipping.");
            return;
        }
    }
}

        foreach ($rows as $row) {
            $row['_export_date'] = now()->toDateTimeString();
            $csvLines[] = $this->toCsvLine(array_values($row));
        }

        $csvContent = implode("\n", $csvLines) . "\n";

        if ($fileExists) {
            $existing   = Storage::disk('s3')->get($s3Path);
            $csvContent = $existing . $csvContent;
        }

        Storage::disk('s3')->put($s3Path, $csvContent);

        $this->line("  ✓ {$table}: {$rowCount} rows → s3://jeweltag/{$tenantId}/{$table}.csv");
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