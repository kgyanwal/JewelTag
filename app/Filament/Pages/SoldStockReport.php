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
use App\Forms\Components\CustomDatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB;

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

                TextColumn::make('buyer_info')
                    ->label('Purchased By')
                    ->getStateUsing(function ($record) {
                        $saleItem = \App\Models\SaleItem::where('product_item_id', $record->id)
    ->whereHas('sale', fn($q) => $q->whereNotIn('status', ['cancelled', 'void', 'refunded']))
    ->latest()
    ->first();
                        $sale     = $saleItem ? $saleItem->sale : null;
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
                        CustomDatePicker::make('from')->label('Sold From'),
                        CustomDatePicker::make('until')->label('Sold Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'],  fn($q) => $q->whereDate('updated_at', '>=', $data['from']))
                            ->when($data['until'], fn($q) => $q->whereDate('updated_at', '<=', $data['until']));
                    }),

                SelectFilter::make('category')
                    ->options(fn() => collect(\App\Models\InventorySetting::where('key', 'categories')->first()?->value ?? [])
                        ->flatten()
                        ->mapWithKeys(fn($i) => [$i => $i])
                        ->toArray()),
            ])
            ->actions([
                \Filament\Tables\Actions\Action::make('view_sale')
                    ->label('View Sale')
                    ->icon('heroicon-o-shopping-bag')
                    ->color('info')
                    ->url(function ($record) {
                        $saleItem = \App\Models\SaleItem::where('product_item_id', $record->id)->first();
                        return $saleItem
                            ? \App\Filament\Resources\SaleResource::getUrl('view', ['record' => $saleItem->sale_id])
                            : null;
                    }),

                \Filament\Tables\Actions\Action::make('restock')
                    ->label('Restock')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn() => auth()->user()?->hasRole('Superadmin'))
                    ->form([
                        \Filament\Forms\Components\Select::make('reason')
                            ->label('Reason for Restock')
                            ->options([
                                'returned_by_customer' => 'Returned by Customer',
                                'sale_cancelled'       => 'Sale Cancelled / Voided',
                                'incorrect_sale'       => 'Incorrect Sale Entry',
                                'consignment_returned' => 'Consignment Returned',
                                'damaged_exchange'     => 'Damaged — Exchange',
                                'trade_in_reversal'    => 'Trade-In Reversal',
                                'admin_correction'     => 'Admin Correction',
                                'other'                => 'Other',
                            ])
                            ->required()
                            ->searchable(),

                        \Filament\Forms\Components\Textarea::make('notes')
                            ->label('Additional Notes')
                            ->placeholder('Optional details about this restock...')
                            ->rows(3),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Restock Item')
                    ->modalDescription('This will mark the item as available again. Please provide a reason.')
                    ->modalSubmitActionLabel('Confirm Restock')
                    ->action(function ($record, array $data) {
                        // Update product status back to in_stock
                      $record->update([
        'status' => 'in_stock',
        'qty'    => 1,
    ]);

                        // Log using DB directly — avoids activity() helper namespace issue
                        try {
                            DB::table('activity_log')->insert([
                                'log_name'     => 'Inventory',
                                'description'  => 'Item restocked. Reason: ' . $data['reason'],
                                'subject_type' => ProductItem::class,
                                'subject_id'   => $record->id,
                                'causer_type'  => get_class(auth()->user()),
                                'causer_id'    => auth()->id(),
                                'properties'   => json_encode([
                                    'reason'          => $data['reason'],
                                    'notes'           => $data['notes'] ?? null,
                                    'previous_status' => 'sold',
                                    'new_status'      => 'in_stock',
                                ]),
                                'event'        => 'restocked',
                                'created_at'   => now(),
                                'updated_at'   => now(),
                            ]);
                        } catch (\Exception $e) {
                            // Silent — restock completes even if log fails
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Item Restocked')
                            ->body("'{$record->barcode}' is now in_stock in inventory.")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    protected function getViewData(): array
    {
        return [
            'totalSoldValue' => ProductItem::where('status', 'sold')->sum('retail_price'),
            'soldCount'      => ProductItem::where('status', 'sold')->count(),
        ];
    }
}