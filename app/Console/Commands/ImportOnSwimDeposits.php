<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\Laybuy;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * ImportOnSwimDeposits
 *
 * WHAT THIS DOES (simply):
 *   Reads deposit_sales.csv → finds matching Sale by Job No. → creates Laybuy record
 *
 * WHAT IT DOES NOT DO:
 *   - Does NOT create new items (items already in sale_items from import:onswim-sales)
 *   - Does NOT create payment records (payments already in payments table from POS)
 *   - Does NOT change any sale data except amount_paid and balance_due
 *
 * CSV columns:
 *   Customer | Date Sold | Job No. | Last Date Paid | Invoice Total |
 *   Payment Total | Balance | Staff | Date Required
 *
 * EXAMPLE:
 *   Remy Heritier | 3/19/2026 | 9363 | 3/19/2026 | $968.67 | $300.00 | $668.67 | Anthony
 *   → Finds Sale with invoice_number LIKE '%-9363'
 *   → Creates laybuys row: total=$968.67, paid=$300, balance=$668.67, staff=Anthony
 */
class ImportOnSwimDeposits extends Command
{
    protected $signature = 'import:onswim-deposits {tenant} {--dry-run} {--rollback}';
    protected $description = 'Import OnSwim Deposit Sales into laybuys table';

    private int   $matched  = 0;
    private int   $skipped  = 0;
    private int   $created  = 0;
    private int   $updated  = 0;
    private array $warnings = [];
    private array $notFound = [];

    public function handle()
    {
        $tenantId   = $this->argument('tenant');
        $isDryRun   = $this->option('dry-run');
        $isRollback = $this->option('rollback');

        try {
            $tenant = Tenant::findOrFail($tenantId);
            tenancy()->initialize($tenant);
        } catch (\Exception $e) {
            $this->error("Could not initialize tenant: {$tenantId}");
            return 1;
        }

        // ── Rollback ──────────────────────────────────────────────
        if ($isRollback) {
            if (!$this->confirm("⚠️  Delete ALL Laybuy records for [{$tenantId}]?")) {
                $this->info("Rollback cancelled.");
                return 0;
            }
            $count = Laybuy::count();
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            Laybuy::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->warn("Rolled back: {$count} records deleted.");
            return 0;
        }

        // ── File ──────────────────────────────────────────────────
        $directoryPath = storage_path("app/data");
$filePath = "{$directoryPath}/dsq23mar_deposit_sales.csv";

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            $this->line("Place your OnSwim Deposit Sales CSV at:");
            $this->line("  storage/app/data/{$tenantId}/deposit_sales.csv");
            return 1;
        }

        // ── Read CSV ──────────────────────────────────────────────
        $file   = fopen($filePath, 'r');
        $header = array_map('trim', fgetcsv($file));

        $required = ['Job No.', 'Invoice Total', 'Payment Total', 'Balance', 'Date Sold'];
        $missing  = array_diff($required, $header);
        if (!empty($missing)) {
            $this->error("CSV missing columns: " . implode(', ', $missing));
            $this->line("Found: " . implode(', ', $header));
            fclose($file);
            return 1;
        }

        $rows = [];
        while (($row = fgetcsv($file)) !== false) {
            if (count($row) !== count($header)) continue;
            $data = array_combine($header, $row);
            // Skip empty job rows
            if (empty(trim($data['Job No.'] ?? ''))) continue;
            // Skip totals footer row OnSwim adds at bottom
            if (empty(trim($data['Customer'] ?? ''))) continue;
            if (is_numeric(trim($data['Customer'] ?? ''))) continue;
            $rows[] = $data;
        }
        fclose($file);

        $total = count($rows);
        $this->info($isDryRun
            ? "🔍 DRY RUN — {$total} rows (no data written)"
            : "📥 Importing {$total} deposit rows for [{$tenantId}]..."
        );

