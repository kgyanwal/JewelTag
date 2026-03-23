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
use Illuminate\Support\Facades\Session;
use Tapp\FilamentGoogleAutocomplete\Forms\Components\GoogleAutocomplete;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'Quick Sale';
    protected static ?string $recordTitleAttribute = 'invoice_number';

    public static function getGloballySearchableAttributes(): array
    {
        return ['invoice_number', 'customer.name', 'customer.phone', 'customer.last_name'];
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        $customer = $record->customer;
        $name = $customer ? "{$customer->name} {$customer->last_name}" : 'Unknown';
        return "Invoice #{$record->invoice_number} — {$name}";
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'Customer' => $record->customer?->name . ' ' . $record->customer?->last_name,
            'Total'    => '$' . number_format($record->final_total, 2),
            'Status'   => ucfirst($record->status),
            'Date'     => $record->created_at?->format('M d, Y'),
        ];
    }

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
                                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                            if (!$state) return;

                                            $item = ProductItem::find($state);
                                            if (!$item) return;

                                            $currentItems = $get('items') ?? [];
                                            $qty          = $get('current_qty') ?? 1;

                                            $newRow = [
                                                'product_item_id'    => $item->id,
                                                'repair_id'          => null,
                                                'stock_no_display'   => $item->barcode,
                                                'custom_description' => $item->custom_description ?? $item->barcode,
                                                'qty'                => $qty,
                                                'sold_price'         => $item->retail_price,
                                                'sale_price_override'=> $item->retail_price * $qty,
                                                'discount_percent'   => 0,
                                                'discount_amount'    => 0,
                                                'is_tax_free'        => false,
                                            ];

                                            $currentItems[] = $newRow;
                                            $set('items', $currentItems);
                                            $set('current_item_search', null);
                                            $set('current_qty', 1);

                                            self::updateTotals($get, $set);
                                        })->columnSpan(3),

                                    TextInput::make('current_qty')
                                        ->label('Qty')
                                        ->numeric()
                                        ->default(1)
                                        ->dehydrated(false)
                                        ->live(),


                                      \Filament\Forms\Components\Actions::make([
        FormAction::make('add_non_tag_item')
            ->label('+ Non-Tag Item')
            ->color('gray')
            ->outlined()
            ->icon('heroicon-o-plus-circle')
            ->modalHeading('Add Non-Tag Item')
            ->modalWidth('lg')
            ->modalSubmitActionLabel('Add to Bill')
            ->form([
                TextInput::make('description')
                    ->label('Description')
                    ->required()
                    ->placeholder('e.g. Engraving, Cleaning Fee, Labour Charge'),

                \Filament\Forms\Components\Grid::make(2)->schema([
                    TextInput::make('price')
                        ->label('Unit Price')
                        ->numeric()
                        ->prefix('$')
                        ->required()
                        ->default(0),

                    TextInput::make('qty')
                        ->label('Qty')
                        ->numeric()
                        ->required()
                        ->default(1),
                ]),

                \Filament\Forms\Components\Grid::make(2)->schema([
                    TextInput::make('discount_percent')
                        ->label('Discount %')
                        ->numeric()
                        ->suffix('%')
                        ->default(0),

                    \Filament\Forms\Components\Toggle::make('is_tax_free')
                        ->label('Tax Free?')
                        ->default(false)
                        ->inline(false),
                ]),
            ])
            ->action(function (array $data, Get $get, Set $set) {
                $price    = floatval($data['price'] ?? 0);
                $qty      = intval($data['qty'] ?? 1);
                $discPct  = floatval($data['discount_percent'] ?? 0);
                $lineTotal = $price * $qty;
                $discAmt  = $lineTotal * ($discPct / 100);
                $finalPrice = $lineTotal - $discAmt;

                $currentItems   = $get('items') ?? [];
                $currentItems[] = [
                    'product_item_id'    => null,   // ← null = no stock link
                    'repair_id'          => null,
                    'custom_order_id'    => null,
                    'stock_no_display'   => 'NON-TAG',
                    'custom_description' => $data['description'],
                    'qty'                => $qty,
                    'sold_price'         => $price,
                    'sale_price_override'=> $finalPrice,
                    'discount_percent'   => $discPct,
                    'discount_amount'    => $discAmt,
                    'is_tax_free'        => $data['is_tax_free'] ?? false,
                ];

                $set('items', $currentItems);
                self::updateTotals($get, $set);
            }),
    ])->columnSpan(1),  

                                ]),
                                
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
                                        ->rows(2)
                                        ->autosize(false)
                                        ->extraInputAttributes([
                                            'style' => 'max-height:60px; overflow-y:auto; resize:none;'
                                        ])
                                        ->columnSpan(3),

                                    TextInput::make('qty')
                                        ->numeric()
                                        ->default(1)
                                        ->required()
                                        ->live(onBlur: true)
                                        ->columnSpan(1)
                                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                            if ($pid = $get('product_item_id')) {
                                                $productItem = ProductItem::find($pid);
                                                if ($productItem && $state > $productItem->qty) {
                                                    $set('qty', $productItem->qty);
                                                    $state = $productItem->qty;
                                                }
                                            }

                                            $price   = floatval($get('sold_price'));
                                            $percent = floatval($get('discount_percent'));
                                            $lineTotal = $price * $state;
                                            $set('discount_amount', number_format($lineTotal * ($percent / 100), 2, '.', ''));

                                            self::updateTotals($get, $set);
                                        }),

                                    TextInput::make('sold_price')
                                        ->label('Price')
                                        ->numeric()
                                        ->live(onBlur: true)
                                        ->columnSpan(2)
                                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                            $qty     = intval($get('qty') ?? 1);
                                            $percent = floatval($get('discount_percent') ?? 0);

                                            $lineTotalBeforeDiscount = floatval($state) * $qty;
                                            $newDiscountAmount       = $lineTotalBeforeDiscount * ($percent / 100);

                                            $set('discount_amount', number_format($newDiscountAmount, 2, '.', ''));
                                            self::updateTotals($get, $set);
                                        }),

                                    TextInput::make('sale_price_override')
                                        ->label('Sale Price')
                                        ->columnSpan(2)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                            $unitPrice    = floatval($get('sold_price') ?? 0);
                                            $qty          = intval($get('qty') ?? 1);
                                            $totalOriginal = $unitPrice * $qty;
                                            $newFinalTotal = floatval($state ?? 0);

                                            if ($totalOriginal > 0) {
                                                $diff    = $totalOriginal - $newFinalTotal;
                                                $percent = ($diff / $totalOriginal) * 100;

                                                $set('discount_amount', number_format($diff, 2, '.', ''));
                                                $set('discount_percent', number_format($percent, 2, '.', ''));
                                            }
                                            static::updateTotals($get, $set);
                                        }),

                                    TextInput::make('discount_percent')
                                        ->label('Disc %')
                                        ->numeric()
                                        ->suffix('%')
                                        ->default(0)
                                        ->live(onBlur: true)
                                        ->columnSpan(2)
                                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                            $price     = floatval($get('sold_price') ?? 0);
                                            $qty       = intval($get('qty') ?? 1);
                                            $lineTotal = $price * $qty;
                                            $percent   = floatval($state ?? 0);

                                            $discountAmount = $lineTotal * ($percent / 100);
                                            $finalPrice     = $lineTotal - $discountAmount;

                                            $set('discount_amount', number_format($discountAmount, 2, '.', ''));
                                            $set('sale_price_override', number_format($finalPrice, 2, '.', ''));

                                            static::updateTotals($get, $set);
                                        }),

                                    TextInput::make('discount_amount')
                                        ->label('Disc $')
                                        ->numeric()
                                        ->default(0)
                                        ->prefix('$')
                                        ->live(onBlur: true)
                                        ->columnSpan(2)
                                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                            $price          = floatval($get('sold_price') ?? 0);
                                            $qty            = intval($get('qty') ?? 1);
                                            $lineTotal      = $price * $qty;
                                            $discountAmount = floatval($state ?? 0);

                                            if ($lineTotal > 0) {
                                                $percent = ($discountAmount / $lineTotal) * 100;
                                                $set('discount_percent', number_format($percent, 2, '.', ''));
                                            }

                                            $set('sale_price_override', number_format($lineTotal - $discountAmount, 2, '.', ''));
                                            static::updateTotals($get, $set);
                                        }),

                                    Checkbox::make('is_tax_free')
                                        ->label('No Tax')
                                        ->columnSpan(1)
                                        ->default(false)
                                        ->dehydrated(true)
                                        ->live()
                                        ->hint(fn($state) => $state ? 'There is no tax involved here' : null)
                                        ->hintColor('warning')
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
                                            'Resize'        => 'Resize',
                                            'Solder'        => 'Solder / Weld',
                                            'Bail Change'   => 'Bail Change',
                                            'Shorten'       => 'Shortening',
                                            'Stone Setting' => 'Stone Setting',
                                            'Polishing'     => 'Polishing / Rhodium',
                                        ])
                                        ->placeholder('Select Job Type')
                                        ->live()
                                        ->afterStateUpdated(fn(Get $get, Set $set) => self::mapResizeToNotes($get, $set)),

                                    Select::make('metal_type')
                                        ->label('Metal')
                                        ->options([
                                            '10k'      => '10k Gold',
                                            '14k'      => '14k Gold',
                                            '18k'      => '18k Gold',
                                            'Platinum' => 'Platinum',
                                            'Silver'   => 'Sterling Silver',
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

                                Hidden::make('notes'),
                            ]),

                        Section::make('Trade-In Details')
                            ->schema([
                                Select::make('has_trade_in')
                                    ->label('Is there a Trade-In?')
                                    ->options([1 => 'Yes', 0 => 'No'])
                                    ->default(0)
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
                                            ->required(fn(Get $get) => $get('has_trade_in') == 1)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotals($get, $set)),

                                        TextInput::make('trade_in_receipt_no')
                                            ->label('Trade-In Tracking #')
                                            ->default(fn() => 'TRD-' . date('Ymd-His'))
                                            ->readOnly(),

                                        Forms\Components\Textarea::make('trade_in_description')
                                            ->label('Item Description')
                                            ->placeholder('e.g. 14k White Gold Diamond Band')
                                            ->required(fn(Get $get) => $get('has_trade_in') == 1)
                                            ->columnSpanFull()
                                            ->rows(2),
                                    ]),
                            ]),

                        Section::make('Shipping & Handling')->schema([
                            Grid::make(4)->schema([
                                TextInput::make('shipping_charges')
                                    ->label('Charges')->numeric()->prefix('$')->live(onBlur: true)->default(0)->required()
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotals($get, $set)),
                                Checkbox::make('shipping_taxed')->label('Taxed?')->default(false),
                                Select::make('carrier')->options(['No carrier' => 'No carrier', 'UPS' => 'UPS', 'FedEx' => 'FedEx'])->default('No carrier'),
                                TextInput::make('tracking_number')->label('Tracking')->nullable(),
                            ]),

                            Grid::make(4)->schema([
                                Select::make('has_warranty')
                                    ->label('Include Warranty?')
                                    ->options([0 => 'No', 1 => 'Yes'])
                                    ->default(0)
                                    ->live(),

                                Select::make('warranty_period')
                                    ->label('Warranty Time')
                                    ->visible(fn(Get $get) => $get('has_warranty') == 1)
                                    ->required(fn(Get $get) => $get('has_warranty') == 1)
                                    ->options(function () {
                                        $json    = DB::table('site_settings')->where('key', 'warranty_options')->value('value');
                                        $options = $json ? json_decode($json, true) : ['1 Year', '2 Years', 'Lifetime'];
                                        return array_combine($options, $options);
                                    }),

                                Forms\Components\DatePicker::make('follow_up_date')
                                    ->label('Follow Up (2 Weeks)')
                                    ->default(now()->addWeeks(2))
                                    ->native(false)
                                    ->displayFormat('M d, Y'),

                                Forms\Components\DatePicker::make('second_follow_up_date')
                                    ->label('Follow Up Second')
                                    ->default(now()->addMonths(6))
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
                                                                DatePicker::make('dob')->rule('before_or_equal:today')->label('Birth Date'),
                                                                Forms\Components\DatePicker::make('wedding_anniversary')->label('Wedding Date'),
                                                            ]),
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
                                                                            TextInput::make('street')->label('Street Address')->extraInputAttributes(['data-google-field' => '{street_number} {route}']),
                                                                            TextInput::make('address_line_2')->label('Address 2 / Apt / Suite')->extraInputAttributes(['data-google-field' => 'subpremise']),
                                                                            TextInput::make('city')->label('City')->extraInputAttributes(['data-google-field' => 'locality', 'data-google-value' => 'short_name']),
                                                                            TextInput::make('state')->label('State')->extraInputAttributes(['data-google-field' => 'administrative_area_level_1'])->columnSpan(1),
                                                                            TextInput::make('postcode')->label('Zip Code')->extraInputAttributes(['data-google-field' => 'postal_code'])->columnSpan(1),
                                                                        ]),
                                                                    Forms\Components\Select::make('country')->label('Country')->default('United States')->searchable(),
                                                                ]),
                                                        ]),
                                                ]),
                                            Forms\Components\Hidden::make('customer_no')->default(fn() => 'CUST-' . strtoupper(Str::random(6))),
                                        ])
                                        ->createOptionUsing(function (array $data) {
                                            return \App\Models\Customer::create($data)->id;
                                        }),
                                ]),

                                Select::make('sales_person_list')
                                    ->label('Sales Staff')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->options(fn() => User::pluck('name', 'name')->toArray())
                                    ->default(fn() => [Session::get('active_staff_name') ?? auth()->user()->name])
                                    ->required(),
                            ]),

                        Section::make('Payment & Status')->schema([
                            Toggle::make('is_split_payment')
                                ->label('Enable Split Payment')
                                ->onColor('warning')
                                ->live()
                                ->columnSpanFull(),

                            // ── STANDARD PAYMENT ──────────────────────────────────
                            Select::make('payment_method')
                                ->label('Payment Method')
                                ->options(self::getPaymentOptions())
                                ->default('cash')
                                ->required(fn(Get $get) => !$get('is_split_payment'))
                                ->visible(fn(Get $get) => !$get('is_split_payment'))
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    self::updateTotals($get, $set);
                                }),

                            // Big blue display of total to collect
                            TextInput::make('display_total_due')
                                ->label('Total Amount to Collect')
                                ->prefix('$')
                                ->readOnly()
                                ->extraInputAttributes(['style' => 'font-size: 1.5rem; font-weight: 900; color: #0284c7; background-color: #f0f9ff;'])
                                ->visible(fn(Get $get) => !$get('is_split_payment'))
                                ->dehydrated(false),

                            // ✅ Amount received from customer — saves to amount_paid column
                            TextInput::make('amount_paid')
                                ->label('Amount Received')
                                ->numeric()
                                ->prefix('$')
                                ->default(0)
                                ->live(onBlur: true)
                                ->visible(fn(Get $get) => !$get('is_split_payment'))
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    self::updateTotals($get, $set);
                                    self::syncStatus($get, $set);
                                })
                                ->helperText('What the customer physically handed over'),

                            // Change to give back — read only, auto-calculated
                            TextInput::make('change_given')
                                ->label('Change Given')
                                ->prefix('$')
                                ->readOnly()
                                ->default('0.00')
                                ->dehydrated(false)
                                ->live()
                                ->visible(fn(Get $get) => !$get('is_split_payment'))
                                ->extraInputAttributes(['class' => 'text-right font-bold text-green-600 text-xl']),

                            // ── SPLIT PAYMENT ─────────────────────────────────────
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
                                ->defaultItems(2)
                                ->maxItems(6)
                                ->addActionLabel('Add Another Payment Method')
                                ->reorderable(false)
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    self::updateTotals($get, $set);
                                    self::syncStatus($get, $set);
                                }),

                            Placeholder::make('split_calc')
                                ->label('Remaining Balance')
                                ->content(function (Get $get) {
                                    $total    = (float) $get('final_total');
                                    $payments = $get('split_payments') ?? [];
                                    $sum      = collect($payments)->sum(fn($p) => (float)($p['amount'] ?? 0));
                                    $remaining = $total - $sum;

                                    if ($remaining < 0) {
                                        return new HtmlString("<span class='text-danger-600 font-bold'>Overpaid by: $" . number_format(abs($remaining), 2) . "</span>");
                                    }
                                    $color = round($remaining, 2) == 0 ? 'text-success-600' : 'text-primary-600';
                                    return new HtmlString("<span class='{$color} font-bold text-xl'>$" . number_format($remaining, 2) . "</span>");
                                })
                                ->visible(fn(Get $get) => $get('is_split_payment')),

                            Select::make('status')
                                ->label('Sale Status')
                                ->options([
                                    'completed'  => 'Completed',
                                    'inprogress' => 'In Progress',
                                    'pending'    => 'Pending',
                                ])
                                ->default('inprogress')
                                ->required()
                                ->live(),
                        ]),

                        Section::make('Financial Summary')->schema([
                            self::totalRow('TAX TOTAL', 'tax_amount'),
                            self::totalRow('SUBTOTAL', 'subtotal'),
                            Placeholder::make('balance_due_display')
                                ->label('BALANCE DUE')
                                ->content(function (Get $get) {
                                    $total  = floatval($get('final_total') ?? 0);
                                    $method = $get('payment_method') ?? '';

                                    if (!$get('is_split_payment')) {
                                        $tendered = floatval($get('amount_paid') ?? 0);

                                        // ✅ Fully paid
                                        if ($tendered > 0 && $tendered >= $total) {
                                            $change = $tendered - $total;
                                            return new HtmlString("
                                                <span style='color:#16a34a; font-weight:700; font-size:1.875rem;'>\$0.00</span>
                                                <div style='color:#16a34a; font-size:0.75rem; margin-top:4px;'>✅ Fully Paid via " . strtoupper($method) . "</div>
                                                " . ($change > 0 ? "<div style='color:#2563eb; font-size:0.875rem; font-weight:700; margin-top:8px;'>💵 Change Due: \$" . number_format($change, 2) . "</div>" : "") . "
                                            ");
                                        }

                                        // 🔴 Partial payment entered
                                        if ($tendered > 0 && $tendered < $total) {
                                            $remaining = $total - $tendered;
                                            return new HtmlString("
                                                <span style='color:#dc2626; font-weight:700; font-size:1.875rem;'>\$" . number_format($remaining, 2) . "</span>
                                                <div style='color:#dc2626; font-size:0.75rem; margin-top:4px;'>⚠️ Still owed — collect before completing</div>
                                            ");
                                        }

                                        // 🔴 Method selected but nothing entered yet
                                        if (!empty($method)) {
                                            return new HtmlString("
                                                <span style='color:#dc2626; font-weight:700; font-size:1.875rem;'>\$" . number_format($total, 2) . "</span>
                                                <div style='color:#dc2626; font-size:0.75rem; margin-top:4px;'>Enter Amount Received above</div>
                                            ");
                                        }

                                        // 🔴 Nothing selected
                                        return new HtmlString("
                                            <span style='color:#dc2626; font-weight:700; font-size:1.875rem;'>\$" . number_format($total, 2) . "</span>
                                            <div style='color:#dc2626; font-size:0.75rem; margin-top:4px;'>Select a payment method</div>
                                        ");
                                    }

                                    // ── SPLIT ──────────────────────────────────────
                                    $payments = $get('split_payments') ?? [];
                                    $paidSum  = collect($payments)->sum(fn($p) => (float)($p['amount'] ?? 0));
                                    $remaining = $total - $paidSum;

                                    if (round($remaining, 2) <= 0) {
                                        $overpaid = abs($remaining);
                                        return new HtmlString("
                                            <span style='color:#16a34a; font-weight:700; font-size:1.875rem;'>\$0.00</span>
                                            <div style='color:#16a34a; font-size:0.75rem; margin-top:4px;'>✅ Fully Paid</div>
                                            " . ($overpaid > 0 ? "<div style='color:#2563eb; font-size:0.875rem; font-weight:700; margin-top:8px;'>💵 Change Due: \$" . number_format($overpaid, 2) . "</div>" : "") . "
                                        ");
                                    }

                                    return new HtmlString("
                                        <span style='color:#dc2626; font-weight:700; font-size:1.875rem;'>\$" . number_format($remaining, 2) . "</span>
                                        <div style='color:#dc2626; font-size:0.75rem; margin-top:4px;'>Remaining balance</div>
                                    ");
                                })
                                ->live(),
                        ]),
                    ]),
                ]),

                Hidden::make('final_total'),
                Hidden::make('subtotal'),
                Hidden::make('tax_amount'),
                Hidden::make('store_id')->default(fn() => auth()->user()->store_id ?? Store::first()?->id ?? 1),
                Hidden::make('invoice_number')->dehydrated(true),
                Hidden::make('change_given')->dehydrated(false),
                Hidden::make('balance_due'),
                Hidden::make('custom_order_id')->dehydrated(false),
                Hidden::make('repair_id')->dehydrated(false),
            ])
            ->statePath('data');
    }

    public function recordNewPayment(Sale $sale, $amount, $method)
    {
        \App\Models\Payment::create([
            'sale_id' => $sale->id,
            'amount'  => $amount,
            'method'  => $method,
            'paid_at' => now(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->where('status', '!=', 'void'))
            ->columns([
                TextColumn::make('invoice_number')->label('Inv #')->searchable()->sortable()->grow(false),
                TextColumn::make('customer.name')->label('Customer')->searchable(['name', 'phone'])->sortable(),
                TextColumn::make('sales_person_list')->label('Sales Staff')->badge()->separator(',')->searchable(),
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

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'completed'          => 'success',
                        'refunded'           => 'danger',
                        'partially_refunded' => 'warning',
                        'cancelled'          => 'gray',
                        default              => 'gray',
                    })
                    ->formatStateUsing(fn(string $state) => ucfirst($state)),

                TextColumn::make('payment_method')
                    ->label('Method')
                    ->formatStateUsing(fn($record) => $record->is_split_payment ? 'SPLIT' : $record->payment_method)
                    ->badge()
                    ->color(fn($state) => $state === 'SPLIT' ? 'warning' : 'gray'),

                TextColumn::make('payment_status_summary')
                    ->label('PAYMENT SUMMARY')
                    ->getStateUsing(function ($record) {
                        $isCustomDeposit = $record->has_trade_in && str_contains($record->trade_in_description ?? '', 'Prior Deposit');

                        $total = floatval($record->final_total);
                        if ($isCustomDeposit) $total += floatval($record->trade_in_value);

                        $paid = floatval($record->payments->sum('amount'));

                        if ($paid == 0 && floatval($record->amount_paid) > 0) {
                            $paid = floatval($record->amount_paid);
                        }

                        if ($isCustomDeposit) $paid += floatval($record->trade_in_value);

                        if ($record->status === 'completed' && $paid < $total) {
                            $paid = $total;
                        }

                        $balance = max(0, $total - $paid);

                        $html  = "<div class='text-xs text-gray-500'>Bill Total: $" . number_format($total, 2) . "</div>";
                        $html .= "<div class='text-sm font-bold text-success-600'>Paid: $" . number_format($paid, 2) . "</div>";

                        if ($balance > 0.01) {
                            $html .= "<div class='text-sm font-bold text-danger-600'>Balance: $" . number_format($balance, 2) . "</div>";
                        } else {
                            $html .= "<div class='text-[10px] bg-success-100 text-success-700 px-1 rounded inline-block uppercase font-bold mt-1'>Fully Paid</div>";
                        }

                        return new HtmlString($html);
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, y')
                    ->timezone(fn() => config('app.timezone'))
                    ->sortable()
                    ->grow(false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')->label('Payment Type')
                    ->options(['cash' => 'CASH', 'laybuy' => 'LAYBUY', 'visa' => 'VISA']),
                Tables\Filters\TernaryFilter::make('has_trade_in')->label('Trade-Ins'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('printStandard')
                        ->label('Print Standard Receipt')
                        ->icon('heroicon-o-printer')
                        ->url(fn(Sale $record) => route('sales.receipt', ['record' => $record, 'type' => 'standard']))
                        ->openUrlInNewTab()
                        ->extraAttributes([
                            'onclick' => "setTimeout(() => { let win = window.open(this.href, '_blank'); win.onload = function() { win.print(); } }, 100); return false;"
                        ]),

                    Tables\Actions\Action::make('printGift')
                        ->label('Print Gift Receipt')
                        ->icon('heroicon-o-gift')
                        ->color('success')
                        ->url(fn(Sale $record) => route('sales.receipt', ['record' => $record, 'type' => 'gift']))
                        ->openUrlInNewTab(),

                    Tables\Actions\Action::make('printJob')
                        ->label('Print Workshop Card')
                        ->icon('heroicon-o-wrench')
                        ->color('warning')
                        ->url(fn(Sale $record) => route('sales.receipt', ['record' => $record, 'type' => 'job']))
                        ->openUrlInNewTab(),

                    Tables\Actions\Action::make('emailReceipt')
                        ->label('Email to Customer')
                        ->icon('heroicon-o-envelope')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function (Sale $record) {
                            if (!$record->customer || empty($record->customer->email)) {
                                \Filament\Notifications\Notification::make()->title('Email Missing')->body('Customer has no email address.')->danger()->send();
                                return;
                            }

                            $mailable = new \App\Mail\CustomerReceipt($record);
                            $sent     = $mailable->sendDirectly();

                            if ($sent) {
                                \Filament\Notifications\Notification::make()->title('Receipt Sent')->body("Successfully emailed to {$record->customer->email}")->success()->send();
                            } else {
                                \Filament\Notifications\Notification::make()->title('Email Error')->body('Check Laravel logs for SES details.')->danger()->send();
                            }
                        }),

                    Tables\Actions\Action::make('smsReceipt')
                        ->label('Send via SMS')
                        ->icon('heroicon-o-device-phone-mobile')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Send Receipt via SMS')
                        ->action(function (Sale $record) {
                            $phone = $record->customer->phone ?? null;

                            if (empty($phone)) {
                                \Filament\Notifications\Notification::make()->title('Error')->body('Customer has no phone number.')->danger()->send();
                                return;
                            }

                            $digits = preg_replace('/[^0-9]/', '', $phone);
                            if (\Illuminate\Support\Str::startsWith($digits, '1') && strlen($digits) === 11) {
                                $digits = substr($digits, 1);
                            }
                            $formattedPhone = '+1' . $digits;

                            $store     = $record->store;
                            $baseUrl   = $store && !empty($store->domain_url) ? rtrim($store->domain_url, '/') : config('app.url');
                            $link      = $baseUrl . "/receipt/" . $record->id;
                            $storeName = $store->name ?? 'Diamond Square';
                            $message   = "Hi {$record->customer->name}, thanks for visiting {$storeName}! View your receipt here: {$link}";

                            try {
                                $settings = \Illuminate\Support\Facades\DB::table('site_settings')->pluck('value', 'key');

                                $sns = new \Aws\Sns\SnsClient([
                                    'version'     => 'latest',
                                    'region'      => $settings['aws_sms_default_region'] ?? config('services.sns.region'),
                                    'credentials' => [
                                        'key'    => $settings['aws_sms_access_key_id'] ?? config('services.sns.key'),
                                        'secret' => $settings['aws_sms_secret_access_key'] ?? config('services.sns.secret'),
                                    ],
                                ]);

                                $sns->publish([
                                    'Message'          => $message,
                                    'PhoneNumber'      => $formattedPhone,
                                    'MessageAttributes' => [
                                        'OriginationNumber' => [
                                            'DataType'    => 'String',
                                            'StringValue' => $settings['aws_sns_sms_from'] ?? config('services.sns.sms_from'),
                                        ],
                                    ],
                                ]);

                                \Filament\Notifications\Notification::make()->title('SMS Sent')->success()->send();
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()->title('SMS Failed')->body($e->getMessage())->danger()->send();
                            }
                        }),
                ])
                    ->label('Receipt / Share')
                    ->icon('heroicon-m-printer')
                    ->color('gray')
                    ->button()
                    ->outlined(),

                Tables\Actions\Action::make('refund')
                    ->label('Refund')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn(Sale $record) => $record->status === 'completed')
                    ->url(fn(Sale $record) => \App\Filament\Resources\RefundResource::getUrl('create', ['sale_id' => $record->id])),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function mapResizeToNotes(Get $get, Set $set): void
    {
        $type    = $get('job_type');
        $current = $get('current_size');
        $target  = $get('target_size');
        $date    = $get('date_required');
        $instr   = $get('job_instructions');

        if (!$type) return;

        $parts   = [];
        $parts[] = strtoupper($type) . " JOB:";

        if ($type === 'Resize') {
            if ($current) $parts[] = "From {$current}";
            if ($target)  $parts[] = "To {$target}";
        }

        if ($date) {
            try {
                $parts[] = "Due: " . \Carbon\Carbon::parse($date)->format('M d, Y');
            } catch (\Exception $e) {}
        }

        if ($instr) $parts[] = "Notes: {$instr}";

        $set('notes', implode(' | ', $parts));
    }

    public static function updateTotals(callable|Get $get, callable|Set $set): void
    {
        $items           = $get('items') ?? [];
        $shipping        = floatval($get('shipping_charges') ?? 0);
        $isShippingTaxed = (bool) ($get('shipping_taxed') ?? false);
        $tradeIn         = ($get('has_trade_in') == 1) ? floatval($get('trade_in_value') ?? 0) : 0;

        $itemsSubtotal = 0;
        $taxableBasis  = 0;

        foreach ($items as $item) {
            $qty = intval($item['qty'] ?? 1);

            if (!empty($item['sale_price_override'])) {
                $rowTotal = floatval($item['sale_price_override']);
            } else {
                $price    = floatval($item['sold_price'] ?? 0);
                $discount = floatval($item['discount_amount'] ?? 0);
                $rowTotal = ($price * $qty) - $discount;
            }

            $itemsSubtotal += $rowTotal;

            if (!($item['is_tax_free'] ?? false)) {
                $taxableBasis += $rowTotal;
            }
        }

        $dbTax   = DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? 7.63;
        $taxRate = floatval($dbTax) / 100;

        if ($isShippingTaxed) $taxableBasis += $shipping;

        $totalTax   = $taxableBasis * $taxRate;
        $grandTotal = ($itemsSubtotal + $shipping + $totalTax) - $tradeIn;

        $set('subtotal',    number_format($itemsSubtotal, 2, '.', ''));
        $set('tax_amount',  number_format($totalTax,      2, '.', ''));
        $set('final_total', number_format($grandTotal,    2, '.', ''));

        // ── AUTO-FILL LOGIC ───────────────────────────────────────────────────
        $isSplit = $get('is_split_payment');

        if ($isSplit) {
            $payments   = $get('split_payments') ?? [];
            $amountPaid = collect($payments)->sum(fn($p) => (float)($p['amount'] ?? 0));
        } else {
            $amountPaid = floatval($get('amount_paid') ?? 0);

            // ✅ Auto-fill with full total only if amount_paid is genuinely 0 and untouched
            // Works for ALL payment methods — no hardcoded method name checks
            if ($amountPaid == 0 && !empty($get('payment_method'))) {
                $set('amount_paid', number_format($grandTotal, 2, '.', ''));
                $amountPaid = $grandTotal;
            }
        }

        $changeGiven = max(0, $amountPaid - $grandTotal);
        $balanceDue  = max(0, $grandTotal - $amountPaid);

        $set('change_given',      number_format($changeGiven, 2, '.', ''));
        $set('balance_due',       number_format($balanceDue,  2, '.', ''));
        $set('display_total_due', number_format($grandTotal,  2, '.', ''));

        self::syncStatus($get, $set);
    }

    public static function syncStatus(callable|Get $get, callable|Set $set): void
    {
        $total   = floatval($get('final_total') ?? 0);
        $isSplit = $get('is_split_payment');

        if ($isSplit) {
            $payments  = $get('split_payments') ?? [];
            $paidSum   = collect($payments)->sum(fn($p) => (float)($p['amount'] ?? 0));
            $fullyPaid = round($paidSum, 2) >= round($total, 2);
        } else {
            $tendered  = floatval($get('amount_paid') ?? 0);
            // ✅ Simple: if amount_paid >= total, fully paid — no method name assumptions
            $fullyPaid = $tendered > 0 && round($tendered, 2) >= round($total, 2);
        }

        // ✅ Never downgrade an already completed sale
        $currentStatus = $get('status');
        if ($currentStatus !== 'completed') {
            $set('status', $fullyPaid ? 'completed' : 'inprogress');
        }
    }

    public static function getPaymentOptions(): array
    {
        $json           = DB::table('site_settings')->where('key', 'payment_methods')->value('value');
        $defaultMethods = ['CASH', 'VISA', 'MASTERCARD', 'AMEX', 'LAYBUY'];
        $methods        = $json ? json_decode($json, true) : $defaultMethods;
        $options        = [];

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
            'index'  => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit'   => Pages\EditSale::route('/{record}/edit'),
            'view'   => Pages\ViewSale::route('/{record}'),
        ];
    }
}