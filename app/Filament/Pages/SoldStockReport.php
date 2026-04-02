<?php

namespace App\Filament\Pages;

use App\Models\ProductItem;
use App\Models\Sale;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class SoldStockReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $navigationLabel = 'Sold Stock';
    protected static ?string $title = 'Sold Inventory Ledger';

    protected static string $view = 'filament.pages.sold-stock-report';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ProductItem::query()
                    ->where('status', 'sold')
                    ->latest('updated_at')
            )
            ->columns([
                TextColumn::make('barcode')
                    ->label('Stock #')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('custom_description')
                    ->label('Item Details')
                    ->limit(50)
                    ->searchable(),

                // 🚀 CUSTOMER INFORMATION INTEGRATION
                TextColumn::make('buyer_info')
                    ->label('Purchased By')
                    ->getStateUsing(function ($record) {
                        // Find the sale item that matches this product
                        $saleItem = \App\Models\SaleItem::where('product_item_id', $record->id)->first();
                        $sale = $saleItem ? $saleItem->sale : null;
                        $customer = $sale ? $sale->customer : null;

                        if (!$customer) return 'No Customer Data';

                        return new HtmlString("
                            <div class='flex flex-col'>
                                <span class='font-semibold text-primary-600'>{$customer->name} {$customer->last_name}</span>
                                <span class='text-xs text-gray-500'>{$customer->phone}</span>
                                <span class='text-[10px] bg-gray-100 px-1 rounded w-fit mt-1'>Inv: {$sale->invoice_number}</span>
                            </div>
                        ");
                    }),

                TextColumn::make('retail_price')
                    ->label('Sold Price')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Date Sold')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('sold_date')
                    ->form([
                        DatePicker::make('from')->label('Sold From'),
                        DatePicker::make('until')->label('Sold Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn($q) => $q->whereDate('updated_at', '>=', $data['from']))
                            ->when($data['until'], fn($q) => $q->whereDate('updated_at', '<=', $data['until']));
                    }),
                
                SelectFilter::make('category')
                    ->options(fn() => collect(\App\Models\InventorySetting::where('key', 'categories')->first()?->value ?? [])->flatten()->mapWithKeys(fn($i) => [$i => $i])->toArray()),
            ])
            ->actions([
                \Filament\Tables\Actions\Action::make('view_sale')
                    ->label('View Sale')
                    ->icon('heroicon-o-shopping-bag')
                    ->color('info')
                    ->url(function ($record) {
                        $saleItem = \App\Models\SaleItem::where('product_item_id', $record->id)->first();
                        return $saleItem ? \App\Filament\Resources\SaleResource::getUrl('view', ['record' => $saleItem->sale_id]) : null;
                    }),
            ]);
    }

    protected function getViewData(): array
    {
        return [
            'totalSoldValue' => ProductItem::where('status', 'sold')->sum('retail_price'),
            'soldCount' => ProductItem::where('status', 'sold')->count(),
        ];
    }
}