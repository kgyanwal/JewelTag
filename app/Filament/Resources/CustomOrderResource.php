<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomOrderResource\Pages;
use App\Models\CustomOrder;
use App\Models\ProductItem;
use App\Models\User;
use App\Models\Sale;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\{Section, Grid, Group, Select, TextInput, Textarea, FileUpload, DatePicker, Toggle, Placeholder, Repeater, Hidden, DateTimePicker};
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Notifications\Notification;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Aws\Sns\SnsClient;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class CustomOrderResource extends Resource
{
    protected static ?string $model = CustomOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'Custom Orders';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(12)->schema([

                // ── LEFT COLUMN ───────────────────────────────────────
                Group::make()->columnSpan(['lg' => 8])->schema([

                    Section::make('Customer & Assignment')
                        ->description('Assign this custom piece to a customer and sales rep.')
                        ->icon('heroicon-o-user-group')
                        ->schema([
                            Grid::make(2)->schema([
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
                                                    ]),
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
                                                                ->rule('regex:/^[0-9]{10}$/'),
                                                            Forms\Components\TextInput::make('email')->label('Email')->email(),
                                                        ]),
                                                        Forms\Components\Section::make('Customer Address')
                                                            ->description('Search for an address to automatically fill the fields below.')
                                                            ->columns(2)
                                                            ->collapsible()
                                                            ->schema([
                                                                \Tapp\FilamentGoogleAutocomplete\Forms\Components\GoogleAutocomplete::make('address_search')
                                                                    ->label('Search Address')
                                                                    ->countries(['US'])
                                                                    ->columnSpanFull()
                                                                    ->withFields([
                                                                        TextInput::make('street')->label('Street Address')->extraInputAttributes(['data-google-field' => '{street_number} {route}']),
                                                                        TextInput::make('city')->label('City')->extraInputAttributes(['data-google-field' => 'locality']),
                                                                        TextInput::make('state')->label('State')->extraInputAttributes(['data-google-field' => 'administrative_area_level_1']),
                                                                        TextInput::make('postcode')->label('Zip Code')->extraInputAttributes(['data-google-field' => 'postal_code']),
                                                                    ]),
                                                            ]),
                                                    ]),
                                            ]),
                                        Forms\Components\Hidden::make('customer_no')->default(fn() => 'CUST-' . strtoupper(Str::random(6))),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        return \App\Models\Customer::create($data)->id;
                                    }),

                                Select::make('staff_id')
                                    ->label('Sales Person')
                                    ->options(User::pluck('name', 'id'))
                                    ->default(fn() => auth()->id())
                                    ->searchable()
                                    ->prefixIcon('heroicon-o-identification')
                                    ->required(),
                            ]),
                        ]),

                    // ── ORDER TYPE ────────────────────────────────────
                    Section::make('Order Type')
                        ->icon('heroicon-o-tag')
                        ->schema([
                            Select::make('order_type')
                                ->label('What kind of order is this?')
                                ->options([
                                    'custom'       => 'Custom Made Item (new from scratch)',
                                    'stock_modify' => 'Modify Existing Stock Item',
                                ])
                                ->default('custom')
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn(Set $set) => $set('items', [])),
                        ]),

                    // ── ORDER ITEMS REPEATER ──────────────────────────
                    Section::make('Order Items')
                        ->description('Add one or more items to this order.')
                        ->icon('heroicon-o-sparkles')
                        ->schema([
                            Repeater::make('items')
                                ->label('')
                                ->schema([

                                    // Stock selector — only for stock_modify
                                    Select::make('stock_item_id')
                                        ->label('Select Stock Item to Modify')
                                        ->placeholder('Search by barcode or description...')
                                        ->options(
                                            fn() => ProductItem::whereIn('status', ['in_stock', 'sold'])
                                                ->get()
                                                ->mapWithKeys(fn($i) => [
                                                    $i->id => "{$i->barcode} — " . Str::limit($i->custom_description, 40)
                                                ])
                                        )
                                        ->searchable()
                                        ->live()
                                        ->afterStateUpdated(function ($state, Set $set) {
                                            if (!$state) return;
                                            $item = ProductItem::find($state);
                                            if (!$item) return;
                                            $set('product_name',   $item->custom_description ?? $item->barcode);
                                            $set('metal_type',     $item->metal_type);
                                            $set('metal_weight',   $item->metal_weight);
                                            $set('diamond_weight', $item->diamond_weight);
                                            $set('size',           $item->size);
                                            $set('quoted_price',   $item->retail_price);
                                        })
                                        ->visible(
                                            fn(Get $get, $livewire) =>
                                            data_get($livewire->data, 'order_type') === 'stock_modify'
                                        )
                                        ->columnSpanFull(),

                                    Grid::make(2)->schema([
                                        Select::make('product_name')
                                            ->label('Product / Jewelry Type')
                                            ->placeholder('e.g. Diamond Tennis Bracelet')
                                            ->options(
                                                fn() => CustomOrder::distinct()
                                                    ->whereNotNull('product_name')
                                                    ->pluck('product_name', 'product_name')
                                            )
                                            ->searchable()
                                            ->createOptionForm([
                                                TextInput::make('new_product_name')->required()->label('New Product Name')
                                            ])
                                            ->createOptionUsing(fn(array $data) => $data['new_product_name'])
                                            ->required(),

                                        Select::make('metal_type')
                                            ->label('Metal Karat/Type')
                                            ->options([
                                                '10k'      => '10k Gold',
                                                '14k'      => '14k Gold',
                                                '18k'      => '18k Gold',
                                                'platinum' => 'Platinum',
                                                'silver'   => 'Silver',
                                            ])
                                            ->required(),
                                    ]),

                                    Grid::make(3)->schema([
                                        TextInput::make('metal_weight')
                                            ->label('Metal Wt. (g)')
                                            ->numeric()
                                            ->placeholder('e.g. 4.5'),

                                        TextInput::make('diamond_weight')
                                            ->label('Diamond (CTW)')
                                            ->numeric()
                                            ->placeholder('e.g. 1.25'),

                                        TextInput::make('size')
                                            ->label('Size')
                                            ->placeholder('e.g. 6.5'),
                                    ]),

                                    // ── ITEM PRICE — full width, prominent ───────────────────────
                                    TextInput::make('quoted_price')
                                        ->label('Item Price')
                                        ->numeric()
                                        ->prefix('$')
                                        ->required()
                                        ->live(onBlur: true)
                                        ->columnSpanFull()
                                        ->helperText('Enter the agreed price for this item')
                                        ->extraInputAttributes([
                                            'class' => 'font-black text-2xl text-success-700',
                                            'style' => 'font-size:1.5rem;height:3rem;border:2px solid #10b981;background:#f0fdf4;',
                                        ]),

                                    Textarea::make('design_notes')
                                        ->label('Design Notes / Instructions')
                                        ->placeholder('Describe stone placements, engraving, specific styles...')
                                        ->rows(3)
                                        ->columnSpanFull(),

                                    TextInput::make('reference_image')
                                        ->label('Reference Image URL')
                                        ->placeholder('https://...')
                                        ->url()
                                        ->columnSpanFull(),

                                    Toggle::make('is_tax_free')
                                        ->label('Tax Free Item?')
                                        ->default(false)
                                        ->inline(false),
                                ])
                                ->defaultItems(1)
                                ->addActionLabel('+ Add Another Item')
                                ->itemLabel(
                                    fn(array $state): ?string => ($state['product_name'] ?? 'New Item') .
                                        (!empty($state['quoted_price']) ? ' — $' . number_format((float)$state['quoted_price'], 2) : '')
                                )
                                ->collapsible()
                                ->cloneable()
                                ->reorderable(true)
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    $total = collect($get('items') ?? [])
                                        ->sum(fn($i) => (float)($i['quoted_price'] ?? 0));
                                    $set('quoted_price', number_format($total, 2, '.', ''));
                                    self::calculateBalance($get, $set);
                                }),
                        ]),

                    // ── VENDOR ────────────────────────────────────────
                    Section::make('Vendor & Scheduling')
                        ->description('Track external production and deadlines.')
                        ->icon('heroicon-o-calendar-days')
                        ->collapsible()
                        ->collapsed()
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('vendor_name')
                                    ->label('Vendor Name')
                                    ->prefixIcon('heroicon-o-building-storefront')
                                    ->placeholder('Who is making this?'),

                                TextInput::make('vendor_info')
                                    ->label('Vendor Contact/Info')
                                    ->prefixIcon('heroicon-o-phone'),
                            ]),

                            Grid::make(3)->schema([
                                DatePicker::make('due_date')->label('Vendor Due Date')->native(false),
                                DatePicker::make('expected_delivery_date')->label('Cust. Delivery Date')->native(false),
                                DatePicker::make('follow_up_date')->label('Follow Up Date')->native(false),
                            ]),
                        ]),
                ]),

                // ── RIGHT COLUMN ──────────────────────────────────────
                Group::make()->columnSpan(['lg' => 4])->schema([

                    Section::make('Financials & Status')
                        ->icon('heroicon-o-banknotes')
                        ->schema([

                            TextInput::make('budget')
                                ->label('Customer Budget')
                                ->numeric()
                                ->prefix('$'),

                            TextInput::make('quoted_price')
                                ->label('Total Quoted Price')
                                ->numeric()
                                ->prefix('$')
                                ->required()
                                ->readOnly()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn(Get $get, Set $set) => self::calculateBalance($get, $set))
                                ->extraInputAttributes(['class' => 'font-bold text-green-600 bg-green-50']),

                            TextInput::make('amount_paid')
                                ->label('Initial Deposit / Amount Paid')
                                ->numeric()
                                ->prefix('$')
                                ->default(0)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn(Get $get, Set $set) => self::calculateBalance($get, $set))
                                ->readOnly(
                                    fn(string $operation) =>
                                    $operation === 'edit' && !\App\Helpers\Staff::user()?->hasAnyRole(['Superadmin', 'Administration', 'Manager'])
                                )
                                ->helperText(
                                    fn(string $operation) =>
                                    $operation === 'edit' && \App\Helpers\Staff::user()?->hasAnyRole(['Superadmin', 'Administration', 'Manager'])
                                        ? '⚠️ Admin: editing this will recalculate balance'
                                        : null
                                )
                                ->extraInputAttributes(['class' => 'font-bold text-blue-600']),

                            // Split deposit toggle
                            // Superadmin/Administration can see on edit to fix mistakes
                            Toggle::make('is_split_deposit')
                                ->label('Split Deposit Payment?')
                                ->onColor('warning')
                                ->live()
                                ->default(false)
                                ->visible(
                                    fn(string $operation) =>
                                    $operation === 'create' || \App\Helpers\Staff::user()?->hasAnyRole(['Superadmin', 'Administration', 'Manager'])
                                )
                                ->dehydrated(false),

                            // Single payment method
                            // Superadmin/Administration can edit on existing orders to fix wrong method
                            Select::make('initial_payment_method')
                                ->label('Deposit Payment Method')
                                ->options(self::getPaymentOptions())
                                ->default('CASH')
                                ->required(fn(Get $get) => floatval($get('amount_paid')) > 0 && !$get('is_split_deposit'))
                                ->visible(
                                    fn(string $operation, Get $get) => ($operation === 'create' || \App\Helpers\Staff::user()?->hasAnyRole(['Superadmin', 'Administration', 'Manager']))
                                        && !$get('is_split_deposit')
                                )
                                ->dehydrated(false),

                            // Split payment repeater
                            // Superadmin/Administration can edit on existing orders to fix wrong split
                            Repeater::make('split_deposit_payments')
                                ->label('Split Deposit Breakdown')
                                ->visible(
                                    fn(string $operation, Get $get) => ($operation === 'create' || \App\Helpers\Staff::user()?->hasAnyRole(['Superadmin', 'Administration', 'Manager']))
                                        && $get('is_split_deposit')
                                )
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
                                ->defaultItems(2)
                                ->maxItems(4)
                                ->reorderable(false)
                                ->live()
                                ->dehydrated(false),

                            // Split remaining balance display
                            Placeholder::make('split_deposit_calc')
                                ->label('Deposit Remaining')
                                ->visible(fn(Get $get) => $get('is_split_deposit'))
                                ->content(function (Get $get) {
                                    $total     = floatval($get('amount_paid') ?? 0);
                                    $payments  = $get('split_deposit_payments') ?? [];
                                    $sum       = collect($payments)->sum(fn($p) => (float)($p['amount'] ?? 0));
                                    $remaining = $total - $sum;
                                    $color     = round($remaining, 2) <= 0 ? 'text-success-600' : 'text-danger-600';
                                    return new HtmlString("<span class='{$color} font-bold text-lg'>$" . number_format(max(0, $remaining), 2) . " remaining</span>");
                                }),

                            Toggle::make('is_tax_free')
                                ->label('Tax Free Order?')
                                ->helperText('Toggle on if this order is financed or exempt from tax.')
                                ->default(false)
                                ->live()
                                ->afterStateUpdated(fn(Get $get, Set $set) => self::calculateBalance($get, $set))
                                ->dehydrated(true),

                            TextInput::make('balance_due')
                                ->label('Remaining Balance')
                                ->numeric()
                                ->prefix('$')
                                ->default(0)
                                ->readOnly()
                                ->afterStateHydrated(function (Get $get, Set $set) {
                                    // Instantly recalculate and fix the balance on page load
                                    self::calculateBalance($get, $set); 
                                })
                                ->extraInputAttributes(['class' => 'font-bold text-red-600 bg-red-50']),

                            // Financial summary
                            Placeholder::make('financial_summary')
                                ->label('Order Summary')
                                ->live()
                                ->content(function (Get $get) {
                                    $quoted    = floatval($get('quoted_price') ?? 0);
                                    $paid      = floatval($get('amount_paid') ?? 0);
                                    $isTaxFree = (bool)($get('is_tax_free') ?? false);

                                    $dbTax   = DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? 7.63;
                                    $taxRate = $isTaxFree ? 0 : floatval($dbTax) / 100;
                                    $tax     = $quoted * $taxRate;
                                    $total   = $quoted + $tax;
                                    $balance = max(0, $total - $paid);

                                    return new HtmlString("
                                        <div class='rounded-lg border border-gray-200 overflow-hidden text-sm'>
                                            <div class='flex justify-between px-3 py-2 bg-gray-50'>
                                                <span class='text-gray-500'>Quoted Price</span>
                                                <span class='font-semibold'>\$" . number_format($quoted, 2) . "</span>
                                            </div>
                                            <div class='flex justify-between px-3 py-2'>
                                                <span class='text-gray-500'>Tax (" . ($isTaxFree ? 'Exempt' : number_format($dbTax, 2) . '%') . ")</span>
                                                <span class='font-semibold'>\$" . number_format($tax, 2) . "</span>
                                            </div>
                                            <div class='flex justify-between px-3 py-2 bg-gray-50 font-bold'>
                                                <span>Total</span>
                                                <span>\$" . number_format($total, 2) . "</span>
                                            </div>
                                            <div class='flex justify-between px-3 py-2'>
                                                <span class='text-success-600'>Deposit Paid</span>
                                                <span class='font-semibold text-success-600'>-\$" . number_format($paid, 2) . "</span>
                                            </div>
                                            <div class='flex justify-between px-3 py-2 bg-red-50 font-black text-red-600 text-base'>
                                                <span>Balance Due</span>
                                                <span>\$" . number_format($balance, 2) . "</span>
                                            </div>
                                        </div>
                                    ");
                                }),

                            Select::make('status')
                                ->options([
                                    'draft'         => 'Draft',
                                    'quoted'        => 'Quoted',
                                    'approved'      => 'Approved',
                                    'in_production' => 'In Production',
                                    'received'      => 'Ready for Pickup',
                                    'completed'     => 'Picked Up / Completed',
                                ])
                                ->default('draft')
                                ->live()
                                ->extraAttributes(['class' => 'font-bold']),

                            Section::make('Communication Log')
                                ->schema([
                                    Toggle::make('is_customer_notified')
                                        ->label('Mark as Notified')
                                        ->onColor('success')
                                        ->offColor('gray'),
                                    DateTimePicker::make('notified_at')
                                        ->label('Last Notified Timestamp')
                                        ->readOnly(),
                                ])
                                ->visible(fn(Get $get) => in_array($get('status'), ['received', 'completed']))
                                ->compact(),
                        ]),
                ]),
            ]),
        ]);
    }

   public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_no')
                    ->label('ORDER #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->grow(false),

                Tables\Columns\TextColumn::make('order_type')
                    ->label('TYPE')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state === 'stock_modify' ? 'Modify Stock' : 'Custom')
                    ->color(fn($state) => $state === 'stock_modify' ? 'warning' : 'info')
                    ->grow(false),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('CUSTOMER')
                    ->searchable()
                    ->description(fn($record) => $record->product_name ?? 'Custom Piece'),

                Tables\Columns\TextColumn::make('staff.name')
                    ->label('SALES REP')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('gray'),

                Tables\Columns\TextColumn::make('due_date')
                    ->date('M d, Y')
                    ->label('DUE DATE')
                    ->sortable()
                    ->grow(false)
                    ->color(fn($record) => $record->due_date && $record->due_date <= now() ? 'danger' : 'warning'),

                Tables\Columns\TextColumn::make('quoted_price')
                    ->label('QUOTED')
                    ->money('USD')
                    ->color('success')
                    ->grow(false),

                // ── 1. NEW PAID COLUMN ──
                Tables\Columns\TextColumn::make('total_paid')
                    ->label('PAID')
                    ->getStateUsing(function (CustomOrder $record) {
                        // Check for recorded payments first, fallback to the initial amount_paid column
                        $paidFromPayments = $record->payments()->sum('amount');
                        return $paidFromPayments > 0 ? $paidFromPayments : floatval($record->amount_paid);
                    })
                    ->money('USD')
                    ->color('info')
                    ->grow(false),

                // ── 2. DYNAMIC BALANCE CALCULATION ──
                Tables\Columns\TextColumn::make('computed_balance')
                    ->label('BALANCE')
                    ->getStateUsing(function (CustomOrder $record) {
                        // Dynamically calculate the true grand total including tax
                        $quoted    = floatval($record->quoted_price);
                        $isTaxFree = (bool)($record->is_tax_free);
                        $dbTax     = \Illuminate\Support\Facades\DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? 7.63;
                        $taxRate   = $isTaxFree ? 0 : floatval($dbTax) / 100;
                        $grandTotal = $quoted + ($quoted * $taxRate);

                        // Fetch the true total paid
                        $paidFromPayments = $record->payments()->sum('amount');
                        $totalPaid = $paidFromPayments > 0 ? $paidFromPayments : floatval($record->amount_paid);

                        // Return the accurate remaining balance
                        return max(0, $grandTotal - $totalPaid);
                    })
                    ->money('USD')
                    ->sortable(false) // Dynamic columns cannot be natively sorted via DB
                    ->grow(false)
                    ->color(fn($state) => $state > 0 ? 'danger' : 'success')
                    ->weight('bold'),

                Tables\Columns\SelectColumn::make('status')
                    ->label('STATUS')
                    ->options([
                        'draft'         => 'Draft',
                        'quoted'        => 'Quoted',
                        'approved'      => 'Approved',
                        'in_production' => 'In Production',
                        'received'      => 'Ready for Pickup',
                        'completed'     => 'Completed',
                    ])
                    ->selectablePlaceholder(false)
                    ->grow(false),

                Tables\Columns\TextColumn::make('notified_at')
                    ->label('NOTIFIED')
                    ->getStateUsing(fn($record) => $record->is_customer_notified && $record->notified_at
                        ? $record->notified_at->format('M d, y')
                        : 'No'
                    )
                    ->icon(fn($state) => $state !== 'No' ? 'heroicon-s-check-circle' : 'heroicon-o-clock')
                    ->color(fn($state) => $state !== 'No' ? 'success' : 'gray')
                    ->grow(false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft'         => 'Draft',
                        'quoted'        => 'Quoted',
                        'approved'      => 'Approved',
                        'in_production' => 'In Production',
                        'received'      => 'Ready for Pickup',
                        'completed'     => 'Completed',
                    ]),
                Tables\Filters\SelectFilter::make('order_type')
                    ->options([
                        'custom'       => 'Custom Made',
                        'stock_modify' => 'Modify Stock',
                    ]),
            ])
            ->actions([
                // ── EDIT ──────────────────────────────────────────────
                Tables\Actions\EditAction::make()->iconButton(),

                // ── ADD DEPOSIT / PAYMENT ─────────────────────────────
                Tables\Actions\Action::make('recordPayment')
                    ->label('Add Deposit')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->button()
                    ->size('sm')
                    ->visible(fn(CustomOrder $record) => !in_array($record->status, ['completed', 'cancelled']) && $record->balance_due > 0)
                    ->modalHeading(fn(CustomOrder $record) => "Add Payment — Order {$record->order_no}")
                    ->modalSubmitActionLabel('✓ Record Payment')
                    ->modalWidth('lg')
                    ->form(function (CustomOrder $record) {
                        $record = $record->fresh(['payments']);
                        // ── Build payment history HTML ─────────────────
                        $payments   = $record->payments()->orderBy('paid_at')->get();
                        $totalPaid  = $payments->sum('amount');
                        $isTaxFree  = (bool)($record->is_tax_free ?? false);
                        $dbTax      = DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? 7.63;
                        $taxRate    = $isTaxFree ? 0 : floatval($dbTax) / 100;
                        $grandTotal = floatval($record->quoted_price) + (floatval($record->quoted_price) * $taxRate);
                        $balance    = max(0, $grandTotal - $totalPaid);

                        $rows = '';
                        if ($payments->isEmpty()) {
                            $rows = "<p style='font-size:11px;color:#9ca3af;font-style:italic;padding:8px 0;'>No payments recorded yet.</p>";
                        } else {
                            $running = 0;
                            foreach ($payments as $p) {
                                $running    += floatval($p->amount);
                                $runningBal  = max(0, $grandTotal - $running);
                                $rows .= "
                                    <div style='display:flex;justify-content:space-between;align-items:center;font-size:11px;padding:6px 0;border-bottom:1px dashed #e5e7eb;'>
                                        <div>
                                            <span style='color:#374151;font-weight:600;'>" . \Carbon\Carbon::parse($p->paid_at)->format('M d, Y') . "</span>
                                            <span style='color:#9ca3af;margin-left:6px;'>" . \Carbon\Carbon::parse($p->paid_at)->format('h:i A') . "</span>
                                            <span style='display:inline-block;margin-left:8px;background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;border-radius:99px;padding:1px 8px;font-size:10px;font-weight:700;'>" . strtoupper($p->method) . "</span>
                                        </div>
                                        <div style='text-align:right;'>
                                            <span style='color:#10b981;font-weight:700;'>+\$" . number_format($p->amount, 2) . "</span>
                                            <span style='color:#9ca3af;font-size:10px;margin-left:8px;'>bal: \$" . number_format($runningBal, 2) . "</span>
                                        </div>
                                    </div>
                                ";
                            }
                        }

                        $balColor    = $balance <= 0 ? '#10b981' : '#ef4444';
                        $summaryHtml = "
                            <div style='background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:14px;margin-bottom:2px;'>
                                <div style='font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;'>
                                    📋 Payment History
                                </div>
                                {$rows}
                                <div style='margin-top:10px;padding-top:10px;border-top:2px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;'>
                                    <div>
                                        <div style='font-size:10px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;'>Order Total</div>
                                        <div style='font-size:14px;font-weight:700;color:#374151;'>\$" . number_format($grandTotal, 2) . "</div>
                                    </div>
                                    <div style='text-align:center;'>
                                        <div style='font-size:10px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;'>Total Paid</div>
                                        <div style='font-size:14px;font-weight:700;color:#10b981;'>\$" . number_format($totalPaid, 2) . "</div>
                                    </div>
                                    <div style='text-align:right;'>
                                        <div style='font-size:10px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;'>Remaining</div>
                                        <div style='font-size:20px;font-weight:900;color:{$balColor};'>\$" . number_format($balance, 2) . "</div>
                                    </div>
                                </div>
                            </div>
                        ";

                        return [
                            Placeholder::make('payment_history')
                                ->label('')
                                ->content(new HtmlString($summaryHtml)),

                            TextInput::make('amount')
                                ->label('Amount to Collect Now')
                                ->numeric()
                                ->required()
                                ->prefix('$')
                                ->default(fn() => $record->fresh()->balance_due)
                                ->extraInputAttributes([
                                    'style' => 'font-size:1.4rem;font-weight:900;height:3rem;border:2px solid #10b981;background:#f0fdf4;color:#15803d;',
                                ])
                                ->helperText('Enter the amount the customer is paying today'),

                            Toggle::make('is_split')
                                ->label('Split Payment?')
                                ->live()
                                ->default(false),

                            Select::make('payment_method')
                                ->label('Payment Method')
                                ->options(self::getPaymentOptions())
                                ->default('CASH')
                                ->required(fn(Get $get) => !$get('is_split'))
                                ->visible(fn(Get $get) => !$get('is_split'))
                                ->native(false),

                            Repeater::make('split_payments')
                                ->label('Split Payment Breakdown')
                                ->visible(fn(Get $get) => $get('is_split'))
                                ->schema([
                                    Grid::make(2)->schema([
                                        Select::make('method')
                                            ->options(self::getPaymentOptions())
                                            ->required()
                                            ->label('Method')
                                            ->native(false),
                                        TextInput::make('amount')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required()
                                            ->label('Amount'),
                                    ]),
                                ])
                                ->defaultItems(2)
                                ->maxItems(4)
                                ->reorderable(false),
                        ];
                    })
                    ->action(function (CustomOrder $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $amountPaid    = round((float) $data['amount'], 2);
                            $newAmountPaid = $record->amount_paid + $amountPaid;
                            $newBalance    = max(0, $record->quoted_price - $newAmountPaid);

                            $record->update([
                                'amount_paid' => $newAmountPaid,
                                'balance_due' => $newBalance,
                            ]);

                            if ($data['is_split'] ?? false) {
                                foreach ($data['split_payments'] ?? [] as $payment) {
                                    \App\Models\Payment::create([
                                        'custom_order_id' => $record->id,
                                        'sale_id'         => $record->sale_id ?? null,
                                        'amount'          => round((float) $payment['amount'], 2),
                                        'method'          => strtoupper(trim($payment['method'])),
                                        'paid_at'         => now(),
                                    ]);
                                }
                            } else {
                                \App\Models\Payment::create([
                                    'custom_order_id' => $record->id,
                                    'sale_id'         => $record->sale_id ?? null,
                                    'amount'          => $amountPaid,
                                    'method'          => strtoupper(trim($data['payment_method'])),
                                    'paid_at'         => now(),
                                ]);
                            }

                            if ($record->sale_id) {
                                $sale = Sale::find($record->sale_id);
                                if ($sale) {
                                    $totalPaid = $sale->payments()->sum('amount');
                                    $bal       = round(max(0, $sale->final_total - $totalPaid), 2);
                                    $sale->update([
                                        'amount_paid'  => $totalPaid,
                                        'balance_due'  => $bal,
                                        'status'       => $bal <= 0 ? 'completed' : 'pending',
                                        'completed_at' => $bal <= 0 ? now() : null,
                                    ]);
                                }
                            }
                        });

                        Notification::make()
                            ->title('Payment Recorded')
                            ->body('Balance has been updated.')
                            ->success()
                            ->send();
                    }),

                // ── GROUPED SECONDARY ACTIONS ─────────────────────────
                Tables\Actions\ActionGroup::make([

                    Tables\Actions\Action::make('viewItems')
                        ->label('View Order Items')
                        ->icon('heroicon-o-list-bullet')
                        ->color('info')
                        ->slideOver()
                        ->modalHeading(fn(CustomOrder $record) => "Order {$record->order_no} — Items")
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->form(fn(CustomOrder $record): array => [
                            Placeholder::make('items_detail')
                                ->label('')
                                ->content(function () use ($record) {
                                    $items = $record->items ?? [];
                                    if (empty($items)) {
                                        $items = [[
                                            'product_name'   => $record->product_name,
                                            'metal_type'     => $record->metal_type,
                                            'metal_weight'   => $record->metal_weight,
                                            'diamond_weight' => $record->diamond_weight,
                                            'size'           => $record->size,
                                            'design_notes'   => $record->design_notes,
                                            'quoted_price'   => $record->quoted_price,
                                        ]];
                                    }

                                    $html = '<div class="space-y-4">';
                                    foreach ($items as $i => $item) {
                                        $name  = $item['product_name'] ?? 'Item ' . ($i + 1);
                                        $price = '$' . number_format((float)($item['quoted_price'] ?? 0), 2);
                                        $html .= "
                                            <div class='p-4 bg-gray-50 border border-gray-200 rounded-lg'>
                                                <div class='flex justify-between items-center mb-2'>
                                                    <span class='font-bold text-gray-900'>{$name}</span>
                                                    <span class='text-success-600 font-bold text-lg'>{$price}</span>
                                                </div>
                                                <div class='grid grid-cols-3 gap-2 text-xs text-gray-500 mb-2'>
                                                    <div><span class='font-medium'>Metal:</span> " . ($item['metal_type'] ?? '—') . "</div>
                                                    <div><span class='font-medium'>Weight:</span> " . ($item['metal_weight'] ?? '—') . "g</div>
                                                    <div><span class='font-medium'>Size:</span> " . ($item['size'] ?? '—') . "</div>
                                                </div>
                                                " . (!empty($item['design_notes']) ? "<p class='text-sm text-gray-600 italic border-t border-gray-100 pt-2 mt-2'>{$item['design_notes']}</p>" : "") . "
                                            </div>";
                                    }
                                    $html .= '</div>';
                                    return new HtmlString($html);
                                }),
                        ]),

                   Tables\Actions\Action::make('printDepositReceipt')
    ->label('Print Receipt')
    ->icon('heroicon-o-printer')
    ->color('gray')
    ->url(fn(CustomOrder $record) => $record->sale_id
        ? route('sales.receipt', ['record' => $record->sale_id, 'type' => 'standard'])
        : route('custom-orders.deposit-receipt', ['customOrder' => $record->id])
    )
    ->openUrlInNewTab(),

                    Tables\Actions\Action::make('notifyDelay')
                        ->label('Notify: Delay')
                        ->icon('heroicon-o-clock')
                        ->color('danger')
                        ->modalHeading('Notify Customer of Delay')
                        ->form([
                            Select::make('notify_method')
                                ->label('Notification Method')
                                ->options(['sms' => 'SMS Only', 'email' => 'Email Only', 'both' => 'Both (SMS & Email)'])
                                ->default('sms')
                                ->required(),
                            Textarea::make('message')
                                ->label('Message Content')
                                ->default(fn($record) => "Hi {$record->customer->name}, your custom order #{$record->order_no} is taking a little longer than expected. We apologize for the delay and will update you soon.")
                                ->rows(3)
                                ->required(),
                        ])
                        ->action(fn(CustomOrder $record, array $data) => self::handleNotification($record, $data['notify_method'], $data['message'])),

                    Tables\Actions\Action::make('markReady')
                        ->label('Mark Ready & Notify')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->modalHeading('Mark Ready & Notify Customer')
                        ->form([
                            Select::make('notify_method')
                                ->label('Notification Method')
                                ->options(['sms' => 'SMS Only', 'email' => 'Email Only', 'both' => 'Both (SMS & Email)', 'none' => 'Do not notify'])
                                ->default('both')
                                ->required(),
                            Textarea::make('message')
                                ->label('Message Content')
                                ->default(fn($record) => "Hi {$record->customer->name}, Great news! Your custom order #{$record->order_no} is READY for pickup at Diamond Square.")
                                ->rows(3)
                                ->required(fn(Get $get) => $get('notify_method') !== 'none')
                                ->visible(fn(Get $get) => $get('notify_method') !== 'none'),
                        ])
                        ->action(function (CustomOrder $record, array $data) {
                            $record->update([
                                'status'               => 'received',
                                'is_customer_notified' => $data['notify_method'] !== 'none',
                                'notified_at'          => $data['notify_method'] !== 'none' ? now() : null,
                            ]);
                            if ($data['notify_method'] !== 'none') {
                                self::handleNotification($record, $data['notify_method'], $data['message']);
                            } else {
                                Notification::make()->title('Status updated. No notifications sent.')->success()->send();
                            }
                        }),

                    Tables\Actions\Action::make('convertToSale')
                        ->label('Convert to Sale')
                        ->icon('heroicon-o-shopping-cart')
                        ->color('info')
                        ->visible(fn(CustomOrder $record) => $record->status === 'received')
                        ->action(fn(CustomOrder $record) => redirect()->route('filament.admin.resources.sales.create', [
                            'customer_id'     => $record->customer_id,
                            'custom_order_id' => $record->id,
                        ])),

                    Tables\Actions\Action::make('viewMessageHistory')
                        ->label('Message History')
                        ->icon('heroicon-o-chat-bubble-left-ellipsis')
                        ->color('gray')
                        ->visible(fn(CustomOrder $record) => $record->is_customer_notified)
                        ->modalHeading('Communication History')
                        ->modalWidth('lg')
                        ->slideOver()
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->form([
                            Placeholder::make('history_log')
                                ->label('')
                                ->content(fn(CustomOrder $record) => new HtmlString("
                                    <div class='space-y-4'>
                                        <div class='p-4 bg-gray-50 border border-gray-200 rounded-lg shadow-sm'>
                                            <div class='flex justify-between items-center mb-2'>
                                                <span class='text-xs font-bold text-primary-600 uppercase'>Last Notification</span>
                                                <span class='text-[10px] text-gray-400'>" . ($record->notified_at?->format('M d, Y h:i A') ?? 'N/A') . "</span>
                                            </div>
                                            <p class='text-sm text-gray-700 italic leading-relaxed'>\"{$record->last_message}\"</p>
                                        </div>
                                    </div>
                                ")),
                        ]),

                ])
                ->label('More')
                ->icon('heroicon-m-ellipsis-horizontal')
                ->color('gray')
                ->button()
                ->size('sm'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPaymentOptions(): array
    {
        $json           = DB::table('site_settings')->where('key', 'payment_methods')->value('value');
        $defaultMethods = ['CASH', 'VISA', 'MASTERCARD', 'AMEX'];
        $methods        = $json ? json_decode($json, true) : $defaultMethods;
        $options        = [];

        foreach ($methods as $method) {
            if (strtoupper($method) !== 'LAYBUY') {
                // FIX: key is uppercase to match EOD grouping
                $options[strtoupper($method)] = strtoupper($method);
            }
        }
        return $options;
    }

    // FIX: calculateBalance now includes tax and respects is_tax_free toggle
    public static function calculateBalance(Get $get, Set $set): void
    {
        $quoted    = floatval($get('quoted_price') ?? 0);
        $isTaxFree = (bool)($get('is_tax_free') ?? false);

        // Account for split deposits vs regular deposits
        $isSplit = (bool)($get('is_split_deposit') ?? false);
        $paid = $isSplit 
            ? collect($get('split_deposit_payments') ?? [])->sum(fn($p) => (float)($p['amount'] ?? 0))
            : floatval($get('amount_paid') ?? 0);

        $dbTax   = DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? 7.63;
        $taxRate = $isTaxFree ? 0 : floatval($dbTax) / 100;
        $tax     = $quoted * $taxRate;
        $total   = $quoted + $tax;

        $set('balance_due', round(max(0, $total - $paid), 2));
    }

    public static function handleNotification(CustomOrder $record, string $method, string $message): void
    {
        $record->update([
            'last_message'         => $message,
            'notified_at'          => now(),
            'is_customer_notified' => true,
        ]);

        $smsSuccess   = false;
        $emailSuccess = false;

        if (in_array($method, ['sms', 'both'])) {
            if (empty($record->customer->phone)) {
                Notification::make()->title('SMS Failed')->body('Customer has no phone number.')->danger()->send();
            } else {
                try {
                    $digits = preg_replace('/[^0-9]/', '', $record->customer->phone);
                    if (Str::startsWith($digits, '1') && strlen($digits) === 11) $digits = substr($digits, 1);
                    $formattedPhone = '+1' . $digits;

                    $settings = DB::table('site_settings')->pluck('value', 'key');

                    $sns = new SnsClient([
                        'version'     => 'latest',
                        'region'      => $settings['aws_sms_default_region'] ?? config('services.sns.region'),
                        'credentials' => [
                            'key'    => $settings['aws_sms_access_key_id'] ?? config('services.sns.key'),
                            'secret' => $settings['aws_sms_secret_access_key'] ?? config('services.sns.secret'),
                        ],
                    ]);

                    $sns->publish([
                        'Message'           => $message,
                        'PhoneNumber'       => $formattedPhone,
                        'MessageAttributes' => [
                            'OriginationNumber' => [
                                'DataType'    => 'String',
                                'StringValue' => $settings['aws_sns_sms_from'] ?? config('services.sns.sms_from'),
                            ],
                        ],
                    ]);
                    $smsSuccess = true;
                } catch (\Exception $e) {
                    Notification::make()->title('SMS Error')->body($e->getMessage())->danger()->send();
                }
            }
        }

        if (in_array($method, ['email', 'both'])) {
            if (empty($record->customer->email)) {
                Notification::make()->title('Email Failed')->body('Customer has no email address.')->danger()->send();
            } else {
                try {
                    $settings = DB::table('site_settings')->pluck('value', 'key');
                    config([
                        'services.ses.key'    => $settings['aws_access_key_id']     ?? config('services.ses.key'),
                        'services.ses.secret' => $settings['aws_secret_access_key'] ?? config('services.ses.secret'),
                        'services.ses.region' => $settings['aws_default_region']    ?? config('services.ses.region'),
                    ]);

                    Mail::raw($message, function ($mail) use ($record) {
                        $mail->to($record->customer->email)
                            ->subject("Update on your Custom Order #{$record->order_no}");
                    });
                    $emailSuccess = true;
                } catch (\Exception $e) {
                    Notification::make()->title('Email Error')->body('Failed to send email. Check SMTP settings.')->danger()->send();
                }
            }
        }

        if ($method === 'both' && $smsSuccess && $emailSuccess) {
            Notification::make()->title('Success')->body('SMS & Email sent successfully.')->success()->send();
        } elseif ($method === 'sms' && $smsSuccess) {
            Notification::make()->title('Success')->body('SMS sent successfully.')->success()->send();
        } elseif ($method === 'email' && $emailSuccess) {
            Notification::make()->title('Success')->body('Email sent successfully.')->success()->send();
        }
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCustomOrders::route('/'),
            'create' => Pages\CreateCustomOrder::route('/create'),
            'edit'   => Pages\EditCustomOrder::route('/{record}/edit'),
        ];
    }
}
