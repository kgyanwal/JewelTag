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
                    ->sortable(query: fn(Builder $query, string $direction) =>
                        $query->orderByRaw(self::effectiveDateExpr() . " {$direction}")
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

                TextColumn::make('pre_tax_total')
                    ->label('Pre-Tax Total (My Sale)')
                    ->alignRight()
                    ->getStateUsing(function ($record) {
                        return floatval($record->subtotal)
                             + floatval($record->warranty_charge ?? 0)
                             + floatval($record->shipping_charges ?? 0)
                             - floatval($record->trade_in_value ?? 0);
                    })
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
                                $sales = $query->get(['subtotal', 'warranty_charge', 'shipping_charges', 'trade_in_value', 'sales_person_list']);

                                $splitTotal = $splitTotalFull = $soloTotal = $myTotal = 0;

                                foreach ($sales as $sale) {
                                    $saleVolume = floatval($sale->subtotal)
                                                + floatval($sale->warranty_charge ?? 0)
                                                + floatval($sale->shipping_charges ?? 0)
                                                - floatval($sale->trade_in_value ?? 0);

                                    $staffList = is_string($sale->sales_person_list)
                                        ? json_decode($sale->sales_person_list, true)
                                        : ($sale->sales_person_list ?? []);
                                    $count  = max(1, is_array($staffList) ? count($staffList) : 1);
                                    $share  = $saleVolume / $count;
                                    $myTotal += $share;

                                    if ($count > 1) {
                                        $splitTotal     += $share;
                                        $splitTotalFull += $saleVolume;
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

                // ── STAFF FILTER: now supports selecting MULTIPLE associates ──
                Filter::make('staff_filter')
                    ->label('Associate')
                    ->form([
                        Select::make('associates')
                            ->label('Sales Associates')
                            ->options(
                                $isPrivileged
                                    ? User::orderBy('name')->pluck('name', 'name')
                                    : []
                            )
                            ->placeholder('All Associates')
                            ->searchable()
                            ->multiple()   // ← allows selecting 2+ people
                            ->preload(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!auth()->user()->hasAnyRole(['Superadmin', 'Administration'])) {
                            return $query;
                        }

                        $names = $data['associates'] ?? [];
                        if (empty($names)) return $query;

                        // Match sales where ANY of the selected names appears in sales_person_list
                        // For a single name: whereJsonContains
                        // For multiple names: wrap in where() with orWhereJsonContains for each
                        return $query->where(function (Builder $q) use ($names) {
                            foreach ($names as $name) {
                                $q->orWhereJsonContains('sales_person_list', $name);
                            }
                        });
                    })
                    ->indicateUsing(function (array $data): array {
                        $names = $data['associates'] ?? [];
                        if (empty($names)) return [];
                        return ['Associates: ' . implode(', ', $names)];
                    })
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

        $filters = $this->tableFilters ?? [];

        // ── Resolve which names are being filtered ────────────────────────────
        // New: 'associates' is an array (multi-select). Fall back to single
        // 'associate' key for backwards compat if someone had the old filter cached.
        $filteredAssocs = [];
        if ($isPrivileged) {
            $raw = $filters['staff_filter']['associates'] ?? $filters['staff_filter']['associate'] ?? null;
            if (is_array($raw) && !empty($raw)) {
                $filteredAssocs = array_values(array_filter($raw));
            } elseif (is_string($raw) && $raw !== '') {
                $filteredAssocs = [$raw];
            }
        }

        // For stat cards: if exactly one person is selected show their name,
        // if multiple show "X Associates", if none show current user (non-privileged) or "All Staff"
        $viewingLabel = match(true) {
            !$isPrivileged         => $currentUser,
            count($filteredAssocs) === 1 => $filteredAssocs[0],
            count($filteredAssocs) > 1   => count($filteredAssocs) . ' Associates',
            default                => null,   // All Staff
        };

        $from  = !empty($filters['date_range']['from'])
            ? Carbon::parse($filters['date_range']['from'], $tz)->startOfDay()->utc()->toDateTimeString()
            : Carbon::now($tz)->startOfMonth()->utc()->toDateTimeString();

        $until = !empty($filters['date_range']['until'])
            ? Carbon::parse($filters['date_range']['until'], $tz)->endOfDay()->utc()->toDateTimeString()
            : Carbon::now($tz)->endOfDay()->utc()->toDateTimeString();

        $expr = self::effectiveDateExpr();

        // ── BUILD BASE QUERY for personal/filtered stats ──────────────────────
        $statsQuery = Sale::query()
            ->where('status', 'completed')
            ->where('balance_due', 0)
            ->whereRaw("$expr >= ?", [$from])
            ->whereRaw("$expr <= ?", [$until]);

        if (!$isPrivileged) {
            // Regular staff see only their own sales
            $statsQuery->whereJsonContains('sales_person_list', $currentUser);
        } elseif (!empty($filteredAssocs)) {
            // Privileged + specific people selected: show sales where ANY of them appear
            $statsQuery->where(function (Builder $q) use ($filteredAssocs) {
                foreach ($filteredAssocs as $name) {
                    $q->orWhereJsonContains('sales_person_list', $name);
                }
            });
        }
        // else: privileged + no filter = all staff, no extra constraint

        $sales = $statsQuery->get([
            'subtotal', 'warranty_charge', 'shipping_charges',
            'trade_in_value', 'tax_amount', 'sales_person_list', 'payment_method',
        ]);

        // ── CALCULATE NET SHARE ───────────────────────────────────────────────
        // When multiple associates are filtered, we sum each person's individual
        // share (so if Nabin + Rabin are selected and they both appear on a $1000
        // sale split 2-ways, each contributed $500 → total shown = $1000).
        $netShare = $laybuyCount = 0;
        $targetNames = !$isPrivileged ? [$currentUser] : $filteredAssocs;

        foreach ($sales as $sale) {
            $saleVolume = floatval($sale->subtotal)
                        + floatval($sale->warranty_charge ?? 0)
                        + floatval($sale->shipping_charges ?? 0)
                        - floatval($sale->trade_in_value ?? 0);

            $staffList = is_string($sale->sales_person_list)
                ? json_decode($sale->sales_person_list, true)
                : ($sale->sales_person_list ?? []);
            if (!is_array($staffList)) $staffList = [];
            $count = max(1, count($staffList));
            $share = $saleVolume / $count;

            if (!empty($targetNames)) {
                // Count how many of the filtered names appear on this sale
                $matchCount = count(array_intersect($targetNames, $staffList));
                $netShare += $share * $matchCount;
            } else {
                // No filter = show per-person share (one slot)
                $netShare += $share;
            }

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
                ->selectRaw('SUM(subtotal + COALESCE(warranty_charge,0) + COALESCE(shipping_charges,0) - COALESCE(trade_in_value,0)) as total_volume')
                ->value('total_volume');
        }

        // ── STAFF LEADERBOARD ─────────────────────────────────────────────────
        $staffBreakdown = [];
        if ($isPrivileged) {
            $allSales = Sale::query()
                ->where('status', 'completed')
                ->where('balance_due', 0)
                ->whereRaw("$expr >= ?", [$from])
                ->whereRaw("$expr <= ?", [$until])
                ->get(['subtotal', 'warranty_charge', 'shipping_charges', 'trade_in_value', 'sales_person_list']);

            foreach ($allSales as $sale) {
                $saleVolume = floatval($sale->subtotal)
                            + floatval($sale->warranty_charge ?? 0)
                            + floatval($sale->shipping_charges ?? 0)
                            - floatval($sale->trade_in_value ?? 0);

                $staffList = is_string($sale->sales_person_list)
                    ? json_decode($sale->sales_person_list, true)
                    : ($sale->sales_person_list ?? []);
                if (!is_array($staffList)) continue;
                $count = max(1, count($staffList));

                foreach ($staffList as $name) {
                    $name = trim($name);
                    if (!$name) continue;
                    $staffBreakdown[$name] = ($staffBreakdown[$name] ?? 0) + ($saleVolume / $count);
                }
            }
            arsort($staffBreakdown);
        }

        return [
            'count'            => $sales->count(),
            'net_share'        => $netShare,
            'tax'              => $sales->sum('tax_amount'),
            'store_total'      => floatval($storeTotal),
            'laybuy_count'     => $laybuyCount,
            'is_privileged'    => $isPrivileged,
            'is_superadmin'    => $isSuperadmin,
            'user_name'        => $currentUser,
            'viewing_label'    => $viewingLabel,          // ← replaces old 'viewing_user'
            'filtered_assocs'  => $filteredAssocs,        // ← array, may be empty
            // keep old keys for blade compat
            'viewing_user'     => $viewingLabel,
            'filtered_assoc'   => count($filteredAssocs) === 1 ? $filteredAssocs[0] : null,
            'staff_breakdown'  => $staffBreakdown,
        ];
    }
}