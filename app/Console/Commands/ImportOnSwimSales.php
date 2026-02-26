<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Customer;
use App\Models\ProductItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ImportOnSwimSales extends Command
{
    /**
     * Usage: php artisan import:onswim-sales lxdiamond
     */
    protected $signature = 'import:onswim-sales {tenant}';
    protected $description = 'Import sales and items from OnSwim CSV for a specific tenant';

    public function handle()
    {
        $tenantId = $this->argument('tenant');
        
        try {
            $tenant = \App\Models\Tenant::findOrFail($tenantId);
            tenancy()->initialize($tenant);
            $this->info("Tenant '{$tenantId}' initialized. Starting Sales Import...");
        } catch (\Exception $e) {
            $this->error("Could not find or initialize tenant: {$tenantId}");
            return;
        }

        $filePath = getenv('HOME') . '/Downloads/example.csv';

        if (!file_exists($filePath)) {
            $this->error("File not found at: {$filePath}");
            return;
        }

        $file = fopen($filePath, 'r');
        $headers = fgetcsv($file);
        
        $rows = [];
        while (($row = fgetcsv($file)) !== FALSE) {
            $rows[] = array_combine($headers, $row);
        }
        fclose($file);

        // Group rows by 'Job No.' to keep multi-item receipts together
        $salesGroups = collect($rows)->groupBy('Job No.');

        $this->info("Importing " . $salesGroups->count() . " unique transactions...");
        $bar = $this->output->createProgressBar($salesGroups->count());
        $bar->start();

        foreach ($salesGroups as $jobNo => $items) {
            try {
                $firstItem = $items->first();
                $customer = $this->getOrCreateCustomer($firstItem);
                $purchaseDate = $this->parseDate($firstItem['Purchase Date']);

                // Generate a unique invoice number to avoid collisions
                // Format: D + MMDDYY + JobNo (padded)
                $invoiceNo = 'D' . $purchaseDate->format('mdy') . str_pad($jobNo, 4, '0', STR_PAD_LEFT);

                // 1. Create the Main Sale Record
                $sale = Sale::updateOrCreate(
                    ['invoice_number' => $invoiceNo],
                    [
                        'customer_id' => $customer->id,
                        'store_id' => 1,
                        'status' => 'completed',
                        'sales_person_list' => [$firstItem['Sales Assistant'] ?? 'System'],
                        'created_at' => $purchaseDate,
                        'subtotal' => 0,
                        'tax_amount' => 0,
                        'final_total' => 0,
                        'payment_method' => 'cash',
                        'repair_number' => $firstItem['Repair Number'] ?? null,
                    ]
                );

                // 2. Clear old items if re-running to prevent duplicates
                $sale->items()->delete();

                $subtotal = 0;
                $taxTotal = 0;
                $tradeInTotal = 0;
                $tradeInDesc = '';

                // 3. Process each line item within the Job
                foreach ($items as $item) {
                    $description = strtoupper($item['Description'] ?? '');
                    
                    // HANDLE TRADE-INS
                    if (str_contains($description, 'TRADE IN')) {
                        $val = abs(floatval($item['Discount Amount'] ?? 0));
                        $tradeInTotal += $val;
                        $tradeInDesc .= ($item['Job Description'] ?: 'Trade-in Item') . '; ';
                        continue; 
                    }

                    // HANDLE REGULAR ITEMS
                    $qty = floatval($item['Qty'] ?? 1);
                    $soldPrice = floatval($item['Sold Price'] ?? 0);
                    $taxAmount = floatval($item['Tax Amount'] ?? 0);

                    $sale->items()->create([
                        'product_item_id' => $this->findProductId($item['Stock/Item No.']),
                        'custom_description' => $item['Description'],
                        'job_description' => $item['Job Description'] ?? null,
                        'qty' => $qty,
                        'sold_price' => $soldPrice,
                        'discount_amount' => floatval($item['Discount Amount'] ?? 0),
                        'discount_percent' => floatval($item['Discount'] ?? 0),
                        'is_manual' => empty($item['Stock/Item No.']),
                        'store_id' => 1,
                    ]);

                    $subtotal += ($soldPrice * $qty);
                    $taxTotal += $taxAmount;
                }

                // 4. Update the final math on the Sale
                $sale->update([
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxTotal,
                    'has_trade_in' => $tradeInTotal > 0,
                    'trade_in_value' => $tradeInTotal,
                    'trade_in_description' => rtrim($tradeInDesc, '; '),
                    'trade_in_receipt_no' => $tradeInTotal > 0 ? 'TRD-' . $jobNo : null,
                    'final_total' => ($subtotal + $taxTotal) - $tradeInTotal,
                ]);

            } catch (\Exception $e) {
                $this->error("\nError importing Job #{$jobNo}: " . $e->getMessage());
                // Continue to next Job instead of crashing
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Import process finished for {$tenantId}.");
    }

    /**
     * Helper: Handles Unique Constraint for Phone Numbers
     */
    private function getOrCreateCustomer($data)
    {
        // 1. Identify Email
        $email = !empty($data['Email']) ? $data['Email'] : 'no-email-' . ($data['Customer No.'] ?? rand(1000, 9999)) . '@example.com';

        // 2. Identify Phone (Convert empty strings to NULL to avoid unique index crash)
        $rawPhone = !empty($data['Mobile']) ? $data['Mobile'] : (!empty($data['Customer Ph']) ? $data['Customer Ph'] : null);
        $phone = $rawPhone ?: null;

        // 3. Return existing if found
        $existing = Customer::where('email', $email)->first();
        if ($existing) return $existing;

        // 4. Create New
        return Customer::create([
            'email' => $email,
            'name' => $data['Customer Name'] ?? 'Walk-in',
            'customer_no' => 'CUST-' . ($data['Customer No.'] ?? rand(1000, 9999)),
            'phone' => $phone,
            'address' => $data['Customer Address'] ?? null,
        ]);
    }

    private function findProductId($barcode)
    {
        if (empty($barcode)) return null;
        return ProductItem::where('barcode', $barcode)->value('id');
    }

    private function parseDate($date)
    {
        try {
            return Carbon::parse($date);
        } catch (\Exception $e) {
            return now();
        }
    }
}