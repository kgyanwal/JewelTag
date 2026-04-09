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

class FindStock extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon  = 'heroicon-o-magnifying-glass';
    protected static ?string $navigationLabel = 'Find Stock';
    protected static ?string $navigationGroup = 'Inventory';
    protected static string  $view            = 'filament.pages.find-stock';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    // 🚀 1. Override native table search (Top right search bar)
    public function updatedTableSearch(): void
    {
        $this->resetPage($this->getTablePaginationPageName());
    }

    // 🚀 2. Override native column searches
    public function updatedTableColumnSearches(): void
    {
        $this->resetPage($this->getTablePaginationPageName());
    }

    // 🚀 3. Override your custom form filters
    public function updated($property): void
    {
        if (str_starts_with($property, 'data.')) {
            $this->resetPage($this->getTablePaginationPageName());
        }
    }

    public function applyFilters(): void
    {
        $this->resetPage($this->getTablePaginationPageName());
    }

    protected function getHeaderActions(): array
    {
        return [
            HeaderAction::make('clear')
                ->label('Reset Filters')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->action(function () {
                    $this->form->fill();
                    // Cleanly deselect records and reset page without destroying table state completely
                    $this->deselectAllTableRecords();
                    $this->resetPage($this->getTablePaginationPageName());
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

    private function getInventoryValuationHtml(): string
    {
        $query = ProductItem::query();
        $this->applyQueryFilters($query);

        $count  = $query->count();
        $retail = $query->sum('retail_price');
        $cost   = $query->sum('cost_price');

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
                                Tabs\Tab::make('General')
                                    ->icon('heroicon-o-tag')
                                    ->schema([
                                        Grid::make(4)->schema([
                                            TextInput::make('stock_number')
                                                ->label('Stock # / Barcode')
                                                ->placeholder('G10274...')
                                                ->live()->debounce(500),

                                            // ── JOB NUMBER SEARCH ─────────────────────────────
                                            TextInput::make('job_number')
                                                ->label('Job # / Deposit No.')
                                                ->placeholder('9363...')
                                                ->live()
                                                ->debounce(500),

                                            TextInput::make('description')
                                                ->label('Keywords')
                                                ->placeholder('Gold, Necklace...')
                                                ->live()->debounce(500),

                                            Select::make('status')
                                                ->label('Status')
                                                ->options([
                                                    'in_stock' => 'In Stock',
                                                    'sold'     => 'Sold',
                                                    'on_hold'  => 'On Hold',
                                                    'memo'     => 'On Memo',
                                                ])->live(),

                                            // 1. Department Fix
                                            Select::make('department')
                                                ->options(fn() => collect($settings['departments'] ?? [])
                                                    ->filter(fn($item) => !empty($item['name'])) // 🚀 Strip out nulls
                                                    ->pluck('name', 'name'))
                                                ->searchable()->live(),

                                            // 2. Sub-Department Fix
                                            Select::make('sub_department')
                                                ->options(fn() => collect($settings['sub_departments'] ?? [])
                                                    ->filter() // 🚀 Strip out nulls
                                                    ->mapWithKeys(fn($i) => [$i => $i]))
                                                ->searchable()->live(),

                                            // 3. Category Fix
                                            Select::make('category')
                                                ->options(fn() => collect($settings['categories'] ?? [])
                                                    ->filter() // 🚀 Strip out nulls
                                                    ->mapWithKeys(fn($i) => [$i => $i]))
                                                ->searchable()->live(),

                                            // 4. Metal Type Fix
                                            Select::make('metal_type')
                                                ->label('Metal Karat')
                                                ->options(fn() => collect($settings['metal_types'] ?? [])
                                                    ->filter() // 🚀 Strip out nulls
                                                    ->mapWithKeys(fn($i) => [$i => $i]))
                                                ->searchable()->live(),

                                            TextInput::make('size')->label('Size/Length')->live(),
                                        ]),
                                    ]),

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

    protected function applyQueryFilters(Builder $query): Builder
    {
        $f = $this->data;

        return $query
            ->when($f['stock_number'] ?? null, fn($q, $v) => $q->where('barcode', 'like', "%{$v}%"))
            ->when($f['description'] ?? null,  fn($q, $v) => $q->where('custom_description', 'like', "%{$v}%"))
            ->when($f['status'] ?? null,        fn($q, $v) => $q->where('status', $v))
            ->when($f['department'] ?? null,    fn($q, $v) => $q->where('department', $v))
            ->when($f['sub_department'] ?? null, fn($q, $v) => $q->where('sub_department', $v))
            ->when($f['category'] ?? null,      fn($q, $v) => $q->where('category', $v))
            ->when($f['metal_type'] ?? null,    fn($q, $v) => $q->where('metal_type', $v))
            ->when($f['size'] ?? null,          fn($q, $v) => $q->where('size', 'like', "%{$v}%"))
            ->when($f['shape'] ?? null,         fn($q, $v) => $q->where('shape', $v))
            ->when($f['color'] ?? null,         fn($q, $v) => $q->where('color', $v))
            ->when($f['clarity'] ?? null,       fn($q, $v) => $q->where('clarity', $v))
            ->when($f['cut'] ?? null,           fn($q, $v) => $q->where('cut', $v))
            ->when($f['certificate_number'] ?? null, fn($q, $v) => $q->where('certificate_number', 'like', "%{$v}%"))
            ->when($f['is_lab_grown'] ?? null,  fn($q, $v) => $q->where('is_lab_grown', $v))
            ->when($f['diamond_weight_from'] ?? null, fn($q, $v) => $q->where('diamond_weight', '>=', $v))
            ->when($f['diamond_weight_to'] ?? null,   fn($q, $v) => $q->where('diamond_weight', '<=', $v))
            ->when($f['supplier_id'] ?? null,   fn($q, $v) => $q->where('supplier_id', $v))
            ->when($f['supplier_code'] ?? null, fn($q, $v) => $q->where('supplier_code', 'like', "%{$v}%"))
            ->when($f['serial_number'] ?? null, fn($q, $v) => $q->where('serial_number', 'like', "%{$v}%"))
            ->when($f['rfid_code'] ?? null,     fn($q, $v) => $q->where('rfid_code', 'like', "%{$v}%"))
            ->when($f['is_memo'] !== null && $f['is_memo'] !== '',     fn($q) => $q->where('is_memo', $f['is_memo']))
            ->when($f['is_trade_in'] !== null && $f['is_trade_in'] !== '', fn($q) => $q->where('is_trade_in', $f['is_trade_in']))
            ->when($f['price_from'] ?? null,    fn($q, $v) => $q->where('retail_price', '>=', $v))
            ->when($f['price_to'] ?? null,      fn($q, $v) => $q->where('retail_price', '<=', $v))
            ->when(
                $f['job_number'] ?? null,
                fn($q, $v) =>
                $q->whereHas(
                    'saleItem.sale',
                    fn($q) =>
                    $q->where('invoice_number', 'like', "%-{$v}")
                        ->orWhere('invoice_number', 'like', "%{$v}%")
                )
            );
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ProductItem::query())
            ->modifyQueryUsing(fn(Builder $query) => $this->applyQueryFilters($query))
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
                    ->description(
                        fn($record) =>
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
                    ->color(fn($state) => match ($state) {
                        'in_stock' => 'success',
                        'sold'     => 'danger',
                        'on_hold'  => 'warning',
                        'memo'     => 'info',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn($state) => Str::headline($state)),

                TextColumn::make('linked_job')
                    ->label('Linked Job / Deposit')
                    ->getStateUsing(function (ProductItem $record) {
                        $saleItems = \App\Models\SaleItem::where('product_item_id', $record->id)
                            ->with(['sale', 'sale.customer'])
                            ->get();

                        if ($saleItems->isEmpty()) {
                            return new HtmlString(
                                "<span class='text-[10px] text-gray-300 italic'>No sale</span>"
                            );
                        }

                        return new HtmlString(
                            $saleItems->map(function ($si) {
                                $inv     = $si->sale?->invoice_number ?? '—';
                                $parts   = explode('-', $inv);
                                $jobNo   = end($parts);
                                $name    = htmlspecialchars(trim(
                                    ($si->sale?->customer?->name ?? '') . ' ' .
                                        ($si->sale?->customer?->last_name ?? '')
                                ));
                                $balance = floatval($si->sale?->balance_due ?? 0);

                                $badge = $balance > 0.01
                                    ? "<span class='text-[9px] bg-danger-100 text-danger-600 px-1.5 py-0.5 rounded-full font-bold'>\$" . number_format($balance, 2) . " due</span>"
                                    : "<span class='text-[9px] bg-success-100 text-success-700 px-1.5 py-0.5 rounded-full font-bold'>Paid ✓</span>";

                                $url = \App\Filament\Resources\SaleResource::getUrl('edit', ['record' => $si->sale_id]);

                                return "
                                    <div class='mb-1.5 last:mb-0'>
                                        <a href='{$url}' class='font-mono text-xs font-bold text-primary-600 hover:underline'>
                                            Job #{$jobNo}
                                        </a>
                                        {$badge}
                                        <div class='text-[10px] text-gray-500 mt-0.5'>{$name}</div>
                                    </div>
                                ";
                            })->implode('')
                        );
                    })
                    ->html()
                    ->toggleable(isToggledHiddenByDefault: true),
TextColumn::make('date_added')
    ->label('DATE IN')
    ->getStateUsing(function (ProductItem $record) {
        $date = $record->date_in ?? $record->created_at;
        return $date?->format('M d, Y');
    })
    ->description(function (ProductItem $record) {
        $date = $record->date_in ?? $record->created_at;
        return $date?->format('h:i A');
    })
    ->size('xs')
    ->color('gray')
    ->grow(false),

TextColumn::make('created_at')
    ->label('CREATED')
    ->date('M d, Y')
    ->description(fn($record) => $record->created_at?->format('h:i A'))
    ->sortable()
    ->size('xs')
    ->color('gray')
    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                 ViewAction::make()
                        ->modalHeading('Stock Item Details')
                        ->modalWidth('5xl')
                        ->infolist(fn(Infolist $infolist, $record) => $infolist->schema([
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

                            // ── WHO BOUGHT THIS ITEM ──────────────────────────────────────
                            InfoSection::make('🛒 Sales History — Who Bought This')
                                ->schema([
                                    \Filament\Infolists\Components\TextEntry::make('sales_history')
                                        ->label('')
                                        ->state(fn() => 'loaded')
                                        ->formatStateUsing(function () use ($record) {
                                            $saleItems = \App\Models\SaleItem::where('product_item_id', $record->id)
                                                ->with(['sale.customer', 'sale.payments'])
                                                ->latest()
                                                ->get();

                                            if ($saleItems->isEmpty()) {
                                                return new HtmlString("
                                <div class='text-center py-6 text-gray-400 italic text-sm'>
                                    No sales recorded for this item.
                                </div>
                            ");
                                            }

                                            $rows = $saleItems->map(function ($si) {
                                                $sale     = $si->sale;
                                                $customer = $sale?->customer;
                                                $name     = $customer
                                                    ? trim($customer->name . ' ' . ($customer->last_name ?? ''))
                                                    : 'Walk-in';
                                                $phone    = $customer?->phone ?? '—';
                                                $invoice  = $sale?->invoice_number ?? '—';
                                                $date     = $sale?->created_at?->format('M d, Y') ?? '—';
                                                $total    = '$' . number_format($sale?->final_total ?? 0, 2);
                                                $paid     = '$' . number_format($sale?->payments?->sum('amount') ?? 0, 2);
                                                $balance  = floatval($sale?->balance_due ?? 0);
                                                $status   = $sale?->status ?? 'unknown';
                                                $method   = strtoupper($sale?->payment_method ?? '—');

                                                $statusColor = match ($status) {
                                                    'completed' => 'bg-success-100 text-success-700',
                                                    'pending'   => 'bg-warning-100 text-warning-700',
                                                    default     => 'bg-gray-100 text-gray-600',
                                                };

                                                $balanceBadge = $balance > 0.01
                                                    ? "<span class='bg-danger-100 text-danger-700 text-[10px] font-bold px-2 py-0.5 rounded-full'>Balance: \${$balance}</span>"
                                                    : "<span class='bg-success-100 text-success-700 text-[10px] font-bold px-2 py-0.5 rounded-full'>✓ Fully Paid</span>";

                                                $editUrl = \App\Filament\Resources\SaleResource::getUrl('edit', ['record' => $sale?->id]);

                                                return "
                                <div class='border border-gray-100 rounded-xl p-4 mb-3 bg-white shadow-sm hover:shadow-md transition'>
                                    <div class='flex justify-between items-start mb-3'>
                                        <div>
                                            <a href='{$editUrl}' target='_blank'
                                               class='font-mono font-black text-primary-600 text-sm hover:underline'>
                                                Invoice #{$invoice}
                                            </a>
                                            <span class='ml-2 text-xs text-gray-400'>{$date}</span>
                                        </div>
                                        <div class='flex gap-2 items-center'>
                                            <span class='{$statusColor} text-[10px] font-bold px-2 py-0.5 rounded-full uppercase'>{$status}</span>
                                            {$balanceBadge}
                                        </div>
                                    </div>
                                    <div class='grid grid-cols-2 gap-3 text-sm'>
                                        <div>
                                            <div class='text-[10px] text-gray-400 uppercase font-bold mb-0.5'>Customer</div>
                                            <div class='font-semibold text-gray-800'>{$name}</div>
                                            <div class='text-xs text-gray-500'>{$phone}</div>
                                        </div>
                                        <div>
                                            <div class='text-[10px] text-gray-400 uppercase font-bold mb-0.5'>Payment</div>
                                            <div class='font-semibold text-gray-800'>{$total} total</div>
                                            <div class='text-xs text-gray-500'>Paid: {$paid} via {$method}</div>
                                        </div>
                                    </div>
                                </div>
                            ";
                                            })->implode('');

                                            return new HtmlString("<div class='space-y-1'>{$rows}</div>");
                                        })
                                        ->html(),
                                ]),
                        ])),
                TableActionGroup::make([
                   

                    EditAction::make()
                        ->url(fn($record) => ProductItemResource::getUrl('edit', ['record' => $record])),

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
                                ->label('Destination Store')
                                ->options(Tenant::where('id', '!=', tenant('id'))->pluck('id', 'id'))
                                ->required(),
                            \Filament\Forms\Components\Textarea::make('notes')
                                ->label('Transfer Notes / Reason')
                                ->placeholder('e.g. Sending for display, customer request...')
                                ->rows(2),
                        ])
                        ->action(function (ProductItem $record, array $data) {
                            $sourceTenantId = tenant('id');
                            $targetTenantId = $data['target_tenant_id'];

                            // 1. Create transfer log in SOURCE tenant
                            $transfer = \App\Models\StockTransfer::create([
                                'transfer_number' => 'TRF-' . date('Ymd') . '-' . rand(100, 999),
                                'from_store_id'   => $record->store_id ?? 1,
                                'to_store_id'     => 1, // default store in destination tenant
                                'from_tenant'     => $sourceTenantId,
                                'to_tenant'       => $targetTenantId,
                                'status'          => 'pending',
                                'item_snapshot'   => [$record->toArray()],
                                'barcode'         => $record->barcode,
                                'transferred_by'  => auth()->user()->name,
                                'notes'           => $data['notes'] ?? null,
                                'transfer_date'   => now(),
                            ]);

                            // Link the item to the transfer
                            \App\Models\StockTransferItem::create([
                                'stock_transfer_id' => $transfer->id,
                                'product_item_id'   => $record->id,
                            ]);

                            // 2. Mark item as on_hold while awaiting acceptance
                            $record->update(['status' => 'on_hold']);

                            // 3. Switch to destination tenant and create mirror transfer record
                            $destTenant = Tenant::find($targetTenantId);
                            if ($destTenant) {
                                tenancy()->initialize($destTenant);

                                \App\Models\StockTransfer::create([
                                    'transfer_number' => $transfer->transfer_number,
                                    'from_store_id'   => $record->store_id ?? 1,
                                    'to_store_id'     => 1,
                                    'from_tenant'     => $sourceTenantId,
                                    'to_tenant'       => $targetTenantId,
                                    'status'          => 'pending',
                                    'item_snapshot'   => [$record->toArray()],
                                    'barcode'         => $record->barcode,
                                    'transferred_by'  => auth()->user()->name,
                                    'notes'           => $data['notes'] ?? null,
                                    'transfer_date'   => now(),
                                ]);

                                // Notify all users in destination tenant
                                $recipients = User::all();
                                \Filament\Notifications\Notification::make()
                                    ->title('📦 Incoming Stock Transfer')
                                    ->body("Item #{$record->barcode} is being sent from store {$sourceTenantId}. Go to Stock Transfers to Accept or Deny.")
                                    ->warning()
                                    ->sendToDatabase($recipients);

                                tenancy()->initialize(Tenant::find($sourceTenantId));
                            }

                            Notification::make()
                                ->title('Transfer Request Sent')
                                ->body("Item #{$record->barcode} is pending — awaiting acceptance from {$targetTenantId}.")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
           ->bulkActions([
                BulkAction::make('bulk_hold')
                    ->label('Batch Hold')
                    ->icon('heroicon-o-hand-raised')
                    ->color('warning')
                    // 🚀 THE FIX: Use standard collection processing
                    ->action(function (EloquentCollection $records, BulkAction $action) {
                        $records->each->update(['status' => 'on_hold']);
                        Notification::make()->title('Items placed on hold')->success()->send();
                        $action->deselectRecordsAfterCompletion();
                    }),

               BulkAction::make('bulk_transfer')
    ->label('Batch Transfer')
    ->icon('heroicon-o-truck')
    ->color('info')
    ->form([
        Select::make('target_tenant_id')
            ->label('Destination Store')
            ->options(Tenant::where('id', '!=', tenant('id'))->pluck('id', 'id'))
            ->required(),
        \Filament\Forms\Components\Textarea::make('notes')
            ->label('Transfer Notes / Reason')
            ->rows(2),
    ])
    ->action(function (EloquentCollection $records, array $data, BulkAction $action) {

        $sourceTenantId = tenant('id');
        $targetTenantId = $data['target_tenant_id'];
        $transferNumber = 'TRF-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        $staffName      = auth()->user()->name;

        // Fresh models to avoid stale state
        $processRecords = ProductItem::whereIn('id', $records->pluck('id'))->get();

        if ($processRecords->isEmpty()) {
            Notification::make()->title('No items found')->danger()->send();
            return;
        }

        $snapshot = [];

        // ── 1. Build snapshot + mark on_hold in SOURCE ────────────────
        foreach ($processRecords as $record) {
            $itemArray = $record->toArray();
            $itemArray['supplier_company_name'] = $record->supplier?->company_name;
            $snapshot[] = $itemArray;
            $record->update(['status' => 'on_hold']);
        }

        // ── 2. Create ONE transfer record in SOURCE ────────────────────
        $transfer = \App\Models\StockTransfer::create([
            'transfer_number' => $transferNumber,
            'from_store_id'   => auth()->user()->store_id ?? 1,
            'to_store_id'     => 1,
            'from_tenant'     => $sourceTenantId,
            'to_tenant'       => $targetTenantId,
            'status'          => 'pending',
            'item_snapshot'   => $snapshot,
            'transferred_by'  => $staffName,
            'notes'           => $data['notes'] ?? null,
            'transfer_date'   => now(),
        ]);

        // ── 3. Link each item to the transfer ─────────────────────────
        foreach ($processRecords as $record) {
            \App\Models\StockTransferItem::create([
                'stock_transfer_id' => $transfer->id,
                'product_item_id'   => $record->id,
            ]);
        }

        // ── 4. Create ONE mirror record in DESTINATION (safe upsert) ──
        $destTenant = Tenant::find($targetTenantId);
        if ($destTenant) {
            tenancy()->initialize($destTenant);

            \App\Models\StockTransfer::firstOrCreate(
                ['transfer_number' => $transferNumber],
                [
                    'from_store_id'  => 1,
                    'to_store_id'    => 1,
                    'from_tenant'    => $sourceTenantId,
                    'to_tenant'      => $targetTenantId,
                    'status'         => 'pending',
                    'item_snapshot'  => $snapshot,
                    'transferred_by' => $staffName,
                    'notes'          => $data['notes'] ?? null,
                    'transfer_date'  => now(),
                ]
            );

            $count = count($snapshot);
            User::all()->each(fn($u) =>
                \Filament\Notifications\Notification::make()
                    ->title('📦 Incoming Batch Transfer')
                    ->body("{$count} item(s) being sent from [{$sourceTenantId}]. Transfer #: {$transferNumber}. Go to Stock Transfers to Accept or Deny.")
                    ->warning()
                    ->sendToDatabase($u)
            );

            tenancy()->initialize(Tenant::find($sourceTenantId));
        }

        Notification::make()
            ->title('✅ Batch Transfer Sent')
            ->body(count($snapshot) . ' item(s) pending acceptance by [' . $targetTenantId . '].')
            ->success()
            ->send();

        $action->deselectRecordsAfterCompletion();
    }),
                BulkAction::make('bulk_print_tags')
                    ->label('Print Selected Tags')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Bulk Tags Print?')
                    ->modalDescription('Are you sure you want to send these items to the Zebra printer?')
                    ->action(function (EloquentCollection $records, BulkAction $action) {
                        $service     = new ZebraPrinterService();
                        $combinedZpl = "";
                        
                        foreach ($records as $record) {
                            $combinedZpl .= $service->getZplCode($record);
                        }
                        
                        $this->dispatch('print-zpl-locally', zpl: $combinedZpl);
                        $action->deselectRecordsAfterCompletion();
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No stock matches your criteria');
    }

    protected function performStockTransfer(ProductItem $record, string $targetId)
    {
        $sourceId   = tenant('id');
        $itemData   = $record->toArray();
        $barcode    = $record->barcode;
        $vendorName = $record->supplier?->company_name;

        try {
            DB::transaction(function () use ($itemData, $targetId, $vendorName, $record, $sourceId, $barcode) {
                $targetTenant = Tenant::find($targetId);
                tenancy()->initialize($targetTenant);

                // Auto-create vendor in destination if not found
                $targetSupplier = Supplier::where('company_name', $vendorName)->first();
                if (!$targetSupplier) {
                    // Use updateOrCreate without triggering LogsActivity observer
                    $targetSupplier = Supplier::withoutEvents(function () use ($vendorName, $record) {
                        return Supplier::create([
                            'company_name'  => $vendorName ?? 'Unknown Vendor',
                            'supplier_code' => $record->supplier?->supplier_code ?? null,
                        ]);
                    });
                }
                unset($itemData['id'], $itemData['created_at'], $itemData['updated_at']);
                $itemData['supplier_id'] = $targetSupplier->id;
                $itemData['status']      = 'in_stock';
                $itemData['store_id']    = 1;

                // Check if barcode already exists in destination — skip if so
                $existsInDestination = ProductItem::where('barcode', $barcode)->exists();
                if ($existsInDestination) {
                    tenancy()->initialize(Tenant::find($sourceId));
                    return; // silently skip — bulk action will count this
                }

                ProductItem::withoutEvents(function () use ($itemData) {
                    ProductItem::create($itemData);
                });
                $recipients = User::all();
                Notification::make()
                    ->title('Incoming Stock Transfer')
                    ->body("Item #{$barcode} arrived from Store {$sourceId}.")
                    ->success()
                    ->sendToDatabase($recipients);

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