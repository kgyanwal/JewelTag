<?php

namespace App\Filament\Widgets;

use App\Models\Supplier;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SupplierQuickActions extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Suppliers', Supplier::count())
                ->icon('heroicon-o-building-storefront')
                ->color('primary'),
                // Temporarily remove ->url() until routes are working
                // ->url(route('filament.resources.suppliers.index')),
                
            Stat::make('Active Suppliers', Supplier::where('is_active', true)->count())
                ->icon('heroicon-o-check-circle')
                ->color('success'),
                
            Stat::make('New Supplier', 'Create')
                ->icon('heroicon-o-plus-circle')
                ->color('gray'),
                // Temporarily remove ->url() until routes are working
                // ->url(route('filament.resources.suppliers.create')),
        ];
    }
}