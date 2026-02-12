<?php

namespace App\Filament\Resources;
use App\Filament\Resources\ProductItemResource\Pages;
use App\Models\ProductItem;
use App\Services\ZebraPrinterService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Actions\Action as FormAction;

use Filament\Forms\Components\{
    Section,
    Grid,
    Select,
    TextInput,
    CheckboxList,
    Hidden,
    Placeholder,
    Textarea,
    Radio,
    Toggle
};
use Filament\Forms\Get;
use Filament\Tables\Actions\{
    Action,
    EditAction,
    ViewAction,
    ActionGroup
};
use Filament\Notifications\Notification;

class ProductItemResource extends Resource
{
    protected static ?string $model = ProductItem::class;
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Assemble Stock';
    protected static ?string $navigationGroup = 'Inventory';

    /**
     * Converts RFID Hex to a short alphanumeric tag (Option 2)
     */
    public static function generateShortCode(?string $rfid): ?string
    {
        if (!$rfid || strlen($rfid) < 8) return null;
        $hexPart = substr($rfid, -8);
        return strtoupper(base_convert(hexdec($hexPart), 10, 36));
    }

    /**
     * COLLISION-PROOF BARCODE GENERATOR
     * Uses withTrashed() to ensure we skip past soft-deleted barcodes like D1002.
     */
    public static function generatePersistentBarcode(?string $prefix = null): string
{
    // If no prefix passed (like in Edit mode or manual calls), fetch from DB
    if (!$prefix) {
        $prefix = \Illuminate\Support\Facades\DB::table('site_settings')
            ->where('key', 'barcode_prefix')
            ->value('value') ?? 'D';
    }

    $maxInDb = ProductItem::withTrashed()
        ->where('barcode', 'LIKE', "{$prefix}%")
        ->selectRaw("MAX(CAST(SUBSTRING(barcode, " . (strlen($prefix) + 1) . ") AS UNSIGNED)) as max_num")
        ->first();

    $lastNumber = $maxInDb && $maxInDb->max_num ? (int) $maxInDb->max_num : 1000;
    $nextNumber = $lastNumber + 1;

    // Optional: Keep your sequence tracker updated
    \App\Models\InventorySetting::updateOrCreate(
        ['key' => 'barcode_sequence'],
        ['value' => $nextNumber + 1]
    );

    return $prefix . $nextNumber;
}

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Assemble New Stock Item')
                ->columns(12)
                ->schema([

                    /* ───────── STORE ───────── */
                    Select::make('store_id')
                        ->label('Target Store')
                        ->relationship('store', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->default(fn() => auth()->user()->store_id ?? \App\Models\Store::first()?->id)
                        ->disabled(fn() => ! auth()->user()->hasRole('super_admin'))
                        ->columnSpan(4),

                    /* ───────── ASSEMBLY MODE ───────── */
                    Section::make('Assembly Mode')
                        ->schema([
                            Grid::make(12)->schema([
                                Radio::make('creation_mode')
                                    ->label('Stock Type')
                                    ->options(['new' => 'New Stock', 'trade_in' => 'Trade-In'])
                                    ->default(fn($record) => $record?->is_trade_in ? 'trade_in' : 'new')
                                    ->required()
                                    ->inline()->live()->dehydrated(false)
                                    ->columnSpan(4),

                                Select::make('original_trade_in_no')
                                    ->label('Tracking #')
                                    ->placeholder('Search TRD-...')
                                    ->options(function () {
                                        $used = \App\Models\ProductItem::whereNotNull('original_trade_in_no')->pluck('original_trade_in_no')->toArray();
                                        return \App\Models\Sale::where('has_trade_in', 1)->whereNotIn('trade_in_receipt_no', $used)->latest()->pluck('trade_in_receipt_no', 'trade_in_receipt_no');
                                    })
                                    ->visible(fn(Get $get) => $get('creation_mode') === 'trade_in')
                                    ->required(fn(Get $get) => $get('creation_mode') === 'trade_in')
                                    ->searchable()->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $tradeValue = \App\Models\Sale::where('trade_in_receipt_no', $state)->value('trade_in_value');
                                            $set('cost_price', $tradeValue);
                                            $set('supplier_id', null);
                                            $set('is_trade_in', true);
                                        } else { $set('is_trade_in', false); }
                                    })->columnSpan(8),
                            ]),

                            Placeholder::make('trade_in_check')
                                ->content(function (Get $get) {
                                    if (! $get('original_trade_in_no')) return 'Select a tracking number to verify.';
                                    $sale = \App\Models\Sale::where('trade_in_receipt_no', $get('original_trade_in_no'))->first();
                                    return new \Illuminate\Support\HtmlString("<div class='p-2 bg-blue-50 border border-blue-200 rounded text-blue-700 text-sm font-medium'>✓ Verified Trade-In Value: <strong>$" . number_format($sale?->trade_in_value ?? 0, 2) . "</strong></div>");
                                })
                                ->visible(fn(Get $get) => $get('creation_mode') === 'trade_in' && $get('original_trade_in_no'))
                                ->columnSpanFull(),
                        ]),

