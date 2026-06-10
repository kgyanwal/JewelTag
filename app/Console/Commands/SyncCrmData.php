<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\CustomerPurchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class SyncCrmData extends Command
{
    protected $signature   = 'crm:sync {--tenant= : The ID of the tenant}';
    protected $description = 'Sync newly updated sales and customers from JewelTag into the local CRM tables';

    public function handle()
    {
        $tenantId = $this->option('tenant');

        if (!$tenantId) {
            $this->error('You must specify a tenant ID (e.g., --tenant=lxd)');
            return Command::FAILURE;
        }

        tenancy()->initialize($tenantId);
        $this->info("Starting CRM sync for tenant: {$tenantId}");

        // ── 1. Determine the last sync time ───────────────────────────────────
        $lastSyncStr = DB::table('site_settings')->where('key', 'crm_last_sync')->value('value');
        $lastSyncUtc = $lastSyncStr
            ? Carbon::parse($lastSyncStr)
            : now()->subHour()->utc();

        $this->info("Fetching records updated since: " . $lastSyncUtc->toIso8601String());

        // ── 2. Fetch updated sales (with all relationships needed for CRM) ────
        $sales = Sale::with([
                'items.productItem',
                'payments',
                'customer',
            ])
            ->where('updated_at', '>=', $lastSyncUtc)
            ->whereNull('deleted_at')
            ->get();

        $customers = Customer::where('updated_at', '>=', $lastSyncUtc)->get();

        if ($sales->isEmpty() && $customers->isEmpty()) {
            $this->info("No new updates found.");
            return Command::SUCCESS;
        }

        $this->info("Found {$sales->count()} sales and {$customers->count()} customers to sync.");

        // ── 3. Upsert customers first so foreign references resolve ───────────
        $synced = $this->syncCustomers($customers);
        $this->info("Customers synced: {$synced}");

        // ── 4. Upsert sales into CustomerPurchase (one row per line item) ─────
        $rows = $this->syncSales($sales);
        $this->info("Sale rows synced: {$rows}");

        // ── 5. Record the successful sync timestamp ───────────────────────────
        DB::table('site_settings')->updateOrInsert(
            ['key' => 'crm_last_sync'],
            ['value' => now()->utc()->toIso8601String(), 'updated_at' => now()]
        );

        $this->info("Sync complete!");
        return Command::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Sync customers into the CRM customers table
    // ─────────────────────────────────────────────────────────────────────────
    private function syncCustomers($customers): int
    {
        $count = 0;

        foreach ($customers as $c) {
            $phoneValue = $c->phone ?? null;
            $mobile     = null;

            if (!empty($phoneValue)) {
                $clean  = preg_replace('/[^0-9]/', '', $phoneValue);
                $mobile = strlen($clean) === 10 ? '+1' . $clean : '+' . $clean;
            }

            $apiCustomerNo = $c->customer_no ?? null;
            if (!$apiCustomerNo) continue;

            \App\Models\Customer::updateOrCreate(
                ['customer_no' => $apiCustomerNo],
                [
                    'first_name'  => $c->name      ?? 'Unknown',
                    'last_name'   => $c->last_name ?? '',
                    'mobile'      => $mobile,
                    'email'       => $c->email    ?? null,
                    'street'      => $c->street   ?? null,
                    'suburb_city' => $c->city     ?? null,
                    'postcode'    => $c->postcode ?? null,
                    'birthdate'   => $c->dob      ?? null,
                ]
            );
            $count++;
        }

        return $count;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Sync sales into CustomerPurchase — one row per line item, matching
    // exactly what JewelTagSyncService::saveSaleToCrm() produces so the
    // CustomDashboard sees consistent data regardless of sync source.
    // ─────────────────────────────────────────────────────────────────────────
    private function syncSales($sales): int
    {
        $count = 0;

        foreach ($sales as $sale) {
            $customer = $sale->customer;

            // ── Sales person: VARCHAR comma-separated in your DB ──────────────
            $rawStaff    = $sale->sales_person_list ?? null;
            $salesPerson = $this->parseSalesPerson($rawStaff);

            // ── Purchase date: completed_at first, then created_at ────────────
            $purchaseDate = null;
            foreach (['completed_at', 'created_at'] as $field) {
                if (!empty($sale->$field)) {
                    try {
                        $purchaseDate = \Carbon\Carbon::parse($sale->$field);
                        break;
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
            $purchaseDate = $purchaseDate ?? now();

            // ── Customer name ─────────────────────────────────────────────────
            $fullName = trim(
                trim($customer?->name ?? '') . ' ' . trim($customer?->last_name ?? '')
            ) ?: 'Walk-in';

            // ── Mobile ────────────────────────────────────────────────────────
            $mobile = null;
            if (!empty($customer?->phone)) {
                $clean  = preg_replace('/[^0-9]/', '', $customer->phone);
                $mobile = strlen($clean) === 10 ? '+1' . $clean : '+' . $clean;
            }

            // ── One CRM row per sale item ─────────────────────────────────────
            foreach ($sale->items as $item) {
                $productItem = $item->productItem;

                // Accurate sold price respecting discounts and overrides
                $qty         = max(1, intval($item->qty ?? 1));
                $unitPrice   = floatval($item->sold_price ?? 0);
                $discountAmt = floatval($item->discount_amount ?? 0);
                $override    = ($item->sale_price_override !== null)
                    ? floatval($item->sale_price_override)
                    : null;

                $soldPrice = $override ?? (($unitPrice * $qty) - $discountAmt);
                $soldPrice = max(0, round($soldPrice, 2));

                // Category chain
                $category = $productItem?->department
                    ?? $productItem?->category
                    ?? $productItem?->sub_department
                    ?? ($item->custom_description ? 'Custom Item' : 'Sale');

                // Barcode
                $barcode = $productItem?->barcode
                    ?? $item->stock_no_display
                    ?? ('ITEM-' . $item->id);

                CustomerPurchase::updateOrCreate(
                    [
                        'invoice_number' => $sale->invoice_number,
                        'stock_number'   => $barcode,
                    ],
                    [
                        'customer_no'     => $customer?->customer_no,
                        'customer_name'   => $fullName,
                        'mobile'          => $mobile,
                        'email'           => $customer?->email ?? null,
                        'category'        => $category,
                        'sold_price'      => $soldPrice,
                        'purchase_date'   => $purchaseDate,
                        'sales_assistant' => $salesPerson,
                        'needs_followup'  => 'no',
                        'due_date'        => null,
                    ]
                );

                $count++;
            }

            // ── If no items, save a single summary row for the sale ───────────
            if ($sale->items->isEmpty()) {
                CustomerPurchase::updateOrCreate(
                    [
                        'invoice_number' => $sale->invoice_number,
                        'stock_number'   => 'SALE-' . $sale->id,
                    ],
                    [
                        'customer_no'     => $customer?->customer_no,
                        'customer_name'   => $fullName,
                        'mobile'          => $mobile,
                        'email'           => $customer?->email ?? null,
                        'category'        => 'Sale',
                        'sold_price'      => max(0, round(floatval($sale->final_total), 2)),
                        'purchase_date'   => $purchaseDate,
                        'sales_assistant' => $salesPerson,
                        'needs_followup'  => 'no',
                        'due_date'        => null,
                    ]
                );
                $count++;
            }
        }

        return $count;
    }

    /**
     * Parse sales_person_list whether it's a comma-separated VARCHAR string
     * or a JSON-decoded array — matches JewelTagSyncService::parseSalesPerson().
     */
    private function parseSalesPerson($raw): string
    {
        if (is_array($raw)) {
            $parsed = implode(', ', array_filter(array_map('trim', $raw)));
        } elseif (is_string($raw) && !empty($raw)) {
            $parsed = implode(', ', array_filter(array_map('trim', explode(',', $raw))));
        } else {
            $parsed = '';
        }

        return $parsed ?: 'System';
    }
}