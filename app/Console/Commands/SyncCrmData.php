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

        $lastSyncStr = DB::table('site_settings')->where('key', 'crm_last_sync')->value('value');
        $lastSyncUtc = $lastSyncStr
            ? Carbon::parse($lastSyncStr)
            : now()->subHour()->utc();

        $this->info("Fetching records updated since: " . $lastSyncUtc->toIso8601String());

        $sales = Sale::with(['items.productItem', 'payments', 'customer'])
            ->where('updated_at', '>=', $lastSyncUtc)
            ->whereNull('deleted_at')
            ->get();

        $customers = Customer::where('updated_at', '>=', $lastSyncUtc)->get();

        if ($sales->isEmpty() && $customers->isEmpty()) {
            $this->info("No new updates found.");
            return Command::SUCCESS;
        }

        $this->info("Found {$sales->count()} sales and {$customers->count()} customers to sync.");

        $synced = $this->syncCustomers($customers);
        $this->info("Customers synced: {$synced}");

        $rows = $this->syncSales($sales);
        $this->info("Sale rows synced: {$rows}");

        DB::table('site_settings')->updateOrInsert(
            ['key' => 'crm_last_sync'],
            ['value' => now()->utc()->toIso8601String(), 'updated_at' => now()]
        );

        $this->info("Sync complete!");
        return Command::SUCCESS;
    }

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
    // ONE row per sale, using final_total — matches JewelTag's own reports.
    // ─────────────────────────────────────────────────────────────────────────
    private function syncSales($sales): int
    {
        $count = 0;

        foreach ($sales as $sale) {
            $customer = $sale->customer;

            // ── Sales person list — store full comma-separated list ────────
            $rawStaff    = $sale->sales_person_list ?? '';
            $salesPerson = $this->parseSalesPerson($rawStaff);

            // ── Purchase date ─────────────────────────────────────────────
            $purchaseDate = null;
            foreach (['completed_at', 'created_at'] as $field) {
                if (!empty($sale->$field)) {
                    try { $purchaseDate = \Carbon\Carbon::parse($sale->$field); break; }
                    catch (\Exception $e) { continue; }
                }
            }
            $purchaseDate = $purchaseDate ?? now();

            // ── Customer info ─────────────────────────────────────────────
            $fullName = trim(
                trim($customer?->name ?? '') . ' ' . trim($customer?->last_name ?? '')
            ) ?: 'Walk-in';

            $mobile = null;
            if (!empty($customer?->phone)) {
                $clean  = preg_replace('/[^0-9]/', '', $customer->phone);
                $mobile = strlen($clean) === 10 ? '+1' . $clean : '+' . $clean;
            }

            // ── Category from first item ──────────────────────────────────
            $firstItem   = $sale->items->first();
            $product     = $firstItem?->productItem;
            $category    = $product?->department
                ?? $product?->category
                ?? $product?->sub_department
                ?? ($firstItem?->custom_description ? 'Custom Item' : 'Sale');

            // ── Sold price: store each staff member's SHARE ──────────────
            // Matches MySalesReport: final_total ÷ number of staff on sale.
            $staffParts = is_array($rawStaff)
                ? array_filter(array_map('trim', $rawStaff))
                : array_filter(array_map('trim', explode(',', (string) $rawStaff)));
            $staffCount = max(1, count($staffParts));
            $soldPrice  = round(floatval($sale->final_total ?? 0) / $staffCount, 2);

            CustomerPurchase::updateOrCreate(
                [
                    'invoice_number' => $sale->invoice_number,
                    'stock_number'   => 'SALE',
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

        return $count;
    }

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