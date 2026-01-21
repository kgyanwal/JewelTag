<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestSales extends BaseWidget
{
    protected static ?int $sort = 3; 
    protected int | string | array $columnSpan = 'full'; // Make it wide

    public function table(Table $table): Table
    {
        return $table
            ->query(Sale::query()->latest()->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')->label('Invoice #')->searchable(),
                Tables\Columns\TextColumn::make('customer.first_name')->label('Customer'),
                Tables\Columns\TextColumn::make('final_total')->label('Total Amount')->money('USD'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('Date')->dateTime('M d, Y h:i A'),
            ]);
    }
}