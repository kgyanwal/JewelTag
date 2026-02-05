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
use Illuminate\Support\Facades\Cache;

class ProductItemResource extends Resource
{
    protected static ?string $model = ProductItem::class;
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Assemble Stock';
    protected static ?string $navigationGroup = 'Inventory';

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
                                    ->options([
                                        'new' => 'New Stock',
                                        'trade_in' => 'Trade-In',
                                    ])
                                    ->default(fn($record) => $record?->is_trade_in ? 'trade_in' : 'new')
                                    ->required()
                                    ->inline()
                                    ->live()
                                    ->dehydrated(false)
                                    ->columnSpan(4),

                                Select::make('original_trade_in_no')
                                    ->label('Tracking #')
                                    ->placeholder('Search TRD-...')
                                    ->options(function () {
                                        $used = \App\Models\ProductItem::whereNotNull('original_trade_in_no')
                                            ->pluck('original_trade_in_no')
                                            ->toArray();

                                        return \App\Models\Sale::where('has_trade_in', 1)
                                            ->whereNotIn('trade_in_receipt_no', $used)
                                            ->latest()
                                            ->pluck('trade_in_receipt_no', 'trade_in_receipt_no');
                                    })
                                    ->visible(fn(Get $get) => $get('creation_mode') === 'trade_in')
                                    ->required(fn(Get $get) => $get('creation_mode') === 'trade_in')
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $tradeValue = \App\Models\Sale::where('trade_in_receipt_no', $state)
                                                ->value('trade_in_value');

                                            $set('cost_price', $tradeValue);
                                            $set('supplier_id', null);
                                            $set('is_trade_in', true);
                                        } else {
                                            $set('is_trade_in', false);
                                        }
                                    })
                                    ->columnSpan(8),
                            ]),

                            Placeholder::make('trade_in_check')
                                ->content(function (Get $get) {
                                    if (! $get('original_trade_in_no')) {
                                        return 'Select a tracking number to verify the item.';
                                    }

                                    $sale = \App\Models\Sale::where(
                                        'trade_in_receipt_no',
                                        $get('original_trade_in_no')
                                    )->first();

                                    return new \Illuminate\Support\HtmlString(
                                        "<div class='p-2 bg-blue-50 border border-blue-200 rounded text-blue-700 text-sm font-medium'>
                                            ✓ Verified Trade-In Value:
                                            <strong>$" . number_format($sale->trade_in_value ?? 0, 2) . "</strong>
                                        </div>"
                                    );
                                })
                                ->visible(
                                    fn(Get $get) =>
                                    $get('creation_mode') === 'trade_in'
                                        && $get('original_trade_in_no')
                                )
                                ->columnSpanFull(),
                        ]),

                    /* ───────── SUPPLIER ───────── */
                    /* ───────── SUPPLIER / MEMO ───────── */
/* ───────── SUPPLIER / MEMO ───────── */
/* ───────── SUPPLIER / MEMO ───────── */
Grid::make(12)->schema([
    Select::make('supplier_id')
        ->relationship('supplier', 'company_name')
        ->label('Vendor')
        ->searchable()
        ->preload()
        ->required()
        ->live()
        ->afterStateUpdated(function (Get $get, Forms\Set $set, $state) {
            // Automatically sync the memo_vendor_id if is_memo is toggled
            if ($get('is_memo')) {
                $set('memo_vendor_id', $state);
            }
            
            if ($state) {
                $latestCode = ProductItem::where('supplier_id', $state)
                    ->latest()
                    ->value('supplier_code');
                $set('supplier_code', $latestCode);
            }
        })
        ->columnSpan(4),

    Toggle::make('is_memo')
        ->label('Is this Memo?')
        ->inline(false)
        ->live()
        ->afterStateUpdated(function (Get $get, Forms\Set $set, $state) {
            // When toggled ON, copy supplier_id to memo_vendor_id
            if ($state) {
                $set('memo_vendor_id', $get('supplier_id'));
            } else {
                $set('memo_vendor_id', null);
            }
        })
        ->columnSpan(2),

    // Hidden field to ensure memo_vendor_id is saved to the database
    Hidden::make('memo_vendor_id'),

    Placeholder::make('memo_vender_label')
        ->label('Current Inventory Type')
        ->visible(fn (Get $get) => filled($get('supplier_id'))) 
        ->content(function (Get $get) {
            $supplierId = $get('supplier_id');
            $supplier = \App\Models\Supplier::find($supplierId);
            $supplierName = $supplier?->company_name ?? 'Vendor';
            
            return $get('is_memo') 
                ? "Consignment: Memo + {$supplierName}" 
                : "Owned: {$supplierName}";
        })
        ->columnSpan(3),

    Select::make('memo_status')
        ->label('Memo Action')
        ->options([
            'on_memo' => 'On Memo',
            'returned' => 'Returned to Vendor',
        ])
        ->default('on_memo')
        ->visible(fn (Get $get) => $get('is_memo')) 
        ->required(fn (Get $get) => $get('is_memo'))
        ->columnSpan(3),
]),
                    CheckboxList::make('print_options')
                        ->options([
                            'print_tag' => 'Print Barcode Tag',
                            'print_rfid' => 'Print RFID Tag',
                            'encode_rfid' => 'Encode RFID Data',
                        ])
                        ->columns(3)
                        ->columnSpan(12),

                    Toggle::make('enable_rfid_tracking')
                        ->label('Enable RFID Tracking')
                        ->columnSpan(6),

                    TextInput::make('rfid_code')
                        ->label('RFID EPC Code')
                        ->disabled()
                        ->columnSpan(6),

                    TextInput::make('supplier_code')
                        ->label('Vendor Code')
                        ->columnSpan(3),

                   
