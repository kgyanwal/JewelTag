<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductItemResource\Pages;
use App\Models\ProductItem;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ZebraPrinterService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Set;
use Illuminate\Support\Str;
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
use Illuminate\Support\Facades\DB;

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
    public static function generatePersistentBarcode(?string $prefix = 'D'): string
    {
        // Ensure prefix is uppercase and clean
        $prefix = strtoupper($prefix ?: 'D');

        // Find the highest number in the DB that matches THIS specific prefix
        $maxInDb = ProductItem::withTrashed()
            ->where('barcode', 'LIKE', "{$prefix}%")
            // This regex/substring logic extracts the numeric part after the prefix
            ->selectRaw("MAX(CAST(SUBSTRING(barcode, " . (strlen($prefix) + 1) . ") AS UNSIGNED)) as max_num")
            ->first();

        // Start at 1000 if no items exist for this category yet
        $lastNumber = $maxInDb && $maxInDb->max_num ? (int) $maxInDb->max_num : 1000;
        $nextNumber = $lastNumber + 1;

        return $prefix . $nextNumber;
    }
    public static function getPrefixForSubDepartment(?string $subDept): string
    {
        // 1. Get the Default Prefix (Fallback)
        $defaultPrefix = \Illuminate\Support\Facades\DB::table('site_settings')
            ->where('key', 'barcode_prefix')
            ->value('value') ?? 'D';

        if (!$subDept) return $defaultPrefix;

        // 2. Check Settings Database (Client's custom mapping)
        $prefixesJson = \Illuminate\Support\Facades\DB::table('site_settings')
            ->where('key', 'sub_department_prefixes')
            ->value('value');

        if ($prefixesJson) {
            $prefixes = json_decode($prefixesJson, true);
            foreach ($prefixes as $mapping) {
                $settingDept = trim($mapping['sub_department'] ?? '');
                // Fuzzy match (e.g. "Rings" contains "Ring")
                if ($settingDept && stripos($subDept, $settingDept) !== false) {
                    return strtoupper(trim($mapping['prefix']));
                }
            }
        }

        // 3. SMART FALLBACK (Works instantly even if Settings are empty)
        $lowerSub = strtolower(trim($subDept));
        if (str_contains($lowerSub, 'ring')) return 'R';
        if (str_contains($lowerSub, 'necklace')) return 'N';
        if (str_contains($lowerSub, 'pendant')) return 'P';
        if (str_contains($lowerSub, 'bracelet')) return 'B';
        if (str_contains($lowerSub, 'earring')) return 'E';
        if (str_contains($lowerSub, 'watch')) return 'W';
        if (str_contains($lowerSub, 'chain')) return 'C';
        if (str_contains($lowerSub, 'band')) return 'B';

        // 4. Ultimate Fallback
        return $defaultPrefix;
    }
    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()
    ->compact()
    ->schema([
        /* ───────── TOP RADIO TOGGLE ───────── */
        Radio::make('entry_type')
            ->label('')
            ->options([
                'new' => 'Assemble New Stock Item',
                'copy' => 'Copy Existing Item',
            ])
            ->default('new')
            ->inline()
            ->live()
            ->extraAttributes(['class' => 'font-bold text-lg'])
            ->afterStateUpdated(fn (Set $set) => $set('template_item_id', null)),

        /* ───────── ADVANCED FILTER BAR (Visible only when 'copy' is selected) ───────── */
        Grid::make(12)
            ->visible(fn (Get $get) => $get('entry_type') === 'copy')
            ->schema([
                
                // 1. Filter by Vendor
                Select::make('filter_vendor')
                    ->label('Filter Vendor')
                    ->options(\App\Models\Supplier::pluck('company_name', 'id'))
                    ->searchable()
                    ->placeholder('All Vendors')
                    ->live()
                    ->columnSpan(3),

                // 2. Filter by Department
                Select::make('filter_dept')
                    ->label('Filter Dept')
                    ->options(fn() => collect(\App\Models\InventorySetting::where('key', 'departments')->first()?->value ?? [])->pluck('name', 'name'))
                    ->placeholder('All Depts')
                    ->live()
                    ->columnSpan(3),

                // 3. Filter by Category
                Select::make('filter_category')
                    ->label('Filter Category')
                    ->options(fn() => collect(\App\Models\InventorySetting::where('key', 'categories')->first()?->value ?? [])->filter()->mapWithKeys(fn($i)=>[$i=>$i]))
                    ->placeholder('All Categories')
                    ->live()
                    ->columnSpan(3),

                /* ───────── DYNAMIC STOCK SELECTOR (Filtered by above choices) ───────── */
                Select::make('template_item_id')
                    ->label('Select Stock:')
                    ->placeholder('SEARCH FILTERED ITEMS...')
                    // 🚀 THE LOGIC: This dropdown now changes based on the filters above
                    ->options(function (Get $get) {
                        $query = ProductItem::query();

                        if ($get('filter_vendor')) $query->where('supplier_id', $get('filter_vendor'));
                        if ($get('filter_dept')) $query->where('department', $get('filter_dept'));
                        if ($get('filter_category')) $query->where('category', $get('filter_category'));

                        return $query->latest()
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(fn($i) => [
                                $i->id => "{$i->barcode} - " . Str::limit($i->custom_description, 30)
                            ]);
                    })
                    ->searchable()
                    ->live()
                    ->required(fn(Get $get) => $get('entry_type') === 'copy')
                    ->columnSpan(3)
                    ->afterStateUpdated(function ($state, Set $set) {
                        if (!$state) return;
                        $item = ProductItem::find($state);
                        if (!$item) return;

                        // 📋 CLONE ALL FIELDS LIVE
                        $set('supplier_id', $item->supplier_id);
                        $set('supplier_code', $item->supplier_code);
                        $set('is_memo', $item->is_memo);
                        $set('memo_status', $item->memo_status);
                        $set('department', $item->department);
                        $set('sub_department', $item->sub_department);
                        $set('category', $item->category);
                        $set('metal_type', $item->metal_type);
                        $set('metal_weight', $item->metal_weight);
                        $set('diamond_weight', $item->diamond_weight);
                        $set('size', $item->size);
                        $set('custom_description', $item->custom_description);
                        $set('cost_price', $item->cost_price);
                        $set('retail_price', $item->retail_price);
                        $set('web_price', $item->web_price);
                        
                        // CLONE JEWELRY SPECS (Hidden Fields)
                        $set('certificate_number', $item->certificate_number);
                        $set('certificate_agency', $item->certificate_agency);
                        $set('shape', $item->shape);
                        $set('color', $item->color);
                        $set('clarity', $item->clarity);
                        $set('cut', $item->cut);
                        $set('polish', $item->polish);
                        $set('symmetry', $item->symmetry);
                        $set('fluorescence', $item->fluorescence);
                        $set('measurements', $item->measurements);
                        $set('is_lab_grown', $item->is_lab_grown);

                        Notification::make()->title('Template Applied')->success()->send();
                    }),
            ]),
    ]),
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
                                    ->required(fn($livewire) => $livewire instanceof \App\Filament\Resources\ProductItemResource\Pages\CreateProductItem)

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
                                    ->required(function (Get $get, $livewire) {
                                        return $livewire instanceof \App\Filament\Resources\ProductItemResource\Pages\CreateProductItem
                                            && $get('creation_mode') === 'trade_in';
                                    })

                                    ->searchable()->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $tradeValue = \App\Models\Sale::where('trade_in_receipt_no', $state)->value('trade_in_value');
                                            $set('cost_price', $tradeValue);
                                            $set('supplier_id', null);
                                            $set('is_trade_in', true);
                                        } else {
                                            $set('is_trade_in', false);
                                        }
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
                                    // We fetch the code directly from the Supplier model (The master record)
                                    // instead of searching through previous product items.
                                    $vendor = \App\Models\Supplier::find($state);

                                    if ($vendor) {
                                        // Assuming your Supplier model has a 'code' or 'supplier_code' column
                                        $set('supplier_code', $vendor->code ?? $vendor->supplier_code);
                                    }
                                }
                            })->columnSpan(4),

                        Toggle::make('is_memo')->label('Is this Memo?')->inline(false)->live()
                            ->afterStateUpdated(fn(Get $get, Forms\Set $set, $state) => $set('memo_vendor_id', $state ? $get('supplier_id') : null))
                            ->columnSpan(2),

                        Hidden::make('memo_vendor_id'),

                        Placeholder::make('memo_vender_label')
                            ->label('Current Inventory Type')
                            ->visible(fn(Get $get) => filled($get('supplier_id')))
                            ->content(function (Get $get) {
                                $supplier = \App\Models\Supplier::find($get('supplier_id'));
                                return $get('is_memo') ? "Consignment: Memo + {$supplier?->company_name}" : "Owned: {$supplier?->company_name}";
                            })->columnSpan(3),

                        Select::make('memo_status')->label('Memo Action')
                            ->options(['on_memo' => 'On Memo', 'returned' => 'Returned to Vendor'])
                            ->default('on_memo')->visible(fn(Get $get) => $get('is_memo'))->required(fn(Get $get) => $get('is_memo'))->columnSpan(3),
                    ]),

                    CheckboxList::make('print_options')
                        ->options(['print_tag' => 'Print Barcode Tag', 'print_rfid' => 'Print RFID Tag', 'encode_rfid' => 'Encode RFID Data'])
                        ->columns(3)->columnSpan(12),

                    Toggle::make('enable_rfid_tracking')->label('Enable RFID Tracking')->default(true)->live()->columnSpan(6),

                    TextInput::make('rfid_code')
                        ->label('RFID EPC Code')
                        // 🚀 Generates a unique 8-character hex code for the database
                        ->default(fn() => strtoupper(bin2hex(random_bytes(4))))
                        ->required()
                        ->dehydrated() // Ensures it saves even if the field is not manually edited
                        ->helperText(fn(Get $get) => $get('rfid_code') ? "Short ID: " . self::generateShortCode($get('rfid_code')) : null)
                        ->columnSpan(6),

                    TextInput::make('supplier_code')->label('Vendor Code')->columnSpan(3),

                    Select::make('department')
                        ->label('Department')
                        ->hintAction(
                            FormAction::make('Help')
                                ->icon('heroicon-o-information-circle')
                                ->tooltip('Go to Inventory Settings → Categories to configure Department')
                        )
                        ->options(function () {
                            $depts = \App\Models\InventorySetting::where('key', 'departments')->first()?->value ?? [];
                            return collect($depts)->filter(fn($item) => !empty($item['name']))->pluck('name', 'name');
                        })
                        ->required()
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(fn(Get $get, Forms\Set $set) => self::runSmartPricing($get, $set))
                        ->columnSpan(3)
                        // 🚀 NEW: Quick Add Department
                        ->createOptionForm([
                            TextInput::make('name')->label('New Department Name')->required(),
                            TextInput::make('multiplier')->label('Pricing Multiplier')->numeric()->default(2.5),
                        ])
                        ->createOptionUsing(function (array $data) {
                            $record = \App\Models\InventorySetting::firstOrCreate(['key' => 'departments']);
                            $currentValue = $record->value ?? [];
                            $currentValue[] = ['name' => $data['name'], 'multiplier' => $data['multiplier']];
                            $record->update(['value' => $currentValue]);
                            return $data['name'];
                        }),

                    Select::make('sub_department')
                        ->label('Sub-Department')
                        ->options(function () {
                            $data = \App\Models\InventorySetting::where('key', 'sub_departments')->first()?->value ?? [];
                            return collect($data)->filter()->mapWithKeys(fn($item) => [$item => $item])->toArray();
                        })
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                            $prefix = self::getPrefixForSubDepartment($state);
                            $newBarcode = self::generatePersistentBarcode($prefix);
                            $set('barcode', $newBarcode);
                        })
                        ->columnSpan(3)
                        // 🚀 NEW: Quick Add Sub-Department
                        ->createOptionForm([
                            TextInput::make('name')->label('New Sub-Department')->required(),
                            TextInput::make('prefix')->label('Barcode Prefix')->required()->maxLength(3),
                        ])
                        ->createOptionUsing(function (array $data) {
                            $subDeptRecord = \App\Models\InventorySetting::firstOrCreate(['key' => 'sub_departments']);
                            $subs = $subDeptRecord->value ?? [];
                            $subs[] = $data['name'];
                            $subDeptRecord->update(['value' => $subs]);

                            $siteSettings = \Illuminate\Support\Facades\DB::table('site_settings')->where('key', 'sub_department_prefixes')->first();
                            $prefixes = $siteSettings ? json_decode($siteSettings->value, true) : [];
                            $prefixes[] = ['sub_department' => $data['name'], 'prefix' => strtoupper($data['prefix'])];
                            \Illuminate\Support\Facades\DB::table('site_settings')->updateOrInsert(
                                ['key' => 'sub_department_prefixes'],
                                ['value' => json_encode($prefixes), 'updated_at' => now()]
                            );
                            return $data['name'];
                        }),

                    Select::make('category')
                        ->label('Category')
                        ->options(function () {
                            $data = \App\Models\InventorySetting::where('key', 'categories')->first()?->value ?? [];
                            return collect($data)->filter()->mapWithKeys(fn($item) => [$item => $item])->toArray();
                        })
                        ->searchable()
                        ->dehydrated()
                        ->columnSpan(2)
                        // 🚀 NEW: Quick Add Category
                        ->createOptionForm([
                            TextInput::make('name')->label('New Category Name')->required(),
                        ])
                        ->createOptionUsing(function (array $data) {
                            $record = \App\Models\InventorySetting::firstOrCreate(['key' => 'categories']);
                            $current = $record->value ?? [];
                            $current[] = $data['name'];
                            $record->update(['value' => $current]);
                            return $data['name'];
                        }),

                    Select::make('metal_type')
                        ->label('Metal Karat')
                        ->hintAction(
                            FormAction::make('Help')
                                ->icon('heroicon-o-information-circle')
                                ->tooltip('Go to Inventory Settings → Categories to configure Metal Karat')
                        )
                        ->options(fn() => collect(\App\Models\InventorySetting::where('key', 'metal_types')->first()?->value ?? [])->filter()->toArray())
                        ->searchable()
                        ->columnSpan(2)
                        // 🚀 NEW: Quick Add Metal Karat
                        ->createOptionForm([
                            TextInput::make('name')->label('New Metal Karat (e.g. 14k White)')->required(),
                        ])
                        ->createOptionUsing(function (array $data) {
                            $record = \App\Models\InventorySetting::firstOrCreate(['key' => 'metal_types']);
                            $current = $record->value ?? [];
                            $current[] = $data['name'];
                            $record->update(['value' => $current]);
                            return $data['name'];
                        }),
                    TextInput::make('size')->columnSpan(2),
                    TextInput::make('metal_weight')->label('Metal Weight')->columnSpan(2),
                    TextInput::make('diamond_weight')->label('Diamond Weight (CTW)')->placeholder('1.25 CTW')->columnSpan(6),

                    /* ───────── PRICING ───────── */
                    TextInput::make('qty')->label('Qty')->numeric()->required()->default(1)->columnSpan(2),
                    TextInput::make('cost_price')->label('Cost price')->prefix('$')->numeric()->required()->live(onBlur: true)
                        ->afterStateUpdated(fn(Get $get, Forms\Set $set) => self::runSmartPricing($get, $set))->columnSpan(2),
                    TextInput::make('retail_price')->label('Retail price')->prefix('$')->numeric()->required()->live()->columnSpan(3),
                    TextInput::make('web_price')->label('Web price')->prefix('$')->numeric()->live()->columnSpan(3),
                    TextInput::make('discount_percent')->label('Discount percent')->suffix('%')->default(0)->numeric()->columnSpan(2),

                  Textarea::make('custom_description')
                    ->label('Description')
                    ->rows(3)
                    ->columnSpan(12)
                    // 🚀 ON-SWIM STYLE POPUP
                    ->hintAction(
                        Forms\Components\Actions\Action::make('moreDetails')
                            ->label('Add Jewelry Specs')
                            ->icon('heroicon-o-sparkles')
                            ->color('info')
                            ->modalHeading('Detailed Specifications')
                            ->modalWidth('4xl')
                            ->fillForm(fn (Get $get) => [
                                'certificate_number' => $get('certificate_number'),
                                'certificate_agency' => $get('certificate_agency'),
                                'shape' => $get('shape'),
                                'color' => $get('color'),
                                'clarity' => $get('clarity'),
                                'cut' => $get('cut'),
                                'polish' => $get('polish'),
                                'symmetry' => $get('symmetry'),
                                'fluorescence' => $get('fluorescence'),
                                'measurements' => $get('measurements'),
                                'is_lab_grown' => $get('is_lab_grown'),
                            ])
                            ->form([
                                Grid::make(3)->schema([
                                    TextInput::make('certificate_number')->label('Cert #'),
                                    Select::make('certificate_agency')
                                        ->options(['GIA' => 'GIA', 'IGI' => 'IGI', 'AGS' => 'AGS', 'HRD' => 'HRD']),
                                    Toggle::make('is_lab_grown')->label('Lab Grown?')->inline(false),
                                ]),
                                Section::make('Stone Details')->schema([
                                    Grid::make(4)->schema([
                                        TextInput::make('shape'),
                                        TextInput::make('color'),
                                        TextInput::make('clarity'),
                                        TextInput::make('cut'),
                                        TextInput::make('polish'),
                                        TextInput::make('symmetry'),
                                        TextInput::make('fluorescence'),
                                        TextInput::make('measurements'),
                                    ]),
                                ]),
                            ])
                            ->action(function (array $data, Set $set) {
                                foreach ($data as $key => $value) {
                                    $set($key, $value);
                                }
                            })
                    ),

                // 🚀 HIDDEN STORAGE (Connects the popup to your database)
               Hidden::make('certificate_number')->default(''),
