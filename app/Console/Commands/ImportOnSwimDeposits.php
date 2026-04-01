<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\DepositSale;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportOnSwimDeposits extends Command
{
    protected $signature = 'import:onswim-deposits {tenant} {--dry-run} {--rollback}';
    protected $description = 'Import OnSwim Deposit Sales into deposit_sales table';

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
            if (!$this->confirm("⚠️  Delete ALL Deposit records for [{$tenantId}]?")) {
                $this->info("Rollback cancelled.");
                return 0;
            }
            $count = DepositSale::count();
            DepositSale::truncate();
            $this->warn("Rolled back: {$count} records deleted.");
            return 0;
        }

        // ── Dynamic File Path Logic ──────────────────────────────
        // 🚀 Matches your stock structure: storage/tenantId/data/
        $directoryPath = storage_path("/{$tenantId}/data");
        
        // Looks for filename like: deposits_2026_03_31.csv
        $yesterday = Carbon::yesterday()->format('Y_m_d'); 
        $fileName = "deposits_{$yesterday}.csv";
        $filePath = "{$directoryPath}/{$fileName}";

        if (!File::exists($filePath)) {
            $this->error("❌ FILE NOT FOUND: {$filePath}");
            $this->line("Place your OnSwim Deposit CSV in: {$directoryPath}");
            return 1;
        }

        // ── Read CSV ──────────────────────────────────────────────
        $file   = fopen($filePath, 'r');
        $header = array_map('trim', fgetcsv($file));

        $required = ['Job No.', 'Invoice Total', 'Payment Total', 'Balance', 'Date Sold'];
        $missing  = array_diff($required, $header);
        if (!empty($missing)) {
            $this->error("CSV missing columns: " . implode(', ', $missing));
            fclose($file);
            return 1;
        }

        $rows = [];
        while (($row = fgetcsv($file)) !== false) {
            if (count($row) !== count($header)) continue;
            $data = array_combine($header, $row);
            if (empty(trim($data['Job No.'] ?? ''))) continue;
            if (empty(trim($data['Customer'] ?? '')) || is_numeric(trim($data['Customer'] ?? ''))) continue;
            $rows[] = $data;
        }
        fclose($file);

        $total = count($rows);
        $this->info($isDryRun ? "🔍 DRY RUN — {$total} rows" : "📥 Importing {$total} rows...");

        // ── Build Job No → Sale lookup map ────────────────────────
        $jobToSale = [];
        Sale::select('id', 'invoice_number', 'customer_id')->chunk(500, function ($sales) use (&$jobToSale) {
            foreach ($sales as $sale) {
                $inv = $sale->invoice_number ?? '';
                if (str_contains($inv, '-')) {
                    $jobNo = end(explode('-', $inv));
                    if (is_numeric($jobNo)) { $jobToSale[(int)$jobNo] = $sale; continue; }
                }
                $numeric = preg_replace('/[^0-9]/', '', $inv);
                if (!empty($numeric)) $jobToSale[(int)$numeric] = $sale;
            }
        });

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        if (!$isDryRun) DB::beginTransaction();

        try {
            foreach ($rows as $data) {
                $jobNo = trim($data['Job No.']);
                $numericJob = (int) preg_replace('/[^0-9]/', '', $jobNo);
                
                $sale = $jobToSale[$numericJob] ?? Sale::where('invoice_number', 'like', "%{$jobNo}")->first();

                if (!$sale) {
                    $this->notFound[] = "Job #{$jobNo} ({$data['Customer']})";
                    $this->skipped++;
                    $bar->advance();
                    continue;
                }

                $this->matched++;
                $invoiceTotal = $this->cleanMoney($data['Invoice Total']);
                $amountPaid   = $this->cleanMoney($data['Payment Total']);
                $balance      = $this->cleanMoney($data['Balance']);
                $status       = $balance <= 0.50 ? 'completed' : 'active';

                $staff        = trim($data['Staff'] ?? 'System');
                $staffList    = array_values(array_filter(preg_split('/[\/,]+/', $staff)));

                $payload = [
                    'customer_id'    => $sale->customer_id,
                    'deposit_no'     => 'DEP-' . $jobNo,
                    'total_amount'   => $invoiceTotal,
                    'amount_paid'    => $amountPaid,
                    'balance_due'    => max(0, $balance),
                    'status'         => $status,
                    'sales_person'   => $staffList[0] ?? 'System',
                    'staff_list'     => $staffList,
                    'start_date'     => $this->parseDate($data['Date Sold']),
                    'last_paid_date' => $this->parseDate($data['Last Date Paid']),
                    'due_date'       => $this->parseDate($data['Date Required']),
                    'notes'          => "Imported. Job #{$jobNo}. Last Paid: " . ($data['Last Date Paid'] ?? 'N/A'),
                ];

                if (!$isDryRun) {
                    $exists = DepositSale::where('sale_id', $sale->id)->exists();
                    DepositSale::updateOrCreate(['sale_id' => $sale->id], $payload);
                    $sale->update(['amount_paid' => $amountPaid, 'balance_due' => max(0, $balance)]);
                    $exists ? $this->updated++ : $this->created++;
                }

                $bar->advance();
            }
            if (!$isDryRun) DB::commit();
        } catch (\Exception $e) {
            if (!$isDryRun) DB::rollBack();
            $this->error("\nError: " . $e->getMessage());
            return 1;
        }

        $bar->finish();
        $this->newLine(2);
        $this->table(['Metric', 'Count'], [
            ['Total Rows', $total], ['Matched', $this->matched], ['Created', $this->created], ['Updated', $this->updated]
        ]);

        return 0;
    }

    private function cleanMoney($value): float {
        $clean = str_replace(['$', ',', ' '], '', trim((string)($value ?? '0')));
        return ($clean === '-' || $clean === '') ? 0.00 : (float)$clean;
    }

    private function parseDate($date): ?string {
        if (empty($date) || $date === '-') return null;
        try { return Carbon::parse($date)->format('Y-m-d'); }
        catch (\Exception $e) { return null; }
    }
}