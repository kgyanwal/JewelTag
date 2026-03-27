<?php

namespace App\Filament\Pages;

use App\Models\Laybuy;
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
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

/**
 * DepositSalesReport
 *
 * WHAT THIS SHOWS (simply):
 *   A list of all layby/deposit plans from the laybuys table.
 *   Each row shows:
 *     - Customer name + Job number
 *     - What items they are buying (from sale_items → product_items)
 *     - Payment history (from payments table via sale_id)
 *     - How much they owe (balance_due from laybuys table)
 *     - Staff member who made the sale
 *
 * DATA SOURCES:
 *   laybuys table        → populated by ImportOnSwimDeposits
 *   payments table       → populated by POS when payments are taken
 *                          columns: id, sale_id, amount, method, paid_at
 *   sale_items table     → populated by ImportOnSwimSales
 *   product_items table  → populated by ImportOnSwimStock
 *
 * DOES NOT USE:
 *   sale_payments table  → that's a separate OnSwim import table
 *   LaybuyPayment model  → doesn't exist in your system
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
                Laybuy::query()
                    ->with([
                        'customer',
                        'sale.items.productItem',  // to show stock items (G10274 etc.)
                    ])
                    ->latest('start_date')
            )
            ->columns([

                // ── Customer + Job Number ──────────────────────────────
                // Job number is extracted from invoice_number: "D031926-9363" → #9363
                TextColumn::make('customer.name')
                    ->label('Customer & Job')
                    ->weight(FontWeight::Bold)
                    ->formatStateUsing(fn($state, $record) =>
                        trim(($record->customer?->name ?? '') . ' ' . ($record->customer?->last_name ?? ''))
                    )
                    ->description(fn(Laybuy $record) =>
                        'Job: ' . ($record->sale?->invoice_number ?? $record->laybuy_no) .
                        ' | ' . ($record->sale?->store?->name ?? '—')
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
                            ->orWhere('laybuy_no', 'like', "%{$search}%");
                    }),

                // ── Date Sold ──────────────────────────────────────────
                TextColumn::make('start_date')
                    ->label('Date Sold')
                    ->date('M d, Y')
                    ->sortable()
                    ->grow(false),

                // ── Stock Items ────────────────────────────────────────
                // Shows items from sale_items table linked to this job.
                // Example: [G10274] 1GM Cross Pendant $299.99
                // These items were already imported by ImportOnSwimSales.
                // This does NOT add new items — just displays existing ones.
                TextColumn::make('stock_items')
                    ->label('Items / Stock #')
                    ->getStateUsing(function (Laybuy $record) {
                        $items = $record->sale?->items ?? collect();

                        if ($items->isEmpty()) {
                            return new HtmlString(
                                "<span class='text-[11px] text-gray-300 italic'>No items linked</span>"
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
                // Reads from `payments` table (your actual payments table).
                // columns used: amount, method, paid_at
                // Linked via: payments.sale_id = laybuys.sale_id
                TextColumn::make('payment_history')
                    ->label('Payment History')
                    ->getStateUsing(function (Laybuy $record) {
                        if (!$record->sale_id) {
                            return new HtmlString(
                                "<span class='text-[11px] text-gray-300 italic'>No sale linked</span>"
                            );
                        }

                        // Direct DB query — no relationship needed
                        // Using your actual payments table columns: method, paid_at, amount
                        $payments = Payment::where('sale_id', $record->sale_id)
                            ->orderByDesc('paid_at')
                            ->get();

                        if ($payments->isEmpty()) {
                            return new HtmlString(
                                "<span class='text-[11px] text-gray-300 italic'>No payments yet</span>"
                            );
                        }

                        $rows = $payments->map(function ($p) {
                            // payments table uses 'paid_at' (not 'payment_date')
                            $date   = $p->paid_at
                                ? Carbon::parse($p->paid_at)->format('M d, Y')
                                : '—';
                            // payments table uses 'method' (not 'payment_method')
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
                // staff_list is stored as JSON array ["Jeanette","Anthony"]
                // Displayed as "Jeanette / Anthony"
                TextColumn::make('staff_list')
                    ->label('Staff')
                    ->getStateUsing(function (Laybuy $record) {
                        $list = $record->staff_list ?? [];
                        if (empty($list)) return $record->sales_person ?? '—';
                        return implode(' / ', (array)$list);
                    })
                    ->badge()
                    ->color('gray')
                    ->grow(false),

                // ── Status Badge ───────────────────────────────────────
                // 'active'    = customer still owes money
                // 'completed' = fully paid
                TextColumn::make('status')
                    ->badge()
                    ->grow(false)
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'active'      => 'Active',
                        'completed'   => 'Fully Paid',
                        'cancelled'   => 'Cancelled',
                        'in_progress' => 'Active',  // handle old records
                        default       => ucfirst($state),
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default     => 'warning',
                    }),

                // ── Due Date ───────────────────────────────────────────
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
            ])
            ->filtersLayout(FiltersLayout::AboveContent)

            ->headerActions([
                Action::make('export_csv')
                    ->label('Download CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function () {
                        $records = Laybuy::with(['customer', 'sale'])
                            ->latest('start_date')
                            ->get();

                        $csv = "Customer,Date Sold,Job No,Laybuy No,Invoice Total,Amount Paid,Balance,Status,Staff,Due Date\n";

                        foreach ($records as $r) {
                            $name  = trim(($r->customer?->name ?? '') . ' ' . ($r->customer?->last_name ?? ''));
                            $staff = implode('/', (array)($r->staff_list ?? [$r->sales_person]));

                            $csv .= implode(',', [
                                '"' . str_replace('"', '""', $name) . '"',
                                $r->start_date?->format('m/d/Y'),
                                $r->sale?->invoice_number ?? '—',
                                $r->laybuy_no,
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

            ->actions([
                Action::make('view_sale')
                    ->label('View Sale')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn(Laybuy $record) => $record->sale_id !== null)
                    ->url(fn(Laybuy $record) =>
                        \App\Filament\Resources\SaleResource::getUrl('edit', ['record' => $record->sale_id])
                    ),
            ])

            ->persistFiltersInSession()
            ->defaultSort('start_date', 'desc')
            ->striped()
            ->paginated([25, 50, 100]);
    }
}