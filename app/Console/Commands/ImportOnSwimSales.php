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
use Illuminate\Support\Str;

class ImportOnSwimSales extends Command
{
    protected $signature = 'import:onswim-sales {tenant} {--rollback}';
    protected $description = 'Import sales with duplicate detection across POS and OnSwim formats';

    public function handle()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1G');

        $tenantId = $this->argument('tenant');

        try {
            $tenant = \App\Models\Tenant::findOrFail($tenantId);
            tenancy()->initialize($tenant);
        } catch (\Exception $e) {
            $this->error("Could not find or initialize tenant: {$tenantId}");
            return;
        }

        $walkInCustomer = Customer::withoutEvents(function () {
            return Customer::firstOrCreate(
                ['customer_no' => 'CUST-WALKIN'],
                ['name' => 'Walk-in', 'last_name' => 'Customer', 'is_active' => true]
            );
        });

        if ($this->option('rollback')) {
            $this->warn("Rolling back sales for tenant: {$tenantId}...");
            Schema::disableForeignKeyConstraints();
            SaleItem::truncate();
            Sale::truncate();
            Schema::enableForeignKeyConstraints();
            $this->info("Sales tables truncated.");
            return;
        }

        $directoryPath = storage_path("{$tenantId}/data");
        $filePath      = "{$directoryPath}/sales_dsqdata_mar28_26.csv";

        if (!file_exists($filePath)) {
            $this->error("File not found at: {$filePath}");
            return;
        }

        $storeId = Store::first()?->id ?? 1;
        $file    = fopen($filePath, 'r');
        $headers = array_map('trim', fgetcsv($file));

        $rows = [];
        while (($row = fgetcsv($file)) !== false) {
            $rows[] = array_combine($headers, $row);
        }
        fclose($file);

        $salesGroups = collect($rows)->groupBy(function ($item) {
            $job  = trim($item['Job No.'] ?? '0');
            $date = trim($item['Purchase Date'] ?? '00-00-00');
            $cust = trim($item['Customer No.'] ?? 'WALKIN');
            return "{$job}-{$date}-{$cust}";
        });

        $this->info("Processing " . $salesGroups->count() . " individual sales...");
        $bar = $this->output->createProgressBar($salesGroups->count());

        // Track stats
        $created   = 0;
        $skipped   = 0;

        DB::connection('tenant')->beginTransaction();

        try {
            foreach ($salesGroups as $uniqueKey => $items) {
                $firstItem = $items->first();
                $customer  = $this->findCustomer($firstItem);
                $customerId = $customer?->id ?? $walkInCustomer->id;

                $purchaseDate = !empty($firstItem['Purchase Date'])
                    ? Carbon::parse($firstItem['Purchase Date'])
                    : now();

                $rawJobNo = trim($firstItem['Job No.'] ?? '');
                $jobNo    = empty($rawJobNo) ? rand(10000, 99999) : $rawJobNo;

                // ── PRE-CALCULATE TOTAL for duplicate detection ───────────
                // We need an approximate total BEFORE creating the sale
                // so we can compare against existing POS sales
                $preCalcSubtotal = 0;
                $preCalcTax      = 0;
                foreach ($items as $preItem) {
                    $desc = strtoupper($preItem['Description'] ?? '');
                    if (str_contains($desc, 'TRADE IN')) continue;
                    $preCalcSubtotal += floatval($preItem['Sold Price'] ?? 0) * floatval($preItem['Qty'] ?? 1);
                    $preCalcTax      += floatval($preItem['Tax Amount'] ?? 0);
                }
                $preCalcTotal = $preCalcSubtotal + $preCalcTax;

                // ── CHECK 1: Exact invoice number duplicate ────────────────
                $baseInvoiceNo = 'D' . $purchaseDate->format('mdy') . '-' . $jobNo;
                $invoiceNo     = $baseInvoiceNo;
                $counter       = 1;
                while (Sale::where('invoice_number', $invoiceNo)->exists()) {
                    $invoiceNo = $baseInvoiceNo . '-' . $counter;
                    $counter++;
                }

                // ── CHECK 2: Cross-format duplicate ───────────────────────
                // Detects when the same sale exists in two formats:
                //   OnSwim format: D030726-9260  (this import creates)
                //   POS format:    D030826004    (already in DB from POS)
                //
                // Match criteria:
                //   - Same customer_id
                //   - Same date (within same calendar day)
                //   - Final total within $1 tolerance (accounts for tax rounding)
                //   - Invoice number does NOT already contain the job number
                //     (avoids matching itself on re-runs)
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
                        $this->newLine();
                        $this->warn(
                            "  ⚠ SKIP Job #{$jobNo} — already exists as [{$crossFormatDuplicate->invoice_number}]" .
                                " | customer_id:{$customerId} | date:{$purchaseDate->toDateString()}" .
                                " | total:~\$" . number_format($preCalcTotal, 2)
                        );
                        $skipped++;
                        $bar->advance();
                        continue;
                    }
                }

                // ── CREATE SALE ───────────────────────────────────────────
                $staff = array_filter([
                    trim($firstItem['Sales Assistant'] ?? null),
                    trim($firstItem['Sales Assistant 2'] ?? null)
                ]);

