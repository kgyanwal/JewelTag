<?php

namespace App\Filament\Widgets;

use App\Models\ProductItem;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Repair;
use App\Models\CustomOrder;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StatsOverview extends Widget
{
    protected static string $view = 'filament.widgets.stats-overview';
    protected static ?int $sort = -2;
    protected int|string|array $columnSpan = 'full';
    protected static bool $isLazy = false;

    public static function getColumns(): int
    {
        return 1;
    }

    public function getColumnSpan(): int | string | array
    {
        return 'full';
    }

    public static function getColumnSpanKey(): string
    {
        return 'full';
    }

    public function getViewData(): array
    {
        $expr = 'COALESCE(completed_at, updated_at, created_at)';

        $monthlyRevenue = Sale::where('status','completed')
            ->whereMonth(DB::raw($expr), now()->month)
            ->whereYear(DB::raw($expr), now()->year)
            ->sum('final_total');

        $monthlySalesCount = Sale::where('status','completed')
            ->whereMonth(DB::raw($expr), now()->month)
            ->whereYear(DB::raw($expr), now()->year)
            ->count();

        $lastMonthRevenue = Sale::where('status','completed')
            ->whereMonth(DB::raw($expr), now()->subMonth()->month)
            ->whereYear(DB::raw($expr), now()->subMonth()->year)
            ->sum('final_total');

        $revenueChange = $lastMonthRevenue > 0
            ? round((($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : 0;

        $todayRevenue = Sale::where('status','completed')
            ->whereDate(DB::raw($expr), today())
            ->sum('final_total');

        $todaySales = Sale::where('status','completed')
            ->whereDate(DB::raw($expr), today())
            ->count();

        $inventoryValue = ProductItem::where('status','in_stock')->sum('retail_price');
        $inventoryCount = ProductItem::where('status','in_stock')->count();
        $soldToday      = ProductItem::where('status','sold')->whereDate('updated_at', today())->count();

        $outstandingCount = Sale::where('status','completed')->where('balance_due','>',0)->count();
        $outstandingTotal = Sale::where('status','completed')->where('balance_due','>',0)->sum('balance_due');

        $repairsOpen    = Repair::whereIn('status',['received','in_progress'])->count();
        $repairsReady   = Repair::where('status','ready')->count();
        $repairsOverdue = Repair::whereIn('status',['received','in_progress'])
            ->whereNotNull('customer_pickup_date')
            ->where('customer_pickup_date','<',today())->count();

        $newCustomers   = Customer::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $totalCustomers = Customer::count();

        $customOrdersPending = CustomOrder::whereIn('status',['pending','in_production'])->count();
        $customOrdersReady   = CustomOrder::where('status','ready')->count();

        $sparkline = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $sparkline[] = [
                'day'     => $day->format('D'),
                'revenue' => Sale::where('status','completed')
                    ->whereDate(DB::raw($expr), $day->toDateString())
                    ->sum('final_total'),
            ];
        }
        $maxSparkline = max(array_column($sparkline, 'revenue')) ?: 1;

        return compact(
            'monthlyRevenue','monthlySalesCount','lastMonthRevenue','revenueChange',
            'todayRevenue','todaySales',
            'inventoryValue','inventoryCount','soldToday',
            'outstandingCount','outstandingTotal',
            'repairsOpen','repairsReady','repairsOverdue',
            'newCustomers','totalCustomers',
            'customOrdersPending','customOrdersReady',
            'sparkline','maxSparkline'
        );
    }
}