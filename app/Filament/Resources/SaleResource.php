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
use Filament\Forms\Components\{Section, Grid, Group, Repeater, Select, TextInput, Placeholder, Hidden, Checkbox, Toggle};
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
                       Section::make('Stock Selection')->schema([
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
                        Section::make('Current Bill Items')->schema([
                            Repeater::make('items')
                                ->relationship('items')
                                ->schema([
                                    Hidden::make('product_item_id')->dehydrated(),
                                    Hidden::make('repair_id')->dehydrated(),
                                    Hidden::make('custom_order_id')->dehydrated(),

                                    TextInput::make('stock_no_display')
                                        ->label('Item')
                                        ->readOnly()
                                        ->dehydrated(false)
                                        ->columnSpan(1),

                                    TextInput::make('custom_description')
                                        ->label('Description')
                                        ->columnSpan(3), // Adjusted span to fit new field

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
                                        ->live(onBlur: true)
                                        ->columnSpan(2)
                                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                            // Recalculate Discount Amount based on existing Percent
                                            $qty = intval($get('qty'));
                                            $percent = floatval($get('discount_percent'));
                                            $lineTotal = floatval($state) * $qty;
                                            $set('discount_amount', number_format($lineTotal * ($percent / 100), 2, '.', ''));

                                            self::updateTotals($get, $set);
                                        }),

                                    // 3. PERCENT CHANGE: Calculate Amount ($)
                                    TextInput::make('discount_percent')
                                        ->label('Disc %')
                                        ->numeric()
                                        ->suffix('%')
                                        ->default(0)
                                        ->dehydrateStateUsing(fn($state) => $state ?: 0)
                                        ->live(onBlur: true)
                                        ->columnSpan(2)
                                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                            $price = floatval($get('sold_price'));
                                            $qty = intval($get('qty'));
                                            $lineTotal = $price * $qty;

                                            // Calculate $ Amount from %
                                            $amount = $lineTotal * (floatval($state) / 100);
                                            $set('discount_amount', number_format($amount, 2, '.', ''));

                                            self::updateTotals($get, $set);
                                        }),

                                    // 4. NEW FIELD: AMOUNT CHANGE: Calculate Percent (%)
                                    TextInput::make('discount_amount')
                                        ->label('Disc $')
                                        ->numeric()
                                        ->default(0)
                                        ->dehydrateStateUsing(fn($state) => $state ?: 0)
                                        ->prefix('$')
                                        ->live(onBlur: true)
                                        ->columnSpan(2)
                                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                            $price = floatval($get('sold_price'));
                                            $qty = intval($get('qty'));
                                            $lineTotal = $price * $qty;

                                            if ($lineTotal > 0) {
                                                // Calculate % from $ Amount
                                                $percent = (floatval($state) / $lineTotal) * 100;
                                                $set('discount_percent', number_format($percent, 2, '.', ''));
                                            } else {
                                                $set('discount_percent', 0);
                                            }

                                            self::updateTotals($get, $set);
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
                    ]),

                    Group::make()->columnSpan(4)->schema([
                        Section::make('Customer & Personnel')->schema([
                            Select::make('customer_id')
                                ->label('Customer')
                                ->relationship('customer', 'name')
                                ->getOptionLabelFromRecordUsing(fn($record) => "{$record->name} {$record->last_name}")
                                ->searchable(['name', 'last_name', 'phone'])
                                ->preload()
                                ->required()
                                ->live()
                                // 🆕 1. Auto-fills the customer if coming from the "Find Customer" page
                                ->default(fn() => request()->query('customer_id'))

                                // 🆕 2. Tells the client exactly how to add a customer
                                ->helperText('Need a new customer? Click the "+" button.')

                                // 🆕 3. Adds a clear title to the popup modal
                                ->createOptionModalHeading('Create New Customer')
                                // 🚀 ENHANCED CUSTOMER CREATION MODAL
                                ->createOptionForm([
                                    Forms\Components\Tabs::make('New Customer')
                                        ->tabs([
                                            // 1. Essential Contact Info
                                            Forms\Components\Tabs\Tab::make('Contact')
                                                ->icon('heroicon-o-user')
                                                ->schema([
                                                    Forms\Components\Grid::make(2)->schema([
                                                        Forms\Components\TextInput::make('name')
                                                            ->label('First Name')
                                                            ->required(),
                                                        Forms\Components\TextInput::make('last_name')
                                                            ->label('Last Name'),
                                                    ]),

                                                    Forms\Components\Grid::make(2)->schema([
                                                        Forms\Components\TextInput::make('phone')
                                                            ->label('Mobile Phone')
                                                            ->tel()
                                                            ->prefix('+1')
                                                            ->mask('(999) 999-9999') // Auto-format
                                                            ->stripCharacters(['(', ')', '-', ' '])
                                                            ->required()
                                                            ->unique('customers', 'phone'),

                                                        Forms\Components\TextInput::make('email')
                                                            ->label('Email')
                                                            ->email(),
                                                    ]),

                                                    Forms\Components\Textarea::make('address')
                                                        ->label('Address')
                                                        ->rows(2)
                                                        ->columnSpanFull(),
                                                ]),

                                            // 2. Personal Details & Image
                                            Forms\Components\Tabs\Tab::make('Profile')
                                                ->icon('heroicon-o-camera')
                                                ->schema([
                                                    Forms\Components\FileUpload::make('image')
                                                        ->label('Customer Photo')
                                                        ->image()
                                                        ->avatar()
                                                        ->imageEditor()
                                                        ->directory('customer-photos')
                                                        ->visibility('public')
                                                        ->columnSpanFull(),

                                                    Forms\Components\Grid::make(2)->schema([
                                                        Forms\Components\DatePicker::make('dob')
                                                            ->label('Birthday')
                                                            ->native(false),

                                                        Forms\Components\DatePicker::make('wedding_anniversary')
                                                            ->label('Anniversary')
                                                            ->native(false),
                                                    ]),
                                                ]),
                                        ]),

                                    // Auto-generate customer number
                                    Forms\Components\Hidden::make('customer_no')
                                        ->default(fn() => 'CUST-' . strtoupper(bin2hex(random_bytes(3)))),
                                ])
                                ->createOptionUsing(function (array $data) {
                                    // Save and return ID
                                    return \App\Models\Customer::create($data)->id;
                                }),
                            Select::make('sales_person_list')
                                ->label('Sales Persons (Double Assistant)')
                                ->multiple() // 🚀 THIS ENABLES MULTIPLE SELECTION
                                ->searchable()
                                ->preload()
                                ->hintAction(
                                    FormAction::make('Help')
                                        ->icon('heroicon-o-information-circle')
                                        ->tooltip('Add SalesPerson for multi-people sales')
                                )
                                ->options(fn() => User::pluck('name', 'name')->toArray()) // Fetches your team list
                                ->default(fn() => [auth()->user()->name]) // Defaults to current user in an array
                                ->required()
                                ->helperText('Select one or more staff members for this sale.')
                                ->dehydrated(),
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
            ]);
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
                TextColumn::make('created_at')->label('Date')->dateTime('M d, y')->sortable()->grow(false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')->label('Payment Type')
                    ->options(['cash' => 'CASH', 'laybuy' => 'LAYBUY', 'visa' => 'VISA']),
                Tables\Filters\TernaryFilter::make('has_trade_in')->label('Trade-Ins')
            ])
            ->actions([
                Tables\Actions\EditAction::make()->visible(fn($record) => $record->status !== 'completed' || auth()->user()->hasRole('Superadmin')),
                Tables\Actions\ViewAction::make()->label('View')->icon('heroicon-o-eye')->color('info'),
                Tables\Actions\DeleteAction::make()
                    ->label('Archive')
                    ->icon('heroicon-o-archive-box-arrow-down')
                    ->modalHeading('Delete Sale')
                    ->modalDescription('Are you sure? Items will return to stock and this sale will move to Archives.')
                    // 🔒 Security Check: Only allow Superadmin OR Administration
                    ->visible(fn() => auth()->user()->hasAnyRole(['Superadmin', 'Administration'])),

                Tables\Actions\Action::make('refund')
                    ->label('Refund')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn(Sale $record) => $record->status === 'completed')
                    ->url(fn(Sale $record): string => RefundResource::getUrl('create', [
                        'sale_id' => $record->id,
                    ])),
                Tables\Actions\Action::make('printReceipt')->label('Receipt')->icon('heroicon-o-printer')->color('info')
                    ->url(fn(Sale $record): string => route('sales.receipt', $record))->openUrlInNewTab(),
                // Inside SaleResource.php table actions
                Tables\Actions\Action::make('emailReceipt')
                    ->label('Email Receipt')
                    ->icon('heroicon-o-envelope')
                    ->color('success')
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

                        // Initialize mailable and call the direct send method
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


                Tables\Actions\Action::make('smsReceipt')
                    ->label('SMS Receipt')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Send Receipt via SMS')
                    ->action(function (Sale $record) {
                        // 1. Get Customer Phone
                        $phone = $record->customer->phone;
                        if (empty($phone)) {
                            Notification::make()->title('Error')->body('Customer has no phone number.')->danger()->send();
                            return;
                        }

                        // 2. Format Phone (+1 for USA)
                        $digits = preg_replace('/[^0-9]/', '', $phone);

                        // 🔹 FIX: Used 'Str::' instead of 'Illuminate\Support\Str::'
                        if (Str::startsWith($digits, '1') && strlen($digits) === 11) {
                            $digits = substr($digits, 1);
                        }
                        $formattedPhone = '+1' . $digits;

                        // ---------------------------------------------------------
                        // 3. GENERATE DYNAMIC LINK BASED ON STORE
                        // ---------------------------------------------------------

                        $store = $record->store;

                        $baseUrl = $store && !empty($store->domain_url)
                            ? rtrim($store->domain_url, '/')
                            : config('app.url');

                        $link = $baseUrl . "/receipt/" . $record->id;

                        // ---------------------------------------------------------

                        // 4. Create Message
                        $storeName = $store->name ?? 'Diamond Square';
                        $message = "Hi {$record->customer->name}, thanks for visiting {$storeName}! View your receipt here: {$link}";

                        // 5. Send via AWS SNS
                        try {
                            $sns = new SnsClient([
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

                            Notification::make()->title('SMS Sent')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('SMS Failed')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function updateTotals(Get $get, Set $set)
    {
        $items = $get('items') ?? [];
        $shipping = floatval($get('shipping_charges') ?? 0);
        $subtotal = 0;
        $taxableSubtotal = 0; // 🚀 Added to track only taxable items

        foreach ($items as $item) {
            $price = floatval($item['sold_price'] ?? 0);
            $qty = intval($item['qty'] ?? 1);
            $percent = floatval($item['discount_percent'] ?? 0);

            $rowTotal = $price * $qty;
            $rowAfterDiscount = ($rowTotal - ($rowTotal * ($percent / 100)));

            $subtotal += $rowAfterDiscount;

            // 🚀 THE LOGIC: Check if the 'No Tax' checkbox is UNCHECKED
            if (!($item['is_tax_free'] ?? false)) {
                $taxableSubtotal += $rowAfterDiscount;
            }
        }

        $dbTax = DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? 7.63;

        // 🚀 Tax now calculates ONLY on taxable items + shipping (if shipping is taxed)
        $tax = ($taxableSubtotal + ($get('shipping_taxed') ? $shipping : 0)) * (floatval($dbTax) / 100);

        $tradeIn = ($get('has_trade_in') == 1) ? floatval($get('trade_in_value') ?? 0) : 0;

        $set('subtotal', number_format($subtotal + $shipping, 2, '.', ''));
        $set('tax_amount', number_format($tax, 2, '.', ''));
        $set('final_total', number_format(($subtotal + $shipping + $tax) - $tradeIn, 2, '.', ''));
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
