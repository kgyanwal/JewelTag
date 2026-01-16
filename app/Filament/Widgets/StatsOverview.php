<?php

namespace App\Filament\Widgets;

use App\Models\ProductItem;
use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Inventory Value', '$' . number_format(ProductItem::where('status', 'in_stock')->sum('retail_price'), 2))
                ->description('Combined retail price of all items in stock')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Active Stock Items', ProductItem::where('status', 'in_stock')->count())
                ->description('Total serialized items currently on shelf')
                ->descriptionIcon('heroicon-m-archive-box'),

            Stat::make('Monthly Revenue', '$' . number_format(Sale::whereMonth('created_at', now()->month)->sum('final_total'), 2))
                ->description('Total sales for ' . now()->format('F'))
                ->descriptionIcon('heroicon-m-presentation-chart-line')
                ->color('info'),
        ];
    }
}