                $sale = Sale::withoutEvents(function () use (
                    $invoiceNo,
                    $customerId,
                    $storeId,
                    $staff,
                    $purchaseDate,
                    $firstItem
                ) {
                    return Sale::create([
                        'invoice_number'   => $invoiceNo,
                        'customer_id'      => $customerId,
                        'store_id'         => $storeId,
                        'status'           => 'completed',
                        'sales_person_list' => array_values(array_unique($staff)) ?: ['System'],
                        'created_at'       => $purchaseDate,
                        'payment_method'   => 'cash',
                        'is_split_payment' => false,
                        'repair_number'    => $firstItem['Repair Number'] ?? null,
                        'subtotal'         => 0,
                        'tax_amount'       => 0,
                        'final_total'      => 0,
                        'discount_amount'  => 0,
                    ]);
                });

                // ── CREATE SALE ITEMS ─────────────────────────────────────
                $subtotal      = 0;
                $taxTotal      = 0;
                $discountTotal = 0;
                $tradeInTotal  = 0;
                $tradeInDesc   = '';

                foreach ($items as $item) {
                    $desc = strtoupper($item['Description'] ?? '');

                    if (str_contains($desc, 'TRADE IN')) {
                        $val           = abs(floatval($item['Discount Amount'] ?? 0));
                        $tradeInTotal += $val;
                        $tradeInDesc  .= ($item['Job Description'] ?? 'Trade-in') . '; ';
                        continue;
                    }

                    $qty       = floatval($item['Qty'] ?? 1);
                    $soldPrice = floatval($item['Sold Price'] ?? 0);
                    $taxAmount = floatval($item['Tax Amount'] ?? 0);
                    $discAmount = floatval($item['Discount Amount'] ?? 0);

                    $repairId = !empty($item['Repair Number'])
                        ? Repair::where('repair_no', $item['Repair Number'])->value('id')
                        : null;

                    $truncatedJobDesc = Str::limit(trim($item['Job Description'] ?? ''), 250, '');

                    SaleItem::withoutEvents(function () use (
                        $sale,
                        $item,
                        $repairId,
                        $qty,
                        $soldPrice,
                        $discAmount,
                        $storeId,
                        $truncatedJobDesc
                    ) {
                        $sale->items()->create([
                            'product_item_id'  => ProductItem::where('barcode', $item['Stock/Item No.'])->value('id'),
                            'repair_id'        => $repairId,
                            'custom_description' => $item['Description'] ?? 'Item',
                            'job_description'  => $truncatedJobDesc,
                            'qty'              => $qty,
                            'sold_price'       => $soldPrice,
                            'discount_amount'  => $discAmount,
                            'discount_percent' => floatval($item['Discount'] ?? 0),
                            'is_manual'        => empty($item['Stock/Item No.']),
                            'store_id'         => $storeId,
                        ]);
                    });

                    $subtotal      += ($soldPrice * $qty);
                    $taxTotal      += $taxAmount;
                    $discountTotal += $discAmount;
                }

                $finalTotal = ($subtotal + $taxTotal) - $tradeInTotal;

                Sale::withoutEvents(function () use (
                    $sale,
                    $subtotal,
                    $taxTotal,
                    $discountTotal,
                    $tradeInTotal,
                    $tradeInDesc,
                    $finalTotal
                ) {
                    $sale->update([
                        'subtotal'             => $subtotal,
                        'tax_amount'           => $taxTotal,
                        'discount_amount'      => $discountTotal,
                        'has_trade_in'         => $tradeInTotal > 0,
                        'trade_in_value'       => $tradeInTotal,
                        'trade_in_description' => rtrim($tradeInDesc, '; '),
                        'final_total'          => $finalTotal,
                        'payment_method_1'     => 'cash',
                        'payment_amount_1'     => $finalTotal,
                    ]);
                });

                $created++;
                $bar->advance();
            }

            DB::connection('tenant')->commit();
            $bar->finish();
            $this->newLine();
            $this->info("✅ Sales Import Complete!");
            $this->table(
                ['Created', 'Skipped (duplicates)'],
                [[$created, $skipped]]
            );

            if ($skipped > 0) {
                $this->warn("⚠  {$skipped} sales were skipped as duplicates of existing POS sales.");
                $this->line("   These already exist in your DB from the POS — no data was lost.");
            }
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            $this->error("\nImport Error: " . $e->getMessage());
            $this->error($e->getTraceAsString());
        }
    }

    private function findCustomer($data)
    {
        $onSwimCustNo = trim($data['Customer No.'] ?? '');
        if (!empty($onSwimCustNo)) {
            $found = Customer::withoutGlobalScopes()->where('customer_no', 'CUST-' . $onSwimCustNo)->first();
            if ($found) return $found;
        }

        $email = trim($data['Email'] ?? '');
        if (!empty($email)) {
            $found = Customer::withoutGlobalScopes()->where('email', $email)->first();
            if ($found) return $found;
        }

        $rawPhone = !empty(trim($data['Mobile'] ?? '')) ? $data['Mobile'] : ($data['Customer Ph'] ?? null);
        if (!empty($rawPhone)) {
            $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
            $found = Customer::withoutGlobalScopes()->where('phone', 'like', "%{$cleanPhone}%")->first();
            if ($found) return $found;
        }

        return null;
    }
}
