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
                $q->whereIn('id', function (\Illuminate\Database\Query\Builder $query) use ($v) {
                    $query->select('product_item_id')
                          ->from('sale_items')
                          ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                          ->where('sales.invoice_number', 'like', "%{$v}%")
                          // 🚀 FIX: Prevent finding ghost items that were already released/deleted
                          ->whereNull('sale_items.deleted_at'); 
                })
            );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Shared helpers for "Release from Laybuy"
    // ─────────────────────────────────────────────────────────────────────────

    private function buildReleaseFromLaybuyForm(ProductItem $record): array
    {
        $saleItem = \App\Models\SaleItem::where('product_item_id', $record->id)
            ->with(['sale.laybuy'])
            ->latest()
            ->first();

        $laybuy   = $saleItem?->sale?->laybuy;
        $laybuyNo = $laybuy?->laybuy_no ?? '—';
        $customer = $laybuy?->customer;
        $custName = $customer ? trim($customer->name . ' ' . ($customer->last_name ?? '')) : '—';

        return [
            // Warning banner — doubles as the confirmation message
            \Filament\Forms\Components\Placeholder::make('info_banner')
                ->hiddenLabel()
                ->content(new \Illuminate\Support\HtmlString("
                    <div class='p-3 bg-warning-50 border border-warning-200 rounded-lg text-sm'>
                        <div class='font-black text-warning-800 mb-1'>⚠️ Releasing: {$record->barcode}</div>
                        <div class='text-warning-700'>{$record->custom_description}</div>
                        <div class='mt-2 text-xs text-warning-600'>
                            Laybuy Plan: <strong>{$laybuyNo}</strong> — Customer: <strong>{$custName}</strong>
                        </div>
                        <div class='mt-2 text-xs text-danger-700 font-bold'>
                            This will release the item back to in-stock and adjust the payment balance.
                        </div>
                    </div>
                ")),

            \Filament\Forms\Components\Hidden::make('item_price'),
            \Filament\Forms\Components\Hidden::make('laybuy_no'),
            \Filament\Forms\Components\Hidden::make('total_amount'),
            \Filament\Forms\Components\Hidden::make('amount_paid'),
            \Filament\Forms\Components\Hidden::make('suggested_refund'),

            \Filament\Forms\Components\Placeholder::make('summary')
                ->hiddenLabel()
                ->content(function (\Filament\Forms\Get $get) {
                    $itemPrice   = number_format($get('item_price') ?? 0, 2);
                    $totalAmount = number_format($get('total_amount') ?? 0, 2);
                    $amountPaid  = number_format($get('amount_paid') ?? 0, 2);
                    $suggested   = number_format($get('suggested_refund') ?? 0, 2);
                    return new \Illuminate\Support\HtmlString("
                        <div class='grid grid-cols-2 gap-3 text-sm'>
                            <div class='p-2 bg-gray-50 rounded border'>
                                <div class='text-xs text-gray-400 uppercase font-bold'>Item Price</div>
                                <div class='font-black text-gray-800'>\${$itemPrice}</div>
                            </div>
                            <div class='p-2 bg-gray-50 rounded border'>
                                <div class='text-xs text-gray-400 uppercase font-bold'>Laybuy Total</div>
                                <div class='font-black text-gray-800'>\${$totalAmount}</div>
                            </div>
                            <div class='p-2 bg-green-50 rounded border border-green-200'>
                                <div class='text-xs text-green-600 uppercase font-bold'>Total Paid So Far</div>
                                <div class='font-black text-green-700'>\${$amountPaid}</div>
                            </div>
                            <div class='p-2 bg-blue-50 rounded border border-blue-200'>
                                <div class='text-xs text-blue-600 uppercase font-bold'>Suggested Refund</div>
                                <div class='font-black text-blue-700'>\${$suggested}</div>
                            </div>
                        </div>
                    ");
                }),

            \Filament\Forms\Components\TextInput::make('refund_amount')
                ->label('Amount to Refund / Deduct Today')
                ->numeric()
                ->prefix('$')
                ->required()
                ->helperText('This amount will be deducted from the laybuy balance and removed from today\'s payments.'),

            \Filament\Forms\Components\Select::make('refund_method')
                ->label('Refund Method')
                ->options(function () {
                    $json    = \Illuminate\Support\Facades\DB::table('site_settings')
                        ->where('key', 'payment_methods')->value('value');
                    $methods = $json ? json_decode($json, true) : ['CASH', 'VISA', 'MASTERCARD', 'AMEX'];
                    $options = [];
                    foreach ($methods as $m) {
                        $clean = strtoupper(trim($m));
                        if ($clean !== 'LAYBUY') $options[$clean] = $clean;
                    }
                    return $options;
                })
                ->default('CASH')
                ->required(),

            \Filament\Forms\Components\Textarea::make('reason')
                ->label('Reason for Removal')
                ->placeholder('e.g. Customer changed mind, keeping R016 only')
                ->rows(2),
        ];
    }

    private function mountReleaseFromLaybuy(\Filament\Forms\ComponentContainer $form, ProductItem $record): void
    {
        $saleItem = \App\Models\SaleItem::where('product_item_id', $record->id)
            ->with(['sale.laybuy'])
            ->latest()
            ->first();

        $laybuy      = $saleItem?->sale?->laybuy;
        $itemPrice   = floatval($saleItem?->sold_price ?? $record->retail_price ?? 0);
        $laybuyNo    = $laybuy?->laybuy_no ?? '—';
        $totalAmount = floatval($laybuy?->total_amount ?? 0);
        $amountPaid  = floatval($laybuy?->amount_paid ?? 0);

        $suggestedRefund = $totalAmount > 0
            ? round(($itemPrice / $totalAmount) * $amountPaid, 2)
            : 0;

        $form->fill([
            'item_price'       => $itemPrice,
            'laybuy_no'        => $laybuyNo,
            'total_amount'     => $totalAmount,
            'amount_paid'      => $amountPaid,
            'suggested_refund' => $suggestedRefund,
            'refund_amount'    => $suggestedRefund,
        ]);
    }

    private function executeReleaseFromLaybuy(ProductItem $record, array $data): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data) {

            $refundAmount = floatval($data['refund_amount'] ?? 0);
            $refundMethod = strtoupper(trim($data['refund_method'] ?? 'CASH'));
            $storeId      = auth()->user()->store_id ?? 1;

            // ── 1. ALWAYS release the item back to in_stock immediately ─────────
            $record->update([
                'status'          => 'in_stock',
                'hold_reason'     => null,
                'held_by_sale_id' => null,
            ]);

            // ── 2. Find the sale item and laybuy ─────────────────────────────────
            $saleItem = \App\Models\SaleItem::where('product_item_id', $record->id)
                ->with(['sale.laybuy'])
                ->latest()
                ->first();

            // 🚀 FIX: If this was just a "manual hold" (no active sale record), exit safely here
            // This prevents the system from crashing while still putting the item back in stock
            if (!$saleItem) {
                return; 
            }

            $sale   = $saleItem->sale;
            $laybuy = $sale?->laybuy;

            // ── 3. Remove the sale item row ───────────────────────────
            $saleItem->delete();

            // ── 4. Record a NEGATIVE payment to reverse the refund ───
            if ($refundAmount > 0 && $sale) {
                $lastPayment = \App\Models\Payment::where('sale_id', $sale->id)
                    ->where('amount', '>', 0)
                    ->latest('paid_at')
                    ->first();

                \App\Models\Payment::create([
                    'sale_id'  => $sale->id,
                    'amount'   => -abs($refundAmount),
                    'method'   => $refundMethod,
                    'paid_at'  => $lastPayment?->paid_at ?? now(),
                    'store_id' => $storeId,
                ]);
            }

            // ── 5. Recalculate sale totals ───────────────────────────
            if ($sale) {
                $newSubtotal   = \App\Models\SaleItem::where('sale_id', $sale->id)->sum('sold_price');
                $newFinalTotal = $newSubtotal;
                $totalPaid     = \App\Models\Payment::where('sale_id', $sale->id)->sum('amount');
                $newBalance    = max(0, $newFinalTotal - $totalPaid);

                $sale->update([
                    'subtotal'    => $newSubtotal,
                    'final_total' => $newFinalTotal,
                    'amount_paid' => max(0, $totalPaid),
                    'balance_due' => $newBalance,
                    'status'      => $newBalance <= 0.01 ? 'completed' : 'pending',
                ]);
            }

            // ── 6. Recalculate laybuy totals ─────────────────────────
            if ($laybuy) {
                $itemPrice      = floatval($saleItem->sold_price ?? 0);
                $newLaybuyTotal = max(0, floatval($laybuy->total_amount) - $itemPrice);
                $newLaybuyPaid  = max(0, floatval($laybuy->amount_paid) - $refundAmount);
                $newLaybuyBal   = max(0, $newLaybuyTotal - $newLaybuyPaid);

                $laybuy->update([
                    'total_amount' => $newLaybuyTotal,
                    'amount_paid'  => $newLaybuyPaid,
                    'balance_due'  => $newLaybuyBal,
                    'status'       => $newLaybuyBal <= 0.01 && $newLaybuyTotal > 0
                        ? 'completed'
                        : ($newLaybuyTotal <= 0 ? 'cancelled' : 'in_progress'),
                ]);
            }
        });

        Notification::make()
            ->title('Item Released Successfully')
            ->body("{$record->barcode} is now back in stock.")
            ->success()
            ->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ProductItem::query())
            ->modifyQueryUsing(fn(Builder $query) => $this->applyQueryFilters($query))
            ->columns([
                 TextColumn::make('photo')
        ->label('')
        ->getStateUsing(function (ProductItem $record) {
            $url      = $record->primary_image_url;
            $hasPhoto = $record->has_image;
            $badge    = !$hasPhoto
                ? "<div style='position:absolute;bottom:2px;right:2px;background:#ef444480;border-radius:3px;padding:1px 3px;font-size:7px;font-weight:800;color:white;line-height:1;'>NO PHOTO</div>"
                : '';
            return new HtmlString("
                <div style='position:relative;width:52px;height:52px;flex-shrink:0;'>
                    <img src='{$url}'
                         style='width:52px;height:52px;border-radius:8px;object-fit:cover;border:1px solid #e2e8f0;background:#f8fafc;'
                         onerror=\"this.src='/placeholders/jewelry-generic.svg'\" />
                    {$badge}
                </div>
            ");
        })
        ->html()
        ->grow(false),
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
\Filament\Infolists\Components\TextEntry::make('photos')
    ->label('Item Photos')
    ->state(fn() => 'loaded')
    ->formatStateUsing(function () use ($record) {
        $images = $record->all_images;
        if (empty($images)) {
            return new HtmlString("
                <div style='display:flex;align-items:center;gap:8px;padding:10px;background:#f8fafc;border:1px dashed #e2e8f0;border-radius:8px;'>
                    <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='#94a3b8' style='width:18px;height:18px;'>
                        <path stroke-linecap='round' stroke-linejoin='round' d='M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z' />
                        <path stroke-linecap='round' stroke-linejoin='round' d='M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z' />
                    </svg>
                    <span style='font-size:12px;color:#94a3b8;font-style:italic;'>No photos on file for this item.</span>
                </div>
            ");
        }

        $imgs = collect($images)->map(fn($url) => "
            <div style='position:relative;cursor:pointer;' onclick=\"window.open('{$url}','_blank')\">
                <img src='{$url}'
                     style='width:90px;height:90px;border-radius:10px;object-fit:cover;border:2px solid #e2e8f0;transition:transform .15s;'
                     onmouseover=\"this.style.transform='scale(1.05)'\"
                     onmouseout=\"this.style.transform='scale(1)'\"
                     onerror=\"this.src='/placeholders/jewelry-generic.svg'\" />
            </div>
        ")->implode('');

        return new HtmlString("
            <div style='display:flex;gap:10px;flex-wrap:wrap;margin-top:4px;'>
                {$imgs}
            </div>
            <p style='font-size:10px;color:#94a3b8;margin-top:6px;'>Click any photo to open full size.</p>
        ");
    })
    ->html()
    ->columnSpanFull(),
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

                // ── STANDALONE release_from_laybuy (outside the group) ────────
                // 🚀 FIX: Will ALWAYS show as long as the item is manually or systemically "on_hold"
                TableAction::make('release_from_laybuy')
                    ->label('Release from Hold/Laybuy')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->hidden(fn(ProductItem $record) => $record->status !== 'on_hold')
                    ->mountUsing(fn(\Filament\Forms\ComponentContainer $form, ProductItem $record) =>
                        $this->mountReleaseFromLaybuy($form, $record))
                    ->form(fn(ProductItem $record) => $this->buildReleaseFromLaybuyForm($record))
                    ->modalHeading('Release Item from Laybuy')
                    ->modalSubmitActionLabel('Yes, Release Item')
                    ->action(fn(ProductItem $record, array $data) =>
                        $this->executeReleaseFromLaybuy($record, $data)),

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

                    // ── GROUPED release_from_laybuy — MUST use a different name ──
                    TableAction::make('release_from_laybuy_group')
                        ->label('Release from Hold/Laybuy')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('warning')
                        // 🚀 FIX: Will ALWAYS show as long as the item is manually or systemically "on_hold"
                        ->hidden(fn(ProductItem $record) => $record->status !== 'on_hold')
                        ->mountUsing(fn(\Filament\Forms\ComponentContainer $form, ProductItem $record) =>
                            $this->mountReleaseFromLaybuy($form, $record))
                        ->form(fn(ProductItem $record) => $this->buildReleaseFromLaybuyForm($record))
                        ->modalHeading('Release Item from Laybuy')
                        ->modalSubmitActionLabel('Yes, Release Item')
                        ->action(fn(ProductItem $record, array $data) =>
                            $this->executeReleaseFromLaybuy($record, $data)),
                ]),
            ])
            ->bulkActions([
                BulkAction::make('bulk_hold')
                    ->label('Batch Hold')
                    ->icon('heroicon-o-hand-raised')
                    ->color('warning')
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

                        $processRecords = ProductItem::whereIn('id', $records->pluck('id'))->get();

                        if ($processRecords->isEmpty()) {
                            Notification::make()->title('No items found')->danger()->send();
                            return;
                        }

                        $snapshot = [];

                        foreach ($processRecords as $record) {
                            $itemArray = $record->toArray();
                            $itemArray['supplier_company_name'] = $record->supplier?->company_name;
                            $snapshot[] = $itemArray;
                            $record->update(['status' => 'in_transit']);
                        }

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

                        foreach ($processRecords as $record) {
                            \App\Models\StockTransferItem::create([
                                'stock_transfer_id' => $transfer->id,
                                'product_item_id'   => $record->id,
                            ]);
                        }

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
                            User::all()->each(
                                fn($u) =>
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

                $targetSupplier = Supplier::where('company_name', $vendorName)->first();
                if (!$targetSupplier) {
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

                $existsInDestination = ProductItem::where('barcode', $barcode)->exists();
                if ($existsInDestination) {
                    tenancy()->initialize(Tenant::find($sourceId));
                    return;
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