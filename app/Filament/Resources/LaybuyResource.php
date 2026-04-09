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

                        // 2. PAYMENT LEDGER (YOUR ORIGINAL CODE)
                        Section::make('Installment History & Ledger')
                            ->description('Full transparency of all payments received.')
                            ->icon('heroicon-o-table-cells')
                            ->collapsible()
                            ->schema([
                                Placeholder::make('payment_history')
    ->label('')
    ->content(function ($record) {
        // 1. Initial check: if no record exists yet, show empty
        if (!$record) return new HtmlString("<div class='text-sm text-gray-400 italic py-4'>No payment history found.</div>");

        // 2. GET RECENT PAYMENTS (from new laybuy system)
        $laybuyPayments = $record->payments;

        // 3. GET FINALIZED PAYMENTS (from the standard payments table)
        $finalPayments = $record->sale ? $record->sale->payments : collect();

        // 4. GET HISTORICAL PAYMENTS (Filtered for Laybuy only)
        $historicalPayments = \App\Models\SalePayment::where('sale_id', $record->sale_id)
            ->where('is_layby', 1) // 👈 This ensures we don't pull direct sale payments
            ->get();
            // 5. MERGE ALL SOURCES
            // We use concat() to combine them into one list
        $allPayments = collect()
            ->concat($laybuyPayments)
            ->concat($finalPayments)
            ->concat($historicalPayments)
            ->sortByDesc(function ($p) {
                // Sort by created_at or payment_date
                return $p->created_at ?? $p->payment_date;
            });

        if ($allPayments->isEmpty()) {
            return new HtmlString("<div class='text-sm text-gray-400 italic py-4'>No payment history found.</div>");
        }

        $rows = $allPayments->map(function ($p) {
            // Normalize dates (historical use payment_date, others use created_at)
        $date = $p->payment_date ? date('M d, Y', strtotime($p->payment_date)) : ($p->created_at ? $p->created_at->format('M d, Y') : 'N/A');            
            // Normalize method names (sale_payments uses 'payment_method', others use 'method')
            $method = $p->payment_method ?? $p->method ?? 'N/A';
            
            return "
                <tr class='border-b border-gray-100'>
                    <td class='py-3 text-sm font-medium'>{$date}</td>
                    <td class='py-3 text-sm uppercase text-gray-500'>{$method}</td>
                    <td class='py-3 text-right text-sm font-bold text-green-600'>$" . number_format($p->amount, 2) . "</td>
                </tr>";
        })->implode('');

        return new HtmlString("
            <table class='w-full'>
                <thead>
                    <tr class='text-left border-b border-gray-200'>
                        <th class='pb-2 text-[10px] uppercase tracking-widest text-gray-400 font-black'>Date</th>
                        <th class='pb-2 text-[10px] uppercase tracking-widest text-gray-400 font-black'>Method</th>
                        <th class='pb-2 text-right text-[10px] uppercase tracking-widest text-gray-400 font-black'>Amount</th>
                    </tr>
                </thead>
                <tbody>{$rows}</tbody>
            </table>
        ");
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
                                    ->label('Amount Collected')
                                    ->prefix('$')
                                    ->readOnly() // Keep it read-only to prevent math errors
                                    ->extraInputAttributes(['class' => 'text-green-600 font-bold']),

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
        ->modifyQueryUsing(fn($query) => $query->with(['customer', 'sale.payments', 'payments']))
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
                ->formatStateUsing(fn($record) =>
                    trim(($record->customer?->name ?? '') . ' ' . ($record->customer?->last_name ?? ''))
                )
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
                ->color(fn($state) => match($state) {
                    'completed'   => 'success',
                    'cancelled'   => 'danger',
                    default       => 'warning',
                })
                ->formatStateUsing(fn($state) => match($state) {
                    'in_progress' => 'Active',
                    'completed'   => 'Paid',
                    'cancelled'   => 'Cancelled',
                    default       => $state,
                }),

            TextColumn::make('due_date')
                ->date('M d, Y')
                ->label('Deadline')
                ->sortable()
                ->color(fn($record) =>
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
                ->visible(fn (Laybuy $record) => $record->balance_due > 0)
                ->form([
                    TextInput::make('amount')
                        ->numeric()
                        ->required()
                        ->prefix('$')
                        ->default(fn(Laybuy $record) => $record->balance_due),
                    Select::make('payment_method')
                        ->options([
                            'cash'       => 'CASH',
                            'visa'       => 'VISA',
                            'mastercard' => 'MASTERCARD',
                            'amex'       => 'AMEX',
                        ])
                        ->default('cash')
                        ->required(),
                ])
                ->action(function (Laybuy $record, array $data) {
                    DB::transaction(function () use ($record, $data) {
                        $amountPaid = (float) $data['amount'];

                        // 1. Record for Laybuy History Ledger
                        \App\Models\LaybuyPayment::create([
                            'laybuy_id'      => $record->id,
                            'amount'         => $amountPaid,
                            'payment_method' => $data['payment_method'],
                        ]);

                        // 2. Record for Main Sales Ledger (End of day reporting)
                        if ($record->sale_id) {
                            \App\Models\Payment::create([
                                'sale_id' => $record->sale_id,
                                'amount'  => $amountPaid,
                                'method'  => $data['payment_method'],
                                'paid_at' => now(),
                            ]);
                        }

                        // 3. Update the Laybuy balances
                        $newAmountPaid = $record->amount_paid + $amountPaid;
                        $newBalance    = max(0, $record->total_amount - $newAmountPaid);
                        $newStatus     = $newBalance <= 0 ? 'completed' : 'in_progress';

                        $record->update([
                            'amount_paid' => $newAmountPaid,
                            'balance_due' => $newBalance,
                            'status'      => $newStatus,
                        ]);

                        // 4. Mark sale as completed if fully paid off
                        if ($newBalance <= 0 && $record->sale_id) {
                            \App\Models\Sale::where('id', $record->sale_id)->update([
                                'status'       => 'completed',
                                'completed_at' => now(),
                            ]);
                        }
                    });

                    Notification::make()
                        ->title('Payment Recorded Successfully')
                        ->success()
                        ->send();
                }),

            Tables\Actions\EditAction::make(),
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
