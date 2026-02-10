<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use App\Helpers\Staff;
use Filament\Widgets\Widget;

class DashboardQuickMenu extends Widget
{
    protected static string $view = 'filament.widgets.dashboard-quick-menu';
 protected static ?string $heading = '';
    protected int | array | string $columnSpan = 'full';
public $store;

    public function getHeading(): string
    {
        return '';
    }
public function getViewData(): array
{
    $store = \App\Models\Store::first();

    return [
        'recentSales' => \App\Models\Sale::latest()->limit(5)->get(),
        'store' => $store,
        // CHANGED: Using 'final_total' instead of 'grand_total'
        'todaySales' => \App\Models\Sale::whereDate('created_at', today())
            ->sum('final_total'), 
    ];
}
    public static function canView(): bool
    {
        return true; 
    }
}