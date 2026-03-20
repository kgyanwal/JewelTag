<?php

namespace App\Filament\Pages;

use App\Models\ProductItem;
use App\Models\Supplier;
use App\Models\InventorySetting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ZebraPrinterService;
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
use Filament\Forms\Set;
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
use Filament\Tables\Actions\ActionGroup as TableActionGroup;
use Filament\Actions\Action as HeaderAction;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProductItemResource;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * FindStock Page
 * A high-performance search engine and inventory management hub.
 * Replicates professional POS layouts with deep jewelry specification tracking.
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
     * Form state storage container for live reactive filtering.
     */
    public ?array $data = [];

    /**
     * Lifecycle Hook: Initialize the filter form.
     */
    public function mount(): void
    {
        $this->form->fill();
    }

    /**
     * Auto-refresh: Triggers table reset immediately when form data changes.
     */
    public function updated($property): void
    {
        if (str_starts_with($property, 'data.')) {
            $this->resetTable();
        }
    }

    /**
     * Manual trigger for filters.
     */
    public function applyFilters(): void
    {
        $this->resetTable();
    }

    /**
     * Global Header Actions for the FindStock Hub.
     */
    protected function getHeaderActions(): array
    {
        return [
            HeaderAction::make('clear')
                ->label('Reset Filters')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->action(function () {
                    $this->form->fill();
                    $this->resetTable();
                    Notification::make()->title('Filters Cleared')->success()->send();
                }),

            HeaderAction::make('financial_stats')
                ->label('Stock Valuation')
                ->icon('heroicon-o-calculator')
                ->color('success')
                ->modalHeading('Filtered Inventory Value')
                ->modalContent(fn() => new HtmlString($this->getInventoryValuationHtml())),
        ];
    }

    /**
     * Financial Logic: Calculates the valuation of the currently filtered result set.
     */
    private function getInventoryValuationHtml(): string
    {
        $query = ProductItem::query();
        $this->applyQueryFilters($query);
        
        $count = $query->count();
        $retail = $query->sum('retail_price');
        $cost = $query->sum('cost_price');

        return "
            <div class='grid grid-cols-3 gap-6 p-4'>
                <div class='text-center p-4 bg-gray-50 rounded-2xl border border-gray-100 shadow-sm'>
                    <p class='text-[10px] text-gray-400 uppercase tracking-widest font-black mb-2'>Total Count</p>
                    <p class='text-3xl font-black text-gray-800'>{$count}</p>
                </div>
                <div class='text-center p-4 bg-green-50 rounded-2xl border border-green-100 shadow-sm'>
                    <p class='text-[10px] text-green-600 uppercase tracking-widest font-black mb-2'>Retail Value</p>
                    <p class='text-3xl font-black text-green-700'>$" . number_format($retail, 2) . "</p>
                </div>
                <div class='text-center p-4 bg-amber-50 rounded-2xl border border-amber-100 shadow-sm'>
                    <p class='text-[10px] text-amber-600 uppercase tracking-widest font-black mb-2'>Inventory Cost</p>
                    <p class='text-3xl font-black text-amber-700'>$" . number_format($cost, 2) . "</p>
                </div>
            </div>
        ";
    }

    /**
     * Form Construction: Multi-tab layout for deep jewelry searching.
     */
    public function form(Form $form): Form
    {
        $settings = InventorySetting::all()->pluck('value', 'key');

        return $form
            ->statePath('data')
            ->schema([
                Section::make()
                    ->compact()
                    ->schema([
                        Tabs::make('Inventory Filters')
                            ->tabs([
                                // --- TAB 1: GENERAL CLASSIFICATION ---
                                Tabs\Tab::make('General')
                                    ->icon('heroicon-o-tag')
                                    ->schema([
                                        Grid::make(4)->schema([
                                            TextInput::make('stock_number')
                                                ->label('Stock # / Barcode')
                                                ->placeholder('R1024...')
                                                ->live()->debounce(500),

                                            TextInput::make('description')
                                                ->label('Keywords')
                                                ->placeholder('Gold, Necklace...')
                                                ->live()->debounce(500),

                                            Select::make('status')
                                                ->label('Status')
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
                                                ->label('Metal Karat')
                                                ->options(fn() => collect($settings['metal_types'] ?? [])->mapWithKeys(fn($i) => [$i => $i]))
                                                ->searchable()->live(),

                                            TextInput::make('size')->label('Size/Length')->live(),
                                        ]),
                                    ]),

                                // --- TAB 2: JEWELRY & STONE SPECS ---
                                Tabs\Tab::make('Stone Details')
                                    ->icon('heroicon-o-sparkles')
                                    ->schema([
                                        Grid::make(4)->schema([
                                            Select::make('shape')
                                                ->options(['Round' => 'Round', 'Princess' => 'Princess', 'Emerald' => 'Emerald', 'Oval' => 'Oval', 'Cushion' => 'Cushion', 'Pear' => 'Pear', 'Marquise' => 'Marquise'])
                                                ->live(),

                                            Select::make('color')
                                                ->options(['D' => 'D', 'E' => 'E', 'F' => 'F', 'G' => 'G', 'H' => 'H', 'I' => 'I', 'J' => 'J', 'Fancy' => 'Fancy Color'])
                                                ->live(),

                                            Select::make('clarity')
                                                ->options(['FL' => 'FL', 'IF' => 'IF', 'VVS1' => 'VVS1', 'VVS2' => 'VVS2', 'VS1' => 'VS1', 'VS2' => 'VS2', 'SI1' => 'SI1', 'SI2' => 'SI2', 'I1' => 'I1'])
                                                ->live(),

                                            Select::make('cut')
                                                ->options(['Ideal' => 'Ideal', 'Excellent' => 'Excellent', 'Very Good' => 'Very Good', 'Good' => 'Good'])
                                                ->live(),

                                            TextInput::make('certificate_number')->label('Cert # (GIA/IGI)')->live(),

                                            Toggle::make('is_lab_grown')->label('Lab Grown Only')->live(),

                                            TextInput::make('diamond_weight_from')->label('Min CTW')->numeric()->live(),
                                            TextInput::make('diamond_weight_to')->label('Max CTW')->numeric()->live(),
                                        ]),
                                    ]),

                                // --- TAB 3: PROCUREMENT & VENDOR RELATIONS ---
                                Tabs\Tab::make('Acquisition')
                                    ->icon('heroicon-o-building-office')
                                    ->schema([
                                        Grid::make(4)->schema([
                                            Select::make('supplier_id')
                                                ->label('Primary Vendor')
                                                ->options(Supplier::pluck('company_name', 'id'))
                                                ->searchable()->live(),

                                            TextInput::make('supplier_code')->label('Vendor SKU')->live(),

                                            TextInput::make('price_from')->label('Retail Min')->numeric()->prefix('$')->live(),
                                            TextInput::make('price_to')->label('Retail Max')->numeric()->prefix('$')->live(),

                                            Select::make('is_memo')
                                                ->label('Memo Item')
                                                ->options(['1' => 'Consignment', '0' => 'Store Owned'])->live(),

                                            Select::make('is_trade_in')
                                                ->label('Source')
                                                ->options(['1' => 'Trade-In', '0' => 'Purchased Stock'])->live(),

                                            TextInput::make('serial_number')->label('Serial Number')->live(),
                                            TextInput::make('rfid_code')->label('RFID Tag ID')->live(),
                                        ]),
                                    ]),
                            ])->persistTabInQueryString(),
                    ]),
            ]);
    }

    /**
     * Global query filter logic shared by Table and Valuation.
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
            ->when($f['rfid_code'] ?? null, fn ($q, $v) => $q->where('rfid_code', 'like', "%{$v}%"))
            ->when($f['is_memo'] !== null && $f['is_memo'] !== '', fn ($q) => $q->where('is_memo', $f['is_memo']))
            ->when($f['is_trade_in'] !== null && $f['is_trade_in'] !== '', fn ($q) => $q->where('is_trade_in', $f['is_trade_in']))
            ->when($f['price_from'] ?? null, fn ($q, $v) => $q->where('retail_price', '>=', $v))
            ->when($f['price_to'] ?? null, fn ($q, $v) => $q->where('retail_price', '<=', $v));
    }

    /**
     * Table Definition: Includes the fixed Infolist for the ViewAction modal.
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
                    ->description(fn($record) => "SKU: " . ($record->supplier_code ?? 'N/A')),

                TextColumn::make('custom_description')
                    ->label('ITEM DESCRIPTION')
                    ->wrap()
                    ->description(fn($record) => 
                        new HtmlString("<span class='text-xs text-gray-500 font-medium uppercase'>" .
                        ($record->metal_type ? $record->metal_type : "") . 
                        ($record->diamond_weight ? " • " . $record->diamond_weight . "ctw" : "") .
                        ($record->shape ? " • " . $record->shape : "") .
                        "</span>")
                    )->limit(60),

                TextColumn::make('retail_price')
                    ->label('RETAIL PRICE')
                    ->money('USD')
                    ->sortable()
                    ->color('success')
                    ->weight('black'),

                TextColumn::make('qty')
                    ->label('HAND')
                    ->numeric()
                    ->alignCenter()
                    ->weight('medium'),

                IconColumn::make('is_memo')
                    ->label('MEMO')
                    ->boolean()
                    ->trueIcon('heroicon-o-arrows-right-left')
                    ->falseIcon('heroicon-o-check-badge')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->alignCenter(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'in_stock' => 'success',
                        'sold' => 'danger',
                        'on_hold' => 'warning',
                        'memo' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => Str::headline($state)),

                TextColumn::make('date_in')
                    ->label('DATE IN')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                TableActionGroup::make([
                    ViewAction::make()
                        ->modalHeading('Detailed Jewelry Specifications')
                        ->modalWidth('5xl')
                        ->infolist(fn (Infolist $infolist) => $infolist->schema([
                            InfoSection::make('Inventory Identity')->schema([
                                InfoGrid::make(3)->schema([
                                    TextEntry::make('barcode')->label('Stock Number')->weight('black')->size('lg'),
                                    TextEntry::make('status')->badge()->color('success'),
                                    TextEntry::make('retail_price')->label('Showroom Price')->money('USD')->color('success')->weight('bold'),
                                ]),
                                TextEntry::make('custom_description')->label('Display Description'),
                            ]),
                            InfoSection::make('Diamond & Gemstone Specifications')->schema([
                                InfoGrid::make(4)->schema([
                                    TextEntry::make('metal_type')->label('Metal Karat'),
                                    TextEntry::make('diamond_weight')->label('Total Carat Wt.'),
                                    TextEntry::make('shape')->label('Stone Shape'),
                                    TextEntry::make('color')->label('Stone Color'),
                                    TextEntry::make('clarity')->label('Clarity Grade'),
                                    TextEntry::make('cut')->label('Cut Grade'),
                                    TextEntry::make('certificate_number')->label('GIA/IGI Certificate'),
                                    IconEntry::make('is_lab_grown')->label('Lab Grown Diamond')->boolean(),
                                ]),
                            ])->columns(2),
                            InfoSection::make('Procurement History')->schema([
                                InfoGrid::make(3)->schema([
                                    TextEntry::make('supplier.company_name')->label('Original Vendor'),
                                    TextEntry::make('cost_price')->label('Acquisition Cost')->money('USD'),
                                    TextEntry::make('date_in')->label('Date Received')->date(),
                                    TextEntry::make('rfid_code')->label('RFID EPC Tag')->fontFamily('mono'),
                                ]),
                            ]),
                        ])),

                    EditAction::make()
                        ->url(fn ($record) => ProductItemResource::getUrl('edit', ['record' => $record])),
                    
                    TableAction::make('print')
                        ->label('Print Barcode')
                        ->icon('heroicon-o-printer')
                        ->color('gray')
                        ->action(fn($record) => $this->dispatch('print-barcode', record: $record->id)),
                    
                    TableAction::make('hold_item')
                        ->label('Place on Hold')
                        ->icon('heroicon-o-hand-raised')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->hidden(fn($record) => $record->status !== 'in_stock')
                        ->action(function ($record) {
                            $record->update(['status' => 'on_hold']);
                            Notification::make()->title('Item placed on hold')->warning()->send();
                        }),

                    TableAction::make('transfer_stock')
                        ->label('Transfer to Store')
                        ->icon('heroicon-o-truck')
                        ->color('info')
                        ->form([
                            Select::make('target_tenant_id')
                                ->label('Target Store')
                                ->options(Tenant::where('id', '!=', tenant('id'))->pluck('id', 'id'))
                                ->required(),
                        ])
                        ->action(function (ProductItem $record, array $data) {
                            $this->performStockTransfer($record, $data['target_tenant_id']);
                        }),
                ]),
            ])
            ->bulkActions([
                
                BulkAction::make('bulk_hold')
                    ->label('Batch Hold')
                    ->icon('heroicon-o-hand-raised')
                    ->color('warning')
                    ->action(fn (EloquentCollection $records) => $records->each->update(['status' => 'on_hold'])),

                BulkAction::make('bulk_transfer')
                    ->label('Batch Transfer')
                    ->icon('heroicon-o-truck')
                    ->color('info')
                    ->form([
                        Select::make('target_tenant_id')
                            ->label('Destination Store')
                            ->options(Tenant::where('id', '!=', tenant('id'))->pluck('id', 'id'))
                            ->required(),
                    ])
                    ->action(function (EloquentCollection $records, array $data) {
                        foreach ($records as $record) {
                            $this->performStockTransfer($record, $data['target_tenant_id']);
                        }
                    }),

                BulkAction::make('bulk_print_tags')
                    ->label('Print Selected Tags')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Print Jewelry Tags')
                    ->modalDescription('Are you sure you want to send these items to the Zebra printer?')
                    ->action(function (EloquentCollection $records) {
                        $service = new ZebraPrinterService();
                        $success = $service->bulkPrintJewelryTags($records);

                        if ($success) {
                            Notification::make()
                                ->title('Printing Started')
                                ->body(count($records) . ' tags sent to printer.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Print Error')
                                // 🚀 FIXED: Now correctly reports the printer IP from service
                                ->body('Could not connect to Zebra printer at ' . $service->getPrinterIp())
                                ->danger()
                                ->send();
                        }
                    }),    
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No stock matches your criteria');
    }

    /**
     * Business Logic: Performs the complex stock transfer between isolated tenant databases.
     */
    protected function performStockTransfer(ProductItem $record, string $targetId)
    {
        $sourceId = tenant('id');
        $itemData = $record->toArray();
        $barcode = $record->barcode;
        $vendorName = $record->supplier?->company_name;

        try {
            DB::transaction(function () use ($itemData, $targetId, $vendorName, $record, $sourceId, $barcode) {
                // Switch to Destination
                $targetTenant = Tenant::find($targetId);
                tenancy()->initialize($targetTenant);

                // Map local Vendor ID
                $targetSupplierId = Supplier::where('company_name', $vendorName)->value('id');

                // Prepare Payload
                unset($itemData['id'], $itemData['created_at'], $itemData['updated_at']);
                $itemData['supplier_id'] = $targetSupplierId;
                $itemData['status'] = 'in_stock';

                ProductItem::create($itemData);

                // Notify Destination
                $recipients = User::all(); // Destination store users
                Notification::make()
                    ->title('Incoming Stock Transfer')
                    ->body("Item #{$barcode} arrived from Store {$sourceId}.")
                    ->success()
                    ->sendToDatabase($recipients);

                // Return to Source and Remove
                tenancy()->initialize(Tenant::find($sourceId));
                $record->delete();
            });

            Notification::make()->title('Stock Moved Successfully')->success()->send();
        } catch (\Exception $e) {
            tenancy()->initialize(Tenant::find($sourceId));
            Notification::make()->title('Transfer Error')->body($e->getMessage())->danger()->send();
        }
    }
}