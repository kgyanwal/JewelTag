<?php

namespace App\Filament\Resources;

use App\Models\Laybuy;
use App\Models\ProductItem;
use App\Models\Customer;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\{TextInput, Select, Section, Grid, Placeholder, Textarea, Repeater, Hidden, Group, Checkbox};
use App\Forms\Components\CustomDatePicker;
use App\Filament\Resources\LaybuyResource\Pages;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Actions\Action as FormAction;
use Illuminate\Support\Str;

class LaybuyResource extends Resource
{
    protected static ?string $model = Laybuy::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'Layby Plans';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(12)->schema([

                    /* ─── LEFT COLUMN (8/12) ─── */
                    Group::make()->columnSpan(8)->schema([

                        // Work Order Details (edit only)
                        Section::make('Work Order Details')
                            ->description('Physical items and job instructions from the original sale.')
                            ->icon('heroicon-o-wrench-screwdriver')
                            ->collapsible()
                            ->schema([
                                Placeholder::make('imported_items_list')
                                    ->hiddenLabel()
                                    ->content(function ($record) {
                                        if (!$record || !$record->sale || $record->sale->items->isEmpty()) {
                                            return new HtmlString("<div class='text-sm text-gray-400 italic font-medium'>No physical items found in the original sale records.</div>");
                                        }
                                        $rows = $record->sale->items->map(function ($item) {
                                            $stock   = $item->productItem->barcode ?? 'N/A';
                                            $desc    = $item->custom_description ?? 'No Description';
                                            $jobDesc = $item->job_description ?? '--';
                                            $price   = number_format($item->sold_price, 2);
                                            return "<tr class='border-b border-gray-100'>
                                                <td class='py-2 text-xs font-mono'>{$stock}</td>
                                                <td class='py-2 text-sm'><b>{$desc}</b><br><span class='text-xs text-gray-500 italic'>{$jobDesc}</span></td>
                                                <td class='py-2 text-right font-bold'>\${$price}</td>
                                            </tr>";
                                        })->implode('');
                                        return new HtmlString("
                                            <table class='w-full'>
                                                <thead><tr class='text-left border-b border-gray-200'>
                                                    <th class='pb-2 text-[10px] uppercase text-gray-400 font-black'>Stock #</th>
                                                    <th class='pb-2 text-[10px] uppercase text-gray-400 font-black'>Item & Job Instructions</th>
                                                    <th class='pb-2 text-right text-[10px] uppercase text-gray-400 font-black'>Price</th>
                                                </tr></thead>
                                                <tbody>{$rows}</tbody>
                                            </table>");
                                    }),
                            ]),

                        // Stock Reservation
                        Section::make('Stock Reservation (Items on Hold)')
                            ->description('Scan or search items to put aside for this customer.')
                            ->icon('heroicon-o-archive-box')
                            ->schema([
                                Select::make('item_search')
                                    ->label('Add Item to Plan')
                                    ->placeholder('Scan barcode or type item description...')
                                    ->options(fn() => ProductItem::where('status', 'in_stock')->limit(50)->get()->mapWithKeys(fn($i) => [
                                        $i->id => "{$i->barcode} - {$i->custom_description} (\${$i->retail_price})"
                                    ]))
                                    ->getSearchResultsUsing(function (string $search) {
                                        return ProductItem::where('status', 'in_stock')
                                            ->where(function ($q) use ($search) {
                                                $q->where('barcode', 'like', "%{$search}%")
                                                    ->orWhere('custom_description', 'like', "%{$search}%");
                                            })
                                            ->limit(50)->get()
                                            ->mapWithKeys(fn($i) => [
                                                $i->id => "{$i->barcode} — " . Str::limit($i->custom_description ?? '', 35) . " (\${$i->retail_price})"
                                            ]);
                                    })
                                    ->searchable()
                                    ->dehydrated(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if (!$state) return;
                                        $item  = ProductItem::find($state);
                                        $items = $get('layby_items') ?? [];
                                        if (collect($items)->contains('product_item_id', $state)) {
                                            Notification::make()->title('Item already added')->warning()->send();
                                            $set('item_search', null);
                                            return;
                                        }
                                        $items[] = [
                                            'product_item_id'  => $item->id,
                                            'barcode'          => $item->barcode,
                                            'description'      => $item->custom_description,
                                            'unit_price'       => $item->retail_price,
                                            'discount_percent' => 0,
                                            'discount_amount'  => 0,
                                            'sale_price'       => $item->retail_price,
                                            'is_tax_free'      => false,
                                            'qty'              => 1,
                                        ];
                                        $set('layby_items', $items);
                                        $set('item_search', null);
                                        self::calculateTotals($get, $set);
                                    }),

                                Repeater::make('layby_items')
                                    ->label('Items reserved in this agreement')
                                    ->schema([
                                        Grid::make(12)->schema([
                                            TextInput::make('barcode')
                                                ->label('Stock #')
                                                ->readOnly()
                                                ->columnSpan(2),

                                            TextInput::make('description')
                                                ->label('Description')
                                                ->readOnly()
                                                ->columnSpan(3),

                                            TextInput::make('unit_price')
                                                ->label('Unit Price')
                                                ->prefix('$')
                                                ->numeric()
                                                ->live(onBlur: true)
                                                ->columnSpan(2)
                                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                    $price     = floatval($state ?? 0);
                                                    $qty       = intval($get('qty') ?? 1);
                                                    $pct       = floatval($get('discount_percent') ?? 0);
                                                    $lineTotal = $price * $qty;
                                                    $discAmt   = $lineTotal * ($pct / 100);
                                                    $set('discount_amount', round($discAmt, 2));
                                                    $set('sale_price', round($lineTotal - $discAmt, 2));
                                                    self::calculateTotals($get, $set);
                                                }),

                                            TextInput::make('sale_price')
                                                ->label('Sale Price')
                                                ->prefix('$')
                                                ->numeric()
                                                ->live(onBlur: true)
                                                ->columnSpan(2)
                                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                    $price     = floatval($get('unit_price') ?? 0);
                                                    $qty       = intval($get('qty') ?? 1);
                                                    $lineTotal = $price * $qty;
                                                    $salePrice = min($lineTotal, max(0, floatval($state ?? 0)));
                                                    $discAmt   = $lineTotal - $salePrice;
                                                    $pct       = $lineTotal > 0 ? round(($discAmt / $lineTotal) * 100, 2) : 0;
                                                    $set('discount_amount', round($discAmt, 2));
                                                    $set('discount_percent', $pct);
                                                    self::calculateTotals($get, $set);
                                                }),

                                            TextInput::make('discount_percent')
                                                ->label('Disc %')
                                                ->numeric()
                                                ->suffix('%')
                                                ->default(0)
                                                ->live(onBlur: true)
                                                ->columnSpan(2)
                                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                    $price     = floatval($get('unit_price') ?? 0);
                                                    $qty       = intval($get('qty') ?? 1);
                                                    $lineTotal = $price * $qty;
                                                    $pct       = min(100, max(0, floatval($state ?? 0)));
                                                    $discAmt   = $lineTotal * ($pct / 100);
                                                    $set('discount_amount', round($discAmt, 2));
                                                    $set('sale_price', round($lineTotal - $discAmt, 2));
                                                    self::calculateTotals($get, $set);
                                                }),

                                            Checkbox::make('is_tax_free')
                                                ->label('No Tax')
                                                ->default(false)
                                                ->live()
                                                ->columnSpan(1)
                                                ->afterStateUpdated(fn(Get $get, Set $set) => self::calculateTotals($get, $set)),
                                        ]),
                                        Hidden::make('discount_amount')->default(0),
                                        Hidden::make('product_item_id'),
                                        Hidden::make('qty')->default(1),
                                    ])
                                    ->defaultItems(0)
                                    ->addable(false)
                                    ->deletable(true)
                                    ->live()
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::calculateTotals($get, $set)),

                                // Summary bar
                                Placeholder::make('totals_summary')
                                    ->label('')
                                    ->content(function (Get $get) {
                                        $items   = $get('layby_items') ?? [];
                                        $dbTax   = DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? 7.63;
                                        $taxRate = floatval($dbTax) / 100;

                                        $subtotalBeforeDisc = 0;
                                        $totalDiscount      = 0;
                                        $subtotalAfterDisc  = 0;
                                        $tax                = 0;

                                        foreach ($items as $item) {
                                            $unitPrice = floatval($item['unit_price'] ?? 0);
                                            $qty       = intval($item['qty'] ?? 1);
                                            $lineTotal = $unitPrice * $qty;
                                            $salePrice = floatval($item['sale_price'] ?? $lineTotal);
                                            $discAmt   = floatval($item['discount_amount'] ?? ($lineTotal - $salePrice));
                                            $isTaxFree = !empty($item['is_tax_free']);

                                            $subtotalBeforeDisc += $lineTotal;
                                            $totalDiscount      += $discAmt;
                                            $subtotalAfterDisc  += $salePrice;
                                            if (!$isTaxFree) $tax += $salePrice * $taxRate;
                                        }

                                        $grandTotal = $subtotalAfterDisc + $tax;
                                        $itemCount  = count($items);
                                        if ($itemCount === 0) return null;

                                        $discHtml = $totalDiscount > 0
                                            ? "<div><div style='font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;'>Discount</div><div style='font-size:16px;font-weight:800;color:#ef4444;'>-\$" . number_format($totalDiscount, 2) . "</div></div>"
                                            : '';

                                        return new HtmlString("
                                            <div style='background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px 18px;margin-top:8px;display:flex;gap:28px;align-items:center;flex-wrap:wrap;'>
                                                <div><div style='font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;'>Items</div><div style='font-size:20px;font-weight:900;color:#334155;'>{$itemCount}</div></div>
                                                <div><div style='font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;'>Subtotal</div><div style='font-size:16px;font-weight:800;color:#334155;'>\$" . number_format($subtotalAfterDisc, 2) . "</div></div>
                                                {$discHtml}
                                                <div><div style='font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;'>Tax (" . number_format($dbTax, 2) . "%)</div><div style='font-size:16px;font-weight:800;color:#f59e0b;'>\$" . number_format($tax, 2) . "</div></div>
                                                <div style='margin-left:auto;background:#0f172a;border-radius:8px;padding:10px 18px;text-align:right;'>
                                                    <div style='font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;'>Agreement Total</div>
                                                    <div style='font-size:24px;font-weight:900;color:#ffffff;'>\$" . number_format($grandTotal, 2) . "</div>
                                                </div>
                                            </div>
                                        ");
                                    })
                                    ->live(),
                            ]),

                        // Payment Ledger — shows BOTH laybuy_payments AND payments from sale edits
                        Section::make('Installment History & Ledger')
                            ->description('Full transparency of all payments received from any source.')
                            ->icon('heroicon-o-table-cells')
                            ->collapsible()
                            ->schema([
                                Placeholder::make('payment_history')
                                    ->label('')
                                    ->content(function ($record) {
                                        if (!$record) return new HtmlString("<div class='text-sm text-gray-400 italic py-4'>No payment history found.</div>");

                                        // 1. LaybuyPayments ledger
                                        $laybuyPayments = $record->laybuyPayments->map(fn($p) => [
                                            'date'     => \Carbon\Carbon::parse($p->created_at)->format('M d, Y'),
                                            'raw_date' => $p->created_at,
                                            'method'   => strtoupper($p->payment_method ?? 'N/A'),
                                            'amount'   => floatval($p->amount),
                                            'id'       => $p->id,
                                            'source'   => 'laybuy',
                                        ]);

                                        // 2. Payments from Sale edit that are NOT already in laybuy_payments
                                        $salePayments = collect();
                                        if ($record->sale_id) {
                                            $laybuyAmounts = $record->laybuyPayments->map(fn($p) => [
                                                'amount' => round(floatval($p->amount), 2),
                                                'method' => strtoupper($p->payment_method ?? ''),
                                                'date'   => \Carbon\Carbon::parse($p->created_at)->format('Y-m-d'),
                                            ])->toArray();

                                            $salePayments = \App\Models\Payment::where('sale_id', $record->sale_id)
                                                ->orderBy('paid_at')
                                                ->get()
                                                ->filter(function ($p) use ($laybuyAmounts) {
                                                    // Skip if already recorded in laybuy_payments
                                                    foreach ($laybuyAmounts as $lp) {
                                                        if (
                                                            round(floatval($p->amount), 2) === $lp['amount'] &&
                                                            strtoupper($p->method ?? '') === $lp['method'] &&
                                                            \Carbon\Carbon::parse($p->paid_at)->format('Y-m-d') === $lp['date']
                                                        ) {
                                                            return false;
                                                        }
                                                    }
                                                    return true;
                                                })
                                                ->map(fn($p) => [
                                                    'date'     => \Carbon\Carbon::parse($p->paid_at)->format('M d, Y'),
                                                    'raw_date' => $p->paid_at,
                                                    'method'   => strtoupper($p->method ?? 'N/A'),
                                                    'amount'   => floatval($p->amount),
                                                    'id'       => $p->id,
                                                    'source'   => 'sale',
                                                ]);
                                        }

                                        $allPayments = $laybuyPayments->concat($salePayments)
                                            ->sortBy('raw_date')->values();

                                        if ($allPayments->isEmpty()) {
                                            return new HtmlString("<div class='text-sm text-gray-400 italic py-4'>No payment history found.</div>");
                                        }

                                        $rows = $allPayments->map(function ($p) use ($record) {
                                            $date       = $p['date'];
                                            $method     = $p['method'];
                                            $source     = $p['source'];
                                            $sourceBadge = $source === 'sale'
                                                ? "<span style='background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;padding:1px 6px;border-radius:4px;font-size:9px;font-weight:700;margin-left:4px;'>via Sale</span>"
                                                : '';
                                            $printBtn = $source === 'laybuy'
                                                ? "<a href='" . route('laybuy.print', ['laybuy' => $record->id, 'payment_id' => $p['id'], 'source' => 'laybuy']) . "' target='_blank' style='background:#0ea5e9;color:#fff;padding:5px 12px;border-radius:6px;font-size:11px;font-weight:bold;text-decoration:none;'>Receipt</a>"
                                                : "<span style='font-size:11px;color:#94a3b8;'>—</span>";

                                            return "<tr class='border-b border-gray-100 hover:bg-gray-50'>
                                                <td class='py-3 text-sm font-medium'>{$date}</td>
                                                <td class='py-3 text-sm uppercase'>
                                                    <span class='bg-gray-100 border border-gray-200 text-gray-700 px-2 py-1 rounded text-[10px] font-bold'>{$method}</span>
                                                    {$sourceBadge}
                                                </td>
                                                <td class='py-3 text-right text-sm font-black text-green-600'>$" . number_format($p['amount'], 2) . "</td>
                                                <td class='py-3 text-right'>{$printBtn}</td>
                                            </tr>";
                                        })->implode('');

                                        $totalPaid = $allPayments->sum('amount');
                                        $totalHtml = "<tr class='border-t-2 border-gray-300 bg-gray-50'>
                                            <td colspan='2' class='py-3 text-sm font-black text-gray-700 uppercase'>Total Paid</td>
                                            <td class='py-3 text-right text-sm font-black text-green-700'>$" . number_format($totalPaid, 2) . "</td>
                                            <td></td>
                                        </tr>";

                                        return new HtmlString("
                                            <table class='w-full text-left'>
                                                <thead><tr class='border-b border-gray-200'>
                                                    <th class='pb-2 text-[10px] uppercase text-gray-400 font-black'>Date</th>
                                                    <th class='pb-2 text-[10px] uppercase text-gray-400 font-black'>Method</th>
                                                    <th class='pb-2 text-right text-[10px] uppercase text-gray-400 font-black'>Amount</th>
                                                    <th class='pb-2 text-right text-[10px] uppercase text-gray-400 font-black'>Receipt</th>
                                                </tr></thead>
                                                <tbody>{$rows}{$totalHtml}</tbody>
                                            </table>");
                                    }),
                            ]),
                    ]),

                    /* ─── RIGHT COLUMN (4/12) ─── */
                    Group::make()->columnSpan(4)->schema([

                        Section::make('Customer Context')
                            ->schema([
                                Select::make('customer_id')
                                    ->label('Assign Customer')
                                    ->relationship('customer', 'name')
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->name} {$record->last_name} | {$record->phone}")
                                    ->searchable(['name', 'last_name', 'phone', 'customer_no'])
                                    ->preload()->required()->live()
                                    ->disabledOn('edit')
                                    ->hintAction(
                                        FormAction::make('profile')
                                            ->label('Details')
                                            ->icon('heroicon-o-user-circle')
                                            ->visible(fn(Get $get) => $get('customer_id'))
                                            ->slideOver()
                                            ->form(fn(Get $get) => [
                                                Placeholder::make('info')->content(fn() => "Customer ID: " . $get('customer_id'))
                                            ])
                                    ),
                            ]),

                        // Staff — create shows multi-select, edit shows read-only
                        Section::make('Sales Staff')
                            ->schema([
                                Select::make('sales_person_list')
                                    ->label('Staff on Sale')
                                    ->multiple()
                                    ->options(fn() => User::orderBy('name')->pluck('name', 'name')->toArray())
                                    ->default(fn() => [\Illuminate\Support\Facades\Session::get('active_staff_name') ?? auth()->user()->name])
                                    ->required()
                                    ->visible(fn(string $operation) => $operation === 'create'),

                                TextInput::make('sales_person')
                                    ->label('Sales Person')
                                    ->readOnly()
                                    ->visible(fn(string $operation) => $operation === 'edit'),
                            ]),

                        Section::make('Agreement Status')
                            ->schema([
                                Select::make('status')
                                    ->options(['in_progress' => 'Active Plan', 'completed' => 'Fully Paid', 'cancelled' => 'Cancelled'])
                                    ->required()->native(false),

                                TextInput::make('total_amount')
                                    ->label('Agreement Total')
                                    ->prefix('$')->readOnly()
                                    ->extraInputAttributes(['class' => 'font-bold text-xl text-gray-900']),

                                TextInput::make('amount_paid')
                                    ->label('Initial Deposit Collected')
                                    ->prefix('$')
                                    ->numeric()
                                    ->readOnly(fn(string $operation) => $operation === 'edit')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::calculateTotals($get, $set))
                                    ->extraInputAttributes(['class' => 'text-green-600 font-bold']),

                                Select::make('initial_payment_method')
                                    ->label('Deposit Payment Method')
                                    ->options(function () {
                                        $json    = DB::table('site_settings')->where('key', 'payment_methods')->value('value');
                                        $methods = $json ? json_decode($json, true) : ['CASH', 'VISA', 'MASTERCARD', 'AMEX'];
                                        $options = [];
                                        foreach ($methods as $method) {
                                            if (strtoupper($method) !== 'LAYBUY') {
                                                $options[strtoupper($method)] = strtoupper($method);
                                            }
                                        }
                                        return $options;
                                    })
                                    ->default('CASH')
                                    ->visible(fn(Get $get, string $operation) => $operation === 'create' && floatval($get('amount_paid')) > 0)
                                    ->required(fn(Get $get, string $operation) => $operation === 'create' && floatval($get('amount_paid')) > 0),

                                TextInput::make('balance_due')
                                    ->label('Remaining Balance')
                                    ->prefix('$')->readOnly()
                                    ->extraInputAttributes(['class' => 'text-red-600 font-black text-2xl bg-red-50']),

                                Placeholder::make('progress_bar')
                                    ->label('Payment Progress')
                                    ->content(function (Get $get) {
                                        $total = (float) $get('total_amount');
                                        $paid  = (float) $get('amount_paid');
                                        $perc  = $total > 0 ? min(100, round(($paid / $total) * 100)) : 0;
                                        return new HtmlString("
                                            <div class='w-full bg-gray-200 rounded-full h-2.5 mt-2'>
                                                <div class='bg-primary-600 h-2.5 rounded-full transition-all duration-500' style='width:{$perc}%'></div>
                                            </div>
                                            <div class='flex justify-between text-[10px] font-bold text-gray-500 mt-1 uppercase'>
                                                <span>Progress</span><span>{$perc}%</span>
                                            </div>
                                        ");
                                    }),
                            ]),

                        Section::make('Timeline')
                            ->schema([
                                CustomDatePicker::make('due_date')->label('Payment Deadline')->default(now()->addMonths(3))->required(),
                                Textarea::make('notes')->rows(3)->placeholder('Internal notes...'),
                            ]),
                    ]),
                ]),
                Hidden::make('laybuy_no')->default(fn() => 'LB-' . date('Ymd-His')),
            ]);
    }

    public static function calculateTotals(Get $get, Set $set): void
    {
        $items   = $get('layby_items') ?? [];
        $dbTax   = DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? 7.63;
        $taxRate = floatval($dbTax) / 100;

        $subtotal = 0;
        $tax      = 0;

        foreach ($items as $item) {
            $salePrice = floatval($item['sale_price'] ?? $item['unit_price'] ?? 0);
            $subtotal += $salePrice;
            if (empty($item['is_tax_free'])) {
                $tax += $salePrice * $taxRate;
            }
        }

        $total = round($subtotal + $tax, 2);
        $paid  = floatval($get('amount_paid') ?? 0);

        $set('total_amount', number_format($total, 2, '.', ''));
        $set('balance_due',  number_format(max(0, $total - $paid), 2, '.', ''));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn($query) => $query->with(['customer', 'sale.payments', 'laybuyPayments']))
            ->columns([
                TextColumn::make('laybuy_no')
                    ->label('ID')->searchable()->sortable()->weight('bold')->fontFamily('mono')->grow(false),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->weight('bold')
                    ->formatStateUsing(fn($record) => trim(($record->customer?->name ?? '') . ' ' . ($record->customer?->last_name ?? '')))
                    ->description(fn($record) => $record->customer?->phone)
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('customer', fn($q) =>
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhereRaw("CONCAT(name, ' ', last_name) LIKE ?", ["%{$search}%"])
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('customer_no', 'like', "%{$search}%")
                        );
                    }),

                TextColumn::make('progress')
                    ->label('Progress')
                    ->getStateUsing(function ($record) {
                        $total = floatval($record->total_amount);
                        if ($total <= 0) return new HtmlString('<span class="text-gray-400">—</span>');
                        $paid  = floatval($record->amount_paid);
                        $perc  = min(100, round(($paid / $total) * 100));
                        $color = $perc >= 100 ? '#16a34a' : ($perc >= 50 ? '#0284c7' : '#dc2626');
                        return new HtmlString("
                            <div style='min-width:140px;'>
                                <div style='display:flex;justify-content:space-between;font-size:11px;font-weight:700;margin-bottom:3px;'>
                                    <span style='color:#6b7280;'>\$" . number_format($paid, 2) . " / \$" . number_format($total, 2) . "</span>
                                    <span style='color:{$color};font-weight:900;'>{$perc}%</span>
                                </div>
                                <div style='background:#e5e7eb;border-radius:999px;height:7px;overflow:hidden;'>
                                    <div style='background:{$color};width:{$perc}%;height:100%;border-radius:999px;transition:width 0.3s;'></div>
                                </div>
                            </div>
                        ");
                    }),

                TextColumn::make('balance_due')->money('USD')->label('Balance')->color('danger')->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn($state) => match ($state) { 'completed' => 'success', 'cancelled' => 'danger', default => 'warning' })
                    ->formatStateUsing(fn($state) => match ($state) { 'in_progress' => 'Active', 'completed' => 'Paid', 'cancelled' => 'Cancelled', default => $state }),

                TextColumn::make('due_date')
                    ->date('M d, Y')->label('Deadline')->sortable()
                    ->color(fn($record) => $record->due_date && $record->due_date < now() && $record->status === 'in_progress' ? 'danger' : null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['in_progress' => 'Active Plan', 'completed' => 'Fully Paid', 'cancelled' => 'Cancelled']),
            ])
            ->actions([
                Tables\Actions\Action::make('quick_pay')
                    ->label('Add Payment')->icon('heroicon-o-banknotes')->color('success')
                    ->visible(fn(Laybuy $record) => $record->balance_due > 0.01)
                    ->form([
                        TextInput::make('amount')->numeric()->required()->prefix('$')->default(fn(Laybuy $record) => $record->balance_due),
                        Select::make('payment_method')
                            ->options(function () {
                                $json    = DB::table('site_settings')->where('key', 'payment_methods')->value('value');
                                $methods = $json ? json_decode($json, true) : ['CASH', 'VISA', 'MASTERCARD', 'AMEX'];
                                $options = [];
                                foreach ($methods as $method) {
                                    $clean = strtoupper(trim($method));
                                    if ($clean !== 'LAYBUY') $options[$clean] = $clean;
                                }
                                return $options;
                            })
                            ->default('CASH')->required(),
                    ])
                    ->action(function (Laybuy $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $amountPaid = (float) $data['amount'];
                            $method     = strtoupper(trim($data['payment_method']));
                            $paidAt     = now();
                            $storeId    = auth()->user()->store_id ?? $record->sale?->store_id ?? 1;

                            \App\Models\LaybuyPayment::create(['laybuy_id' => $record->id, 'amount' => $amountPaid, 'payment_method' => $method, 'created_at' => $paidAt, 'updated_at' => $paidAt]);

                            if ($record->sale_id) {
                                \App\Models\Payment::create(['sale_id' => $record->sale_id, 'amount' => $amountPaid, 'method' => $method, 'paid_at' => $paidAt, 'store_id' => $storeId]);
                            }

                            $newPaid = (float) $record->amount_paid + $amountPaid;
                            $newBal  = max(0, (float) $record->total_amount - $newPaid);
                            $record->update(['amount_paid' => $newPaid, 'balance_due' => $newBal, 'status' => $newBal <= 0.01 ? 'completed' : 'in_progress', 'last_paid_date' => $paidAt]);

                            if ($record->sale_id) {
                                $sale          = $record->sale;
                                $totalSalePaid = \App\Models\Payment::where('sale_id', $sale->id)->sum('amount');
                                $newSaleBal    = max(0, (float) $sale->final_total - $totalSalePaid);
                                $isFullyPaid   = $newSaleBal <= 0.01;
                                $sale->update(['amount_paid' => $totalSalePaid, 'balance_due' => $newSaleBal, 'status' => $isFullyPaid ? 'completed' : $sale->status, 'completed_at' => $isFullyPaid ? $paidAt : $sale->completed_at]);
                                if ($isFullyPaid) {
                                    foreach ($sale->items as $saleItem) {
                                        if ($saleItem->product_item_id) {
                                            ProductItem::where('id', $saleItem->product_item_id)->where('status', 'on_hold')->update(['status' => 'sold', 'hold_reason' => null, 'held_by_sale_id' => null]);
                                        }
                                    }
                                }
                            }
                        });
                        Notification::make()->title('Payment Recorded')->body('$' . number_format($data['amount'], 2) . ' applied to plan.')->success()->send();
                    }),

                Tables\Actions\Action::make('cancel_laybuy')
                    ->label('Cancel Plan')->icon('heroicon-o-x-circle')->color('danger')
                    ->requiresConfirmation()->modalHeading('Cancel Laybuy Plan?')
                    ->modalDescription('This will release all reserved items back to in-stock and mark the plan as cancelled.')
                    ->visible(fn(Laybuy $record) => $record->status === 'in_progress')
                    ->action(function (Laybuy $record) {
                        DB::transaction(function () use ($record) {
                            if ($record->sale_id) {
                                $sale = \App\Models\Sale::with('items')->find($record->sale_id);
                                if ($sale) {
                                    foreach ($sale->items as $saleItem) {
                                        if ($saleItem->product_item_id) {
                                            ProductItem::where('id', $saleItem->product_item_id)->where('status', 'on_hold')->update(['status' => 'in_stock', 'hold_reason' => null, 'held_by_sale_id' => null]);
                                        }
                                    }
                                    $sale->update(['status' => 'cancelled']);
                                }
                            }
                            $record->update(['status' => 'cancelled']);
                        });
                        Notification::make()->title('Laybuy Cancelled')->body('All reserved items released back to stock.')->warning()->send();
                    }),

                Tables\Actions\EditAction::make()->tooltip('Manage ledger')->icon('heroicon-m-pencil-square'),

                Tables\Actions\DeleteAction::make()
                    ->before(function (Laybuy $record) {
                        if ($record->sale_id) {
                            $sale = \App\Models\Sale::with('items')->find($record->sale_id);
                            if ($sale) {
                                foreach ($sale->items as $saleItem) {
                                    if ($saleItem->product_item_id) {
                                        ProductItem::where('id', $saleItem->product_item_id)->where('status', 'on_hold')->update(['status' => 'in_stock', 'hold_reason' => null, 'held_by_sale_id' => null]);
                                    }
                                }
                            }
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLaybuys::route('/'),
            'create' => Pages\CreateLaybuy::route('/create'),
            'edit'   => Pages\EditLaybuy::route('/{record}/edit'),
        ];
    }
}