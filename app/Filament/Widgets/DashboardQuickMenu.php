<?php

namespace App\Filament\Widgets;

use App\Models\Store;
use App\Models\Sale;
use Filament\Widgets\Widget;

class DashboardQuickMenu extends Widget
{
    protected static string $view = 'filament.widgets.dashboard-quick-menu';
    protected int | array | string $columnSpan = 'full';

    public function getViewData(): array
    {
        // Get the store associated with the logged-in staff, or the first store
        $store = \App\Helpers\Staff::user()?->store ?? Store::first();

        return [
            'store' => $store,
            'recentSales' => Sale::latest()->limit(5)->get(),
            'todaySales' => Sale::whereDate('created_at', today())->sum('final_total'), 
        ];
    }
}