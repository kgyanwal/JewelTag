<?php

namespace App\Filament\Pages;

use App\Models\SaleItem;
use App\Models\Sale;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Columns\Summarizers\Sum;

class NonStockReport extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-wrench';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'Non-Stock Sales';
    protected static string $view = 'filament.pages.non-stock-report';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SaleItem::query()
                    ->where(function ($q) {
                        $q->where('is_non_stock', true)
                          ->orWhere(function ($q2) {
                              $q2->whereNull('product_item_id')
                                 ->whereNull('repair_id')
                                 ->whereNull('custom_order_id');
                          });
                    })
                    ->with(['sale.customer'])
            )
            ->columns([
                TextColumn::make('sale.invoice_number')
                    ->label('Job No.')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->url(fn (SaleItem $record) => $record->sale
                        ? \App\Filament\Resources\SaleResource::getUrl('edit', ['record' => $record->sale_id])
                        : null
                    ),

                TextColumn::make('stock_no_display')
                    ->label('Item')
                    ->badge()
                    ->color('gray')
                    ->default('NON-TAG'),

                TextColumn::make('custom_description')
                    ->label('Description')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('sale.customer.name')
                    ->label('Customer')
                    ->formatStateUsing(fn ($record) => $record->sale?->customer
                        ? $record->sale->customer->name . ' ' . $record->sale->customer->last_name
                        : 'Walk-in'
                    )
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('sale.created_at')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('qty')
                    ->label('Qty')
                    ->alignCenter(),

                TextColumn::make('cost_price')
                    ->label('Cost')
                    ->money('USD')
                    ->summarize(Sum::make()->money('USD')->label('Total Cost')),

                TextColumn::make('sold_price')
                    ->label('Sold')
                    ->money('USD')
                    ->summarize(Sum::make()->money('USD')->label('Total Sold')),

                TextColumn::make('profit')
                    ->label('Profit')
                    ->state(fn ($record) => floatval($record->sold_price) - floatval($record->cost_price))
                    ->money('USD')
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                    ->summarize(
                        \Filament\Tables\Columns\Summarizers\Summarizer::make()
                            ->label('Total Profit')
                            ->money('USD')
                            ->using(fn (\Illuminate\Database\Query\Builder $query) =>
                                $query->sum(DB::raw('sold_price - cost_price'))
                            )
                    ),

                TextColumn::make('import_source')
                    ->label('Source')
                    ->badge()
                    ->color(fn ($state) => $state === 'onswim_nonstock' ? 'warning' : 'gray')
                    ->formatStateUsing(fn ($state) => $state ? ($state === 'onswim_nonstock' ? 'Imported' : $state) : 'Manual')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('import_source')
                    ->label('Source')
                    ->options([
                        'onswim_nonstock' => 'Imported (OnSwim)',
                        ''                => 'Manual (In-Store)',
                    ]),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('From'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'],  fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Action::make('make_stock_item')
                    ->label('Add to Inventory')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->color('success')
                    ->visible(fn (SaleItem $record) =>
                        $record->is_non_stock ||
                        (is_null($record->product_item_id) && is_null($record->repair_id) && is_null($record->custom_order_id))
                    )
                    ->modalHeading('Convert to Stock Item')
                    ->modalWidth('4xl')
                    ->form([
                        // ── SECTION 1: IDENTIFICATION ─────────────────────────
                        \Filament\Forms\Components\Section::make('Stock Identification')
                            ->columns(12)
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('barcode')
                                    ->label('Stock Number')
                                    ->default(function () {
                                        $prefix  = DB::table('site_settings')->where('key', 'barcode_prefix')->value('value') ?? 'D';
                                        $maxInDb = \App\Models\ProductItem::withTrashed()
                                            ->where('barcode', 'LIKE', "{$prefix}%")
                                            ->selectRaw("MAX(CAST(SUBSTRING(barcode, " . (strlen($prefix) + 1) . ") AS UNSIGNED)) as max_num")
                                            ->first();
                                        return $prefix . (($maxInDb?->max_num ?? 1000) + 1);
                                    })
                                    ->required()
                                    ->columnSpan(4),

                                \Filament\Forms\Components\TextInput::make('serial_number')
                                    ->label('Serial Number')
                                    ->nullable()
                                    ->columnSpan(4),

                                \Filament\Forms\Components\TextInput::make('rfid_code')
                                    ->label('RFID Code')
                                    ->default(fn () => strtoupper(bin2hex(random_bytes(4))))
                                    ->required()
                                    ->columnSpan(4),
                            ]),

                        // ── SECTION 2: CLASSIFICATION ─────────────────────────
                        \Filament\Forms\Components\Section::make('Classification')
                            ->columns(12)
                            ->schema([
                                \Filament\Forms\Components\Select::make('supplier_id')
                                    ->label('Vendor')
                                    ->options(\App\Models\Supplier::pluck('company_name', 'id'))
                                    ->searchable()
                                    ->columnSpan(4),

                                \Filament\Forms\Components\TextInput::make('supplier_code')
                                    ->label('Vendor Code')
                                    ->nullable()
                                    ->columnSpan(4),

                                \Filament\Forms\Components\Select::make('department')
                                    ->label('Department')
                                    ->options(fn () => collect(\App\Models\InventorySetting::where('key', 'departments')->first()?->value ?? [])
                                        ->filter(fn ($item) => !empty($item['name']))
                                        ->pluck('name', 'name'))
                                    ->searchable()
                                    ->columnSpan(4),

                                \Filament\Forms\Components\Select::make('sub_department')
                                    ->label('Sub-Department')
                                    ->options(fn () => collect(\App\Models\InventorySetting::where('key', 'sub_departments')->first()?->value ?? [])
                                        ->filter()
                                        ->mapWithKeys(fn ($item) => [$item => $item]))
                                    ->searchable()
                                    ->columnSpan(4),

                                \Filament\Forms\Components\Select::make('category')
                                    ->label('Category')
                                    ->options(fn () => collect(\App\Models\InventorySetting::where('key', 'categories')->first()?->value ?? [])
                                        ->filter()
                                        ->mapWithKeys(fn ($item) => [$item => $item]))
                                    ->searchable()
                                    ->columnSpan(4),

                                \Filament\Forms\Components\Select::make('metal_type')
                                    ->label('Metal Karat')
                                    ->options(fn () => collect(\App\Models\InventorySetting::where('key', 'metal_types')->first()?->value ?? [])
                                        ->filter()
                                        ->mapWithKeys(fn ($item) => [$item => $item]))
                                    ->searchable()
                                    ->columnSpan(4),
                            ]),

                        // ── SECTION 3: PHYSICAL DETAILS ───────────────────────
                        \Filament\Forms\Components\Section::make('Physical Details')
                            ->columns(12)
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('size')
                                    ->label('Size')
                                    ->nullable()
                                    ->columnSpan(3),

                                \Filament\Forms\Components\TextInput::make('metal_weight')
                                    ->label('Metal Weight')
                                    ->nullable()
                                    ->columnSpan(3),

                                \Filament\Forms\Components\TextInput::make('diamond_weight')
                                    ->label('Diamond Weight (CTW)')
                                    ->placeholder('1.25 CTW')
                                    ->nullable()
                                    ->columnSpan(6),

                                \Filament\Forms\Components\Textarea::make('custom_description')
                                    ->label('Description')
                                    ->default(fn (SaleItem $record) => $record->custom_description)
                                    ->rows(2)
                                    ->columnSpan(12),
                            ]),

                        // ── SECTION 4: PRICING ────────────────────────────────
                        \Filament\Forms\Components\Section::make('Pricing & Stock')
                            ->columns(12)
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('qty')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->columnSpan(3),

                                \Filament\Forms\Components\TextInput::make('cost_price')
                                    ->label('Cost Price')
                                    ->prefix('$')
                                    ->numeric()
                                    ->default(fn (SaleItem $record) => $record->cost_price ?? 0)
                                    ->required()
                                    ->columnSpan(3),

                                \Filament\Forms\Components\TextInput::make('retail_price')
                                    ->label('Retail Price')
                                    ->prefix('$')
                                    ->numeric()
                                    ->default(fn (SaleItem $record) => $record->sold_price ?? 0)
                                    ->required()
                                    ->columnSpan(3),

                                \Filament\Forms\Components\TextInput::make('web_price')
                                    ->label('Web Price')
                                    ->prefix('$')
                                    ->numeric()
                                    ->default(fn (SaleItem $record) => $record->sold_price ?? 0)
                                    ->columnSpan(3),

                                \Filament\Forms\Components\TextInput::make('discount_percent')
                                    ->label('Discount %')
                                    ->suffix('%')
                                    ->numeric()
                                    ->default(0)
                                    ->columnSpan(3),

                                \Filament\Forms\Components\TextInput::make('component_qty')
                                    ->label('Component Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->columnSpan(3),
                            ]),

                        // ── SECTION 5: JEWELRY SPECS ──────────────────────────
                        \Filament\Forms\Components\Section::make('Jewelry Specifications')
                            ->columns(4)
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('certificate_number')
                                    ->label('Cert #'),
                                \Filament\Forms\Components\Select::make('certificate_agency')
                                    ->label('Agency')
                                    ->options(['GIA' => 'GIA', 'IGI' => 'IGI', 'AGS' => 'AGS', 'HRD' => 'HRD']),
                                \Filament\Forms\Components\Toggle::make('is_lab_grown')
                                    ->label('Lab Grown?')
                                    ->inline(false),
                                \Filament\Forms\Components\TextInput::make('shape'),
                                \Filament\Forms\Components\TextInput::make('color'),
                                \Filament\Forms\Components\TextInput::make('clarity'),
                                \Filament\Forms\Components\TextInput::make('cut'),
                                \Filament\Forms\Components\TextInput::make('polish'),
                                \Filament\Forms\Components\TextInput::make('symmetry'),
                                \Filament\Forms\Components\TextInput::make('fluorescence'),
                                \Filament\Forms\Components\TextInput::make('measurements')
                                    ->columnSpan(2),
                            ]),

                        // ── SECTION 6: STORE ──────────────────────────────────
                        \Filament\Forms\Components\Section::make('Store')
                            ->schema([
                                \Filament\Forms\Components\Select::make('store_id')
                                    ->label('Target Store')
                                    ->options(\App\Models\Store::pluck('name', 'id'))
                                    ->default(fn (SaleItem $record) =>
                                        $record->store_id
                                        ?? $record->sale?->store_id
                                        ?? auth()->user()->store_id
                                        ?? \App\Models\Store::first()?->id
                                    )
                                    ->required()
                                    ->searchable(),
                            ]),
                    ])
                    ->action(function (SaleItem $record, array $data) {
                        // ✅ Resolve store_id with multiple fallbacks
                        $storeId = $data['store_id']
                            ?? $record->store_id
                            ?? $record->sale?->store_id
                            ?? auth()->user()->store_id
                            ?? \App\Models\Store::first()?->id
                            ?? 1;

                        $productItem = \App\Models\ProductItem::create([
                            'barcode'            => $data['barcode'],
                            'serial_number'      => $data['serial_number'] ?? null,
                            'rfid_code'          => $data['rfid_code'],
                            'qty'                => (int) $data['qty'],
                            'component_qty'      => (int) ($data['component_qty'] ?? 1),
                            'cost_price'         => floatval($data['cost_price']),
                            'retail_price'       => floatval($data['retail_price']),
                            'web_price'          => floatval($data['web_price'] ?? $data['retail_price']),
                            'discount_percent'   => floatval($data['discount_percent'] ?? 0),
                            'custom_description' => $data['custom_description'] ?? $record->custom_description,
                            'department'         => $data['department'] ?? null,
                            'sub_department'     => $data['sub_department'] ?? null,
                            'category'           => $data['category'] ?? null,
                            'metal_type'         => $data['metal_type'] ?? null,
                            'metal_weight'       => $data['metal_weight'] ?? null,
                            'diamond_weight'     => $data['diamond_weight'] ?? null,
                            'size'               => $data['size'] ?? null,
                            'supplier_id'        => $data['supplier_id'] ?? null,
                            'supplier_code'      => $data['supplier_code'] ?? null,
                            'store_id'           => $storeId,
                            'status'             => 'in_stock',
                            // Jewelry specs
                            'certificate_number' => $data['certificate_number'] ?? null,
                            'certificate_agency' => $data['certificate_agency'] ?? null,
                            'is_lab_grown'       => $data['is_lab_grown'] ?? false,
                            'shape'              => $data['shape'] ?? null,
                            'color'              => $data['color'] ?? null,
                            'clarity'            => $data['clarity'] ?? null,
                            'cut'                => $data['cut'] ?? null,
                            'polish'             => $data['polish'] ?? null,
                            'symmetry'           => $data['symmetry'] ?? null,
                            'fluorescence'       => $data['fluorescence'] ?? null,
                            'measurements'       => $data['measurements'] ?? null,
                        ]);

                        $record->update([
                            'product_item_id'  => $productItem->id,
                            'is_non_stock'     => false,
                            'stock_no_display' => $data['barcode'],
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Added to Inventory')
                            ->body("Stock #{$data['barcode']} created and linked to invoice #{$record->sale?->invoice_number}.")
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}