<?php

namespace App\Filament\Pages;

use App\Models\SaleItem;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Columns\Summarizers\Sum;

class NonStockReport extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-wrench';
    protected static ?string $navigationGroup = 'Analytics & Reports';
    protected static ?string $navigationLabel = 'Non-Stock / Service Sales';
    protected static string $view = 'filament.pages.non-stock-report';

    public function table(Table $table): Table
    {
        return $table
            ->query(SaleItem::query()->where('is_non_stock', true))
            ->columns([
                TextColumn::make('sale.invoice_number')
                    ->label('Job No.')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('custom_description')
                    ->label('Service / Item Name')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('cost_price')
                    ->label('Cost')
                    ->money('USD')
                    ->summarize(Sum::make()->money('USD')),

                TextColumn::make('sold_price')
                    ->label('Sold')
                    ->money('USD')
                    ->summarize(Sum::make()->money('USD')),

              TextColumn::make('profit')
    ->label('Profit')
    ->state(fn ($record) => $record->sold_price - $record->cost_price)
    ->money('USD')
    ->color('success')
    ->summarize(
        \Filament\Tables\Columns\Summarizers\Summarizer::make()
            ->label('Total Profit')
            ->money('USD')
            ->using(fn (\Illuminate\Database\Query\Builder $query) => 
                $query->sum(DB::raw('sold_price - cost_price'))
            )
    ),

                TextColumn::make('sale.customer.name')
                    ->label('Customer')
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}