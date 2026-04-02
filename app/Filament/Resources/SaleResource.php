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
use App\Models\DailyClosing;
use App\Models\SaleEditRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\{Section, Grid, Group, Repeater, Select, TextInput, Placeholder, Hidden, Checkbox, DatePicker, Textarea, Toggle};
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Tables\Actions\Action as TableAction;
use Illuminate\Support\Facades\Session;
use Tapp\FilamentGoogleAutocomplete\Forms\Components\GoogleAutocomplete;
use App\Helpers\Staff;

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

    // ── EOD LOCKING LOGIC ─────────────────────────────────────────────────────
    public static function isDateClosed(string $date): bool
    {
        return DailyClosing::whereDate('closing_date', $date)->exists();
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = Staff::user();
        if ($user?->hasAnyRole(['Superadmin', 'Administration']) || auth()->user()->hasRole('Superadmin')) {
            return true;
        }

        if ($record->payment_method === 'laybuy') return true;
        
        $record->loadMissing('items');
        if ($record->items->contains(fn($i) => !empty($i->custom_order_id))) return true;

        if ($record->status !== 'completed') return true;
        if (floatval($record->balance_due) > 0.01) return true;

        $dayClosed = self::isDateClosed($record->created_at->format('Y-m-d'));
        if (!$dayClosed) return true;

        return SaleEditRequest::where('sale_id', $record->id)
            ->where('user_id', auth()->id())
            ->where('status', 'approved')
            ->exists();
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = Staff::user();
        return $user?->hasRole('Superadmin') || auth()->user()->hasRole('Superadmin');
    }

    // ── FORM SCHEMA ───────────────────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        // Define UI lock closure
        $isLocked = function (?Sale $record) {
            if (!$record) return false;
            return !self::canEdit($record);
        };

        return $form
            ->schema([
                Grid::make(12)->schema([
                    Group::make()->columnSpan(8)
                        ->disabled($isLocked)
                        ->schema([
                        Section::make('Stock Selection')
                            ->headerActions([
                                FormAction::make('clear_draft')
                                    ->label('Start Fresh / New Sale')
                                    ->icon('heroicon-m-trash')
                                    ->color('danger')
                                    ->requiresConfirmation()
                                    ->action(function () {
                                        foreach (array_keys(session()->all()) as $key) {
                                            if (str_starts_with($key, 'sale_draft_')) {
                                                session()->forget($key);
                                            }
                                        }
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
                                                ->whereIn('status', ['in_stock', 'sold'])
                                                ->get()
                                                ->mapWithKeys(fn($item) => [
                                                    $item->id => "{$item->barcode} - {$item->qty} left " . ($item->status === 'sold' ? '[CURRENTLY SOLD]' : "(\${$item->retail_price})")
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

                                            $currentItems[] = [
                                                'product_item_id'    => $item->id,
                                                'repair_id'          => null,
                                                'stock_no_display'   => $item->barcode,
                                                'custom_description' => $item->custom_description ?? $item->barcode,
                                                'qty'                => $qty,
                                                'sold_price'         => $item->retail_price,
                                                'sale_price_override' => $item->retail_price * $qty,
                                                'discount_percent'   => 0,
                                                'discount_amount'    => 0,
                                                'is_tax_free'        => false,
                                            ];

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
                                ]),

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
                                                ->placeholder('e.g. Engraving, Cleaning Fee, Labour Charge')
                                                ->rules([
                                                    fn () => function (string $attribute, $value, $fail) {
                                                        if (preg_match('/^[GR]\d{4,}/i', $value)) {
                                                            $fail('Please use the Stock Search above. Do not use Non-Tag for barcoded items.');
                                                        }
                                                    },
                                                ]),
                                            Grid::make(2)->schema([
                                                TextInput::make('price')->label('Unit Price')->numeric()->prefix('$')->required()->default(0),
                                                TextInput::make('qty')->label('Qty')->numeric()->required()->default(1),
                                            ]),
                                            Grid::make(2)->schema([
                                                TextInput::make('discount_percent')->label('Discount %')->numeric()->suffix('%')->default(0),
                                                Toggle::make('is_tax_free')->label('Tax Free?')->default(false)->inline(false),
                                            ]),
                                        ])
                                        ->action(function (array $data, Get $get, Set $set) {
                                            $price   = floatval($data['price'] ?? 0);
                                            $qty     = intval($data['qty'] ?? 1);
                                            $discPct = floatval($data['discount_percent'] ?? 0);
                                            $lineTotal = $price * $qty;
                                            $discAmt   = $lineTotal * ($discPct / 100);
                                            $finalPrice = $lineTotal - $discAmt;

                                            $currentItems   = $get('items') ?? [];
                                            $currentItems[] = [
                                                'product_item_id'    => null,
                                                'repair_id'          => null,
                                                'custom_order_id'    => null,
                                                'is_non_stock'       => true,
                                                'stock_no_display'   => 'NON-TAG',
                                                'custom_description' => $data['description'],
                                                'qty'                => $qty,
                                                'sold_price'         => $price,
                                                'sale_price_override' => $finalPrice,
                                                'discount_percent'   => $discPct,
                                                'discount_amount'    => $discAmt,
                                                'is_tax_free'        => $data['is_tax_free'] ?? false,
                                            ];

                                            $set('items', $currentItems);
                                            self::updateTotals($get, $set);
                                        }),

                                    FormAction::make('create_new_custom_order')
                                        ->label('+ New Custom Order')
                                        ->color('success')
                                        ->outlined()
                                        ->icon('heroicon-o-paint-brush')
                                        ->modalHeading('Design New Custom Piece')
                                        ->modalDescription('This adds the item to the receipt and generates a tracking ticket.')
                                        ->modalWidth('3xl')
                                        ->modalSubmitActionLabel('Add to Bill')
                                        ->form([
                                            Grid::make(2)->schema([
                                                TextInput::make('product_name')->label('What are we making?')->placeholder('e.g. 14k Diamond Tennis Bracelet')->required(),
                                                Select::make('metal_type')
                                                    ->label('Metal Type')
                                                    ->options([
                                                        '10k' => '10k Gold', '14k' => '14k Gold', '18k' => '18k Gold',
                                                        'platinum' => 'Platinum', 'silver' => 'Silver', 'other' => 'Other',
                                                    ])
                                                    ->default('14k')->required(),
                                            ]),
                                            Grid::make(3)->schema([
                                                TextInput::make('quoted_price')->label('Total Item Price')->numeric()->prefix('$')->required()->live(onBlur: true),
                                                TextInput::make('amount_paid')
                                                    ->label('Deposit Amount')
                                                    ->numeric()->prefix('$')->default(0)
                                                    ->helperText('How much are they paying today?'),
                                                Select::make('deposit_method')
                                                    ->label('Deposit Payment Method')
                                                    ->options(self::getPaymentOptions())
                                                    ->default('VISA')
                                                    ->required(),
                                            ]),
                                            Grid::make(2)->schema([
                                                DatePicker::make('due_date')->label('Promised By Date')->native(false)->required(),
                                                Textarea::make('design_notes')->label('Design Notes & Instructions')->rows(3),
                                            ]),
                                        ])
                                        ->action(function (array $data, Get $get, Set $set) {
                                            $currentItems = $get('items') ?? [];
                                            $price = floatval($data['quoted_price']);
                                            $deposit = floatval($data['amount_paid'] ?? 0);
                                            $method = $data['deposit_method'] ?? 'CASH';

                                            $currentItems[] = [
                                                'product_item_id'     => null,
                                                'repair_id'           => null,
                                                'custom_order_id'     => null,
                                                'is_non_stock'        => false,
                                                'is_new_custom_order' => true, 
                                                'new_custom_data'     => $data, 
                                                'stock_no_display'    => 'NEW CUSTOM',
                                                'custom_description'  => "CUSTOM Order Sale: {$data['product_name']}\nMetal: {$data['metal_type']}\n" . ($data['design_notes'] ?? ''),
                                                'qty'                 => 1,
                                                'sold_price'          => $price,
                                                'sale_price_override' => $price,
                                                'discount_percent'    => 0,
                                                'discount_amount'     => 0,
                                                'is_tax_free'         => true,
                                            ];

                                            $set('items', $currentItems);

                                            // 🚀 INJECT DEPOSIT DIRECTLY INTO PAYMENTS
                                            if ($deposit > 0) {
                                                $set('is_split_payment', true);
                                                $currentSplits = $get('split_payments') ?? [];
                                                
                                                // Clean out empty rows filament creates by default
                                                $filteredSplits = [];
                                                foreach ($currentSplits as $k => $v) {
                                                    if (!empty($v['method']) && floatval($v['amount'] ?? 0) > 0) {
                                                        $filteredSplits[$k] = $v;
                                                    }
                                                }
                                                
                                                // Add deposit payment
                                                $filteredSplits[(string) Str::uuid()] = [
                                                    'method' => $method,
                                                    'amount' => $deposit,
                                                ];
                                                
                                                $set('split_payments', $filteredSplits);
                                            }

                                            self::updateTotals($get, $set);
                                        }),
                                ])->columnSpan(1),
                            ]),

                        Section::make('Current Bill Items')->schema([
                            Repeater::make('items')
                                ->relationship('items')
                                ->mutateRelationshipDataBeforeCreateUsing(function (array $data, $livewire) {
                                    if (!empty($data['is_new_custom_order'])) {
                                        $customOrder = \App\Models\CustomOrder::create([
                                            'customer_id'  => $livewire->data['customer_id'] ?? null,
                                            'staff_id'     => auth()->id(),
                                            'order_type'   => 'custom',
                                            'product_name' => $data['new_custom_data']['product_name'] ?? 'Custom',
                                            'metal_type'   => $data['new_custom_data']['metal_type'] ?? '14k', 
                                            'quoted_price' => $data['sale_price_override'] ?? 0, 
                                            'due_date'     => $data['new_custom_data']['due_date'] ?? null,
                                            'design_notes' => $data['new_custom_data']['design_notes'] ?? null,
                                            'status'       => 'in_production',
                                            'amount_paid'  => $data['new_custom_data']['amount_paid'] ?? 0,
                                            'balance_due'  => max(0, floatval($data['sale_price_override']) - floatval($data['new_custom_data']['amount_paid'] ?? 0)),
                                        ]);
                                        $data['custom_order_id'] = $customOrder->id;
                                    }
                                    unset($data['is_new_custom_order'], $data['new_custom_data'], $data['is_non_stock']);
                                    return $data;
                                })
                                ->mutateRelationshipDataBeforeSaveUsing(function (array $data, $livewire) {
                                    if (!empty($data['is_new_custom_order'])) {
                                        $customOrder = \App\Models\CustomOrder::create([
                                            'customer_id'  => $livewire->data['customer_id'] ?? null,
                                            'staff_id'     => auth()->id(),
                                            'order_type'   => 'custom',
                                            'product_name' => $data['new_custom_data']['product_name'] ?? 'Custom',
                                            'metal_type'   => $data['new_custom_data']['metal_type'] ?? '14k', 
                                            'quoted_price' => $data['sale_price_override'] ?? 0, 
                                            'due_date'     => $data['new_custom_data']['due_date'] ?? null,
                                            'design_notes' => $data['new_custom_data']['design_notes'] ?? null,
                                            'status'       => 'in_production',
                                            'amount_paid'  => $data['new_custom_data']['amount_paid'] ?? 0,
                                            'balance_due'  => max(0, floatval($data['sale_price_override']) - floatval($data['new_custom_data']['amount_paid'] ?? 0)),
                                        ]);
                                        $data['custom_order_id'] = $customOrder->id;
                                    }
                                    unset($data['is_new_custom_order'], $data['new_custom_data'], $data['is_non_stock']);
                                    return $data;
                                })
                                ->live()
                                ->schema([
                                    Hidden::make('product_item_id')->dehydrated(),
                                    Hidden::make('repair_id')->dehydrated(),
                                    Hidden::make('custom_order_id')->dehydrated(),
                                    Hidden::make('is_new_custom_order')->default(false)->dehydrated(),
                                    Hidden::make('new_custom_data')->dehydrated(),

                                    TextInput::make('stock_no_display')
                                        ->label('Item')
                                        ->readOnly()
                                        ->dehydrated(false)
                                        ->extraInputAttributes(fn($state) => [
                                            'class' => str_contains($state ?? '', 'CUSTOM') ? 'text-blue-600 font-bold bg-blue-50' : '',
                                        ])
                                        ->columnSpan(1),

                                    Textarea::make('custom_description')
                                        ->label('Description')
                                        ->maxLength(200)
                                        ->rows(2)
                                        ->autosize(false)
                                        ->extraInputAttributes(['style' => 'max-height:60px; overflow-y:auto; resize:none;'])
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
                                            self::updateTotals($get, $set);
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
                                            self::updateTotals($get, $set);
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
                                            self::updateTotals($get, $set);
                                        }),

                                    Checkbox::make('is_tax_free')
                                        ->label('No Tax')
                                        ->columnSpan(1)
                                        ->default(false)
                                        ->dehydrated(true)
                                        ->live()
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

                        Section::make('🛠️ Repair / Special Jobs')
                            ->description('Add one or more job instructions (Resize, Solder, Engraving, etc.)')
                            ->icon('heroicon-o-wrench')
                            ->collapsible()
                            ->schema([
                                Repeater::make('special_jobs')
                                    ->label('')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            Select::make('job_type')
                                                ->label('Service Type')
                                                ->options(fn() => self::getServiceTypeOptions())
                                                ->placeholder('Select Job Type')
                                                ->live()
                                                ->required(),
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
                                                ->displayFormat('M d, Y'),
                                        ]),
                                        Grid::make(2)
                                            ->visible(fn(Forms\Get $get) => $get('job_type') === 'Resize')
                                            ->schema([
                                                TextInput::make('current_size')->label('Current Size'),
                                                TextInput::make('target_size')->label('Target Size'),
                                            ]),
                                        Textarea::make('job_instructions')
                                            ->label('Bench Notes / Instructions')
                                            ->columnSpanFull()
                                            ->rows(2),
                                    ])
                                    ->columns(1)
                                    ->addActionLabel('+ Add Another Job')
                                    ->defaultItems(0)
                                    ->collapsible()
                                    ->itemLabel(fn(array $state): string => ($state['job_type'] ?? 'New Job'))
                                    ->reorderable(false),
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
                                        Textarea::make('trade_in_description')
                                            ->label('Item Description')
                                            ->required(fn(Get $get) => $get('has_trade_in') == 1)
                                            ->columnSpanFull()
                                            ->rows(2),
                                    ]),
                            ]),

                        Section::make('Warranty')
                            ->icon('heroicon-o-shield-check')
                            ->collapsible()
                            ->schema([
                                Grid::make(4)->schema([
                                    Select::make('has_warranty')
                                        ->label('Include Warranty?')
                                        ->options([0 => 'No', 1 => 'Yes'])
                                        ->default(0)
                                        ->live(),
                                    Select::make('warranty_period')
                                        ->label('Warranty Duration')
                                        ->visible(fn(Get $get) => $get('has_warranty') == 1)
                                        ->required(fn(Get $get) => $get('has_warranty') == 1)
                                        ->options(function () {
                                            $json    = DB::table('site_settings')->where('key', 'warranty_options')->value('value');
                                            $options = $json ? json_decode($json, true) : ['1 Year', '2 Years', 'Lifetime'];
                                            return array_combine($options, $options);
                                        }),
                                    TextInput::make('warranty_charge')
                                        ->label('Warranty Charge ($)')
                                        ->numeric()
                                        ->prefix('$')
                                        ->default(0)
                                        ->visible(fn(Get $get) => $get('has_warranty') == 1)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotals($get, $set)),
                                    DatePicker::make('follow_up_date')
                                        ->label('Follow Up (2 Weeks)')
                                        ->default(now()->addWeeks(2))
                                        ->native(false)
                                        ->displayFormat('M d, Y'),
                                ]),
                            ]),
                        Section::make('Shipping & Handling')
                            ->icon('heroicon-o-truck')
                            ->collapsible()
                            ->schema([
                                Grid::make(4)->schema([
                                    TextInput::make('shipping_charges')->label('Charges')->numeric()->prefix('$')->live(onBlur: true)->default(0)->required()
                                        ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotals($get, $set)),
                                    Checkbox::make('shipping_taxed')->label('Taxed?')->default(false),
                                    Select::make('carrier')
                                        ->options(['No carrier' => 'No carrier', 'UPS' => 'UPS', 'FedEx' => 'FedEx'])
                                        ->default('No carrier'),
                                    TextInput::make('tracking_number')->label('Tracking #')->nullable(),
                                ]),
                            ]),
                        Section::make('Receipt Customization')
                            ->schema([
                                Textarea::make('notes')
                                    ->label('Receipt Notes / Warranty')
                                    ->rows(3),
                            ]),
                    ]),

                    Group::make()->columnSpan(4)
                        ->disabled($isLocked)
                        ->schema([
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
                                            return Customer::query()
                                                ->where(function ($q) use ($search) {
                                                    $q->whereRaw("CONCAT(name, ' ', last_name) LIKE ?", ["%{$search}%"])
                                                        ->orWhereRaw("CONCAT(last_name, ' ', name) LIKE ?", ["%{$search}%"])
                                                        ->orWhere('phone', 'like', "%{$search}%")
                                                        ->orWhere('customer_no', 'like', "%{$search}%");
                                                })
                                                ->limit(50)
                                                ->get()
                                                ->mapWithKeys(function ($customer) {
                                                    return [$customer->id => "{$customer->name} {$customer->last_name} | {$customer->phone} (#{$customer->customer_no})"];
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
                                                    $customer = Customer::find($get('customer_id'));
                                                    if (!$customer) return [];
                                                    return [
                                                        Grid::make(2)->schema([
                                                            Placeholder::make('img')->label('')
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
                                                            TextInput::make('addr')->label('Full Address')->default(trim("{$customer->street} {$customer->suburb} {$customer->city} {$customer->state} {$customer->postcode}"))->readOnly()->columnSpanFull(),
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
                                                        ]),
                                                ]),
                                            Hidden::make('customer_no')->default(fn() => 'CUST-' . strtoupper(Str::random(6))),
                                        ])
                                        ->createOptionUsing(function (array $data) {
                                            return Customer::create($data)->id;
                                        }),
                                ]),

                                Select::make('sales_person_list')
                                    ->label('Sales Staff')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->options(fn() => User::orderBy('name')->pluck('name', 'name')->toArray())
                                    ->default(fn() => [Session::get('active_staff_name') ?? auth()->user()->name])
                                    ->required()
                                    ->live(onBlur: true),
                            ]),

                        Section::make('Payment & Status')->schema([
                            Placeholder::make('custom_order_payment_logs')
                                ->label('PREVIOUS DEPOSITS LOG')
                                ->visible(function (Get $get) {
                                    $items = $get('items') ?? [];
                                    return collect($items)->contains(fn($item) => !empty($item['custom_order_id']))
                                        || request()->has('custom_order_id');
                                })
                                ->content(function (Get $get) {
                                    $items = $get('items') ?? [];
                                    $customOrderId = collect($items)->firstWhere('custom_order_id')['custom_order_id']
                                        ?? request()->get('custom_order_id');

                                    if (!$customOrderId) return null;

                                    $payments = \App\Models\Payment::where('custom_order_id', $customOrderId)
                                        ->orderBy('paid_at', 'asc')
                                        ->get();

                                    if ($payments->isEmpty()) {
                                        return new HtmlString("<div class='text-xs text-gray-400 italic bg-gray-50 p-2 rounded'>No previous deposit records found in database.</div>");
                                    }

                                    $rows = "";
                                    foreach ($payments as $payment) {
                                        $date = \Carbon\Carbon::parse($payment->paid_at)->format('M d, Y h:i A');
                                        $method = strtoupper($payment->method);
                                        $amount = number_format($payment->amount, 2);

                                        $rows .= "
                                        <div style='display:flex; justify-content:space-between; align-items:center; padding:4px 0; border-bottom:1px solid #f1f5f9;'>
                                            <div style='display:flex; flex-direction:column;'>
                                                <span style='font-size:10px; font-weight:700; color:#334155;'>{$method}</span>
                                                <span style='font-size:9px; color:#94a3b8;'>{$date}</span>
                                            </div>
                                            <span style='font-size:11px; font-weight:700; color:#10b981;'>+\${$amount}</span>
                                        </div>";
                                    }

                                    $totalDeposited = $payments->sum('amount');

                                    return new HtmlString("
                                    <div style='background-color: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 12px; margin-bottom: 15px;'>
                                        <div style='font-size:10px; font-weight:900; color:#0369a1; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:8px; display:flex; align-items:center; gap:5px;'>
                                            Custom Order Payment Trail
                                        </div>
                                        <div style='max-height: 150px; overflow-y: auto; padding-right: 5px;'>
                                            {$rows}
                                        </div>
                                        <div style='display:flex; justify-content:space-between; align-items:center; margin-top:10px; pt-2; border-top: 1px solid #bae6fd; padding-top:8px;'>
                                            <span style='font-size:10px; font-weight:700; color:#0369a1;'>TOTAL APPLIED CREDIT</span>
                                            <span style='font-size:14px; font-weight:900; color:#0369a1;'>$" . number_format($totalDeposited, 2) . "</span>
                                        </div>
                                    </div>
                                    ");
                                }),

                            Toggle::make('is_split_payment')
                                ->label('Enable Split Payment')
                                ->onColor('warning')
                                ->live()
                                ->columnSpanFull(),

                            Select::make('payment_method')
                                ->label('Payment Method')
                                ->options(self::getPaymentOptions())
                                ->placeholder('Select Payment Method')
                                ->required(fn(Get $get) => !$get('is_split_payment'))
                                ->visible(fn(Get $get) => !$get('is_split_payment'))
                                ->live()
                                ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotals($get, $set)),

                            TextInput::make('display_total_due')
                                ->label('Total Amount to Collect')
                                ->prefix('$')
                                ->readOnly()
                                ->extraInputAttributes(['style' => 'font-size: 1.5rem; font-weight: 900; color: #0284c7; background-color: #f0f9ff;'])
                                ->visible(fn(Get $get) => !$get('is_split_payment'))
                                ->dehydrated(false),

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
                                ->helperText('What the customer physically handed over')
                                ->hintAction(
                                    FormAction::make('fill_full_amount')
                                        ->label('Collect Full Amount')
                                        ->icon('heroicon-o-banknotes')
                                        ->color('success')
                                        ->action(function (Get $get, Set $set) {
                                            $total = floatval($get('final_total') ?? 0);
                                            $set('amount_paid', number_format($total, 2, '.', ''));
                                            self::updateTotals($get, $set);
                                            self::syncStatus($get, $set);
                                        })
                                ),

                            TextInput::make('change_given')
                                ->label('Change Given')
                                ->prefix('$')
                                ->readOnly()
                                ->default('0.00')
                                ->dehydrated(false)
                                ->live()
                                ->visible(fn(Get $get) => !$get('is_split_payment'))
                                ->extraInputAttributes(['class' => 'text-right font-bold text-green-600 text-xl']),

                            Repeater::make('split_payments')
                                ->label('Payment Breakdown')
                                ->schema([
                                    Grid::make(2)->schema([
                                        Select::make('method')->options(self::getPaymentOptions())->required()->label('Method'),
                                        TextInput::make('amount')->numeric()->prefix('$')->required()->label('Amount')->live(onBlur: true),
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
                                ->label('INVOICE TOTALS')
                                ->content(function (Get $get) {
                                    $total  = floatval($get('final_total') ?? 0);
                                    $method = $get('payment_method') ?? '';

                                    // ── 1. CALCULATE CUSTOM DEPOSIT VS REGULAR PAYMENT ──
                                    $items = $get('items') ?? [];
                                    $intendedCustomDeposit = 0;
                                    
                                    // Extract ONLY the deposit intended for custom pieces
                                    foreach ($items as $item) {
                                        if (!empty($item['is_new_custom_order'])) {
                                            $intendedCustomDeposit += floatval($item['new_custom_data']['amount_paid'] ?? 0);
                                        }
                                    }

                                    // ── 2. CALCULATE TOTAL PAID ──
                                    $isSplit = $get('is_split_payment');
                                    $paidSum = $isSplit
                                        ? collect($get('split_payments') ?? [])->sum(fn($p) => (float)($p['amount'] ?? 0))
                                        : floatval($get('amount_paid') ?? 0);

                                    // ── 3. SEPARATE AMOUNTS ──
                                    // min() ensures that if they manually delete a payment row, the deposit doesn't artificially exceed total paid
                                    $actualCustomDeposit  = min($intendedCustomDeposit, $paidSum);
                                    $regularPaymentAmount = $paidSum - $actualCustomDeposit;
                                    $remaining            = $total - $paidSum;

                                    // ── 4. RENDER BREAKDOWN HELPER ──
                                    $renderBreakdown = function() use ($total, $actualCustomDeposit, $regularPaymentAmount) {
                                        $html = "
                                        <div style='display:flex; justify-content: space-between; margin-bottom: 5px; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px;'>
                                            <span style='font-size: 0.875rem; color: #64748b;'>Invoice Total:</span>
                                            <span style='font-size: 0.875rem; font-weight: bold; color: #334155;'>$" . number_format($total, 2) . "</span>
                                        </div>";

                                        if ($actualCustomDeposit > 0) {
                                            $html .= "
                                            <div style='display:flex; justify-content: space-between; align-items: center; margin-bottom: 5px; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px;'>
                                                <span class='px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wider bg-purple-50 text-purple-700 border-purple-200'>✨ CUSTOM DEPOSIT</span>
                                                <span style='font-size: 0.875rem; font-weight: bold; color: #10b981;'>+$" . number_format($actualCustomDeposit, 2) . "</span>
                                            </div>";
                                        }

                                        if ($regularPaymentAmount > 0) {
                                            $html .= "
                                            <div style='display:flex; justify-content: space-between; align-items: center; margin-bottom: 5px; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px;'>
                                                <span class='px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wider bg-teal-50 text-teal-700 border-teal-200'>💵 PAYMENT</span>
                                                <span style='font-size: 0.875rem; font-weight: bold; color: #10b981;'>+$" . number_format($regularPaymentAmount, 2) . "</span>
                                            </div>";
                                        }
                                        return $html;
                                    };

                                    // ── WHEN USING STANDARD PAYMENT ──
                                    if (!$isSplit) {
                                        $tendered = floatval($get('amount_paid') ?? 0);

                                        if (round($total, 2) <= 0 || ($tendered > 0 && round($tendered, 2) >= round($total, 2))) {
                                            return new HtmlString("
                                            " . $renderBreakdown() . "
                                            <div style='background:#ecfdf5; border:1px solid #10b981; padding:10px; border-radius:8px; text-align:right;'>
                                                <span style='color:#10b981; font-weight:900; font-size:1.875rem;'>$0.00</span>
                                                <div style='color:#047857; font-size:0.75rem; font-weight:700; margin-top:4px;'>✅ FULLY PAID</div>
                                            </div>
                                            ");
                                        }

                                        if ($tendered > 0 && $tendered < $total) {
                                            return new HtmlString("
                                            " . $renderBreakdown() . "
                                            <div style='text-align:right;'>
                                                <span style='color:#dc2626; font-weight:700; font-size:1.875rem;'>\$" . number_format($remaining, 2) . "</span>
                                                <div style='color:#dc2626; font-size:0.75rem; margin-top:4px;'>⚠️ Still owed — collect before completing</div>
                                            </div>
                                            ");
                                        }

                                        if (!empty($method)) {
                                            return new HtmlString("
                                                " . $renderBreakdown() . "
                                                <div style='text-align:right;'>
                                                    <span style='color:#dc2626; font-weight:700; font-size:1.875rem;'>\$" . number_format($total, 2) . "</span>
                                                    <div style='color:#dc2626; font-size:0.75rem; margin-top:4px;'>Enter Amount Received above</div>
                                                </div>
                                            ");
                                        }

                                        if (round($total, 2) <= 0) {
                                            return new HtmlString("
                                            " . $renderBreakdown() . "
                                            <div style='text-align:right;'>
                                                <span style='color:#16a34a; font-weight:700; font-size:1.875rem;'>\$0.00</span>
                                                <div style='color:#16a34a; font-size:0.75rem; margin-top:4px;'>✅ Fully Covered by Prior Deposits</div>
                                            </div>
                                            ");
                                        }

                                        return new HtmlString("
                                        " . $renderBreakdown() . "
                                        <div style='text-align:right;'>
                                            <span style='color:#dc2626; font-weight:700; font-size:1.875rem;'>\$" . number_format($total, 2) . "</span>
                                            <div style='color:#dc2626; font-size:0.75rem; margin-top:4px;'>Select a payment method</div>
                                        </div>
                                        ");
                                    }

                                    // ── WHEN USING SPLIT PAYMENT / CUSTOM DEPOSITS ──
                                    if (round($remaining, 2) <= 0) {
                                        $overpaid = abs($remaining);
                                        return new HtmlString("
                                            " . $renderBreakdown() . "
                                            <div style='text-align: right;'>
                                                <span style='color:#16a34a; font-weight:900; font-size:1.875rem;'>\$0.00</span>
                                                <div style='color:#16a34a; font-size:0.75rem; font-weight:700;'>✅ FULLY PAID</div>
                                                " . ($overpaid > 0 ? "<div style='color:#2563eb; font-size:0.875rem; font-weight:700; margin-top:8px;'>💵 Change Due: \$" . number_format($overpaid, 2) . "</div>" : "") . "
                                            </div>
                                        ");
                                    }

                                    // Display clear breakdown if balance remains
                                    return new HtmlString("
                                        " . $renderBreakdown() . "
                                        <div style='text-align: right;'>
                                            <span style='color:#dc2626; font-weight:900; font-size:1.875rem;'>\$" . number_format($remaining, 2) . "</span>
                                            <div style='color:#dc2626; font-size:0.75rem; font-weight:700;'>BALANCE DUE</div>
                                        </div>
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
            ->modifyQueryUsing(
                fn(Builder $query) => $query
                    ->where('status', '!=', 'void')
                    ->with(['customer', 'items.productItem', 'payments'])
            )
            ->columns([
                TextColumn::make('invoice_number')->label('Inv #')->searchable()->sortable()->grow(false),
                TextColumn::make('sale_type_badge')
                    ->label('TYPE')
                    ->getStateUsing(function ($record) {
                        $isLaybuy     = $record->payment_method === 'laybuy';
                        $hasRepair    = $record->items->contains(fn($i) => !empty($i->repair_id));
                        $hasCustom    = $record->items->contains(fn($i) => !empty($i->custom_order_id));
                        $hasSpecialJob = !empty($record->special_jobs);

                        if ($isLaybuy) return new HtmlString("<span style='background:#fef3c7;color:#b45309;border:1px solid #fcd34d;padding:3px 8px;border-radius:20px;font-size:10px;font-weight:800;white-space:nowrap;'>⏳ LAYBUY</span>");
                        if ($hasRepair) return new HtmlString("<span style='background:#f0fdf4;color:#15803d;border:1px solid #86efac;padding:3px 8px;border-radius:20px;font-size:10px;font-weight:800;white-space:nowrap;'>🔧 REPAIR</span>");
                        if ($hasCustom) return new HtmlString("<span style='background:#f5f3ff;color:#7c3aed;border:1px solid #c4b5fd;padding:3px 8px;border-radius:20px;font-size:10px;font-weight:800;white-space:nowrap;'>✨ CUSTOM</span>");
                        if ($hasSpecialJob) return new HtmlString("<span style='background:#fff7ed;color:#c2410c;border:1px solid #fdba74;padding:3px 8px;border-radius:20px;font-size:10px;font-weight:800;white-space:nowrap;'>🛠️ SERVICE</span>");
                        return new HtmlString("<span style='background:#eff6ff;color:#1d4ed8;border:1px solid #93c5fd;padding:3px 8px;border-radius:20px;font-size:10px;font-weight:800;white-space:nowrap;'>💎 SALE</span>");
                    })
                    ->grow(false),
                TextColumn::make('customer_name_display')
                    ->label('Customer')
                    ->getStateUsing(fn($record) => $record->customer ? trim($record->customer->name . ' ' . ($record->customer->last_name ?? '')) : '—')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('customer', fn($q) =>
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhereRaw("CONCAT(name, ' ', last_name) LIKE ?", ["%{$search}%"])
                        );
                    })
                    ->sortable(false),

                TextColumn::make('sales_person_list')->label('Sales Staff')->badge()->separator(',')->searchable(),

                TextColumn::make('items')
                    ->label('Sold Items')
                    ->listWithLineBreaks()
                    ->getStateUsing(function ($record) {
                        return $record->items->map(function ($item) {
                            if ($item->productItem) return $item->productItem->barcode . ' — ' . ($item->productItem->custom_description ?? 'Item');
                            if ($item->repair_id) return 'REPAIR: #' . ($item->repair->repair_no ?? 'SVC');
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
                    ->formatStateUsing(fn(string $state) => strtoupper(str_replace('_', ' ', $state))),

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
                        if ($paid == 0 && floatval($record->amount_paid) > 0) $paid = floatval($record->amount_paid);

                        $balance = max(0, $total - $paid);
                        $html  = "<div class='text-xs text-gray-500'>Bill Total: $" . number_format($total, 2) . "</div>";
                        $html .= "<div class='text-sm font-bold text-success-600'>Paid: $" . number_format($paid, 2) . "</div>";

                        if ($balance > 0.01) {
                            $html .= "<div class='text-sm font-bold text-danger-600'>Balance: $" . number_format($balance, 2) . "</div>";
                        } else {
                            $html .= "<div class='text-[10px] bg-success-100 text-success-700 px-1.5 py-0.5 rounded inline-block uppercase font-bold mt-1'>Fully Paid</div>";
                        }

                        return new HtmlString($html);
                    }),
                TextColumn::make('created_at')->label('Date')->dateTime('M d, y')->timezone(fn() => config('app.timezone'))->sortable()->grow(false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')->label('Payment Type')->options(fn() => self::getPaymentOptions()),
                Tables\Filters\TernaryFilter::make('has_trade_in')->label('Trade-Ins'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Sale Status')
                    ->options([
                        'completed'  => 'Completed',
                        'pending'    => 'Pending',
                        'inprogress' => 'In Progress',
                        'cancelled'  => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->url(fn(Sale $record) => SaleResource::getUrl('edit', ['record' => $record]))
                    ->visible(function (Sale $record) {
                        $user = Staff::user();
                        if ($user?->hasAnyRole(['Superadmin', 'Administration']) || auth()->user()->hasRole('Superadmin')) return true;
                        if ($record->payment_method === 'laybuy') return true;
                        $record->loadMissing('items');
                        if ($record->items->contains(fn($i) => !empty($i->custom_order_id))) return true;
                        if ($record->status === 'pending') return true;
                        if ($record->status === 'completed' && floatval($record->balance_due) > 0) return true;

                        $dayClosed = DailyClosing::whereDate('closing_date', $record->created_at->format('Y-m-d'))->exists();
                        if (!$dayClosed) return true;

                        return SaleEditRequest::where('sale_id', $record->id)
                            ->where('user_id', auth()->id())
                            ->where('status', 'approved')
                            ->exists();
                    }),

                TableAction::make('request_edit')
                    ->label('Request Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(function (Sale $record) {
                        $user = Staff::user();
                        if ($user?->hasAnyRole(['Superadmin', 'Administration']) || auth()->user()->hasRole('Superadmin')) return false;
                        if ($record->payment_method === 'laybuy') return false;
                        $record->loadMissing('items');
                        if ($record->items->contains(fn($i) => !empty($i->custom_order_id))) return false;
                        if ($record->status !== 'completed') return false;
                        if (floatval($record->balance_due) > 0) return false;

                        $dayClosed = DailyClosing::whereDate('closing_date', $record->created_at->format('Y-m-d'))->exists();
                        if (!$dayClosed) return false;

                        return !SaleEditRequest::where('sale_id', $record->id)
                            ->where('user_id', auth()->id())
                            ->whereIn('status', ['pending', 'approved'])
                            ->exists();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Request Edit Approval')
                    ->modalDescription('This sale\'s day is EOD locked. Your request will be sent to Administration for approval.')
                    ->modalSubmitActionLabel('Send Request')
                    ->action(function (Sale $record) {
                        \App\Models\SaleEditRequest::create([
                            'sale_id'          => $record->id,
                            'user_id'          => auth()->id(),
                            'status'           => 'pending',
                            'proposed_changes' => [],
                        ]);

                        $admins = \App\Models\User::role(['Superadmin', 'Administration'])->get();
                        foreach ($admins as $admin) {
                            Notification::make()
                                ->title('Edit Request')
                                ->body(auth()->user()->name . ' requested to edit ' . $record->invoice_number)
                                ->warning()
                                ->sendToDatabase($admin);
                        }

                        Notification::make()->title('Request Sent')->body('Your edit request has been sent to Administration for approval.')->success()->send();
                    }),

                TableAction::make('edit_pending')
                    ->label('Edit Pending...')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->disabled()
                    ->visible(function (Sale $record) {
                        $user = Staff::user();
                        if ($user?->hasAnyRole(['Superadmin', 'Administration']) || auth()->user()->hasRole('Superadmin')) return false;

                        return SaleEditRequest::where('sale_id', $record->id)
                            ->where('user_id', auth()->id())
                            ->where('status', 'pending')
                            ->exists();
                    }),

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('printStandard')
                        ->label('Print Standard Receipt')
                        ->icon('heroicon-o-printer')
                        ->url(fn(Sale $record) => route('sales.receipt', ['record' => $record, 'type' => 'standard']))
                        ->openUrlInNewTab()
                        ->extraAttributes(['onclick' => "setTimeout(() => { let win = window.open(this.href, '_blank'); win.onload = function() { win.print(); } }, 100); return false;"]),

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

                    TableAction::make('emailReceipt')
                        ->label('Email to Customer')
                        ->icon('heroicon-o-envelope')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function (Sale $record) {
                            if (!$record->customer || empty($record->customer->email)) {
                                Notification::make()->title('Email Missing')->body('Customer has no email address.')->danger()->send();
                                return;
                            }
                            $mailable = new \App\Mail\CustomerReceipt($record);
                            $sent     = $mailable->sendDirectly();
                            if ($sent) {
                                Notification::make()->title('Receipt Sent')->body("Successfully emailed to {$record->customer->email}")->success()->send();
                            } else {
                                Notification::make()->title('Email Error')->body('Check Laravel logs for SES details.')->danger()->send();
                            }
                        }),

                    TableAction::make('smsReceipt')
                        ->label('Send via SMS')
                        ->icon('heroicon-o-device-phone-mobile')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Send Receipt via SMS')
                        ->action(function (Sale $record) {
                            $phone = $record->customer->phone ?? null;
                            if (empty($phone)) {
                                Notification::make()->title('Error')->body('Customer has no phone number.')->danger()->send();
                                return;
                            }
                            $digits = preg_replace('/[^0-9]/', '', $phone);
                            if (\Illuminate\Support\Str::startsWith($digits, '1') && strlen($digits) === 11) {
                                $digits = substr($digits, 1);
                            }
                            $formattedPhone = '+1' . $digits;
                            $store          = $record->store;
                            $baseUrl        = $store && !empty($store->domain_url) ? rtrim($store->domain_url, '/') : config('app.url');
                            $link      = $baseUrl . '/receipt/' . $record->id;
                            $storeName = $store->name ?? 'Diamond Square';
                            $message   = "Hi {$record->customer->name}, thanks for visiting {$storeName}! View your receipt here: {$link}";
                            try {
                                $settings = \Illuminate\Support\Facades\DB::table('site_settings')->pluck('value', 'key');
                                $sns      = new \Aws\Sns\SnsClient([
                                    'version'     => 'latest',
                                    'region'      => $settings['aws_sms_default_region'] ?? config('services.sns.region'),
                                    'credentials' => [
                                        'key'    => $settings['aws_sms_access_key_id'] ?? config('services.sns.key'),
                                        'secret' => $settings['aws_sms_secret_access_key'] ?? config('services.sns.secret'),
                                    ],
                                ]);
                                $sns->publish([
                                    'Message'     => $message,
                                    'PhoneNumber' => $formattedPhone,
                                    'MessageAttributes' => [
                                        'OriginationNumber' => [
                                            'DataType'    => 'String',
                                            'StringValue' => $settings['aws_sns_sms_from'] ?? config('services.sns.sms_from'),
                                        ],
                                    ],
                                ]);
                                Notification::make()->title('SMS Sent')->success()->send();
                            } catch (\Exception $e) {
                                Notification::make()->title('SMS Failed')->body($e->getMessage())->danger()->send();
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

    public static function getServiceTypeOptions(): array
    {
        $defaultTypes = ['Resize', 'Solder / Weld', 'Bail Change', 'Shortening', 'Stone Setting', 'Engraving', 'Polishing / Rhodium'];
        $json  = DB::table('site_settings')->where('key', 'service_types')->value('value');
        $types = $json ? json_decode($json, true) : $defaultTypes;
        return collect($types)->filter()->mapWithKeys(fn($type) => [$type => $type])->toArray();
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
            if (!empty($item['sale_price_override']) && floatval($item['sale_price_override']) > 0) {
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

        $totalTax       = $taxableBasis * $taxRate;
        $warrantyCharge = ($get('has_warranty') == 1) ? floatval($get('warranty_charge') ?? 0) : 0;
        
        // FIX: The deposit does NOT lower the Grand Total of the invoice. It is a payment.
        $grandTotal = ($itemsSubtotal + $shipping + $totalTax + $warrantyCharge) - $tradeIn;

        $set('subtotal',    number_format($itemsSubtotal, 2, '.', ''));
        $set('tax_amount',  number_format($totalTax,      2, '.', ''));
        $set('final_total', number_format($grandTotal,    2, '.', ''));

        $isSplit = $get('is_split_payment');

        if ($isSplit) {
            $payments   = $get('split_payments') ?? [];
            $amountPaid = collect($payments)->sum(fn($p) => (float)($p['amount'] ?? 0));
        } else {
            $amountPaid = floatval($get('amount_paid') ?? 0);
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
            if (round($total, 2) <= 0) {
                $fullyPaid = true;
            } else {
                $fullyPaid = $tendered > 0 && round($tendered, 2) >= round($total, 2);
            }
        }

        $set('status', $fullyPaid ? 'completed' : 'pending');
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