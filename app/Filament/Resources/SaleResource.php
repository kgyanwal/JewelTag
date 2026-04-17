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
use Filament\Forms\Components\{Section, Grid, Group, Repeater, Select, TextInput, Placeholder, Hidden, Checkbox, Textarea, Toggle};
use App\Forms\Components\CustomDatePicker;
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

                                                // 1. Get the current items array (safely preserving what's already there)
                                                $currentItems = $get('items') ?? [];
                                                $qty          = $get('current_qty') ?? 1;

                                                // 2. Create a unique UUID for this new regular item
                                                $newItemId = (string) \Illuminate\Support\Str::uuid();

                                                // 3. Append the new regular item using the unique UUID as the key
                                                // This prevents it from wiping out the Custom Order, which also uses a UUID key!
                                                $currentItems[$newItemId] = [
                                                    'product_item_id'     => $item->id,
                                                    'repair_id'           => null,
                                                    'custom_order_id'     => null, // explicitly mark as not custom
                                                    'is_new_custom_order' => false, // explicitly mark as not custom
                                                    'stock_no_display'    => $item->barcode,
                                                    'custom_description'  => $item->custom_description ?? $item->barcode,
                                                    'qty'                 => $qty,
                                                    'sold_price'          => $item->retail_price,
                                                    'sale_price_override' => $item->retail_price * $qty,
                                                    'discount_percent'    => 0,
                                                    'discount_amount'     => 0,
                                                    'is_tax_free'         => false,
                                                ];

                                                // 4. Save the safely merged array back to the state
                                                $set('items', $currentItems);
                                                
                                                // 5. Reset the search box for the next scan
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
                                                        fn() => function (string $attribute, $value, $fail) {
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
                                                $price      = floatval($data['price'] ?? 0);
                                                $qty        = intval($data['qty'] ?? 1);
                                                $discPct    = floatval($data['discount_percent'] ?? 0);
                                                $lineTotal  = $price * $qty;
                                                $discAmt    = $lineTotal * ($discPct / 100);
                                                $finalPrice = $lineTotal - $discAmt;

                                                $currentItems   = $get('items') ?? [];
                                                $currentItems[] = [
                                                    'product_item_id'     => null,
                                                    'repair_id'           => null,
                                                    'custom_order_id'     => null,
                                                    'is_non_stock'        => true,
                                                    'stock_no_display'    => 'NON-TAG',
                                                    'custom_description'  => $data['description'],
                                                    'qty'                 => $qty,
                                                    'sold_price'          => $price,
                                                    'sale_price_override' => $finalPrice,
                                                    'discount_percent'    => $discPct,
                                                    'discount_amount'     => $discAmt,
                                                    'is_tax_free'         => $data['is_tax_free'] ?? false,
                                                ];

                                                $set('items', $currentItems);
                                                self::updateTotals($get, $set);
                                            }),

                                        // ── NEW CUSTOM ORDER ──────────────────────────────────────────
                                        FormAction::make('create_new_custom_order')
                                            ->label('+ New Custom Order')
                                            ->color('success')
                                            ->outlined()
                                            ->icon('heroicon-o-paint-brush')
                                            ->modalHeading('Design New Custom Piece')
                                            ->modalWidth('3xl')
                                            ->modalSubmitActionLabel('Add to Bill')
                                            ->form([
                                                // ── ROW 1: What & Metal ──────────────────────────────
                                                Grid::make(2)->schema([
                                                    Select::make('product_name')
                                                        ->label('What are we making?')
                                                        ->options(fn() => \App\Models\CustomOrder::whereNotNull('product_name')->pluck('product_name', 'product_name')->unique())
                                                        ->searchable()
                                                        ->createOptionForm([TextInput::make('new_product_name')->required()->label('New Product Name')])
                                                        ->createOptionUsing(fn($data) => $data['new_product_name'])
                                                        ->required(),
                                                    Select::make('metal_type')
                                                        ->label('Metal Type')
                                                        ->options(['10k' => '10k Gold', '14k' => '14k Gold', '18k' => '18k Gold', 'platinum' => 'Platinum', 'silver' => 'Silver', 'other' => 'Other'])
                                                        ->default('14k')
                                                        ->required(),
                                                ]),

                                                // ── ROW 2: Price + Tax Preview + Deposit ─────────────
                                                Grid::make(3)->schema([
                                                    TextInput::make('quoted_price')
                                                        ->label('Total Item Price')
                                                        ->numeric()
                                                        ->prefix('$')
                                                        ->required()
                                                        ->live(onBlur: true)
                                                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                            $price   = floatval($state ?? 0);
                                                            $discAmt = floatval($get('discount_amount') ?? 0);
                                                            $discAmt = min($price, $discAmt);
                                                            $pct     = $price > 0 ? round(($discAmt / $price) * 100, 2) : 0;
                                                            $final   = $price - $discAmt;
                                                            $set('discount_amount', round($discAmt, 2));
                                                            $set('discount_percent', $pct);
                                                            $set('sale_price_display', round($final, 2));
                                                        }),

                                                    Placeholder::make('total_with_tax')
                                                        ->label('Total (w/ Tax)')
                                                        ->content(function (Get $get) {
                                                            $quoted  = (float)($get('quoted_price') ?? 0);
                                                            $discAmt = min($quoted, max(0, (float)($get('discount_amount') ?? 0)));
                                                            $discounted = $quoted - $discAmt;
                                                            $taxRate = $get('is_tax_free') ? 0 : ((float)\Illuminate\Support\Facades\DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? 7.63) / 100;
                                                            return new HtmlString("<div class='text-2xl font-black text-gray-900 mt-1'>$" . number_format($discounted * (1 + $taxRate), 2) . "</div>");
                                                        }),

                                                    TextInput::make('custom_deposit_amount')
                                                        ->label('Deposit Amount')
                                                        ->numeric()
                                                        ->prefix('$')
                                                        ->default(0),
                                                ]),

                                                // ── ROW 3: Disc % + Disc $ + Sale Price ─────────────
                                                Grid::make(3)->schema([
                                                    TextInput::make('discount_percent')
                                                        ->label('Disc %')
                                                        ->numeric()
                                                        ->suffix('%')
                                                        ->default(0)
                                                        ->minValue(0)
                                                        ->maxValue(100)
                                                        ->live(onBlur: true)
                                                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                            $price   = floatval($get('quoted_price') ?? 0);
                                                            $pct     = min(100, max(0, floatval($state ?? 0)));
                                                            $discAmt = $price * $pct / 100;
                                                            $final   = $price - $discAmt;
                                                            $set('discount_amount', round($discAmt, 2));
                                                            $set('sale_price_display', round($final, 2));
                                                        }),

                                                    TextInput::make('discount_amount')
                                                        ->label('Disc $')
                                                        ->numeric()
                                                        ->prefix('$')
                                                        ->default(0)
                                                        ->live(onBlur: true)
                                                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                            $price   = floatval($get('quoted_price') ?? 0);
                                                            $discAmt = min($price, max(0, floatval($state ?? 0)));
                                                            $pct     = $price > 0 ? round(($discAmt / $price) * 100, 2) : 0;
                                                            $final   = $price - $discAmt;
                                                            $set('discount_amount', round($discAmt, 2));
                                                            $set('discount_percent', $pct);
                                                            $set('sale_price_display', round($final, 2));
                                                        }),

                                                    TextInput::make('sale_price_display')
                                                        ->label('Sale Price')
                                                        ->numeric()
                                                        ->prefix('$')
                                                        ->default(0)
                                                        ->dehydrated(false)
                                                        ->live(onBlur: true)
                                                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                            $price     = floatval($get('quoted_price') ?? 0);
                                                            $salePrice = min($price, max(0, floatval($state ?? 0)));
                                                            $discAmt   = $price - $salePrice;
                                                            $pct       = $price > 0 ? round(($discAmt / $price) * 100, 2) : 0;
                                                            $set('discount_amount', round($discAmt, 2));
                                                            $set('discount_percent', $pct);
                                                        }),
                                                ]),

                                                // ── ROW 4: Payment + Spacer + Tax Free ──────────────
                                                Grid::make(3)->schema([
                                                    Select::make('deposit_method')
                                                        ->label('Deposit Payment Method')
                                                        ->options(self::getPaymentOptions())
                                                        ->default('VISA')
                                                        ->required(),
                                                    Placeholder::make('spacer')->label('')->content(''),
                                                    Toggle::make('is_tax_free')
                                                        ->label('Tax Free Order?')
                                                        ->default(false)
                                                        ->inline(false)
                                                        ->live(),
                                                ]),

                                                CustomDatePicker::make('due_date')->label('Promised By Date')->required(),
                                                Textarea::make('design_notes')->label('Design Notes & Instructions')->rows(2),
                                            ])
                                            ->action(function (array $data, Get $get, Set $set) {
                                                $price   = floatval($data['quoted_price']);
                                                $discPct = min(100, max(0, floatval($data['discount_percent'] ?? 0)));
                                                $discAmt = min($price, max(0, floatval($data['discount_amount'] ?? ($price * $discPct / 100))));
                                                $discPct = $price > 0 ? round(($discAmt / $price) * 100, 2) : $discPct;
                                                $finalPrice = max(0, $price - $discAmt);
                                                $deposit = floatval($data['custom_deposit_amount'] ?? 0);
                                                $method  = $data['deposit_method'] ?? 'CASH';

                                                $draftId = (string) Str::uuid();
                                                $data['draft_id']         = $draftId;
                                                $data['discount_percent'] = $discPct;
                                                $data['discount_amount']  = $discAmt;

                                                $currentItems = $get('items') ?? [];
                                                $currentItems[$draftId] = [
                                                    'product_item_id'     => null,
                                                    'repair_id'           => null,
                                                    'custom_order_id'     => null,
                                                    'is_non_stock'        => false,
                                                    'is_new_custom_order' => true,
                                                    'new_custom_data'     => json_encode($data),
                                                    'stock_no_display'    => 'NEW CUSTOM',
                                                    'custom_description'  => "CUSTOM Order: {$data['product_name']}\nMetal: {$data['metal_type']}\n" . ($data['design_notes'] ?? ''),
                                                    'qty'                 => 1,
                                                    'sold_price'          => $price,
                                                    'sale_price_override' => $finalPrice,
                                                    'discount_percent'    => $discPct,
                                                    'discount_amount'     => $discAmt,
                                                    'is_tax_free'         => $data['is_tax_free'] ?? false,
                                                ];
                                                $set('items', $currentItems);

                                                if ($deposit > 0) {
                                                    $set('is_split_payment', true);
                                                    $splits      = $get('split_payments') ?? [];
                                                    $cleanSplits = [];
                                                    foreach ($splits as $k => $v) {
                                                        if (floatval($v['amount'] ?? 0) > 0) $cleanSplits[$k] = $v;
                                                    }
                                                   $cleanSplits[$draftId] = ['method' => $method, 'amount' => $deposit, 'payment_target' => 'custom'];
                                                    $set('split_payments', $cleanSplits);
                                                }

                                                self::updateTotals($get, $set);
                                            }),
                                    ])->columnSpan(1),
                                ]),

                            Section::make('Current Bill Items')->schema([
                                Repeater::make('items')
                                    ->relationship('items')
                                    ->mutateRelationshipDataBeforeFillUsing(function (array $data): array {
                                        if (!empty($data['product_item_id'])) {
                                            $item = \App\Models\ProductItem::find($data['product_item_id']);
                                            $data['stock_no_display'] = $item ? $item->barcode : 'UNKNOWN STOCK';
                                        } elseif (!empty($data['custom_order_id'])) {
                                            $co = \App\Models\CustomOrder::find($data['custom_order_id']);
                                            $data['stock_no_display'] = $co ? 'CUSTOM #' . ($co->order_no ?? $co->id) : 'CUSTOM';
                                        } elseif (!empty($data['repair_id'])) {
                                            $rep = \App\Models\Repair::find($data['repair_id']);
                                            $data['stock_no_display'] = $rep ? 'REPAIR #' . ($rep->repair_no ?? $rep->id) : 'REPAIR';
                                        } else {
                                            $data['stock_no_display'] = 'NON-TAG';
                                        }
                                        return $data;
                                    })
                                   ->mutateRelationshipDataBeforeCreateUsing(function (array $data, $livewire) {
    if (!empty($data['is_new_custom_order'])) {
        $customData = is_string($data['new_custom_data']) ? json_decode($data['new_custom_data'], true) : ($data['new_custom_data'] ?? []);

        // 🚀 THE FIX: Extensive fallbacks. If the JSON corrupts, it safely pulls from the livewire repeater row's raw values!
        $quotedPrice = floatval($customData['quoted_price'] ?? $data['sold_price'] ?? $data['sale_price_override'] ?? 0);
        
       $isTaxFree = !empty($customData['is_tax_free']) || !empty($data['is_tax_free']);
        $dbTax = \Illuminate\Support\Facades\DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? 7.63;
        $taxRate = $isTaxFree ? 0 : floatval($dbTax) / 100;

        $discountPct         = min(100, max(0, floatval($customData['discount_percent'] ?? 0)));
        $discountAmt         = floatval($customData['discount_amount'] ?? ($quotedPrice * $discountPct / 100));
        $quotedAfterDiscount = max(0, $quotedPrice - $discountAmt);
        $grandTotal          = $quotedAfterDiscount + ($quotedAfterDiscount * $taxRate);

        $depositAmt = floatval($customData['custom_deposit_amount'] ?? $customData['amount_paid'] ?? 0);

        $customOrder = \App\Models\CustomOrder::create([
            'customer_id'      => $livewire->data['customer_id'] ?? null,
            'staff_id'         => auth()->id(),
            'order_type'       => 'custom',
            'product_name'     => $customData['product_name'] ?? 'Custom',
            'metal_type'       => $customData['metal_type'] ?? '14k',
            'quoted_price'     => $quotedPrice, // 🚀 Will now properly save the actual price
            'discount_percent' => $discountPct,
            'discount_amount'  => $discountAmt,
            'due_date'         => $customData['due_date'] ?? null,
            'design_notes'     => $customData['design_notes'] ?? null,
            'status'           => 'in_production',
            'is_tax_free'      => $isTaxFree,
            'amount_paid'      => $depositAmt,
            'balance_due'      => max(0, $grandTotal - $depositAmt),

            'items'            => [
                [
                    'product_name' => $customData['product_name'] ?? 'Custom',
                    'metal_type'   => $customData['metal_type'] ?? '14k',
                    'quoted_price' => $quotedPrice,
                    'design_notes' => $customData['design_notes'] ?? null,
                    'is_tax_free'  => $isTaxFree,
                ]
            ],
        ]);
        $data['custom_order_id'] = $customOrder->id;
    }
    unset($data['is_new_custom_order'], $data['new_custom_data'], $data['is_non_stock']);
    return $data;
})
                                    ->mutateRelationshipDataBeforeSaveUsing(function (array $data, $livewire) {
                                        if (!empty($data['is_new_custom_order'])) {
                                            $customData  = is_string($data['new_custom_data']) ? json_decode($data['new_custom_data'], true) : ($data['new_custom_data'] ?? []);
                                            $quotedPrice = floatval($data['sale_price_override'] ?? 0);
                                            $isTaxFree   = !empty($data['is_tax_free']);
                                            $dbTax       = \Illuminate\Support\Facades\DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? 7.63;
                                            $taxRate     = $isTaxFree ? 0 : floatval($dbTax) / 100;
                                            $depositAmt  = floatval($customData['custom_deposit_amount'] ?? $customData['amount_paid'] ?? 0);

                                            $discountPct         = min(100, max(0, floatval($customData['discount_percent'] ?? 0)));
                                            $discountAmt         = floatval($customData['discount_amount'] ?? ($quotedPrice * $discountPct / 100));
                                            $quotedAfterDiscount = $quotedPrice - $discountAmt;
                                            $grandTotal          = $quotedAfterDiscount + ($quotedAfterDiscount * $taxRate);

                                            $customOrder = \App\Models\CustomOrder::create([
                                                'customer_id'      => $livewire->data['customer_id'] ?? null,
                                                'staff_id'         => auth()->id(),
                                                'order_type'       => 'custom',
                                                'product_name'     => $customData['product_name'] ?? 'Custom',
                                                'metal_type'       => $customData['metal_type'] ?? '14k',
                                                'quoted_price'     => $quotedAfterDiscount,
                                                'discount_percent' => $discountPct,
                                                'discount_amount'  => $discountAmt,
                                                'due_date'         => $customData['due_date'] ?? null,
                                                'design_notes'     => $customData['design_notes'] ?? null,
                                                'status'           => 'in_production',
                                                'is_tax_free'      => $isTaxFree,
                                                'amount_paid'      => $depositAmt,
                                                'balance_due'      => max(0, $grandTotal - $depositAmt),
                                                'items'            => [[
                                                    'product_name' => $customData['product_name'] ?? 'Custom',
                                                    'metal_type'   => $customData['metal_type'] ?? '14k',
                                                    'quoted_price' => $quotedAfterDiscount,
                                                    'design_notes' => $customData['design_notes'] ?? null,
                                                    'is_tax_free'  => $isTaxFree,
                                                ]],
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
                                            ->extraInputAttributes(fn($state) => ['class' => str_contains($state ?? '', 'CUSTOM') ? 'text-blue-600 font-bold bg-blue-50' : ''])
                                            ->hintAction(
                                                FormAction::make('edit_draft_custom_order')
                                                    ->label('Edit Specs')
                                                    ->icon('heroicon-o-pencil-square')
                                                    ->color('warning')
                                                    ->visible(fn(Get $get) => $get('is_new_custom_order') === true)
                                                    ->modalHeading('Edit Custom Piece')
                                                    ->modalWidth('3xl')
                                                    ->fillForm(fn(Get $get) => is_string($d = $get('new_custom_data')) ? json_decode($d, true) : ($d ?? []))
                                                    ->form([
                                                        Grid::make(2)->schema([
                                                            Select::make('product_name')->label('What are we making?')->options(fn() => \App\Models\CustomOrder::whereNotNull('product_name')->pluck('product_name', 'product_name')->unique())->searchable()->createOptionForm([TextInput::make('new_product_name')->required()->label('New Product Name')])->createOptionUsing(fn($data) => $data['new_product_name'])->required(),
                                                            Select::make('metal_type')->label('Metal Type')->options(['10k' => '10k Gold', '14k' => '14k Gold', '18k' => '18k Gold', 'platinum' => 'Platinum', 'silver' => 'Silver', 'other' => 'Other'])->required(),
                                                        ]),
                                                        Grid::make(3)->schema([
                                                            TextInput::make('quoted_price')->label('Total Item Price')->numeric()->prefix('$')->required()->live(onBlur: true),
                                                            Placeholder::make('total_with_tax')->label('Total (w/ Tax)')->content(function (Get $get) {
                                                                $quoted  = (float)($get('quoted_price') ?? 0);
                                                                $discAmt = min($quoted, max(0, (float)($get('discount_amount') ?? 0)));
                                                                $discounted = $quoted - $discAmt;
                                                                $taxRate = $get('is_tax_free') ? 0 : ((float)\Illuminate\Support\Facades\DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? 7.63) / 100;
                                                                return new HtmlString("<div class='text-2xl font-black text-gray-900 mt-1'>$" . number_format($discounted * (1 + $taxRate), 2) . "</div>");
                                                            }),
                                                            TextInput::make('custom_deposit_amount')->label('Deposit Amount')->numeric()->prefix('$')->default(0),
                                                        ]),
                                                        Grid::make(3)->schema([
                                                            TextInput::make('discount_percent')->label('Disc %')->numeric()->suffix('%')->default(0)->minValue(0)->maxValue(100)->live(onBlur: true)
                                                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                                    $price   = floatval($get('quoted_price') ?? 0);
                                                                    $pct     = min(100, max(0, floatval($state ?? 0)));
                                                                    $discAmt = $price * $pct / 100;
                                                                    $set('discount_amount', round($discAmt, 2));
                                                                    $set('sale_price_display', round($price - $discAmt, 2));
                                                                }),
                                                            TextInput::make('discount_amount')->label('Disc $')->numeric()->prefix('$')->default(0)->live(onBlur: true)
                                                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                                    $price   = floatval($get('quoted_price') ?? 0);
                                                                    $discAmt = min($price, max(0, floatval($state ?? 0)));
                                                                    $pct     = $price > 0 ? round(($discAmt / $price) * 100, 2) : 0;
                                                                    $set('discount_percent', $pct);
                                                                    $set('sale_price_display', round($price - $discAmt, 2));
                                                                }),
                                                            TextInput::make('sale_price_display')->label('Sale Price')->numeric()->prefix('$')->default(0)->dehydrated(false)->live(onBlur: true)
                                                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                                    $price     = floatval($get('quoted_price') ?? 0);
                                                                    $salePrice = min($price, max(0, floatval($state ?? 0)));
                                                                    $discAmt   = $price - $salePrice;
                                                                    $pct       = $price > 0 ? round(($discAmt / $price) * 100, 2) : 0;
                                                                    $set('discount_amount', round($discAmt, 2));
                                                                    $set('discount_percent', $pct);
                                                                }),
                                                        ]),
                                                        Grid::make(3)->schema([
                                                            Select::make('deposit_method')->label('Deposit Payment Method')->options(self::getPaymentOptions())->default('VISA')->required(),
                                                            Placeholder::make('spacer')->label('')->content(''),
                                                            Toggle::make('is_tax_free')->label('Tax Free Order?')->inline(false)->live(),
                                                        ]),
                                                        CustomDatePicker::make('due_date')->label('Promised By Date')->required(),
                                                        Textarea::make('design_notes')->label('Design Notes & Instructions')->rows(2),
                                                    ])
                                                    ->action(function (array $data, Get $get, Set $set, \Filament\Forms\Contracts\HasForms $livewire) {
                                                        $oldData = is_string($get('new_custom_data')) ? json_decode($get('new_custom_data'), true) : [];
                                                        $draftId = $oldData['draft_id'] ?? (string) Str::uuid();
                                                        $data['draft_id'] = $draftId;

                                                        $price     = floatval($data['quoted_price'] ?? 0);
                                                        $discAmt   = floatval($data['discount_amount'] ?? 0);
                                                        $discPct   = floatval($data['discount_percent'] ?? 0);
                                                        $deposit   = floatval($data['custom_deposit_amount'] ?? 0);
                                                        $method    = $data['deposit_method'] ?? 'CASH';
                                                        $finalPrice = max(0, $price - $discAmt);

                                                        $set('new_custom_data', json_encode($data));
                                                        $set('sold_price', $price);
                                                        $set('sale_price_override', $finalPrice);
                                                        $set('discount_percent', $discPct);
                                                        $set('discount_amount', $discAmt);
                                                        $set('is_tax_free', $data['is_tax_free'] ?? false);
                                                        $set('custom_description', "CUSTOM Order: {$data['product_name']}\nMetal: {$data['metal_type']}\n" . ($data['design_notes'] ?? ''));

                                                        $splits      = data_get($livewire->data, 'split_payments') ?? [];
                                                        $cleanSplits = [];
                                                        foreach ($splits as $k => $v) {
                                                            if (floatval($v['amount'] ?? 0) > 0 && $k !== $draftId) {
                                                                $cleanSplits[$k] = $v;
                                                            }
                                                        }

                                                        if ($deposit > 0) {
                                                            data_set($livewire->data, 'is_split_payment', true);
                                                            $cleanSplits[$draftId] = ['method' => $method, 'amount' => $deposit, 'payment_target' => 'custom'];
                                                        }

                                                        data_set($livewire->data, 'split_payments', $cleanSplits);

                                                        if (empty($cleanSplits)) {
                                                            data_set($livewire->data, 'is_split_payment', false);
                                                            data_set($livewire->data, 'amount_paid', 0);
                                                        }

                                                        self::updateTotals(fn($p) => data_get($livewire->data, $p), fn($p, $v) => data_set($livewire->data, $p, $v));
                                                    })
                                            )
                                            ->columnSpan(2),

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
                                            ->readOnly(fn(Get $get) => $get('is_new_custom_order') === true || !empty($get('custom_order_id')))
                                            ->dehydrated(true)
                                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                if ($pid = $get('product_item_id')) {
                                                    $productItem = ProductItem::find($pid);
                                                    if ($productItem && $state > $productItem->qty) {
                                                        $set('qty', $productItem->qty);
                                                        $state = $productItem->qty;
                                                    }
                                                }
                                                $price     = floatval($get('sold_price'));
                                                $percent   = floatval($get('discount_percent'));
                                                $lineTotal = $price * $state;
                                                $set('discount_amount', number_format($lineTotal * ($percent / 100), 2, '.', ''));
                                                self::updateTotals($get, $set);
                                            }),

                                        TextInput::make('sold_price')
                                            ->label('Price')
                                            ->numeric()
                                            ->live(onBlur: true)
                                            ->columnSpan(2)
                                            ->readOnly(fn(Get $get) => $get('is_new_custom_order') === true || !empty($get('custom_order_id')))
                                            ->dehydrated(true)
                                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                $qty      = intval($get('qty') ?? 1);
                                                $percent  = floatval($get('discount_percent') ?? 0);
                                                $lineTotalBeforeDiscount = floatval($state) * $qty;
                                                $newDiscountAmount       = $lineTotalBeforeDiscount * ($percent / 100);
                                                $set('discount_amount', number_format($newDiscountAmount, 2, '.', ''));
                                                self::updateTotals($get, $set);
                                            }),

                                        TextInput::make('sale_price_override')
                                            ->label('Sale Price')
                                            ->columnSpan(2)
                                            ->live(onBlur: true)
                                            ->readOnly(fn(Get $get) => $get('is_new_custom_order') === true || !empty($get('custom_order_id')))
                                            ->dehydrated(true)
                                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                $unitPrice     = floatval($get('sold_price') ?? 0);
                                                $qty           = intval($get('qty') ?? 1);
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
                                            ->readOnly(fn(Get $get) => $get('is_new_custom_order') === true || !empty($get('custom_order_id')))
                                            ->dehydrated(true)
                                            ->afterStateUpdated(function ($state, Get $get, Set $set, \Filament\Forms\Contracts\HasForms $livewire) {
                                                $price     = floatval($get('sold_price') ?? 0);
                                                $qty       = intval($get('qty') ?? 1);
                                                $lineTotal = $price * $qty;
                                                $percent   = floatval($state ?? 0);

                                                $discountAmount = $lineTotal * ($percent / 100);
                                                $finalPrice     = $lineTotal - $discountAmount;

                                                $set('discount_amount', number_format($discountAmount, 2, '.', ''));
                                                $set('sale_price_override', number_format($finalPrice, 2, '.', ''));

                                                self::updateTotals(
                                                    fn($p) => data_get($livewire->data, $p),
                                                    fn($p, $v) => data_set($livewire->data, $p, $v)
                                                );
                                            }),

                                        TextInput::make('discount_amount')
                                            ->label('Disc $')
                                            ->numeric()
                                            ->default(0)
                                            ->prefix('$')
                                            ->live(onBlur: true)
                                            ->columnSpan(2)
                                            ->readOnly(fn(Get $get) => $get('is_new_custom_order') === true || !empty($get('custom_order_id')))
                                            ->dehydrated(true)
                                            ->afterStateUpdated(function ($state, Get $get, Set $set, \Filament\Forms\Contracts\HasForms $livewire) {
                                                $price          = floatval($get('sold_price') ?? 0);
                                                $qty            = intval($get('qty') ?? 1);
                                                $lineTotal      = $price * $qty;
                                                $discountAmount = floatval($state ?? 0);

                                                if ($lineTotal > 0) {
                                                    $percent = ($discountAmount / $lineTotal) * 100;
                                                    $set('discount_percent', number_format($percent, 2, '.', ''));
                                                }

                                                $set('sale_price_override', number_format($lineTotal - $discountAmount, 2, '.', ''));

                                                self::updateTotals(
                                                    fn($p) => data_get($livewire->data, $p),
                                                    fn($p, $v) => data_set($livewire->data, $p, $v)
                                                );
                                            }),

                                        Checkbox::make('is_tax_free')
                                            ->label('No Tax')
                                            ->columnSpan(1)
                                            ->default(false)
                                            ->dehydrated(true)
                                            ->live()
                                            ->disabled(fn(Get $get) => $get('is_new_custom_order') === true || !empty($get('custom_order_id')))
                                            ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotals($get, $set)),
                                    ])
                                    ->columns(12)
                                    ->addable(false)
                                    ->deletable(true)
                                    ->reorderable(false)
                                    ->default([])
                                    ->live(onBlur: true)
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
                                                CustomDatePicker::make('date_required')
                                                    ->label('Completion Date')
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
                                        CustomDatePicker::make('follow_up_date')
                                            ->label('Follow Up (2 Weeks)')
                                            ->default(now()->addWeeks(2)->format('Y-m-d'))
                                            ->displayFormat('m/d/Y'),
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

                            Placeholder::make('validation_alert')
                                ->label('')
                                ->content(function (Get $get) {
                                    $missing = [];
                                    if (empty($get('customer_id'))) $missing[] = 'Customer';
                                    if (empty($get('items'))) $missing[] = 'Items in Cart';
                                    if (empty($get('payment_method')) && empty($get('is_split_payment'))) $missing[] = 'Payment Method';

                                    if (empty($missing)) {
                                        return new HtmlString("<div style='padding: 12px; background-color: #ecfdf5; color: #047857; border: 2px solid #10b981; border-radius: 8px; text-align: center; font-weight: 900; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.1);'>✅ READY TO COMPLETE SALE</div>");
                                    }

                                    $text = implode(', ', $missing);
                                    return new HtmlString("<div style='padding: 12px; background-color: #fef2f2; color: #dc2626; border: 2px dashed #ef4444; border-radius: 8px; text-align: center; font-size: 0.875rem; font-weight: 800; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.1);'>⚠️ MISSING: {$text}</div>");
                                }),
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
                                                    // We handle saving manually inside the action
                                                    ->modalSubmitActionLabel('Save Changes')
                                                    ->modalCancelActionLabel('Close')
                                                    ->slideOver()
                                                    ->form(function (Get $get) {
                                                        $customer = \App\Models\Customer::find($get('customer_id'));
                                                        if (!$customer) return [];

                                                        return [
                                                            \Filament\Forms\Components\Tabs::make('CustomerDetailsTabs')
                                                                ->tabs([
                                                                    // ── TAB 1: VIEW & HISTORY ──
                                                                    \Filament\Forms\Components\Tabs\Tab::make('Profile & History')
                                                                        ->icon('heroicon-o-document-text')
                                                                        ->schema([
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
                                                                                TextInput::make('view_phone')->label('Phone')->default($customer->phone)->readOnly(),
                                                                                TextInput::make('view_email')->label('Email')->default($customer->email)->readOnly(),
                                                                                TextInput::make('view_addr')
                                                                                    ->label('Full Address')
                                                                                    ->default(trim("{$customer->street} {$customer->suburb} {$customer->city} {$customer->state} {$customer->postcode}"))
                                                                                    ->readOnly()
                                                                                    ->columnSpanFull(),
                                                                            ]),

                                                                            Placeholder::make('purchase_history')
                                                                                ->label('Purchase History')
                                                                                ->content(function () use ($customer) {
                                                                                    $sales = $customer->sales()
                                                                                        ->with(['items.productItem', 'payments'])
                                                                                        ->whereNotIn('status', ['void', 'cancelled'])
                                                                                        ->latest()
                                                                                        ->limit(5)
                                                                                        ->get();

                                                                                    if ($sales->isEmpty()) {
                                                                                        return new HtmlString("<p class='text-sm text-gray-400 italic'>No prior purchase history.</p>");
                                                                                    }

                                                                                    $rowsHtml = '';
                                                                                    foreach ($sales as $sale) {
                                                                                        $total      = floatval($sale->final_total);
                                                                                        $paid       = floatval($sale->payments->sum('amount'));
                                                                                        if ($paid == 0 && floatval($sale->amount_paid) > 0) {
                                                                                            $paid = floatval($sale->amount_paid);
                                                                                        }
                                                                                        $balance    = max(0, $total - $paid);
                                                                                        $isOwing    = $balance > 0.01;

                                                                                        $statusColor = match ($sale->status) {
                                                                                            'completed'          => 'bg-success-100 text-success-700',
                                                                                            'refunded'           => 'bg-danger-100 text-danger-700',
                                                                                            'partially_refunded' => 'bg-warning-100 text-warning-700',
                                                                                            default              => 'bg-gray-100 text-gray-700',
                                                                                        };
                                                                                        $statusLabel = ucfirst(str_replace('_', ' ', $sale->status));

                                                                                        $itemPills = '';
                                                                                        foreach ($sale->items->take(2) as $item) {
                                                                                            $label = \Illuminate\Support\Str::limit($item->custom_description ?? 'Item', 20);
                                                                                            $itemPills .= "<span class='inline-block bg-gray-100 text-gray-600 text-[10px] px-2 py-0.5 rounded-full mr-1 mt-1 border border-gray-200'>{$label}</span>";
                                                                                        }

                                                                                        $balanceHtml = $isOwing
                                                                                            ? "<p class='text-[10px] text-danger-600 font-bold mt-1 m-0'>Balance: \$" . number_format($balance, 2) . "</p>"
                                                                                            : '';

                                                                                        // We link to view or edit the sale, opening in new tab
                                                                                        $editUrl = SaleResource::getUrl('edit', ['record' => $sale->id]);

                                                                                        $rowsHtml .= "
                                                                                            <div class='border " . ($isOwing ? 'border-warning-300' : 'border-gray-200') . " rounded-lg p-3 mb-2 bg-white shadow-sm'>
                                                                                                <div class='flex justify-between items-start'>
                                                                                                    <div>
                                                                                                        <a href='{$editUrl}' target='_blank' class='font-mono font-bold text-primary-600 text-xs hover:underline'>#{$sale->invoice_number}</a>
                                                                                                        <span class='ml-2 text-[10px] text-gray-400'>{$sale->created_at->format('M d, Y')}</span>
                                                                                                        <div class='mt-1'>{$itemPills}</div>
                                                                                                    </div>
                                                                                                    <div class='text-right'>
                                                                                                        <p class='text-sm font-bold " . ($isOwing ? 'text-warning-600' : 'text-gray-900') . " m-0'>\$" . number_format($total, 2) . "</p>
                                                                                                        <span class='text-[9px] px-2 py-0.5 rounded-full uppercase font-bold mt-1 inline-block {$statusColor}'>{$statusLabel}</span>
                                                                                                        {$balanceHtml}
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        ";
                                                                                    }

                                                                                    return new HtmlString("<div class='mt-2 max-h-[300px] overflow-y-auto pr-2'>{$rowsHtml}</div>");
                                                                                }),
                                                                        ]),

                                                                    // ── TAB 2: EDIT CUSTOMER ──
                                                                  // ── TAB 2: EDIT CUSTOMER ──
                                                                    \Filament\Forms\Components\Tabs\Tab::make('Edit Customer')
                                                                        ->icon('heroicon-o-pencil-square')
                                                                        ->schema([
                                                                            Grid::make(2)->schema([
                                                                                TextInput::make('edit_name')
                                                                                    ->label('First Name')
                                                                                    ->default($customer->name)
                                                                                    ->required(),
                                                                                TextInput::make('edit_last_name')
                                                                                    ->label('Last Name')
                                                                                    ->default($customer->last_name),
                                                                                TextInput::make('edit_phone')
                                                                                    ->label('Phone')
                                                                                    ->tel()
                                                                                    ->prefix('+1')
                                                                                    ->default(preg_replace('/[^0-9]/', '', $customer->phone))
                                                                                    ->mask('(999) 999-9999')
                                                                                    ->stripCharacters(['(', ')', '-', ' '])
                                                                                    ->required(),
                                                                                TextInput::make('edit_email')
                                                                                    ->label('Email')
                                                                                    ->email()
                                                                                    ->default($customer->email),
                                                                                    
                                                                                // 🚀 THE FIX: Replaced manual fields with Google Autocomplete
                                                                                \Tapp\FilamentGoogleAutocomplete\Forms\Components\GoogleAutocomplete::make('edit_address_search')
                                                                                    ->label('Search Address')
                                                                                    ->autocompletePlaceholder('Start typing address...')
                                                                                    ->countries(['US'])
                                                                                    ->columnSpanFull()
                                                                                    ->withFields([
                                                                                        TextInput::make('edit_street')
                                                                                            ->label('Street Address')
                                                                                            ->default($customer->street)
                                                                                            ->extraInputAttributes(['data-google-field' => '{street_number} {route}'])
                                                                                            ->columnSpanFull(),
                                                                                        TextInput::make('edit_city')
                                                                                            ->label('City')
                                                                                            ->default($customer->city)
                                                                                            ->extraInputAttributes(['data-google-field' => 'locality', 'data-google-value' => 'short_name']),
                                                                                        TextInput::make('edit_state')
                                                                                            ->label('State')
                                                                                            ->default($customer->state)
                                                                                            ->extraInputAttributes(['data-google-field' => 'administrative_area_level_1']),
                                                                                        TextInput::make('edit_postcode')
                                                                                            ->label('Zip Code')
                                                                                            ->default($customer->postcode)
                                                                                            ->extraInputAttributes(['data-google-field' => 'postal_code']),
                                                                                    ]),
                                                                            ])
                                                                        ]),
                                                                ]),
                                                        ];
                                                    })
                                                    ->action(function (array $data, Get $get) {
                                                        // When they click "Save Changes", we update the customer if they made edits
                                                        $customer = \App\Models\Customer::find($get('customer_id'));
                                                        if ($customer && isset($data['edit_name'])) {
                                                            $customer->update([
                                                                'name'      => $data['edit_name'],
                                                                'last_name' => $data['edit_last_name'] ?? null,
                                                                'phone'     => $data['edit_phone'],
                                                                'email'     => $data['edit_email'] ?? null,
                                                                'street'    => $data['edit_street'] ?? null,
                                                                'city'      => $data['edit_city'] ?? null,
                                                                'state'     => $data['edit_state'] ?? null,
                                                                'postcode'  => $data['edit_postcode'] ?? null,
                                                            ]);
                                                            Notification::make()->title('Customer Updated Successfully')->success()->send();
                                                        }
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
                                                                        ->unique('customers', 'phone')
                                                                        ->afterStateHydrated(function ($component, $state) {
                                                                            if ($state && preg_match('/^[0-9]{10}$/', $state)) {
                                                                                $component->state('(' . substr($state, 0, 3) . ') ' . substr($state, 3, 3) . '-' . substr($state, 6));
                                                                            }
                                                                        }),
                                                                    Forms\Components\TextInput::make('email')
                                                                        ->label('Email')
                                                                        ->email()
                                                                        ->unique('customers', 'email'),
                                                                ]),
                                                                Forms\Components\Grid::make(2)->schema([
                                                                    CustomDatePicker::make('dob')->rule('before_or_equal:today')->label('Birth Date'),
                                                                    CustomDatePicker::make('wedding_anniversary')->label('Wedding Date'),
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
                                        $items         = $get('items') ?? [];
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
                                            $date   = \Carbon\Carbon::parse($payment->paid_at)->format('M d, Y h:i A');
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
                                        <div style='font-size:10px; font-weight:900; color:#0369a1; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:8px;'>
                                            Custom Order Payment Trail
                                        </div>
                                        <div style='max-height: 150px; overflow-y: auto; padding-right: 5px;'>
                                            {$rows}
                                        </div>
                                        <div style='display:flex; justify-content:space-between; align-items:center; margin-top:10px; border-top: 1px solid #bae6fd; padding-top:8px;'>
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
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        if ($state) {
                                            $existingAmount = floatval($get('amount_paid') ?? 0);
                                            $existingMethod = $get('payment_method') ?? 'VISA';
                                            $currentSplits  = $get('split_payments') ?? [];

                                            if ($existingAmount > 0 && empty($currentSplits)) {
                                                $set('split_payments', [
                                                    (string) \Illuminate\Support\Str::uuid() => [
                                                        'method' => $existingMethod,
                                                        'amount' => $existingAmount,
                                                    ]
                                                ]);
                                            }
                                        } else {
                                            $currentSplits = $get('split_payments') ?? [];
                                            if (!empty($currentSplits)) {
                                                $totalSplit  = collect($currentSplits)->sum(fn($p) => (float)($p['amount'] ?? 0));
                                                $firstMethod = collect($currentSplits)->first()['method'] ?? 'VISA';
                                                $set('amount_paid', number_format($totalSplit, 2, '.', ''));
                                                $set('payment_method', $firstMethod);
                                            }
                                        }
                                        self::updateTotals($get, $set);
                                    })
                                    ->columnSpanFull(),

                              Select::make('payment_method')
    ->label('Payment Method')
    ->options(self::getPaymentOptions())
    ->placeholder('Select Payment Method')
    ->required(fn(Get $get) => !$get('is_split_payment'))
    ->visible(fn(Get $get) => !$get('is_split_payment'))
    ->live()
    ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotals($get, $set)),

