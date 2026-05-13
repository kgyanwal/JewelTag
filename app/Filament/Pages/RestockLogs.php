<?php

namespace App\Filament\Pages;

use App\Helpers\Staff;
use App\Models\ProductItem;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use App\Forms\Components\CustomDatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class RestockLogs extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon  = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup = 'Admin';
    protected static ?string $navigationLabel = 'Restock Logs';
    protected static ?string $title           = 'Restocked Items Log';
    protected static string  $view            = 'filament.pages.restock-logs';

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Helpers\Staff::user()?->hasRole('Superadmin') ?? false;
    }

    public function mount(): void
    {
        abort_unless(
            \App\Helpers\Staff::user()?->hasRole('Superadmin') ?? false,
            403
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Items that are now in_stock BUT have a SaleItem record
                // meaning they were previously sold and then restocked
               ProductItem::query()
    ->where('status', 'in_stock')
    ->whereExists(function ($query) {
        $query->select(\Illuminate\Support\Facades\DB::raw(1))
            ->from('sale_items')
            ->whereColumn('sale_items.product_item_id', 'product_items.id');
    })
    ->latest('updated_at')
            )
            ->columns([
                TextColumn::make('updated_at')
                    ->label('Restocked At')
                    ->dateTime('M d, Y h:i A')
                    ->timezone(fn() => config('app.timezone', 'America/Denver'))
                    ->sortable()
                    ->weight('bold')
                    ->description(fn($record) => \Carbon\Carbon::parse($record->updated_at)
                        ->setTimezone(config('app.timezone', 'America/Denver'))
                        ->diffForHumans()),

                TextColumn::make('barcode')
                    ->label('Stock #')
                    ->weight('black')
                    ->color('primary')
                    ->fontFamily('mono')
                    ->copyable()
                    ->searchable()
                    ->description(fn($record) => Str::limit($record->custom_description ?? '—', 40)),

                TextColumn::make('item_details')
                    ->label('Item Info')
                    ->getStateUsing(function ($record) {
                        return new HtmlString("
                            <div class='flex flex-col gap-0.5 text-xs text-gray-500'>
                                " . ($record->category ? "<span>📁 {$record->category}</span>" : '') . "
                                " . ($record->metal_type ? "<span>⚙️ {$record->metal_type}</span>" : '') . "
                                " . ($record->retail_price ? "<span class='text-success-600 font-bold'>💰 \$" . number_format($record->retail_price, 2) . "</span>" : '') . "
                            </div>
                        ");
                    }),

                TextColumn::make('original_sale')
                    ->label('Original Sale')
                    ->getStateUsing(function ($record) {
                       $saleItem = \App\Models\SaleItem::where('product_item_id', $record->id)
    ->with(['sale.customer'])
    ->latest()
    ->first();

                        if (!$saleItem || !$saleItem->sale) {
                            return new HtmlString("<span class='text-gray-400 italic text-xs'>No sale record</span>");
                        }

                        $sale     = $saleItem->sale;
                        $customer = $sale->customer;
                        $name     = $customer
                            ? trim($customer->name . ' ' . ($customer->last_name ?? ''))
                            : 'Walk-in';
                        $date     = \Carbon\Carbon::parse($sale->created_at)
                            ->setTimezone(config('app.timezone', 'America/Denver'))
                            ->format('M d, Y');

                        $url = \App\Filament\Resources\SaleResource::getUrl('edit', ['record' => $sale->id]);

                        return new HtmlString("
                            <div class='flex flex-col gap-0.5'>
                                <a href='{$url}' target='_blank'
                                   class='font-mono font-bold text-primary-600 text-xs hover:underline'>
                                    {$sale->invoice_number}
                                </a>
                                <span class='text-xs text-gray-600'>{$name}</span>
                                <span class='text-[10px] text-gray-400'>{$date}</span>
                            </div>
                        ");
                    }),

                TextColumn::make('status')
                    ->label('Current Status')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'in_stock' => 'success',
                        'sold'     => 'danger',
                        'on_hold'  => 'warning',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn($state) => strtoupper(str_replace('_', ' ', $state))),
            ])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        CustomDatePicker::make('from')->label('Restocked From'),
                        CustomDatePicker::make('until')->label('Restocked Until'),
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
            ->defaultSort('updated_at', 'desc')
            ->defaultPaginationPageOption(15)
            ->paginationPageOptions([10, 15, 25, 50])
            ->emptyStateHeading('No restocked items found')
            ->emptyStateDescription('Items that have been restocked from Sold status will appear here.')
            ->emptyStateIcon('heroicon-o-arrow-path');
    }
}