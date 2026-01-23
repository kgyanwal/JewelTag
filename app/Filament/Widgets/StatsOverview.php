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
            Stat::make(
                'Total Inventory Value',
                '$' . number_format(
                    ProductItem::where('status', 'in_stock')->sum('retail_price'),
                    2
                )
            )
                ->description('Combined retail price of all items in stock')
                ->icon('heroicon-m-banknotes')
                ->color('success')
                ->extraAttributes([
                    'class' => 'custom-stat-card',
                    'style' => 'background: linear-gradient(135deg, #2D3E50 0%, #1a2530 100%) !important; color: white !important; border: none !important;',
                ]),

            Stat::make(
                'Active Stock Items',
                ProductItem::where('status', 'in_stock')->count()
            )
                ->description('Total serialized items currently on shelf')
                ->icon('heroicon-m-archive-box')
                ->color('info')
                ->extraAttributes([
                    'class' => 'custom-stat-card',
                    'style' => 'background: linear-gradient(135deg, #4A5568 0%, #2D3748 100%) !important; color: white !important; border: none !important;',
                ]),

            Stat::make(
                'Monthly Revenue',
                '$' . number_format(
                    Sale::whereMonth('created_at', now()->month)->sum('final_total'),
                    2
                )
            )
                ->description('Total sales for ' . now()->format('F'))
                ->icon('heroicon-m-presentation-chart-line')
                ->color('warning')
                ->extraAttributes([
                    'class' => 'custom-stat-card',
                    'style' => 'background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important; color: white !important; border: none !important;',
                ]),
        ];
    }
}