Select::make('department')
    ->label('Department')
    // Pull the names from our new array structure
    ->options(function() {
        $depts = \App\Models\InventorySetting::where('key', 'departments')->first()?->value ?? [];
        return collect($depts)->pluck('name', 'name');
    })
    ->searchable()
    ->hintAction(
                            FormAction::make('departmentHelp')
                                ->icon('heroicon-o-information-circle')
                                ->tooltip('Go to Inventory Settings → Categories to configure departments')
                        )
    ->live()
    ->afterStateUpdated(fn (Get $get, Forms\Set $set) => self::runSmartPricing($get, $set))
    ->columnSpan(3),


                    Select::make('sub_department')
                        ->options(fn () => \App\Models\InventorySetting::where('key', 'sub_departments')->first()?->value ?? [])
                        ->hintAction(
                            FormAction::make('Help')
                                ->icon('heroicon-o-information-circle')
                                ->tooltip('Go to Inventory Settings → Categories to configure Sub-departments')
                        )
                        ->searchable()
                        ->columnSpan(3),

                    Select::make('category')
                        ->options(fn () => \App\Models\InventorySetting::where('key', 'categories')->first()?->value ?? [])
                        ->hintAction(
                            FormAction::make('Help')
                                ->icon('heroicon-o-information-circle')
                                ->tooltip('Go to Inventory Settings → Categories to configure Category')
                        )
                        ->searchable()
                        ->columnSpan(2),

                    Select::make('metal_type')
                        ->label('Metal Karat')
                        ->hintAction(
                            FormAction::make('Help')
                                ->icon('heroicon-o-information-circle')
                                ->tooltip('Go to Inventory Settings → Categories to configure Metal Karat')
                        )
                        ->options(fn () => \App\Models\InventorySetting::where('key', 'metal_types')->first()?->value ?? [])
                        ->searchable()
                        ->columnSpan(2),

                    TextInput::make('size')->columnSpan(2),

                    TextInput::make('metal_weight')
                        ->label('Metal Weight')
                        ->columnSpan(2),

                    TextInput::make('diamond_weight')
                        ->label('Diamond Weight (CTW)')
                        ->placeholder('1.25 CTW')
                        ->columnSpan(6),

                    /* ───────── PRICING ───────── */
                    TextInput::make('qty')
                        ->numeric()
                        ->default(1)
                        ->columnSpan(2),

                    TextInput::make('cost_price')
    ->prefix('$')
    ->numeric()
    // 🔹 ADDED: Make this live so math happens while typing
    ->live(onBlur: true) 
    ->afterStateUpdated(fn (Get $get, Forms\Set $set) => self::runSmartPricing($get, $set))
    ->columnSpan(2),

TextInput::make('retail_price')
    ->prefix('$')
    ->numeric()
    // 🔹 ADDED: Make this live so users can still override manually if needed
    ->live() 
    ->columnSpan(3),

