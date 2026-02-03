<?php

namespace App\Filament\Widgets;

use App\Models\ProductItem;
use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?string $componentName = 'stats-overview';
    protected ?string $heading = 'Dashboard Overview';

    protected function getStats(): array
    {
        // Base card style for all stats
        $baseStyle = '
            border-radius: 20px !important;
            padding: 24px !important;
            box-shadow: 0 10px 15px rgba(0,0,0,0.4) !important;
            color: #ffffff !important;
        ';

        // Gradient backgrounds for each stat card
        $blueStyle   = "background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important; " . $baseStyle;
        $greenStyle  = "background: linear-gradient(135deg, #059669 0%, #064e3b 100%) !important; " . $baseStyle;
        $purpleStyle = "background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%) !important; " . $baseStyle;

        return [
            Stat::make(
                'Inventory Value',
                '$' . number_format(ProductItem::where('status', 'in_stock')->sum('retail_price'), 2)
            )
                ->description('Total retail value of stock')
                ->descriptionIcon('heroicon-m-banknotes')
                ->extraAttributes([
                    'style' => $blueStyle,
                    'class' => 'hover:scale-[1.02] transition-transform duration-300',
                ]),

            Stat::make(
                'Items in Stock',
                ProductItem::where('status', 'in_stock')->count() . ' Units'
            )
                ->description('Active units on shelf')
                ->descriptionIcon('heroicon-m-archive-box')
                ->extraAttributes([
                    'style' => $greenStyle,
                    'class' => 'hover:scale-[1.02] transition-transform duration-300',
                ]),

            Stat::make(
                'Monthly Revenue',
                '$' . number_format(Sale::whereMonth('created_at', now()->month)->sum('final_total'), 2)
            )
                ->description('Sales for ' . now()->format('F'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->extraAttributes([
                    'style' => $purpleStyle,
                    'class' => 'hover:scale-[1.02] transition-transform duration-300',
                ]),
        ];
    }
}
