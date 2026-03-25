<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\Laybuy;
use App\Models\Tenant;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ImportOnSwimDeposits extends Command
{
    protected $signature = 'import:onswim-deposits {tenant} {--dry-run} {--rollback}';
    protected $description = 'Import OnSwim Deposit Sales/Laybuys and link to existing sales';

    private int $matched   = 0;
    private int $skipped   = 0;
    private int $created   = 0;
    private int $updated   = 0;
    private array $warnings = [];
    private array $notFound = [];

    public function handle()
    {
        $tenantId  = $this->argument('tenant');
        $isDryRun  = $this->option('dry-run');
        $isRollback = $this->option('rollback');

        // ── TENANT INIT ───────────────────────────────────────────────────────
        try {
            $tenant = Tenant::findOrFail($tenantId);
            tenancy()->initialize($tenant);
        } catch (\Exception $e) {
            $this->error("Could not initialize tenant: {$tenantId}");
            return 1;
        }

        // ── ROLLBACK ──────────────────────────────────────────────────────────
        if ($isRollback) {
            if (!$this->confirm("⚠️  This will DELETE all Laybuy records for tenant [{$tenantId}]. Are you sure?")) {
                $this->info("Rollback cancelled.");
                return 0;
            }
            $count = Laybuy::count();
            Laybuy::truncate();
            $this->warn("Rolled back: {$count} Laybuy records deleted.");
            return 0;
        }

        // ── FILE CHECK ────────────────────────────────────────────────────────
        $directoryPath = storage_path("{$tenantId}/data");
        $filePath      = "{$directoryPath}/dsq23mar_deposit_sales.csv";

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            $this->line("Expected CSV at: <comment>{$filePath}</comment>");
            $this->line("Create directory and place your CSV there.");
            return 1;
        }

        // ── READ CSV ──────────────────────────────────────────────────────────
        $file   = fopen($filePath, 'r');
        $header = array_map('trim', fgetcsv($file));

        // Validate required columns
        $required = ['Job No.', 'Invoice Total', 'Payment Total', 'Balance', 'Date Sold'];
        $missing  = array_diff($required, $header);
        if (!empty($missing)) {
            $this->error("CSV is missing required columns: " . implode(', ', $missing));
            $this->line("Found columns: " . implode(', ', $header));
            fclose($file);
            return 1;
        }

        $rows = [];
        while (($row = fgetcsv($file)) !== false) {
            if (count($row) !== count($header)) continue; // skip malformed rows
            $data = array_combine($header, $row);
            if (empty(trim($data['Job No.'] ?? ''))) continue; // skip blank job rows
            $rows[] = $data;
        }
        fclose($file);

        $total = count($rows);
        $this->info($isDryRun
            ? "🔍 DRY RUN — No data will be written. Processing {$total} rows..."
            : "📥 Importing {$total} deposit rows for tenant [{$tenantId}]..."
        );

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        // ── PROCESS ROWS ──────────────────────────────────────────────────────
        if (!$isDryRun) {
            DB::beginTransaction();
        }

        try {
            foreach ($rows as $data) {
                $jobNo = trim($data['Job No.']);

                // ── FIND SALE ─────────────────────────────────────────────────
                $sale       = null;
                $matchMethod = '';

                // 1. Exact match
                $sale = Sale::where('invoice_number', $jobNo)->first();
                if ($sale) $matchMethod = 'exact';

                // 2. Prefix D match (e.g. job "5028" → "D5028")
                if (!$sale) {
                    $sale = Sale::where('invoice_number', 'D' . $jobNo)->first();
                    if ($sale) $matchMethod = 'D-prefix';
                }

                // 3. Leading zero stripped (e.g. "05028" → "D5028")
                if (!$sale) {
                    $sale = Sale::where('invoice_number', 'D' . ltrim($jobNo, '0'))->first();
                    if ($sale) $matchMethod = 'D-prefix-trimmed';
                }

                // 4. Date-format pattern (LAST RESORT — log a warning)
                if (!$sale) {
                    $candidates = Sale::where('invoice_number', 'like', "%-{$jobNo}")->get();
                    if ($candidates->count() === 1) {
                        $sale        = $candidates->first();
                        $matchMethod = 'pattern-match ⚠️';
                        $this->warnings[] = "Job #{$jobNo} matched via pattern to [{$sale->invoice_number}] — verify manually";
                    } elseif ($candidates->count() > 1) {
                        $this->warnings[] = "Job #{$jobNo} matched {$candidates->count()} invoices via pattern — SKIPPED (ambiguous)";
                        $this->skipped++;
                        $bar->advance();
                        continue;
                    }
                }

                if (!$sale) {
                    $this->notFound[] = "Job #{$jobNo} — no matching sale found";
                    $this->skipped++;
                    $bar->advance();
                    continue;
                }

                $this->matched++;

                // ── PARSE DATA ────────────────────────────────────────────────
                $invoiceTotal = $this->cleanMoney($data['Invoice Total']);
                $amountPaid   = $this->cleanMoney($data['Payment Total']);
                $balance      = $this->cleanMoney($data['Balance']);
                $status       = $balance <= 0.50 ? 'completed' : 'in_progress';

                $rawStaff  = trim($data['Staff'] ?? 'System');
                $staffList = array_map('trim', str_contains($rawStaff, '/')
                    ? explode('/', $rawStaff)
                    : [$rawStaff]
                );
                $staffList = array_filter($staffList); // remove empty

                $startDate    = $this->parseDate($data['Date Sold'] ?? null);
                $lastPaidDate = $this->parseDate($data['Last Date Paid'] ?? null);
                $dueDate      = $this->parseDate($data['Date Required'] ?? null);

                $layPayload = [
                    'customer_id'    => $sale->customer_id,
                    'laybuy_no'      => 'LAY-' . $jobNo,
                    'total_amount'   => $invoiceTotal,
                    'amount_paid'    => $amountPaid,
                    'balance_due'    => $balance,
                    'status'         => $status,
                    'sales_person'   => $rawStaff,
                    'staff_list'     => $staffList,
                    'start_date'     => $startDate,
                    'last_paid_date' => $lastPaidDate,
                    'due_date'       => $dueDate,
                    'notes'          => "Imported from OnSwim. Last Paid: " . ($data['Last Date Paid'] ?? 'N/A'),
                ];

                if ($isDryRun) {
                    $existing = Laybuy::where('sale_id', $sale->id)->first();
                    $action   = $existing ? 'UPDATE' : 'CREATE';
                    $this->line("\n  [{$action}] Job #{$jobNo} → Sale [{$sale->invoice_number}] | " .
                        "Match: {$matchMethod} | Total: \${$invoiceTotal} | Paid: \${$amountPaid} | Balance: \${$balance} | Status: {$status}");
                } else {
                    $existing = Laybuy::where('sale_id', $sale->id)->first();
                    Laybuy::updateOrCreate(['sale_id' => $sale->id], $layPayload);
                    $existing ? $this->updated++ : $this->created++;
                }

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
            $this->error("Stack trace: " . $e->getTraceAsString());
            return 1;
        }

        $bar->finish();
        $this->newLine(2);

        // ── SUMMARY ───────────────────────────────────────────────────────────
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Rows in CSV',       $total],
                ['Matched to Sales',        $this->matched],
                ['Skipped (not found)',     $this->skipped],
                $isDryRun
                    ? ['Would Create',      $this->matched]
                    : ['Created (new)',     $this->created],
                $isDryRun
                    ? ['Would Update',      0]
                    : ['Updated (existing)',$this->updated],
                ['Warnings',               count($this->warnings)],
                ['Not Found',              count($this->notFound)],
            ]
        );

        // ── WARNINGS ─────────────────────────────────────────────────────────
        if (!empty($this->warnings)) {
            $this->newLine();
            $this->warn("⚠️  WARNINGS — Verify these manually:");
            foreach ($this->warnings as $w) {
                $this->line("  · {$w}");
            }
        }

        // ── NOT FOUND ─────────────────────────────────────────────────────────
        if (!empty($this->notFound)) {
            $this->newLine();
            $this->error("❌ NOT FOUND — These job numbers had no matching sale:");
            foreach ($this->notFound as $nf) {
                $this->line("  · {$nf}");
            }
        }

        if ($isDryRun) {
            $this->newLine();
            $this->info("✅ Dry run complete. No data was written.");
            $this->info("   Run without --dry-run to actually import.");
        } else {
            $this->newLine();
            $this->info("✅ Import complete!");
        }

        return 0;
    }

    // ── HELPERS ───────────────────────────────────────────────────────────────

    private function cleanMoney($value): float
    {
        $clean = str_replace(['$', ',', ' '], '', trim($value ?? '0'));
        return ($clean === '-' || $clean === '' || !is_numeric($clean)) ? 0.00 : (float) $clean;
    }

    private function parseDate($date): ?string
    {
        if (empty($date) || trim($date) === '-' || trim($date) === '') return null;
        try {
            return Carbon::parse(trim($date))->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}