        // ── Build Job No → Sale lookup map ────────────────────────
        // invoice_number in DB = "D031926-9363" → job number = 9363
        // We extract the number after the last '-'
        $jobToSale = [];
        Sale::select('id', 'invoice_number', 'customer_id', 'store_id', 'sales_person_list', 'final_total')
            ->chunk(500, function ($sales) use (&$jobToSale) {
                foreach ($sales as $sale) {
                    $inv = $sale->invoice_number ?? '';
                    // D031926-9368 → extract 9368
                    if (str_contains($inv, '-')) {
                        $parts = explode('-', $inv);
                        $jobNo = end($parts);
                        if (is_numeric($jobNo)) { $jobToSale[(int)$jobNo] = $sale; continue; }
                    }
                    if (is_numeric($inv)) { $jobToSale[(int)$inv] = $sale; continue; }
                    $numeric = preg_replace('/[^0-9]/', '', $inv);
                    if (!empty($numeric) && strlen($numeric) >= 3) $jobToSale[(int)$numeric] = $sale;
                }
            });

        $this->info("Mapped " . count($jobToSale) . " sales.");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        if (!$isDryRun) DB::beginTransaction();

        try {
            foreach ($rows as $data) {
                $jobNo = trim($data['Job No.']);

                // ── Find matching Sale ─────────────────────────────
                $sale        = null;
                $matchMethod = '';

                // Method 1: job map (fastest — covers most cases)
                $numericJob = (int) preg_replace('/[^0-9]/', '', $jobNo);
                if (isset($jobToSale[$numericJob])) {
                    $sale        = $jobToSale[$numericJob];
                    $matchMethod = 'map';
                }

                // Method 2: exact invoice_number match
                if (!$sale) {
                    $sale = Sale::where('invoice_number', $jobNo)->first();
                    if ($sale) $matchMethod = 'exact';
                }

                // Method 3: D-prefix match
                if (!$sale) {
                    $sale = Sale::where('invoice_number', 'D' . ltrim($jobNo, '0'))->first();
                    if ($sale) $matchMethod = 'D-prefix';
                }

                // Method 4: pattern match (last resort)
                if (!$sale) {
                    $candidates = Sale::where('invoice_number', 'like', "%-{$jobNo}")->get();
                    if ($candidates->count() === 1) {
                        $sale             = $candidates->first();
                        $matchMethod      = 'pattern ⚠️';
                        $this->warnings[] = "Job #{$jobNo} → [{$sale->invoice_number}] via pattern — verify manually";
                    } elseif ($candidates->count() > 1) {
                        $this->warnings[] = "Job #{$jobNo} matched {$candidates->count()} sales — SKIPPED (ambiguous)";
                        $this->skipped++;
                        $bar->advance();
                        continue;
                    }
                }

                if (!$sale) {
                    $this->notFound[] = "Job #{$jobNo} ({$data['Customer']})";
                    $this->skipped++;
                    $bar->advance();
                    continue;
                }

                $this->matched++;

                // ── Parse money values ─────────────────────────────
                // "-" in Payment Total means $0 paid (e.g. stacy Benally)
                $invoiceTotal = $this->cleanMoney($data['Invoice Total']);
                $amountPaid   = $this->cleanMoney($data['Payment Total']); // "-" → 0.00
                $balance      = $this->cleanMoney($data['Balance']);

                // ── Status ─────────────────────────────────────────
                // 'active' = still has balance (matches DepositSalesReport filter)
                // 'completed' = fully paid
                $status = $balance <= 0.50 ? 'completed' : 'active';

                // ── Parse staff ────────────────────────────────────
                // "Jeanette/Anthony" → ["Jeanette", "Anthony"]
                $rawStaff     = trim($data['Staff'] ?? 'System');
                $staffList    = array_values(array_filter(
                    array_map('trim', preg_split('/[\/,]+/', $rawStaff))
                ));
                $primaryStaff = $staffList[0] ?? 'System';

                $startDate    = $this->parseDate($data['Date Sold'] ?? null);
                $lastPaidDate = $this->parseDate($data['Last Date Paid'] ?? null);
                $dueDate      = $this->parseDate($data['Date Required'] ?? null);

                $payload = [
                    'customer_id'    => $sale->customer_id,
                    'laybuy_no'      => 'LAY-' . $jobNo,
                    'total_amount'   => $invoiceTotal,
                    'amount_paid'    => $amountPaid,
                    'balance_due'    => max(0, $balance),
                    'status'         => $status,
                    'sales_person'   => $primaryStaff,
                    'staff_list'     => $staffList,
                    'start_date'     => $startDate,
                    'last_paid_date' => $lastPaidDate,
                    'due_date'       => $dueDate,
                    'notes'          => "Imported from OnSwim. Last Paid: " . ($data['Last Date Paid'] ?? 'N/A'),
                ];

                if ($isDryRun) {
                    $action = Laybuy::where('sale_id', $sale->id)->exists() ? 'UPDATE' : 'CREATE';
                    $this->line("\n  [{$action}] Job #{$jobNo} → {$sale->invoice_number} | {$matchMethod} | total:\${$invoiceTotal} | paid:\${$amountPaid} | balance:\${$balance} | {$status}");
                } else {
                    $existing = Laybuy::where('sale_id', $sale->id)->first();

                    Laybuy::withoutEvents(fn() =>
                        Laybuy::updateOrCreate(
                            ['sale_id' => $sale->id],  // unique key = one laybuy per sale
                            $payload
                        )
                    );

                    // Sync amount_paid and balance_due back to sales table
                    // so SaleResource payment summary stays accurate
                    // NOTE: no is_fully_paid — that column doesn't exist in your sales table
                    Sale::withoutEvents(fn() =>
                        $sale->update([
                            'amount_paid' => $amountPaid,
                            'balance_due' => max(0, $balance),
                        ])
                    );

                    $existing ? $this->updated++ : $this->created++;
                }

                $bar->advance();
            }

            if (!$isDryRun) DB::commit();

        } catch (\Exception $e) {
            if (!$isDryRun) DB::rollBack();
            $bar->finish();
            $this->newLine();
            $this->error("Import failed: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Rows',       $total],
                ['Matched to Sales', $this->matched],
                ['Skipped',          $this->skipped],
                ['Created',          $this->created],
                ['Updated',          $this->updated],
                ['Warnings',         count($this->warnings)],
                ['Not Found',        count($this->notFound)],
            ]
        );

