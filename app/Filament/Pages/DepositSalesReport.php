<?php

namespace App\Filament\Pages;

use App\Models\DepositSale;
use App\Models\Payment;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

/**
 * DepositSalesReport
 *
 * Shows all deposit sales from the deposit_sales table.
 * Supports recording balance payments and linking to sales.
 */
class DepositSalesReport extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon  = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Analytics & Reports';
    protected static ?string $navigationLabel = 'Deposit Sales Report';
    protected static ?int    $navigationSort  = 3;
    protected static string  $view            = 'filament.pages.deposit-sales-report';

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
                            // Show notes as fallback
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
                        // Get payments from linked sale if exists
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
                SelectFilter::make('status')
                    ->options([
                        'active'    => 'Active Deposits',
                        'completed' => 'Fully Paid',
                        'cancelled' => 'Cancelled',
                    ]),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')->label('From')->default(now()->startOfMonth()),
                        DatePicker::make('to')->label('To')->default(now()),
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
                // Record a balance payment
                Action::make('add_payment')
                    ->label('Add Payment')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn(DepositSale $record) => $record->balance_due > 0)
                    ->form([
                        TextInput::make('amount')
                            ->label('Payment Amount')
                            ->numeric()
                            ->required()
                            ->prefix('$')
                            ->default(fn(DepositSale $record) => $record->balance_due),
                        Select::make('method')
                            ->label('Payment Method')
                            ->options([
                                'CASH'                   => 'Cash',
                                'DEBIT CARD'             => 'Debit Card',
                                'VISA'                   => 'Visa',
                                'MASTERCARD'             => 'Mastercard',
                                'AMERICAN EXPRESS'       => 'American Express',
                                'KAFENE'                 => 'Kafene',
                                'ACIMA'                  => 'Acima',
                                'PROGRESSIVE LEASING'    => 'Progressive Leasing',
                                'AMERICAN FIRST FINANCE' => 'American First Finance',
                                'WELLS FARGO'            => 'Wells Fargo',
                                'COMENITY BANK'          => 'Comenity Bank',
                                'KATAPULT'               => 'Katapult',
                            ])
                            ->required(),
                    ])
                    ->action(function (DepositSale $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $amount = (float) $data['amount'];

                            // Record payment against linked sale if exists
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

                            // If fully paid and has linked sale, mark sale completed
                            if ($newBalance <= 0 && $record->sale_id) {
                                \App\Models\Sale::where('id', $record->sale_id)->update([
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

                // View linked sale
                Action::make('view_sale')
                    ->label('View Sale')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn(DepositSale $record) => $record->sale_id !== null)
                    ->url(fn(DepositSale $record) =>
                        \App\Filament\Resources\SaleResource::getUrl('edit', ['record' => $record->sale_id])
                    ),
            ])

            ->persistFiltersInSession()
            ->defaultSort('start_date', 'desc')
            ->striped()
            ->paginated([25, 50, 100]);
    }
}