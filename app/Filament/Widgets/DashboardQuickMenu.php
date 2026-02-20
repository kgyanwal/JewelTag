<?php

namespace App\Filament\Widgets;

use App\Models\Store;
use App\Models\Sale;
use App\Models\ProductItem; // Add this
use Filament\Widgets\Widget;

class DashboardQuickMenu extends Widget
{
    protected static string $view = 'filament.widgets.dashboard-quick-menu';
    protected int | array | string $columnSpan = 'full';
    
    // Remove the widget title/label
    protected static ?string $heading = null;

    public function getViewData(): array
    {
        // Get the store associated with the logged-in staff, or the first store
        $store = \App\Helpers\Staff::user()?->store ?? Store::first();

        // Get counts for the priority items
        $pendingLaybuys = \App\Models\Laybuy::where('status', 'active')->count();
        $pendingCustomOrders = \App\Models\CustomOrder::whereIn('status', ['pending', 'in_progress'])->count();
        
        // Fix: Query for follow-ups using your page's logic
        $pendingFollowUps = \App\Models\Sale::where('status', 'completed')
            ->where(function ($query) {
                $today = now();
                $future = now()->addDays(14);
                $query->whereBetween('follow_up_date', [$today, $future])
                      ->orWhereBetween('second_follow_up_date', [$today, $future]);
            })->count();
        
        // Fix: Query memo items from product_items table (since that's where you added memo fields)
        $memoItems = ProductItem::where('is_memo', true)
            ->where('memo_status', 'on_memo')
            ->count();
        
        // Fix: Query trade-in requests using your page's logic
        $tradeInRequests = \App\Models\Sale::where('has_trade_in', 1)
            ->whereNotIn('trade_in_receipt_no', function($query) {
                $query->select('original_trade_in_no')
                      ->from('product_items')
                      ->whereNotNull('original_trade_in_no');
            })->count();

        return [
            'store' => $store,
            'recentSales' => Sale::latest()->limit(5)->get(),
            'todaySales' => Sale::whereDate('created_at', today())->sum('final_total'),
            'pendingLaybuys' => $pendingLaybuys,
            'pendingCustomOrders' => $pendingCustomOrders,
            'pendingFollowUps' => $pendingFollowUps,
            'memoItems' => $memoItems,
            'tradeInRequests' => $tradeInRequests,
        ];
    }
}