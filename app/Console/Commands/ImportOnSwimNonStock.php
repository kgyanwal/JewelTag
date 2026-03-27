<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

class ImportOnSwimNonStock extends Command
{
    protected $signature = 'import:onswim-nonstock {tenant} {--dry-run} {--rollback}';
    protected $description = 'Import Non-Stock items (Grills, Repairs, Custom Labor) from OnSwim. Only creates SaleItems — never touches Sales, Payments, or Laybuys.';

    // ── ACTUAL CSV FORMAT FROM ONSWIM ─────────────────────────────────────────
    // Job No. | Item | Description | Cost Price | Sale Price | Sold Price | Discount | Gross Profit | GP Margin
    // "7502"  | "10k"| "bottom 6 grills" | "$266.67" | "$720.00" | "$720.00" | "-" | "$453.33" | "62.96%"
    // NOTE: Rows where Sold Price = $0.00 AND Sale Price = $0.00 are estimates — skip them

    private int   $imported     = 0;
    private int   $skipped      = 0;
    private int   $notFound     = 0;
    private array $notFoundList = [];
    private array $skippedList  = [];

    public function handle(): int
    {
        $tenantId   = $this->argument('tenant');
        $isDryRun   = $this->option('dry-run');
        $isRollback = $this->option('rollback');

        // ── TENANT INIT ───────────────────────────────────────────────────────
        try {
            tenancy()->initialize(\App\Models\Tenant::findOrFail($tenantId));
        } catch (\Exception $e) {
            $this->error("Could not initialize tenant: {$tenantId}");
            return 1;
        }

        // ── ROLLBACK ──────────────────────────────────────────────────────────
        // SAFE: only deletes rows tagged import_source = 'onswim_nonstock'
        // Will NEVER touch manually-created SaleItems, Sales, Payments, or Laybuys
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
            $this->info("✅ All other Sales, Payments and Laybuys untouched.");
            return 0;
        }

