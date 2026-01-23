<?php

namespace App\Filament\Widgets;

use App\Models\ProductItem;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\DB;

class FastestSellingItems extends TableWidget
{
    protected static ?string $heading = 'Fastest Selling Items';

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
            )
            ->columns([
                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('total_sold')
                    ->label('Units Sold')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('avg_price')
                    ->label('Average Price')
                    ->money('USD'),
            ]);
    }
}
