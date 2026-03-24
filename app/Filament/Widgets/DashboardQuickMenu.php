<?php

namespace App\Filament\Widgets;

use App\Models\Store;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\ProductItem;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class DashboardQuickMenu extends Widget
{
    protected static string $view = 'filament.widgets.dashboard-quick-menu';
    protected int | array | string $columnSpan = 'full';
    
    protected static ?string $heading = null;

    public function getViewData(): array
    {
        $store = \App\Helpers\Staff::user()?->store ?? Store::first();

        // Timezone-aware today window (matches EOD closing logic)
        $tz = $store?->timezone ?? config('app.timezone', 'UTC');
        $startUtc = Carbon::now($tz)->startOfDay()->utc();
        $endUtc   = Carbon::now($tz)->endOfDay()->utc();

        $pendingLaybuys = \App\Models\Laybuy::where('status', 'active')->count();
        $pendingCustomOrders = \App\Models\CustomOrder::whereIn('status', ['pending', 'in_progress'])->count();
        
        $pendingFollowUps = Sale::where('status', 'completed')
            ->where(function ($query) {
                $today = now();
                $future = now()->addDays(14);
                $query->whereBetween('follow_up_date', [$today, $future])
                      ->orWhereBetween('second_follow_up_date', [$today, $future]);
            })->count();
        
        $memoItems = ProductItem::where('is_memo', true)
            ->where('memo_status', 'on_memo')
            ->count();
        
        $tradeInRequests = Sale::where('has_trade_in', 1)
            ->whereNotIn('trade_in_receipt_no', function($query) {
                $query->select('original_trade_in_no')
                      ->from('product_items')
                      ->whereNotNull('original_trade_in_no');
            })->count();

        return [
            'store'              => $store,
            'recentSales'        => Sale::latest()->limit(5)->get(),
            'todaySales'         => Payment::whereBetween('paid_at', [$startUtc, $endUtc])->sum('amount'),
            'pendingLaybuys'     => $pendingLaybuys,
            'pendingCustomOrders'=> $pendingCustomOrders,
            'pendingFollowUps'   => $pendingFollowUps,
            'memoItems'          => $memoItems,
            'tradeInRequests'    => $tradeInRequests,
        ];
    }
}