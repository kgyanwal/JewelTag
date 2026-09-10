<?php

namespace App\Filament\Resources;

use App\Models\Refund;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Restock;
use App\Models\VendorReturn;
use App\Models\Customer;
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
            // 🚀 FIX — collapsed back to ONE unified flow. Every custom order and
            // laybuy already creates a real Sale record immediately (status =
            // pending) and stays synced, so there is no separate "not yet moved
            // to Sales" state to branch on. A refund always targets a sale_id —
            // whether that sale is fully paid, partially paid, laybuy, or a
            // custom order still in production.
            Forms\Components\Section::make('Refund Selection')
                ->schema([
                    Forms\Components\TextInput::make('refund_no')
                        ->label('Refund Receipt #')
                        ->default(fn() => 'RFD-' . strtoupper(bin2hex(random_bytes(4))))
                        ->readOnly()
                        ->dehydrated()
                        ->required(),

                    Forms\Components\Select::make('sale_id')
                        ->label('Original Sale / Invoice')
                        // 🚀 FIX — no longer restricted to status='completed'. A
                        // partially-paid sale, a laybuy still in progress, or a
                        // custom order still in production all have real money
                        // sitting on a real Sale record and are equally valid
                        // refund targets. Only exclude sales with nothing to
                        // refund at all.
                        ->relationship(
                            'sale',
                            'invoice_number',
                            fn(Builder $query) => $query->whereNotIn('status', ['cancelled', 'void'])
                        )
                        ->getOptionLabelFromRecordUsing(function (Sale $sale) {
                            $paid = $sale->payments()->sum('amount') + $sale->salePayments()->sum('amount');
                            if ($paid == 0) $paid = floatval($sale->amount_paid);
                            $balanceNote = floatval($sale->balance_due) > 0.01 ? ' — Balance Due' : ' — Paid in Full';
                            return "#{$sale->invoice_number} — Paid: $" . number_format($paid, 2) . $balanceNote;
                        })
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

                    // 🚀 NEW — shows the sale's actual payment state right where staff
                    // are choosing it, so a partially-paid sale is never mistaken for
                    // a fully paid one before refunding.
                    Forms\Components\Placeholder::make('sale_payment_status')
                        ->hiddenLabel()
                        ->visible(fn(Get $get) => $get('sale_id'))
                        ->live()
                        ->content(function (Get $get) {
                            $sale = Sale::find($get('sale_id'));
                            if (!$sale) return '';

                            $paid = $sale->payments()->sum('amount') + $sale->salePayments()->sum('amount');
                            if ($paid == 0) $paid = floatval($sale->amount_paid);
                            $balance = max(0, floatval($sale->final_total) - $paid);

                            $bg = $balance > 0.01 ? '#fef2f2' : '#f0fdf4';
                            $border = $balance > 0.01 ? '#fca5a5' : '#86efac';
                            $label = $balance > 0.01
                                ? "⚠️ This sale still has a balance due of \$" . number_format($balance, 2) . " — only \$" . number_format($paid, 2) . " has actually been collected so far."
                                : "✅ Fully paid — \$" . number_format($paid, 2) . " collected.";

                            return new \Illuminate\Support\HtmlString("
                                <div style='background:{$bg};border:1px solid {$border};border-radius:8px;padding:10px 14px;font-size:12px;'>{$label}</div>
                            ");
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

                    // 🚀 Two independent toggles: "where the item goes" is a
                    // separate decision from "how the customer gets refunded"
                    // (see the Refund Method radio further below). Return to
                    // Vendor is ALWAYS available here regardless of payment
                    // status — a defective/unwanted item can be sent back to
                    // the vendor whether the sale was fully paid or not.
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
                ])->columns(2),

            Forms\Components\Section::make('Refund Amount & Method')
                ->schema([
                    Forms\Components\TextInput::make('refund_amount')
                        ->label('Refund Amount')
                        ->helperText('Auto-filled from selected items — adjust if needed (e.g. restocking fee, or only refunding the deposit collected so far).')
                        ->numeric()->prefix('$')->required()
                        ->live(onBlur: true)
                        // 🚀 NEW — hard cap: can never refund more than has
                        // actually been collected on this sale, whether it's
                        // fully paid or still partial.
                        ->rule(function (Get $get) {
                            return function (string $attribute, $value, \Closure $fail) use ($get) {
                                $sale = Sale::find($get('sale_id'));
                                if (!$sale) return;
                                $paid = $sale->payments()->sum('amount') + $sale->salePayments()->sum('amount');
                                if ($paid == 0) $paid = floatval($sale->amount_paid);
                                if (floatval($value) > $paid) {
                                    $fail('Cannot refund more than the amount actually collected on this sale ($' . number_format($paid, 2) . ').');
                                }
                            };
                        }),

                    Forms\Components\Radio::make('refund_method')
                        ->label('How Should the Customer Be Refunded?')
                        ->options([
                            'cash'         => 'Cash / Original Payment Method',
                            'store_credit' => 'Store Credit (added to customer\'s account)',
                        ])
                        ->descriptions([
                            'cash'         => 'Reverses the payment directly.',
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
                            $customer   = $customerId ? Customer::find($customerId) : null;

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

                    // 🚀 Return-to-vendor preview is ALWAYS available (no gating on
                    // payment status) — it just needs items checked above.
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

            // 🚀 NEW — shows whether the underlying sale was fully paid or still
            // partial at the time of refund, since both are now valid targets.
            Tables\Columns\TextColumn::make('sale_payment_state')
                ->label('Sale Was')
                ->getStateUsing(function (Refund $record) {
                    $sale = $record->sale;
                    if (!$sale) return '—';
                    return floatval($sale->balance_due) > 0.01 ? 'Partially Paid' : 'Fully Paid';
                })
                ->badge()
                ->color(fn($state) => $state === 'Partially Paid' ? 'warning' : 'success'),

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

                            // ── ITEM DISPOSITION — return to vendor OR restock ──
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

                            // ── SALE STATUS SYNC ──
                            $sale = $record->sale;
                            if ($sale) {
                                $totalItemsInSale   = $sale->items()->count();
                                $totalItemsRefunded = collect($record->refunded_items)->count();

                                // 🚀 FIX — a partially-paid sale (e.g. laybuy or
                                // custom order still in production) getting a
                                // refund isn't necessarily "refunded/partially
                                // refunded" in the customer-facing sense the old
                                // logic assumed (which was written only for fully
                                // completed sales). We still mark item-level
                                // refund status the same way, but leave sales that
                                // were never 'completed' on their existing
                                // workflow status (pending/in_production etc.)
                                // rather than force-labeling them refunded.
                                if ($sale->status === 'completed') {
                                    $newStatus = ($totalItemsRefunded >= $totalItemsInSale)
                                        ? 'refunded'
                                        : 'partially_refunded';
                                    $sale->update(['status' => $newStatus]);
                                }

                                // Always resync amount_paid/balance_due from the DB
                                // after the refund payment below is inserted.
                            }

                            // ── MONEY SIDE — cash reversal vs store credit ──
                            if ($record->refund_method === 'store_credit') {
                                $customer = Customer::find($record->customer_id);
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

                            // 🚀 NEW — resync the Sale's amount_paid/balance_due
                            // from the DB after the refund, same pattern used
                            // everywhere else in CreateSale/EditSale, so a
                            // partially-paid sale's balance reflects the refund
                            // immediately instead of drifting stale.
                            if ($sale) {
                                $totalPaid = \App\Models\Payment::where('sale_id', $sale->id)->sum('amount')
                                    + $sale->salePayments()->sum('amount');
                                $sale->update([
                                    'amount_paid' => round($totalPaid, 2),
                                    'balance_due' => max(0, round(floatval($sale->final_total) - $totalPaid, 2)),
                                ]);
                            }

                            $record->update([
                                'status'      => 'approved',
                                'approved_by' => auth()->id(),
                            ]);
                        });

                        $moneyLabel = $record->refund_method === 'store_credit' ? 'issued as store credit' : 'refunded to original payment method';
                        $itemLabel  = $record->return_to_vendor ? ', item(s) sent for vendor return' : ($record->should_restock ? ', item(s) back in stock' : '');

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