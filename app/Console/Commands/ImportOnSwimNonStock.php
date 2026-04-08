<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportOnSwimNonStock extends Command
{
    // Added --date option to allow manual override if needed
    protected $signature = 'import:onswim-nonstock {tenant} {--date= : The date of the CSV file Y_m_d} {--dry-run} {--rollback}';
    protected $description = 'Import Non-Stock items (Grills, Repairs, Custom Labor) from OnSwim using aggressive matching.';

    private int   $imported     = 0;
    private int   $skipped      = 0;
    private int   $notFound     = 0;
    private array $notFoundList = [];

    public function handle(): int
    {
        set_time_limit(0);
        ini_set('memory_limit', '1G');

        $tenantId   = $this->argument('tenant');
        $isDryRun   = $this->option('dry-run');
        $isRollback = $this->option('rollback');

        // ── TENANT INIT ───────────────────────────────────────────────────────
        try {
            tenancy()->initialize(Tenant::findOrFail($tenantId));
        } catch (\Exception $e) {
            $this->error("Could not initialize tenant: {$tenantId}");
            return 1;
        }

        // ── ROLLBACK ──────────────────────────────────────────────────────────
        if ($isRollback) {
            $count = SaleItem::where('import_source', 'onswim_nonstock')->count();
            if ($count === 0) {
                $this->info("Nothing to rollback — no onswim_nonstock items found.");
                return 0;
            }
            if (!$this->confirm("⚠️  Delete {$count} SaleItems tagged 'onswim_nonstock' for tenant [{$tenantId}]?")) {
                $this->info("Rollback cancelled.");
                return 0;
            }
            SaleItem::where('import_source', 'onswim_nonstock')->forceDelete();
            $this->warn("Rollback complete. {$count} items removed.");
            return 0;
        }

        // ── DYNAMIC FILE PATH LOGIC (5-DAY LOOKBACK) ──────────────────────────
        $directoryPath = storage_path("/{$tenantId}/data");
        $filesToProcess = [];

        if ($this->option('date')) {
            // If you manually specify a date, only look for that one
            $manualDate = $this->option('date');
            $filesToProcess[] = "non_stock_{$manualDate}.csv";
            $filesToProcess[] = "non_stock_sales_{$manualDate}.csv"; 
        } else {
            // Otherwise, scan the last 5 days to ignore timezones and weekends
            for ($i = 0; $i <= 5; $i++) {
                $dateStr = Carbon::now()->subDays($i)->format('Y_m_d');
                $filesToProcess[] = "non_stock_{$dateStr}.csv";
                $filesToProcess[] = "non_stock_sales_{$dateStr}.csv";
            }
        }

        // Find the first file in the array that actually exists
        $filePath = null;
        $fileName = null;
        foreach ($filesToProcess as $file) {
            if (File::exists("{$directoryPath}/{$file}")) {
                $filePath = "{$directoryPath}/{$file}";
                $fileName = $file;
                break;
            }
        }

        if (!$filePath) {
            $this->error("❌ NO FILES FOUND in {$directoryPath}");
            $this->line("The script searched for 'non_stock_YYYY_MM_DD.csv' over the last 5 days.");
            return 1;
        }

        // ── READ CSV ──────────────────────────────────────────────────────────
        $file    = fopen($filePath, 'r');
        $headers = array_map('trim', fgetcsv($file));

        $required = ['Job No.', 'Description', 'Sold Price'];
        $missing  = array_diff($required, $headers);
        if (!empty($missing)) {
            $this->error("CSV missing required columns: " . implode(', ', $missing));
            fclose($file);
            return 1;
        }

        $rows = [];
        while (($row = fgetcsv($file)) !== false) {
            if (count($row) !== count($headers)) continue;
            $data  = array_combine($headers, $row);
            if (empty(trim($data['Job No.'] ?? ''))) continue;
            $rows[] = $data;
        }
        fclose($file);

        $total = count($rows);
        $this->newLine();
        $this->info($isDryRun
            ? "🔍 DRY RUN — No data will be written. Processing {$total} rows from {$fileName}..."
            : "📥 Importing {$total} rows from {$fileName} for [{$tenantId}]..."
        );

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        if (!$isDryRun) DB::connection('tenant')->beginTransaction();

        try {
            foreach ($rows as $data) {
                $jobNo       = trim($data['Job No.']);
                $itemType    = trim($data['Item'] ?? 'NON-TAG');
                $description = trim($data['Description'] ?? 'Service');
                $costPrice   = $this->cleanMoney($data['Cost Price'] ?? '0');
                $salePrice   = $this->cleanMoney($data['Sale Price'] ?? '0');
                $soldPrice   = $this->cleanMoney($data['Sold Price'] ?? '0');
                $discount    = $this->cleanMoney($data['Discount'] ?? '0');

                if ($soldPrice == 0.00 && $salePrice == 0.00) {
                    $this->skipped++;
                    $bar->advance();
                    continue;
                }

                // 🚀 AGGRESSIVE FUZZY MAPPING LOGIC
                $cleanJobNo = preg_replace('/[^0-9]/', '', $jobNo);
                
                $sale = Sale::where('invoice_number', $cleanJobNo)
                    ->orWhere('invoice_number', 'LIKE', "%-{$cleanJobNo}")
                    ->orWhere('invoice_number', 'LIKE', "{$cleanJobNo}-%")
                    ->orWhere('invoice_number', 'LIKE', "%_{$cleanJobNo}")
                    ->orWhere('invoice_number', 'LIKE', "%{$cleanJobNo}%")
                    ->first();

                if (!$sale) {
                    $this->notFoundList[] = $jobNo;
                    $this->notFound++;
                    $bar->advance();
                    continue;
                }

                // Idempotency (Prevents duplicates)
                $alreadyExists = SaleItem::where('sale_id', $sale->id)
                    ->where('import_source', 'onswim_nonstock')
                    ->where('custom_description', $description)
                    ->where('sold_price', $soldPrice)
                    ->exists();

                if ($alreadyExists) {
                    $this->skipped++;
                    $bar->advance();
                    continue;
                }

                if (!$isDryRun) {
                    SaleItem::withoutEvents(fn() => 
                        SaleItem::create([
                            'sale_id'             => $sale->id,
                            'is_non_stock'        => true,
                            'import_source'       => 'onswim_nonstock',
                            'stock_no_display'    => strtoupper($itemType),
                            'custom_description'  => $description,
                            'qty'                 => 1,
                            'cost_price'          => $costPrice,
                            'sold_price'          => $soldPrice,
                            'sale_price_override' => $soldPrice,
                            'discount_amount'     => $discount,
                            'is_tax_free'         => false,
                            'is_manual'           => true,
                            'store_id'            => $sale->store_id,
                        ])
                    );
                }

                $this->imported++;
                $bar->advance();
            }

            if (!$isDryRun) {
                DB::connection('tenant')->commit();
            } else {
                DB::connection('tenant')->rollBack();
            }

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            $this->error("\nError: " . $e->getMessage());
            return 1;
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(['Metric', 'Count'], [
            ['Total Rows Processed', $total],
            ['Matched & Imported', $this->imported],
            ['Skipped (Dupes/Zeros)', $this->skipped],
            ['Sale Not Found', $this->notFound],
        ]);

        if (!empty($this->notFoundList)) {
            $this->newLine();
            $this->warn("⚠️ No Sale found for Job Numbers: " . implode(', ', array_unique($this->notFoundList)));
            $this->line("Ensure you have run the Sales import for these dates first!");
        }

        return 0;
    }

    private function cleanMoney($val): float
    {
        $clean = str_replace(['$', ',', ' '], '', trim($val ?? '0'));
        return ($clean === '-' || $clean === '' || !is_numeric($clean)) ? 0.00 : (float) $clean;
    }
}