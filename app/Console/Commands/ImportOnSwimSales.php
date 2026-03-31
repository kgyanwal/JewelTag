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
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImportOnSwimSales extends Command
{
    /**
     * Signature restored with all options
     */
    protected $signature = 'import:onswim-sales {tenant} {--rollback} {--dry-run}';
    protected $description = 'Import sales with full pre-calculation and duplicate detection';

    public function handle()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1G');

        $tenantId = $this->argument('tenant');
        $isDryRun = $this->option('dry-run');

        try {
            $tenant = Tenant::findOrFail($tenantId);
            tenancy()->initialize($tenant);

            // 🚀 WORKING PATH: storage/lxd/data/
            $directoryPath = storage_path("/{$tenantId}/data");
            
            // 🚀 WORKING DATE PATTERN: sales_2026_03_30.csv
            $yesterday = Carbon::yesterday()->format('Y_m_d'); 
            $fileName = "sales_{$yesterday}.csv";
            $filePath = "{$directoryPath}/{$fileName}";

            if (!File::exists($filePath)) {
                $this->error("❌ FILE NOT FOUND: {$filePath}");
                return Command::SUCCESS;
            }

        } catch (\Exception $e) {
            $this->error("Initialization Error: " . $e->getMessage());
            return Command::FAILURE;
        }

        // Handle Rollback
        if ($this->option('rollback')) {
            $this->warn("Rolling back sales for tenant: {$tenantId}...");
            Schema::disableForeignKeyConstraints();
            SaleItem::truncate();
            Sale::truncate();
            Schema::enableForeignKeyConstraints();
            $this->info("Sales tables truncated.");
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

        // Grouping logic for single invoice creation
        $salesGroups = collect($rows)->groupBy(fn($item) => 
            trim($item['Job No.'] ?? '0') . '-' . trim($item['Purchase Date'] ?? '0') . '-' . trim($item['Customer No.'] ?? 'WALKIN')
        );

        $this->info("Processing " . $salesGroups->count() . " sales...");
        $bar = $this->output->createProgressBar($salesGroups->count());
        
        // Stats tracking
        $created = 0; $skipped = 0; $updated = 0;

        DB::connection('tenant')->beginTransaction();

        try {
            foreach ($salesGroups as $uniqueKey => $items) {
                $firstItem = $items->first();
                
                // 🚀 DUAL-PHONE SEARCH (Home and Mobile addressed)
                $customer = $this->findCustomer($firstItem);
                $customerId = $customer?->id ?? $walkInCustomer->id;

                $purchaseDate = !empty($firstItem['Purchase Date']) ? Carbon::parse($firstItem['Purchase Date']) : now();
                $rawJobNo = trim($firstItem['Job No.'] ?? '');
                $jobNo = empty($rawJobNo) ? rand(10000, 99999) : $rawJobNo;

                // ── PRE-CALCULATE TOTAL for duplicate detection ──
                $preCalcSubtotal = 0;
                $preCalcTax = 0;
                foreach ($items as $preItem) {
                    $desc = strtoupper($preItem['Description'] ?? '');
                    if (str_contains($desc, 'TRADE IN')) continue;
                    $preCalcSubtotal += floatval($preItem['Sold Price'] ?? 0) * floatval($preItem['Qty'] ?? 1);
                    $preCalcTax += floatval($preItem['Tax Amount'] ?? 0);
                }
                $preCalcTotal = $preCalcSubtotal + $preCalcTax;

                // ── CHECK 1: Exact invoice number duplicate (Idempotency) ──
                $baseInvoiceNo = 'D' . $purchaseDate->format('mdy') . '-' . $jobNo;
                $invoiceNo = $baseInvoiceNo;
                
                // ── CHECK 2: Cross-format POS duplicate check ──
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

                if (!$isDryRun) {
                    // 🚀 CREATE/UPDATE PARENT SALE
                    $staff = array_filter([trim($firstItem['Sales Assistant'] ?? null), trim($firstItem['Sales Assistant 2'] ?? null)]);

                    $sale = Sale::withoutEvents(fn() => 
                        Sale::updateOrCreate(
                            ['invoice_number' => $invoiceNo],
                            [
                                'customer_id'       => $customerId,
                                'store_id'          => $storeId,
                                'status'            => 'completed',
                                'sales_person_list' => array_values(array_unique($staff)) ?: ['System'],
                                'created_at'        => $purchaseDate,
                                'payment_method'    => 'cash',
                                'repair_number'     => $firstItem['Repair Number'] ?? null,
                            ]
                        )
                    );

                    // Clear items to allow fresh overwrite
                    $sale->items()->delete();

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
                        ]);

                        $subtotal += ($soldPrice * $qty);
                        $taxTotal += $taxAmount;
                        $discountTotal += $discAmount;
                    }

                    $finalTotal = ($subtotal + $taxTotal) - $tradeInTotal;
                    $sale->update([
                        'subtotal'             => $subtotal,
                        'tax_amount'           => $taxTotal,
                        'discount_amount'      => $discountTotal,
                        'has_trade_in'         => $tradeInTotal > 0,
                        'trade_in_value'       => $tradeInTotal,
                        'trade_in_description' => rtrim($tradeInDesc, '; '),
                        'final_total'          => $finalTotal,
                        'payment_amount_1'     => $finalTotal,
                    ]);
                    $created++;
                }
                $bar->advance();
            }

            if ($isDryRun) {
                DB::connection('tenant')->rollBack();
                $bar->finish();
                $this->newLine(2);
                $this->info("✅ DRY RUN COMPLETE. No data changed.");
            } else {
                DB::connection('tenant')->commit();
                $bar->finish();
                $this->newLine(2);
                $this->info("✅ Sales Import Complete!");
                $this->table(
                    ['Created/Updated', 'Skipped (POS Duplicates)'],
                    [[$created, $skipped]]
                );
            }
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            $this->error("\nCritical Error: " . $e->getMessage());
        }
    }

    /**
     * 🚀 DUAL PHONE RESOLUTION: Checks Mobile and Customer Ph (Home)
     */
    private function findCustomer($data)
    {
        $onSwimCustNo = trim($data['Customer No.'] ?? '');
        $email = strtolower(trim($data['Email'] ?? ''));
        
        // Check both potential phone columns
        $rawMobile = trim($data['Mobile'] ?? '');
        $rawHome = trim($data['Customer Ph'] ?? '');
        
        $searchPhones = array_filter([
            preg_replace('/[^0-9]/', '', $rawMobile),
            preg_replace('/[^0-9]/', '', $rawHome)
        ]);

        if (!empty($onSwimCustNo)) {
            $found = Customer::withoutGlobalScopes()->where('customer_no', 'CUST-' . $onSwimCustNo)->first();
            if ($found) return $found;
        }
        if (!empty($email)) {
            $found = Customer::withoutGlobalScopes()->where('email', $email)->first();
            if ($found) return $found;
        }
        
        // Iterate through both Mobile and Home phone to find a match
        foreach ($searchPhones as $cleanPhone) {
            if (strlen($cleanPhone) >= 7) {
                $found = Customer::withoutGlobalScopes()->where('phone', 'like', "%{$cleanPhone}%")->first();
                if ($found) return $found;
            }
        }
        
        return null;
    }
}