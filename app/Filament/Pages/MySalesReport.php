<?php

namespace App\Filament\Pages;

use App\Models\Sale;
use App\Models\User;
use App\Models\Store;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use App\Forms\Components\CustomDatePicker;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB;

class MySalesReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon  = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationGroup = 'Analytics & Reports';
    protected static ?string $navigationLabel = 'My Sales Report';
    protected static ?string $title           = 'Sales Performance Report';
    protected static string  $view            = 'filament.pages.my-sales-report';

    // ─── SHARED HELPER ───────────────────────────────────────────────────────
    // Returns the "effective completion date" expression for a sale.
    // Priority: completed_at → updated_at → created_at
    // This guarantees laybuy sales with a NULL completed_at still sort/filter
    // correctly using the date the record was last touched (which is when
    // quick_pay updated balance_due to 0).
    private static function effectiveDateExpr(): string
    {
        return 'COALESCE(completed_at, updated_at, created_at)';
    }

    public function table(Table $table): Table
    {
        $isPrivileged = auth()->user()->hasAnyRole(['Superadmin', 'Administration']);
        $tz           = Store::first()?->timezone ?? config('app.timezone', 'UTC');

        return $table
            ->query(
                Sale::query()
                    ->with('customer')
                    ->where('status', 'completed')
                    ->where('balance_due', 0)
                    ->when(!$isPrivileged, function (Builder $query) {
                        $query->whereJsonContains('sales_person_list', auth()->user()->name);
                    })
            )
            ->columns([
                // Effective date column — shows completed_at, falls back gracefully
                TextColumn::make('effective_date')
                    ->label('Completed Date')
                    ->getStateUsing(function ($record) use ($tz) {
                        $raw = $record->completed_at
                            ?? $record->updated_at
                            ?? $record->created_at;
                        return Carbon::parse($raw)->setTimezone($tz)->format('M d, Y');
                    })
                    ->description(function ($record) use ($tz) {
                        if ($record->payment_method !== 'laybuy') return null;
                        return 'Created: ' . Carbon::parse($record->created_at)
                            ->setTimezone($tz)->format('M d, Y');
                    })
                    ->sortable(query: fn(Builder $q, string $dir) =>
                        $q->orderByRaw(self::effectiveDateExpr() . " $dir")
                    ),

                TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->weight('bold')
                    ->color('primary')
                    ->searchable(),

                TextColumn::make('customer_name_display')
                    ->label('Customer')
                    ->getStateUsing(fn($record) => $record->customer
                        ? trim($record->customer->name . ' ' . ($record->customer->last_name ?? ''))
                        : '—'
                    )
                    ->searchable(query: fn(Builder $query, string $search) =>
                        $query->whereHas('customer', fn($q) =>
                            $q->where('name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%")
                              ->orWhereRaw("CONCAT(name, ' ', last_name) LIKE ?", ["%{$search}%"])
                        )
                    )
                    ->sortable(false),

                TextColumn::make('sales_person_list')
                    ->label('Associates')
                    ->badge()
                    ->separator(','),

                TextColumn::make('payment_method')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn($record) =>
                        $record->payment_method === 'laybuy' ? '⏳ LAYBUY' :
                        ($record->is_split_payment ? 'SPLIT' : strtoupper($record->payment_method ?? '—'))
                    )
                    ->color(fn($record) =>
                        $record->payment_method === 'laybuy' ? 'warning' :
                        ($record->is_split_payment ? 'info' : 'gray')
                    ),

                TextColumn::make('subtotal')
                    ->label('Subtotal (My Sale)')
                    ->alignRight()
                    ->formatStateUsing(function ($state, $record) {
                        $staffList = is_string($record->sales_person_list)
                            ? json_decode($record->sales_person_list, true)
                            : ($record->sales_person_list ?? []);
                        $count     = max(1, is_array($staffList) ? count($staffList) : 1);
                        $perPerson = floatval($state) / $count;

                        $html = "<div class='font-bold text-gray-900 text-lg'>$"
                            . number_format($perPerson, 2) . "</div>";

                        if ($count > 1) {
                            $html .= "<div class='text-xs text-gray-400'>Invoice Total: $"
                                . number_format($state, 2) . "</div>";
                            $html .= "<div class='text-[10px] text-primary-600 font-medium uppercase tracking-wider'>"
                                . "Split with {$count}</div>";
                        } else {
                            $html .= "<div class='text-[10px] text-gray-400 font-medium uppercase tracking-wider'>"
                                . "Full Sale Amount</div>";
                        }
                        return new HtmlString($html);
                    })
                    ->summarize(
                        Summarizer::make()
                            ->label('')
                            ->using(function ($query) {
                                $sales = $query->get(['subtotal', 'sales_person_list']);

                                $splitTotal = $splitTotalFull = $soloTotal = $myTotal = 0;

                                foreach ($sales as $sale) {
                                    $staffList = is_string($sale->sales_person_list)
                                        ? json_decode($sale->sales_person_list, true)
                                        : ($sale->sales_person_list ?? []);
                                    $count  = max(1, is_array($staffList) ? count($staffList) : 1);
                                    $share  = floatval($sale->subtotal) / $count;
                                    $myTotal += $share;

                                    if ($count > 1) {
                                        $splitTotal     += $share;
                                        $splitTotalFull += floatval($sale->subtotal);
                                    } else {
                                        $soloTotal += $share;
                                    }
                                }

                                return new HtmlString(
                                    "<div style='display:flex;gap:24px;justify-content:flex-end;align-items:center;flex-wrap:wrap;'>"
                                    . "<div style='text-align:right;'><div style='font-size:10px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;'>Split Sales</div><div style='font-size:16px;font-weight:900;color:#d97706;'>$" . number_format($splitTotal, 2) . "</div><div style='font-size:11px;font-weight:600;color:#00c0ff;'>Total: $" . number_format($splitTotalFull, 2) . "</div></div>"
                                    . "<div style='text-align:right;'><div style='font-size:10px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;'>Solo Sales</div><div style='font-size:16px;font-weight:900;color:#0284c7;'>$" . number_format($soloTotal, 2) . "</div></div>"
                                    . "<div style='text-align:right;padding:4px 10px;background:#f0fdf4;border-radius:8px;border:1px solid #bbf7d0;'><div style='font-size:10px;font-weight:700;color:#16a34a;text-transform:uppercase;letter-spacing:.05em;'>My Total</div><div style='font-size:18px;font-weight:900;color:#15803d;'>$" . number_format($myTotal, 2) . "</div></div>"
                                    . "</div>"
                                );
                            })
                    ),
            ])
            ->filters([
                // ── DATE FILTER ───────────────────────────────────────────────
                // Uses COALESCE(completed_at, updated_at, created_at) so rows
                // with NULL completed_at (old laybuy records) are never dropped.
                Filter::make('date_range')
                    ->label('Date Range')
                    ->form([
                        CustomDatePicker::make('from')
                            ->label('Start Date')
                            ->default(now()->startOfMonth()),
                        CustomDatePicker::make('until')
                            ->label('End Date')
                            ->default(now()),
                    ])
                    ->query(function (Builder $query, array $data) use ($tz): Builder {
                        $from  = !empty($data['from'])
                            ? Carbon::parse($data['from'], $tz)->startOfDay()->utc()->toDateTimeString()
                            : null;
                        $until = !empty($data['until'])
                            ? Carbon::parse($data['until'], $tz)->endOfDay()->utc()->toDateTimeString()
                            : null;

                        return $query
                            ->when($from,  fn($q) => $q->whereRaw(
                                self::effectiveDateExpr() . ' >= ?', [$from]
                            ))
                            ->when($until, fn($q) => $q->whereRaw(
                                self::effectiveDateExpr() . ' <= ?', [$until]
                            ));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (!empty($data['from']))  $indicators[] = 'From: '  . $data['from'];
                        if (!empty($data['until'])) $indicators[] = 'Until: ' . $data['until'];
                        return $indicators;
                    }),

                // ── STAFF FILTER ──────────────────────────────────────────────
                Filter::make('staff_filter')
                    ->label('Associate')
                    ->form([
                        Select::make('associate')
                            ->label('Sales Associate')
                            ->options(
                                $isPrivileged
                                    ? User::orderBy('name')->pluck('name', 'name')
                                    : []
                            )
                            ->placeholder('All Associates')
                            ->searchable(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!auth()->user()->hasAnyRole(['Superadmin', 'Administration'])) {
                            return $query;
                        }
                        return $query->when(
                            $data['associate'] ?? null,
                            fn(Builder $q, $name) =>
                                $q->whereJsonContains('sales_person_list', $name)
                        );
                    })
                    ->indicateUsing(fn(array $data) =>
                        !empty($data['associate']) ? ["Associate: {$data['associate']}"] : []
                    )
                    ->hidden(!$isPrivileged),
            ])
            ->defaultSort(
                fn(Builder $q) => $q->orderByRaw(self::effectiveDateExpr() . ' DESC')
            )
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->filtersTriggerAction(
                fn($action) => $action->label('Filter / Date Range')->icon('heroicon-o-funnel')
            );
    }

    public function getStats(): array
    {
        $isPrivileged = auth()->user()->hasAnyRole(['Superadmin', 'Administration']);
        $isSuperadmin = auth()->user()->hasRole('Superadmin');
        $tz           = Store::first()?->timezone ?? config('app.timezone', 'UTC');
        $currentUser  = auth()->user()->name;

        $filters       = $this->tableFilters ?? [];
        $filteredAssoc = $isPrivileged ? ($filters['staff_filter']['associate'] ?? null) : null;
        $viewingUser   = $filteredAssoc ?? $currentUser;

        $from  = !empty($filters['date_range']['from'])
            ? Carbon::parse($filters['date_range']['from'], $tz)->startOfDay()->utc()->toDateTimeString()
            : Carbon::now($tz)->startOfMonth()->utc()->toDateTimeString();

        $until = !empty($filters['date_range']['until'])
            ? Carbon::parse($filters['date_range']['until'], $tz)->endOfDay()->utc()->toDateTimeString()
            : Carbon::now($tz)->endOfDay()->utc()->toDateTimeString();

        $expr = self::effectiveDateExpr();

        // ── PERSONAL STATS ────────────────────────────────────────────────────
        $sales = Sale::query()
            ->where('status', 'completed')
            ->where('balance_due', 0)
            ->whereJsonContains('sales_person_list', $viewingUser)
            ->whereRaw("$expr >= ?", [$from])
            ->whereRaw("$expr <= ?", [$until])
            ->get(['subtotal', 'tax_amount', 'sales_person_list', 'payment_method']);

        $netShare = $laybuyCount = 0;
        foreach ($sales as $sale) {
            $staffList = is_string($sale->sales_person_list)
                ? json_decode($sale->sales_person_list, true)
                : ($sale->sales_person_list ?? []);
            $count     = max(1, is_array($staffList) ? count($staffList) : 1);
            $netShare += floatval($sale->subtotal) / $count;
            if ($sale->payment_method === 'laybuy') $laybuyCount++;
        }

        // ── STORE TOTAL ───────────────────────────────────────────────────────
        $storeTotal = null;
        if ($isSuperadmin) {
            $storeTotal = Sale::query()
                ->where('status', 'completed')
                ->where('balance_due', 0)
                ->whereRaw("$expr >= ?", [$from])
                ->whereRaw("$expr <= ?", [$until])
                ->sum('subtotal');
        }

        // ── STAFF LEADERBOARD ─────────────────────────────────────────────────
        $staffBreakdown = [];
        if ($isPrivileged) {
            $allSales = Sale::query()
                ->where('status', 'completed')
                ->where('balance_due', 0)
                ->whereRaw("$expr >= ?", [$from])
                ->whereRaw("$expr <= ?", [$until])
                ->get(['subtotal', 'sales_person_list']);

            foreach ($allSales as $sale) {
                $staffList = is_string($sale->sales_person_list)
                    ? json_decode($sale->sales_person_list, true)
                    : ($sale->sales_person_list ?? []);
                if (!is_array($staffList)) continue;
                $count = max(1, count($staffList));

                foreach ($staffList as $name) {
                    $name = trim($name);
                    if (!$name) continue;
                    $staffBreakdown[$name] = ($staffBreakdown[$name] ?? 0)
                        + floatval($sale->subtotal) / $count;
                }
            }
            arsort($staffBreakdown);
        }

        return [
            'count'           => $sales->count(),
            'net_share'       => $netShare,
            'tax'             => $sales->sum('tax_amount'),
            'store_total'     => $storeTotal,
            'laybuy_count'    => $laybuyCount,
            'is_privileged'   => $isPrivileged,
            'is_superadmin'   => $isSuperadmin,
            'user_name'       => $currentUser,
            'viewing_user'    => $viewingUser,
            'filtered_assoc'  => $filteredAssoc,
            'staff_breakdown' => $staffBreakdown,
        ];
    }
}