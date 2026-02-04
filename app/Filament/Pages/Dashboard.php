<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\DashboardQuickMenu;

class Dashboard extends BaseDashboard
{
     protected static ?string $heading = '';
    /**
     * 🔹 This forces the Dashboard to ONLY show these widgets.
     */

    public function getHeading(): string
    {
        return '';
    }
    public function getWidgets(): array
    {
        return [
            DashboardQuickMenu::class,
        ];
    }
}