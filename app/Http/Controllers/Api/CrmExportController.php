<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Store;
use App\Models\CustomOrder;
use App\Models\Repair;
use Illuminate\Support\Carbon;

/**
 * ============================================================
 * CRM Export API Controller
 * ============================================================
 *
 * Endpoint: GET /api/v1/crm/daily-export
 *
 * ──────────────────────────────────────────────────────────
 * QUERY PARAMETER REFERENCE
 * ──────────────────────────────────────────────────────────
 *
 * ① DATA SCOPE (choose one or combine):
 *
 *    ?resources=customers,sales,repairs,custom_orders
 *       → Comma-separated list of resources to include.
 *         Defaults to ALL resources if omitted.
 *
 *    ?include_deleted=true
 *       → Also return soft-deleted records.
 *
 * ──────────────────────────────────────────────────────────
 *
 * ② DATE / SYNC MODE (choose exactly one):
 *
 *    ?updated_since=2026-04-14T15:30:00Z          [MODE 1 – Real-Time Sync]
 *    ?start_date=2026-01-01&end_date=2026-03-31   [MODE 2 – Historical Range]
 *    ?date=2026-04-12                             [MODE 3 – Single Day]
 *
 * ──────────────────────────────────────────────────────────
 *
 * ③ PAGINATION:
 *
 *    ?per_page=100&page=1
 *       → Default: 250 per page, max: 1000.
 *
 * ──────────────────────────────────────────────────────────
 *
 * RESPONSE STRUCTURE:
 * {
 *   "store_id":  "...",
 *   "mode":      "customers_only | real_time_sync | historical_range | single_day",
 *   "metadata":  { "records_returned": {...}, "pagination": {...}, "timestamp_utc": "..." },
 *   "data":      { "customers": [...], "sales": [...], ... }
 * }
 *
 * ============================================================
 */
class CrmExportController extends Controller
{
    private const DEFAULT_PER_PAGE = 250;
    private const MAX_PER_PAGE     = 1000;
    private const ALL_RESOURCES    = ['customers', 'sales', 'custom_orders', 'repairs'];

