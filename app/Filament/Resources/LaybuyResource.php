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
use Filament\Forms\Components\{TextInput, Select, Section, Grid, Placeholder, Textarea, Repeater, Hidden, Group};
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

                    /* ─── LEFT COLUMN: INVENTORY & LEDGER (8/12) ─── */
                    Group::make()->columnSpan(8)->schema([

                        // ─── ADDED: WORK ORDER DETAILS (FOR IMPORTED ON-SWIM DATA) ───
                        Section::make('Work Order Details')
                            ->description('Physical items and job instructions (Engravings, Sizing, etc.) from the original job.')
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
                                            $stock = $item->productItem->barcode ?? 'N/A';
                                            $desc = $item->custom_description ?? 'No Description';
                                            $jobDesc = $item->job_description ?? '--';
                                            $price = number_format($item->sold_price, 2);

                                            return "
                                                <tr class='border-b border-gray-100'>
                                                    <td class='py-2 text-xs font-mono'>{$stock}</td>
                                                    <td class='py-2 text-sm'><b>{$desc}</b><br><span class='text-xs text-gray-500 italic'>{$jobDesc}</span></td>
                                                    <td class='py-2 text-right font-bold'>\${$price}</td>
                                                </tr>";
                                        })->implode('');

                                        return new HtmlString("
                                            <table class='w-full'>
                                                <thead>
                                                    <tr class='text-left border-b border-gray-200'>
                                                        <th class='pb-2 text-[10px] uppercase text-gray-400 font-black'>Stock #</th>
                                                        <th class='pb-2 text-[10px] uppercase text-gray-400 font-black'>Item & Job Instructions</th>
                                                        <th class='pb-2 text-right text-[10px] uppercase text-gray-400 font-black'>Price</th>
                                                    </tr>
                                                </thead>
                                                <tbody>{$rows}</tbody>
                                            </table>");
                                    })
                            ]),

                        // 1. DYNAMIC STOCK SELECTION (YOUR ORIGINAL CODE)
                        Section::make('Stock Reservation (Items on Hold)')
                            ->description('Scan or search items to put aside for this customer.')
                            ->icon('heroicon-o-archive-box')
                            ->schema([
                                Select::make('item_search')
                                    ->label('Add Item to Plan')
                                    ->placeholder('Scan barcode or type item description...')
                                    ->options(fn() => ProductItem::where('status', 'in_stock')->get()->mapWithKeys(fn($i) => [
                                        $i->id => "{$i->barcode} - {$i->custom_description} (\${$i->retail_price})"
                                    ]))
                                    ->getSearchResultsUsing(function (string $search) {
                                        return \App\Models\ProductItem::query()
                                            ->where('status', 'on_hold')  // ✅ only reserved/held items
                                            ->where(function ($q) use ($search) {
                                                $q->where('barcode', 'like', "%{$search}%")
                                                    ->orWhere('custom_description', 'like', "%{$search}%");
                                            })
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(fn($i) => [
                                                $i->id => "{$i->barcode} — " . \Illuminate\Support\Str::limit($i->custom_description ?? '', 35)
                                                    . " (\${$i->retail_price})"
                                            ]);
                                    })
                                    ->searchable()
                                    ->dehydrated(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if (!$state) return;

                                        $item = ProductItem::find($state);
                                        $items = $get('layby_items') ?? [];

                                        if (collect($items)->contains('product_item_id', $state)) {
                                            Notification::make()->title('Item already added')->warning()->send();
                                            $set('item_search', null);
                                            return;
                                        }

                                        $items[] = [
                                            'product_item_id' => $item->id,
                                            'barcode' => $item->barcode,
                                            'description' => $item->custom_description,
                                            'price' => $item->retail_price,
                                        ];

                                        $set('layby_items', $items);
                                        $set('item_search', null);
                                        self::calculateTotals($get, $set);
                                    }),

                                Repeater::make('layby_items')
                                    ->label('Items reserved in this agreement')
                                    ->schema([
                                        Grid::make(4)->schema([
                                            TextInput::make('barcode')
                                                ->label('Stock #')
                                                ->readOnly(),

                                            TextInput::make('description')
                                                ->label('Item Details')
                                                ->columnSpan(2)
                                                ->readOnly(),

                                            // 🚀 THE FIX: Removed readOnly() and added live(onBlur) to recalculate
                                            TextInput::make('price')
                                                ->label('Unit Price')
                                                ->prefix('$')
                                                ->numeric()
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(fn(Get $get, Set $set) => self::calculateTotals($get, $set)),
                                        ]),
                                        Hidden::make('product_item_id'),
                                    ])
                                    ->defaultItems(0)
                                    ->addable(false)
                                    ->deletable(true)
                                    ->live()
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::calculateTotals($get, $set)),
                            ]),

                        // 2. PAYMENT LEDGER
                        Section::make('Installment History & Ledger')
                            ->description('Full transparency of all payments received.')
                            ->icon('heroicon-o-table-cells')
                            ->collapsible()
                            ->schema([
                             Placeholder::make('payment_history')
    ->label('')
    ->content(function ($record) {
        if (!$record) return new HtmlString("<div class='text-sm text-gray-400 italic py-4'>No payment history found.</div>");

        // 🚀 THE FIX: We pull directly from the 'payments' relationship.
        // This bypasses the Sale ID issues and shows the permanent ledger history.
        $allPayments = $record->laybuyPayments;

        if ($allPayments->isEmpty()) {
            return new HtmlString("<div class='text-sm text-gray-400 italic py-4'>No payment history found.</div>");
        }

        $rows = $allPayments->map(function ($p) use ($record) {
            $date = \Carbon\Carbon::parse($p->created_at)->format('M d, Y');            
            $method = $p->payment_method ?? 'N/A';
            $printUrl = route('laybuy.print', ['laybuy' => $record->id, 'payment_id' => $p->id, 'source' => 'laybuy']);

            return "
                <tr class='border-b border-gray-100 hover:bg-gray-50'>
                    <td class='py-3 text-sm font-medium'>{$date}</td>
                    <td class='py-3 text-sm uppercase'>
                        <span class='bg-gray-100 border border-gray-200 text-gray-700 px-2 py-1 rounded text-[10px] font-bold'>{$method}</span>
                    </td>
                    <td class='py-3 text-right text-sm font-black text-green-600'>$" . number_format($p->amount, 2) . "</td>
                    <td class='py-3 text-right'>
                        <a href='{$printUrl}' target='_blank' style='background-color: #0ea5e9; color: #ffffff; padding: 5px 12px; border-radius: 6px; font-size: 11px; font-weight: bold; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;'>
                            Receipt
                        </a>
                    </td>
                </tr>";
        })->implode('');

        return new HtmlString("
            <table class='w-full text-left'>
                <thead>
                    <tr class='border-b border-gray-200'>
                        <th class='pb-2 text-[10px] uppercase text-gray-400 font-black'>Date</th>
                        <th class='pb-2 text-[10px] uppercase text-gray-400 font-black'>Method</th>
                        <th class='pb-2 text-right text-[10px] uppercase text-gray-400 font-black'>Amount</th>
                        <th class='pb-2 text-right text-[10px] uppercase text-gray-400 font-black'>Action</th>
                    </tr>
                </thead>
                <tbody>{$rows}</tbody>
            </table>");
    })
                                   
                            ]),
                    ]),

                    /* ─── RIGHT COLUMN: CUSTOMER & STATUS (YOUR ORIGINAL CODE) ─── */
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
                                                Placeholder::make('info')->content(fn() => "Loading Customer ID: " . $get('customer_id'))
                                            ])
                                    ),
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

                                // 🚀 THE FIX: Dynamically show payment options if they enter a deposit
                                Select::make('initial_payment_method')
                                    ->label('Deposit Payment Method')
                                    ->options(function () {
                                        $json = DB::table('site_settings')->where('key', 'payment_methods')->value('value');
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
                                        $total = (float)$get('total_amount');
                                        $paid = (float)$get('amount_paid');
                                        $perc = $total > 0 ? min(100, round(($paid / $total) * 100)) : 0;
                                        return new HtmlString("
                                            <div class='w-full bg-gray-200 rounded-full h-2.5 mt-2'>
                                                <div class='bg-primary-600 h-2.5 rounded-full transition-all duration-500' style='width: {$perc}%'></div>
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
                                TextInput::make('sales_person')->default(fn() => auth()->user()->name)->readOnly(),
                                Textarea::make('notes')->rows(3)->placeholder('Internal notes...'),
                            ]),
                    ]),
                ]),
                Hidden::make('laybuy_no')->default(fn() => 'LB-' . date('Ymd-His')),
            ]);
    }

    public static function calculateTotals(Get $get, Set $set)
    {
        $items = $get('layby_items') ?? [];
        $total = collect($items)->sum(fn($i) => (float)($i['price'] ?? 0));

        // If we are editing, we should fetch the existing paid amount
        $paid = (float)($get('amount_paid') ?? 0);

        $set('total_amount', $total);
        $set('balance_due', max(0, $total - $paid));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn($query) => $query->with(['customer', 'sale.payments', 'laybuyPayments']))
            ->columns([
                TextColumn::make('laybuy_no')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->fontFamily('mono')
                    ->grow(false),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->weight('bold')
                    ->formatStateUsing(
                        fn($record) =>
                        trim(($record->customer?->name ?? '') . ' ' . ($record->customer?->last_name ?? ''))
                    )
                    ->description(fn($record) => $record->customer?->phone)
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas(
                            'customer',
                            fn($q) =>
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

                        // 🚀 FIX: Just use the database column directly instead of summing both tables
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

                TextColumn::make('balance_due')
                    ->money('USD')
                    ->label('Balance')
                    ->color('danger')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'completed'   => 'success',
                        'cancelled'   => 'danger',
                        default       => 'warning',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'in_progress' => 'Active',
                        'completed'   => 'Paid',
                        'cancelled'   => 'Cancelled',
                        default       => $state,
                    }),

                TextColumn::make('due_date')
                    ->date('M d, Y')
                    ->label('Deadline')
                    ->sortable()
                    ->color(
                        fn($record) =>
                        $record->due_date && $record->due_date < now() && $record->status === 'in_progress'
                            ? 'danger'
                            : null
                    ),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'in_progress' => 'Active Plan',
                        'completed'   => 'Fully Paid',
                        'cancelled'   => 'Cancelled',
                    ]),
            ])
            ->actions([
             Tables\Actions\Action::make('quick_pay')
    ->label('Add Payment')
    ->icon('heroicon-o-banknotes')
    ->color('success')
    ->visible(fn(Laybuy $record) => $record->balance_due > 0.01)
    ->form([
        TextInput::make('amount')
            ->numeric()
            ->required()
            ->prefix('$')
            ->default(fn(Laybuy $record) => $record->balance_due),
        Select::make('payment_method')
            ->options(function () {
                $json    = DB::table('site_settings')->where('key', 'payment_methods')->value('value');
                $methods = $json ? json_decode($json, true) : ['CASH', 'VISA', 'MASTERCARD', 'AMEX'];
                $options = [];
                foreach ($methods as $method) {
                    $clean = strtoupper(trim($method));
                    if ($clean !== 'LAYBUY') {
                        $options[$clean] = $clean;
                    }
                }
                return $options;
            })
            ->default('CASH')
            ->required(),
    ])
    ->action(function (Laybuy $record, array $data) {
        DB::transaction(function () use ($record, $data) {
            $amountPaid = (float) $data['amount'];
            $method     = strtoupper(trim($data['payment_method']));
            $paidAt     = now(); 
            $storeId    = auth()->user()->store_id ?? $record->sale?->store_id ?? 1;

            // 1. Record in the specific Laybuy Ledger (for the timeline tab)
            \App\Models\LaybuyPayment::create([
                'laybuy_id'      => $record->id,
                'amount'         => $amountPaid,
                'payment_method' => $method,
                'created_at'     => $paidAt,
                'updated_at'     => $paidAt,
            ]);

            // 2. Record in the Main Sales Ledger (for EOD reports)
            if ($record->sale_id) {
                \App\Models\Payment::create([
                    'sale_id'  => $record->sale_id,
                    'amount'   => $amountPaid,
                    'method'   => $method,
                    'paid_at'  => $paidAt,
                    'store_id' => $storeId,
                ]);
            }

            // 3. Update Laybuy running totals
            $newLaybuyPaid = (float)$record->amount_paid + $amountPaid;
            $newLaybuyBal  = max(0, (float)$record->total_amount - $newLaybuyPaid);
            
            $record->update([
                'amount_paid'    => $newLaybuyPaid,
                'balance_due'    => $newLaybuyBal,
                'status'         => $newLaybuyBal <= 0.01 ? 'completed' : 'in_progress',
                'last_paid_date' => $paidAt,
            ]);

            // 4. Sync the master Sale Record
            if ($record->sale_id) {
                $sale = $record->sale;
                // Re-sum all payments to ensure perfect accuracy
                $totalSalePaid = \App\Models\Payment::where('sale_id', $sale->id)->sum('amount');
                $newSaleBal    = max(0, (float)$sale->final_total - $totalSalePaid);
                $isFullyPaid   = $newSaleBal <= 0.01;

                $sale->update([
                    'amount_paid'  => $totalSalePaid,
                    'balance_due'  => $newSaleBal,
                    'status'       => $isFullyPaid ? 'completed' : $sale->status,
                    'completed_at' => $isFullyPaid ? $paidAt : $sale->completed_at,
                ]);

                // 5. If fully paid, release items from ON_HOLD to SOLD
                if ($isFullyPaid) {
                    foreach ($sale->items as $saleItem) {
                        if ($saleItem->product_item_id) {
                            \App\Models\ProductItem::where('id', $saleItem->product_item_id)
                                ->where('status', 'on_hold')
                                ->update([
                                    'status'          => 'sold',
                                    'hold_reason'     => null,
                                    'held_by_sale_id' => null,
                                ]);
                        }
                    }
                }
            }
        });

        Notification::make()
            ->title('Payment Recorded Successfully')
            ->body('$' . number_format($data['amount'], 2) . ' applied to plan.')
            ->success()
            ->send();
    }),

                Tables\Actions\EditAction::make()
    // tooltips show when you hover over the button
    ->tooltip("Click manage ledger to edit today's amount")
    // If you want a specific icon for the edit button
    ->icon('heroicon-m-pencil-square'),
                        ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaybuys::route('/'),
            'create' => Pages\CreateLaybuy::route('/create'),
            'edit' => Pages\EditLaybuy::route('/{record}/edit'),
        ];
    }
}
