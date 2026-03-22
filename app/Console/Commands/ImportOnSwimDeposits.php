<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\Laybuy; 
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ImportOnSwimDeposits extends Command
{
    protected $signature = 'import:onswim-deposits {tenant} {--rollback}';
    protected $description = 'Import OnSwim Deposit Sales (Laybuys) and link them to Sales';

    public function handle()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $tenantId = $this->argument('tenant');
        
        try {
            $tenant = \App\Models\Tenant::findOrFail($tenantId);
            tenancy()->initialize($tenant);
        } catch (\Exception $e) {
            $this->error("Could not find tenant: {$tenantId}");
            return;
        }

        if ($this->option('rollback')) {
            Schema::disableForeignKeyConstraints();
            Laybuy::truncate(); 
            Schema::enableForeignKeyConstraints();
            $this->info("Laybuys table truncated.");
            return;
        }

        // STORAGE PATH
        $directoryPath = storage_path("{$tenantId}/data");
        $filePath = "{$directoryPath}/deposit_sales_dsq_mar26.csv"; 
        
        if (!file_exists($filePath)) {
            $this->error("File not found at: {$filePath}");
            return;
        }

        $file = fopen($filePath, 'r');
        $headers = array_map('trim', fgetcsv($file));
        $rows = [];
        while (($row = fgetcsv($file)) !== false) {
            if (count($row) === count($headers)) $rows[] = array_combine($headers, $row);
        }
        fclose($file);

        $this->info("Building Job → Sale map...");
        $jobToSale = [];
        Sale::select('id', 'invoice_number', 'customer_id', 'store_id', 'created_at')
            ->chunk(500, function ($sales) use (&$jobToSale) {
                foreach ($sales as $sale) {
                    $parts = explode('-', $sale->invoice_number);
                    $jobNoStr = $parts[1] ?? null;
                    if (is_numeric($jobNoStr)) {
                        $jobToSale[(int)$jobNoStr] = $sale;
                    }
                }
            });

        $created = 0; 
        $skippedNoSale = 0; 
        
        DB::connection('tenant')->beginTransaction();

        try {
            $bar = $this->output->createProgressBar(count($rows));

            foreach ($rows as $row) {
                $jobNo = (int) trim($row['Job No.'] ?? 0);
                
                $invoiceTotal = (float) str_replace(['$', ',', ' '], '', trim($row['Invoice Total'] ?? '0'));
                $paymentTotal = (float) str_replace(['$', ',', ' '], '', trim($row['Payment Total'] ?? '0'));
                $balance = (float) str_replace(['$', ',', ' '], '', trim($row['Balance'] ?? '0'));

                if ($jobNo <= 0) { $bar->advance(); continue; }

                $sale = $jobToSale[$jobNo] ?? null;

                if (!$sale) {
                    $skippedNoSale++; 
                    $bar->advance(); 
                    continue;
                }

                $status = $balance > 0 ? 'active' : 'completed';
                $staff = trim($row['Staff'] ?? 'System');

                if ($balance > 0) {
                    $sale->update(['payment_method' => 'laybuy', 'status' => 'pending']);
                }

                Laybuy::withoutEvents(function() use ($sale, $jobNo, $invoiceTotal, $paymentTotal, $balance, $status, $staff, $row) {
                    $laybuy = Laybuy::where('sale_id', $sale->id)->first();
                    
                    if (!$laybuy) {
                        $laybuy = new Laybuy();
                        $laybuy->sale_id = $sale->id;
                        $laybuy->laybuy_no = 'LAY-' . $jobNo . '-' . strtoupper(Str::random(3));
                    }

                    $laybuy->customer_id = $sale->customer_id;
                    $laybuy->store_id = $sale->store_id;
                    $laybuy->total_amount = $invoiceTotal;
                    $laybuy->amount_paid = $paymentTotal;
                    $laybuy->balance_due = $balance;
                    $laybuy->status = $status;
                    $laybuy->sales_person = $staff;
                    $laybuy->start_date = $this->parseDate($row['Date Sold'] ?? null) ?? $sale->created_at;
                    $laybuy->due_date = $this->parseDate($row['Date Required'] ?? null);
                    $laybuy->notes = "Last Date Paid: " . ($row['Last Date Paid'] ?? 'N/A');
                    
                    $laybuy->save();
                });

                $created++;
                $bar->advance();
            }

            DB::connection('tenant')->commit();
            $bar->finish();
            $this->newLine(2);
            
            $this->table(
                ['Total CSV Rows', 'Laybuys Created/Updated', 'No Sale Match (Skipped)'], 
                [[count($rows), $created, $skippedNoSale]]
            );

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            $this->error("Error: " . $e->getMessage());
        }
    }

    private function parseDate(?string $date): ?string
    {
        if (empty($date) || $date === '-') return null;
        try { return Carbon::parse($date)->format('Y-m-d'); } 
        catch (\Exception $e) { return null; }
    }
}