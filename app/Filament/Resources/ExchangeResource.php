<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExchangeResource\Pages;
use App\Models\Exchange;
use App\Models\Sale;
use App\Models\ProductItem;
use App\Models\CustomOrder;
use App\Models\Repair;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Filament\Forms\Components\{Section, Grid, Select, TextInput, Placeholder, Hidden, Toggle, Textarea, Repeater, Checkbox};
use Filament\Forms\Components\Actions\Action as FormAction;
use App\Forms\Components\CustomDatePicker;

class ExchangeResource extends Resource
{
    protected static ?string $model          = Exchange::class;
    protected static ?string $navigationIcon  = 'heroicon-o-arrow-path-rounded-square';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'Exchanges';
    protected static ?int    $navigationSort  = 4;

    // ── TOTALS ENGINE ─────────────────────────────────────────────────────────
    public static function updateExchangeTotals(Get $get, Set $set): void
    {
        $newItems = $get('new_items_form') ?? [];
        $dbTax    = DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? 7.63;
        $taxRate  = floatval($dbTax) / 100;

        $itemsSubtotal = 0;
        $taxableBasis  = 0;

        foreach ($newItems as $item) {
            $qty      = intval($item['qty'] ?? 1);
            $rowTotal = (isset($item['sale_price_override']) && floatval($item['sale_price_override']) > 0)
                ? floatval($item['sale_price_override'])
                : (floatval($item['sold_price'] ?? 0) * $qty) - floatval($item['discount_amount'] ?? 0);

            $itemsSubtotal += $rowTotal;
            $isTaxFree = filter_var($item['is_tax_free'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if (!$isTaxFree) {
                $taxableBasis += $rowTotal;
            }
        }

        $itemsTax    = round($taxableBasis * $taxRate, 2);
        $newTotal    = round($itemsSubtotal + $itemsTax, 2);
        $totalCredit = floatval($get('total_credit') ?? 0);
        $difference  = round($newTotal - $totalCredit, 2);

        $set('new_items_subtotal_display', number_format($itemsSubtotal, 2, '.', ''));
        $set('new_items_tax_display',      number_format($itemsTax,      2, '.', ''));
        $set('new_sale_amount',            number_format($newTotal,       2, '.', ''));
        $set('difference_amount',          number_format($difference,     2, '.', ''));

        if ($difference > 0.009)      $set('exchange_type', 'upgrade');
        elseif ($difference < -0.009) $set('exchange_type', 'downgrade');
        else                          $set('exchange_type', 'same_value');

        $currentReceived = $get('amount_received');
        if (($currentReceived === null || $currentReceived === '' || floatval($currentReceived) == 0)
            && $difference > 0.009) {
            $set('amount_received', number_format($difference, 2, '.', ''));
        }
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            // ── STATUS BANNER ─────────────────────────────────────────────────
            Placeholder::make('status_banner')
                ->hiddenLabel()->columnSpanFull()
                ->content(function ($record) {
                    if (!$record) return '';
                    $colors = [
                        'pending_approval' => ['bg'=>'#fffbeb','border'=>'#f59e0b','text'=>'#92400e','icon'=>'⏳'],
                        'approved'         => ['bg'=>'#eff6ff','border'=>'#3b82f6','text'=>'#1e40af','icon'=>'✅'],
                        'completed'        => ['bg'=>'#f0fdf4','border'=>'#22c55e','text'=>'#166534','icon'=>'🎉'],
                        'rejected'         => ['bg'=>'#fef2f2','border'=>'#ef4444','text'=>'#991b1b','icon'=>'❌'],
                        'exchanged'        => ['bg'=>'#f5f3ff','border'=>'#7c3aed','text'=>'#5b21b6','icon'=>'🔄'],
                    ];
                    $c = $colors[$record->status] ?? $colors['pending_approval'];
                    return new HtmlString("<div style='background:{$c['bg']};border:1.5px solid {$c['border']};border-left:5px solid {$c['border']};border-radius:8px;padding:12px 16px;'>
                        <div style='font-size:13px;font-weight:800;color:{$c['text']};'>{$c['icon']} Exchange #{$record->exchange_no} — " . ucfirst(str_replace('_', ' ', $record->status)) . "</div>
                    </div>");
                }),

            Grid::make(12)->schema([

                // ══ LEFT ══════════════════════════════════════════════════════
                Forms\Components\Group::make()->columnSpan(8)->schema([

                    // ── CUSTOMER ──────────────────────────────────────────────
                    Section::make('Customer')->icon('heroicon-o-user')->schema([
                        Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'name')
                            ->getOptionLabelFromRecordUsing(fn($record) =>
                                trim($record->name . ' ' . ($record->last_name ?? '')) . ($record->phone ? ' — ' . $record->phone : ''))
                            ->searchable(['name','last_name','phone'])
                            ->preload()->required()->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('original_sale_id', null);
                                $set('custom_order_id', null);
                                $set('returned_items_form', []);
                                $set('total_credit', 0);
                            })
                            ->columnSpanFull(),
                    ]),

                    // ── FROM CUSTOM ORDER? ─────────────────────────────────────
                    Section::make('Exchange Source')->icon('heroicon-o-arrow-path')->schema([
                        Toggle::make('from_custom_order')
                            ->label('Is this exchange from a Custom Order?')
                            ->helperText('Enable if the customer is exchanging a custom/special order (not a regular stock purchase)')
                            ->default(false)->live()->columnSpanFull()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $set('custom_order_id', null);
                                $set('original_sale_id', null);
                                $set('returned_items_form', []);
                                $set('total_credit', 0);
                                $set('co_item_in_production', true);
                                $set('co_item_arrived', false);
                            }),
                    ]),

                    // ── CUSTOM ORDER PATH ──────────────────────────────────────
                    Section::make('Custom Order Being Exchanged')
                        ->icon('heroicon-o-sparkles')
                        ->visible(fn(Get $get) => filter_var($get('from_custom_order') ?? false, FILTER_VALIDATE_BOOLEAN))
                        ->schema([

                        Select::make('custom_order_id')
                            ->label('Select Custom Order')
                            ->options(function (Get $get) {
                                $cid = $get('customer_id');
                                if (!$cid) return [];
                                return CustomOrder::where('customer_id', $cid)
                                    ->whereNotIn('status', ['exchanged', 'cancelled'])
                                    ->get()
                                    ->mapWithKeys(fn($co) =>
                                        [$co->id => "#{$co->order_no} — {$co->product_name} | Paid: $"
                                            . number_format($co->amount_paid, 2)
                                            . " | Balance: $" . number_format($co->balance_due, 2)]
                                    );
                            })
                            ->searchable()->live()->columnSpanFull()
                            ->helperText('Select customer first')
                            ->dehydrated(true)
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                if (!$state) {
                                    $set('total_credit', 0);
                                    $set('returned_items_form', []);
                                    return;
                                }
                                $co = CustomOrder::find($state);
                                if (!$co) return;

                                // Credit = actual payments made (deposits only)
                                $totalPaid  = round(\App\Models\Payment::where('custom_order_id', $co->id)->sum('amount'), 2);

                                // Grand total with tax
                                $isTaxFree  = filter_var($co->is_tax_free ?? false, FILTER_VALIDATE_BOOLEAN);
                                $dbTax      = DB::table('site_settings')->where('key','tax_rate')->value('value') ?? 7.63;
                                $taxRate    = $isTaxFree ? 0 : floatval($dbTax) / 100;
                                $discAmt    = floatval($co->discount_amount ?? 0);
                                $afterDisc  = floatval($co->quoted_price) - $discAmt;
                                $grandTotal = round($afterDisc * (1 + $taxRate), 2);
                                $balance    = max(0, round($grandTotal - $totalPaid, 2));

                                $set('co_grand_total',   number_format($grandTotal, 2, '.', ''));
                                $set('co_total_paid',    number_format($totalPaid,  2, '.', ''));
                                $set('co_balance_due',   number_format($balance,    2, '.', ''));
                                $set('co_product_name',  $co->product_name);
                                $set('co_status',        $co->status);

                                // Credit = what they paid (deposits)
                                $set('total_credit', number_format($totalPaid, 2, '.', ''));

                                $set('returned_items_form', [[
                                    'item_source'     => 'custom_order',
                                    'custom_order_id' => $co->id,
                                    'description'     => "Custom Order #{$co->order_no} — {$co->product_name}",
                                    'stock_no'        => 'CUSTOM-'.$co->order_no,
                                    'original_price'  => $grandTotal,
                                    'credit_amount'   => $totalPaid,
                                    'returning'       => true,
                                ]]);

                                $set('co_item_in_production', true);
                                $set('co_item_arrived', false);

                                self::updateExchangeTotals($get, $set);
                            }),

                        // Custom order summary panel
                        Placeholder::make('custom_order_summary')->hiddenLabel()->columnSpanFull()->live()
                            ->visible(fn(Get $get) => filled($get('custom_order_id')))
                            ->content(function (Get $get) {
                                $co = CustomOrder::find($get('custom_order_id'));
                                if (!$co) return '';

                                $isTaxFree  = filter_var($co->is_tax_free ?? false, FILTER_VALIDATE_BOOLEAN);
                                $dbTax      = DB::table('site_settings')->where('key','tax_rate')->value('value') ?? 7.63;
                                $taxRate    = $isTaxFree ? 0 : floatval($dbTax) / 100;
                                $discAmt    = floatval($co->discount_amount ?? 0);
                                $afterDisc  = floatval($co->quoted_price) - $discAmt;
                                $grandTotal = round($afterDisc * (1 + $taxRate), 2);
                                $totalPaid  = round(\App\Models\Payment::where('custom_order_id', $co->id)->sum('amount'), 2);
                                $balance    = max(0, $grandTotal - $totalPaid);

                                $statusBg    = match($co->status) { 'in_production'=>'#fef3c7','received'=>'#dcfce7','completed'=>'#dbeafe',default=>'#f1f5f9' };
                                $statusColor = match($co->status) { 'in_production'=>'#b45309','received'=>'#15803d','completed'=>'#1d4ed8',default=>'#475569' };

                                return new HtmlString("
                                    <div style='background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px;'>
                                        <div style='display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;'>
                                            <div>
                                                <div style='font-size:14px;font-weight:800;color:#0f172a;'>#{$co->order_no} — {$co->product_name}</div>
                                                <div style='font-size:11px;color:#64748b;margin-top:2px;'>{$co->metal_type}" . ($co->due_date ? " · Due: ".date('M d, Y',strtotime($co->due_date)) : '') . "</div>
                                            </div>
                                            <span style='background:{$statusBg};color:{$statusColor};padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;'>".ucfirst(str_replace('_',' ',$co->status))."</span>
                                        </div>
                                        <div style='display:grid;grid-template-columns:repeat(3,1fr);gap:10px;'>
                                            <div style='background:#fff;border-radius:8px;padding:10px;text-align:center;border:1px solid #e2e8f0;'>
                                                <div style='font-size:9px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:3px;'>Order Total (w/ Tax)</div>
                                                <div style='font-size:1.2rem;font-weight:900;color:#0f172a;'>$".number_format($grandTotal,2)."</div>
                                            </div>
                                            <div style='background:#f0fdf4;border-radius:8px;padding:10px;text-align:center;border:1px solid #bbf7d0;'>
                                                <div style='font-size:9px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:3px;'>Total Deposited ✓</div>
                                                <div style='font-size:1.2rem;font-weight:900;color:#16a34a;'>$".number_format($totalPaid,2)."</div>
                                            </div>
                                            <div style='background:".($balance>0?'#fef2f2':'#f0fdf4').";border-radius:8px;padding:10px;text-align:center;border:1px solid ".($balance>0?'#fecaca':'#bbf7d0').";'>
                                                <div style='font-size:9px;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:3px;'>Remaining Balance</div>
                                                <div style='font-size:1.2rem;font-weight:900;color:".($balance>0?'#dc2626':'#16a34a')."'>$".number_format($balance,2)."</div>
                                            </div>
                                        </div>
                                        <div style='margin-top:10px;padding:8px 12px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;font-size:11px;color:#b45309;font-weight:600;'>
                                            💡 <strong>Exchange Credit = \$".number_format($totalPaid,2)."</strong> (total deposited). The remaining balance of \$".number_format($balance,2)." is written off when exchange completes.
                                        </div>
                                    </div>
                                ");
                            }),

                        // ── Item Status Questions ──────────────────────────────
                        Placeholder::make('co_item_status_header')->hiddenLabel()->columnSpanFull()
                            ->visible(fn(Get $get) => filled($get('custom_order_id')))
                            ->content(new HtmlString("<div style='font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#64748b;padding-top:4px;'>Custom Order Item — Current Status</div>")),

                        Grid::make(2)
                            ->visible(fn(Get $get) => filled($get('custom_order_id')))
                            ->schema([
                            Toggle::make('co_item_in_production')
                                ->label('Is the item still in production?')
                                ->helperText('Still being made by the jeweler?')
                                ->default(true)->live()->inline(false),

                            Toggle::make('co_item_arrived')
                                ->label('Has the item arrived at the store?')
                                ->helperText('Physical item is here at the store?')
                                ->default(false)->live()->inline(false)
                                ->visible(fn(Get $get) => !filter_var($get('co_item_in_production') ?? true, FILTER_VALIDATE_BOOLEAN)),
                        ]),

                        // Status consequence message
                        Placeholder::make('co_item_status_msg')->hiddenLabel()->columnSpanFull()->live()
                            ->visible(fn(Get $get) => filled($get('custom_order_id')))
                            ->content(function (Get $get) {
                                $inProd  = filter_var($get('co_item_in_production') ?? true, FILTER_VALIDATE_BOOLEAN);
                                $arrived = filter_var($get('co_item_arrived') ?? false, FILTER_VALIDATE_BOOLEAN);

                                if ($inProd) {
                                    return new HtmlString("<div style='background:#fffbeb;border:1.5px solid #f59e0b;border-left:4px solid #f59e0b;border-radius:8px;padding:12px 14px;'>
                                        <div style='font-size:12px;font-weight:700;color:#b45309;margin-bottom:4px;'>🔧 Still In Production</div>
                                        <div style='font-size:11px;color:#92400e;'>When exchange completes, the custom order will be marked <strong>VOID / EXCHANGED</strong>. No stock entry created.</div>
                                    </div>");
                                }
                                if (!$arrived) {
                                    return new HtmlString("<div style='background:#fef2f2;border:1.5px solid #ef4444;border-left:4px solid #ef4444;border-radius:8px;padding:12px 14px;'>
                                        <div style='font-size:12px;font-weight:700;color:#dc2626;margin-bottom:4px;'>📦 Item Not Yet at Store</div>
                                        <div style='font-size:11px;color:#991b1b;'>Custom order will be marked <strong>VOID / EXCHANGED</strong>. No stock entry created.</div>
                                    </div>");
                                }
                                return new HtmlString("<div style='background:#f0fdf4;border:1.5px solid #22c55e;border-left:4px solid #22c55e;border-radius:8px;padding:12px 14px;'>
                                    <div style='font-size:12px;font-weight:700;color:#15803d;margin-bottom:4px;'>✅ Item at Store — Will Go Back to Stock</div>
                                    <div style='font-size:11px;color:#166534;'>After exchange completes, this item will be added <strong>back to inventory as in_stock</strong> with a new barcode.</div>
                                </div>");
                            }),

                        Hidden::make('co_grand_total')->default(0),
                        Hidden::make('co_total_paid')->default(0),
                        Hidden::make('co_balance_due')->default(0),
                        Hidden::make('co_product_name'),
                        Hidden::make('co_status'),
                    ]),

                    // ── REGULAR SALE PATH ──────────────────────────────────────
                    Section::make('Item(s) Being Returned')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->visible(fn(Get $get) => !filter_var($get('from_custom_order') ?? false, FILTER_VALIDATE_BOOLEAN))
                        ->schema([

                        Forms\Components\Radio::make('returned_source')
                            ->label('Where was the item purchased?')
                            ->options(['our_store'=>'🏪 From our store','outside'=>'🌐 Not from our store (manual)'])
                            ->default('our_store')->live()->columnSpanFull()
                            ->afterStateUpdated(fn(Set $set) => [$set('original_sale_id', null), $set('returned_items_form', [])]),

                        Select::make('original_sale_id')
                            ->label('Search Invoice / Job Number')
                            ->options(function (Get $get) {
                                $cid = $get('customer_id');
                                if (!$cid) return [];
                                return Sale::where('customer_id', $cid)->where('status','completed')
                                    ->orderByDesc('completed_at')->get()
                                    ->mapWithKeys(fn($s) => [$s->id =>
                                        $s->invoice_number . ' — $' . number_format($s->final_total, 2) .
                                        ' (' . ($s->completed_at ? \Carbon\Carbon::parse($s->completed_at)->format('M d, Y') : 'N/A') . ')']);
                            })
                            ->searchable()->live()->dehydrated(true)
                            ->dehydrateStateUsing(fn($state) => empty($state) ? (\App\Models\Sale::latest()->value('id') ?? 1) : $state)
                            ->visible(fn(Get $get) => $get('returned_source') === 'our_store')
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                if (!$state) return;
                                $sale = Sale::with(['items.productItem'])->find($state);
                                if (!$sale) return;

                                $dbTax   = DB::table('site_settings')->where('key','tax_rate')->value('value') ?? 7.63;
                                $taxRate = floatval($dbTax) / 100;

                                $items = $sale->items->whereNotNull('product_item_id')
                                    ->map(function ($si) use ($taxRate) {
                                        $preTax    = floatval($si->sale_price_override ?? ($si->sold_price * ($si->qty ?? 1)));
                                        $isTaxFree = filter_var($si->is_tax_free ?? false, FILTER_VALIDATE_BOOLEAN);
                                        $withTax   = round($preTax * (1 + ($isTaxFree ? 0 : $taxRate)), 2);
                                        return [
                                            'item_source'     => 'stock',
                                            'sale_item_id'    => $si->id,
                                            'product_item_id' => $si->product_item_id,
                                            'description'     => $si->custom_description ?? $si->productItem?->custom_description ?? 'Item',
                                            'stock_no'        => $si->productItem?->barcode ?? '—',
                                            'original_price'  => $withTax,
                                            'credit_amount'   => $withTax,
                                            'returning'       => true,
                                        ];
                                    })->values()->toArray();

                                $set('returned_items_form', $items);
                                $total = collect($items)->sum(fn($i) => floatval($i['credit_amount'] ?? 0));
                                $set('total_credit', number_format($total, 2, '.', ''));
                                self::updateExchangeTotals($get, $set);
                            })
                            ->helperText('Select customer first')->columnSpanFull(),

                        Placeholder::make('sale_items_preview')->hiddenLabel()->columnSpanFull()
                            ->visible(fn(Get $get) => $get('returned_source') === 'our_store' && filled($get('original_sale_id')))
                            ->content(function (Get $get) {
                                $sale = Sale::with(['items.productItem'])->find($get('original_sale_id'));
                                if (!$sale) return '';
                                $dbTax   = DB::table('site_settings')->where('key','tax_rate')->value('value') ?? 7.63;
                                $taxRate = floatval($dbTax) / 100;
                                $rows = $sale->items->map(function ($si) use ($taxRate) {
                                    $preTax    = floatval($si->sale_price_override ?? ($si->sold_price * ($si->qty ?? 1)));
                                    $isTaxFree = filter_var($si->is_tax_free ?? false, FILTER_VALIDATE_BOOLEAN);
                                    $withTax   = round($preTax * (1 + ($isTaxFree ? 0 : $taxRate)), 2);
                                    return "<tr>
                                        <td style='padding:5px 10px;font-family:monospace;font-weight:700;color:#0369a1;'>" . ($si->productItem?->barcode ?? '—') . "</td>
                                        <td style='padding:5px 10px;color:#374151;'>" . Str::limit($si->custom_description ?? '', 45) . "</td>
                                        <td style='padding:5px 10px;font-weight:700;color:#059669;text-align:right;'>\$" . number_format($withTax, 2) . " <span style='color:#94a3b8;font-size:10px;'>(incl. tax)</span></td>
                                    </tr>";
                                })->implode('');
                                return new HtmlString("<div style='background:#f0fdf4;border:1px solid #86efac;border-radius:8px;overflow:hidden;'>
                                    <div style='padding:8px 12px;background:#dcfce7;font-size:11px;font-weight:800;color:#065f46;'>📦 Items in Invoice " . $sale->invoice_number . "</div>
                                    <table style='width:100%;border-collapse:collapse;font-size:12px;'><tbody>{$rows}</tbody></table></div>");
                            }),

                        Repeater::make('returned_items_form')->label('Returned Items')->columnSpanFull()
                            ->schema([
                                Toggle::make('returning')->label('Include')->default(true)->live()->inline(false),
                                TextInput::make('stock_no')->label('Stock #')->disabled()
                                    ->visible(fn(Get $get) => ($get('item_source') ?? 'stock') === 'stock'),
                                TextInput::make('description')->label('Description')
                                    ->disabled(fn(Get $get) => ($get('item_source') ?? 'stock') !== 'outside')->columnSpan(2),
                                TextInput::make('original_price')->label('Original Price (incl. tax)')->prefix('$')->disabled()
                                    ->visible(fn(Get $get) => ($get('item_source') ?? 'stock') === 'stock'),
                                TextInput::make('credit_amount')->label('Credit ($)')->prefix('$')->numeric()->required()
                                    ->live(debounce:400)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $total = collect($get('../../returned_items_form') ?? [])
                                            ->filter(fn($i) => !empty($i['returning']))
                                            ->sum(fn($i) => floatval($i['credit_amount'] ?? 0));
                                        $set('../../total_credit', number_format($total, 2, '.', ''));
                                        self::updateExchangeTotals($get, $set);
                                    }),
                                Hidden::make('item_source')->default('stock'),
                                Hidden::make('sale_item_id'),
                                Hidden::make('product_item_id'),
                                Hidden::make('custom_order_id'),
                            ])
                            ->columns(3)->reorderable(false)
                            ->deletable(fn(Get $get) => ($get('returned_source') ?? 'our_store') === 'outside')
                            ->addable(fn(Get $get) => ($get('returned_source') ?? 'our_store') === 'outside')
                            ->addActionLabel('+ Add Returned Item')
                            ->itemLabel(fn(array $state) =>
                                ($state['stock_no'] ?? ($state['description'] ?? 'Item')) . ' — Credit: $' . number_format($state['credit_amount'] ?? 0, 2))
                            ->visible(fn(Get $get) =>
                                ($get('returned_source') === 'our_store' && filled($get('original_sale_id'))) ||
                                $get('returned_source') === 'outside')
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                $total = collect($state)->filter(fn($i) => !empty($i['returning']))->sum(fn($i) => floatval($i['credit_amount'] ?? 0));
                                $set('total_credit', number_format($total, 2, '.', ''));
                                self::updateExchangeTotals($get, $set);
                            }),
                    ]),

