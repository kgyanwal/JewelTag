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
use App\Forms\Components\CustomDatePicker;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\{Grid, Section, Select};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB;

class MySalesReport extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationGroup = 'Analytics & Reports';
    protected static ?string $navigationLabel = 'My Sales Report';
    protected static ?string $title           = 'Sales Performance Report';
    protected static string  $view            = 'filament.pages.my-sales-report';

    // ── URL-bound filter state (persists in browser URL like ListRepairs) ──

    #[\Livewire\Attributes\Url(as: 'from')]
    public ?string $date_from = null;

    #[\Livewire\Attributes\Url(as: 'until')]
    public ?string $date_until = null;

    #[\Livewire\Attributes\Url(as: 'associates')]
    public ?array $associates = [];

    public ?array $data = [];

    public function mount(): void
    {
        $this->data = [
            'date_from'  => $this->date_from ?? now()->startOfMonth()->format('Y-m-d'),
            'date_until' => $this->date_until ?? now()->format('Y-m-d'),
            'associates' => $this->associates ?? [],
        ];

        $this->form->fill($this->data);
    }

    public function updated($property): void
    {
        if (str_starts_with($property, 'data.')) {
            $this->date_from  = $this->data['date_from']  ?? null;
            $this->date_until = $this->data['date_until'] ?? null;
            $this->associates = $this->data['associates'] ?? [];

            $this->resetTable();
        }
    }

    public function form(Form $form): Form
    {
        $isPrivileged = auth()->user()->hasAnyRole(['Superadmin', 'Administration']);

        return $form
            ->statePath('data')
            ->schema([
               Section::make('Filter Sales')
                    ->description('Filter by date range and sales associate')
                    ->schema([
                       Grid::make(['default' => 1, 'md' => 3])->schema([

                            CustomDatePicker::make('date_from')
                                ->label('Start Date')
                                ->displayFormat('m/d/Y')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable())
                                ->extraAttributes(['style' => 'height: 42px;']),

                            CustomDatePicker::make('date_until')
                                ->label('End Date')
                                ->displayFormat('m/d/Y')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable())
                                ->extraAttributes(['style' => 'height: 42px;']),

                            Select::make('associates')
                                ->label('Sales Associates')
                                ->options(
                                    $isPrivileged
                                        ? User::orderBy('name')->pluck('name', 'name')
                                        : []
                                )
                                ->placeholder('All Associates')
                                ->searchable()
                                ->multiple()
                                ->preload()
                                ->visible($isPrivileged)
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable())
                                ->native(false),
                        ])->columns(3),

                        \Filament\Forms\Components\Actions::make([
                            \Filament\Forms\Components\Actions\Action::make('reset_filters')
                                ->label('Clear Filters')
                                ->icon('heroicon-o-x-circle')
                                ->color('gray')
                                ->outlined()
                                ->action(function () {
                                    $this->date_from  = now()->startOfMonth()->format('Y-m-d');
                                    $this->date_until = now()->format('Y-m-d');
                                    $this->associates = [];

                                    $this->data = [
                                        'date_from'  => $this->date_from,
                                        'date_until' => $this->date_until,
                                        'associates' => [],
                                    ];

                                    $this->form->fill($this->data);
                                    $this->resetTable();
                                }),
                        ])->verticalAlignment(\Filament\Support\Enums\VerticalAlignment::Start),
                    ])
                    ->extraAttributes(['style' => 'position: relative; z-index: 40;']),
            ]);
    }

    // ─── SHARED HELPER ───────────────────────────────────────────────────────
    // Returns a SQL expression for the "effective date" of a sale, used for
    // both sorting and date-range filtering.
    //
    // Imported (OnSwim) invoices encode the REAL historical sale date right
    // inside the invoice number as 6 digits (MMDDYY) somewhere after a letter
    // prefix — e.g. D071623-23 means July 16, 2023. When these were imported,
    // the import process stamped completed_at/updated_at with the IMPORT date
    // (e.g. April 8), not the real sale date. So for any invoice number that
    // contains that 6-digit MMDDYY date pattern, we parse the date directly
    // out of the invoice number and IGNORE completed_at/updated_at entirely.
    //
    // The previous version anchored the regex to match from the very start
    // of the string only (^[A-Za-z][0-9]{6}-), which silently failed to match
    // any invoice number that didn't have EXACTLY one letter before the 6
    // digits, or that had something other than a single trailing dash right
    // after — e.g. extra prefix characters, a differently-placed dash, etc.
    // This version instead searches for the 6-digit date pattern ANYWHERE in
    // the invoice number (still requiring it to look like a valid MMDDYY
    // date — month 01-12, day 01-31), which is far more tolerant of minor
    // formatting differences across different import batches while still
    // refusing to misfire on a normal JewelTag-generated invoice number like
    // "D5230" (no 6-digit run present at all).
    private static function effectiveDateExpr(): string
    {
        return "
            CASE
                WHEN invoice_number REGEXP '[0-9]{6}'
                     AND CAST(SUBSTRING(
                            REGEXP_SUBSTR(invoice_number, '[0-9]{6}'), 1, 2
                         ) AS UNSIGNED) BETWEEN 1 AND 12
                     AND CAST(SUBSTRING(
                            REGEXP_SUBSTR(invoice_number, '[0-9]{6}'), 3, 2
                         ) AS UNSIGNED) BETWEEN 1 AND 31
                THEN STR_TO_DATE(REGEXP_SUBSTR(invoice_number, '[0-9]{6}'), '%m%d%y')
                ELSE COALESCE(completed_at, updated_at, created_at)
            END
        ";
    }

    // ── Resolve the store timezone once, consistently, everywhere on this page ──
    private static function storeTimezone(): string
    {
        return Store::first()?->timezone ?? config('app.timezone', 'UTC');
    }

    // ── Convert a local calendar date (Y-m-d string) into UTC start-of-day ──
    private static function localStartOfDayUtc(string $date, string $tz): string
    {
        return Carbon::parse($date, $tz)->startOfDay()->utc()->toDateTimeString();
    }

    // ── Convert a local calendar date (Y-m-d string) into UTC end-of-day ──
    private static function localEndOfDayUtc(string $date, string $tz): string
    {
        return Carbon::parse($date, $tz)->endOfDay()->utc()->toDateTimeString();
    }

    public function table(Table $table): Table
    {
        $isPrivileged = auth()->user()->hasAnyRole(['Superadmin', 'Administration']);
        $tz           = self::storeTimezone();

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
                        // Mirror the SQL CASE logic in PHP so the displayed
                        // date always matches what the filter/sort used.
                        if (preg_match('/(\d{6})/', $record->invoice_number, $m)) {
                            $digits = $m[1];
                            $month  = (int) substr($digits, 0, 2);
                            $day    = (int) substr($digits, 2, 2);
                            if ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
                                try {
                                    return Carbon::createFromFormat('mdy', $digits)->format('M d, Y');
                                } catch (\Exception $e) {
                                    // fall through to completed_at below
                                }
                            }
                        }

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
            ->defaultSort('effective_date', 'desc');
    }

    // ── Override the table query to apply our custom Blade-bound filters ──
    protected function getTableQuery(): Builder
    {
        $isPrivileged = auth()->user()->hasAnyRole(['Superadmin', 'Administration']);
        $tz           = self::storeTimezone();
        $expr         = self::effectiveDateExpr();

        $query = Sale::query()
            ->with('customer')
            ->where('status', 'completed')
            ->where('balance_due', 0);

        if (!$isPrivileged) {
            $query->whereJsonContains('sales_person_list', auth()->user()->name);
        }

        $from  = $this->data['date_from']  ?? $this->date_from  ?? null;
        $until = $this->data['date_until'] ?? $this->date_until ?? null;

        if ($from) {
            $query->whereRaw("$expr >= ?", [self::localStartOfDayUtc($from, $tz)]);
        }

        if ($until) {
            $query->whereRaw("$expr <= ?", [self::localEndOfDayUtc($until, $tz)]);
        }

        if ($isPrivileged) {
            $names = $this->data['associates'] ?? $this->associates ?? [];
            if (!empty($names)) {
                $query->where(function (Builder $q) use ($names) {
                    foreach ($names as $name) {
                        $q->orWhereJsonContains('sales_person_list', $name);
                    }
                });
            }
        }

     $query->orderByRaw(self::effectiveDateExpr() . ' DESC')
              ->orderBy('created_at', 'desc')
              ->orderBy('id', 'desc');

        return $query;
    }

    public function getStats(): array
    {
        $isPrivileged = auth()->user()->hasAnyRole(['Superadmin', 'Administration']);
        $isSuperadmin = auth()->user()->hasRole('Superadmin');
        $tz           = self::storeTimezone();
        $currentUser  = auth()->user()->name;

        $filteredAssocs = [];
        if ($isPrivileged) {
            $raw = $this->data['associates'] ?? $this->associates ?? [];
            if (is_array($raw) && !empty($raw)) {
                $filteredAssocs = array_values(array_filter($raw));
            } elseif (is_string($raw) && $raw !== '') {
                $filteredAssocs = [$raw];
            }
        }

        $viewingLabel = match(true) {
            !$isPrivileged         => $currentUser,
            count($filteredAssocs) === 1 => $filteredAssocs[0],
            count($filteredAssocs) > 1   => count($filteredAssocs) . ' Associates',
            default                => null,
        };

        $fromDate  = $this->data['date_from']  ?? $this->date_from  ?? now($tz)->startOfMonth()->format('Y-m-d');
        $untilDate = $this->data['date_until'] ?? $this->date_until ?? now($tz)->format('Y-m-d');

        $from  = self::localStartOfDayUtc($fromDate, $tz);
        $until = self::localEndOfDayUtc($untilDate, $tz);

        $expr = self::effectiveDateExpr();

        $statsQuery = Sale::query()
            ->where('status', 'completed')
            ->where('balance_due', 0)
            ->whereRaw("$expr >= ?", [$from])
            ->whereRaw("$expr <= ?", [$until]);

        if (!$isPrivileged) {
            $statsQuery->whereJsonContains('sales_person_list', $currentUser);
        } elseif (!empty($filteredAssocs)) {
            $statsQuery->where(function (Builder $q) use ($filteredAssocs) {
                foreach ($filteredAssocs as $name) {
                    $q->orWhereJsonContains('sales_person_list', $name);
                }
            });
        }

        $sales = $statsQuery->get([
            'subtotal', 'warranty_charge', 'shipping_charges',
            'trade_in_value', 'tax_amount', 'sales_person_list', 'payment_method',
        ]);

        $netShare = $laybuyCount = 0;
        $targetNames = !$isPrivileged ? [$currentUser] : $filteredAssocs;

        foreach ($sales as $sale) {
            $saleVolume = floatval($sale->subtotal)
                        
                        + floatval($sale->shipping_charges ?? 0)
                        - floatval($sale->trade_in_value ?? 0);

            $staffList = is_string($sale->sales_person_list)
                ? json_decode($sale->sales_person_list, true)
                : ($sale->sales_person_list ?? []);
            if (!is_array($staffList)) $staffList = [];
            $count = max(1, count($staffList));
            $share = $saleVolume / $count;

            if (!empty($targetNames)) {
                $matchCount = count(array_intersect($targetNames, $staffList));
                $netShare += $share * $matchCount;
            } else {
                $netShare += $share;
            }

            if ($sale->payment_method === 'laybuy') $laybuyCount++;
        }

        $storeTotal = null;
        if ($isSuperadmin) {
            $storeTotal = Sale::query()
                ->where('status', 'completed')
                ->where('balance_due', 0)
                ->whereRaw("$expr >= ?", [$from])
                ->whereRaw("$expr <= ?", [$until])
               ->selectRaw('SUM(subtotal + COALESCE(shipping_charges,0) - COALESCE(trade_in_value,0)) as total_volume')
                ->value('total_volume');
        }

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
            'viewing_label'    => $viewingLabel,
            'filtered_assocs'  => $filteredAssocs,
            'viewing_user'     => $viewingLabel,
            'filtered_assoc'   => count($filteredAssocs) === 1 ? $filteredAssocs[0] : null,
            'staff_breakdown'  => $staffBreakdown,
        ];
    }
}