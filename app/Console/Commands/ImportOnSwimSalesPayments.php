<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Store;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImportOnSwimSalesPayments extends Command
{
    // Removed {--rollback} to strictly prevent data deletion
    protected $signature = 'import:onswim-sales-payments {tenant} {--dry-run}';
    protected $description = 'Import OnSwim Sales Payments, map safely to Sales, or directly to Customers without data loss';

    public function handle()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1G');

        $tenantId = $this->argument('tenant');
        $isDryRun = $this->option('dry-run');
        
        try {
            $tenant = Tenant::findOrFail($tenantId);
            tenancy()->initialize($tenant);

            // Authenticate admin to satisfy potential activity_logs foreign key constraints
            $adminUser = User::first();
            if ($adminUser) {
                auth()->login($adminUser);
            }

            // Path & Dynamic File Logic
            $directoryPath = storage_path("/{$tenantId}/data");
            $yesterday = Carbon::yesterday()->format('Y_m_d'); 
            $fileName = "sales_payments_{$yesterday}.csv";
            $filePath = "{$directoryPath}/{$fileName}";

            if (!File::exists($filePath)) {
                $this->newLine();
                $this->error("❌ FILE NOT FOUND: {$filePath}");
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error("Initialization Error: " . $e->getMessage());
            return Command::FAILURE;
        }

        if ($isDryRun) {
            $this->newLine();
            $this->warn("🛠️  DRY RUN ENABLED: No changes will be saved to the database.");
            $this->newLine();
        }

        $defaultStoreId = Store::first()?->id ?? 1;

        // Ensure fallback customer exists to prevent NOT NULL customer_id constraint errors
        $walkInCustomer = Customer::withoutEvents(fn() => 
            Customer::firstOrCreate(
                ['customer_no' => 'WALKIN'], 
                ['name' => 'Walk-in', 'last_name' => 'Customer', 'is_active' => true]
            )
        );

        // 1. FETCH DYNAMIC PAYMENT METHODS
        $paymentMethodsJson = DB::table('site_settings')->where('key', 'payment_methods')->value('value');
        $activePaymentMethods = $paymentMethodsJson ? json_decode($paymentMethodsJson, true) : ['CASH', 'VISA'];
        
        $normalizedActiveMethods = [];
        foreach ($activePaymentMethods as $method) {
            $normalizedActiveMethods[strtolower(trim($method))] = $method;
        }

        // Read CSV
        $file = fopen($filePath, 'r');
        $headers = array_map('trim', fgetcsv($file));
        $rows = [];
        while (($row = fgetcsv($file)) !== false) {
            if (count($row) === count($headers)) {
                $rows[] = array_combine($headers, $row);
            }
        }
        fclose($file);

        $this->info("Building Job → Sale lookup map...");
        $jobToSale = [];
        Sale::select('id', 'invoice_number', 'customer_id', 'sales_person_list', 'store_id')
            ->chunk(500, function ($sales) use (&$jobToSale) {
                foreach ($sales as $sale) {
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
            $this->info("Processing " . count($rows) . " payment records...");
            $bar = $this->output->createProgressBar(count($rows));

            foreach ($rows as $row) {
                $jobNo = (int) trim($row['Job Number'] ?? 0);
                $amountRaw = trim($row['Amount'] ?? '0');
                $amount = (float) str_replace(['$', ',', ' ', '-'], '', $amountRaw);
                $paymentType = trim($row['Payment Type'] ?? 'cash');
                $saleType = strtolower(trim($row['Sale Type'] ?? 'sale'));

                if ($amount <= 0) { 
                    $bar->advance(); 
                    continue; 
                }

                $paymentDate = $this->parseDate($row['Date'] ?? null) ?? now()->format('Y-m-d');
                $mappedMethod = $this->mapDynamicPaymentType($paymentType, $normalizedActiveMethods, $unmappedMethods);
                $isLayby = in_array($saleType, ['layby', 'lay-by', 'deposit']) || strtolower($mappedMethod) === 'laybuy';

                // 🚀 DIRECT CUSTOMER MAPPING LOGIC
                $sale = $jobToSale[$jobNo] ?? null;
                $isHistorical = false;

                if (!$sale) {
                    $firstName = trim($row['First Name'] ?? '');
                    $lastName = trim($row['Last Name'] ?? '');
                    
                    // A. Find the Customer
                    $customer = null;
                    if (!empty($firstName) || !empty($lastName)) {
                        $customer = Customer::withoutGlobalScopes()
                            ->where('name', $firstName)
                            ->where('last_name', $lastName)
                            ->first();
                    }

                    // B. Create customer safely if not found, to preserve identity instead of lumping to Walk-in
                    if (!$customer && (!empty($firstName) || !empty($lastName))) {
                        $customer = Customer::withoutEvents(fn() => 
                            Customer::create([
                                'customer_no' => 'CUST-HIST-' . strtoupper(substr(md5($firstName . $lastName . time()), 0, 8)),
                                'name' => $firstName ?: 'Historical',
                                'last_name' => $lastName ?: 'Customer',
                                'is_active' => true,
                            ])
                        );
                    }
                    
                    $customerId = $customer ? $customer->id : $walkInCustomer->id;

                    // C. Create a "Historical Sale" shell to attach the payment to
                    $sale = Sale::withoutEvents(function () use ($jobNo, $customerId, $paymentDate, $defaultStoreId) {
                        return Sale::create([
                            'invoice_number' => 'HIST-' . $jobNo . '-' . strtoupper(Str::random(4)),
                            'customer_id' => $customerId,
                            'store_id' => $defaultStoreId,
                            'status' => 'completed',
                            'sales_person_list' => 'Historical Import',
                            'created_at' => $paymentDate,
                            'payment_method' => 'other',
                            'is_split_payment' => false,
                            'subtotal' => 0,
                            'tax_amount' => 0,
                            'final_total' => 0,
                            'discount_amount' => 0,
                            'amount_paid' => 0,
                            'balance_due' => 0,
                        ]);
                    });

                    // Add to map so subsequent payments for this job find it instantly
                    $jobToSale[$jobNo] = $sale;
                    $isHistorical = true;
                }

                // Safely extract Sales Person
                $salesPersonRaw = $sale->sales_person_list;
                $salesPerson = is_array($salesPersonRaw) ? ($salesPersonRaw[0] ?? 'System') : (is_string($salesPersonRaw) ? explode(' / ', $salesPersonRaw)[0] : 'System');
                if (empty(trim($salesPerson))) $salesPerson = 'System';

                // Duplicate Check ensures we never double-charge an imported payment
                $exists = SalePayment::where('sale_id', $sale->id)
                    ->where('amount', $amount)
                    ->where('payment_method', $mappedMethod)
                    ->where('payment_date', $paymentDate)
                    ->exists();

                if ($exists) { 
                    $skippedDup++; 
                    $bar->advance(); 
                    continue; 
                }

                if (!$isDryRun) {
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
                }
                
                $bar->advance();
            }

            if ($isDryRun) {
                DB::connection('tenant')->rollBack();
                $bar->finish();
                $this->newLine(2);
                $this->info("✅ DRY RUN COMPLETE. No data modified.");
            } else {
                DB::connection('tenant')->commit();
                $bar->finish();
                $this->newLine(2);
                $this->info("✅ SUCCESS: Sales Payments synchronized.");
            }
            
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
            $this->error("\nDatabase Error: " . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
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