                    // ── NEW ITEMS ──────────────────────────────────────────────
                    Section::make('New Item(s) Customer is Taking')->icon('heroicon-o-shopping-cart')
                        ->visible(fn(Get $get) =>
                            filter_var($get('from_custom_order') ?? false, FILTER_VALIDATE_BOOLEAN)
                                ? filled($get('custom_order_id'))
                                : (($get('returned_source') === 'our_store' && filled($get('original_sale_id'))) || $get('returned_source') === 'outside')
                        )
                        ->description('Search stock, add non-tag items, or create a custom order')->schema([

                        Grid::make(4)->schema([
                            Select::make('current_item_search')
                                ->label('Select Stock #')->searchable()->dehydrated(false)
                                ->getSearchResultsUsing(fn(string $search) => ProductItem::where('status','in_stock')
                                    ->where(fn($q) => $q->where('barcode','like',"%{$search}%")->orWhere('custom_description','like',"%{$search}%"))
                                    ->limit(30)->get()
                                    ->mapWithKeys(fn($i) => [$i->id => "{$i->barcode} — " . Str::limit($i->custom_description ?? '', 35) . " (\${$i->retail_price})"]))
                                ->live()
                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                    if (!$state) return;
                                    $item = ProductItem::find($state);
                                    if (!$item) return;
                                    $qty       = $get('current_item_qty') ?? 1;
                                    $lineTotal = $item->retail_price * $qty;
                                    $rows      = $get('new_items_form') ?? [];
                                    $newId     = (string) Str::uuid();
                                    $rows[$newId] = [
                                        'selection_type'           => 'stock',
                                        'product_item_id'          => $item->id,
                                        'stock_no_display'         => $item->barcode,
                                        'stock_no_display_persist' => $item->barcode,
                                        'custom_description'       => $item->custom_description ?? $item->barcode,
                                        'qty'                      => $qty,
                                        'sold_price'               => $item->retail_price,
                                        'sale_price_override'      => $lineTotal,
                                        'discount_percent'         => 0,
                                        'discount_amount'          => 0,
                                        'is_tax_free'              => false,
                                        'custom_order_id'          => null,
                                        'is_new_custom_order'      => false,
                                    ];
                                    $set('new_items_form', $rows);
                                    $set('current_item_search', null);
                                    $set('current_item_qty', 1);
                                    self::updateExchangeTotals($get, $set);
                                })
                                ->columnSpan(3),
                            TextInput::make('current_item_qty')->label('Qty')->numeric()->default(1)->dehydrated(false)->live(),
                        ]),

