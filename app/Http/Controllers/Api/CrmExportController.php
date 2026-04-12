<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Store; // 🚀 REQUIRED FOR TIMEZONE
use Illuminate\Support\Carbon;

class CrmExportController extends Controller
{
    public function export(Request $request) // 🚀 Kept as 'export' so your routes don't break
    {
        // 1. Get the requested date (defaults to yesterday if not provided)
        $dateString = $request->query('date', Carbon::yesterday()->format('Y-m-d'));

        // 2. Fetch the store's timezone
        $tz = Store::first()?->timezone ?? config('app.timezone', 'UTC');

        // 3. Convert the local date into explicit UTC start and end boundaries
        try {
            $startUtc = Carbon::createFromFormat('Y-m-d', $dateString, $tz)->startOfDay()->utc();
            $endUtc   = Carbon::createFromFormat('Y-m-d', $dateString, $tz)->endOfDay()->utc();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid date format. Use YYYY-MM-DD.'], 400);
        }

        // 4. Fetch Sales & Payments within the UTC window
        $sales = Sale::with(['items.productItem', 'payments'])
            ->whereBetween('created_at', [$startUtc, $endUtc])
            ->get();

        // 5. Fetch Customers within the UTC window
        $customers = Customer::whereBetween('created_at', [$startUtc, $endUtc])->get();

        // 6. Return the payload matching your exact structure
        return response()->json([
            'store_id'     => tenant('id'),
            'export_date'  => $dateString,
            'data'         => [
                'sales'     => $sales,
                'customers' => $customers,
            ]
        ]);
    }
}