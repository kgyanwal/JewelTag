<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use App\Helpers\Staff;
use Filament\Widgets\Widget;

class DashboardQuickMenu extends Widget
{
    protected static string $view = 'filament.widgets.dashboard-quick-menu';

    protected int | array | string $columnSpan = 'full';

    public function getViewData(): array
{
    $activeStaff = \App\Helpers\Staff::user();
    
    return [
        'recentSales' => \App\Models\Sale::latest()->limit(5)->get(),
        // This pulls the logo_path from the migration you just created
        'storeLogo' => $activeStaff?->store?->logo_path, 
    ];
}

    public static function canView(): bool
    {
        return true; 
    }
}