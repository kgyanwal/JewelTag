<?php

namespace App\Filament\Widgets;

use App\Models\ProductItem;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\DB;

class FastestSellingItems extends TableWidget
{
    protected static ?string $heading = '🚀 Fastest Selling Items';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ProductItem::query()
                    ->where('status', 'sold')
                    ->select([
                        DB::raw('MIN(id) as id'), // ✅ REQUIRED fake primary key
                        'category',
                        DB::raw('COUNT(*) as total_sold'),
                        DB::raw('AVG(retail_price) as avg_price'),
                    ])
                    ->groupBy('category')
                    ->orderByDesc('total_sold')
                    ->limit(10) // Limit results to prevent too many rows
            )
            ->emptyStateHeading('No sales data yet')
            ->emptyStateDescription('Sales data will appear here as items are sold.')
            ->emptyStateIcon('heroicon-o-shopping-bag')
            ->striped() // Alternating row colors
            ->columns([
                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->description('Product category')
                    ->weight('bold')
                    ->color('primary')
                    ->icon('heroicon-o-tag')
                    ->iconColor('primary')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_sold')
                    ->label('Units Sold')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state > 50 => 'success',
                        $state > 20 => 'warning',
                        $state > 0 => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => number_format($state))
                    ->icon('heroicon-o-chart-bar')
                    ->iconPosition('after')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('avg_price')
                    ->label('Average Price')
                    ->money('USD')
                    ->color(fn ($state): string => $state > 1000 ? 'success' : 'gray')
                    ->weight(fn ($state): string => $state > 1000 ? 'bold' : 'normal')
                    ->icon('heroicon-o-currency-dollar')
                    ->iconColor('success')
                    ->sortable()
                    ->alignRight(),
            ])
            ->paginated(false) // Show all items on one page for widget
            ->recordUrl(null); // Disable click-through
    }

    // Add custom CSS for the widget
    public static function getAssets(): array
    {
        return [
            'style' => <<<'HTML'
<style>
    /* Custom styling for Fastest Selling Items widget */
    .fi-wi-fastest-selling-items {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%) !important;
        border-radius: 16px !important;
        border: 1px solid #e5e7eb !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05) !important;
        overflow: hidden !important;
    }

    .fi-wi-fastest-selling-items .fi-wi-header {
        background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding: 1.25rem 1.5rem !important;
    }

    .fi-wi-fastest-selling-items .fi-wi-header-heading {
        color: white !important;
        font-weight: 700 !important;
        font-size: 1.25rem !important;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }

    .fi-wi-fastest-selling-items .fi-ta-ctn {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        margin: 0 !important;
    }

    .fi-wi-fastest-selling-items .fi-ta-header {
        background: rgba(13, 148, 136, 0.05) !important;
        border-bottom: 2px solid #0d9488 !important;
    }

    .fi-wi-fastest-selling-items .fi-ta-header-cell {
        color: #111827 !important;
        font-weight: 700 !important;
        font-size: 0.875rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        padding: 1rem 1.5rem !important;
    }

    .fi-wi-fastest-selling-items .fi-ta-row {
        transition: all 0.2s ease !important;
        border-bottom: 1px solid #f1f5f9 !important;
    }

    .fi-wi-fastest-selling-items .fi-ta-row:hover {
        background-color: rgba(13, 148, 136, 0.03) !important;
    }

    .fi-wi-fastest-selling-items .fi-ta-cell {
        padding: 1rem 1.5rem !important;
        color: #1f2937 !important;
        font-weight: 500 !important;
    }

    .fi-wi-fastest-selling-items .fi-ta-cell:first-child {
        font-weight: 600 !important;
    }

    .fi-wi-fastest-selling-items .fi-badge {
        font-weight: 700 !important;
        font-size: 0.75rem !important;
        padding: 0.25rem 0.75rem !important;
        border-radius: 9999px !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
    }

    .fi-wi-fastest-selling-items .fi-badge-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
        color: white !important;
        border: none !important;
    }

    .fi-wi-fastest-selling-items .fi-badge-warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
        color: white !important;
        border: none !important;
    }

    .fi-wi-fastest-selling-items .fi-badge-gray {
        background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%) !important;
        color: white !important;
        border: none !important;
    }

    /* Fix for empty state */
    .fi-wi-fastest-selling-items .fi-empty-state {
        padding: 3rem 2rem !important;
        background: rgba(13, 148, 136, 0.02) !important;
        border-radius: 12px !important;
        border: 2px dashed #e5e7eb !important;
        margin: 1rem !important;
    }

    .fi-wi-fastest-selling-items .fi-empty-state-icon {
        color: #0d9488 !important;
        opacity: 0.3;
    }

    /* Custom scrollbar */
    .fi-wi-fastest-selling-items .fi-ta-body {
        max-height: 320px;
        overflow-y: auto;
    }

    .fi-wi-fastest-selling-items .fi-ta-body::-webkit-scrollbar {
        width: 6px;
    }

    .fi-wi-fastest-selling-items .fi-ta-body::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 3px;
    }

    .fi-wi-fastest-selling-items .fi-ta-body::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    .fi-wi-fastest-selling-items .fi-ta-body::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>
HTML,
        ];
    }
}