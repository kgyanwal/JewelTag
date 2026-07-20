<?php

namespace App\Filament\Pages;

use App\Models\CustomOrder;
use App\Models\ProductItem;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ExchangedCustomOrdersReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationLabel = 'Exchanged Custom Orders';
    protected static ?string $navigationGroup = 'Analytics & Reports';
    protected static ?string $title = 'Exchanged Custom Orders — Stock Trace Report';
    protected static string $view = 'filament.pages.exchanged-custom-orders-report';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CustomOrder::query()
                    ->where('status', 'exchanged')
                    ->with(['customer'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('order_no')
                    ->label('Order #')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->getStateUsing(fn($record) => trim(($record->customer?->name ?? '') . ' ' . ($record->customer?->last_name ?? '')))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('customer', fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"));
                    }),

                Tables\Columns\TextColumn::make('product_name')
                    ->label('Product')
                    ->limit(35),

                Tables\Columns\TextColumn::make('metal_type')
                    ->label('Metal')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('quoted_price')
                    ->label('Order Value')
                    ->money('USD')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('amount_paid')
                    ->label('Deposited')
                    ->money('USD')
                    ->color('success'),

                Tables\Columns\TextColumn::make('stock_status')
                    ->label('Stock Status')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $converted = ProductItem::where('source_custom_order_id', $record->id)->exists();
                        return $converted ? 'converted' : 'not_traced';
                    })
                    ->formatStateUsing(fn($state) => $state === 'converted' ? '✅ Converted to Stock' : '⚠️ Not Traced — No Stock Entry')
                    ->color(fn($state) => $state === 'converted' ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('converted_barcode')
                    ->label('Stock Barcode')
                    ->getStateUsing(function ($record) {
                        $item = ProductItem::where('source_custom_order_id', $record->id)->first();
                        return $item?->barcode ?? '—';
                    })
                    ->fontFamily('mono')
                    ->color('info'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Exchanged On')
                    ->dateTime('M d, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('design_notes')
                    ->label('Void/Exchange Note')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->design_notes)
                    ->wrap(),
            ])
            ->filters([
                Tables\Filters\Filter::make('not_traced_only')
                    ->label('Show only NOT traced (missing stock entry)')
                    ->query(function (Builder $query) {
                        return $query->whereNotExists(function ($sub) {
                            $sub->selectRaw(1)
                                ->from('product_items')
                                ->whereColumn('product_items.source_custom_order_id', 'custom_orders.id');
                        });
                    })
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('convert_to_stock')
                    ->label('Convert to Stock')
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->visible(fn($record) => !ProductItem::where('source_custom_order_id', $record->id)->exists())
                    ->url(fn($record) => \App\Filament\Resources\ProductItemResource::getUrl('create', [
                        'source_custom_order_id' => $record->id,
                    ])),

                Tables\Actions\Action::make('view_stock')
                    ->label('View Stock Item')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->visible(fn($record) => ProductItem::where('source_custom_order_id', $record->id)->exists())
                    ->url(function ($record) {
                        $item = ProductItem::where('source_custom_order_id', $record->id)->first();
                        return $item ? \App\Filament\Resources\ProductItemResource::getUrl('edit', ['record' => $item]) : null;
                    }),
            ])
            ->defaultSort('updated_at', 'desc')
            ->striped()
            ->poll('60s');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['Superadmin', 'Administration', 'Manager']) ?? false;
    }
}