        if (!empty($this->warnings)) {
            $this->newLine();
            $this->warn("⚠️  Warnings — verify manually:");
            foreach ($this->warnings as $w) $this->line("  · {$w}");
        }

        if (!empty($this->notFound)) {
            $this->newLine();
            $this->error("❌ No matching sale found for:");
            foreach ($this->notFound as $nf) $this->line("  · {$nf}");
            $this->line("  → Run import:onswim-sales first, then re-run this.");
        }

        $isDryRun
            ? $this->info("✅ Dry run complete — run without --dry-run to import.")
            : $this->info("✅ Import complete!");

        return 0;
    }

    // ── Helpers ───────────────────────────────────────────────

    /**
     * Clean OnSwim money strings.
     * "$1,200.00" → 1200.00
     * "-"         → 0.00  (means nothing paid)
     * ""          → 0.00
     */
    private function cleanMoney($value): float
    {
        $clean = str_replace(['$', ',', ' '], '', trim((string)($value ?? '0')));
        return ($clean === '-' || $clean === '' || !is_numeric($clean)) ? 0.00 : (float)$clean;
    }

    private function parseDate($date): ?string
    {
        if (empty($date) || trim($date) === '-' || trim($date) === '') return null;
        try { return Carbon::parse(trim($date))->format('Y-m-d'); }
        catch (\Exception $e) { return null; }
    }
}