    public function export(Request $request)
    {
        $tz = Store::first()?->timezone ?? config('app.timezone', 'UTC');

        $requestedResources = $this->resolveResources($request);

        $perPage = min((int) $request->query('per_page', self::DEFAULT_PER_PAGE), self::MAX_PER_PAGE);
        $page    = max((int) $request->query('page', 1), 1);

        $includeTrashed = $request->boolean('include_deleted', false);

        $queries = $this->buildBaseQueries($requestedResources, $includeTrashed);

        [$queries, $mode, $filterMeta] = $this->applyDateFilters($request, $queries, $tz, $requestedResources);

        [$results, $paginationMeta] = $this->executeQueries($queries, $requestedResources, $perPage, $page);

        return response()->json([
            'store_id' => tenant('id'),
            'mode'     => $mode,
            'metadata' => [
                'resources_requested' => $requestedResources,
                'records_returned'    => array_map(
                    fn($items) => is_array($items) ? count($items) : count($items),
                    $results
                ),
                'pagination'    => $paginationMeta,
                'filter'        => $filterMeta,
                'timestamp_utc' => now()->utc()->toIso8601String(),
            ],
            'data' => $results,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Private Helpers
    // ────────────────────────────────────────────────────────────────────────

    private function resolveResources(Request $request): array
    {
        if (!$request->has('resources')) {
            return self::ALL_RESOURCES;
        }

        $requested = array_map('trim', explode(',', strtolower($request->query('resources'))));
        $valid     = array_intersect($requested, self::ALL_RESOURCES);

        return empty($valid) ? self::ALL_RESOURCES : array_values($valid);
    }

    private function buildBaseQueries(array $resources, bool $includeTrashed): array
    {
        $queries = [];

        if (in_array('customers', $resources)) {
            $q = Customer::query();
            if ($includeTrashed && method_exists(Customer::class, 'withTrashed')) $q->withTrashed();
            $queries['customers'] = $q;
        }

        if (in_array('sales', $resources)) {
            // ── Load items.productItem so we can map barcode, department etc ──
            $q = Sale::with(['items.productItem', 'payments', 'customer']);
            if ($includeTrashed && method_exists(Sale::class, 'withTrashed')) $q->withTrashed();
            $queries['sales'] = $q;
        }

        if (in_array('custom_orders', $resources)) {
            $q = CustomOrder::with(['payments', 'customer']);
            if ($includeTrashed && method_exists(CustomOrder::class, 'withTrashed')) $q->withTrashed();
            $queries['custom_orders'] = $q;
        }

        if (in_array('repairs', $resources)) {
            $q = Repair::with(['customer']);
            if ($includeTrashed && method_exists(Repair::class, 'withTrashed')) $q->withTrashed();
            $queries['repairs'] = $q;
        }

        return $queries;
    }

    private function applyDateFilters(Request $request, array $queries, string $tz, array $resources): array
    {
        $filterMeta = [];

        // Customers-only with no date filter → return all customers
        if ($resources === ['customers'] && !$request->hasAny(['updated_since', 'start_date', 'date'])) {
            $filterMeta = ['note' => 'No date filter applied — returning all customers.'];
            return [$queries, 'customers_only', $filterMeta];
        }

        // MODE 1: Real-Time Sync
        if ($request->has('updated_since')) {
            try {
                $sinceUtc   = Carbon::parse($request->query('updated_since'))->utc();
                $filterMeta = ['updated_since_utc' => $sinceUtc->toIso8601String()];
                foreach ($queries as $key => $q) {
                    $queries[$key] = $q->where('updated_at', '>=', $sinceUtc);
                }
                return [$queries, 'real_time_sync', $filterMeta];
            } catch (\Exception $e) {
                abort(400, 'Invalid updated_since format. Use ISO-8601 (e.g. 2026-04-14T15:30:00Z).');
            }
        }

        // MODE 2: Historical Range
        if ($request->has('start_date') && $request->has('end_date')) {
            try {
                $startUtc   = Carbon::createFromFormat('Y-m-d', $request->query('start_date'), $tz)->startOfDay()->utc();
                $endUtc     = Carbon::createFromFormat('Y-m-d', $request->query('end_date'), $tz)->endOfDay()->utc();
                $filterMeta = ['start_utc' => $startUtc->toIso8601String(), 'end_utc' => $endUtc->toIso8601String()];
                foreach ($queries as $key => $q) {
                    $queries[$key] = $q->whereBetween('created_at', [$startUtc, $endUtc]);
                }
                return [$queries, 'historical_range', $filterMeta];
            } catch (\Exception $e) {
                abort(400, 'Invalid date format. Use YYYY-MM-DD for start_date and end_date.');
            }
        }

        // MODE 3: Single Day (or today)
        try {
            $dateString = $request->query('date', Carbon::now($tz)->format('Y-m-d'));
            $startUtc   = Carbon::createFromFormat('Y-m-d', $dateString, $tz)->startOfDay()->utc();
            $endUtc     = Carbon::createFromFormat('Y-m-d', $dateString, $tz)->endOfDay()->utc();
            $filterMeta = ['date' => $dateString, 'start_utc' => $startUtc->toIso8601String(), 'end_utc' => $endUtc->toIso8601String()];
            foreach ($queries as $key => $q) {
                $queries[$key] = $q->whereBetween('created_at', [$startUtc, $endUtc]);
            }
            return [$queries, 'single_day', $filterMeta];
        } catch (\Exception $e) {
            abort(400, 'Invalid date format. Use YYYY-MM-DD.');
        }
    }

    private function executeQueries(array $queries, array $resources, int $perPage, int $page): array
    {
        $results        = [];
        $paginationMeta = [];

        foreach ($queries as $key => $query) {
            $total = (clone $query)->count();
            $items = $query->forPage($page, $perPage)->get();

            // ── Serialize each resource with explicit, consistent field names ──
            $results[$key] = match ($key) {
                'sales'         => $this->serializeSales($items),
                'customers'     => $this->serializeCustomers($items),
                'custom_orders' => $this->serializeCustomOrders($items),
                'repairs'       => $this->serializeRepairs($items),
                default         => $items->toArray(),
            };

            $paginationMeta[$key] = [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $perPage),
            ];
        }

        // Fill zero-counts for unrequested resources
        foreach (self::ALL_RESOURCES as $resource) {
            if (!in_array($resource, $resources)) {
                $results[$resource]        = [];
                $paginationMeta[$resource] = null;
            }
        }

        return [$results, $paginationMeta];
    }

    // ────────────────────────────────────────────────────────────────────────
    // Serializers — explicit shape so JewelTagSyncService always gets
    // predictable snake_case keys and correctly typed values.
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Serialize sales with items mapped under the key "items", each item
     * having a nested "product_item" (snake_case) key.
     *
     * Fixes:
     *  - sales_person_list: always a clean comma-separated STRING (VARCHAR match)
     *  - item.sold_price: post-discount value (sale_price_override → computed)
     *  - item.product_item: snake_case key with all fields the CRM needs
     *  - purchase_date: completed_at preferred, then created_at
     */
    private function serializeSales($sales): array
    {
        return $sales->map(function (Sale $sale) {
            // ── sales_person_list: normalize to a clean comma-separated string ──
            // The DB column is VARCHAR; Eloquent returns it as a string.
            // Normalize spacing so "Alice,Bob" → "Alice, Bob"
            $rawStaff    = $sale->sales_person_list ?? '';
            $staffString = implode(', ', array_filter(array_map('trim', explode(',', $rawStaff))));

            return [
                'id'               => $sale->id,
                'invoice_number'   => $sale->invoice_number,
                'status'           => $sale->status,
                'payment_method'   => $sale->payment_method,
                'subtotal'         => (float) $sale->subtotal,
                'tax_amount'       => (float) $sale->tax_amount,
                'discount_amount'  => (float) $sale->discount_amount,
                'final_total'      => (float) $sale->final_total,
                'amount_paid'      => (float) $sale->amount_paid,
                'balance_due'      => (float) $sale->balance_due,

                // ── Always a plain string — matches your VARCHAR column ────────
                'sales_person_list' => $staffString,

                // ── Prefer completed_at for the "when was this sale done" date ─
                'completed_at' => $sale->completed_at?->toIso8601String(),
                'created_at'   => $sale->created_at?->toIso8601String(),
                'updated_at'   => $sale->updated_at?->toIso8601String(),

                // ── Customer nested object ─────────────────────────────────────
                'customer' => $sale->customer ? $this->serializeCustomerInline($sale->customer) : null,

                // ── Items with snake_case product_item key ────────────────────
                'items' => $sale->items->map(function ($item) {
                    $product = $item->productItem;

                    // Accurate post-discount price:
                    // Prefer sale_price_override (explicit line total after discount),
                    // fallback to (sold_price × qty) - discount_amount
                    $qty          = max(1, (int) ($item->qty ?? 1));
                    $unitPrice    = (float) ($item->sold_price ?? 0);
                    $discountAmt  = (float) ($item->discount_amount ?? 0);
                    $override     = $item->sale_price_override !== null
                        ? (float) $item->sale_price_override
                        : null;
                    $effectivePrice = $override ?? (($unitPrice * $qty) - $discountAmt);
                    $effectivePrice = max(0, round($effectivePrice, 2));

                    return [
                        'id'                  => $item->id,
                        'qty'                 => $qty,
                        'sold_price'          => $effectivePrice,   // post-discount line total
                        'unit_price'          => $unitPrice,        // raw unit price before discount
                        'sale_price_override' => $override,
                        'discount_amount'     => $discountAmt,
                        'discount_percent'    => (float) ($item->discount_percent ?? 0),
                        'custom_description'  => $item->custom_description,
                        'stock_no_display'    => $item->stock_no_display,
                        'is_non_stock'        => (bool) $item->is_non_stock,
                        'is_tax_free'         => (bool) ($item->is_tax_free ?? false),
                        'repair_id'           => $item->repair_id,
                        'custom_order_id'     => $item->custom_order_id,

                        // ── snake_case key — matches $item['product_item'] in service ──
                        'product_item' => $product ? [
                            'id'              => $product->id,
                            'barcode'         => $product->barcode,
                            'custom_description' => $product->custom_description,
                            // Category chain — CRM uses the first non-null value
                            'department'      => $product->department,
                            'category'        => $product->category,
                            'sub_department'  => $product->sub_department,
                            'metal_type'      => $product->metal_type,
                            'retail_price'    => (float) ($product->retail_price ?? 0),
                            'cost_price'      => (float) ($product->cost_price ?? 0),
                        ] : null,
                    ];
                })->values()->all(),

                // ── Payments (for balance reconciliation) ─────────────────────
                'payments' => $sale->payments->map(fn($p) => [
                    'id'      => $p->id,
                    'amount'  => (float) $p->amount,
                    'method'  => strtoupper(trim($p->method ?? '')),
                    'paid_at' => $p->paid_at?->toIso8601String(),
                ])->values()->all(),
            ];
        })->values()->all();
    }

    /**
     * Serialize customers with consistent field names.
     */
    private function serializeCustomers($customers): array
    {
        return $customers->map(fn(Customer $c) => $this->serializeCustomerInline($c))->values()->all();
    }

    /**
     * Inline customer serializer — used both in serializeCustomers() and
     * nested inside sales/orders/repairs so the shape is always identical.
     */
    private function serializeCustomerInline(Customer $c): array
    {
        // Normalize phone to E.164 (+1XXXXXXXXXX)
        $rawPhone = $c->phone ?? $c->mobile ?? null;
        $mobile   = null;
        if (!empty($rawPhone)) {
            $clean  = preg_replace('/[^0-9]/', '', $rawPhone);
            $mobile = strlen($clean) === 10 ? '+1' . $clean : '+' . $clean;
        }

        return [
            'id'          => $c->id,
            'customer_no' => $c->customer_no,
            'name'        => $c->name      ?? $c->first_name ?? 'Unknown',
            'last_name'   => $c->last_name ?? '',
            'phone'       => $mobile,
            'mobile'      => $mobile,
            'email'       => $c->email    ?? null,
            'address'     => $c->street   ?? null,
            'city'        => $c->city     ?? $c->suburb_city ?? null,
            'postcode'    => $c->postcode ?? null,
            'dob'         => $c->dob      ?? $c->birthdate  ?? null,
            'created_at'  => $c->created_at?->toIso8601String(),
            'updated_at'  => $c->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Serialize custom orders.
     * sold_price uses final_total → quoted_price → amount_paid chain.
     */
    private function serializeCustomOrders($orders): array
    {
        return $orders->map(function (CustomOrder $order) {
            $rawStaff    = $order->sales_person_list ?? $order->staff_name ?? '';
            $staffString = implode(', ', array_filter(array_map('trim', explode(',', $rawStaff))));

            return [
                'id'               => $order->id,
                'order_number'     => $order->order_no ?? ('ORDER-' . $order->id),
                'invoice_number'   => $order->invoice_number ?? null,
                'status'           => $order->status,
                'product_name'     => $order->product_name,
                'metal_type'       => $order->metal_type,
                'quoted_price'     => (float) ($order->quoted_price ?? 0),
                'final_total'      => (float) ($order->final_total ?? $order->quoted_price ?? 0),
                'amount_paid'      => (float) ($order->amount_paid ?? 0),
                'balance_due'      => (float) ($order->balance_due ?? 0),
                'sales_person_list' => $staffString,
                'completed_at'     => $order->completed_at?->toIso8601String() ?? null,
                'created_at'       => $order->created_at?->toIso8601String(),
                'updated_at'       => $order->updated_at?->toIso8601String(),
                'customer'         => $order->customer ? $this->serializeCustomerInline($order->customer) : null,
                'payments'         => $order->payments->map(fn($p) => [
                    'id'      => $p->id,
                    'amount'  => (float) $p->amount,
                    'method'  => strtoupper(trim($p->method ?? '')),
                    'paid_at' => $p->paid_at?->toIso8601String(),
                ])->values()->all(),
            ];
        })->values()->all();
    }

    /**
     * Serialize repairs.
     * sold_price uses total_price → quoted_price → amount_paid chain.
     */
    private function serializeRepairs($repairs): array
    {
        return $repairs->map(function (Repair $repair) {
            $rawStaff    = $repair->sales_person_list ?? $repair->staff_name ?? '';
            $staffString = implode(', ', array_filter(array_map('trim', explode(',', $rawStaff))));

            return [
                'id'               => $repair->id,
                'repair_number'    => $repair->repair_no ?? $repair->repair_number ?? ('REPAIR-' . $repair->id),
                'invoice_number'   => $repair->invoice_number ?? null,
                'status'           => $repair->status,
                'description'      => $repair->description ?? $repair->job_instructions ?? null,
                'total_price'      => (float) ($repair->total_price ?? $repair->quoted_price ?? $repair->amount_paid ?? 0),
                'quoted_price'     => (float) ($repair->quoted_price ?? 0),
                'amount_paid'      => (float) ($repair->amount_paid ?? 0),
                'balance_due'      => (float) ($repair->balance_due ?? 0),
                'sales_person_list' => $staffString,
                'completed_at'     => $repair->completed_at?->toIso8601String() ?? null,
                'created_at'       => $repair->created_at?->toIso8601String(),
                'updated_at'       => $repair->updated_at?->toIso8601String(),
                'customer'         => $repair->customer ? $this->serializeCustomerInline($repair->customer) : null,
            ];
        })->values()->all();
    }
}