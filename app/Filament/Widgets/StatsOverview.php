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
                ->color('success')
                ->icon('heroicon-m-banknotes')
                ->extraAttributes([
                    'style' => '
                        background: linear-gradient(145deg, #1e3a8a 0%, #2563eb 100%) !important;
                        border-radius: 16px !important;
                        border: none !important;
                        padding: 28px !important;
                        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4) !important;
                        transition: all 0.3s ease !important;
                        overflow: hidden !important;
                        position: relative !important;
                    ',
                ]),

            Stat::make('Active Stock Items', ProductItem::where('status', 'in_stock')->count())
                ->description('Total serialized items currently on shelf')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('success')
                ->icon('heroicon-m-archive-box')
                ->extraAttributes([
                    'style' => '
                        background: linear-gradient(145deg, #7c3aed 0%, #8b5cf6 100%) !important;
                        border-radius: 16px !important;
                        border: none !important;
                        padding: 28px !important;
                        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4) !important;
                        transition: all 0.3s ease !important;
                        overflow: hidden !important;
                        position: relative !important;
                    ',
                ]),

            Stat::make('Monthly Revenue', '$' . number_format(Sale::whereMonth('created_at', now()->month)->sum('final_total'), 2))
                ->description('Total sales for ' . now()->format('F'))
                ->descriptionIcon('heroicon-m-presentation-chart-line')
                ->color('success')
                ->icon('heroicon-m-presentation-chart-line')
                ->extraAttributes([
                    'style' => '
                        background: linear-gradient(145deg, #059669 0%, #10b981 100%) !important;
                        border-radius: 16px !important;
                        border: none !important;
                        padding: 28px !important;
                        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4) !important;
                        transition: all 0.3s ease !important;
                        overflow: hidden !important;
                        position: relative !important;
                    ',
                ]),
        ];
    }
}