                        Forms\Components\Actions::make([
                            FormAction::make('add_non_tag')
                                ->label('+ Non-Tag Item')->color('gray')->outlined()->icon('heroicon-o-plus-circle')
                                ->modalHeading('Add Non-Tag Item')->modalWidth('lg')->modalSubmitActionLabel('Add to Exchange')
                                ->form([
                                    TextInput::make('description')->label('Description')->required()->placeholder('e.g. Engraving, Cleaning Fee'),
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
                                    $price     = floatval($data['price'] ?? 0);
                                    $qty       = intval($data['qty'] ?? 1);
                                    $discPct   = floatval($data['discount_percent'] ?? 0);
                                    $lineTotal = $price * $qty;
                                    $discAmt   = $lineTotal * ($discPct / 100);
                                    $final     = $lineTotal - $discAmt;
                                    $rows      = $get('new_items_form') ?? [];
                                    $newId     = (string) Str::uuid();
                                    $rows[$newId] = [
                                        'selection_type'           => 'non_tag',
                                        'product_item_id'          => null,
                                        'custom_order_id'          => null,
                                        'is_new_custom_order'      => false,
                                        'stock_no_display'         => 'NON-TAG',
                                        'stock_no_display_persist' => 'NON-TAG',
                                        'custom_description'       => $data['description'],
                                        'qty'                      => $qty,
                                        'sold_price'               => $price,
                                        'sale_price_override'      => $final,
                                        'discount_percent'         => $discPct,
                                        'discount_amount'          => $discAmt,
                                        'is_tax_free'              => $data['is_tax_free'] ?? false,
                                    ];
                                    $set('new_items_form', $rows);
                                    self::updateExchangeTotals($get, $set);
                                }),

                            FormAction::make('create_custom_order')
                                ->label('+ New Custom Order')->color('success')->outlined()->icon('heroicon-o-paint-brush')
                                ->modalHeading('Design New Custom Piece')->modalWidth('3xl')->modalSubmitActionLabel('Add to Exchange')
                                ->form([
                                    Grid::make(2)->schema([
                                        Select::make('product_name')->label('What are we making?')
                                            ->options(fn() => CustomOrder::whereNotNull('product_name')->pluck('product_name','product_name')->unique())
                                            ->searchable()
                                            ->createOptionForm([TextInput::make('new_product_name')->required()->label('New Product Name')])
                                            ->createOptionUsing(fn($data) => $data['new_product_name'])->required(),
                                        Select::make('metal_type')->label('Metal Type')
                                            ->options(['10k'=>'10k Gold','14k'=>'14k Gold','18k'=>'18k Gold','platinum'=>'Platinum','silver'=>'Silver','other'=>'Other'])
                                            ->default('14k')->required(),
                                    ]),
                                    Grid::make(3)->schema([
                                        TextInput::make('quoted_price')->label('Total Item Price')->numeric()->prefix('$')->required()->live(onBlur:true)
                                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                $price = floatval($state ?? 0);
                                                $discAmt = min($price, floatval($get('discount_amount') ?? 0));
                                                $set('discount_amount', round($discAmt,2));
                                                $set('discount_percent', $price > 0 ? round(($discAmt/$price)*100,2) : 0);
                                                $set('sale_price_display', round($price-$discAmt,2));
                                            }),
                                        Placeholder::make('total_with_tax')->label('Total (w/ Tax)')
                                            ->content(function (Get $get) {
                                                $q = floatval($get('quoted_price') ?? 0);
                                                $d = min($q, floatval($get('discount_amount') ?? 0));
                                                $r = filter_var($get('is_tax_free') ?? false, FILTER_VALIDATE_BOOLEAN) ? 0 : floatval(DB::table('site_settings')->where('key','tax_rate')->value('value') ?? 7.63) / 100;
                                                return new HtmlString("<div class='text-2xl font-black text-gray-900 mt-1'>$" . number_format(($q-$d)*(1+$r),2) . "</div>");
                                            }),
                                        TextInput::make('custom_deposit_amount')->label('Deposit Now')->numeric()->prefix('$')->default(0),
                                    ]),
                                    Grid::make(3)->schema([
                                        TextInput::make('discount_percent')->label('Disc %')->numeric()->suffix('%')->default(0)->live(onBlur:true)
                                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                $price = floatval($get('quoted_price') ?? 0);
                                                $disc  = $price * min(100,max(0,floatval($state??0))) / 100;
                                                $set('discount_amount', round($disc,2));
                                                $set('sale_price_display', round($price-$disc,2));
                                            }),
                                        TextInput::make('discount_amount')->label('Disc $')->numeric()->prefix('$')->default(0)->live(onBlur:true)
                                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                $price = floatval($get('quoted_price') ?? 0);
                                                $disc  = min($price,max(0,floatval($state??0)));
                                                $set('discount_percent', $price > 0 ? round(($disc/$price)*100,2) : 0);
                                                $set('sale_price_display', round($price-$disc,2));
                                            }),
                                        TextInput::make('sale_price_display')->label('Sale Price')->numeric()->prefix('$')->default(0)->dehydrated(false)->live(onBlur:true)
                                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                $price = floatval($get('quoted_price') ?? 0);
                                                $sp    = min($price,max(0,floatval($state??0)));
                                                $disc  = $price - $sp;
                                                $set('discount_amount', round($disc,2));
                                                $set('discount_percent', $price > 0 ? round(($disc/$price)*100,2) : 0);
                                            }),
                                    ]),
                                    Grid::make(3)->schema([
                                        Select::make('deposit_method')->label('Deposit Payment Method')
                                            ->options(fn() => SaleResource::getPaymentOptions())->default('CASH')->required(),
                                        Placeholder::make('spacer')->label('')->content(''),
                                        Toggle::make('is_tax_free')->label('Tax Free?')->default(false)->inline(false)->live(),
                                    ]),
                                    Forms\Components\DatePicker::make('due_date')->label('Promised By Date')->displayFormat('m/d/Y')->native(false),
                                    Textarea::make('design_notes')->label('Design Notes & Instructions')->rows(2),
                                ])
                                ->action(function (array $data, Get $get, Set $set) {
                                    $price         = floatval($data['quoted_price']);
                                    $discPct       = min(100,max(0,floatval($data['discount_percent']??0)));
                                    $discAmt       = min($price,max(0,floatval($data['discount_amount']??($price*$discPct/100))));
                                    $discPct       = $price > 0 ? round(($discAmt/$price)*100,2) : $discPct;
                                    $afterDiscount = max(0, $price - $discAmt);
                                    $isTaxFree     = $data['is_tax_free'] ?? false;
                                    $deposit       = floatval($data['custom_deposit_amount'] ?? 0);
                                    $draftId       = (string) Str::uuid();
                                    $data['draft_id']         = $draftId;
                                    $data['discount_percent'] = $discPct;
                                    $data['discount_amount']  = $discAmt;
                                    $rows = $get('new_items_form') ?? [];
                                    $rows[$draftId] = [
                                        'selection_type'           => 'custom_order',
                                        'product_item_id'          => null,
                                        'custom_order_id'          => null,
                                        'is_new_custom_order'      => true,
                                        'new_custom_data'          => json_encode($data),
                                        'stock_no_display'         => 'NEW CUSTOM',
                                        'stock_no_display_persist' => 'NEW CUSTOM',
                                        'custom_description'       => "CUSTOM Order: {$data['product_name']}\nMetal: {$data['metal_type']}\n" . ($data['design_notes'] ?? ''),
                                        'qty'                      => 1,
                                        'sold_price'               => $afterDiscount,
                                        'sale_price_override'      => $afterDiscount,
                                        'discount_percent'         => $discPct,
                                        'discount_amount'          => $discAmt,
                                        'is_tax_free'              => $isTaxFree,
                                    ];
                                    $set('new_items_form', $rows);
                                    if ($deposit > 0) {
                                        $set('is_split_payment', true);
                                        $splits = $get('split_payments') ?? [];
                                        $splits[$draftId] = ['method'=>$data['deposit_method']??'CASH','amount'=>$deposit];
                                        $set('split_payments', $splits);
                                    }
                                    self::updateExchangeTotals($get, $set);
                                }),
                        ]),

                        Repeater::make('new_items_form')->label('Items in this Exchange')
                            ->hidden(fn(Get $get) => empty(collect($get('new_items_form') ?? [])->filter(fn($i) => filled($i['selection_type'] ?? null))->all()))
                            ->schema([
                                Hidden::make('selection_type'),
                                Hidden::make('product_item_id'),
                                Hidden::make('custom_order_id'),
                                Hidden::make('is_new_custom_order')->default(false),
                                Hidden::make('new_custom_data'),
                                Hidden::make('stock_no_display_persist'),

                                TextInput::make('stock_no_display')->label('Item')->readOnly()->dehydrated(false)
                                    ->extraInputAttributes(fn($state) => ['class' => str_contains($state ?? '', 'CUSTOM') ? 'text-blue-600 font-bold bg-blue-50' : ''])
                                    ->columnSpan(2),

                                Textarea::make('custom_description')->label('Description')->rows(2)->autosize(false)
                                    ->extraInputAttributes(['style'=>'max-height:55px;overflow-y:auto;resize:none;'])
                                    ->readOnly(fn(Get $get) => $get('is_new_custom_order') === true || !empty($get('custom_order_id')))
                                    ->columnSpan(3),

                                TextInput::make('qty')->numeric()->default(1)->live(onBlur:true)->columnSpan(1)
                                    ->readOnly(fn(Get $get) => $get('is_new_custom_order') === true || !empty($get('custom_order_id')))
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        $price = floatval($get('sold_price') ?? 0);
                                        $pct   = floatval($get('discount_percent') ?? 0);
                                        $line  = $price * intval($state ?? 1);
                                        $disc  = $line * $pct / 100;
                                        $set('discount_amount', number_format($disc,2,'.','')); 
                                        $set('sale_price_override', number_format($line-$disc,2,'.','')); 
                                        self::updateExchangeTotals($get, $set);
                                    }),

                                TextInput::make('sold_price')->label('Price')->numeric()->live(onBlur:true)->columnSpan(2)
                                    ->readOnly(fn(Get $get) => $get('is_new_custom_order') === true || !empty($get('custom_order_id')))
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        $qty  = intval($get('qty') ?? 1);
                                        $pct  = floatval($get('discount_percent') ?? 0);
                                        $line = floatval($state) * $qty;
                                        $disc = $line * $pct / 100;
                                        $set('discount_amount', number_format($disc,2,'.','')); 
                                        $set('sale_price_override', number_format($line-$disc,2,'.','')); 
                                        self::updateExchangeTotals($get, $set);
                                    }),

                                TextInput::make('sale_price_override')->label('Sale Price')->live(onBlur:true)->columnSpan(2)
                                    ->readOnly(fn(Get $get) => $get('is_new_custom_order') === true || !empty($get('custom_order_id')))
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        $price = floatval($get('sold_price') ?? 0);
                                        $qty   = intval($get('qty') ?? 1);
                                        $orig  = $price * $qty;
                                        $final = floatval($state ?? 0);
                                        if ($orig > 0) {
                                            $disc = $orig - $final;
                                            $set('discount_amount', number_format($disc,2,'.','')); 
                                            $set('discount_percent', number_format(($disc/$orig)*100,2,'.','')); 
                                        }
                                        self::updateExchangeTotals($get, $set);
                                    }),

                                TextInput::make('discount_percent')->label('Disc %')->numeric()->suffix('%')->default(0)->live(onBlur:true)->columnSpan(2)
                                    ->readOnly(fn(Get $get) => $get('is_new_custom_order') === true || !empty($get('custom_order_id')))
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        $price = floatval($get('sold_price') ?? 0);
                                        $qty   = intval($get('qty') ?? 1);
                                        $line  = $price * $qty;
                                        $disc  = $line * floatval($state ?? 0) / 100;
                                        $set('discount_amount', number_format($disc,2,'.','')); 
                                        $set('sale_price_override', number_format($line-$disc,2,'.','')); 
                                        self::updateExchangeTotals($get, $set);
                                    }),

                                TextInput::make('discount_amount')->label('Disc $')->numeric()->prefix('$')->default(0)->live(onBlur:true)->columnSpan(2)
                                    ->readOnly(fn(Get $get) => $get('is_new_custom_order') === true || !empty($get('custom_order_id')))
                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                        $price = floatval($get('sold_price') ?? 0);
                                        $qty   = intval($get('qty') ?? 1);
                                        $line  = $price * $qty;
                                        $disc  = floatval($state ?? 0);
                                        if ($line > 0) $set('discount_percent', number_format(($disc/$line)*100,2,'.','')); 
                                        $set('sale_price_override', number_format($line-$disc,2,'.','')); 
                                        self::updateExchangeTotals($get, $set);
                                    }),

                                Checkbox::make('is_tax_free')->label('No Tax')->default(false)->live()->columnSpan(1)
                                    ->disabled(fn(Get $get) => $get('is_new_custom_order') === true || !empty($get('custom_order_id')))
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::updateExchangeTotals($get, $set)),
                            ])
                            ->columns(12)->addable(false)->reorderable(false)->deletable(true)->live()->defaultItems(0)
                            ->afterStateUpdated(fn(Get $get, Set $set) => self::updateExchangeTotals($get, $set)),
                    ]),

                    Section::make('Reason & Notes')->icon('heroicon-o-chat-bubble-left-right')->schema([
                        Textarea::make('reason')->label("Customer's Reason")->required()->rows(2)->columnSpanFull(),
                        Textarea::make('staff_notes')->label('Internal Staff Notes')->rows(2)->columnSpanFull(),
                        Textarea::make('rejection_reason')->label('Rejection Reason')->rows(2)->columnSpanFull()
                            ->visible(fn($record) => $record?->status === 'rejected'),
                    ]),
                ]),

                // ══ RIGHT ═════════════════════════════════════════════════════
                Forms\Components\Group::make()->columnSpan(4)->schema([

                    Section::make('Exchange Summary')->icon('heroicon-o-arrows-right-left')->schema([
                        TextInput::make('total_credit')->label('Total Credit ($)')->prefix('$')->readOnly()
                            ->extraInputAttributes(['class'=>'font-bold text-danger-600']),
                        TextInput::make('new_sale_amount')->label('New Items Total (incl. tax)')->prefix('$')->readOnly()
                            ->extraInputAttributes(['class'=>'font-bold text-success-600']),

                        Placeholder::make('tax_breakdown_display')->label('Tax Breakdown')->live()
                            ->content(function (Get $get) {
                                $sub = floatval($get('new_items_subtotal_display') ?? 0);
                                $tax = floatval($get('new_items_tax_display') ?? 0);
                                if ($sub <= 0) return new HtmlString('<span style="color:#94a3b8;font-size:12px;">Add items to see tax breakdown</span>');
                                $dbTax = DB::table('site_settings')->where('key','tax_rate')->value('value') ?? 7.63;
                                return new HtmlString("
                                    <div style='background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;font-size:12px;'>
                                        <div style='display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #f1f5f9;'>
                                            <span style='color:#64748b;'>Subtotal (pre-tax)</span>
                                            <span style='font-weight:700;'>$".number_format($sub,2)."</span>
                                        </div>
                                        <div style='display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #f1f5f9;'>
                                            <span style='color:#64748b;'>Tax ({$dbTax}%)</span>
                                            <span style='font-weight:700;color:#f59e0b;'>+$".number_format($tax,2)."</span>
                                        </div>
                                        <div style='display:flex;justify-content:space-between;padding:6px 0;'>
                                            <span style='font-weight:800;color:#0f172a;'>Total (incl. tax)</span>
                                            <span style='font-weight:900;color:#059669;font-size:1.1rem;'>$".number_format($sub+$tax,2)."</span>
                                        </div>
                                    </div>");
                            }),

                        Select::make('exchange_type')->label('Exchange Type')
                            ->options(['same_value'=>'↔️ Same Value','upgrade'=>'⬆️ Upgrade','downgrade'=>'⬇️ Downgrade'])
                            ->disabled()->dehydrated(true),

                        Placeholder::make('difference_display')->label('Difference')->live()
                            ->content(function (Get $get) {
                                $diff = floatval($get('difference_amount') ?? 0);
                                if ($diff > 0.009)
                                    return new HtmlString("<span style='font-size:1.4rem;font-weight:900;color:#059669;'>+\$".number_format($diff,2)."<br><small style='font-size:0.75rem;color:#64748b;'>Customer pays this</small></span>");
                                if ($diff < -0.009)
                                    return new HtmlString("<span style='font-size:1.4rem;font-weight:900;color:#B8463F;'>-\$".number_format(abs($diff),2)."<br><small style='font-size:0.75rem;color:#64748b;'>Store refunds this</small></span>");
                                return new HtmlString("<span style='font-size:1.4rem;font-weight:900;color:#059669;'>\$0.00 ↔ Same Value</span>");
                            }),
                        TextInput::make('difference_amount')->label('Difference ($)')->prefix('$')->numeric()->disabled(),
                    ]),

                    Section::make('Payment for Difference')->icon('heroicon-o-credit-card')
                        ->visible(fn(Get $get) => abs(floatval($get('difference_amount') ?? 0)) > 0.009)->schema([

                        Placeholder::make('display_difference_due')->label('Amount to Collect')
                            ->visible(fn(Get $get) => floatval($get('difference_amount') ?? 0) > 0.009)->live()
                            ->content(function (Get $get) {
                                $diff = floatval($get('difference_amount') ?? 0);
                                return new HtmlString("<div style='background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:12px 16px;'>
                                    <span style='font-size:1.75rem;font-weight:900;color:#0284c7;'>\$".number_format($diff,2)."</span></div>");
                            }),

                        Toggle::make('is_split_payment')->label('Split Payment')->live()->columnSpanFull()
                            ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                if ($state && empty($get('split_payments'))) {
                                    $diff = floatval($get('difference_amount') ?? 0);
                                    if ($diff > 0) $set('split_payments', [['method'=>'CASH','amount'=>number_format($diff,2,'.','')] ]);
                                }
                            }),

                        Select::make('difference_payment_method')->label('Payment Method')
                            ->options(fn() => SaleResource::getPaymentOptions() + ['STORE_CREDIT'=>'Store Credit'])
                            ->visible(fn(Get $get) => !$get('is_split_payment') && floatval($get('difference_amount') ?? 0) > 0.009)
                            ->live(),

                        TextInput::make('amount_received')->label('Amount Received')->numeric()->prefix('$')->live(onBlur:true)
                            ->visible(fn(Get $get) => !$get('is_split_payment') && floatval($get('difference_amount') ?? 0) > 0.009)
                            ->helperText(function (Get $get) {
                                $rem   = max(0, floatval($get('difference_amount') ?? 0) - floatval($get('amount_received') ?? 0));
                                $color = $rem <= 0.009 ? '#16a34a' : '#dc2626';
                                return new HtmlString("<strong style='color:{$color};'>Remaining: " . ($rem <= 0.009 ? '✅ $0.00' : '$'.number_format($rem,2)) . "</strong>");
                            })
                            ->hintAction(FormAction::make('fill_diff')->label('Collect Remaining')->icon('heroicon-o-banknotes')->color('success')
                                ->action(fn(Get $get, Set $set) => $set('amount_received', number_format(floatval($get('difference_amount') ?? 0),2,'.','')))
                            ),

                        Placeholder::make('change_given_display')->label('Change Given')->live()
                            ->visible(fn(Get $get) => !$get('is_split_payment') && floatval($get('difference_amount') ?? 0) > 0.009)
                            ->content(function (Get $get) {
                                $change = max(0, floatval($get('amount_received') ?? 0) - floatval($get('difference_amount') ?? 0));
                                if ($change > 0.009) return new HtmlString("<span style='font-size:1.2rem;font-weight:900;color:#2563eb;'>\$".number_format($change,2)."</span>");
                                return new HtmlString("<span style='color:#94a3b8;'>$0.00</span>");
                            }),

                        Repeater::make('split_payments')->label('Payment Breakdown')
                            ->visible(fn(Get $get) => $get('is_split_payment') && floatval($get('difference_amount') ?? 0) > 0.009)
                            ->schema([
                                Grid::make(2)->schema([
                                    Select::make('method')->options(fn() => SaleResource::getPaymentOptions())->required(),
                                    TextInput::make('amount')->numeric()->prefix('$')->required()->live(onBlur:true)
                                        ->hintAction(FormAction::make('fill_remaining')->label('Fill Remaining')->icon('heroicon-o-banknotes')->color('success')
                                            ->visible(fn(Get $get) => floatval($get('amount') ?? 0) == 0)
                                            ->action(function (Get $get, Set $set) {
                                                $diff = floatval($get('../../difference_amount') ?? 0);
                                                $sum  = collect($get('../../split_payments') ?? [])->sum(fn($p) => floatval($p['amount'] ?? 0));
                                                $set('amount', number_format(max(0,$diff-$sum),2,'.','')); 
                                            })
                                        ),
                                ]),
                            ])
                            ->addActionLabel('+ Add Payment Method')->reorderable(false)->defaultItems(0)->live()->columnSpanFull(),

                        Placeholder::make('split_remaining')->label('Remaining Balance')->live()
                            ->visible(fn(Get $get) => $get('is_split_payment') && floatval($get('difference_amount') ?? 0) > 0.009)
                            ->content(function (Get $get) {
                                $diff = floatval($get('difference_amount') ?? 0);
                                $sum  = collect($get('split_payments') ?? [])->sum(fn($p) => floatval($p['amount'] ?? 0));
                                $rem  = $diff - $sum;
                                $color = abs($rem) < 0.009 ? '#16a34a' : '#0284c7';
                                return new HtmlString("<span style='font-size:1.1rem;font-weight:900;color:{$color};'>\$".number_format(max(0,$rem),2)."</span>");
                            }),
                    ]),

                    Section::make('Staff')->schema([
                        Select::make('sales_person_list')->label('Sales Staff')->multiple()
                            ->options(fn() => \App\Models\User::orderBy('name')->pluck('name','name')->toArray())
                            ->default(fn() => [\Illuminate\Support\Facades\Session::get('active_staff_name') ?? auth()->user()->name])
                            ->required()->columnSpanFull(),
                    ]),

                    // ── Hidden fields — ALL must be here so they save to DB ─────
                    Hidden::make('total_credit')->default(0),
                    Hidden::make('new_sale_amount')->default(0),
                    Hidden::make('difference_amount')->default(0),
                    Hidden::make('new_items_subtotal_display'),
                    Hidden::make('new_items_tax_display'),
                    Hidden::make('custom_order_id'),          // ← stores originating CO id
                    Hidden::make('from_custom_order')->default(false),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('exchange_no')
                ->label('Exchange #')
                ->weight('bold')
                ->color('primary')
                ->searchable()
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->copyable()
                ->copyMessage('Exchange # copied'),

            Tables\Columns\TextColumn::make('customer.name')
                ->label('Customer')
                ->getStateUsing(fn($record) => trim(($record->customer?->name ?? '') . ' ' . ($record->customer?->last_name ?? '')))
                ->description(fn($record) => $record->customer?->phone ?? '')
                ->searchable(query: function ($query, string $search) {
                    return $query->whereHas('customer', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%")
                          ->orWhere('phone', 'like', "%{$search}%");
                    });
                })
                ->icon('heroicon-o-user')
                ->weight('semibold'),

            Tables\Columns\TextColumn::make('source')
                ->label('Source')
                ->badge()
                ->getStateUsing(fn($record) => filter_var($record->from_custom_order ?? false, FILTER_VALIDATE_BOOLEAN) ? 'custom_order' : 'stock_sale')
                ->formatStateUsing(fn($state) => $state === 'custom_order' ? '✨ Custom Order' : '🏪 Stock Sale')
                ->color(fn($state) => $state === 'custom_order' ? 'purple' : 'gray'),

            Tables\Columns\TextColumn::make('originalSale.invoice_number')
                ->label('Original Invoice')
                ->badge()
                ->color('gray')
                ->placeholder('—')
                ->toggleable(),

            Tables\Columns\TextColumn::make('exchange_type')
                ->label('Type')
                ->badge()
                ->formatStateUsing(fn($state) => match($state) {
                    'upgrade'    => '⬆️ Upgrade',
                    'downgrade'  => '⬇️ Downgrade',
                    'same_value' => '↔️ Same Value',
                    default      => ucfirst($state ?? ''),
                })
                ->color(fn($state) => match($state) {
                    'upgrade'   => 'success',
                    'downgrade' => 'danger',
                    default     => 'gray',
                }),

            Tables\Columns\TextColumn::make('total_credit')
                ->label('Credit')
                ->formatStateUsing(fn($state) => '$' . number_format($state, 2))
                ->color('warning')
                ->weight('semibold')
                ->alignEnd(),

            Tables\Columns\TextColumn::make('new_sale_amount')
                ->label('New Total')
                ->formatStateUsing(fn($state) => '$' . number_format($state, 2))
                ->color('success')
                ->weight('semibold')
                ->alignEnd()
                ->toggleable(),

            Tables\Columns\TextColumn::make('difference_amount')
                ->label('Difference')
                ->html()
                ->alignEnd()
                ->formatStateUsing(function ($state) {
                    $amt   = floatval($state);
                    $color = $amt > 0 ? '#059669' : ($amt < 0 ? '#B8463F' : '#64748b');
                    $label = $amt > 0
                        ? '+$' . number_format($amt, 2)
                        : ($amt < 0 ? '-$' . number_format(abs($amt), 2) : '$0.00');
                    return "<span style='color:{$color};font-weight:800;'>{$label}</span>";
                }),

            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn($state) => match($state) {
                    'pending_approval' => 'warning',
                    'approved'         => 'info',
                    'completed'        => 'success',
                    'rejected'         => 'danger',
                    'exchanged'        => 'purple',
                    default            => 'gray',
                })
                ->formatStateUsing(fn($state) => match($state) {
                    'exchanged'         => '🔄 Exchanged',
                    'pending_approval'  => '⏳ Pending',
                    'approved'          => '✅ Approved',
                    'completed'         => '🎉 Completed',
                    'rejected'          => '❌ Rejected',
                    default             => ucfirst(str_replace('_', ' ', $state ?? '')),
                }),

            Tables\Columns\TextColumn::make('requester.name')
                ->label('Requested By')
                ->icon('heroicon-o-user-circle')
                ->color('gray')
                ->toggleable(isToggledHiddenByDefault: true),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Date')
                ->date('M d, Y')
                ->description(fn($record) => $record->created_at?->format('h:i A'))
                ->sortable()
                ->color('gray'),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('status')
                ->options([
                    'pending_approval' => 'Pending',
                    'approved'         => 'Approved',
                    'completed'        => 'Completed',
                    'rejected'         => 'Rejected',
                    'exchanged'        => 'Exchanged',
                ]),
            Tables\Filters\SelectFilter::make('exchange_type')
                ->options([
                    'upgrade'    => 'Upgrade',
                    'downgrade'  => 'Downgrade',
                    'same_value' => 'Same Value',
                ]),
            Tables\Filters\TernaryFilter::make('from_custom_order')
                ->label('Custom Order Exchange')
                ->placeholder('All Sources')
                ->trueLabel('Custom Order Only')
                ->falseLabel('Stock Sale Only'),
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
                    Notification::make()->title('Exchange Approved ✅')->success()->send();
                    if ($record->requester) {
                        Notification::make()
                            ->title('Exchange Approved')
                            ->body("Exchange {$record->exchange_no} approved.")
                            ->success()
                            ->sendToDatabase($record->requester);
                    }
                }),

            Tables\Actions\Action::make('complete')
                ->label('Complete & Create Sale')
                ->icon('heroicon-o-flag')
                ->color('info')
                ->visible(fn($record) => $record->status === 'approved')
                ->requiresConfirmation()
                ->modalHeading('Complete Exchange?')
                ->modalDescription('This will void the custom order (if applicable), update inventory, and create a Sale record with $0 balance.')
                ->action(fn($record) => static::completeExchange($record)),

            Tables\Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn($record) => $record->status === 'pending_approval' && auth()->user()->hasAnyRole(['Superadmin', 'Administration', 'Manager']))
                ->form([Textarea::make('rejection_reason')->required()->rows(2)])
                ->action(function ($record, array $data) {
                    $record->update(['status' => 'rejected', 'approved_by' => auth()->id(), 'rejection_reason' => $data['rejection_reason']]);
                    Notification::make()->title('Exchange Rejected')->warning()->send();
                }),

            Tables\Actions\Action::make('print')
                ->label('Print Receipt')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn($record) => route('exchange.print', $record))
                ->openUrlInNewTab(),

            Tables\Actions\EditAction::make()
                ->visible(fn($record) => !in_array($record->status, ['completed', 'exchanged'])),
        ])
        ->defaultSort('created_at', 'desc')
        ->striped()
        ->paginated([10, 25, 50, 100])
        ->poll('30s');
}

    public static function completeExchange($record): void
    {
        DB::transaction(function () use ($record) {
            $storeId         = $record->store_id ?? auth()->user()->store_id ?? 1;
            $newItemsRaw     = $record->new_items ?? [];
            $dbTax           = DB::table('site_settings')->where('key','tax_rate')->value('value') ?? 7.63;
            $taxRate         = floatval($dbTax) / 100;

            // ── The ORIGINAL custom order being voided ─────────────────────
            $fromCustomOrder = filter_var($record->from_custom_order ?? false, FILTER_VALIDATE_BOOLEAN);
            $origCoId        = $record->custom_order_id ?? null;  // ← CORRECT field

            $itemInProd  = filter_var($record->co_item_in_production ?? true,  FILTER_VALIDATE_BOOLEAN);
            $itemArrived = filter_var($record->co_item_arrived       ?? false, FILTER_VALIDATE_BOOLEAN);

            $saleItems    = [];
            $newCoId      = null;
            $saleSubtotal = 0;
            $saleTax      = 0;

            foreach ($newItemsRaw as $item) {
                $type       = $item['selection_type'] ?? 'non_tag';
                $qty        = intval($item['qty'] ?? 1);
                $linePreTax = (isset($item['sale_price_override']) && floatval($item['sale_price_override']) > 0)
                    ? floatval($item['sale_price_override'])
                    : floatval($item['sold_price'] ?? 0) * $qty;
                $isTaxFree  = filter_var($item['is_tax_free'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $itemTax    = $isTaxFree ? 0 : $linePreTax * $taxRate;
                $saleSubtotal += $linePreTax;
                $saleTax      += $itemTax;

                if ($type === 'custom_order' && !empty($item['is_new_custom_order'])) {
                    $coData    = is_string($item['new_custom_data'] ?? null) ? json_decode($item['new_custom_data'], true) : ($item['new_custom_data'] ?? []);
                    $coPrice   = floatval($coData['quoted_price'] ?? $linePreTax);
                    $coDiscAmt = floatval($coData['discount_amount'] ?? 0);
                    $coAfter   = max(0, $coPrice - $coDiscAmt);
                    $coTaxRate = filter_var($coData['is_tax_free'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 0 : $taxRate;
                    $coGrand   = $coAfter * (1 + $coTaxRate);
                    $deposit   = floatval($coData['custom_deposit_amount'] ?? 0);

                    $co = CustomOrder::create([
                        'customer_id'      => $record->customer_id,
                        'staff_id'         => auth()->id(),
                        'order_type'       => 'custom',
                        'product_name'     => $coData['product_name'] ?? 'Custom',
                        'metal_type'       => $coData['metal_type'] ?? '14k',
                        'quoted_price'     => $coPrice,
                        'discount_percent' => floatval($coData['discount_percent'] ?? 0),
                        'discount_amount'  => $coDiscAmt,
                        'due_date'         => $coData['due_date'] ?? null,
                        'design_notes'     => $coData['design_notes'] ?? null,
                        'status'           => 'in_production',
                        'is_tax_free'      => filter_var($coData['is_tax_free'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'amount_paid'      => $deposit,
                        'balance_due'      => max(0, $coGrand - $deposit),
                        'items'            => [['product_name'=>$coData['product_name']??'Custom','metal_type'=>$coData['metal_type']??'14k','quoted_price'=>$coPrice]],
                    ]);
                    $newCoId = $co->id;
                    if ($deposit > 0) {
                        \App\Models\Payment::create(['custom_order_id'=>$co->id,'amount'=>$deposit,'method'=>strtoupper($coData['deposit_method']??'CASH'),'paid_at'=>now(),'store_id'=>$storeId]);
                    }
                    $saleItems[] = ['custom_order_id'=>$co->id,'custom_description'=>"CUSTOM Order: {$co->product_name}",'qty'=>1,'sold_price'=>$coAfter,'sale_price_override'=>$linePreTax,'discount_percent'=>floatval($coData['discount_percent']??0),'discount_amount'=>$coDiscAmt,'is_tax_free'=>filter_var($coData['is_tax_free']??false,FILTER_VALIDATE_BOOLEAN),'is_non_stock'=>false,'stock_no_display'=>'CUSTOM #'.$co->id];

                } elseif ($type === 'stock' && !empty($item['product_item_id'])) {
                    $saleItems[] = ['product_item_id'=>$item['product_item_id'],'custom_description'=>$item['custom_description']??null,'qty'=>$qty,'sold_price'=>floatval($item['sold_price']??0),'sale_price_override'=>$linePreTax,'discount_percent'=>floatval($item['discount_percent']??0),'discount_amount'=>floatval($item['discount_amount']??0),'is_tax_free'=>$isTaxFree,'is_non_stock'=>false,'stock_no_display'=>$item['stock_no_display']??null];
                    ProductItem::where('id', $item['product_item_id'])->update(['status'=>'sold']);

                } else {
                    $saleItems[] = ['custom_description'=>$item['custom_description']??'Item','qty'=>$qty,'sold_price'=>floatval($item['sold_price']??0),'sale_price_override'=>$linePreTax,'discount_percent'=>floatval($item['discount_percent']??0),'discount_amount'=>floatval($item['discount_amount']??0),'is_tax_free'=>$isTaxFree,'is_non_stock'=>true,'stock_no_display'=>'NON-TAG'];
                }
            }

            $saleGrandTotal  = round($saleSubtotal + $saleTax, 2);
            $totalCredit     = floatval($record->total_credit);
            $collectedNow    = $record->is_split_payment
                ? collect($record->split_payments ?? [])->sum(fn($p) => floatval($p['amount'] ?? 0))
                : floatval($record->amount_received ?? $record->difference_amount ?? 0);
            $salesPersonList = is_array($record->sales_person_list ?? null)
                ? implode(',', $record->sales_person_list)
                : ($record->sales_person_list ?? auth()->user()->name);

            // ── Create Sale — always balance_due = 0 ───────────────────────
            $sale = Sale::create([
                'customer_id'          => $record->customer_id,
                'store_id'             => $storeId,
                'invoice_number'       => $record->exchange_no,
                'subtotal'             => $saleSubtotal,
                'tax_amount'           => $saleTax,
                'final_total'          => $saleGrandTotal,
                'amount_paid'          => $saleGrandTotal,
                'balance_due'          => 0,
                'payment_method'       => $record->is_split_payment ? 'split' : ($record->difference_payment_method ?? 'EXCHANGE_CREDIT'),
                'is_split_payment'     => (bool)$record->is_split_payment,
                'status'               => 'completed',
                'completed_at'         => now(),
                'has_trade_in'         => 1,
                'trade_in_value'       => $totalCredit,
                'trade_in_description' => 'Exchange credit — ' . $record->exchange_no,
                'sales_person_list'    => $salesPersonList,
                'notes'                => 'Created from Exchange #' . $record->exchange_no,
            ]);

            foreach ($saleItems as $si) {
                $sale->items()->create(array_merge($si, ['store_id' => $storeId]));
            }

            // Exchange credit payment row
            if ($totalCredit > 0.009) {
                \App\Models\Payment::create(['sale_id'=>$sale->id,'amount'=>round($totalCredit,2),'method'=>'EXCHANGE_CREDIT','paid_at'=>now(),'store_id'=>$storeId]);
            }

            // Difference payments
            $difference = floatval($record->difference_amount ?? 0);
            if ($difference > 0.009) {
                if ($record->is_split_payment) {
                    foreach ($record->split_payments ?? [] as $p) {
                        if (floatval($p['amount'] ?? 0) <= 0) continue;
                        \App\Models\Payment::create(['sale_id'=>$sale->id,'amount'=>floatval($p['amount']),'method'=>strtoupper($p['method']??'CASH'),'paid_at'=>now(),'store_id'=>$storeId]);
                    }
                } else {
                    $collected = floatval($record->amount_received ?? $difference);
                    if ($collected > 0) \App\Models\Payment::create(['sale_id'=>$sale->id,'amount'=>$collected,'method'=>strtoupper($record->difference_payment_method??'CASH'),'paid_at'=>now(),'store_id'=>$storeId]);
                }
            }

            // Link newly created CO to sale
            if ($newCoId) {
                CustomOrder::where('id', $newCoId)->update(['sale_id' => $sale->id]);
                \App\Models\Payment::where('custom_order_id', $newCoId)->whereNull('sale_id')->update(['sale_id' => $sale->id]);
            }

            // ── LOCK the ORIGINAL custom order ─────────────────────────────
            if ($fromCustomOrder && $origCoId) {
                $origCo = CustomOrder::find($origCoId);
                if ($origCo) {
                    $origCo->update([
                        'status'       => 'exchanged',   // ← this is what locks it in CustomOrderResource
                        'sale_id'      => $sale->id,
                        'balance_due'  => 0,             // write off remaining balance
                        'design_notes' => ($origCo->design_notes ?? '')
                            . "\n[VOID/EXCHANGED via Exchange #{$record->exchange_no} on " . now()->format('M d, Y') . "]",
                    ]);

                    // If item arrived at store → add back to stock
                    if (!$itemInProd && $itemArrived) {
                        $barcode = 'EX-' . $record->exchange_no . '-' . strtoupper(Str::random(4));
                        $newStock = ProductItem::create([
                            'barcode'            => $barcode,
                            'custom_description' => "Returned via Exchange {$record->exchange_no}: {$origCo->product_name}",
                            'department'         => 'Custom',
                            'metal_type'         => $origCo->metal_type ?? null,
                            'status'             => 'in_stock',
                            'cost_price'         => floatval($origCo->amount_paid),
                            'retail_price'       => floatval($origCo->quoted_price),
                            'qty'                => 1,
                            'store_id'           => $storeId,
                        ]);
                        Notification::make()
                            ->title('Item Added to Stock')
                            ->body("Barcode {$barcode} — {$origCo->product_name} is now in_stock.")
                            ->success()->send();
                    }
                    // Still in production OR not arrived → just void, no stock entry
                }
            }

            // Return traded-in stock items to inventory
            foreach ($record->returned_items ?? [] as $item) {
                if (!empty($item['returning']) && !empty($item['product_item_id'])) {
                    ProductItem::where('id', $item['product_item_id'])->update(['status' => 'in_stock']);
                }
            }

            // Mark exchange as 🔄 EXCHANGED (voided)
            // Sales associate totals: completed_at = now() + sales_person_list → appears in EOD automatically
            $record->update([
                'status'       => 'exchanged',
                'completed_at' => now(),
                'new_sale_id'  => $sale->id,
            ]);
        });

        Notification::make()
            ->title('Exchange Completed 🎉')
            ->body("Exchange {$record->exchange_no} done. Original custom order VOIDED. Sale created. Balance = \$0.")
            ->success()->send();
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