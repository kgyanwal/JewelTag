<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExchangeResource\Pages;
use App\Models\Exchange;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\ProductItem;
use App\Models\CustomOrder;
use App\Forms\Components\CustomDatePicker;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Filament\Forms\Components\Actions\Action as FormAction;

class ExchangeResource extends Resource
{
    protected static ?string $model = Exchange::class;
    protected static ?string $navigationIcon  = 'heroicon-o-arrow-path-rounded-square';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'Exchanges';
    protected static ?int    $navigationSort  = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([

            // ── BANNER: status ──────────────────────────────────────────
            Forms\Components\Placeholder::make('status_banner')
                ->hiddenLabel()
                ->columnSpanFull()
                ->content(function ($record) {
                    if (!$record) return '';
                    $colors = [
                        'pending_approval' => ['bg' => '#fffbeb', 'border' => '#f59e0b', 'text' => '#92400e', 'icon' => '⏳'],
                        'approved'         => ['bg' => '#eff6ff', 'border' => '#3b82f6', 'text' => '#1e40af', 'icon' => '✅'],
                        'completed'        => ['bg' => '#f0fdf4', 'border' => '#22c55e', 'text' => '#166534', 'icon' => '🎉'],
                        'rejected'         => ['bg' => '#fef2f2', 'border' => '#ef4444', 'text' => '#991b1b', 'icon' => '❌'],
                        'cancelled'        => ['bg' => '#f9fafb', 'border' => '#9ca3af', 'text' => '#374151', 'icon' => '🚫'],
                    ];
                    $c = $colors[$record->status] ?? $colors['pending_approval'];
                    return new HtmlString("
                        <div style='background:{$c['bg']};border:1.5px solid {$c['border']};border-left:5px solid {$c['border']};border-radius:8px;padding:12px 16px;'>
                            <div style='font-size:13px;font-weight:800;color:{$c['text']};'>{$c['icon']} Exchange #{$record->exchange_no} — " . ucfirst(str_replace('_', ' ', $record->status)) . "</div>
                        </div>
                    ");
                }),

            Forms\Components\Grid::make(12)->schema([

                Forms\Components\Group::make()->columnSpan(8)->schema([

                    // ── SECTION 1: Customer & Original Sale ─────────────────
                    Forms\Components\Section::make('Customer & Original Sale')
                        ->icon('heroicon-o-shopping-bag')
                        ->schema([
                            Forms\Components\Select::make('customer_id')
                                ->label('Customer')
                                ->relationship('customer', 'name')
                                ->getOptionLabelFromRecordUsing(fn($record) =>
                                    trim($record->name . ' ' . ($record->last_name ?? '')) .
                                    ($record->phone ? ' — ' . $record->phone : '')
                                )
                                ->searchable(['name', 'last_name', 'phone'])
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(fn(Set $set) => $set('original_sale_id', null)),

                            Forms\Components\Select::make('original_sale_id')
                                ->label('Original Sale (Invoice)')
                                ->options(function (Get $get) {
                                    $customerId = $get('customer_id');
                                    if (!$customerId) return [];
                                    return Sale::where('customer_id', $customerId)
                                        ->where('status', 'completed')
                                        ->orderByDesc('completed_at')
                                        ->get()
                                        ->mapWithKeys(fn($s) =>
                                            [$s->id => $s->invoice_number . ' — $' . number_format($s->final_total, 2) . ' (' . ($s->completed_at ? \Carbon\Carbon::parse($s->completed_at)->format('M d, Y') : 'N/A') . ')']
                                        );
                                })
                                ->searchable()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    if (!$state) return;
                                    $sale = Sale::with(['items.productItem'])->find($state);
                                    if (!$sale) return;
                                    $items = $sale->items->where('is_non_stock', false)->whereNotNull('product_item_id')->map(fn($si) => [
                                        'sale_item_id'    => $si->id,
                                        'product_item_id' => $si->product_item_id,
                                        'description'     => $si->productItem?->custom_description ?? $si->custom_description ?? 'Item',
                                        'stock_no'        => $si->productItem?->barcode ?? '—',
                                        'original_price'  => floatval($si->sale_price_override ?? ($si->sold_price * $si->qty)),
                                        'credit_amount'   => floatval($si->sale_price_override ?? ($si->sold_price * $si->qty)),
                                        'returning'       => true,
                                    ])->values()->toArray();
                                    $set('returned_items_form', $items);
                                    self::updateExchangeTotals($get, $set);
                                })
                                ->helperText('Only completed sales shown'),
                        ])->columns(2),

                    // ── SECTION 2: Items Being Returned ─────────────────────
                    Forms\Components\Section::make('Items Being Returned')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->description('Select which items the customer is returning and set the credit amount for each')
                        ->schema([
                            Forms\Components\Repeater::make('returned_items_form')
                                ->label('Returned Items')
                                ->schema([
                                    Forms\Components\Toggle::make('returning')->label('Include')->default(true)->live()->inline(false),
                                    Forms\Components\TextInput::make('stock_no')->label('Stock #')->disabled(),
                                    Forms\Components\TextInput::make('description')->label('Description')->disabled()->columnSpan(2),
                                    Forms\Components\TextInput::make('original_price')->label('Original Price')->prefix('$')->disabled(),
                                    Forms\Components\TextInput::make('credit_amount')
                                        ->label('Credit Amount ($)')
                                        ->prefix('$')
                                        ->numeric()
                                        ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn(Get $get, Set $set) => self::updateExchangeTotals($get, $set))
                                        ->helperText('Staff can adjust — default is what customer paid'),
                                    Forms\Components\Hidden::make('sale_item_id'),
                                    Forms\Components\Hidden::make('product_item_id'),
                                ])
                                ->columns(3)
                                ->reorderable(false)
                                ->deletable(false)
                                ->addable(false)
                                ->live()
                                ->afterStateUpdated(fn(Get $get, Set $set) => self::updateExchangeTotals($get, $set))
                                ->itemLabel(fn(array $state) => ($state['stock_no'] ?? '?') . ' — ' . Str::limit($state['description'] ?? '', 40)),
                        ]),

                    // ── SECTION 3: New Item(s) Being Taken ───────────────────
                    Forms\Components\Section::make('New Item(s) Being Taken')
                        ->icon('heroicon-o-shopping-cart')
                        ->description('Search stock, add a non-tag item, or create/link a custom order — same as Quick Sale')
                        ->headerActions([])
                        ->schema([

                            // ── Quick add row: stock search + qty ───────────
                            Forms\Components\Grid::make(4)->schema([
                                Forms\Components\Select::make('current_stock_search')
                                    ->label('Select Stock #')
                                    ->placeholder('Search barcode / description...')
                                    ->searchable()
                                    ->dehydrated(false)
                                    ->getSearchResultsUsing(fn(string $search) => ProductItem::where('status', 'in_stock')
                                        ->where(function ($q) use ($search) {
                                            $q->where('barcode', 'like', "%{$search}%")
                                                ->orWhere('custom_description', 'like', "%{$search}%");
                                        })
                                        ->limit(30)
                                        ->get()
                                        ->mapWithKeys(fn($i) => [$i->id => "{$i->barcode} — {$i->custom_description} (\${$i->retail_price})"]))
                                    ->live()
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        if (!$state) return;
                                        $item = ProductItem::find($state);
                                        if (!$item) return;
                                        $qty = $get('current_stock_qty') ?? 1;

                                        $rows = $get('new_items_form') ?? [];
                                        $rows[] = [
                                            'selection_type'   => 'stock',
                                            'product_item_id'  => $item->id,
                                            'stock_no_display' => $item->barcode,
                                            'description'       => $item->custom_description ?? $item->barcode,
                                            'qty'               => $qty,
                                            'price'             => $item->retail_price,
                                            'discount_percent'  => 0,
                                            'discount_amount'   => 0,
                                            'sale_price'        => $item->retail_price * $qty,
                                            'is_tax_free'       => false,
                                        ];
                                        $set('new_items_form', $rows);
                                        $set('current_stock_search', null);
                                        $set('current_stock_qty', 1);
                                        self::updateExchangeTotals($get, $set);
                                    })
                                    ->columnSpan(3),

                                Forms\Components\TextInput::make('current_stock_qty')
                                    ->label('Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->dehydrated(false),
                            ]),

                            // ── Quick add buttons: Non-Tag / New Custom Order ──
                            Forms\Components\Actions::make([

                                FormAction::make('add_non_tag_item')
                                    ->label('+ Non-Tag Item')
                                    ->color('gray')
                                    ->outlined()
                                    ->icon('heroicon-o-plus-circle')
                                    ->modalHeading('Add Non-Tag Item')
                                    ->modalWidth('lg')
                                    ->modalSubmitActionLabel('Add to Exchange')
                                    ->form([
                                        Forms\Components\TextInput::make('description')
                                            ->label('Description')
                                            ->required()
                                            ->placeholder('e.g. Engraving, Cleaning Fee, Labour Charge'),
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('price')->label('Unit Price')->numeric()->prefix('$')->required()->default(0),
                                            Forms\Components\TextInput::make('qty')->label('Qty')->numeric()->required()->default(1),
                                        ]),
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('discount_percent')->label('Discount %')->numeric()->suffix('%')->default(0),
                                            Forms\Components\Toggle::make('is_tax_free')->label('Tax Free?')->default(false)->inline(false),
                                        ]),
                                    ])
                                    ->action(function (array $data, Get $get, Set $set) {
                                        $price     = floatval($data['price'] ?? 0);
                                        $qty       = intval($data['qty'] ?? 1);
                                        $discPct   = floatval($data['discount_percent'] ?? 0);
                                        $lineTotal = $price * $qty;
                                        $discAmt   = $lineTotal * ($discPct / 100);

                                        $rows = $get('new_items_form') ?? [];
                                        $rows[] = [
                                            'selection_type'   => 'non_tag',
                                            'description'      => $data['description'],
                                            'qty'               => $qty,
                                            'price'             => $price,
                                            'discount_percent'  => $discPct,
                                            'discount_amount'   => $discAmt,
                                            'sale_price'        => $lineTotal - $discAmt,
                                            'is_tax_free'       => $data['is_tax_free'] ?? false,
                                        ];
                                        $set('new_items_form', $rows);
                                        self::updateExchangeTotals($get, $set);
                                    }),

                                // ── + New Custom Order: creates a REAL CustomOrder record now ──
                                FormAction::make('create_new_custom_order')
                                    ->label('+ New Custom Order')
                                    ->color('success')
                                    ->outlined()
                                    ->icon('heroicon-o-paint-brush')
                                    ->modalHeading('Design New Custom Piece')
                                    ->modalWidth('3xl')
                                    ->modalSubmitActionLabel('Create & Add to Exchange')
                                    ->form([
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\Select::make('product_name')
                                                ->label('What are we making?')
                                                ->options(fn() => CustomOrder::whereNotNull('product_name')->pluck('product_name', 'product_name')->unique())
                                                ->searchable()
                                                ->createOptionForm([Forms\Components\TextInput::make('new_product_name')->required()->label('New Product Name')])
                                                ->createOptionUsing(fn($data) => $data['new_product_name'])
                                                ->required(),
                                            Forms\Components\Select::make('metal_type')
                                                ->label('Metal Type')
                                                ->options(['10k' => '10k Gold', '14k' => '14k Gold', '18k' => '18k Gold', 'platinum' => 'Platinum', 'silver' => 'Silver', 'other' => 'Other'])
                                                ->default('14k')
                                                ->required(),
                                        ]),
                                        Forms\Components\Grid::make(3)->schema([
                                            Forms\Components\TextInput::make('quoted_price')->label('Total Item Price')->numeric()->prefix('$')->required()->live(onBlur: true),
                                            Forms\Components\TextInput::make('discount_percent')->label('Disc %')->numeric()->suffix('%')->default(0)->live(onBlur: true),
                                            Forms\Components\TextInput::make('custom_deposit_amount')->label('Deposit Toward Difference ($)')->numeric()->prefix('$')->default(0)
                                                ->helperText('Optional — if the customer pays part of the difference now'),
                                        ]),
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\Select::make('deposit_method')
                                                ->label('Deposit Payment Method')
                                                ->options(fn() => SaleResource::getPaymentOptions())
                                                ->default('VISA'),
                                            Forms\Components\Toggle::make('is_tax_free')->label('Tax Free Order?')->default(false)->inline(false),
                                        ]),
                                       Forms\Components\DatePicker::make('due_date')
    ->label('Promised By Date')
    ->displayFormat('m/d/Y')
    ->native(false) // Gives you the premium calendar dropdown style
    ->required(),
                                        Forms\Components\Textarea::make('design_notes')->label('Design Notes & Instructions')->rows(2),
                                    ])
                                    ->action(function (array $data, Get $get, Set $set) {
                                        $price   = floatval($data['quoted_price']);
                                        $discPct = min(100, max(0, floatval($data['discount_percent'] ?? 0)));
                                        $discAmt = $price * $discPct / 100;
                                        $finalPrice = max(0, $price - $discAmt);
                                        $isTaxFree = $data['is_tax_free'] ?? false;

                                        // 🚀 Create the REAL custom order record now — shows up immediately in Custom Order Resource
                                        $customOrder = CustomOrder::create([
                                            'customer_id'      => $get('customer_id'),
                                            'staff_id'         => auth()->id(),
                                            'order_type'       => 'custom',
                                            'product_name'     => $data['product_name'],
                                            'metal_type'       => $data['metal_type'],
                                            'quoted_price'     => $price,
                                            'discount_percent' => $discPct,
                                            'discount_amount'  => $discAmt,
                                            'due_date'         => $data['due_date'] ?? null,
                                            'design_notes'     => $data['design_notes'] ?? null,
                                            'status'           => 'in_production',
                                            'is_tax_free'      => $isTaxFree,
                                            'amount_paid'      => 0,
                                            'balance_due'      => $finalPrice,
                                            'items'            => [[
                                                'product_name' => $data['product_name'],
                                                'metal_type'   => $data['metal_type'],
                                                'quoted_price' => $price,
                                                'design_notes' => $data['design_notes'] ?? null,
                                                'is_tax_free'  => $isTaxFree,
                                            ]],
                                        ]);

                                        $rows = $get('new_items_form') ?? [];
                                        $rows[] = [
                                            'selection_type'  => 'custom_order',
                                            'custom_order_id' => $customOrder->id,
                                            'description'      => "Custom {$customOrder->product_name} (#{$customOrder->id})",
                                            'qty'               => 1,
                                            'price'             => $price,
                                            'discount_percent'  => $discPct,
                                            'discount_amount'   => $discAmt,
                                            'sale_price'        => $finalPrice,
                                            'is_tax_free'       => $isTaxFree,
                                        ];
                                        $set('new_items_form', $rows);

                                        // Record deposit toward the difference, if any
                                        $deposit = floatval($data['custom_deposit_amount'] ?? 0);
                                        if ($deposit > 0) {
                                            \App\Models\Payment::create([
                                                'custom_order_id' => $customOrder->id,
                                                'amount'          => $deposit,
                                                'method'          => strtoupper($data['deposit_method'] ?? 'CASH'),
                                                'paid_at'         => now(),
                                                'store_id'        => auth()->user()->store_id ?? \App\Models\Store::first()?->id ?? 1,
                                            ]);
                                            $customOrder->update([
                                                'amount_paid' => $deposit,
                                                'balance_due' => max(0, $finalPrice - $deposit),
                                            ]);

                                            $set('is_split_payment', true);
                                            $splits = $get('split_payments') ?? [];
                                            $splits[] = ['method' => $data['deposit_method'] ?? 'CASH', 'amount' => $deposit];
                                            $set('split_payments', $splits);

                                            Notification::make()
                                                ->title('Custom Order Created')
                                                ->body("Order #{$customOrder->id} created with a \$" . number_format($deposit, 2) . ' deposit recorded.')
                                                ->success()
                                                ->send();
                                        } else {
                                            Notification::make()
                                                ->title('Custom Order Created')
                                                ->body("Order #{$customOrder->id} created and added to this exchange.")
                                                ->success()
                                                ->send();
                                        }

                                        self::updateExchangeTotals($get, $set);
                                    }),

                                // ── Link an EXISTING custom order instead of creating new ──
                                FormAction::make('link_existing_custom_order')
                                    ->label('🔗 Link Existing Custom Order')
                                    ->color('info')
                                    ->outlined()
                                    ->icon('heroicon-o-link')
                                    ->modalWidth('lg')
                                    ->form([
                                        Forms\Components\Select::make('custom_order_id')
                                            ->label('Select Custom Order')
                                            ->options(function (Get $get) {
                                                $customerId = $get('../../customer_id');
                                                if (!$customerId) return [];
                                                return CustomOrder::where('customer_id', $customerId)
                                                    ->whereIn('status', ['pending', 'in_production'])
                                                    ->get()
                                                    ->mapWithKeys(fn($c) => [$c->id => "#{$c->id} — {$c->product_name} (Bal: \$" . number_format($c->balance_due, 2) . ')']);
                                            })
                                            ->required()
                                            ->searchable(),
                                    ])
                                    ->action(function (array $data, Get $get, Set $set) {
                                        $co = CustomOrder::find($data['custom_order_id']);
                                        if (!$co) return;
                                        $finalPrice = max(0, floatval($co->quoted_price) - floatval($co->discount_amount ?? 0));

                                        $rows = $get('new_items_form') ?? [];
                                        $rows[] = [
                                            'selection_type'  => 'custom_order',
                                            'custom_order_id' => $co->id,
                                            'description'      => "Custom {$co->product_name} (#{$co->id})",
                                            'qty'               => 1,
                                            'price'             => $co->quoted_price,
                                            'discount_percent'  => $co->discount_percent ?? 0,
                                            'discount_amount'   => $co->discount_amount ?? 0,
                                            'sale_price'        => $finalPrice,
                                            'is_tax_free'       => (bool) $co->is_tax_free,
                                        ];
                                        $set('new_items_form', $rows);
                                        self::updateExchangeTotals($get, $set);
                                    }),
                            ]),

                            // ── Repeater showing added rows (editable discount/qty) ──
                            Forms\Components\Repeater::make('new_items_form')
                                ->label('Items in this Exchange')
                                ->schema([
                                    Forms\Components\Hidden::make('selection_type'),
                                    Forms\Components\Hidden::make('product_item_id'),
                                    Forms\Components\Hidden::make('custom_order_id'),
                                    Forms\Components\Hidden::make('stock_no_display'),

                                    Forms\Components\TextInput::make('description')
                                        ->label('Item')
                                        ->columnSpan(3)
                                        ->readOnly(fn(Get $get) => $get('selection_type') === 'custom_order'),

                                    Forms\Components\TextInput::make('qty')
                                        ->numeric()
                                        ->default(1)
                                        ->live(onBlur: true)
                                        ->readOnly(fn(Get $get) => $get('selection_type') === 'custom_order')
                                        ->afterStateUpdated(fn(Get $get, Set $set) => self::recalcLine($get, $set)),

                                    Forms\Components\TextInput::make('price')
                                        ->label('Unit Price')
                                        ->numeric()
                                        ->prefix('$')
                                        ->live(onBlur: true)
                                        ->readOnly(fn(Get $get) => $get('selection_type') === 'custom_order')
                                        ->afterStateUpdated(fn(Get $get, Set $set) => self::recalcLine($get, $set)),

                                    Forms\Components\TextInput::make('discount_percent')
                                        ->label('Disc %')
                                        ->numeric()
                                        ->suffix('%')
                                        ->default(0)
                                        ->live(onBlur: true)
                                        ->readOnly(fn(Get $get) => $get('selection_type') === 'custom_order')
                                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                            $price = floatval($get('price') ?? 0);
                                            $qty   = intval($get('qty') ?? 1);
                                            $line  = $price * $qty;
                                            $pct   = min(100, max(0, floatval($state ?? 0)));
                                            $discAmt = $line * $pct / 100;
                                            $set('discount_amount', round($discAmt, 2));
                                            $set('sale_price', round($line - $discAmt, 2));
                                            self::updateExchangeTotals($get, $set);
                                        }),

                                    Forms\Components\TextInput::make('discount_amount')
                                        ->label('Disc $')
                                        ->numeric()
                                        ->prefix('$')
                                        ->default(0)
                                        ->live(onBlur: true)
                                        ->readOnly(fn(Get $get) => $get('selection_type') === 'custom_order')
                                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                            $price = floatval($get('price') ?? 0);
                                            $qty   = intval($get('qty') ?? 1);
                                            $line  = $price * $qty;
                                            $discAmt = min($line, max(0, floatval($state ?? 0)));
                                            $pct = $line > 0 ? round(($discAmt / $line) * 100, 2) : 0;
                                            $set('discount_percent', $pct);
                                            $set('sale_price', round($line - $discAmt, 2));
                                            self::updateExchangeTotals($get, $set);
                                        }),

                                    Forms\Components\TextInput::make('sale_price')
                                        ->label('Sale Price (final)')
                                        ->numeric()
                                        ->prefix('$')
                                        ->live(onBlur: true)
                                        ->readOnly(fn(Get $get) => $get('selection_type') === 'custom_order')
                                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                            $price = floatval($get('price') ?? 0);
                                            $qty   = intval($get('qty') ?? 1);
                                            $line  = $price * $qty;
                                            $final = min($line, max(0, floatval($state ?? 0)));
                                            $discAmt = $line - $final;
                                            $pct = $line > 0 ? round(($discAmt / $line) * 100, 2) : 0;
                                            $set('discount_amount', round($discAmt, 2));
                                            $set('discount_percent', $pct);
                                            self::updateExchangeTotals($get, $set);
                                        }),

                                    Forms\Components\Checkbox::make('is_tax_free')
                                        ->label('No Tax')
                                        ->disabled(fn(Get $get) => $get('selection_type') === 'custom_order')
                                        ->live()
                                        ->afterStateUpdated(fn(Get $get, Set $set) => self::updateExchangeTotals($get, $set)),
                                ])
                                ->columns(4)
                                ->live()
                                ->afterStateUpdated(fn(Get $get, Set $set) => self::updateExchangeTotals($get, $set))
                                ->addable(false)
                                ->reorderable(false)
                                ->itemLabel(fn(array $state) => strtoupper($state['selection_type'] ?? 'item') . ' — ' . Str::limit($state['description'] ?? '', 30)),
                        ]),

                    // ── SECTION 4: Reason & Notes ────────────────────────────
                    Forms\Components\Section::make('Reason & Notes')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->schema([
                            Forms\Components\Textarea::make('reason')
                                ->label("Customer's Reason for Exchange")
                                ->required()
                                ->rows(2)
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('staff_notes')
                                ->label('Internal Staff Notes')
                                ->rows(2)
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('rejection_reason')
                                ->label('Rejection Reason (if rejected)')
                                ->rows(2)
                                ->columnSpanFull()
                                ->visible(fn($record) => $record?->status === 'rejected'),
                        ]),
                ]),

                // ── RIGHT COLUMN: Financials & Payment ──────────────────────
                Forms\Components\Group::make()->columnSpan(4)->schema([

                    Forms\Components\Section::make('Exchange Summary')
                        ->icon('heroicon-o-arrows-right-left')
                        ->schema([
                            Forms\Components\TextInput::make('total_credit')
                                ->label('Total Credit')
                                ->prefix('$')
                                ->readOnly()
                                ->extraInputAttributes(['class' => 'font-bold text-danger-600']),

                            Forms\Components\TextInput::make('new_sale_amount')
                                ->label('New Item(s) Total (incl. tax)')
                                ->prefix('$')
                                ->readOnly()
                                ->extraInputAttributes(['class' => 'font-bold text-success-600']),

                            Forms\Components\Select::make('exchange_type')
                                ->label('Exchange Type')
                                ->options([
                                    'same_value' => '↔️ Same Value',
                                    'upgrade'    => '⬆️ Upgrade',
                                    'downgrade'  => '⬇️ Downgrade',
                                ])
                                ->disabled()
                                ->dehydrated(true),

                            Forms\Components\TextInput::make('difference_amount')
                                ->label('Difference')
                                ->prefix('$')
                                ->readOnly()
                                ->helperText('Positive = customer pays, Negative = store refunds'),
                        ]),

                    Forms\Components\Section::make('Payment for Difference')
                        ->icon('heroicon-o-credit-card')
                        ->visible(fn(Get $get) => abs(floatval($get('difference_amount') ?? 0)) > 0.009)
                        ->schema([
                            Forms\Components\Toggle::make('is_split_payment')
                                ->label('Split Payment')
                                ->live()
                                ->columnSpanFull(),

                            Forms\Components\Select::make('difference_payment_method')
                                ->label('Payment Method')
                                ->options(fn() => SaleResource::getPaymentOptions() + ['STORE_CREDIT' => 'Store Credit', 'N/A' => 'N/A'])
                                ->visible(fn(Get $get) => !$get('is_split_payment'))
                                ->required(fn(Get $get) => !$get('is_split_payment') && floatval($get('difference_amount') ?? 0) > 0),

                            Forms\Components\Repeater::make('split_payments')
                                ->label('Payment Breakdown')
                                ->visible(fn(Get $get) => $get('is_split_payment'))
                                ->schema([
                                    Forms\Components\Grid::make(2)->schema([
                                        Forms\Components\Select::make('method')
                                            ->options(fn() => SaleResource::getPaymentOptions())
                                            ->required(),
                                        Forms\Components\TextInput::make('amount')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required(),
                                    ]),
                                ])
                                ->addActionLabel('+ Add Payment Method')
                                ->reorderable(false)
                                ->defaultItems(1),
                        ]),

                    Forms\Components\Section::make('Link New Sale')
                        ->schema([
                            Forms\Components\Select::make('new_sale_id')
                                ->label('New Sale (auto-created on complete)')
                                ->options(fn(Get $get) =>
                                    Sale::where('customer_id', $get('customer_id'))
                                        ->where('status', 'completed')
                                        ->whereDate('created_at', '>=', now()->subDays(7))
                                        ->orderByDesc('created_at')
                                        ->pluck('invoice_number', 'id')
                                )
                                ->searchable()
                                ->nullable()
                                ->helperText('Leave blank — a Sale record is created automatically when the exchange is approved and completed.'),
                        ]),
                ]),
            ]),

        ]);
    }

    /**
     * Recalculate a single new_items_form row's discount/sale_price when qty or price changes.
     */
    protected static function recalcLine(Get $get, Set $set): void
    {
        $price = floatval($get('price') ?? 0);
        $qty   = intval($get('qty') ?? 1);
        $line  = $price * $qty;
        $pct   = floatval($get('discount_percent') ?? 0);
        $discAmt = $line * $pct / 100;
        $set('discount_amount', round($discAmt, 2));
        $set('sale_price', round($line - $discAmt, 2));
        self::updateExchangeTotals($get, $set);
    }

    // ── TOTALS ENGINE ──────────────────────────────────────────────────────
    public static function updateExchangeTotals(Get $get, Set $set): void
    {
        $returned = collect($get('returned_items_form') ?? []);
        $totalCredit = $returned->filter(fn($i) => !empty($i['returning']))->sum(fn($i) => floatval($i['credit_amount'] ?? 0));

        $newItems = collect($get('new_items_form') ?? []);
        $dbTax   = DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? 7.63;
        $taxRate = floatval($dbTax) / 100;

        $newSubtotal = 0;
        $newTax = 0;
        foreach ($newItems as $item) {
            $lineTotal = floatval($item['sale_price'] ?? 0);
            $newSubtotal += $lineTotal;
            if (empty($item['is_tax_free'])) {
                $newTax += $lineTotal * $taxRate;
            }
        }

        $newTotal = $newSubtotal + $newTax;
        $difference = $newTotal - $totalCredit;

        $set('total_credit', number_format($totalCredit, 2, '.', ''));
        $set('new_sale_amount', number_format($newTotal, 2, '.', ''));
        $set('difference_amount', number_format($difference, 2, '.', ''));

        if ($difference > 0.009) {
            $set('exchange_type', 'upgrade');
        } elseif ($difference < -0.009) {
            $set('exchange_type', 'downgrade');
        } else {
            $set('exchange_type', 'same_value');
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('exchange_no')->label('Exchange #')->weight('bold')->color('primary')->searchable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->getStateUsing(fn($record) => trim(($record->customer?->name ?? '') . ' ' . ($record->customer?->last_name ?? '')))
                    ->searchable(['customers.name', 'customers.last_name'])
                    ->description(fn($record) => $record->customer?->phone ?? ''),
                Tables\Columns\TextColumn::make('originalSale.invoice_number')->label('Original Invoice')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('exchange_type')
                    ->label('Type')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'upgrade'    => '⬆️ Upgrade',
                        'downgrade'  => '⬇️ Downgrade',
                        'same_value' => '↔️ Same Value',
                        default      => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('total_credit')->label('Credit')->formatStateUsing(fn($state) => '$' . number_format($state, 2))->color('warning'),
                Tables\Columns\TextColumn::make('difference_amount')
                    ->label('Difference')
                    ->formatStateUsing(function ($state) {
                        $amt   = floatval($state);
                        $color = $amt > 0 ? '#059669' : ($amt < 0 ? '#B8463F' : '#64748b');
                        $label = $amt > 0 ? '+$' . number_format($amt, 2) : ($amt < 0 ? '-$' . number_format(abs($amt), 2) : '$0.00');
                        return new HtmlString("<span style='color:{$color};font-weight:700;'>{$label}</span>");
                    })->html(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn($record) => $record->getStatusColor())
                    ->formatStateUsing(fn($state) => ucfirst(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('requester.name')->label('By')->size('sm')->color('gray'),
                Tables\Columns\TextColumn::make('created_at')->label('Date')->date('M d, Y')->sortable()->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'pending_approval' => 'Pending Approval',
                    'approved'         => 'Approved',
                    'completed'        => 'Completed',
                    'rejected'         => 'Rejected',
                    'cancelled'        => 'Cancelled',
                ]),
                Tables\Filters\SelectFilter::make('exchange_type')->options([
                    'upgrade' => 'Upgrade', 'downgrade' => 'Downgrade', 'same_value' => 'Same Value',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'pending_approval' && auth()->user()->hasAnyRole(['Superadmin', 'Administration', 'Manager']))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
                        Notification::make()->title('Exchange Approved ✅')->success()->sendToDatabase($record->requester);
                        Notification::make()->title('Exchange Approved')->success()->send();
                    }),

                Tables\Actions\Action::make('complete')
                    ->label('Mark Complete')
                    ->icon('heroicon-o-flag')
                    ->color('info')
                    ->visible(fn($record) => $record->status === 'approved')
                    ->action(fn($record) => static::completeExchange($record)),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn($record) => $record->status === 'pending_approval' && auth()->user()->hasAnyRole(['Superadmin', 'Administration', 'Manager']))
                    ->form([Forms\Components\Textarea::make('rejection_reason')->required()->rows(2)])
                    ->action(function ($record, array $data) {
                        $record->update(['status' => 'rejected', 'approved_by' => auth()->id(), 'rejection_reason' => $data['rejection_reason']]);
                        Notification::make()->title('Exchange Rejected')->danger()->sendToDatabase($record->requester);
                        Notification::make()->title('Exchange Rejected')->warning()->send();
                    }),

                Tables\Actions\Action::make('print')
                    ->label('Print Receipt')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn($record) => route('exchange.print', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    /**
     * Core completion logic: creates the Sale (custom orders/deposits are already created
     * at add-time via the modals above), returns traded-in items to stock, deducts new
     * stock items from inventory, and links everything back to the exchange.
     */
    public static function completeExchange($record): void
    {
        DB::transaction(function () use ($record) {
            $newItemsRaw = $record->new_items ?? [];

            $saleItems = [];
            $newCustomOrderId = null;

            foreach ($newItemsRaw as $item) {
                if (($item['selection_type'] ?? null) === 'custom_order' && !empty($item['custom_order_id'])) {
                    $newCustomOrderId = $item['custom_order_id'];
                    $saleItems[] = [
                        'custom_order_id'     => $item['custom_order_id'],
                        'custom_description'  => $item['description'] ?? 'Custom Order',
                        'qty'                 => 1,
                        'sold_price'          => floatval($item['price'] ?? 0),
                        'sale_price_override' => floatval($item['sale_price'] ?? $item['price'] ?? 0),
                        'discount_percent'    => floatval($item['discount_percent'] ?? 0),
                        'discount_amount'     => floatval($item['discount_amount'] ?? 0),
                        'is_tax_free'         => (bool) ($item['is_tax_free'] ?? false),
                        'is_non_stock'        => false,
                    ];
                } elseif (($item['selection_type'] ?? null) === 'stock' && !empty($item['product_item_id'])) {
                    $saleItems[] = [
                        'product_item_id'     => $item['product_item_id'],
                        'custom_description'  => $item['description'] ?? null,
                        'qty'                 => intval($item['qty'] ?? 1),
                        'sold_price'          => floatval($item['price'] ?? 0),
                        'sale_price_override' => floatval($item['sale_price'] ?? $item['price'] ?? 0),
                        'discount_percent'    => floatval($item['discount_percent'] ?? 0),
                        'discount_amount'     => floatval($item['discount_amount'] ?? 0),
                        'is_tax_free'         => (bool) ($item['is_tax_free'] ?? false),
                        'is_non_stock'        => false,
                    ];
                } else {
                    $saleItems[] = [
                        'custom_description'  => $item['description'] ?? 'Non-Tag Item',
                        'qty'                 => intval($item['qty'] ?? 1),
                        'sold_price'          => floatval($item['price'] ?? 0),
                        'sale_price_override' => floatval($item['sale_price'] ?? $item['price'] ?? 0),
                        'discount_percent'    => floatval($item['discount_percent'] ?? 0),
                        'discount_amount'     => floatval($item['discount_amount'] ?? 0),
                        'is_tax_free'         => (bool) ($item['is_tax_free'] ?? false),
                        'is_non_stock'        => true,
                    ];
                }
            }

            $sale = Sale::create([
                'customer_id'        => $record->customer_id,
                'store_id'           => $record->store_id,
                'sales_person_list'  => [auth()->user()->name],
                'final_total'        => $record->new_sale_amount,
                'subtotal'           => $record->new_items_subtotal,
                'tax_amount'         => $record->new_items_tax,
                'status'             => 'completed',
                'payment_method'     => $record->is_split_payment ? 'split' : ($record->difference_payment_method ?? 'CASH'),
                'is_split_payment'   => (bool) $record->is_split_payment,
                'completed_at'       => now(),
                'invoice_number'     => 'EX-' . $record->exchange_no,
                'notes'              => 'Created from Exchange #' . $record->exchange_no,
            ]);

            foreach ($saleItems as $si) {
                $sale->items()->create($si);
            }

            // Record payment(s) for the difference (if customer owes money) — deposits taken
            // at custom-order-creation time are NOT re-charged here, only the remaining difference.
            $difference = floatval($record->difference_amount);
            if ($difference > 0.009) {
                if ($record->is_split_payment) {
                    foreach ($record->split_payments ?? [] as $p) {
                        if (floatval($p['amount'] ?? 0) <= 0) continue;
                        \App\Models\Payment::create([
                            'sale_id'  => $sale->id,
                            'amount'   => $p['amount'],
                            'method'   => strtoupper($p['method']),
                            'paid_at'  => now(),
                            'store_id' => $record->store_id,
                        ]);
                    }
                } else {
                    \App\Models\Payment::create([
                        'sale_id'  => $sale->id,
                        'amount'   => $difference,
                        'method'   => strtoupper($record->difference_payment_method ?? 'CASH'),
                        'paid_at'  => now(),
                        'store_id' => $record->store_id,
                    ]);
                }
            }

            // Return traded-in items to stock
            foreach ($record->returned_items ?? [] as $item) {
                if (!empty($item['returning']) && !empty($item['product_item_id'])) {
                    ProductItem::where('id', $item['product_item_id'])->update(['status' => 'in_stock']);
                }
            }

            // Deduct new stock items from inventory
            foreach ($saleItems as $si) {
                if (!empty($si['product_item_id'])) {
                    $pi = ProductItem::lockForUpdate()->find($si['product_item_id']);
                    if ($pi) {
                        $newQty = max(0, $pi->qty - intval($si['qty'] ?? 1));
                        $pi->update(['qty' => $newQty, 'status' => $newQty === 0 ? 'sold' : 'in_stock']);
                    }
                }
            }

            // Link the custom order to this sale so it shows in Custom Order Resource's history
            if ($newCustomOrderId) {
                CustomOrder::where('id', $newCustomOrderId)->update(['sale_id' => $sale->id]);
            }

            $record->update([
                'status'              => 'completed',
                'completed_at'        => now(),
                'new_sale_id'         => $sale->id,
                'new_custom_order_id' => $newCustomOrderId,
            ]);
        });

        Notification::make()
            ->title('Exchange Completed 🎉')
            ->body("Exchange {$record->exchange_no} completed. New sale created and inventory updated.")
            ->success()
            ->send();
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListExchanges::route('/'),
            'create' => Pages\CreateExchange::route('/create'),
            'edit'   => Pages\EditExchange::route('/{record}/edit'),
        ];
    }
}