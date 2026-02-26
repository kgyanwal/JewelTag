<?php

namespace App\Filament\Master\Widgets;

use App\Models\Tenant;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class PlatformHealthWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s'; // Polling once a minute is safer for DB pings

    protected function getStats(): array
    {
        // 1. Connectivity Check: How many tenants are actually reachable?
        $activeTenants = 0;
        $tenants = Tenant::all();
        
        foreach ($tenants as $tenant) {
            try {
                // Attempt a lightweight query to the tenant database
                tenancy()->initialize($tenant);
                if (DB::connection()->getPdo()) {
                    $activeTenants++;
                }
                tenancy()->end();
            } catch (\Exception $e) {
                // Database is offline or connection failed
                continue; 
            }
        }

        // 2. Global User Count: Total staff across the entire platform
        // This gives you a better idea of platform load than just "Store Count"
        $totalStaff = User::count(); // Central users

        return [
            Stat::make('Database Connectivity', "$activeTenants / " . $tenants->count())
                ->description($activeTenants === $tenants->count() ? 'All Stores Online' : 'Warning: Store Offline')
                ->descriptionIcon($activeTenants === $tenants->count() ? 'heroicon-m-globe-alt' : 'heroicon-m-exclamation-triangle')
                ->color($activeTenants === $tenants->count() ? 'success' : 'danger'),

            Stat::make('Total Platform Users', $totalStaff)
                ->description('Staff accounts across all stores')
                ->descriptionIcon('heroicon-m-users')
                ->color('info')
                ->chart([15, 25, 45, 70, 90, 120, $totalStaff]),

            Stat::make('Latest Onboarding', $tenants->last()?->id ?? 'None')
                ->description('Most recent store created')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('primary'),
        ];
    }
}