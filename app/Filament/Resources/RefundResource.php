<?php

namespace App\Filament\Resources;

use App\Models\Refund;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Restock;
use App\Models\VendorReturn;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class RefundResource extends Resource
{
    protected static ?string $model = Refund::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup = 'Sales';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Partial Refund Selection')
                ->schema([
                    Forms\Components\TextInput::make('refund_no')
                        ->label('Refund Receipt #')
                        ->default(fn() => 'RFD-' . strtoupper(bin2hex(random_bytes(4))))
                        ->readOnly()
                        ->dehydrated()
                        ->required(),

                    Forms\Components\Select::make('sale_id')
                        ->label('Original Sale / Invoice')
                        ->relationship(
                            'sale',
                            'invoice_number',
                            fn(Builder $query) => $query->where('status', 'completed')
                        )
                        ->default(fn() => request('sale_id') ? (int) request('sale_id') : null)
                        ->afterStateHydrated(function (Set $set, $state) {
                            if ($state) {
                                $sale = Sale::find($state);
                                if ($sale) {
                                    $set('customer_id', $sale->customer_id);
                                }
                            }
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set, $state) {
                            $set('refunded_items', []);
                            $set('refund_amount', 0);

                            $sale = Sale::find($state);
                            if ($sale) {
                                $set('customer_id', $sale->customer_id);
                            }
                        }),

                    Forms\Components\CheckboxList::make('refunded_items')
                        ->label('Select Items to Refund')
                        ->options(function (Get $get, ?Refund $record) {
                            $saleId = $get('sale_id');
                            if (!$saleId) return [];

                            $sale = Sale::find($saleId);
                            if (!$sale) return [];

                            $alreadyRefundedIds = Refund::where('sale_id', $saleId)
                                ->where('status', 'approved')
                                ->when($record, fn($query) => $query->where('id', '!=', $record->id))
                                ->get()
                                ->pluck('refunded_items')
                                ->flatten()
                                ->toArray();

                           return $sale->items
                                ->whereNotIn('id', $alreadyRefundedIds)
                                ->mapWithKeys(fn($item) => [
                                    $item->id => ($item->productItem->barcode ?? ($item->custom_description ? 'NON-TAG' : 'Stock'))
                                        . " | " . \Illuminate\Support\Str::limit(strip_tags($item->custom_description ?? '—'), 50)
                                        . " — $" . number_format($item->sold_price, 2)
                                ]);
                        })
                        ->visible(fn(Get $get) => $get('sale_id'))
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set) {
                            $amount = SaleItem::whereIn('id', $state ?? [])->sum('sold_price');
                            $set('refund_amount', number_format($amount, 2, '.', ''));
                        })
                        ->columns(2),

                    Forms\Components\Select::make('quality_check')
                        ->options([
                            'excellent' => 'Excellent (Resalable)',
                            'good'      => 'Good',
                            'damaged'   => 'Damaged',
                        ])->required(),

                    // 🚀 NEW — item disposition is now two independent toggles instead of
                    // being bundled into refund_method. "How the customer gets paid back"
                    // (cash/credit) and "where the physical item goes" (stock/vendor) are
                    // separate decisions — e.g. a customer can get store credit while the
                    // item is still in transit back to a vendor, not yet confirmed.
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Toggle::make('should_restock')
                            ->label('Return checked items to Stock?')
                            ->helperText('Item goes back into your own sellable inventory.')
                            ->default(true)
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) $set('return_to_vendor', false);
                            }),

                        Forms\Components\Toggle::make('return_to_vendor')
                            ->label('Return checked items to Vendor?')
                            ->helperText('Item leaves your inventory entirely — tracked separately until the vendor confirms credit. Use this whether the item is defective, unwanted, or still in transit back to them.')
                            ->default(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) $set('should_restock', false);
                            }),
                    ]),

                    Forms\Components\TextInput::make('refund_amount')
                        ->label('Refund Amount')
                        ->helperText('Auto-filled from selected items — adjust if needed (e.g. restocking fee).')
                        ->numeric()->prefix('$')->required()
                        ->live(onBlur: true),

                    Forms\Components\Radio::make('refund_method')
                        ->label('How Should the Customer Be Refunded?')
                        ->options([
                            'cash'         => 'Cash / Original Payment Method',
                            'store_credit' => 'Store Credit (added to customer\'s account)',
                        ])
                        ->descriptions([
                            'cash'         => 'Reverses the payment on this sale directly.',
                            'store_credit' => 'Customer keeps this value to use on any future purchase — no cash leaves the register.',
                        ])
                        ->default('cash')
                        ->required()
                        ->live()
                        ->columnSpanFull(),

                    Forms\Components\Placeholder::make('store_credit_preview')
                        ->hiddenLabel()
                        ->visible(fn(Get $get) => $get('refund_method') === 'store_credit')
                        ->live()
                        ->content(function (Get $get) {
                            $customerId = $get('customer_id');
                            $amount     = floatval($get('refund_amount') ?? 0);
                            $customer   = $customerId ? \App\Models\Customer::find($customerId) : null;

                            if (!$customer) {
                                return new \Illuminate\Support\HtmlString("<div style='background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:10px 14px;font-size:12px;color:#991b1b;'>⚠️ Select a sale first so the customer's account can be identified.</div>");
                            }

                            $currentBalance = floatval($customer->credit_balance ?? 0);
                            $newBalance     = $currentBalance + $amount;

                            return new \Illuminate\Support\HtmlString("
                                <div style='background:#f5f3ff;border:1px solid #c4b5fd;border-radius:8px;padding:10px 14px;font-size:12px;'>
                                    <strong style='color:#6d28d9;'>{$customer->name} {$customer->last_name}</strong>'s store credit will go from
                                    <strong>\$" . number_format($currentBalance, 2) . "</strong> to
                                    <strong style='color:#6d28d9;'>\$" . number_format($newBalance, 2) . "</strong>.
                                </div>
                            ");
                        }),

                    // 🚀 NEW — preview now keys off the return_to_vendor toggle, not
                    // refund_method, so it shows regardless of how the customer is paid.
                    Forms\Components\Placeholder::make('vendor_return_preview')
                        ->hiddenLabel()
                        ->visible(fn(Get $get) => $get('return_to_vendor'))
                        ->live()
                        ->content(function (Get $get) {
                            $itemIds = $get('refunded_items') ?? [];
                            if (empty($itemIds)) {
                                return new \Illuminate\Support\HtmlString("<div style='background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:10px 14px;font-size:12px;color:#991b1b;'>⚠️ Select at least one item above to return to vendor.</div>");
                            }

                            $items = SaleItem::whereIn('id', $itemIds)->with('productItem.supplier')->get();
                            $rows  = '';
                            foreach ($items as $si) {
                                $pi       = $si->productItem;
                                $supplier = $pi?->supplier?->company_name ?? 'Unknown Vendor';
                                $cost     = number_format($pi?->cost_price ?? 0, 2);
                                $rows .= "<div style='display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px dashed #fed7aa;font-size:12px;'>
                                    <span><strong>{$pi?->barcode}</strong> — {$supplier}</span>
                                    <span style='color:#c2410c;font-weight:700;'>Cost: \${$cost}</span>
                                </div>";
                            }

                            return new \Illuminate\Support\HtmlString("
                                <div style='background:#fff7ed;border:1px solid #fdba74;border-radius:8px;padding:12px 14px;'>
                                    <div style='font-size:11px;font-weight:800;color:#c2410c;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px;'>Items Pending Vendor Return</div>
                                    {$rows}
                                </div>
                            ");
                        }),

                    Forms\Components\Textarea::make('remarks')->columnSpanFull(),

                    Forms\Components\Hidden::make('processed_by')->default(auth()->id()),
                    Forms\Components\Hidden::make('customer_id')
                        ->live()
                        ->default(function () {
                            $saleId = request('sale_id');
                            if ($saleId) {
                                return Sale::find($saleId)?->customer_id;
                            }
                            return null;
                        }),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('refund_no')->label('Refund #')->searchable(),
            Tables\Columns\TextColumn::make('sale.invoice_number')->label('Invoice'),
            Tables\Columns\TextColumn::make('status')->badge()
                ->color(fn($state) => match ($state) {
                    'approved' => 'success',
                    'pending'  => 'warning',
                    default    => 'gray'
                }),
            Tables\Columns\TextColumn::make('refund_method')
                ->label('Refund Method')
                ->badge()
                ->formatStateUsing(fn($state) => $state === 'store_credit' ? '💳 Store Credit' : '💵 Cash')
                ->color(fn($state) => $state === 'store_credit' ? 'purple' : 'success'),
            Tables\Columns\IconColumn::make('return_to_vendor')
                ->label('To Vendor?')
                ->boolean()
                ->trueIcon('heroicon-o-arrow-uturn-left')
                ->falseIcon('heroicon-o-minus')
                ->trueColor('warning')
                ->falseColor('gray'),
            Tables\Columns\TextColumn::make('refund_amount')->money('USD'),
        ])
            ->actions([
                Tables\Actions\Action::make('approveRefund')
                    ->label('Approve')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn($record) => $record->status === 'pending'
                        && auth()->user()->hasAnyRole(['Superadmin', 'Administration']))
                    ->action(function (Refund $record) {
                        DB::transaction(function () use ($record) {

                            // 🚀 NEW — vendor return branch, checked first since it now
                            // controls item disposition independently of should_restock.
                            if ($record->return_to_vendor && !empty($record->refunded_items)) {
                                $items = SaleItem::whereIn('id', $record->refunded_items)
                                    ->with('productItem.supplier')
                                    ->get();

                                foreach ($items as $si) {
                                    $pi = $si->productItem;
                                    if (!$pi) continue;

                                    $pi->update([
                                        'status'                => 'returned_to_vendor',
                                        'qty'                   => 0,
                                        'returned_to_vendor_at' => now(),
                                    ]);

                                    VendorReturn::create([
                                        'refund_id'              => $record->id,
                                        'product_item_id'        => $pi->id,
                                        'supplier_id'            => $pi->supplier_id,
                                        'stock_no'               => $pi->barcode,
                                        'cost_price'             => $pi->cost_price ?? 0,
                                        'return_credit_expected' => $pi->cost_price ?? 0,
                                        'status'                 => 'pending',
                                        'notes'                  => $record->remarks,
                                        'processed_by'           => auth()->id(),
                                    ]);
                                }
                            } elseif ($record->should_restock && !empty($record->refunded_items)) {
                                $items = SaleItem::whereIn('id', $record->refunded_items)->get();
                                foreach ($items as $item) {
                                   if ($item->productItem) {
                                        $item->productItem->update([
                                            'status' => 'in_stock',
                                            'qty'    => max(1, $item->productItem->qty + intval($item->qty ?? 1)),
                                        ]);

                                        Restock::create([
                                            'refund_id'       => $record->id,
                                            'product_item_id' => $item->product_item_id,
                                            'stock_no'        => $item->productItem->barcode,
                                            'salesperson_name'=> auth()->user()->name,
                                            'status'          => 'completed',
                                        ]);
                                    }
                                }
                            }

                            $sale = $record->sale;
                            if ($sale) {
                                $totalItemsInSale   = $sale->items()->count();
                                $totalItemsRefunded = collect($record->refunded_items)->count();
                                $newStatus = ($totalItemsRefunded >= $totalItemsInSale)
                                    ? 'refunded'
                                    : 'partially_refunded';
                                $sale->update(['status' => $newStatus]);
                            }

                            // 🚀 Money side (cash vs store credit) is now completely
                            // independent of the item-disposition branch above — a
                            // vendor return can still pay the customer cash or credit.
                            if ($record->refund_method === 'store_credit') {
                                $customer = \App\Models\Customer::find($record->customer_id);
                                if ($customer) {
                                    $customer->increment('credit_balance', abs($record->refund_amount));
                                }
                            } else {
                                \App\Models\Payment::create([
                                    'sale_id' => $record->sale_id,
                                    'amount'  => -abs($record->refund_amount),
                                    'method'  => $record->sale?->payment_method ?? 'cash',
                                    'paid_at' => now(),
                                ]);
                            }

                            $record->update([
                                'status'      => 'approved',
                                'approved_by' => auth()->id(),
                            ]);
                        });

                        $moneyLabel  = $record->refund_method === 'store_credit' ? 'issued as store credit' : 'refunded to original payment method';
                        $itemLabel   = $record->return_to_vendor ? ', item(s) sent for vendor return' : ($record->should_restock ? ', item(s) back in stock' : '');

                        Notification::make()
                            ->title("Refund Approved — {$moneyLabel}{$itemLabel}.")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => RefundResource\Pages\ListRefunds::route('/'),
            'create' => RefundResource\Pages\CreateRefund::route('/create'),
            'edit'   => RefundResource\Pages\EditRefund::route('/{record}/edit'),
        ];
    }
}