Hidden::make('certificate_agency')->default(''),
Hidden::make('is_lab_grown')->default(false),

Hidden::make('shape')->default(''),
Hidden::make('color')->default(''),
Hidden::make('clarity')->default(''),
Hidden::make('cut')->default(''),
Hidden::make('polish')->default(''),
Hidden::make('symmetry')->default(''),
Hidden::make('fluorescence')->default(''),
Hidden::make('measurements')->default(''),

Hidden::make('markup')->default(0),
Hidden::make('web_item')->default(false),
Hidden::make('markup')->default(0.00),
Hidden::make('web_item')->default(false),
                    
                    TextInput::make('barcode')
                        ->label('Stock Number')
                        // Sets an initial value on page load
                        ->default(fn() => self::generatePersistentBarcode(self::getPrefixForSubDepartment(null)))
                        ->readOnly() // 🚨 CRITICAL: Must be readOnly(), NOT disabled()
                        ->dehydrated()
                        ->columnSpan(4),
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

                Tables\Columns\TextColumn::make('status')->badge()->color(fn($state) => match ($state) {
                    'in_stock' => 'success',
                    'sold' => 'danger',
                    default => 'gray'
                }),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('transferStock')
                        ->label('Transfer Stock')
                        ->icon('heroicon-o-truck')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->form([
                            Select::make('target_tenant_id')
                                ->label('Destination Store')
                                ->options(Tenant::where('id', '!=', tenant('id'))->pluck('id', 'id'))
                                ->required()
                                ->searchable(),
                        ])
                        ->action(function (ProductItem $record, array $data) {
                            $sourceTenantId = tenant('id');
                            $targetTenantId = $data['target_tenant_id'];

                            // 1. Capture source data before switching
                            $itemData = $record->toArray();
                            $barcode = $record->barcode;
                            $sourceVendorName = $record->supplier?->company_name;
                            $sourceVendorCode = $record->supplier_code;

                            try {
                                DB::transaction(function () use ($itemData, $targetTenantId, $sourceVendorName, $sourceVendorCode, $record, $sourceTenantId, $barcode) {
                                    
                                    // 2. Switch context to Destination
                                    $targetTenant = Tenant::find($targetTenantId);
                                    tenancy()->initialize($targetTenant);

                                    // 3. Find Vendor in Destination
                                    $targetSupplier = Supplier::where('company_name', $sourceVendorName)
                                        ->orWhere('supplier_code', $sourceVendorCode)
                                        ->first();

                                    if (!$targetSupplier) {
                                        throw new \Exception("Vendor '{$sourceVendorName}' not found in destination store.");
                                    }

                                    // 4. Prepare data
                                    unset($itemData['id'], $itemData['created_at'], $itemData['updated_at']);
                                    $itemData['supplier_id'] = $targetSupplier->id; 
                                    $itemData['status'] = 'in_stock';

                                    // 5. Create item
                                    ProductItem::create($itemData);

                                    // 🚀 6. Notify Destination Store (All users with admin roles)
                                    $recipients = User::role(['Superadmin', 'Administration', 'Manager'])->get();
                                    Notification::make()
                                        ->title('New Stock Transferred')
                                        ->body("Stock #{$barcode} has arrived from Store {$sourceTenantId}.")
                                        ->icon('heroicon-o-building-storefront')
                                        ->success()
                                        ->sendToDatabase($recipients);

                                    // 7. Switch back and delete from source
                                    tenancy()->initialize(Tenant::find($sourceTenantId));
                                    $record->delete();
                                }, 5); // 5 attempts for deadlock safety

                                Notification::make()->title('Transfer Successful')->body("Item {$barcode} moved to {$targetTenantId}")->success()->send();

                            } catch (\Exception $e) {
                                tenancy()->initialize(Tenant::find($sourceTenantId));
                                Notification::make()->title('Transfer Error')->body($e->getMessage())->danger()->persistent()->send();
                            }
                        }),
                    EditAction::make(),
                    ViewAction::make(),
                    Action::make('addToWishlist')
                        ->label('Add to Wishlist')
                        ->icon('heroicon-o-heart')
                        ->color('danger')
                        ->form([
                            Select::make('customer_id')
                                ->label('Customer')
                                // ❌ REMOVE THIS: ->relationship('customer', 'name') 

                                // ✅ USE THIS INSTEAD: Manually fetch options
                                ->options(function () {
                                    return \App\Models\Customer::all()->mapWithKeys(function ($customer) {
                                        return [$customer->id => "{$customer->name} {$customer->last_name} ({$customer->phone})"];
                                    });
                                })
                                ->searchable() // Allows typing to find the name
                                ->preload()
                                ->required()

                                // This part is fine (creating a new customer on the fly)
                                ->createOptionForm([
                                    TextInput::make('name')->required(),
                                    TextInput::make('last_name'),
                                    TextInput::make('phone')->tel()->required(),
                                ])
                                ->createOptionUsing(function (array $data) {
                                    return \App\Models\Customer::create($data)->id;
                                }),

                            Textarea::make('notes')
                                ->label('Notes')
                                ->placeholder('e.g. Loves this for anniversary, budget is tight'),

                            Forms\Components\DatePicker::make('follow_up_date')
                                ->label('Follow Up Date'),
                        ])
                        ->action(function (ProductItem $record, array $data) {
                            \App\Models\Wishlist::create([
                                'product_item_id' => $record->id,
                                'customer_id' => $data['customer_id'],
                                'sales_person_id' => auth()->id(),
                                'notes' => $data['notes'],
                                'follow_up_date' => $data['follow_up_date'],
                                'status' => 'active',
                            ]);

                            Notification::make()->title('Added to Wishlist')->success()->send();
                        }),
                    Tables\Actions\DeleteAction::make()
                        ->label('Archive'),
                    Action::make('print_barcode')->icon('heroicon-o-printer')->action(function ($record, ZebraPrinterService $service, $livewire) {
                        $livewire->dispatch('zebra-print', zpl: $service->getZplCode($record, false));
                    }),
                    Action::make('print_rfid')->icon('heroicon-o-identification')->color('warning')->action(function ($record, ZebraPrinterService $service, $livewire) {
                        if (!$record->rfid_code) {
                            Notification::make()->title('No RFID Data')->danger()->send();
                            return;
                        }
                        $livewire->dispatch('zebra-print', zpl: $service->getZplCode($record, true));
                    }),
                    
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function runSmartPricing(Get $get, Forms\Set $set): void
    {
        $cost = floatval($get('cost_price'));
        $dept = $get('department');
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
    public static function getPages(): array
    {
        return ['index' => Pages\ListProductItems::route('/'), 'create' => Pages\CreateProductItem::route('/create'), 'edit' => Pages\EditProductItem::route('/{record}/edit')];
    }
}
