<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\ProductItem;
use App\Models\Store;
use App\Models\Repair;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImportOnSwimSales extends Command
{
    /**
     * Signature restored with all functional flags + new date flag
     */
    protected $signature = 'import:onswim-sales {tenant} {--date= : The date of the CSV file} {--rollback} {--dry-run}';
    protected $description = 'Import sales with pre-calculation, cross-format duplicate detection, and dynamic file locator';

    public function handle()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1G');

        $tenantId = $this->argument('tenant');
        $isDryRun = $this->option('dry-run');

        try {
            $tenant = Tenant::findOrFail($tenantId);
            tenancy()->initialize($tenant);

            // 🛡️ FIX 1: Authenticate admin to satisfy activity_logs foreign key constraint
            $adminUser = User::first();
            if ($adminUser) {
                auth()->login($adminUser);
            }

            // ── DYNAMIC FILE PATH LOGIC (LATEST UPLOADED FILE) ────────────────────
            $directoryPath = storage_path("/{$tenantId}/data");
            $filePath = null;
            $fileName = null;

            if ($this->option('date')) {
                // If you manually specify a date, only look for that one
                $manualDate = $this->option('date');
                $possible = [
                    "{$directoryPath}/sales_{$manualDate}.csv", 
                    "{$directoryPath}/onswim_sales_{$manualDate}.csv",
                    "{$directoryPath}/sales_dsqdata_{$manualDate}.csv"
                ];
                foreach ($possible as $path) {
                    if (File::exists($path)) {
                        $filePath = $path;
                        $fileName = basename($path);
                        break;
                    }
                }
            } else {
                // Grab ANY file starting with "sales" or "onswim_sales" in that directory
                $allFiles = File::glob("{$directoryPath}/*.csv");
                $validFiles = [];
                
                foreach ($allFiles as $f) {
                    $base = basename($f);
                    // Match 'sales_...' or 'onswim_sales_...' but strictly IGNORE 'sales_payments_...'
                    if ((str_starts_with($base, 'sales_') && !str_starts_with($base, 'sales_payments_')) || str_starts_with($base, 'onswim_sales_')) {
                        $validFiles[] = $f;
                    }
                }
                
                if (!empty($validFiles)) {
                    // Sort the files by their last modified timestamp (newest first)
                    usort($validFiles, function($a, $b) {
                        return File::lastModified($b) <=> File::lastModified($a);
                    });
                    
                    // Grab the absolute newest file in the folder
                    $filePath = $validFiles[0]; 
                    $fileName = basename($filePath);
                }
            }

            if (!$filePath) {
                $this->error("❌ NO FILES FOUND in {$directoryPath}");
                $this->line("The script searched for 'sales_*.csv' or 'onswim_sales_*.csv'.");
                return Command::SUCCESS;
            }

            $this->info("📥 Using file: {$fileName}");

        } catch (\Exception $e) {
            $this->error("Initialization Error: " . $e->getMessage());
            return Command::FAILURE;
        }

        // Handle Rollback
        if ($this->option('rollback')) {
            $this->warn("Rolling back (deleting) ALL sales for tenant: {$tenantId}...");
            if ($this->confirm('Are you sure?', false)) {
                Schema::disableForeignKeyConstraints();
                SaleItem::truncate();
                Sale::truncate();
                Schema::enableForeignKeyConstraints();
                $this->info("Sales tables truncated.");
            }
            return;
        }

        $walkInCustomer = Customer::withoutEvents(fn() => 
            Customer::firstOrCreate(['customer_no' => 'WALKIN'], ['name' => 'Walk-in', 'last_name' => 'Customer', 'is_active' => true])
        );

        $storeId = Store::first()?->id ?? 1;
        $file = fopen($filePath, 'r');
        $headers = array_map('trim', fgetcsv($file));

        $rows = [];
        while (($row = fgetcsv($file)) !== false) {
            if (count($headers) !== count($row)) continue;
            $rows[] = array_combine($headers, $row);
        }
        fclose($file);

        // Grouping: One Job/Date/Customer combo = One Invoice
        $salesGroups = collect($rows)->groupBy(fn($item) => 
            trim($item['Job No.'] ?? '0') . '-' . trim($item['Purchase Date'] ?? '0') . '-' . trim($item['Customer No.'] ?? 'WALKIN')
        );

        $this->info("Processing " . $salesGroups->count() . " individual sales...");
        $bar = $this->output->createProgressBar($salesGroups->count());
        
        $created = 0; $skipped = 0;

        DB::connection('tenant')->beginTransaction();

        try {
            foreach ($salesGroups as $uniqueKey => $items) {
                $firstItem = $items->first();
                $customer = $this->findCustomer($firstItem);
                $customerId = $customer?->id ?? $walkInCustomer->id;

                $purchaseDate = !empty($firstItem['Purchase Date']) ? Carbon::parse($firstItem['Purchase Date']) : now();
                $rawJobNo = trim($firstItem['Job No.'] ?? '');
                $jobNo = empty($rawJobNo) ? rand(10000, 99999) : $rawJobNo;

                // ── 🛡️ RESTORED: PRE-CALCULATE TOTAL for duplicate detection ──
                $preCalcSubtotal = 0;
                $preCalcTax = 0;
                foreach ($items as $preItem) {
                    $desc = strtoupper($preItem['Description'] ?? '');
                    if (str_contains($desc, 'TRADE IN')) continue;
                    $preCalcSubtotal += floatval($preItem['Sold Price'] ?? 0) * floatval($preItem['Qty'] ?? 1);
                    $preCalcTax += floatval($preItem['Tax Amount'] ?? 0);
                }
                $preCalcTotal = $preCalcSubtotal + $preCalcTax;

                // ── 🚀 INVOICE NUMBER GENERATION ──
                $baseInvoiceNo = 'D' . $purchaseDate->format('mdy') . '-' . $jobNo;
                
                // If it starts with G or another letter from the original Job No, keep it clean
                $invoiceNo = is_numeric($jobNo) ? $baseInvoiceNo : $jobNo;

                // ── 🛡️ RESTORED: CROSS-FORMAT DUPLICATE CHECK ──
                if ($customerId !== $walkInCustomer->id && $preCalcTotal > 0) {
                    $crossFormatDuplicate = Sale::where('customer_id', $customerId)
                        ->whereBetween('created_at', [
                            $purchaseDate->copy()->subDays(2)->startOfDay(),
                            $purchaseDate->copy()->addDays(2)->endOfDay(),
                        ])
                        ->whereBetween('final_total', [
                            $preCalcTotal - 1.00,
                            $preCalcTotal + 1.00,
                        ])
                        ->where('invoice_number', 'not like', '%-' . $jobNo)
                        ->first();

                    if ($crossFormatDuplicate) {
                        $skipped++;
                        $bar->advance();
                        continue;
                    }
                }

                // 🚀 Count it as a successful processing (for both Dry and Real runs)
                $created++;

                if (!$isDryRun) {
                    // ── 🚀 FIX 2: Added 0 defaults to satisfy DB NOT NULL constraints ──
                    $sale = Sale::withoutEvents(fn() => 
                        Sale::updateOrCreate(
                            ['invoice_number' => $invoiceNo],
                            [
                                'customer_id'       => $customerId,
                                'store_id'          => $storeId,
                                'status'            => 'completed',
                                'sales_person_list' => array_filter([trim($firstItem['Sales Assistant'] ?? ''), trim($firstItem['Sales Assistant 2'] ?? '')]) ?: ['System'],
                                'created_at'        => $purchaseDate,
                                'payment_method'    => 'cash',
                                'repair_number'     => $firstItem['Repair Number'] ?? null,
                                'subtotal'          => 0,
                                'tax_amount'        => 0,
                                'final_total'       => 0,
                                'discount_amount'   => 0,
                                'amount_paid'       => 0,
                            ]
                        )
                    );

                    // Clear items for overwrite - bypasses heavy observers
                    SaleItem::withoutEvents(fn() => $sale->items()->delete());

                    $subtotal = 0; $taxTotal = 0; $discountTotal = 0; $tradeInTotal = 0; $tradeInDesc = '';

                    foreach ($items as $item) {
                        $desc = strtoupper($item['Description'] ?? '');

                        if (str_contains($desc, 'TRADE IN')) {
                            $val = abs(floatval($item['Discount Amount'] ?? 0));
                            $tradeInTotal += $val;
                            $tradeInDesc .= ($item['Job Description'] ?? 'Trade-in') . '; ';
                            continue;
                        }

                        $qty = floatval($item['Qty'] ?? 1);
                        $soldPrice = floatval($item['Sold Price'] ?? 0);
                        $taxAmount = floatval($item['Tax Amount'] ?? 0);
                        $discAmount = floatval($item['Discount Amount'] ?? 0);

                        $repairId = !empty($item['Repair Number']) ? Repair::where('repair_no', $item['Repair Number'])->value('id') : null;

                        // 🛡️ Bypasses Activity Log crashes on line-items
                        SaleItem::withoutEvents(fn() =>
                            $sale->items()->create([
                                'product_item_id'    => ProductItem::where('barcode', $item['Stock/Item No.'])->value('id'),
                                'repair_id'          => $repairId,
                                'custom_description' => $item['Description'] ?? 'Item',
                                'job_description'    => Str::limit(trim($item['Job Description'] ?? ''), 250, ''),
                                'qty'                => $qty,
                                'sold_price'         => $soldPrice,
                                'discount_amount'    => $discAmount,
                                'discount_percent'   => floatval($item['Discount'] ?? 0),
                                'is_manual'          => empty($item['Stock/Item No.']),
                                'store_id'           => $storeId,
                            ])
                        );

                        $subtotal += ($soldPrice * $qty);
                        $taxTotal += $taxAmount;
                        $discountTotal += $discAmount;
                    }

                    $finalTotal = ($subtotal + $taxTotal) - $tradeInTotal;
                    
                    Sale::withoutEvents(fn() =>
                        $sale->update([
                            'subtotal'             => $subtotal,
                            'tax_amount'           => $taxTotal,
                            'discount_amount'      => $discountTotal,
                            'has_trade_in'         => $tradeInTotal > 0,
                            'trade_in_value'       => $tradeInTotal,
                            'trade_in_description' => rtrim($tradeInDesc, '; '),
                            'final_total'          => $finalTotal,
                            'amount_paid'          => $finalTotal,
                            'payment_amount_1'     => $finalTotal,
                        ])
                    );
                }
                $bar->advance();
            }

            // 🚀 UNIVERSAL SUMMARY OUTPUT
            if ($isDryRun) {
                DB::connection('tenant')->rollBack();
                $bar->finish();
                $this->newLine(2);
                $this->info("✅ DRY RUN COMPLETE: No data was written.");
            } else {
                DB::connection('tenant')->commit();
                $bar->finish();
                $this->newLine(2);
                $this->info("✅ SUCCESS: Sales synchronized.");
            }

            // Show table for both dry and real runs
            $this->table(
                ['Metric', 'Count'], 
                [
                    [$isDryRun ? 'Would Create/Update' : 'Created/Updated', $created],
                    ['Skipped (Duplicates)', $skipped]
                ]
            );

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            $this->error("\nImport Error: " . $e->getMessage());
        }
    }

    /**
     * 🛡️ RESTORED: DUAL PHONE SEARCH
     */
    private function findCustomer($data)
    {
        $onSwimCustNo = trim($data['Customer No.'] ?? '');
        $email = strtolower(trim($data['Email'] ?? ''));
        $searchPhones = array_filter([
            preg_replace('/[^0-9]/', '', trim($data['Mobile'] ?? '')),
            preg_replace('/[^0-9]/', '', trim($data['Customer Ph'] ?? ''))
        ]);

        if (!empty($onSwimCustNo)) {
            $found = Customer::withoutGlobalScopes()->where('customer_no', 'CUST-' . $onSwimCustNo)->first();
            if ($found) return $found;
        }
        if (!empty($email)) {
            $found = Customer::withoutGlobalScopes()->where('email', $email)->first();
            if ($found) return $found;
        }
        foreach ($searchPhones as $cleanPhone) {
            if (strlen($cleanPhone) >= 7) {
                $found = Customer::withoutGlobalScopes()->where('phone', 'like', "%{$cleanPhone}")->first();
                if ($found) return $found;
            }
        }
        return null;
    }
}