        // ── FILE CHECK ────────────────────────────────────────────────────────
        $directoryPath = storage_path("{$tenantId}/data");
        $filePath = "{$directoryPath}/non_stock_sales_lxd26mar.csv";


        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            $this->line("Place your CSV at: <comment>{$filePath}</comment>");
            return 1;
        }

        // ── READ CSV ──────────────────────────────────────────────────────────
        $file    = fopen($filePath, 'r');
        $headers = array_map('trim', fgetcsv($file));

        $this->line("Columns detected: " . implode(', ', $headers));

        // Validate required columns match OnSwim format
        $required = ['Job No.', 'Description', 'Sold Price'];
        $missing  = array_diff($required, $headers);
        if (!empty($missing)) {
            $this->error("CSV missing required columns: " . implode(', ', $missing));
            $this->line("Your CSV has: " . implode(', ', $headers));
            fclose($file);
            return 1;
        }

        // Read all valid rows
        $rows = [];
        while (($row = fgetcsv($file)) !== false) {
            if (count($row) !== count($headers)) continue;
            $data  = array_combine($headers, $row);
            $jobNo = trim($data['Job No.'] ?? '');
            if (empty($jobNo)) continue;
            $rows[] = $data;
        }
        fclose($file);

        $total = count($rows);
        $this->newLine();
        $this->info($isDryRun
            ? "🔍 DRY RUN — No data will be written. Processing {$total} rows..."
            : "📥 Importing {$total} non-stock rows for tenant [{$tenantId}]..."
        );
        $this->line("⚡ This command ONLY creates SaleItems. It does NOT modify Sales, Payments, or Laybuys.");
        $this->newLine();

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        if (!$isDryRun) {
            DB::beginTransaction();
        }

        try {
            foreach ($rows as $data) {
                $jobNo       = trim($data['Job No.']);
                $itemType    = trim($data['Item'] ?? 'NON-TAG');       // e.g. "10k", "remake", "Repair", "Custom"
                $description = trim($data['Description'] ?? 'Service');
                $costPrice   = $this->cleanMoney($data['Cost Price'] ?? '0');
                $salePrice   = $this->cleanMoney($data['Sale Price'] ?? '0');
                $soldPrice   = $this->cleanMoney($data['Sold Price'] ?? '0');
                $discount    = $this->cleanMoney($data['Discount'] ?? '0');

                // ── SKIP ZERO-VALUE ESTIMATE ROWS ─────────────────────────────
                // Rows like: "7747 | Estimate | Prices for both rings are Estimate only | $0.00 | $0.00 | $0.00"
                // These are placeholders/notes, not real billable items
                if ($soldPrice == 0.00 && $salePrice == 0.00) {
                    $reason = "ZERO VALUE (Estimate/Placeholder)";
                    $this->skippedList[] = "Job #{$jobNo} | {$itemType} | {$description} → {$reason}";
                    $this->skipped++;
                    $bar->advance();
                    continue;
                }

                // ── FIND SALE ─────────────────────────────────────────────────
                // Try: exact → D-prefix → D-prefix stripped leading zeros
                $sale = Sale::where('invoice_number', $jobNo)->first()
                    ?? Sale::where('invoice_number', 'D' . $jobNo)->first()
                    ?? Sale::where('invoice_number', 'D' . ltrim($jobNo, '0'))->first();

                if (!$sale) {
                    $this->notFoundList[] = "Job #{$jobNo} | {$itemType} | {$description}";
                    $this->notFound++;
                    $bar->advance();
                    continue;
                }

                // ── IDEMPOTENCY CHECK ─────────────────────────────────────────
                // Checks: same sale + same import source + same description + same sold price
                // This means: same item re-imported = skipped safely
                // But: same description at different price = new row (e.g. two ring sizings at diff prices)
                $alreadyExists = SaleItem::where('sale_id', $sale->id)
                    ->where('import_source', 'onswim_nonstock')
                    ->where('custom_description', $description)
                    ->where('sold_price', $soldPrice)
                    ->exists();

                if ($alreadyExists) {
                    $this->skippedList[] = "Job #{$jobNo} | {$description} → ALREADY IMPORTED";
                    $this->skipped++;
                    $bar->advance();
                    continue;
                }

                if ($isDryRun) {
                    $this->line(
                        "\n  [CREATE] Job #{$jobNo} → Sale [{$sale->invoice_number}]" .
                        " | Item: {$itemType}" .
                        " | Desc: " . \Illuminate\Support\Str::limit($description, 50) .
                        " | Cost: \${$costPrice}" .
                        " | Sale: \${$salePrice}" .
                        " | Sold: \${$soldPrice}" .
                        ($discount > 0 ? " | Disc: \${$discount}" : "")
                    );
                } else {
                    SaleItem::create([
                        'sale_id'             => $sale->id,
                        'product_item_id'     => null,
                        'repair_id'           => null,
                        'custom_order_id'     => null,
                        'is_non_stock'        => true,
                        'import_source'       => 'onswim_nonstock',   // ← tag for safe rollback
                        'stock_no_display'    => strtoupper($itemType), // "10K", "REMAKE", "REPAIR", "CUSTOM"
                        'custom_description'  => $description,
                        'job_description'     => $description,
                        'qty'                 => 1,                   // OnSwim non-stock always qty 1
                        'cost_price'          => $costPrice,
                        'sold_price'          => $soldPrice,
                        'sale_price_override' => $soldPrice,          // no qty multiplication needed
                        'discount_percent'    => 0,
                        'discount_amount'     => $discount,           // store raw discount amount
                        'discount'            => $discount,
                        'is_tax_free'         => false,
                        'is_manual'           => true,
                        'store_id'            => $sale->store_id,
                    ]);
                }

                $this->imported++;
                $bar->advance();
            }

            if (!$isDryRun) {
                DB::commit();
            }

        } catch (\Exception $e) {
            if (!$isDryRun) DB::rollBack();
            $bar->finish();
            $this->newLine();
            $this->error("Import failed: " . $e->getMessage());
            $this->line($e->getTraceAsString());
            return 1;
        }

        $bar->finish();
        $this->newLine(2);

        // ── SUMMARY ───────────────────────────────────────────────────────────
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Rows in CSV',                          $total],
                [$isDryRun ? 'Would Import' : 'Imported',     $this->imported],
                ['Skipped (zero value or already imported)',   $this->skipped],
                ['Invoice Not Found',                          $this->notFound],
            ]
        );

        // ── SKIPPED LIST ──────────────────────────────────────────────────────
        if (!empty($this->skippedList)) {
            $this->newLine();
            $this->warn("⏭️  Skipped rows:");
            foreach ($this->skippedList as $s) {
                $this->line("  · {$s}");
            }
        }

        // ── NOT FOUND LIST ────────────────────────────────────────────────────
        if (!empty($this->notFoundList)) {
            $this->newLine();
            $this->error("❌ No matching Sale found for these rows:");
            foreach ($this->notFoundList as $nf) {
                $this->line("  · {$nf}");
            }
            $this->line("  Tip: These job numbers don't exist in your Sales table as-is, D-prefixed, or zero-stripped.");
        }

        $this->newLine();
        if ($isDryRun) {
            $this->info("✅ Dry run complete — no data written.");
            $this->info("   Run without --dry-run to actually import.");
        } else {
            $this->info("✅ Import complete!");
            $this->info("   To undo: php artisan import:onswim-nonstock {$tenantId} --rollback");
        }

        return 0;
    }

    // ── HELPERS ───────────────────────────────────────────────────────────────

    private function cleanMoney($val): float
    {
        // Handles: "$1,234.56", "1234.56", "-", "", "$0.00", "25.00"
        // The "-" in OnSwim CSVs means zero/no value — NOT a negative number
        $clean = str_replace(['$', ',', ' '], '', trim($val ?? '0'));
        return ($clean === '-' || $clean === '' || !is_numeric($clean)) ? 0.00 : (float) $clean;
    }
}