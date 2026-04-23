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
 *         Use "customers" alone for a customers-only pull.
 *
 *    ?include_deleted=true
 *       → Also return soft-deleted records (requires SoftDeletes on models).
 *
 * ──────────────────────────────────────────────────────────
 *
 * ② DATE / SYNC MODE (choose exactly one):
 *
 *    ?updated_since=2026-04-14T15:30:00Z          [MODE 1 – Real-Time Sync]
 *       → Returns ALL resources updated after this UTC timestamp.
 *         Ideal for polling every 5–15 minutes from your CRM.
 *         Format: ISO-8601 UTC string.
 *
 *    ?start_date=2026-01-01&end_date=2026-03-31   [MODE 2 – Historical Range]
 *       → Returns records created within this date range (store local timezone).
 *         Ideal for backfilling historical data.
 *         Format: YYYY-MM-DD.
 *
 *    ?date=2026-04-12                             [MODE 3 – Single Day]
 *       → Returns records created on this specific date (store local timezone).
 *         Defaults to today if omitted.
 *         Format: YYYY-MM-DD.
 *
 * ──────────────────────────────────────────────────────────
 *
 * ③ PAGINATION (recommended for large datasets):
 *
 *    ?per_page=100&page=1
 *       → Paginate results. Default: 250 per page, max: 1000.
 *         When paginating, each resource is paginated independently.
 *
 * ──────────────────────────────────────────────────────────
 *
 * EXAMPLE CALLS:
 *
 *   # Pull all customers (no date filter):
 *   GET /api/v1/crm/daily-export?resources=customers
 *
 *   # Pull only customers updated in the last 15 minutes:
 *   GET /api/v1/crm/daily-export?resources=customers&updated_since=2026-04-23T10:00:00Z
 *
 *   # Pull all data for a specific day:
 *   GET /api/v1/crm/daily-export?date=2026-04-22
 *
 *   # Full backfill of sales + repairs for Q1:
 *   GET /api/v1/crm/daily-export?resources=sales,repairs&start_date=2026-01-01&end_date=2026-03-31
 *
 *   # Paginated real-time sync:
 *   GET /api/v1/crm/daily-export?updated_since=2026-04-23T09:45:00Z&per_page=100&page=2
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
    /** Default and maximum records per page */
    private const DEFAULT_PER_PAGE = 250;
    private const MAX_PER_PAGE     = 1000;

    /** All supported resource keys */
    private const ALL_RESOURCES = ['customers', 'sales', 'custom_orders', 'repairs'];

    public function export(Request $request)
    {
        // ── Resolve store timezone ────────────────────────────────────────
        $tz = Store::first()?->timezone ?? config('app.timezone', 'UTC');

        // ── Resolve which resources to return ────────────────────────────
        // ?resources=customers               → customers only
        // ?resources=customers,sales         → two resources
        // (omitted)                          → all four resources
        $requestedResources = $this->resolveResources($request);

        // ── Pagination ───────────────────────────────────────────────────
        $perPage = min((int) $request->query('per_page', self::DEFAULT_PER_PAGE), self::MAX_PER_PAGE);
        $page    = max((int) $request->query('page', 1), 1);

        // ── Include soft-deleted records? ────────────────────────────────
        $includeTrashed = $request->boolean('include_deleted', false);

        // ── Build base queries (only for requested resources) ─────────────
        $queries = $this->buildBaseQueries($requestedResources, $includeTrashed);

        // ── Determine sync mode & apply date filters ──────────────────────
        [$queries, $mode, $filterMeta] = $this->applyDateFilters($request, $queries, $tz, $requestedResources);

        // ── Execute queries with pagination ───────────────────────────────
        [$results, $paginationMeta] = $this->executeQueries($queries, $requestedResources, $perPage, $page);

        // ── Build response ────────────────────────────────────────────────
        return response()->json([
            'store_id' => tenant('id'),
            'mode'     => $mode,
            'metadata' => [
                'resources_requested' => $requestedResources,
                'records_returned'    => array_map(
                    fn($items) => is_array($items) ? count($items) : $items->count(),
                    $results
                ),
                'pagination'          => $paginationMeta,
                'filter'              => $filterMeta,
                'timestamp_utc'       => now()->utc()->toIso8601String(),
            ],
            'data' => $results,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Private Helpers
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Resolve and validate the requested resource list.
     */
    private function resolveResources(Request $request): array
    {
        if (!$request->has('resources')) {
            return self::ALL_RESOURCES;
        }

        $requested = array_map('trim', explode(',', strtolower($request->query('resources'))));
        $valid     = array_intersect($requested, self::ALL_RESOURCES);

        // If nothing valid was requested, fall back to all
        return empty($valid) ? self::ALL_RESOURCES : array_values($valid);
    }

    /**
     * Build Eloquent query objects for each requested resource.
     */
    private function buildBaseQueries(array $resources, bool $includeTrashed): array
    {
        $queries = [];

        if (in_array('customers', $resources)) {
            $q = Customer::query();
            if ($includeTrashed && method_exists(Customer::class, 'withTrashed')) {
                $q->withTrashed();
            }
            $queries['customers'] = $q;
        }

        if (in_array('sales', $resources)) {
            $q = Sale::with(['items.productItem', 'payments', 'customer']);
            if ($includeTrashed && method_exists(Sale::class, 'withTrashed')) {
                $q->withTrashed();
            }
            $queries['sales'] = $q;
        }

        if (in_array('custom_orders', $resources)) {
            $q = CustomOrder::with(['payments', 'customer']);
            if ($includeTrashed && method_exists(CustomOrder::class, 'withTrashed')) {
                $q->withTrashed();
            }
            $queries['custom_orders'] = $q;
        }

        if (in_array('repairs', $resources)) {
            $q = Repair::with(['customer']);
            if ($includeTrashed && method_exists(Repair::class, 'withTrashed')) {
                $q->withTrashed();
            }
            $queries['repairs'] = $q;
        }

        return $queries;
    }

    /**
     * Determine sync mode and apply the appropriate date filters.
     *
     * NOTE: Customers are intentionally NOT date-filtered when the mode is
     * "customers_only" — a standalone ?resources=customers pull returns ALL
     * customers (the typical CRM use-case). Date filters still apply when
     * customers are included alongside other resources.
     *
     * Returns: [filteredQueries, modeName, filterMetadata]
     */
    private function applyDateFilters(Request $request, array $queries, string $tz, array $resources): array
    {
        $filterMeta = [];

        // ── Customers-only shortcut (no date filter applied) ──────────────
        if ($resources === ['customers'] && !$request->has('updated_since') && !$request->has('start_date') && !$request->has('date')) {
            $filterMeta = ['note' => 'No date filter applied — returning all customers.'];
            return [$queries, 'customers_only', $filterMeta];
        }

        // ── MODE 1: Real-Time Sync (?updated_since) ───────────────────────
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

        // ── MODE 2: Historical Range (?start_date + ?end_date) ────────────
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

        // ── MODE 3: Single Day (?date or today) ───────────────────────────
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

    /**
     * Execute all queries with offset-based pagination.
     *
     * Returns: [results array, pagination metadata]
     */
    private function executeQueries(array $queries, array $resources, int $perPage, int $page): array
    {
        $results        = [];
        $paginationMeta = [];

        foreach ($queries as $key => $query) {
            $total          = (clone $query)->count();
            $items          = $query->forPage($page, $perPage)->get();
            $results[$key]  = $items;

            $paginationMeta[$key] = [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $perPage),
            ];
        }

        // Fill in zero-counts for resources that were NOT requested
        foreach (self::ALL_RESOURCES as $resource) {
            if (!in_array($resource, $resources)) {
                $results[$resource]        = [];
                $paginationMeta[$resource] = null;
            }
        }

        return [$results, $paginationMeta];
    }
}