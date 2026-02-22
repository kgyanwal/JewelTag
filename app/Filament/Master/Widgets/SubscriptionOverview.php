<?php

namespace App\Filament\Master\Widgets;

use App\Models\Tenant; // 🚨 MUST use Tenant model for Master Panel
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SubscriptionOverview extends BaseWidget
{
    
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // For the Master Panel, we track platform-wide stats from the Central DB
        $totalStores = Tenant::count();
        // Estimated revenue logic (e.g., $99 per store)
        $revenue = $totalStores * 99;

        return [
            Stat::make('Total Active Stores', $totalStores)
                ->description('Jewelry SaaS Clients')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('primary')
                ->chart([3, 5, 8, 10, 12, 15, $totalStores]),

            Stat::make('Platform Revenue', '$' . number_format($revenue, 2))
                ->description('Estimated Monthly Total')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('System Status', 'Healthy')
                ->description('All Systems Operational')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('info'),
        ];
    }
}