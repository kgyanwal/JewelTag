<?php

namespace App\Filament\Pages;

use App\Models\Sale;
use App\Models\SaleItem;
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
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB;

class GrossMarginReport extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Analytics & Reports';
    protected static ?string $navigationLabel = 'Gross Margin';
    protected static ?string $title           = 'Gross Margin Report';
    protected static string  $view            = 'filament.pages.gross-margin-report';

    #[\Livewire\Attributes\Url(as: 'from')]
    public ?string $date_from = null;

    #[\Livewire\Attributes\Url(as: 'until')]
    public ?string $date_until = null;

    #[\Livewire\Attributes\Url(as: 'view')]
    public string $active_view = 'overview';

    public ?array $data = [];

    public function mount(): void
    {
        $this->data = [
            'date_from'  => $this->date_from  ?? now()->startOfMonth()->format('Y-m-d'),
            'date_until' => $this->date_until ?? now()->format('Y-m-d'),
        ];
        $this->form->fill($this->data);
    }

    public function updated($property): void
    {
        if (str_starts_with($property, 'data.')) {
            $this->date_from  = $this->data['date_from']  ?? null;
            $this->date_until = $this->data['date_until'] ?? null;
            $this->resetTable();
        }
    }

    public function setView(string $view): void
    {
        $this->active_view = $view;
        $this->resetTable();
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Section::make()
                    ->schema([
                        Grid::make(['default' => 1, 'md' => 3])->schema([
                            CustomDatePicker::make('date_from')
                                ->label('Start Date')
                                ->displayFormat('m/d/Y')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),

                            CustomDatePicker::make('date_until')
                                ->label('End Date')
                                ->displayFormat('m/d/Y')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),

                            \Filament\Forms\Components\Actions::make([
                                \Filament\Forms\Components\Actions\Action::make('reset')
                                    ->label('This Month')
                                    ->icon('heroicon-o-arrow-path')
                                    ->color('gray')
                                    ->outlined()
                                    ->action(function () {
                                        $this->date_from  = now()->startOfMonth()->format('Y-m-d');
                                        $this->date_until = now()->format('Y-m-d');
                                        $this->data = [
                                            'date_from'  => $this->date_from,
                                            'date_until' => $this->date_until,
                                        ];
                                        $this->form->fill($this->data);
                                        $this->resetTable();
                                    }),
                            ]),
                        ]),
                    ])
                    ->extraAttributes(['style' => 'overflow: visible; z-index: 40; position: relative;']),
            ]);
    }

    private function storeTimezone(): string
    {
        return Store::first()?->timezone ?? config('app.timezone', 'UTC');
    }

    private function dateRange(): array
    {
        $tz    = $this->storeTimezone();
        $from  = $this->data['date_from']  ?? $this->date_from  ?? now()->startOfMonth()->format('Y-m-d');
        $until = $this->data['date_until'] ?? $this->date_until ?? now()->format('Y-m-d');

        return [
            Carbon::parse($from, $tz)->startOfDay()->utc()->toDateTimeString(),
            Carbon::parse($until, $tz)->endOfDay()->utc()->toDateTimeString(),
        ];
    }

    // ── STATS ─────────────────────────────────────────────────────────────────
    public function getStats(): array
    {
        [$from, $until] = $this->dateRange();

        $rows = DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->whereNull('si.deleted_at')
            ->whereNull('s.deleted_at')
            ->where('s.status', 'completed')
            ->whereBetween('s.completed_at', [$from, $until])
            ->whereNull('si.repair_id')
            ->whereNull('si.custom_order_id')
            ->where('si.is_non_stock', 0)
            ->whereNotNull('si.product_item_id')
            ->select([
                DB::raw('SUM(COALESCE(si.sale_price_override, si.sold_price * si.qty)) as revenue'),
                DB::raw('SUM(si.cost_price * si.qty) as cost'),
                DB::raw('COUNT(DISTINCT s.id) as sale_count'),
                DB::raw('COUNT(si.id) as item_count'),
            ])
            ->first();

        $revenue = floatval($rows->revenue ?? 0);
        $cost    = floatval($rows->cost ?? 0);
        $margin  = $revenue - $cost;
        $pct     = $revenue > 0 ? round(($margin / $revenue) * 100, 1) : 0;

        $totalRevenue = DB::table('sales')
            ->whereNull('deleted_at')
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$from, $until])
            ->sum('subtotal');

        return [
            'revenue'             => $revenue,
            'cost'                => $cost,
            'margin'              => $margin,
            'margin_pct'          => $pct,
            'sale_count'          => intval($rows->sale_count ?? 0),
            'item_count'          => intval($rows->item_count ?? 0),
            'total_revenue'       => floatval($totalRevenue),
            'avg_margin_per_sale' => intval($rows->sale_count ?? 0) > 0
                ? $margin / intval($rows->sale_count)
                : 0,
        ];
    }

    // ── BY STAFF ──────────────────────────────────────────────────────────────
    public function getStaffBreakdown(): array
    {
        [$from, $until] = $this->dateRange();

        $rows = DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->whereNull('si.deleted_at')
            ->whereNull('s.deleted_at')
            ->where('s.status', 'completed')
            ->whereBetween('s.completed_at', [$from, $until])
            ->where('si.is_non_stock', 0)
            ->whereNotNull('si.product_item_id')
            ->select([
                's.sales_person_list',
                DB::raw('SUM(COALESCE(si.sale_price_override, si.sold_price * si.qty)) as revenue'),
                DB::raw('SUM(si.cost_price * si.qty) as cost'),
                DB::raw('COUNT(DISTINCT s.id) as sale_count'),
            ])
            ->groupBy('s.sales_person_list')
            ->orderByDesc('revenue')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $names = is_string($row->sales_person_list)
                ? (json_decode($row->sales_person_list, true) ?? array_map('trim', explode(',', $row->sales_person_list)))
                : [];
            $count = max(1, count($names));
            $rev   = floatval($row->revenue);
            $cost  = floatval($row->cost);

            foreach ($names as $name) {
                $name = trim($name);
                if (!$name) continue;
                if (!isset($result[$name])) {
                    $result[$name] = ['name' => $name, 'revenue' => 0, 'cost' => 0, 'sales' => 0];
                }
                $result[$name]['revenue'] += $rev / $count;
                $result[$name]['cost']    += $cost / $count;
                $result[$name]['sales']   += intval($row->sale_count) / $count;
            }
        }

        foreach ($result as &$r) {
            $r['margin'] = $r['revenue'] - $r['cost'];
            $r['pct']    = $r['revenue'] > 0 ? round(($r['margin'] / $r['revenue']) * 100, 1) : 0;
        }

        usort($result, fn($a, $b) => $b['margin'] <=> $a['margin']);
        return $result;
    }

    // ── BY CATEGORY ───────────────────────────────────────────────────────────
    public function getCategoryBreakdown(): array
    {
        [$from, $until] = $this->dateRange();

        // Use the full COALESCE expression in GROUP BY to satisfy MySQL's
        // only_full_group_by mode — grouping by alias is not allowed.
        $categoryExpr = 'COALESCE(NULLIF(pi.category, ""), NULLIF(pi.department, ""), "Uncategorized")';

        $rows = DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->join('product_items as pi', 'pi.id', '=', 'si.product_item_id')
            ->whereNull('si.deleted_at')
            ->whereNull('s.deleted_at')
            ->where('s.status', 'completed')
            ->whereBetween('s.completed_at', [$from, $until])
            ->where('si.is_non_stock', 0)
            ->whereNotNull('si.product_item_id')
            ->select([
                DB::raw("{$categoryExpr} as category"),
                DB::raw('SUM(COALESCE(si.sale_price_override, si.sold_price * si.qty)) as revenue'),
                DB::raw('SUM(si.cost_price * si.qty) as cost'),
                DB::raw('COUNT(si.id) as item_count'),
            ])
            ->groupBy(DB::raw($categoryExpr))
            ->orderByDesc('revenue')
            ->get()
            ->map(fn($r) => [
                'name'       => $r->category,
                'revenue'    => floatval($r->revenue),
                'cost'       => floatval($r->cost),
                'margin'     => floatval($r->revenue) - floatval($r->cost),
                'pct'        => floatval($r->revenue) > 0
                    ? round((floatval($r->revenue) - floatval($r->cost)) / floatval($r->revenue) * 100, 1)
                    : 0,
                'item_count' => intval($r->item_count),
            ])
            ->toArray();

        return $rows;
    }

    // ── BY VENDOR ─────────────────────────────────────────────────────────────
    public function getVendorBreakdown(): array
    {
        [$from, $until] = $this->dateRange();

        $rows = DB::table('sale_items as si')
            ->join('sales as s', 's.id', '=', 'si.sale_id')
            ->join('product_items as pi', 'pi.id', '=', 'si.product_item_id')
            ->join('suppliers as sup', 'sup.id', '=', 'pi.supplier_id')
            ->whereNull('si.deleted_at')
            ->whereNull('s.deleted_at')
            ->where('s.status', 'completed')
            ->whereBetween('s.completed_at', [$from, $until])
            ->where('si.is_non_stock', 0)
            ->whereNotNull('si.product_item_id')
            ->select([
                'sup.company_name as vendor',
                DB::raw('SUM(COALESCE(si.sale_price_override, si.sold_price * si.qty)) as revenue'),
                DB::raw('SUM(si.cost_price * si.qty) as cost'),
                DB::raw('COUNT(si.id) as item_count'),
            ])
            ->groupBy('sup.company_name')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn($r) => [
                'name'       => $r->vendor,
                'revenue'    => floatval($r->revenue),
                'cost'       => floatval($r->cost),
                'margin'     => floatval($r->revenue) - floatval($r->cost),
                'pct'        => floatval($r->revenue) > 0
                    ? round((floatval($r->revenue) - floatval($r->cost)) / floatval($r->revenue) * 100, 1)
                    : 0,
                'item_count' => intval($r->item_count),
            ])
            ->toArray();

        return $rows;
    }

    // ── TABLE QUERY ───────────────────────────────────────────────────────────
    protected function getTableQuery(): Builder
    {
        [$from, $until] = $this->dateRange();

        return Sale::query()
            ->with(['customer', 'items.productItem.supplier'])
            ->where('status', 'completed')
            ->whereBetween('completed_at', [$from, $until])
            ->whereNull('deleted_at')
            ->orderByDesc('completed_at');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('completed_at')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable()
                    ->size('sm'),

                TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->weight('bold')
                    ->color('primary')
                    ->searchable()
                    ->url(fn($record) => \App\Filament\Resources\SaleResource::getUrl('edit', ['record' => $record])),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->getStateUsing(fn($record) => $record->customer
                        ? trim($record->customer->name . ' ' . ($record->customer->last_name ?? ''))
                        : '—')
                    ->searchable(query: fn(Builder $q, string $search) =>
                        $q->whereHas('customer', fn($sq) =>
                            $sq->whereRaw("CONCAT(name,' ',COALESCE(last_name,'')) LIKE ?", ["%{$search}%"])
                        )
                    ),

                TextColumn::make('sales_person_list')
                    ->label('Staff')
                    ->badge()
                    ->separator(','),

                TextColumn::make('revenue')
                    ->label('Revenue')
                    ->getStateUsing(fn($record) =>
                        $record->items
                            ->whereNull('deleted_at')
                            ->where('is_non_stock', false)
                            ->whereNotNull('product_item_id')
                            ->sum(fn($i) => floatval($i->sale_price_override ?? ($i->sold_price * $i->qty)))
                    )
                    ->formatStateUsing(fn($state) => '$' . number_format($state, 2))
                    ->alignRight()
                    ->color('primary'),

                TextColumn::make('cost')
                    ->label('Cost')
                    ->getStateUsing(fn($record) =>
                        $record->items
                            ->whereNull('deleted_at')
                            ->where('is_non_stock', false)
                            ->whereNotNull('product_item_id')
                            ->sum(fn($i) => floatval($i->cost_price) * intval($i->qty))
                    )
                    ->formatStateUsing(fn($state) => '$' . number_format($state, 2))
                    ->alignRight()
                    ->color('gray'),

                TextColumn::make('margin')
                    ->label('Gross Margin')
                    ->getStateUsing(function ($record) {
                        $items = $record->items
                            ->whereNull('deleted_at')
                            ->where('is_non_stock', false)
                            ->whereNotNull('product_item_id');
                        $rev  = $items->sum(fn($i) => floatval($i->sale_price_override ?? ($i->sold_price * $i->qty)));
                        $cost = $items->sum(fn($i) => floatval($i->cost_price) * intval($i->qty));
                        return $rev - $cost;
                    })
                    ->formatStateUsing(function ($state) {
                        $color = $state >= 0 ? '#059669' : '#dc2626';
                        return new HtmlString(
                            "<span style='color:{$color};font-weight:800;'>$" . number_format($state, 2) . "</span>"
                        );
                    })
                    ->html()
                    ->alignRight(),

                TextColumn::make('margin_pct')
                    ->label('Margin %')
                    ->getStateUsing(function ($record) {
                        $items = $record->items
                            ->whereNull('deleted_at')
                            ->where('is_non_stock', false)
                            ->whereNotNull('product_item_id');
                        $rev  = $items->sum(fn($i) => floatval($i->sale_price_override ?? ($i->sold_price * $i->qty)));
                        $cost = $items->sum(fn($i) => floatval($i->cost_price) * intval($i->qty));
                        return $rev > 0 ? round((($rev - $cost) / $rev) * 100, 1) : 0;
                    })
                    ->formatStateUsing(function ($state) {
                        $color = $state >= 50 ? '#059669' : ($state >= 30 ? '#d97706' : '#dc2626');
                        $bg    = $state >= 50 ? '#ecfdf5' : ($state >= 30 ? '#fffbeb' : '#fef2f2');
                        return new HtmlString(
                            "<span style='background:{$bg};color:{$color};font-weight:700;font-size:11px;padding:3px 8px;border-radius:999px;'>{$state}%</span>"
                        );
                    })
                    ->html()
                    ->alignCenter(),
            ])
            ->defaultSort('completed_at', 'desc')
            ->searchable()
            ->striped()
            ->paginated([15, 25, 50])
            ->emptyStateHeading('No sales with margin data in this period');
    }
}