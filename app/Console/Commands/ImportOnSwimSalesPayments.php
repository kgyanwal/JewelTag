<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Store;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ImportOnSwimSalesPayments extends Command
{
    protected $signature = 'import:onswim-sales-payments {tenant} {--rollback}';
    protected $description = 'Import OnSwim Sales Payments, map to Sales, or directly to Customers as Historical Jobs';

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
            // Optional: You could also delete sales that start with 'HIST-' here if you wanted a total wipe
            Schema::enableForeignKeyConstraints();
            $this->info("SalePayments table truncated.");
            return;
        }

        $directoryPath = storage_path("{$tenantId}/data");
        $filePath = "{$directoryPath}/sales_payments_dsq_data_mar22_26.csv";
        
        if (!file_exists($filePath)) {
            $this->error("File not found at: {$filePath}");
            return;
        }

        $defaultStoreId = Store::first()?->id ?? 1;

        // 1. FETCH DYNAMIC PAYMENT METHODS
        $paymentMethodsJson = DB::table('site_settings')->where('key', 'payment_methods')->value('value');
        $activePaymentMethods = $paymentMethodsJson ? json_decode($paymentMethodsJson, true) : ['CASH', 'VISA'];
        
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
                    // 🚀 BUG FIX: Correctly extract Job Number without mangling the date
                    $parts = explode('-', $sale->invoice_number);
                    $jobNoStr = end($parts);
                    
                    if (is_numeric($jobNoStr)) {
                        $jobToSale[(int)$jobNoStr] = $sale;
                    }
                }
            });

        $createdMatched = 0; 
        $createdHistorical = 0; 
        $skippedDup = 0;
        $unmappedMethods = []; 
        
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

                $paymentDate = $this->parseDate($row['Date'] ?? null) ?? now()->format('Y-m-d');
                $mappedMethod = $this->mapDynamicPaymentType($paymentType, $normalizedActiveMethods, $unmappedMethods);
                $isLayby = in_array($saleType, ['layby', 'lay-by', 'deposit']) || strtolower($mappedMethod) === 'laybuy';

                // 🚀 DIRECT CUSTOMER MAPPING LOGIC
                $sale = $jobToSale[$jobNo] ?? null;
                $isHistorical = false;

                if (!$sale) {
                    $firstName = trim($row['First Name'] ?? '');
                    $lastName = trim($row['Last Name'] ?? '');
                    
                    // A. Find or Create the Customer
                    $customer = null;
                    if (!empty($firstName) || !empty($lastName)) {
                        $customer = Customer::withoutGlobalScopes()
                            ->where('name', $firstName)
                            ->where('last_name', $lastName)
                            ->first();
                    }

                    if (!$customer) {
                        $customer = Customer::create([
                            'customer_no' => 'CUST-HIST-' . strtoupper(Str::random(6)),
                            'name' => $firstName ?: 'Historical',
                            'last_name' => $lastName ?: 'Customer',
                            'is_active' => true,
                        ]);
                    }

                    // B. Create a "Historical Sale" shell to attach the payment to
                    $sale = Sale::withoutEvents(function () use ($jobNo, $customer, $paymentDate, $defaultStoreId) {
                        return Sale::create([
                            'invoice_number' => 'HIST-' . $jobNo . '-' . strtoupper(Str::random(4)),
                            'customer_id' => $customer->id,
                            'store_id' => $defaultStoreId,
                            'status' => 'completed',
                            'sales_person_list' => ['Historical Import'],
                            'created_at' => $paymentDate,
                            'payment_method' => 'other',
                            'is_split_payment' => false,
                            'subtotal' => 0,
                            'tax_amount' => 0,
                            'final_total' => 0,
                            'discount_amount' => 0
                        ]);
                    });

                    // Add to map so subsequent payments for this job find it
                    $jobToSale[$jobNo] = $sale;
                    $isHistorical = true;
                }

                $salesPerson = is_array($sale->sales_person_list) ? ($sale->sales_person_list[0] ?? 'System') : ($sale->sales_person_list ?? 'System');

                // Duplicate Check
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

                if ($isHistorical) {
                    $createdHistorical++;
                } else {
                    $createdMatched++;
                }
                
                $bar->advance();
            }

            DB::connection('tenant')->commit();
            $bar->finish();
            $this->newLine(2);
            
            $this->table(
                ['Total CSV Rows', 'Mapped to Existing Sale', 'Mapped to Customer (New Hist. Sale)', 'Duplicates Skipped'], 
                [[count($rows), $createdMatched, $createdHistorical, $skippedDup]]
            );

            if (!empty($unmappedMethods)) {
                $this->warn("\n⚠️ Notice: The following payment types were imported but are missing from Filament Settings:");
                foreach (array_unique($unmappedMethods) as $unmapped) {
                    $this->line(" - " . $unmapped);
                }
            }

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            $this->error("Error: " . $e->getMessage());
        }
    }

    private function mapDynamicPaymentType(string $rawType, array $activeMethods, array &$unmappedTracker): string
    {
        $cleanRaw = strtolower(trim($rawType));
        if (isset($activeMethods[$cleanRaw])) return $activeMethods[$cleanRaw];

        $aliases = ['debit card' => 'debit', 'credit card' => 'credit', 'cheque' => 'check', 'lay-by' => 'laybuy', 'lay by' => 'laybuy'];
        $aliased = $aliases[$cleanRaw] ?? $cleanRaw;

        foreach ($activeMethods as $key => $actualMethod) {
            if (str_contains($key, $aliased) || str_contains($aliased, $key)) return $actualMethod; 
        }

        $unmappedTracker[] = $rawType; 
        return strtoupper(trim($rawType));
    }

    private function parseDate(?string $date): ?string
    {
        if (empty($date)) return null;
        try { return Carbon::parse($date)->format('Y-m-d'); } catch (\Exception $e) { return null; }
    }
}