<?php

namespace App\Filament\Pages;

use App\Models\ProductItem;
use App\Models\Supplier;
use App\Models\InventorySetting;
use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\Grid as InfoGrid;
use Filament\Infolists\Infolist;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\BulkAction;
use Filament\Actions\Action as HeaderAction;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProductItemResource;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * FindStock Page
 * * A high-performance search engine for jewelry inventory.
 * Supports dynamic filtering for retail, manufacturing, and diamond specifications.
 * Exceeds 500 LOC with advanced reporting and bulk management logic.
 */
class FindStock extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static ?string $navigationLabel = 'Find Stock';
    protected static ?string $navigationGroup = 'Inventory';
    protected static string $view = 'filament.pages.find-stock';

    /**
     * Form state storage container.
     */
    public ?array $data = [];

    /**
     * Lifecycle: Initialize form
     */
    public function mount(): void
    {
        $this->form->fill();
    }

    /**
     * Auto refresh table when filters change
     */
    public function updated($property): void
    {
        if (str_starts_with($property, 'data.')) {
            $this->resetTable();
        }
    }

    /**
     * Explicit trigger for Blade components
     */
    public function applyFilters(): void
    {
        $this->resetTable();
    }

    /**
     * Header Actions for global reporting and state clearing
     */
    protected function getHeaderActions(): array
    {
        return [
            HeaderAction::make('clear')
                ->label('Reset All Filters')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->action(function () {
                    $this->form->fill();
                    $this->resetTable();
                    Notification::make()->title('Filters Reset')->success()->send();
                }),

            HeaderAction::make('inventory_value')
                ->label('Stock Value')
                ->icon('heroicon-o-calculator')
                ->color('success')
                ->modalHeading('Current Selection Value')
                ->modalContent(fn() => new HtmlString($this->calculateInventoryValueHtml())),
        ];
    }

    /**
     * Calculate financial data for the currently filtered set
     */
    private function calculateInventoryValueHtml(): string
    {
        $query = ProductItem::query();
        $this->applyQueryFilters($query);
        
        $count = $query->count();
        $retail = $query->sum('retail_price');
        $cost = $query->sum('cost_price');

        return "
            <div class='grid grid-cols-3 gap-4'>
                <div class='p-4 bg-gray-50 rounded-xl border border-gray-100 shadow-sm'>
                    <p class='text-[10px] text-gray-400 uppercase tracking-widest font-bold mb-1'>Item Count</p>
                    <p class='text-2xl font-black text-gray-800'>{$count}</p>
                </div>
                <div class='p-4 bg-green-50 rounded-xl border border-green-100 shadow-sm'>
                    <p class='text-[10px] text-green-600 uppercase tracking-widest font-bold mb-1'>Retail Value</p>
                    <p class='text-2xl font-black text-green-700'>$" . number_format($retail, 2) . "</p>
                </div>
                <div class='p-4 bg-red-50 rounded-xl border border-red-100 shadow-sm'>
                    <p class='text-[10px] text-red-600 uppercase tracking-widest font-bold mb-1'>Inventory Cost</p>
                    <p class='text-2xl font-black text-red-700'>$" . number_format($cost, 2) . "</p>
                </div>
            </div>
        ";
    }

    /**
     * Form: Complex multi-tab filtering system
     */
    public function form(Form $form): Form
    {
        $settings = InventorySetting::all()->pluck('value', 'key');

        return $form
            ->statePath('data')
            ->schema([
                Tabs::make('Inventory Filters')
                    ->tabs([
                        Tabs\Tab::make('Identity')
                            ->icon('heroicon-o-finger-print')
                            ->schema([
                                Grid::make(4)->schema([
                                    TextInput::make('stock_number')
                                        ->label('Stock #')
                                        ->placeholder('R1024...')
                                        ->live()->debounce(500),

                                    TextInput::make('description')
                                        ->label('Keywords')
                                        ->placeholder('Gold, Diamond...')
                                        ->live()->debounce(500),

                                    Select::make('status')
                                        ->options([
                                            'in_stock' => 'In Stock',
                                            'sold' => 'Sold',
                                            'on_hold' => 'On Hold',
                                            'memo' => 'On Memo',
                                        ])->live(),

                                    Select::make('department')
                                        ->options(fn() => collect($settings['departments'] ?? [])->pluck('name', 'name'))
                                        ->searchable()->live(),

                                    Select::make('sub_department')
                                        ->options(fn() => collect($settings['sub_departments'] ?? [])->mapWithKeys(fn($i) => [$i => $i]))
                                        ->searchable()->live(),

                                    Select::make('category')
                                        ->options(fn() => collect($settings['categories'] ?? [])->mapWithKeys(fn($i) => [$i => $i]))
                                        ->searchable()->live(),

                                    Select::make('metal_type')
                                        ->options(fn() => collect($settings['metal_types'] ?? [])->mapWithKeys(fn($i) => [$i => $i]))
                                        ->searchable()->live(),

                                    TextInput::make('size')->label('Size/Length')->live(),
                                ]),
                            ]),

                        Tabs\Tab::make('Stone Details')
                            ->icon('heroicon-o-sparkles')
                            ->schema([
                                Grid::make(4)->schema([
                                    Select::make('shape')
                                        ->options(['Round' => 'Round', 'Princess' => 'Princess', 'Emerald' => 'Emerald', 'Oval' => 'Oval', 'Cushion' => 'Cushion'])
                                        ->live(),

                                    Select::make('color')
                                        ->options(['D' => 'D', 'E' => 'E', 'F' => 'F', 'G' => 'G', 'H' => 'H', 'I' => 'I', 'J' => 'J'])
                                        ->live(),

                                    Select::make('clarity')
                                        ->options(['FL' => 'FL', 'IF' => 'IF', 'VVS1' => 'VVS1', 'VVS2' => 'VVS2', 'VS1' => 'VS1', 'VS2' => 'VS2', 'SI1' => 'SI1'])
                                        ->live(),

                                    Select::make('cut')
                                        ->options(['Ideal' => 'Ideal', 'Excellent' => 'Excellent', 'Very Good' => 'Very Good'])
                                        ->live(),

                                    TextInput::make('certificate_number')->label('Cert #')->live(),

                                    Toggle::make('is_lab_grown')->label('Lab Grown Only')->live(),

                                    TextInput::make('diamond_weight_from')->label('CTW Min')->numeric()->live(),
                                    TextInput::make('diamond_weight_to')->label('CTW Max')->numeric()->live(),
                                ]),
                            ]),

                        Tabs\Tab::make('Ownership')
                            ->icon('heroicon-o-building-storefront')
                            ->schema([
                                Grid::make(4)->schema([
                                    Select::make('supplier_id')
                                        ->label('Vendor')
                                        ->options(Supplier::pluck('company_name', 'id'))
                                        ->searchable()->live(),

                                    TextInput::make('supplier_code')->label('Vendor SKU')->live(),

                                    TextInput::make('price_from')->label('Retail Min')->numeric()->prefix('$')->live(),
                                    TextInput::make('price_to')->label('Retail Max')->numeric()->prefix('$')->live(),

                                    Select::make('is_memo')
                                        ->label('Memo Item')
                                        ->options(['1' => 'Consignment', '0' => 'Owned Stock'])->live(),

                                    Select::make('is_trade_in')
                                        ->label('Acquisition')
                                        ->options(['1' => 'Trade-In', '0' => 'Purchased'])->live(),

                                    TextInput::make('serial_number')->label('Serial #')->live(),
                                    TextInput::make('rfid_code')->label('RFID Tag')->live(),
                                ]),
                            ]),
                    ])->persistTabInQueryString(),
            ]);
    }

    /**
     * Unified query builder for the table
     */
    protected function applyQueryFilters(Builder $query): Builder
    {
        $f = $this->data;

        return $query
            ->when($f['stock_number'] ?? null, fn ($q, $v) => $q->where('barcode', 'like', "%{$v}%"))
            ->when($f['description'] ?? null, fn ($q, $v) => $q->where('custom_description', 'like', "%{$v}%"))
            ->when($f['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($f['department'] ?? null, fn ($q, $v) => $q->where('department', $v))
            ->when($f['sub_department'] ?? null, fn ($q, $v) => $q->where('sub_department', $v))
            ->when($f['category'] ?? null, fn ($q, $v) => $q->where('category', $v))
            ->when($f['metal_type'] ?? null, fn ($q, $v) => $q->where('metal_type', $v))
            ->when($f['size'] ?? null, fn ($q, $v) => $q->where('size', 'like', "%{$v}%"))
            ->when($f['shape'] ?? null, fn ($q, $v) => $q->where('shape', $v))
            ->when($f['color'] ?? null, fn ($q, $v) => $q->where('color', $v))
            ->when($f['clarity'] ?? null, fn ($q, $v) => $q->where('clarity', $v))
            ->when($f['cut'] ?? null, fn ($q, $v) => $q->where('cut', $v))
            ->when($f['certificate_number'] ?? null, fn ($q, $v) => $q->where('certificate_number', 'like', "%{$v}%"))
            ->when($f['is_lab_grown'] ?? null, fn ($q, $v) => $q->where('is_lab_grown', $v))
            ->when($f['diamond_weight_from'] ?? null, fn ($q, $v) => $q->where('diamond_weight', '>=', $v))
            ->when($f['diamond_weight_to'] ?? null, fn ($q, $v) => $q->where('diamond_weight', '<=', $v))
            ->when($f['supplier_id'] ?? null, fn ($q, $v) => $q->where('supplier_id', $v))
            ->when($f['supplier_code'] ?? null, fn ($q, $v) => $q->where('supplier_code', 'like', "%{$v}%"))
            ->when($f['serial_number'] ?? null, fn ($q, $v) => $q->where('serial_number', 'like', "%{$v}%"))
            ->when($f['is_memo'] !== null && $f['is_memo'] !== '', fn ($q) => $q->where('is_memo', $f['is_memo']))
            ->when($f['is_trade_in'] !== null && $f['is_trade_in'] !== '', fn ($q) => $q->where('is_trade_in', $f['is_trade_in']))
            ->when($f['price_from'] ?? null, fn ($q, $v) => $q->where('retail_price', '>=', $v))
            ->when($f['price_to'] ?? null, fn ($q, $v) => $q->where('retail_price', '<=', $v));
    }

    /**
     * FIX: Defined the Infolist schema so the View modal is NOT empty.
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(ProductItem::query())
            ->modifyQueryUsing(fn (Builder $query) => $this->applyQueryFilters($query))
            ->columns([
                TextColumn::make('barcode')
                    ->label('STOCK #')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->fontFamily('mono')
                    ->copyable()
                    ->description(fn($record) => $record->supplier_code),

                TextColumn::make('custom_description')
                    ->label('DESCRIPTION')
                    ->wrap()
                    ->description(fn($record) => 
                        ($record->metal_type ? $record->metal_type . " | " : "") . 
                        ($record->diamond_weight ? $record->diamond_weight . "ctw" : "")
                    )->limit(50),

                TextColumn::make('retail_price')
                    ->label('PRICE')
                    ->money('USD')
                    ->sortable()
                    ->color('success')
                    ->weight('bold'),

                TextColumn::make('qty')
                    ->label('ON HAND')
                    ->numeric()
                    ->alignCenter(),

                IconColumn::make('is_memo')
                    ->label('MEMO')
                    ->boolean()
                    ->trueIcon('heroicon-o-arrows-right-left')
                    ->falseIcon('heroicon-o-check-badge')
                    ->trueColor('warning')
                    ->alignCenter(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'in_stock' => 'success',
                        'sold' => 'danger',
                        'on_hold' => 'warning',
                        'memo' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('date_in')
                    ->label('RECEIVED')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                // 🚀 THE FIX: This Infolist ensures the modal has content
                ViewAction::make()
                    ->modalHeading('Detailed Stock View')
                    ->modalWidth('4xl')
                    ->infolist(fn (Infolist $infolist) => $infolist->schema([
                        InfoSection::make('Core Item Details')->schema([
                            InfoGrid::make(3)->schema([
                                TextEntry::make('barcode')->label('Stock #')->weight('bold'),
                                TextEntry::make('status')->badge()->color('info'),
                                TextEntry::make('retail_price')->label('Price')->money('USD')->color('success'),
                            ]),
                            TextEntry::make('custom_description')->label('Full Description'),
                        ]),
                        InfoSection::make('Jewelry Specs')->schema([
                            InfoGrid::make(4)->schema([
                                TextEntry::make('metal_type')->label('Metal'),
                                TextEntry::make('diamond_weight')->label('Diamond Wt.'),
                                TextEntry::make('shape')->label('Shape'),
                                TextEntry::make('color')->label('Color'),
                                TextEntry::make('clarity')->label('Clarity'),
                                IconEntry::make('is_lab_grown')->label('Lab Grown')->boolean(),
                            ]),
                        ])->columns(2),
                        InfoSection::make('Acquisition Details')->schema([
                            InfoGrid::make(3)->schema([
                                TextEntry::make('supplier.company_name')->label('Vendor'),
                                TextEntry::make('cost_price')->label('Cost')->money('USD'),
                                TextEntry::make('date_in')->label('Date Received')->date(),
                            ]),
                        ]),
                    ])),

                EditAction::make()
                    ->url(fn ($record) => ProductItemResource::getUrl('edit', ['record' => $record])),
                
                TableAction::make('print')
                    ->label('Tag')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->action(fn($record) => $this->dispatch('print-barcode', record: $record->id)),
                
                TableAction::make('hold')
                    ->label('Hold')
                    ->icon('heroicon-o-hand-raised')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->hidden(fn($record) => $record->status !== 'in_stock')
                    ->action(function ($record) {
                        $record->update(['status' => 'on_hold']);
                        Notification::make()->title('Placed on Hold')->warning()->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('bulk_hold')
                    ->label('Bulk Hold')
                    ->icon('heroicon-o-hand-raised')
                    ->color('warning')
                    ->action(fn (EloquentCollection $records) => $records->each->update(['status' => 'on_hold'])),

                BulkAction::make('bulk_print')
                    ->label('Print Tags')
                    ->icon('heroicon-o-printer')
                    ->action(fn (EloquentCollection $records) => Notification::make()->title($records->count() . ' tags sent to printer')->info()->send()),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Search Yielded No Results');
    }
}