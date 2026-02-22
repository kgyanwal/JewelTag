<?php

namespace App\Filament\Pages;

use App\Models\Sale;
use App\Models\ProductItem;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;

class TradeInCheck extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';
    protected static ?string $navigationLabel = 'Trade-In Check';
    protected static ?string $navigationGroup = 'Inventory';
    protected static string $view = 'filament.pages.trade-in-check';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // 🔹 Show sales with trade-ins that aren't in ProductItem yet
                Sale::where('has_trade_in', 1)
                    ->whereNotIn('trade_in_receipt_no', function($query) {
                        $query->select('original_trade_in_no')->from('product_items')->whereNotNull('original_trade_in_no');
                    })
            )
            ->columns([
                TextColumn::make('trade_in_receipt_no')
                    ->label('Receipt #')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('customer.name')->label('Customer'),
                TextColumn::make('trade_in_value')
                    ->label('Credit Value')
                    ->money('USD')
                    ->color('success'),
                TextColumn::make('created_at')
                    ->label('Received Date')
                    ->dateTime()
                    ->sortable(),
                 TextColumn::make('trade_in_description')
                ->label('Item Description')
                ->wrap()
                ->limit(50),   
                BadgeColumn::make('status')
                    ->default('Pending Assembly')
                    ->color('warning'),
            ])
            ->actions([
                // 🔹 Direct "Assemble" shortcut
                Action::make('assemble')
                    ->label('Assemble Now')
                    ->icon('heroicon-o-plus-circle')
                    ->url(fn (Sale $record) => \App\Filament\Resources\ProductItemResource::getUrl('create', [
                        'creation_mode' => 'trade_in',
                        'original_trade_in_no' => $record->trade_in_receipt_no,
                    ])),
            ]);
    }
}