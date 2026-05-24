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

    $tz       = $store?->timezone ?? config('app.timezone', 'UTC');
$today    = Carbon::now($tz)->format('Y-m-d');
$startUtc = Carbon::parse($today, $tz)->startOfDay()->setTimezone('UTC');
$endUtc   = Carbon::parse($today, $tz)->endOfDay()->setTimezone('UTC');



    // ── PULL FROM EOD IF ALREADY CLOSED, OTHERWISE CALCULATE LIVE ──
    $todayEod = \App\Models\DailyClosing::whereDate('closing_date', $today)->first();

    if ($todayEod) {
        // EOD already posted — use the locked total
        $todaySales = $todayEod->total_expected;
    } else {
        // EOD not posted yet — calculate live exactly like EOD does
        $payments = Payment::whereBetween('paid_at', [$startUtc, $endUtc])->get();
        $todaySales = $payments->sum('amount');

        // Add old-style laybuy sales with no payment row
        $laybuyExtra = Sale::whereBetween('created_at', [$startUtc, $endUtc])
            ->where('payment_method', 'laybuy')
            ->whereNotIn('id', function ($q) use ($startUtc, $endUtc) {
                $q->select('sale_id')
                  ->from('payments')
                  ->whereBetween('paid_at', [$startUtc, $endUtc])
                  ->whereNotNull('sale_id');
            })
            ->sum('amount_paid');

        $todaySales += $laybuyExtra;
    }

    $pendingLaybuys      = \App\Models\Laybuy::where('status', 'active')->count();
    $pendingCustomOrders = \App\Models\CustomOrder::whereIn('status', ['pending', 'in_progress'])->count();

    $pendingFollowUps = Sale::where('status', 'completed')
        ->where(function ($query) {
            $query->whereBetween('follow_up_date', [now(), now()->addDays(14)])
                  ->orWhereBetween('second_follow_up_date', [now(), now()->addDays(14)]);
        })->count();

    $memoItems = ProductItem::where('is_memo', true)
        ->where('memo_status', 'on_memo')
        ->count();

    $tradeInRequests = Sale::where('has_trade_in', 1)
        ->whereNotIn('trade_in_receipt_no', function ($query) {
            $query->select('original_trade_in_no')
                  ->from('product_items')
                  ->whereNotNull('original_trade_in_no');
        })->count();

    return [
        'store'               => $store,
        'recentSales'         => Sale::latest()->limit(5)->get(),
        'todaySales' => Payment::whereBetween('paid_at', [$startUtc, $endUtc])
    ->whereHas('sale', function ($q) use ($startUtc, $endUtc) {
        $q->whereBetween('created_at', [$startUtc, $endUtc]);
    })
    ->sum('amount'),
        'pendingLaybuys'      => $pendingLaybuys,
        'pendingCustomOrders' => $pendingCustomOrders,
        'pendingFollowUps'    => $pendingFollowUps,
        'memoItems'           => $memoItems,
        'tradeInRequests'     => $tradeInRequests,
    ];
}
}