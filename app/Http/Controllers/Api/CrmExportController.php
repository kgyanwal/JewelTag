<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Store;
use App\Models\CustomOrder; // 🚀 Added
use App\Models\Repair;      // 🚀 Added
use Illuminate\Support\Carbon;

class CrmExportController extends Controller
{
    public function export(Request $request)
    {
        $tz = Store::first()?->timezone ?? config('app.timezone', 'UTC');

        // 1. Initialize queries with essential eager-loading
        $salesQuery        = Sale::with(['items.productItem', 'payments', 'customer']);
        $customersQuery    = Customer::query();
        $customOrdersQuery = CustomOrder::with(['payments', 'customer']); // 🚀 Added
        $repairsQuery      = Repair::with(['customer']);                  // 🚀 Added

        // ── MODE 1: REAL-TIME SYNC (Using updated_since) ──────────────────
        // E.g., ?updated_since=2026-04-14T15:30:00Z
        if ($request->has('updated_since')) {
            try {
                $sinceUtc = Carbon::parse($request->query('updated_since'))->setTimezone('UTC');
                
                $salesQuery->where('updated_at', '>=', $sinceUtc);
                $customersQuery->where('updated_at', '>=', $sinceUtc);
                $customOrdersQuery->where('updated_at', '>=', $sinceUtc); // 🚀 Added
                $repairsQuery->where('updated_at', '>=', $sinceUtc);      // 🚀 Added
                
                $mode = 'real_time_sync';
            } catch (\Exception $e) {
                return response()->json(['error' => 'Invalid updated_since format. Use ISO-8601.'], 400);
            }
        }
        
        // ── MODE 2: HISTORICAL RANGE (Using start_date and end_date) ──────
        // E.g., ?start_date=2026-01-01&end_date=2026-03-31
        elseif ($request->has('start_date') && $request->has('end_date')) {
            try {
                $startUtc = Carbon::createFromFormat('Y-m-d', $request->query('start_date'), $tz)->startOfDay()->utc();
                $endUtc   = Carbon::createFromFormat('Y-m-d', $request->query('end_date'), $tz)->endOfDay()->utc();

                $salesQuery->whereBetween('created_at', [$startUtc, $endUtc]);
                $customersQuery->whereBetween('created_at', [$startUtc, $endUtc]);
                $customOrdersQuery->whereBetween('created_at', [$startUtc, $endUtc]); // 🚀 Added
                $repairsQuery->whereBetween('created_at', [$startUtc, $endUtc]);      // 🚀 Added

                $mode = 'historical_range';
            } catch (\Exception $e) {
                return response()->json(['error' => 'Invalid date format. Use YYYY-MM-DD.'], 400);
            }
        }

        // ── MODE 3: SINGLE DAY FALLBACK (Legacy support) ──────────────────
        // E.g., ?date=2026-04-12
        else {
            try {
                $dateString = $request->query('date', Carbon::now($tz)->format('Y-m-d'));
                $startUtc   = Carbon::createFromFormat('Y-m-d', $dateString, $tz)->startOfDay()->utc();
                $endUtc     = Carbon::createFromFormat('Y-m-d', $dateString, $tz)->endOfDay()->utc();

                $salesQuery->whereBetween('created_at', [$startUtc, $endUtc]);
                $customersQuery->whereBetween('created_at', [$startUtc, $endUtc]);
                $customOrdersQuery->whereBetween('created_at', [$startUtc, $endUtc]); // 🚀 Added
                $repairsQuery->whereBetween('created_at', [$startUtc, $endUtc]);      // 🚀 Added

                $mode = 'single_day';
            } catch (\Exception $e) {
                return response()->json(['error' => 'Invalid date format.'], 400);
            }
        }

        // 2. Execute queries
        $sales        = $salesQuery->get();
        $customers    = $customersQuery->get();
        $customOrders = $customOrdersQuery->get(); // 🚀 Added
        $repairs      = $repairsQuery->get();      // 🚀 Added

        // 3. Return the formatted payload
        return response()->json([
            'store_id' => tenant('id'),
            'mode'     => $mode,
            'metadata' => [
                'records_returned' => [
                    'customers'     => $customers->count(),
                    'sales'         => $sales->count(),
                    'custom_orders' => $customOrders->count(), // 🚀 Added
                    'repairs'       => $repairs->count(),      // 🚀 Added
                ],
                'timestamp_utc' => now()->timezone('UTC')->toIso8601String(),
            ],
            'data' => [
                'customers'     => $customers,
                'sales'         => $sales,
                'custom_orders' => $customOrders, // 🚀 Added
                'repairs'       => $repairs,      // 🚀 Added
            ]
        ]);
    }
}