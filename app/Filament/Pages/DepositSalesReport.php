<?php

namespace App\Filament\Pages;

use App\Models\DepositSale;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use App\Forms\Components\CustomDatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use App\Helpers\Staff;

class DepositSalesReport extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon  = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'Deposit Sales Report';
    protected static ?int    $navigationSort  = 3;
    protected static string  $view            = 'filament.pages.deposit-sales-report';

    public static function canAccess(): bool
    {
        $user = Staff::user();
        return $user && $user->hasAnyRole(['Superadmin', 'Administration']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DepositSale::query()
                    ->with(['customer', 'sale.items.productItem', 'sale.payments'])
                    ->latest('start_date')
            )
            ->columns([

                // ── Customer & Deposit No ──────────────────────────────
                TextColumn::make('customer.name')
                    ->label('Customer & Deposit')
                    ->weight(FontWeight::Bold)
                    ->formatStateUsing(fn($state, $record) =>
                        trim(($record->customer?->name ?? '') . ' ' . ($record->customer?->last_name ?? ''))
                    )
                    ->description(fn(DepositSale $record) =>
                        $record->deposit_no .
                        ($record->sale ? ' | Sale: ' . $record->sale->invoice_number : ' | No sale linked')
                    )
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->whereHas('customer', fn($q) =>
                                $q->where('name', 'like', "%{$search}%")
                                  ->orWhere('last_name', 'like', "%{$search}%")
                                  ->orWhere('phone', 'like', "%{$search}%")
                            )
                            ->orWhereHas('sale', fn($q) =>
                                $q->where('invoice_number', 'like', "%{$search}%")
                            )
                            ->orWhere('deposit_no', 'like', "%{$search}%");
                    }),

                // ── Date Sold ──────────────────────────────────────────
                TextColumn::make('start_date')
                    ->label('Date Sold')
                    ->date('M d, Y')
                    ->sortable()
                    ->grow(false),

                // ── Items ──────────────────────────────────────────────
                TextColumn::make('stock_items')
                    ->label('Items')
                    ->getStateUsing(function (DepositSale $record) {
                        $items = $record->sale?->items ?? collect();

                        if ($items->isEmpty()) {
                            $notes = $record->notes
                                ? strip_tags($record->notes)
                                : 'No items linked';
                            return new HtmlString(
                                "<span class='text-[11px] text-gray-400 italic'>{$notes}</span>"
                            );
                        }

                        $rows = $items->map(function ($item) {
                            $barcode = htmlspecialchars($item->productItem?->barcode ?? '—');
                            $desc    = htmlspecialchars(
                                $item->custom_description
                                ?? $item->productItem?->custom_description
                                ?? 'Custom Item'
                            );
                            $price = number_format($item->sold_price ?? 0, 2);

                            return "
                                <div class='flex items-start gap-1.5 mb-1 last:mb-0'>
                                    <span class='text-[9px] bg-primary-100 text-primary-700 px-1.5 py-0.5 rounded font-mono font-bold flex-shrink-0 mt-0.5'>{$barcode}</span>
                                    <div>
                                        <div class='text-xs text-gray-700 leading-tight'>{$desc}</div>
                                        <div class='text-[10px] text-gray-400'>\${$price}</div>
                                    </div>
                                </div>
                            ";
                        })->implode('');

                        return new HtmlString("<div class='min-w-[150px]'>{$rows}</div>");
                    })
                    ->html(),

                // ── Payment History ────────────────────────────────────
                TextColumn::make('payment_history')
                    ->label('Payment History')
                    ->getStateUsing(function (DepositSale $record) {
                        $payments = $record->sale_id
                            ? Payment::where('sale_id', $record->sale_id)->orderByDesc('paid_at')->get()
                            : collect();

                        if ($payments->isEmpty()) {
                            $paid = number_format($record->amount_paid, 2);
                            return new HtmlString(
                                "<span class='text-[11px] text-gray-400 italic'>Deposit: \${$paid}</span>"
                            );
                        }

                        $rows = $payments->map(function ($p) {
                            $date   = $p->paid_at ? Carbon::parse($p->paid_at)->format('M d, Y') : '—';
                            $method = htmlspecialchars($p->method ?? 'N/A');
                            $amount = number_format($p->amount, 2);
                            return "
                                <div class='flex items-center gap-2 text-[11px] mb-0.5 last:mb-0'>
                                    <span class='text-gray-400 w-[72px] flex-shrink-0'>{$date}</span>
                                    <span class='text-gray-500 uppercase flex-1 truncate'>{$method}</span>
                                    <span class='font-bold text-success-600 flex-shrink-0'>\${$amount}</span>
                                </div>
                            ";
                        })->implode('');

                        $total = number_format($payments->sum('amount'), 2);
                        return new HtmlString("
                            <div class='min-w-[200px]'>
                                {$rows}
                                <div class='border-t border-gray-100 mt-1 pt-1 flex justify-between text-[11px]'>
                                    <span class='font-bold text-gray-400 uppercase'>Total Paid</span>
                                    <span class='font-black text-success-700'>\${$total}</span>
                                </div>
                            </div>
                        ");
                    })
                    ->html(),

                // ── Financial Summary ──────────────────────────────────
                TextColumn::make('total_amount')
                    ->label('Invoice Total')
                    ->money('USD')
                    ->alignRight()
                    ->weight(FontWeight::Bold)
                    ->summarize(Sum::make()->label('Total Invoiced')->money('USD')),

                TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->money('USD')
                    ->color('success')
                    ->alignRight()
                    ->summarize(Sum::make()->label('Total Collected')->money('USD')),

                TextColumn::make('balance_due')
                    ->label('Balance')
                    ->money('USD')
                    ->weight(FontWeight::Bold)
                    ->color(fn($state) => $state > 0 ? 'danger' : 'gray')
                    ->alignRight()
                    ->summarize(Sum::make()->label('Total Outstanding')->money('USD')),

                // ── Staff ──────────────────────────────────────────────
                TextColumn::make('staff_list')
                    ->label('Staff')
                    ->getStateUsing(function (DepositSale $record) {
                        $list = $record->staff_list ?? [];
                        if (empty($list)) return $record->sales_person ?? '—';
                        return implode(' / ', (array) $list);
                    })
                    ->badge()
                    ->color('gray')
                    ->grow(false),

                // ── Status ────────────────────────────────────────────
                TextColumn::make('status')
                    ->badge()
                    ->grow(false)
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'active'      => 'Active',
                        'completed'   => 'Fully Paid',
                        'cancelled'   => 'Cancelled',
                        'in_progress' => 'Active',
                        default       => ucfirst($state),
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default     => 'warning',
                    }),

                // ── Due Date ──────────────────────────────────────────
                TextColumn::make('due_date')
                    ->label('Required By')
                    ->date('M d, Y')
                    ->color(fn($record) =>
                        $record->due_date
                        && Carbon::parse($record->due_date)->isPast()
                        && $record->status !== 'completed'
                            ? 'danger' : 'gray'
                    )
                    ->placeholder('—')
                    ->grow(false),
            ])

            // ── Filters ───────────────────────────────────────────────
            ->filters([

                // ── 🔍 Item / Keyword Search ──────────────────────────
                Filter::make('item_keyword_search')
                    ->form([
                        TextInput::make('search_item')
                            ->label('🔍 Item / Keyword Search')
                            ->placeholder('Barcode, stock #, description, cert #, SKU...'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['search_item'] ?? null,
                            fn(Builder $query, string $search) => $query->where(function ($q) use ($search) {
                                $q->where('notes', 'like', "%{$search}%")
                                  ->orWhereHas('sale.items.productItem', fn($qi) =>
                                      $qi->where('barcode', 'like', "%{$search}%")
                                         ->orWhere('custom_description', 'like', "%{$search}%")
                                         ->orWhere('certificate_number', 'like', "%{$search}%")
                                         ->orWhere('supplier_code', 'like', "%{$search}%")
                                         ->orWhere('serial_number', 'like', "%{$search}%")
                                         ->orWhere('rfid_code', 'like', "%{$search}%")
                                  )
                                  ->orWhereHas('sale.items', fn($qi) =>
                                      $qi->where('custom_description', 'like', "%{$search}%")
                                  );
                            })
                        );
                    })
                    ->indicateUsing(fn(array $data) =>
                        filled($data['search_item'] ?? null)
                            ? '🔍 Item: ' . $data['search_item']
                            : null
                    ),

                // ── Customer Search ───────────────────────────────────
                Filter::make('customer_search')
                    ->form([
                        TextInput::make('search_customer')
                            ->label('Customer Search')
                            ->placeholder('Enter name or phone...'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['search_customer'],
                            fn (Builder $query, $search) => $query->whereHas('customer', function ($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%")
                                  ->orWhere('last_name', 'like', "%{$search}%")
                                  ->orWhere('phone', 'like', "%{$search}%")
                                  ->orWhere(DB::raw("CONCAT(name, ' ', COALESCE(last_name, ''))"), 'like', "%{$search}%");
                            })
                        );
                    }),

                SelectFilter::make('status')
                    ->options([
                        'active'    => 'Active Deposits',
                        'completed' => 'Fully Paid',
                        'cancelled' => 'Cancelled',
                    ]),

                Filter::make('date_range')
                    ->form([
                        CustomDatePicker::make('from')->label('From')->default(now()->startOfMonth()),
                        CustomDatePicker::make('to')->label('To')->default(now()),
                    ])
                    ->query(fn(Builder $query, array $data) =>
                        $query
                            ->when($data['from'], fn($q) => $q->whereDate('start_date', '>=', $data['from']))
                            ->when($data['to'],   fn($q) => $q->whereDate('start_date', '<=', $data['to']))
                    ),

                Filter::make('staff')
                    ->form([
                        Select::make('sales_person')
                            ->label('Staff Member')
                            ->options(fn() => User::pluck('name', 'name')->toArray())
                            ->placeholder('All Staff'),
                    ])
                    ->query(fn(Builder $query, array $data) =>
                        $query->when($data['sales_person'],
                            fn($q) => $q->where('sales_person', $data['sales_person'])
                                ->orWhereJsonContains('staff_list', $data['sales_person'])
                        )
                    ),

                Filter::make('unlinked')
                    ->label('No Sale Linked')
                    ->query(fn(Builder $query) => $query->whereNull('sale_id'))
                    ->toggle(),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)

            // ── Header Actions ────────────────────────────────────────
            ->headerActions([
                Action::make('export_csv')
                    ->label('Download CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function () {
                        $records = DepositSale::with(['customer', 'sale'])
                            ->latest('start_date')
                            ->get();

                        $csv = "Customer,Date Sold,Deposit No,Sale Invoice,Invoice Total,Amount Paid,Balance,Status,Staff,Due Date\n";

                        foreach ($records as $r) {
                            $name  = trim(($r->customer?->name ?? '') . ' ' . ($r->customer?->last_name ?? ''));
                            $staff = implode('/', (array)($r->staff_list ?? [$r->sales_person]));

                            $csv .= implode(',', [
                                '"' . str_replace('"', '""', $name) . '"',
                                $r->start_date?->format('m/d/Y'),
                                $r->deposit_no,
                                $r->sale?->invoice_number ?? '—',
                                number_format($r->total_amount, 2),
                                number_format($r->amount_paid, 2),
                                number_format($r->balance_due, 2),
                                $r->status,
                                '"' . $staff . '"',
                                $r->due_date?->format('m/d/Y') ?? '',
                            ]) . "\n";
                        }

                        return response()->streamDownload(
                            fn() => print($csv),
                            'deposit_sales_' . now()->format('Y_m_d') . '.csv'
                        );
                    }),
            ])

            // ── Row Actions ───────────────────────────────────────────
            ->actions([

                // ── ADD PAYMENT (linked sales only) ───────────────────
                Action::make('add_payment')
                    ->label('Add Payment')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn(DepositSale $record) => $record->balance_due > 0 && $record->sale_id !== null)
                    ->form([
                        TextInput::make('amount')
                            ->label('Payment Amount')
                            ->numeric()
                            ->required()
                            ->prefix('$')
                            ->default(fn(DepositSale $record) => $record->balance_due),
                        Select::make('method')
                            ->label('Payment Method')
                            ->options(function () {
                                $methodsJson = DB::table('site_settings')
                                    ->where('key', 'payment_methods')
                                    ->value('value');
                                $activeMethods = $methodsJson ? json_decode($methodsJson, true) : [
                                    'CASH', 'VISA', 'MASTERCARD', 'AMERICAN EXPRESS', 'DEBIT CARD'
                                ];
                                $options = [];
                                if (is_array($activeMethods)) {
                                    foreach ($activeMethods as $method) {
                                        $options[strtoupper($method)] = ucwords(strtolower($method));
                                    }
                                }
                                return $options;
                            })
                            ->required(),
                    ])
                    ->action(function (DepositSale $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $amount = (float) $data['amount'];

                            if ($record->sale_id) {
                                Payment::create([
                                    'sale_id' => $record->sale_id,
                                    'amount'  => $amount,
                                    'method'  => $data['method'],
                                    'paid_at' => now(),
                                ]);
                            }

                            $newPaid    = round($record->amount_paid + $amount, 2);
                            $newBalance = round(max(0, $record->total_amount - $newPaid), 2);
                            $newStatus  = $newBalance <= 0 ? 'completed' : 'active';

                            $record->update([
                                'amount_paid'    => $newPaid,
                                'balance_due'    => $newBalance,
                                'status'         => $newStatus,
                                'last_paid_date' => now()->toDateString(),
                            ]);

                            if ($newBalance <= 0 && $record->sale_id) {
                                Sale::where('id', $record->sale_id)->update([
                                    'status'       => 'completed',
                                    'amount_paid'  => $newPaid,
                                    'balance_due'  => 0,
                                    'completed_at' => now(),
                                ]);
                            }
                        });

                        Notification::make()
                            ->title('Payment Recorded')
                            ->body('$' . number_format($data['amount'], 2) . ' recorded successfully.')
                            ->success()
                            ->send();
                    }),

                // ── VIEW SALE (linked sales only) ─────────────────────
                Action::make('view_sale')
                    ->label('View Sale')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn(DepositSale $record) => $record->sale_id !== null)
                    ->url(fn(DepositSale $record) =>
                        \App\Filament\Resources\SaleResource::getUrl('edit', ['record' => $record->sale_id])
                    ),

                // ── UNLINKED DEPOSIT ACTIONS (sale_id is NULL) ────────
                // Grouped so the row stays clean; only shows when no sale linked
                ActionGroup::make([

                    // ── 1. LINK TO EXISTING SALE ──────────────────────
                    // Lets you pick any existing sale for this customer
                    // and attach it to the deposit record.
                    Action::make('link_existing_sale')
                        ->label('Link to Existing Sale')
                        ->icon('heroicon-o-link')
                        ->color('info')
                        ->form(function (DepositSale $record) {
                            // Load this customer's sales that are not yet linked to any deposit
                            $linkedSaleIds = DepositSale::whereNotNull('sale_id')
                                ->pluck('sale_id')
                                ->toArray();

                            $sales = Sale::where('customer_id', $record->customer_id)
                                ->whereNotIn('id', $linkedSaleIds)
                                ->latest()
                                ->get()
                                ->mapWithKeys(fn($s) =>
                                    [$s->id => "#{$s->invoice_number} — $" . number_format($s->final_total, 2) . " — " . $s->created_at?->format('M d, Y')]
                                );

                            return [
                                // Info banner showing the deposit details
                                \Filament\Forms\Components\Placeholder::make('deposit_info')
                                    ->hiddenLabel()
                                    ->content(function () use ($record) {
                                        $name = trim(($record->customer?->name ?? '') . ' ' . ($record->customer?->last_name ?? ''));
                                        return new HtmlString("
                                            <div class='p-3 bg-info-50 border border-info-200 rounded-lg text-sm mb-2'>
                                                <div class='font-black text-info-800 mb-1'>🔗 Linking Deposit: {$record->deposit_no}</div>
                                                <div class='text-info-700'>Customer: <strong>{$name}</strong></div>
                                                <div class='text-info-700'>Deposit Total: <strong>$" . number_format($record->total_amount, 2) . "</strong></div>
                                                <div class='mt-1 text-xs text-info-600'>
                                                    Only this customer's unlinked sales are shown below.
                                                </div>
                                            </div>
                                        ");
                                    }),

                                Select::make('sale_id')
                                    ->label('Select Sale to Link')
                                    ->options($sales)
                                    ->searchable()
                                    ->required()
                                    ->helperText($sales->isEmpty()
                                        ? '⚠️ No unlinked sales found for this customer. Use "Create New Sale" instead.'
                                        : 'Select the sale invoice to attach to this deposit.'
                                    )
                                    ->disabled($sales->isEmpty()),
                            ];
                        })
                        ->modalHeading('Link Deposit to Existing Sale')
                        ->modalSubmitActionLabel('Link Sale')
                        ->action(function (DepositSale $record, array $data) {
                            if (empty($data['sale_id'])) {
                                Notification::make()->title('No sale selected')->warning()->send();
                                return;
                            }

                            $sale = Sale::find($data['sale_id']);
                            if (!$sale) {
                                Notification::make()->title('Sale not found')->danger()->send();
                                return;
                            }

                            // Link the sale to this deposit
                            $record->update([
                                'sale_id'      => $sale->id,
                                'total_amount' => $sale->final_total,
                                'amount_paid'  => $sale->amount_paid,
                                'balance_due'  => $sale->balance_due,
                                'status'       => $sale->balance_due <= 0 ? 'completed' : 'active',
                            ]);

                            Notification::make()
                                ->title('Sale Linked!')
                                ->body("Deposit {$record->deposit_no} is now linked to invoice #{$sale->invoice_number}.")
                                ->success()
                                ->send();
                        }),

                    // ── 2. CREATE NEW SALE ────────────────────────────
                    // Redirects to SaleResource create page pre-filled
                    // with this customer. The deposit can be manually
                    // linked back after the sale is created.
                    Action::make('create_new_sale')
                        ->label('Create New Sale')
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
                        ->form(function (DepositSale $record) {
                            $name = trim(($record->customer?->name ?? '') . ' ' . ($record->customer?->last_name ?? ''));
                            return [
                                \Filament\Forms\Components\Placeholder::make('create_info')
                                    ->hiddenLabel()
                                    ->content(new HtmlString("
                                        <div class='p-3 bg-success-50 border border-success-200 rounded-lg text-sm'>
                                            <div class='font-black text-success-800 mb-1'>✅ Creating Sale for: {$name}</div>
                                            <div class='text-success-700'>Deposit: <strong>{$record->deposit_no}</strong></div>
                                            <div class='text-success-700'>Deposit Amount: <strong>$" . number_format($record->total_amount, 2) . "</strong></div>
                                            <div class='mt-2 text-xs text-success-600 bg-success-100 rounded p-2'>
                                                You will be redirected to the Quick Sale page with this customer pre-selected.
                                                After completing the sale, come back here and use <strong>\"Link to Existing Sale\"</strong>
                                                to connect this deposit to that new invoice.
                                            </div>
                                        </div>
                                    ")),
                            ];
                        })
                        ->modalHeading('Create New Sale for This Customer')
                        ->modalSubmitActionLabel('Go to Quick Sale →')
                        ->action(function (DepositSale $record) {
                            // Redirect to SaleResource create with customer pre-filled
                            $url = \App\Filament\Resources\SaleResource::getUrl('create', [
                                'customer_id' => $record->customer_id,
                            ]);

                            // Store the deposit ID in session so the sale create page
                            // can optionally auto-link back (if you add that later)
                            session(['pending_deposit_link' => $record->id]);

                            return redirect($url);
                        }),

                ])
                ->label('Link / Create Sale')
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('warning')
                ->button()
                ->visible(fn(DepositSale $record) => $record->sale_id === null),
            ])
            ->persistFiltersInSession()
            ->defaultSort('start_date', 'desc')
            ->striped()
            ->paginated([25, 50, 100]);
    }
}