                    /* ───────── SUPPLIER / MEMO ───────── */
                    Grid::make(12)->schema([
                        Select::make('supplier_id')
                            ->relationship('supplier', 'company_name')
                            ->label('Vendor')->searchable()->preload()->required()->live()
                            ->afterStateUpdated(function (Get $get, Forms\Set $set, $state) {
                                if ($get('is_memo')) $set('memo_vendor_id', $state);
                                if ($state) {
                                    $latestCode = ProductItem::where('supplier_id', $state)->latest()->value('supplier_code');
                                    $set('supplier_code', $latestCode);
                                }
                            })->columnSpan(4),

                        Toggle::make('is_memo')->label('Is this Memo?')->inline(false)->live()
                            ->afterStateUpdated(fn(Get $get, Forms\Set $set, $state) => $set('memo_vendor_id', $state ? $get('supplier_id') : null))
                            ->columnSpan(2),

                        Hidden::make('memo_vendor_id'),

                        Placeholder::make('memo_vender_label')
                            ->label('Current Inventory Type')
                            ->visible(fn (Get $get) => filled($get('supplier_id'))) 
                            ->content(function (Get $get) {
                                $supplier = \App\Models\Supplier::find($get('supplier_id'));
                                return $get('is_memo') ? "Consignment: Memo + {$supplier?->company_name}" : "Owned: {$supplier?->company_name}";
                            })->columnSpan(3),

                        Select::make('memo_status')->label('Memo Action')
                            ->options(['on_memo' => 'On Memo', 'returned' => 'Returned to Vendor'])
                            ->default('on_memo')->visible(fn (Get $get) => $get('is_memo'))->required(fn (Get $get) => $get('is_memo'))->columnSpan(3),
                    ]),

                    CheckboxList::make('print_options')
                        ->options(['print_tag' => 'Print Barcode Tag', 'print_rfid' => 'Print RFID Tag', 'encode_rfid' => 'Encode RFID Data'])
                        ->columns(3)->columnSpan(12),

                    Toggle::make('enable_rfid_tracking')->label('Enable RFID Tracking')->default(true)->live()->columnSpan(6),

                    TextInput::make('rfid_code')
                        ->label('RFID EPC Code')
                        // 🚀 Generates a unique 8-character hex code for the database
                        ->default(fn () => strtoupper(bin2hex(random_bytes(4)))) 
                        ->required()
                        ->dehydrated() // Ensures it saves even if the field is not manually edited
                        ->helperText(fn (Get $get) => $get('rfid_code') ? "Short ID: " . self::generateShortCode($get('rfid_code')) : null)
                        ->columnSpan(6),

                    TextInput::make('supplier_code')->label('Vendor Code')->columnSpan(3),

                    Select::make('department')->label('Department')
                    ->hintAction(
                            FormAction::make('Help')
                                ->icon('heroicon-o-information-circle')
                                ->tooltip('Go to Inventory Settings → Categories to configure Department')
                        )
                        ->options(function() {
                            $depts = \App\Models\InventorySetting::where('key', 'departments')->first()?->value ?? [];
                            return collect($depts)->filter(fn($item) => !empty($item['name']))->pluck('name', 'name');
                        })
                        ->required()->searchable()->live()->afterStateUpdated(fn (Get $get, Forms\Set $set) => self::runSmartPricing($get, $set))
                        ->columnSpan(3),

                    // **2. Fix Sub-Department Select**
                    Select::make('sub_department')
                        ->label('Sub-Department')
                        ->options(function() {
                            $data = \App\Models\InventorySetting::where('key', 'sub_departments')->first()?->value ?? [];
                            // Ensure we use the string value as BOTH the key and the label
                            return collect($data)->filter()->mapWithKeys(fn($item) => [$item => $item])->toArray();
                        })
                        ->searchable()
                        ->dehydrated()
                        ->columnSpan(3),

                    // **1. Fix Category Select**
                    Select::make('category')
                        ->label('Category')
                        ->options(function() {
                            $data = \App\Models\InventorySetting::where('key', 'categories')->first()?->value ?? [];
                            // Ensure we use the string value as BOTH the key and the label
                            return collect($data)->filter()->mapWithKeys(fn($item) => [$item => $item])->toArray();
                        })
                        ->searchable()
                        ->dehydrated() 
                        ->columnSpan(2),

