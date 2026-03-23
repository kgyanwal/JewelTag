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
use Filament\Forms\Components\{TextInput, Select, Section, Grid, Placeholder, Textarea, DatePicker, Repeater, Hidden, Group};
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

                                        $rows = $record->sale->items->map(function($item) {
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
                                            TextInput::make('barcode')->label('Stock #')->readOnly(),
                                            TextInput::make('description')->label('Item Details')->columnSpan(2)->readOnly(),
                                            TextInput::make('price')->label('Unit Price')->prefix('$')->readOnly(),
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
                    if (!$record || ($record->payments->isEmpty() && (!$record->sale || $record->sale->payments->isEmpty()))) {
                        return new HtmlString("<div class='text-sm text-gray-400 italic py-4'>No payment history found.</div>");
                    }

                    // 💡 NOTE: This part is important because it merges your 
                    // OnSwim imported payments with any new payments you take!
                    $laybuyPayments = $record->payments;
                    $salePayments = $record->sale ? $record->sale->payments : collect();
                    
                    $allPayments = $laybuyPayments->concat($salePayments)->sortByDesc('created_at');

                    $rows = $allPayments->map(fn($p) => "
                        <tr class='border-b border-gray-100'>
                            <td class='py-3 text-sm font-medium'>{$p->created_at->format('M d, Y')}</td>
                            <td class='py-3 text-sm uppercase text-gray-500'>" . ($p->payment_method ?? $p->method ?? 'N/A') . "</td>
                            <td class='py-3 text-right text-sm font-bold text-green-600'>$" . number_format($p->amount, 2) . "</td>
                        </tr>
                    ")->implode('');

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
    ->formatStateUsing(function ($record, $state) {
        if (!$record) return $state;
        // This summates both imported payments and new ones
        $salePayments = $record->sale ? $record->sale->payments->sum('amount') : 0;
        $laybuyPayments = $record->payments->sum('amount');
        return $salePayments + $laybuyPayments;
    })
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
                                DatePicker::make('due_date')->label('Payment Deadline')->default(now()->addMonths(3))->required(),
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
            ->columns([
                TextColumn::make('laybuy_no')->label('ID')->searchable(),
                TextColumn::make('customer.name')->label('Customer')->weight('bold'),
                TextColumn::make('balance_due')->money('USD')->label('Balance')->color('danger')->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('due_date')->date()->label('Deadline'),
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
                                'cash' => 'CASH', 
                                'visa' => 'VISA', 
                                'mastercard' => 'MASTERCARD', 
                                'amex' => 'AMEX'
                            ])
                            ->default('cash')
                            ->required(),
                    ])
                    ->action(function (Laybuy $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $amountPaid = (float) $data['amount'];
                            
                            if ($record->sale_id) {
                                \App\Models\Payment::create([
                                    'sale_id' => $record->sale_id,
                                    'amount' => $amountPaid,
                                    'method' => $data['payment_method'],
                                    'paid_at' => now(),
                                ]);
                            }

                            $newAmountPaid = $record->amount_paid + $amountPaid;
                            $newBalance = max(0, $record->total_amount - $newAmountPaid);
                            $newStatus = $newBalance <= 0 ? 'completed' : 'in_progress';

                            $record->update([
                                'amount_paid' => $newAmountPaid,
                                'balance_due' => $newBalance,
                                'status' => $newStatus,
                            ]);

                            if ($newBalance <= 0 && $record->sale_id) {
                                \App\Models\Sale::where('id', $record->sale_id)->update([
                                    'status' => 'completed',
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
            ]);
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