TextInput::make('web_price')
    ->prefix('$')
    ->numeric()
    ->live()
    ->columnSpan(3),

                    TextInput::make('discount_percent')
                        ->suffix('%')
                        ->default(0)
                        ->numeric()
                        ->columnSpan(2),

                    Textarea::make('custom_description')
                        ->rows(3)
                        ->columnSpan(12),

                    TextInput::make('barcode')
                        ->label('Stock Number')
                        ->disabled()
                        ->columnSpan(4),

                    TextInput::make('serial_number')->columnSpan(4),

                    TextInput::make('component_qty')
                        ->numeric()
                        ->default(1)
                        ->columnSpan(4),
                ]),
        ]);
    }

    /* ───────────────────────── TABLE ───────────────────────── */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('barcode')
                    ->label('STOCK NO.')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\IconColumn::make('is_trade_in')
                    ->label('SOURCE')
                    ->boolean()
                    ->trueIcon('heroicon-o-user-group')
                    ->falseIcon('heroicon-o-building-office')
                    ->trueColor('primary')
                    ->falseColor('gray')
                    ->alignCenter()
                    ->tooltip(
                        fn($record) =>
                        $record->is_trade_in ? 'Customer Trade-In' : 'Vendor Purchase'
                    ),

                Tables\Columns\TextColumn::make('custom_description')
                    ->label('DESCRIPTION')
                    ->limit(40)
                    ->wrap(),

                Tables\Columns\TextColumn::make('metal_weight')
                    ->label('WEIGHT')
                    ->suffix(' g')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('retail_price')
                    ->label('PRICE')
                    ->money('USD')
                    ->alignRight()
                    ->color('success'),

                /* ✅ RFID NUMBER COLUMN */
                Tables\Columns\TextColumn::make('rfid_code')
                    ->label('RFID NUMBER')
                    ->fontFamily('mono')
                    ->placeholder('Not Printed')
                    ->searchable()
                    ->copyable(),

                /* ✅ RFID STATUS BADGE */
                Tables\Columns\BadgeColumn::make('rfid_status')
                    ->label('RFID')
                    ->getStateUsing(
                        fn($record) =>
                        filled($record->rfid_code) ? 'Encoded' : 'Missing'
                    )
                    ->colors([
                        'success' => 'Encoded',
                        'gray' => 'Missing',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'Encoded',
                        'heroicon-o-x-circle' => 'Missing',
                    ]),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->alignCenter()
                    ->color(fn(string $state) => match ($state) {
                        'in_stock' => 'success',
                        'sold' => 'danger',
                        'on_hold' => 'warning',
                        default => 'gray',
                    }),
            ])

            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    ViewAction::make(),

                    // 🔹 REPLACED: Custom Deletion Workflow with Approval Chain
                    // app/Filament/Resources/ProductItemResource.php

                    Action::make('delete')
                        ->label('Delete')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->visible(fn(ProductItem $record) => $record->status !== 'sold')
                        ->action(function (ProductItem $record, array $data): void {
                            // 🔹 FIX: Changed 'super_admin' to 'Superadmin'
                            if (auth()->user()->hasRole('Superadmin')) {
                                $record->delete();
                                Notification::make()->title('Item Deleted Permanently')->success()->send();
                                return;
                            }

                            \App\Models\DeletionRequest::create([
                                'product_item_id' => $record->id,
                                'user_id' => auth()->id(),
                                'reason' => $data['reason'],
                                'status' => 'pending',
                            ]);

                            Notification::make()->title('Deletion Request Submitted')->warning()->send();
                        })
                        // 🔹 FIX: Changed 'super_admin' to 'Superadmin'
                        ->form(fn() => auth()->user()->hasRole('Superadmin') ? [] : [
                            Forms\Components\Textarea::make('reason')
                                ->label('Reason for Deletion')
                                ->required(),
                        ])
                        ->requiresConfirmation(fn() => auth()->user()->hasRole('Superadmin')),

                    /* ... existing print actions ... */
                    Action::make('print_barcode')
    ->label('Print Barcode')
    ->icon('heroicon-o-printer')
    ->action(function ($record, ZebraPrinterService $service) {
        // This method generates ZPL and sends it to the printer 
        // using the 'false' flag to skip RFID encoding
        $result = $service->printJewelryTag($record, false);

        if ($result) {
            Notification::make()->title('Barcode Sent to Printer')->success()->send();
        } else {
            Notification::make()->title('Printer Connection Failed')->danger()->send();
        }
    }),

Action::make('print_rfid')
    ->label('Print RFID')
    ->icon('heroicon-o-identification')
    ->color('warning')
    ->action(function ($record, ZebraPrinterService $service) {
        // This method generates ZPL and sends it to the printer 
        // using the 'true' flag to include RFID encoding
        $result = $service->printJewelryTag($record, true);

        if ($result) {
            Notification::make()->title('RFID Tag Sent to Printer')->success()->send();
        } else {
            Notification::make()->title('Printer Connection Failed')->danger()->send();
        }
    }),
                ]),
            ]);
    }
public static function runSmartPricing(Get $get, Forms\Set $set): void
{
    $cost = floatval($get('cost_price'));
    $selectedDept = $get('department');

    // If cost is 0 or no department is selected, do nothing
    if ($cost <= 0 || !$selectedDept) return;

    // 1. Fetch the dynamic settings from the database
    $settings = \App\Models\InventorySetting::where('key', 'departments')->first()?->value ?? [];
    
    // 2. Find the specific department chosen by the user
    $deptData = collect($settings)->firstWhere('name', $selectedDept);

    // 3. If the department has a multiplier, do the math
    if ($deptData && isset($deptData['multiplier'])) {
        $multiplier = floatval($deptData['multiplier']);
        
        // Example: $1000 cost * 2x multiplier = $2000 retail
        $calculatedPrice = $cost * $multiplier;
        
        // 4. Update the UI fields automatically
        $set('retail_price', number_format($calculatedPrice, 2, '.', ''));
        $set('web_price', number_format($calculatedPrice, 2, '.', ''));
        
        // Optional: Auto-generate a description if empty
        if (blank($get('custom_description'))) {
            $set('custom_description', "{$selectedDept} - " . ($get('metal_type') ?? 'Jewelry'));
        }
    }
}

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductItems::route('/'),
            'create' => Pages\CreateProductItem::route('/create'),
            'edit' => Pages\EditProductItem::route('/{record}/edit'),
        ];
    }
}
