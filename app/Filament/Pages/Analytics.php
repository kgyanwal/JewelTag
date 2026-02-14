<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Analytics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Data Insights';
    protected static ?string $navigationGroup = 'Analytics & Reports';
    
    protected static string $view = 'filament.pages.analytics';
   protected static ?int $navigationSort = 5; 

    public function getHeaderWidgets(): array
{
    return [
        \App\Filament\Widgets\StatsOverview::class,
        \App\Filament\Widgets\DepartmentChart::class,
    ];
}

public function getFooterWidgets(): array
{
    return [
        \App\Filament\Widgets\FastestSellingItems::class, 
        \App\Filament\Widgets\LatestSales::class,
    ];
}

public function getHeaderWidgetsColumns(): int|string|array
{
    return ['lg' => 2, 'sm' => 1];
}

public function getFooterWidgetsColumns(): int|string|array
{
    return ['lg' => 2, 'sm' => 1];
}
public static function shouldRegisterNavigation(): bool
    {
        // 🔹 Use your Staff helper to check the identity of the person who entered the PIN
        $staff = \App\Helpers\Staff::user();

        // Only allow specific roles to see the Administration menu
        return $staff?->hasAnyRole(['Superadmin', 'Administration']) ?? false;
    }
}
