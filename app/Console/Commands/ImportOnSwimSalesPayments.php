<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportOnSwimSalesPayments extends Command
{
    protected $signature = 'import:onswim-sales-payments {tenant} {--rollback}';
    protected $description = 'Import OnSwim Sales Payments and dynamically map them to Sales';

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
            SalePayment::truncate();
            Schema::enableForeignKeyConstraints();
            $this->info("SalePayments table truncated.");
            return;
        }

       $directoryPath = storage_path("{$tenantId}/data");
        // ⚠️ Double check this filename! You had 'ssales' with two S's in your prompt.
        $filePath = "{$directoryPath}/ssales_payments_dsq_data_mar22_26.csv";
        
        if (!file_exists($filePath)) {
            $this->error("File not found at: {$filePath}");
            return;
        }

        // 🚀 1. FETCH DYNAMIC PAYMENT METHODS FROM SETTINGS
        $paymentMethodsJson = DB::table('site_settings')->where('key', 'payment_methods')->value('value');
        $activePaymentMethods = $paymentMethodsJson ? json_decode($paymentMethodsJson, true) : ['CASH', 'VISA'];
        
        // Normalize active methods for easy matching (e.g., 'CASH' -> 'cash')
        $normalizedActiveMethods = [];
        foreach ($activePaymentMethods as $method) {
            $normalizedActiveMethods[strtolower(trim($method))] = $method;
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
        Sale::select('id', 'invoice_number', 'customer_id', 'sales_person_list', 'store_id')
            ->chunk(500, function ($sales) use (&$jobToSale) {
                foreach ($sales as $sale) {
                    $jobNo = preg_replace('/[^0-9]/', '', $sale->invoice_number);
                    if (is_numeric($jobNo) && $jobNo !== '') {
                        $jobToSale[(int)$jobNo] = $sale;
                    }
                }
            });

        $created = 0; $skippedNoSale = 0; $skippedDup = 0;
        $unmappedMethods = []; // To track missing settings
        
        DB::connection('tenant')->beginTransaction();

        try {
            $bar = $this->output->createProgressBar(count($rows));

            foreach ($rows as $row) {
                $jobNo = (int) trim($row['Job Number'] ?? 0);
                $amountRaw = trim($row['Amount'] ?? '0');
                $amount = (float) str_replace(['$', ',', ' '], '', $amountRaw);
                $paymentType = trim($row['Payment Type'] ?? 'cash');
                $saleType = strtolower(trim($row['Sale Type'] ?? 'sale'));

                if ($amount <= 0) { $bar->advance(); continue; }

                $sale = $jobToSale[$jobNo] ?? null;
                if (!$sale) {
                    $skippedNoSale++; $bar->advance(); continue;
                }

                $paymentDate = $this->parseDate($row['Date'] ?? null) ?? now()->format('Y-m-d');
                
                // 🚀 2. USE DYNAMIC MAPPING FUNCTION
                $mappedMethod = $this->mapDynamicPaymentType($paymentType, $normalizedActiveMethods, $unmappedMethods);
                
                $isLayby = in_array($saleType, ['layby', 'lay-by', 'deposit']) || strtolower($mappedMethod) === 'laybuy';

                $salesPerson = is_array($sale->sales_person_list) ? ($sale->sales_person_list[0] ?? 'System') : ($sale->sales_person_list ?? 'System');

                $exists = SalePayment::where('sale_id', $sale->id)->where('amount', $amount)
                    ->where('payment_method', $mappedMethod)->where('payment_date', $paymentDate)->exists();

                if ($exists) { $skippedDup++; $bar->advance(); continue; }

                SalePayment::withoutEvents(fn() => SalePayment::create([
                    'sale_id' => $sale->id,
                    'customer_id' => $sale->customer_id,
                    'store_id' => $sale->store_id,
                    'amount' => $amount,
                    'payment_method' => $mappedMethod,
                    'original_payment_type' => $paymentType,
                    'payment_date' => $paymentDate,
                    'is_deposit' => $isLayby,
                    'is_layby' => $isLayby,
                    'sales_person' => $salesPerson,
                    'imported_at' => now(),
                ]));

                $created++;
                $bar->advance();
            }

            DB::connection('tenant')->commit();
            $bar->finish();
            $this->newLine(2);
            
            $this->table(['Total CSV Rows', 'Created', 'No Sale Match (Skipped)', 'Duplicates (Skipped)'], [[count($rows), $created, $skippedNoSale, $skippedDup]]);

            // 🚀 3. PRINT WARNING IF METHODS ARE MISSING IN FILAMENT
            if (!empty($unmappedMethods)) {
                $this->warn("\n⚠️ Notice: The following payment types were found in the CSV but are not explicitly mapped in your Filament Settings:");
                foreach (array_unique($unmappedMethods) as $unmapped) {
                    $this->line(" - " . $unmapped);
                }
                $this->line("They were imported using their exact CSV name, but you may want to add them to your Utilities -> Sales -> Payment Methods.");
            }

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            $this->error("Error: " . $e->getMessage());
        }
    }

    /**
     * Maps OnSwim text to your dynamically configured settings.
     */
    private function mapDynamicPaymentType(string $rawType, array $activeMethods, array &$unmappedTracker): string
    {
        $cleanRaw = strtolower(trim($rawType));

        // 1. Direct Match
        if (isset($activeMethods[$cleanRaw])) {
            return $activeMethods[$cleanRaw];
        }

        // 2. Common Alias Matching
        $aliases = [
            'debit card' => 'debit',
            'credit card' => 'credit',
            'cheque' => 'check',
            'lay-by' => 'laybuy',
            'lay by' => 'laybuy',
        ];

        $aliased = $aliases[$cleanRaw] ?? $cleanRaw;

        // 3. Fuzzy Match
        foreach ($activeMethods as $key => $actualMethod) {
            if (str_contains($key, $aliased) || str_contains($aliased, $key)) {
                return $actualMethod; 
            }
        }

        // 4. Safe Fallback
        $unmappedTracker[] = $rawType; // Track this so we can warn the user at the end
        return strtoupper(trim($rawType));
    }

    private function parseDate(?string $date): ?string
    {
        if (empty($date)) return null;
        try { return Carbon::parse($date)->format('Y-m-d'); } 
        catch (\Exception $e) { return null; }
    }
}