Select::make('payment_target')
    ->label('Apply To')
    ->options([
        'regular' => 'Regular Sales',
        'custom'  => 'Custom Deposit',
    ])
    ->default('regular')
    ->required(function (Get $get) {
        if ($get('is_split_payment')) return false;
        $items = $get('items') ?? [];
        return collect($items)->contains(fn($item) => !empty($item['custom_order_id']) || !empty($item['is_new_custom_order']))
            || request()->has('custom_order_id');
    })
    ->visible(function (Get $get) {
        if ($get('is_split_payment')) return false;
        $items = $get('items') ?? [];
        return collect($items)->contains(fn($item) => !empty($item['custom_order_id']) || !empty($item['is_new_custom_order']))
            || request()->has('custom_order_id');
    })
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
        Grid::make(6)->schema([
            Select::make('method')
                ->options(self::getPaymentOptions())
                ->required()
                ->label('Method')
                ->columnSpan(function (Get $get) {
                    $items = $get('../../items') ?? [];
                    $hasCustom = collect($items)->contains(
                        fn($item) => !empty($item['custom_order_id']) || !empty($item['is_new_custom_order'])
                    ) || request()->has('custom_order_id');
                    return $hasCustom ? 2 : 3;
                }),
            
            TextInput::make('amount')
                ->numeric()
                ->prefix('$')
                ->required()
                ->label('Amount')
                ->live(onBlur: true)
                ->columnSpan(function (Get $get) {
                    $items = $get('../../items') ?? [];
                    $hasCustom = collect($items)->contains(
                        fn($item) => !empty($item['custom_order_id']) || !empty($item['is_new_custom_order'])
                    ) || request()->has('custom_order_id');
                    return $hasCustom ? 2 : 3;
                }),
                
            Select::make('payment_target')
                ->label('Apply To')
                ->options([
                    'regular' => 'Regular Sales',
                    'custom'  => 'Custom Deposit',
                ])
                ->default('regular')
                ->columnSpan(2)
                ->visible(function (Get $get) {
                    $items = $get('../../items') ?? [];
                    return collect($items)->contains(
                        fn($item) => !empty($item['custom_order_id']) || !empty($item['is_new_custom_order'])
                    ) || request()->has('custom_order_id');
                })
                ->live()
                ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotals($get, $set)),
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
                                        $total     = (float) $get('final_total');
                                        $payments  = $get('split_payments') ?? [];
                                        $sum       = collect($payments)->sum(fn($p) => (float)($p['amount'] ?? 0));
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
        
        $isSplit = $get('is_split_payment');
        $customPaid = 0;
        $regularPaid = 0;
        
        if ($isSplit) {
            $payments = $get('split_payments') ?? [];
            foreach ($payments as $p) {
                $amt = (float)($p['amount'] ?? 0);
                if (($p['payment_target'] ?? 'regular') === 'custom') {
                    $customPaid += $amt;
                } else {
                    $regularPaid += $amt;
                }
            }
        } else {
            $amt = floatval($get('amount_paid') ?? 0);
            if (($get('payment_target') ?? 'regular') === 'custom') {
                $customPaid += $amt;
            } else {
                $regularPaid += $amt;
            }
        }
        
        $totalPaid = $customPaid + $regularPaid;
        $remaining = $total - $totalPaid;

        $html = "
        <div style='display:flex; justify-content: space-between; margin-bottom: 5px; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px;'>
            <span style='font-size: 0.875rem; color: #64748b;'>Invoice Total:</span>
            <span style='font-size: 0.875rem; font-weight: bold; color: #334155;'>$" . number_format($total, 2) . "</span>
        </div>";
        
        if ($customPaid > 0) {
            $html .= "
            <div style='display:flex; justify-content: space-between; align-items: center; margin-bottom: 5px; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px;'>
                <span class='px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wider bg-purple-50 text-purple-700 border-purple-200'>✨ CUSTOM DEPOSIT</span>
                <span style='font-size: 0.875rem; font-weight: bold; color: #10b981;'>+$" . number_format($customPaid, 2) . "</span>
            </div>";
        }
        
        if ($regularPaid > 0) {
            $html .= "
            <div style='display:flex; justify-content: space-between; align-items: center; margin-bottom: 5px; border-bottom: 1px solid #e2e8f0; padding-bottom: 5px;'>
                <span class='px-2.5 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wider bg-teal-50 text-teal-700 border-teal-200'>💵 PAYMENT</span>
                <span style='font-size: 0.875rem; font-weight: bold; color: #10b981;'>+$" . number_format($regularPaid, 2) . "</span>
            </div>";
        }

        if (round($remaining, 2) <= 0) {
            $overpaid = abs($remaining);
            return new HtmlString($html . "<div style='text-align: right;'><span style='color:#16a34a; font-weight:900; font-size:1.875rem;'>\$0.00</span><div style='color:#16a34a; font-size:0.75rem; font-weight:700;'>✅ FULLY PAID</div>" . ($overpaid > 0 ? "<div style='color:#2563eb; font-size:0.875rem; font-weight:700; margin-top:8px;'>💵 Change Due: \$" . number_format($overpaid, 2) . "</div>" : "") . "</div>");
        }

        return new HtmlString($html . "<div style='text-align: right;'><span style='color:#dc2626; font-weight:900; font-size:1.875rem;'>\$" . number_format($remaining, 2) . "</span><div style='color:#dc2626; font-size:0.75rem; font-weight:700;'>BALANCE DUE</div></div>");
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
            'sale_id'  => $sale->id,
            'amount'   => $amount,
            'method'   => $method,
            'paid_at'  => now(),
            'store_id' => $sale->store_id,
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
                        $total   = floatval($record->final_total);
                        $paid    = floatval($record->payments->sum('amount'));
                        if ($paid == 0 && floatval($record->amount_paid) > 0) $paid = floatval($record->amount_paid);
                        $balance = max(0, $total - $paid);
                        $html    = "<div class='text-xs text-gray-500'>Bill Total: $" . number_format($total, 2) . "</div>";
                        $html   .= "<div class='text-sm font-bold text-success-600'>Paid: $" . number_format($paid, 2) . "</div>";
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
                            $link           = $baseUrl . '/receipt/' . $record->id;
                            $storeName      = $store->name ?? 'Diamond Square';
                            $message        = "Hi {$record->customer->name}, thanks for visiting {$storeName}! View your receipt here: {$link}";
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
        $json         = DB::table('site_settings')->where('key', 'service_types')->value('value');
        $types        = $json ? json_decode($json, true) : $defaultTypes;
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

    $grandTotal = ($itemsSubtotal + $shipping + $totalTax + $warrantyCharge) - $tradeIn;

    $set('subtotal',    number_format($itemsSubtotal, 2, '.', ''));
    $set('tax_amount',  number_format($totalTax,      2, '.', ''));
    $set('final_total', number_format($grandTotal,    2, '.', ''));

    // Tally all payments directly from the main block
    $isSplit = $get('is_split_payment');
    if ($isSplit) {
        $payments   = $get('split_payments') ?? [];
        $totalCollected = collect($payments)->sum(fn($p) => (float)($p['amount'] ?? 0));
    } else {
        $totalCollected = floatval($get('amount_paid') ?? 0);
    }

    $changeGiven = max(0, $totalCollected - $grandTotal);
    $balanceDue  = max(0, $grandTotal - $totalCollected);

    $set('change_given',      number_format($changeGiven, 2, '.', ''));
    $set('balance_due',       number_format($balanceDue,  2, '.', ''));
    $set('display_total_due', number_format($grandTotal,  2, '.', ''));

    self::syncStatus($get, $set, $totalCollected);
}

public static function syncStatus(callable|Get $get, callable|Set $set, $totalCollected = null): void
{
    $total = floatval($get('final_total') ?? 0);

    if ($totalCollected === null) {
        $isSplit = $get('is_split_payment');
        if ($isSplit) {
            $payments = $get('split_payments') ?? [];
            $totalCollected = collect($payments)->sum(fn($p) => (float)($p['amount'] ?? 0));
        } else {
            $totalCollected = floatval($get('amount_paid') ?? 0);
        }
    }

    if (round($total, 2) <= 0) {
        $fullyPaid = true;
    } else {
        $fullyPaid = $totalCollected > 0 && round($totalCollected, 2) >= round($total, 2);
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