                    Select::make('metal_type')->label('Metal Karat')
                     ->hintAction(
                            FormAction::make('Help')
                                ->icon('heroicon-o-information-circle')
                                ->tooltip('Go to Inventory Settings → Categories to configure Metal Karat')
                        )
                        ->options(fn() => collect(\App\Models\InventorySetting::where('key', 'metal_types')->first()?->value ?? [])->filter()->toArray())
                        ->searchable()->columnSpan(2),

                    TextInput::make('size')->columnSpan(2),
                    TextInput::make('metal_weight')->label('Metal Weight')->columnSpan(2),
                    TextInput::make('diamond_weight')->label('Diamond Weight (CTW)')->placeholder('1.25 CTW')->columnSpan(6),

                    /* ───────── PRICING ───────── */
                    TextInput::make('qty')->label('Qty')->numeric()->required()->default(1)->columnSpan(2),
                    TextInput::make('cost_price')->label('Cost price')->prefix('$')->numeric()->required()->live(onBlur: true) 
                        ->afterStateUpdated(fn (Get $get, Forms\Set $set) => self::runSmartPricing($get, $set))->columnSpan(2),
                    TextInput::make('retail_price')->label('Retail price')->prefix('$')->numeric()->required()->live()->columnSpan(3),
                    TextInput::make('web_price')->label('Web price')->prefix('$')->numeric()->live()->columnSpan(3),
                    TextInput::make('discount_percent')->label('Discount percent')->suffix('%')->default(0)->numeric()->columnSpan(2),

                    Textarea::make('custom_description')->rows(3)->columnSpan(12),
                    TextInput::make('barcode')->label('Stock Number')->disabled()->dehydrated()->columnSpan(4),
                    TextInput::make('serial_number')->columnSpan(4),
                    TextInput::make('component_qty')->numeric()->default(1)->columnSpan(4),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('barcode')->label('STOCK NO.')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('short_id')->label('TAG ID')->getStateUsing(fn($record) => self::generateShortCode($record->rfid_code))->fontFamily('mono')->color('info'),
                Tables\Columns\IconColumn::make('is_trade_in')->label('SOURCE')->boolean()->trueIcon('heroicon-o-user-group')->falseIcon('heroicon-o-building-office'),
                Tables\Columns\TextColumn::make('custom_description')->label('DESCRIPTION')->limit(40)->wrap(),
                Tables\Columns\TextColumn::make('retail_price')->label('PRICE')->money('USD')->color('success'),
                
                /* RFID COLUMNS RESTORED */
                Tables\Columns\TextColumn::make('rfid_code')->label('RFID NUMBER')->fontFamily('mono')->limit(12)->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('rfid_status')->label('RFID')->badge()->getStateUsing(fn($record) => filled($record->rfid_code) ? 'Encoded' : 'Missing')
                    ->color(fn($state) => $state === 'Encoded' ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('status')->badge()->color(fn($state) => match($state){'in_stock'=>'success','sold'=>'danger',default=>'gray'}),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    ViewAction::make(),
                    Tables\Actions\DeleteAction::make()
            ->label('Archive'),
                    Action::make('print_barcode')->icon('heroicon-o-printer')->action(function ($record, ZebraPrinterService $service, $livewire) {
                        $livewire->dispatch('zebra-print', zpl: $service->getZplCode($record, false));
                    }),
                    Action::make('print_rfid')->icon('heroicon-o-identification')->color('warning')->action(function ($record, ZebraPrinterService $service, $livewire) {
                        if (!$record->rfid_code) { Notification::make()->title('No RFID Data')->danger()->send(); return; }
                        $livewire->dispatch('zebra-print', zpl: $service->getZplCode($record, true));
                    }),
                ]),
            ]);
    }

    public static function runSmartPricing(Get $get, Forms\Set $set): void
    {
        $cost = floatval($get('cost_price')); $dept = $get('department');
        if ($cost <= 0 || !$dept) return;
        $data = collect(\App\Models\InventorySetting::where('key', 'departments')->first()?->value ?? [])->firstWhere('name', $dept);
        if ($data && isset($data['multiplier'])) {
            $calc = $cost * floatval($data['multiplier']);
            $set('retail_price', number_format($calc, 2, '.', ''));
            $set('web_price', number_format($calc, 2, '.', ''));
            if (blank($get('custom_description'))) $set('custom_description', "{$dept} - " . ($get('metal_type') ?? 'Jewelry'));
        }
    }
public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
{
    return parent::getEloquentQuery()
        ->withoutTrashed();
}
    public static function getPages(): array { return ['index' => Pages\ListProductItems::route('/'), 'create' => Pages\CreateProductItem::route('/create'), 'edit' => Pages\EditProductItem::route('/{record}/edit')]; }
}