<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\ProductItem;
use App\Models\Store;
use App\Models\Repair;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportOnSwimSales extends Command
{
    protected $signature = 'import:onswim-sales {tenant} {--rollback}';
    protected $description = 'Import sales and link them to customers using Sales CSV data';

    public function handle()
    {
        $tenantId = $this->argument('tenant');
        
        try {
            $tenant = \App\Models\Tenant::findOrFail($tenantId);
            tenancy()->initialize($tenant);
        } catch (\Exception $e) {
            $this->error("Could not find or initialize tenant: {$tenantId}");
            return;
        }

        if ($this->option('rollback')) {
            $this->warn("Rolling back sales for tenant: {$tenantId}...");
            Schema::disableForeignKeyConstraints();
            SaleItem::truncate();
            Sale::truncate();
            Schema::enableForeignKeyConstraints();
            $this->info("Sales and Items tables truncated successfully.");
            return;
        }

        $directoryPath = storage_path("app/data/{$tenantId}");
        $filePath = "{$directoryPath}/sales_dsqdata_feb27_26.csv";

        if (!file_exists($filePath)) {
            $this->error("Sales file not found at: {$filePath}");
            return;
        }

        $storeId = Store::first()?->id ?? 1;
        $file = fopen($filePath, 'r');
        $headers = array_map('trim', fgetcsv($file));
        
        $rows = [];
        while (($row = fgetcsv($file)) !== FALSE) {
            $rows[] = array_combine($headers, $row);
        }
        fclose($file);

        $salesGroups = collect($rows)->groupBy('Job No.');
        $bar = $this->output->createProgressBar($salesGroups->count());

        DB::connection('tenant')->beginTransaction();

        try {
            foreach ($salesGroups as $jobNo => $items) {
                $firstItem = $items->first();
                
                // 🚀 LINKING LOGIC: Uses the customer data embedded in the sales row
                $customer = $this->findCustomer($firstItem);
                
                $purchaseDate = !empty($firstItem['Purchase Date']) ? Carbon::parse($firstItem['Purchase Date']) : now();
                $invoiceNo = 'D' . $purchaseDate->format('mdy') . str_pad($jobNo, 4, '0', STR_PAD_LEFT);

                $staff = array_filter([
                    trim($firstItem['Sales Assistant'] ?? null),
                    trim($firstItem['Sales Assistant 2'] ?? null)
                ]);

                $sale = Sale::updateOrCreate(
                    ['invoice_number' => $invoiceNo],
                    [
                        'customer_id' => $customer?->id,
                        'store_id' => $storeId,
                        'status' => 'completed',
                        'sales_person_list' => array_values(array_unique($staff)) ?: ['System'],
                        'created_at' => $purchaseDate,
                        'payment_method' => 'cash',
                        'is_split_payment' => false,
                        'repair_number' => $firstItem['Repair Number'] ?? null,
                    ]
                );

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

                    $repairId = !empty($item['Repair Number']) 
                        ? Repair::where('repair_no', $item['Repair Number'])->value('id') 
                        : null;

                    $sale->items()->create([
                        'product_item_id' => ProductItem::where('barcode', $item['Stock/Item No.'])->value('id'),
                        'repair_id' => $repairId,
                        'custom_description' => $item['Description'] ?? 'Item',
                        'job_description' => $item['Job Description'] ?? null,
                        'qty' => $qty,
                        'sold_price' => $soldPrice,
                        'discount_amount' => $discAmount,
                        'discount_percent' => floatval($item['Discount'] ?? 0),
                        'is_manual' => empty($item['Stock/Item No.']),
                        'store_id' => $storeId,
                    ]);

                    $subtotal += ($soldPrice * $qty);
                    $taxTotal += $taxAmount;
                    $discountTotal += $discAmount;
                }

                $finalTotal = ($subtotal + $taxTotal) - $tradeInTotal;

                $sale->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxTotal,
                    'discount_amount' => $discountTotal,
                    'has_trade_in' => $tradeInTotal > 0,
                    'trade_in_value' => $tradeInTotal,
                    'trade_in_description' => rtrim($tradeInDesc, '; '),
                    'trade_in_receipt_no' => $tradeInTotal > 0 ? 'TRD-' . $jobNo : null,
                    'final_total' => $finalTotal,
                    'payment_method_1' => 'cash',
                    'payment_amount_1' => $finalTotal,
                ]);

                $bar->advance();
            }

            DB::connection('tenant')->commit();
            $bar->finish();
            $this->newLine();
            $this->info("Sales Import Successful.");

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            $this->error("\nImport Error: " . $e->getMessage());
        }
    }

    private function findCustomer($data)
    {
        // 1. Search by Customer Number (Exact Match)
        $onSwimCustNo = trim($data['Customer No.'] ?? '');
        if (!empty($onSwimCustNo)) {
            $found = Customer::where('customer_no', 'CUST-' . $onSwimCustNo)->first();
            if ($found) return $found;
        }

        // 2. Search by Email
        $email = trim($data['Email'] ?? '');
        if (!empty($email)) {
            $found = Customer::where('email', $email)->first();
            if ($found) return $found;
        }

        // 3. Search by Mobile/Phone
        $rawPhone = !empty(trim($data['Mobile'] ?? '')) ? $data['Mobile'] : ($data['Customer Ph'] ?? null);
        if (!empty($rawPhone)) {
            $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
            $found = Customer::where('phone', 'like', "%{$cleanPhone}%")->first();
            if ($found) return $found;
        }

        return null;
    }
}