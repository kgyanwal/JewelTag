<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Models\Sale;
use App\Models\ProductItem;
use App\Models\Customer;
use App\Models\User;
use App\Models\Store;
use App\Models\Repair;
use App\Models\CustomOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\{Section, Grid, Group, Repeater, Select, TextInput, Placeholder, Hidden, Checkbox, DatePicker, Textarea, Toggle};
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Aws\Sns\SnsClient;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Actions\Action as FormAction;
use Tapp\FilamentGoogleAutocomplete\Forms\Components\GoogleAutocomplete;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'Quick Sale';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(12)->schema([
                    Group::make()->columnSpan(8)->schema([
                        Section::make('Stock Selection')
                            ->headerActions([
                                FormAction::make('clear_draft')
                                    ->label('Start Fresh / New Sale')
                                    ->icon('heroicon-m-trash')
                                    ->color('danger')
                                    ->requiresConfirmation()
                                    ->action(function () {
                                        session()->forget('sale_draft');
                                        return redirect(static::getUrl('create'));
                                    }),
                            ])
                            ->schema([

                                Grid::make(4)->schema([
                                    Select::make('current_item_search')
                                        ->label('Select Stock #')
                                        ->searchable()
                                        ->preload()
                                        ->options(
                                            fn() => ProductItem::where('qty', '>', 0)
                                                ->where('status', 'in_stock')
                                                ->get()
                                                ->mapWithKeys(fn($item) => [
                                                    $item->id => "{$item->barcode} - {$item->qty} left (\${$item->retail_price})"
                                                ])
                                        )
                                        ->live()
                                        ->dehydrated(false)
                                        // 🚀 AUTOMATIC ADD LOGIC
                                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                            if (!$state) return;

                                            $item = ProductItem::find($state);
                                            if (!$item) return;

                                            // 1. Get current items or empty array
                                            $currentItems = $get('items') ?? [];
$qty = $get('current_qty') ?? 1;
    $price = $item->retail_price;

    // 🚀 THE FIX: Calculate initial override price (Price * Qty)
    $initialTotal = $price * $qty;
                                            // 2. Prepare the new row
                                            $newRow = [
                                                'product_item_id' => $item->id,
                                                'repair_id' => null,
                                                'stock_no_display' => $item->barcode,
                                                'custom_description' => $item->custom_description ?? $item->barcode,
                                                'qty' => $get('current_qty') ?? 1,
                                                'sold_price' => $item->retail_price,
                                                'discount_percent' => 0,
                                                'discount_amount' => 0,
                                                'is_tax_free' => false,
                                            ];

                                            // 3. Push to repeater and reset search
                                            $currentItems[] = $newRow;

                                            $set('items', $currentItems);
                                            $set('current_item_search', null); // 🚀 Clears the search box for the next item
                                            $set('current_qty', 1);

                                            // 4. Trigger total calculation
                                            self::updateTotals($get, $set);
                                        })->columnSpan(3),

                                    TextInput::make('current_qty')
                                        ->label('Qty')
                                        ->numeric()
                                        ->default(1)
                                        ->dehydrated(false)
                                        ->live(),
                                ]),
                                // 💡 The "ADD TO BILL" button is no longer needed but you can keep a placeholder if preferred.
                            ]),

                        Section::make('Current Bill Items')->schema([
                            Repeater::make('items')
                                ->relationship('items')
                                ->live()
                                ->schema([
                                    Hidden::make('product_item_id')->dehydrated(),
                                    Hidden::make('repair_id')->dehydrated(),
                                    Hidden::make('custom_order_id')->dehydrated(),

                                    TextInput::make('stock_no_display')
                                        ->label('Item')
                                        ->readOnly()
                                        ->dehydrated(false)
                                        ->columnSpan(1),

                                    Forms\Components\Textarea::make('custom_description')
                                        ->label('Description')
                                        ->maxLength(200)
                                        ->rows(2)                 // fixed height
                                        ->autosize(false)         // 🚀 stop auto expanding
                                        ->extraInputAttributes([
                                            'style' => 'max-height:60px; overflow-y:auto; resize:none;'
                                        ])
                                        ->columnSpan(3),

                                    // 1. QUANTITY CHANGE: Update Line Total & Discount Amount
                                    TextInput::make('qty')
                                        ->numeric()
                                        ->default(1)
                                        ->required()
                                        ->live(onBlur: true) // Wait until user leaves field
                                        ->columnSpan(1)
                                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                            // Logic to limit qty based on stock
                                            if ($pid = $get('product_item_id')) {
                                                $productItem = ProductItem::find($pid);
                                                if ($productItem && $state > $productItem->qty) {
                                                    $set('qty', $productItem->qty);
                                                    $state = $productItem->qty;
                                                }
                                            }

                                            // Recalculate Discount Amount based on existing Percent
                                            $price = floatval($get('sold_price'));
                                            $percent = floatval($get('discount_percent'));
                                            $lineTotal = $price * $state;
                                            $set('discount_amount', number_format($lineTotal * ($percent / 100), 2, '.', ''));

                                            self::updateTotals($get, $set);
                                        }),

                                    // 2. PRICE CHANGE: Update Discount Amount
                                    TextInput::make('sold_price')
                                        ->label('Price')
                                        ->numeric()
                                        ->readOnly()
                                        ->live(onBlur: true)
                                        ->columnSpan(2)
                                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                            $qty = intval($get('qty') ?? 1);
                                            $percent = floatval($get('discount_percent') ?? 0);

                                            // Calculate new discount dollar amount based on the new price
                                            $lineTotalBeforeDiscount = floatval($state) * $qty;
                                            $newDiscountAmount = $lineTotalBeforeDiscount * ($percent / 100);

                                            // 🚀 THE FIX: Explicitly set the discount amount so the UI updates
                                            $set('discount_amount', number_format($newDiscountAmount, 2, '.', ''));

                                            // Trigger the global subtotal/tax calculation
                                            self::updateTotals($get, $set);
                                        }),
                                    TextInput::make('sale_price_override')
    ->label('Sale Price')
    ->numeric()
    ->prefix('$')
    ->live(onBlur: true)
    ->columnSpan(2)
    ->afterStateUpdated(function ($state, Get $get, Set $set) {
        $originalPrice = floatval($get('sold_price') ?? 0);
        $qty = intval($get('qty') ?? 1);
        $newFinalPrice = floatval($state ?? 0);
        
        $totalOriginal = $originalPrice * $qty;

        if ($totalOriginal > 0) {
            $discountAmount = $totalOriginal - $newFinalPrice;
            $discountPercent = ($discountAmount / $totalOriginal) * 100;

            // 🚀 Force update all fields so the UI reflects the math perfectly
            $set('discount_amount', number_format($discountAmount, 2, '.', ''));
            $set('discount_percent', number_format($discountPercent, 2, '.', ''));
        }

        static::updateTotals($get, $set);
    }),
                                    // 3. PERCENT CHANGE: Calculate Amount ($)
                                   TextInput::make('discount_percent')
    ->label('Disc %')
    ->numeric()
    ->suffix('%')
    ->default(0)
    ->live(onBlur: true)
    ->columnSpan(2)
    ->afterStateUpdated(function ($state, Get $get, Set $set) {
        $price = floatval($get('sold_price') ?? 0);
        $qty = intval($get('qty') ?? 1);
        $lineTotalBeforeDiscount = $price * $qty;

        $percent = floatval($state ?? 0);
        
        // Calculate the dollar amount of the discount
        
        $discountAmount = $lineTotalBeforeDiscount * ($percent / 100);
        $finalPrice = $lineTotalBeforeDiscount - $discountAmount;

        // 🚀 Update the sibling fields
        $set('discount_amount', number_format($discountAmount, 2, '.', ''));
        $set('sale_price_override', number_format($finalPrice, 2, '.', ''));

        static::updateTotals($get, $set);
    }),

                                    // 4. AMOUNT CHANGE: Calculate Percent (%)
                                    TextInput::make('discount_amount')
    ->label('Disc $')
    ->numeric()
    ->default(0)
    ->prefix('$')
    ->live(onBlur: true)
    ->columnSpan(2)
    ->afterStateUpdated(function ($state, Get $get, Set $set) {
        $price = floatval($get('sold_price') ?? 0);
        $qty = intval($get('qty') ?? 1);
        $lineTotalBeforeDiscount = $price * $qty;
        $discountAmount = floatval($state ?? 0);

        if ($lineTotalBeforeDiscount > 0) {
            $percent = ($discountAmount / $lineTotalBeforeDiscount) * 100;
            $set('discount_percent', number_format($percent, 2, '.', ''));
        }

        // 🚀 Update the final sale price override
        $set('sale_price_override', number_format($lineTotalBeforeDiscount - $discountAmount, 2, '.', ''));

        static::updateTotals($get, $set);
    }),
                                    Checkbox::make('is_tax_free')
                                        ->label('No Tax')
                                        ->columnSpan(1)
                                        ->default(false)
                                        ->dehydrated(true)
                                        ->live()
                                        // 💡 Show message only when checked
                                        ->hint(fn($state) => $state ? 'There is no tax involved here' : null)
                                        ->hintColor('warning')
                                        // 💡 Trigger the recalculation logic immediately
                                        ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotals($get, $set)),
                                ])

                                ->columns(12)
                                ->addable(false)
                                ->deletable(true)
                                ->reorderable(false)
                                ->default([])
                                ->live()
                                ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotals($get, $set))
                        ]),
                        Section::make('🛠️ Workshop / Special Job')
                            ->description('Add custom work instructions (Resize, Solder, etc.)')
                            ->icon('heroicon-o-wrench')
                            ->collapsible()
                            ->schema([
                                Grid::make(3)->schema([
                                    Select::make('job_type')
                                        ->label('Service Type')
                                        ->options([
                                            'Resize' => 'Resize',
                                            'Solder' => 'Solder / Weld',
                                            'Bail Change' => 'Bail Change',
                                            'Shorten' => 'Shortening',
                                            'Stone Setting' => 'Stone Setting',
                                            'Polishing' => 'Polishing / Rhodium',
                                        ])
                                        ->placeholder('Select Job Type')
                                        ->live()
                                        ->afterStateUpdated(fn(Get $get, Set $set) => self::mapResizeToNotes($get, $set)),

                                    Select::make('metal_type')
                                        ->label('Metal')
                                        ->options([
                                            '10k' => '10k Gold',
                                            '14k' => '14k Gold',
                                            '18k' => '18k Gold',
                                            'Platinum' => 'Platinum',
                                            'Silver' => 'Sterling Silver',
                                        ])
                                        ->placeholder('Select Metal'),

                                    DatePicker::make('date_required')
                                        ->label('Completion Date')
                                        ->native(false)
                                        ->live()
                                        ->afterStateUpdated(fn(Get $get, Set $set) => self::mapResizeToNotes($get, $set)),
                                ]),

                                Grid::make(2)
                                    ->visible(fn(Get $get) => $get('job_type') === 'Resize')
                                    ->schema([
                                        TextInput::make('current_size')
                                            ->label('Current Size')
                                            ->live()
                                            ->afterStateUpdated(fn(Get $get, Set $set) => self::mapResizeToNotes($get, $set)),

                                        TextInput::make('target_size')
                                            ->label('Target Size')
                                            ->live()
                                            ->afterStateUpdated(fn(Get $get, Set $set) => self::mapResizeToNotes($get, $set)),
                                    ]),

                                Textarea::make('job_instructions')
                                    ->label('Bench Notes / Instructions')
                                    ->placeholder('Describe exactly what the jeweler needs to do...')
                                    ->columnSpanFull()
                                    ->rows(3),

                                // Hidden field to keep your summary sync logic
                                Hidden::make('notes'),
                            ]),
                        Section::make('Trade-In Details')
                            ->schema([
                                Select::make('has_trade_in')
                                    ->label('Is there a Trade-In?')
                                    ->options([
                                        1 => 'Yes',
                                        0 => 'No'
                                    ])
                                    ->default(0) // 🚀 This fixes the "field is required" error by providing a default value
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotals($get, $set)),

                                Grid::make(2)
                                    ->visible(fn(Get $get) => $get('has_trade_in') == 1)
                                    ->schema([
                                        TextInput::make('trade_in_value')
                                            ->label('Trade-In Value (Deduction)')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required(fn(Get $get) => $get('has_trade_in') == 1) // 🚀 Only required if Yes
                                            ->live()
                                            ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotals($get, $set)),

                                        TextInput::make('trade_in_receipt_no')
                                            ->label('Trade-In Tracking #')
                                            ->default(fn() => 'TRD-' . date('Ymd-His'))
                                            ->readOnly(),

                                        Forms\Components\Textarea::make('trade_in_description')
                                            ->label('Item Description')
                                            ->placeholder('e.g. 14k White Gold Diamond Band')
                                            ->required(fn(Get $get) => $get('has_trade_in') == 1) // 🚀 Only required if Yes
                                            ->columnSpanFull()
                                            ->rows(2),
                                    ]),
                            ]),
                        Section::make('Shipping & Handling')->schema([
                            Grid::make(4)->schema([
                                TextInput::make('shipping_charges')
                                    ->label('Charges')->numeric()->prefix('$')->default(0)->required()->live()
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotals($get, $set)),
                                Checkbox::make('shipping_taxed')->label('Taxed?')->default(false),
                                Select::make('carrier')->options(['No carrier' => 'No carrier', 'UPS' => 'UPS', 'FedEx' => 'FedEx'])->default('No carrier'),
                                TextInput::make('tracking_number')->label('Tracking')->nullable(),
                            ]),

                            // 🆕 WARRANTY & FOLLOW-UP SECTION (Added Below Shipping)
                            Grid::make(4)->schema([
                                // 1. Warranty Toggle (Yes/No)
                                Select::make('has_warranty')
                                    ->label('Include Warranty?')
                                    ->options([0 => 'No', 1 => 'Yes'])
                                    ->default(0)
                                    ->live(),

                                // 2. Warranty Duration (Dynamic from Settings)
                                Select::make('warranty_period')
                                    ->label('Warranty Time')
                                    ->visible(fn(Get $get) => $get('has_warranty') == 1) // Only show if Yes
                                    ->required(fn(Get $get) => $get('has_warranty') == 1)
                                    ->options(function () {
                                        // Fetch from settings or use defaults
                                        $json = DB::table('site_settings')->where('key', 'warranty_options')->value('value');
                                        $options = $json ? json_decode($json, true) : ['1 Year', '2 Years', 'Lifetime'];
                                        return array_combine($options, $options); // Key = Value
                                    }),

                                // 3. First Follow Up
                                Forms\Components\DatePicker::make('follow_up_date')
                                    ->label('Follow Up (2 Weeks)')
                                    ->default(now()->addWeeks(2)) // 🟢 Default to 2 weeks from today
                                    ->native(false)
                                    ->displayFormat('M d, Y'),

                                // 4. Second Follow Up
                                Forms\Components\DatePicker::make('second_follow_up_date')
                                    ->label('Follow Up Second')
                                    ->default(now()->addMonths(6)) // Example default
                                    ->native(false)
                                    ->displayFormat('M d, Y'),
                            ]),

                        ]),
                        Section::make('Receipt Customization')
                            ->schema([
                                Textarea::make('notes')
                                    ->label('Receipt Notes / Warranty')
                                    ->placeholder('Add special notes to be printed on the invoice...')
                                    ->rows(3)
                                    ->helperText('This text will be saved in your database and printed on the customer receipt.'),
                            ]),
                    ]),

                    Group::make()->columnSpan(4)->schema([
                        Section::make('Customer & Personnel')
                            ->description('Select a customer first to load profile and credits.')
                            ->schema([
                                Grid::make(1)->schema([
                                    Select::make('customer_id')
                                        ->label('Select Customer')
                                        ->relationship('customer', 'name')
                                        ->getOptionLabelFromRecordUsing(fn($record) => "{$record->name} {$record->last_name} | {$record->phone} (#{$record->customer_no})")
                                        ->searchable()
                                        ->getSearchResultsUsing(function (string $search) {
                                            return \App\Models\Customer::query()
                                                ->where(function ($q) use ($search) {
                                                    $q->whereRaw("CONCAT(name, ' ', last_name) LIKE ?", ["%{$search}%"])
                                                        ->orWhereRaw("CONCAT(last_name, ' ', name) LIKE ?", ["%{$search}%"])
                                                        ->orWhere('phone', 'like', "%{$search}%")
                                                        ->orWhere('customer_no', 'like', "%{$search}%");
                                                })
                                                ->limit(50)
                                                ->get()
                                                ->mapWithKeys(function ($customer) {
                                                    return [
                                                        $customer->id => "{$customer->name} {$customer->last_name} | {$customer->phone} (#{$customer->customer_no})"
                                                    ];
                                                });
                                        })
                                        ->preload()
                                        ->required()
                                        ->live()
                                        ->columnSpanFull()
                                        ->hintAction(
                                            FormAction::make('view_customer_details')
                                                ->label('View Profile')
                                                ->icon('heroicon-o-user-circle')
                                                ->color('info')
                                                ->visible(fn(Get $get) => $get('customer_id'))
                                                ->modalHeading('Customer Profile Details')
                                                ->modalSubmitAction(false)
                                                ->modalCancelActionLabel('Close')
                                                ->slideOver()
                                                ->form(function (Get $get) {
                                                    $customer = \App\Models\Customer::find($get('customer_id'));
                                                    if (!$customer) return [];

                                                    return [
                                                        Grid::make(2)->schema([
                                                            Placeholder::make('img')
                                                                ->label('')
                                                                ->content(new HtmlString("
                                            <div class='flex items-center gap-4 p-4 bg-gray-50 rounded-xl border border-gray-200'>
                                                <img src='" . ($customer->image ? asset('storage/' . $customer->image) : asset('jeweltaglogo.png')) . "' class='w-20 h-20 rounded-full object-cover shadow-sm'>
                                                <div>
                                                    <h3 class='text-lg font-bold text-gray-900'>{$customer->name} {$customer->last_name}</h3>
                                                    <p class='text-sm text-gray-500'>ID: {$customer->customer_no}</p>
                                                    <span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-800 mt-1'>
                                                        Balance: $" . number_format($customer->credit_balance ?? 0, 2) . "
                                                    </span>
                                                </div>
                                            </div>
                                        "))->columnSpanFull(),
                                                            TextInput::make('p')->label('Phone')->default($customer->phone)->readOnly(),
                                                            TextInput::make('e')->label('Email')->default($customer->email)->readOnly(),
                                                            TextInput::make('addr')
                                                                ->label('Full Address')
                                                                ->default(trim("{$customer->street} {$customer->suburb} {$customer->city} {$customer->state} {$customer->postcode}"))
                                                                ->readOnly()
                                                                ->columnSpanFull(),
                                                            Placeholder::make('history')
                                                                ->label('Last Sale Date')
                                                                ->content($customer->sales()->latest()->first()?->created_at?->format('M d, Y') ?? 'No previous sales')
                                                        ]),
                                                        Placeholder::make('full_details_link')
                                                            ->label('')
                                                            ->content(new HtmlString("
                                        <div class='mt-4 pt-4 border-t border-gray-200'>
                                            <a href='" . \App\Filament\Resources\CustomerResource::getUrl('edit', ['record' => $customer->id]) . "' 
                                               target='_blank' 
                                               class='inline-flex items-center gap-2 px-4 py-2 text-sm font-bold text-white bg-primary-600 rounded-lg hover:bg-primary-500 transition shadow-sm'>
                                                <svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14'></path></svg>
                                                View All Details (Full Profile)
                                            </a>
                                            <p class='text-[10px] text-gray-400 mt-2 italic'>Opens in a new browser tab</p>
                                        </div>
                                    "))->columnSpanFull(),
                                                    ];
                                                })
                                        )
                                        ->createOptionModalHeading('Quick Add New Customer')
                                        ->createOptionForm([
                                            Forms\Components\Tabs::make('New Customer')
                                                ->tabs([
                                                    Forms\Components\Tabs\Tab::make('Contact')
                                                        ->icon('heroicon-o-user')
                                                        ->schema([
                                                            Forms\Components\Grid::make(2)->schema([
                                                                Forms\Components\TextInput::make('name')->label('First Name')->required(),
                                                                Forms\Components\TextInput::make('last_name')->label('Last Name'),
                                                            ]),
                                                            Forms\Components\Grid::make(2)->schema([
                                                                Forms\Components\TextInput::make('phone')
                                                                    ->label('Mobile Phone')
                                                                    ->tel()
                                                                    ->prefix('+1')
                                                                    ->mask('(999) 999-9999')
                                                                    ->placeholder('(555) 555-5555')
                                                                    ->stripCharacters(['(', ')', '-', ' '])
                                                                    ->rule('regex:/^[0-9]{10}$/')
                                                                    ->afterStateHydrated(function ($component, $state) {
                                                                        if ($state && preg_match('/^[0-9]{10}$/', $state)) {
                                                                            $component->state('(' . substr($state, 0, 3) . ') ' . substr($state, 3, 3) . '-' . substr($state, 6));
                                                                        }
                                                                    }),
                                                                Forms\Components\TextInput::make('email')->label('Email')->email(),
                                                            ]),
                                                            Forms\Components\Grid::make(2)->schema([
                                                                DatePicker::make('dob')
                                                                    ->rule('before_or_equal:today')
                                                                    ->label('Birth Date'),

                                                                Forms\Components\DatePicker::make('wedding_anniversary')
                                                                    ->label('Wedding Date')

                                                            ]),
                                                            // 🚀 Google Autocomplete Address Section
                                                            Forms\Components\Section::make('Customer Address')
                                                                ->description('Search for an address to automatically fill the fields below.')
                                                                ->columns(2)
                                                                ->collapsible()
                                                                ->schema([
                                                                    GoogleAutocomplete::make('address_search')
                                                                        ->label('Search Address')
                                                                        ->autocompletePlaceholder('Start typing address...')
                                                                        ->countries(['US'])
                                                                        ->columnSpanFull()
                                                                        ->withFields([
                                                                            TextInput::make('street')
                                                                                ->label('Street Address')
                                                                                ->extraInputAttributes(['data-google-field' => '{street_number} {route}']),

                                                                            TextInput::make('address_line_2')
                                                                                ->label('Address 2 / Apt / Suite')
                                                                                ->extraInputAttributes(['data-google-field' => 'subpremise']),

                                                                            TextInput::make('city')
                                                                                ->label('City')
                                                                                ->extraInputAttributes([
                                                                                    'data-google-field' => 'locality',  // ✅ Single field only
                                                                                    'data-google-value' => 'short_name'  // ✅ Gets "NYC" instead of "New York City"
                                                                                ]),

                                                                            TextInput::make('state')
                                                                                ->label('State')
                                                                                ->extraInputAttributes(['data-google-field' => 'administrative_area_level_1'])
                                                                                ->columnSpan(1),

                                                                            TextInput::make('postcode')
                                                                                ->label('Zip Code')
                                                                                ->extraInputAttributes(['data-google-field' => 'postal_code'])
                                                                                ->columnSpan(1),
                                                                        ]),

                                                                    Forms\Components\Select::make('country')
                                                                        ->label('Country')
                                                                        ->default('United States')
                                                                        ->searchable(),
                                                                ]),

                                                        ]),
                                                ]),
                                            Forms\Components\Hidden::make('customer_no')
                                                ->default(fn() => 'CUST-' . strtoupper(Str::random(6))),
                                        ])
                                        ->createOptionUsing(function (array $data) {
                                            return \App\Models\Customer::create($data)->id;
                                        }),
                                ]),



                                // 🚀 SALES PERSON LIST (Now follows customer selection)
                                Select::make('sales_person_list')
                                    ->label('Sales Staff')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->options(fn() => User::pluck('name', 'name')->toArray())
                                    ->default(fn() => [auth()->user()->name])
                                    ->required(),
                            ]),
                        Section::make('Payment & Status')->schema([
                            Toggle::make('is_split_payment')
                                ->label('Enable Split Payment')
                                ->onColor('warning')
                                ->live()
                                ->columnSpanFull(),

                            // Standard Payment (Hidden if Split is ON)
                            Select::make('payment_method')
                                ->label('Payment Method')
                                ->options(self::getPaymentOptions())
                                ->default('cash')
                                ->required(fn(Get $get) => !$get('is_split_payment'))
                                ->visible(fn(Get $get) => !$get('is_split_payment')),

                            // 🚀 NEW: Dynamic Split Payment Repeater
                            Repeater::make('split_payments')
                                ->label('Payment Breakdown')
                                ->schema([
                                    Grid::make(2)->schema([
                                        Select::make('method')
                                            ->options(self::getPaymentOptions())

                                            ->required()
                                            ->label('Method'),
                                        TextInput::make('amount')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required()
                                            ->label('Amount')
                                            ->live(onBlur: true),


                                    ]),
                                ])
                                ->visible(fn(Get $get) => $get('is_split_payment'))
                                ->defaultItems(2) // 👈 Initially visible two
                                ->maxItems(6)     // 👈 Limit to six
                                ->addActionLabel('Add Another Payment Method') // 👈 Acts as "Load More"
                                ->reorderable(false)
                                ->live()
                                ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotals($get, $set)),

                            // 🔹 Live Balance Calculation
                            Placeholder::make('split_calc')
                                ->label('Remaining Balance')
                                ->content(function (Get $get) {
                                    $total = (float) $get('final_total');
                                    $payments = $get('split_payments') ?? [];

                                    $sum = collect($payments)->sum(fn($p) => (float)($p['amount'] ?? 0));
                                    $remaining = $total - $sum;

                                    $color = round($remaining, 2) == 0 ? 'text-green-600' : 'text-red-600';
                                    return new HtmlString("<span class='{$color} font-bold text-xl'>$" . number_format($remaining, 2) . "</span>");
                                })
                                ->visible(fn(Get $get) => $get('is_split_payment')),

                            Select::make('status')
                                ->label('Sale Status')
                                ->options(['completed' => 'Completed', 'pending' => 'Pending', 'inprogress' => 'In Progress'])
                                ->default('completed')
                                ->required(),
                        ]),

                        Section::make('Financial Summary')->schema([
                            self::totalRow('TAX TOTAL', 'tax_amount'),
                            self::totalRow('SUBTOTAL', 'subtotal'),
                            Placeholder::make('total_display')
                                ->label('BALANCE DUE')
                               ->live()
                                ->content(fn(Get $get) => '$' . (number_format((float)($get('final_total') ?? 0), 2)))
                                ->extraAttributes(['class' => 'text-red-600 font-bold text-3xl']),

                            Forms\Components\Actions::make([
                                // Action::make('complete')
                                //     ->label('COMPLETE SALE')
                                //     ->color('success')
                                //     ->button()
                                //     ->extraAttributes(['class' => 'w-full'])
                                //     // 1. Hide the button if the record is already completed
                                //     ->hidden(fn($record) => $record && $record->status === 'completed')
                                //     // 2. Determine whether to call create() or save() based on the page context
                                //     ->action(function ($livewire, $record) {
                                //         if (method_exists($livewire, 'save')) {
                                //             $livewire->save(); // Context: Edit Page
                                //         } else {
                                //             $livewire->create(); // Context: Create Page
                                //         }

                                //         \Filament\Notifications\Notification::make()
                                //             ->title('Sale Finalized')
                                //             ->success()
                                //             ->send();
                                //     }),
                            ]),
                        ]),




                    ]),
                ]),

                Hidden::make('final_total'),
                Hidden::make('subtotal'),
                Hidden::make('tax_amount'),
                Hidden::make('store_id')->default(fn() => auth()->user()->store_id ?? Store::first()?->id ?? 1),
                Hidden::make('invoice_number')->dehydrated(true),
                Hidden::make('custom_order_id')
                    ->dehydrated(false), // 👈 Add this: tells Filament NOT to insert into 'sales' table

                Hidden::make('repair_id')
                    ->dehydrated(false),
            ])
            ->statePath('data');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->where('status', '!=', 'void'))
            ->columns([
                TextColumn::make('invoice_number')->label('Inv #')->searchable()->sortable()->grow(false),
                TextColumn::make('customer.name')->label('Customer')->searchable(['name', 'phone'])->sortable(),
                TextColumn::make('sales_person_list')
                    ->label('Sales Staff')
                    ->badge()
                    ->separator(',')
                    ->searchable(),
                TextColumn::make('items')
                    ->label('Sold Items')
                    ->listWithLineBreaks()
                    ->getStateUsing(function ($record) {
                        return $record->items->map(function ($item) {
                            if ($item->productItem) return $item->productItem->barcode . ' — ' . ($item->productItem->custom_description ?? 'Item');
                            if ($item->repair_id) return "REPAIR: #" . ($item->repair->repair_no ?? 'SVC');
                            return $item->custom_description;
                        })->toArray();
                    })
                    ->limitList(1)->expandableLimitedList()->size('xs')->color('gray'),
                TextColumn::make('payment_method')->label('Method')->badge()->color('gray')->grow(false),
                TextColumn::make('final_total')->label('Total')->money('USD')->weight('bold')->color('success')->alignRight(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, y')
                    // 🚀 THE FIX: Use a closure to grab the dynamic request timezone
                    ->timezone(fn() => config('app.timezone'))
                    ->sortable()
                    ->grow(false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')->label('Payment Type')
                    ->options(['cash' => 'CASH', 'laybuy' => 'LAYBUY', 'visa' => 'VISA']),
                Tables\Filters\TernaryFilter::make('has_trade_in')->label('Trade-Ins')
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => $record->status !== 'completed' || auth()->user()->hasRole('Superadmin')),

                // 🚀 MAIN GROUP (Receipt + Share)
                Tables\Actions\ActionGroup::make([

                    // ===============================
                    // 🖨️ PRINT OPTIONS
                    // ===============================

                    Tables\Actions\Action::make('printStandard')
    ->label('Print Standard Receipt')
    ->icon('heroicon-o-printer')
    ->url(fn(Sale $record) => route('sales.receipt', ['record' => $record, 'type' => 'standard']))
    ->openUrlInNewTab()
    ->extraAttributes([
        // 🚀 This triggers the browser print dialog immediately
        'onclick' => "setTimeout(() => { let win = window.open(this.href, '_blank'); win.onload = function() { win.print(); } }, 100); return false;"
    ]),

                    Tables\Actions\Action::make('printGift')
                        ->label('Print Gift Receipt')
                        ->icon('heroicon-o-gift')
                        ->color('success')
                        ->url(fn(Sale $record) => route('sales.receipt', [
                            'record' => $record,
                            'type' => 'gift'
                        ]))
                        ->openUrlInNewTab(),

                    Tables\Actions\Action::make('printJob')
                        ->label('Print Workshop Card')
                        ->icon('heroicon-o-wrench')
                        ->color('warning')
                        ->url(fn(Sale $record) => route('sales.receipt', [
                            'record' => $record,
                            'type' => 'job'
                        ]))
                        ->openUrlInNewTab(),

                    // ===============================
                    // 📧 EMAIL RECEIPT
                    // ===============================

                    Tables\Actions\Action::make('emailReceipt')
                        ->label('Email to Customer')
                        ->icon('heroicon-o-envelope')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function (Sale $record) {

                            if (!$record->customer || empty($record->customer->email)) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Email Missing')
                                    ->body('Customer has no email address.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $mailable = new \App\Mail\CustomerReceipt($record);
                            $sent = $mailable->sendDirectly();

                            if ($sent) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Receipt Sent')
                                    ->body("Successfully emailed to {$record->customer->email}")
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Email Error')
                                    ->body('Check Laravel logs for SES details.')
                                    ->danger()
                                    ->send();
                            }
                        }),

                    // ===============================
                    // 📱 SMS RECEIPT
                    // ===============================

                    Tables\Actions\Action::make('smsReceipt')
                        ->label('Send via SMS')
                        ->icon('heroicon-o-device-phone-mobile')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Send Receipt via SMS')
                        ->action(function (Sale $record) {

                            // 1. Get Customer Phone
                            $phone = $record->customer->phone ?? null;

                            if (empty($phone)) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Error')
                                    ->body('Customer has no phone number.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // 2. Format Phone
                            $digits = preg_replace('/[^0-9]/', '', $phone);

                            if (\Illuminate\Support\Str::startsWith($digits, '1') && strlen($digits) === 11) {
                                $digits = substr($digits, 1);
                            }

                            $formattedPhone = '+1' . $digits;

                            // 3. Generate Dynamic Link
                            $store = $record->store;

                            $baseUrl = $store && !empty($store->domain_url)
                                ? rtrim($store->domain_url, '/')
                                : config('app.url');

                            $link = $baseUrl . "/receipt/" . $record->id;

                            // 4. Message
                            $storeName = $store->name ?? 'Diamond Square';
                            $message = "Hi {$record->customer->name}, thanks for visiting {$storeName}! View your receipt here: {$link}";

                            // 5. Send SMS
                            try {
                                $sns = new \Aws\Sns\SnsClient([
                                    'version' => 'latest',
                                    'region'  => config('services.sns.region'),
                                    'credentials' => [
                                        'key'    => config('services.sns.key'),
                                        'secret' => config('services.sns.secret'),
                                    ],
                                ]);

                                $sns->publish([
                                    'Message' => $message,
                                    'PhoneNumber' => $formattedPhone,
                                    'MessageAttributes' => [
                                        'OriginationNumber' => [
                                            'DataType' => 'String',
                                            'StringValue' => config('services.sns.sms_from'),
                                        ],
                                    ],
                                ]);

                                \Filament\Notifications\Notification::make()
                                    ->title('SMS Sent')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('SMS Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                ])
                    ->label('Receipt / Share')
                    ->icon('heroicon-m-printer')
                    ->color('gray')
                    ->button()
                    ->outlined(),

                // ===============================
                // 💰 REFUND
                // ===============================

                Tables\Actions\Action::make('refund')
                    ->label('Refund')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn(Sale $record) => $record->status === 'completed')
                    ->url(fn(Sale $record) => \App\Filament\Resources\RefundResource::getUrl('create', [
                        'sale_id' => $record->id
                    ])),

            ])
            ->defaultSort('created_at', 'desc');
    }
    /**
     * Formats resize data and pushes it into the 'notes' column of the sales table
     */
    public static function mapResizeToNotes(Get $get, Set $set): void
    {
        $type = $get('job_type');
        $current = $get('current_size');
        $target = $get('target_size');
        $date = $get('date_required');
        $instr = $get('job_instructions');

        if (!$type) return;

        $parts = [];
        $parts[] = strtoupper($type) . " JOB:";

        if ($type === 'Resize') {
            if ($current) $parts[] = "From {$current}";
            if ($target) $parts[] = "To {$target}";
        }

        if ($date) {
            try {
                $parts[] = "Due: " . \Carbon\Carbon::parse($date)->format('M d, Y');
            } catch (\Exception $e) {
            }
        }

        if ($instr) {
            $parts[] = "Notes: {$instr}";
        }

        $set('notes', implode(' | ', $parts));
    }

// 🚀 CHANGE: Use 'callable|Get' for the first argument
public static function updateTotals(callable|Get $get, callable|Set $set): void
{
    // Create a helper to handle whether $get is the object or a closure
    $getValue = function($path) use ($get) {
        return is_callable($get) ? $get($path) : $get($path);
    };

    // Use $getValue() instead of $get() throughout the function
    $items = $getValue('items') ?? $getValue('../../items') ?? [];
    
    $shipping = floatval($getValue('shipping_charges') ?? $getValue('../../shipping_charges') ?? 0);
    $isShippingTaxed = (bool) ($getValue('shipping_taxed') ?? $getValue('../../shipping_taxed') ?? false);
    
    $itemsTotal = 0;
    $taxableItemsTotal = 0;

    foreach ($items as $item) {
        $qty = intval($item['qty'] ?? 1);
        
        if (!empty($item['sale_price_override'])) {
            $rowTotal = floatval($item['sale_price_override']);
        } else {
            $price = floatval($item['sold_price'] ?? 0);
            $discount = floatval($item['discount_amount'] ?? 0);
            $rowTotal = ($price * $qty) - $discount;
        }

        $itemsTotal += $rowTotal;
        
        if (!($item['is_tax_free'] ?? false)) {
            $taxableItemsTotal += $rowTotal;
        }
    }

    $dbTax = DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? 7.63;
    $taxRate = floatval($dbTax) / 100;

    $taxableBasis = $taxableItemsTotal + ($isShippingTaxed ? $shipping : 0);
    $totalTax = $taxableBasis * $taxRate;

    // Use $getValue here too
    $tradeIn = ($getValue('has_trade_in') == 1 || $getValue('../../has_trade_in') == 1) 
        ? floatval($getValue('trade_in_value') ?? $getValue('../../trade_in_value') ?? 0) 
        : 0;

    // 🚀 Keep $set as is (Filament handles set paths automatically)
    $set('../../subtotal', number_format($itemsTotal, 2, '.', ''));
    $set('../../tax_amount', number_format($totalTax, 2, '.', ''));
    
    $finalTotal = ($itemsTotal + $shipping + $totalTax) - $tradeIn;
    $set('../../final_total', number_format($finalTotal, 2, '.', ''));
    
    $set('subtotal', number_format($itemsTotal, 2, '.', ''));
    $set('tax_amount', number_format($totalTax, 2, '.', ''));
    $set('final_total', number_format($finalTotal, 2, '.', ''));
}

    public static function getPaymentOptions(): array
    {
        $json = DB::table('site_settings')->where('key', 'payment_methods')->value('value');
        $defaultMethods = ['CASH', 'VISA', 'MASTERCARD', 'AMEX', 'LAYBUY'];
        $methods = $json ? json_decode($json, true) : $defaultMethods;
        $options = [];

        foreach ($methods as $method) {
            if (strtoupper($method) === 'LAYBUY') {
                $options['laybuy'] = 'LAYBUY (Installment Plan)';
            } else {
                $options[$method] = $method;
            }
        }
        return $options;
    }
    protected static function totalRow($label, $field)
    {
        return TextInput::make($field)->label($label)->prefix('$')->readOnly()->extraInputAttributes(['class' => 'text-right']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
            'view' => Pages\ViewSale::route('/{record}'),
        ];
    }
}
