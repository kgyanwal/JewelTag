<?php

namespace App\Filament\Pages;

use App\Models\Sale;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB;
use App\Forms\Components\CustomDatePicker;

class WarrantyReport extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon  = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Warranty Report';
    protected static ?string $navigationGroup = 'Analytics & Reports';
    protected static ?string $title           = 'Warranty Sales Report';
    protected static string  $view            = 'filament.pages.warranty-report';

    // ── State ─────────────────────────────────────────────────────
    public ?float  $warranty_goal = null;
    public ?string $date_from     = null;
    public ?string $date_to       = null;
    public ?string $period        = 'this_month';
    public ?string $sales_person  = null;
    public ?array  $data          = [];

    public function mount(): void
    {
        $savedGoal           = DB::table('site_settings')->where('key', 'warranty_goal')->value('value');
        $this->warranty_goal = floatval($savedGoal ?? 0) ?: null;

        // Apply the default period first so we have dates
        $this->applyPeriodDates('this_month');

        $this->data = [
            'date_from'     => $this->date_from,
            'date_to'       => $this->date_to,
            'period'        => 'this_month',
            'sales_person'  => null,
            'warranty_goal' => $this->warranty_goal,
        ];

        $this->form->fill($this->data);
    }

    public function updated($property): void
    {
        if (str_starts_with($property, 'data.')) {
            $this->sales_person  = $this->data['sales_person'] ?? null;
            $this->warranty_goal = floatval($this->data['warranty_goal'] ?? 0) ?: null;

            $newPeriod = $this->data['period'] ?? null;

            if ($newPeriod && $newPeriod !== 'custom') {
                // Preset selected — compute dates and push back into data
                $this->applyPeriodDates($newPeriod);
                $this->period = $newPeriod;
                $this->data['date_from'] = $this->date_from;
                $this->data['date_to']   = $this->date_to;
            } else {
                // Custom or no preset — read dates directly from the form
                $this->date_from = $this->data['date_from'] ?? null;
                $this->date_to   = $this->data['date_to']   ?? null;
                $this->period    = $newPeriod;
            }

            $this->resetTable();
        }
    }

    /**
     * Compute start/end dates for a preset and store them on $this->date_from / $this->date_to.
     * Does NOT touch $this->data — caller must sync if needed.
     */
    private function applyPeriodDates(string $period): void
    {
        $now = now();
        switch ($period) {
            case 'today':
                $this->date_from = $now->copy()->toDateString();
                $this->date_to   = $now->copy()->toDateString();
                break;
            case 'this_week':
                $this->date_from = $now->copy()->startOfWeek()->toDateString();
                $this->date_to   = $now->copy()->endOfWeek()->toDateString();
                break;
            case 'this_month':
                $this->date_from = $now->copy()->startOfMonth()->toDateString();
                $this->date_to   = $now->copy()->endOfMonth()->toDateString();
                break;
            case 'last_month':
                $this->date_from = $now->copy()->subMonth()->startOfMonth()->toDateString();
                $this->date_to   = $now->copy()->subMonth()->endOfMonth()->toDateString();
                break;
            case 'this_year':
                $this->date_from = $now->copy()->startOfYear()->toDateString();
                $this->date_to   = $now->copy()->endOfYear()->toDateString();
                break;
        }
    }

    private function getWarrantyQuery(): Builder
    {
        return Sale::query()
            ->where('has_warranty', 1)
            ->whereNotIn('status', ['cancelled', 'void', 'refunded'])
            ->when($this->date_from,    fn($q) => $q->whereDate('created_at', '>=', $this->date_from))
            ->when($this->date_to,      fn($q) => $q->whereDate('created_at', '<=', $this->date_to))
            ->when($this->sales_person, fn($q, $v) => $q->where('sales_person_list', 'like', "%{$v}%"));
    }

    public function getStatsHtml(): string
    {
        $query       = $this->getWarrantyQuery();
        $totalSales  = (clone $query)->count();
        $totalCharge = (clone $query)->sum('warranty_charge');
        $avgCharge   = $totalSales > 0 ? $totalCharge / $totalSales : 0;

        $byStaff = (clone $query)
            ->selectRaw("sales_person_list, COUNT(*) as count, SUM(warranty_charge) as total")
            ->groupBy('sales_person_list')
            ->orderByDesc('total')
            ->get();

        $byMonth = (clone $query)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count, SUM(warranty_charge) as total")
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(6)
            ->get();

        $byDuration = (clone $query)
            ->selectRaw("warranty_period, COUNT(*) as count, SUM(warranty_charge) as total")
            ->groupBy('warranty_period')
            ->orderByDesc('count')
            ->get();

        // ── Goal bar ──────────────────────────────────────────────
        $goal      = floatval($this->warranty_goal ?? 0);
        $pct       = $goal > 0 ? min(100, round(($totalCharge / $goal) * 100)) : 0;
        $barColor  = $pct >= 100 ? '#16a34a' : ($pct >= 60 ? '#d97706' : '#dc2626');
        $barBg     = $pct >= 100 ? '#f0fdf4'  : ($pct >= 60 ? '#fffbeb'  : '#fef2f2');
        $barBorder = $pct >= 100 ? '#86efac'  : ($pct >= 60 ? '#fcd34d'  : '#fca5a5');

        $goalHtml = '';
        if ($goal > 0) {
            $remaining = max(0, $goal - $totalCharge);
            $statusMsg = $pct >= 100
                ? "🎉 Goal achieved! Over by \$" . number_format($totalCharge - $goal, 2)
                : "\$" . number_format($remaining, 2) . " more needed to reach goal";

            $goalHtml = "
            <div style='background:{$barBg};border:1px solid {$barBorder};border-radius:12px;padding:16px;margin-bottom:20px;'>
                <div style='display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;'>
                    <div>
                        <span style='font-size:13px;font-weight:900;color:#1e293b;'>🎯 Warranty Revenue Goal</span>
                        <div style='font-size:11px;color:#64748b;margin-top:2px;'>{$statusMsg}</div>
                    </div>
                    <span style='font-size:22px;font-weight:900;color:{$barColor};'>{$pct}%</span>
                </div>
                <div style='background:#e2e8f0;border-radius:99px;height:14px;overflow:hidden;'>
                    <div style='background:{$barColor};height:100%;width:{$pct}%;border-radius:99px;'></div>
                </div>
                <div style='display:flex;justify-content:space-between;margin-top:6px;'>
                    <span style='font-size:11px;color:#64748b;'>\$0</span>
                    <span style='font-size:11px;font-weight:700;color:{$barColor};'>\$" . number_format($totalCharge, 2) . " earned</span>
                    <span style='font-size:11px;color:#64748b;'>Goal: \$" . number_format($goal, 2) . "</span>
                </div>
            </div>";
        }

        // ── 3 stat cards ──────────────────────────────────────────
        $cards = "
        <div style='display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px;'>
            <div style='background:linear-gradient(135deg,#ecfdf5,#d1fae5);border:1px solid #6ee7b7;border-radius:12px;padding:20px;text-align:center;'>
                <div style='font-size:10px;font-weight:900;color:#059669;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;'>Total Warranty Revenue</div>
                <div style='font-size:2rem;font-weight:900;color:#065f46;'>\$" . number_format($totalCharge, 2) . "</div>
            </div>
            <div style='background:linear-gradient(135deg,#eff6ff,#dbeafe);border:1px solid #93c5fd;border-radius:12px;padding:20px;text-align:center;'>
                <div style='font-size:10px;font-weight:900;color:#1d4ed8;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;'>Total Warranty Sales</div>
                <div style='font-size:2rem;font-weight:900;color:#1e3a8a;'>{$totalSales}</div>
            </div>
            <div style='background:linear-gradient(135deg,#fdf4ff,#f3e8ff);border:1px solid #d8b4fe;border-radius:12px;padding:20px;text-align:center;'>
                <div style='font-size:10px;font-weight:900;color:#7c3aed;text-transform:uppercase;letter-spacing:.08em;margin-bottom:8px;'>Avg Warranty Charge</div>
                <div style='font-size:2rem;font-weight:900;color:#4c1d95;'>\$" . number_format($avgCharge, 2) . "</div>
            </div>
        </div>";

        // ── Staff leaderboard ─────────────────────────────────────
        $staffRows = '';
        $rank = 1;
        foreach ($byStaff as $row) {
            $rawList = $row->sales_person_list ?? '—';
            $names   = is_array($rawList)
                ? implode(', ', $rawList)
                : implode(', ', array_map('trim', explode(',', $rawList)));
            $medal   = match ($rank) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => "#{$rank}" };
            $staffRows .= "
            <div style='display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f1f5f9;'>
                <div style='display:flex;align-items:center;gap:10px;'>
                    <span style='font-size:18px;'>{$medal}</span>
                    <div>
                        <div style='font-size:13px;font-weight:700;color:#334155;'>{$names}</div>
                        <div style='font-size:11px;color:#94a3b8;'>{$row->count} warranties</div>
                    </div>
                </div>
                <span style='font-size:14px;font-weight:900;color:#059669;'>\$" . number_format($row->total, 2) . "</span>
            </div>";
            $rank++;
        }

        // ── Monthly trend ─────────────────────────────────────────
        $trendRows = '';
        foreach ($byMonth as $row) {
            $trendRows .= "
            <div style='display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f1f5f9;'>
                <span style='font-size:13px;color:#475569;font-weight:600;'>{$row->month}</span>
                <div style='display:flex;gap:16px;'>
                    <span style='font-size:12px;color:#64748b;'>{$row->count} sales</span>
                    <span style='font-size:13px;font-weight:800;color:#059669;'>\$" . number_format($row->total, 2) . "</span>
                </div>
            </div>";
        }

        // ── Warranty duration ────────────────────────────────────
        $durationRows = '';
        foreach ($byDuration as $row) {
            $label = $row->warranty_period ?: '(Not Set)';
            $durationRows .= "
            <div style='display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f1f5f9;'>
                <span style='font-size:13px;color:#475569;font-weight:600;'>{$label}</span>
                <div style='display:flex;gap:16px;'>
                    <span style='font-size:12px;color:#64748b;'>{$row->count} sold</span>
                    <span style='font-size:13px;font-weight:800;color:#059669;'>\$" . number_format($row->total, 2) . "</span>
                </div>
            </div>";
        }

        $noData = "<div style='color:#94a3b8;font-style:italic;text-align:center;padding:16px;'>No data for this period</div>";

        $grid = "
        <div style='display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;'>
            <div style='background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;'>
                <div style='font-size:11px;font-weight:900;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px;'>🏆 Staff Leaderboard</div>
                " . ($staffRows ?: $noData) . "
            </div>
            <div style='background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;'>
                <div style='font-size:11px;font-weight:900;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px;'>📅 Monthly Trend (Last 6)</div>
                " . ($trendRows ?: $noData) . "
            </div>
            <div style='background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;'>
                <div style='font-size:11px;font-weight:900;color:#64748b;text-transform:uppercase;letter-spacing:.05em;margin-bottom:12px;'>🛡️ By Warranty Duration</div>
                " . ($durationRows ?: $noData) . "
            </div>
        </div>";

        return $goalHtml . $cards . $grid;
    }

    public function form(Form $form): Form
    {
        $isAdmin = auth()->user()?->hasAnyRole(['Superadmin', 'Administration']);

        return $form
            ->statePath('data')
            ->schema([
                Section::make()->compact()->schema([
                    Grid::make(6)->schema([
                        Select::make('period')
                            ->label('Quick Period')
                            ->options([
                                'today'      => 'Today',
                                'this_week'  => 'This Week',
                                'this_month' => 'This Month',
                                'last_month' => 'Last Month',
                                'this_year'  => 'This Year',
                                'custom'     => 'Custom Range',
                            ])
                            ->default('this_month')
                            ->live()
                            ->afterStateUpdated(function ($state, \Filament\Forms\Set $set) {
                                if ($state && $state !== 'custom') {
                                    $this->applyPeriodDates($state);
                                    // Push computed dates back into the form fields
                                    $set('date_from', $this->date_from);
                                    $set('date_to',   $this->date_to);
                                    $this->data['date_from'] = $this->date_from;
                                    $this->data['date_to']   = $this->date_to;
                                }
                                $this->period = $state;
                                $this->resetTable();
                            }),

                        CustomDatePicker::make('date_from')
                            ->label('From')
                            ->displayFormat('m/d/Y')
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->date_from = $state;
                                $this->period    = 'custom';
                                $this->data['period'] = 'custom';
                                $this->resetTable();
                            }),

                        CustomDatePicker::make('date_to')
                            ->label('To')
                            ->displayFormat('m/d/Y')
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->date_to = $state;
                                $this->period  = 'custom';
                                $this->data['period'] = 'custom';
                                $this->resetTable();
                            }),

                        Select::make('sales_person')
                            ->label('Filter by Staff')
                            ->options(fn() => \App\Models\User::orderBy('name')->pluck('name', 'name')->toArray())
                            ->searchable()
                            ->placeholder('All Staff')
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->sales_person = $state;
                                $this->resetTable();
                            }),

                        TextInput::make('warranty_goal')
                            ->label($isAdmin ? '🎯 Set Revenue Goal ($)' : '🎯 Revenue Goal')
                            ->numeric()
                            ->prefix('$')
                            ->placeholder($isAdmin ? 'e.g. 500' : 'Set by admin')
                            ->default(fn() => DB::table('site_settings')->where('key', 'warranty_goal')->value('value'))
                            ->readOnly(!$isAdmin)
                            ->helperText($isAdmin ? 'Saved globally for all staff' : 'Only admins can change this')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state) use ($isAdmin) {
                                if (!$isAdmin) return;
                                DB::table('site_settings')->updateOrInsert(
                                    ['key' => 'warranty_goal'],
                                    ['value' => floatval($state ?? 0)]
                                );
                                $this->warranty_goal = floatval($state ?? 0) ?: null;
                                $this->resetTable();
                            }),

                        \Filament\Forms\Components\Actions::make([
                            \Filament\Forms\Components\Actions\Action::make('reset')
                                ->label('Reset')
                                ->icon('heroicon-o-x-circle')
                                ->color('gray')
                                ->outlined()
                                ->action(function (\Filament\Forms\Set $set) {
                                    $savedGoal           = DB::table('site_settings')->where('key', 'warranty_goal')->value('value');
                                    $this->warranty_goal = floatval($savedGoal ?? 0) ?: null;
                                    $this->sales_person  = null;
                                    $this->period        = 'this_month';

                                    $this->applyPeriodDates('this_month');

                                    $this->data = [
                                        'date_from'     => $this->date_from,
                                        'date_to'       => $this->date_to,
                                        'period'        => 'this_month',
                                        'sales_person'  => null,
                                        'warranty_goal' => $this->warranty_goal,
                                    ];

                                    $this->form->fill($this->data);
                                    $this->resetTable();
                                }),
                        ]),
                    ]),
                ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getWarrantyQuery()->with(['customer', 'items']))
            ->columns([
                TextColumn::make('created_at')
                    ->label('DATE')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->size('sm'),

                TextColumn::make('invoice_number')
                    ->label('INVOICE #')
                    ->weight('bold')
                    ->color('primary')
                    ->searchable()
                    ->copyable()
                    ->url(fn($record) => \App\Filament\Resources\SaleResource::getUrl('edit', ['record' => $record])),

                TextColumn::make('customer_name')
                    ->label('CUSTOMER')
                    ->getStateUsing(fn($record) => $record->customer
                        ? trim($record->customer->name . ' ' . ($record->customer->last_name ?? ''))
                        : '—')
                    ->searchable(query: fn(Builder $q, string $search) =>
                        $q->whereHas('customer', fn($sq) =>
                            $sq->whereRaw("CONCAT(name,' ',COALESCE(last_name,'')) LIKE ?", ["%{$search}%"])
                        )
                    ),

                TextColumn::make('sales_person_list')
                    ->label('STAFF')
                    ->badge()
                    ->separator(',')
                    ->color('gray'),

                TextColumn::make('warranty_period')
                    ->label('WARRANTY')
                    ->badge()
                    ->color('success'),

                TextColumn::make('warranty_charge')
                    ->label('WARRANTY CHARGE')
                    ->money('USD')
                    ->sortable()
                    ->weight('black')
                    ->color('success'),

                TextColumn::make('is_warranty_taxed')
                    ->label('TAXED?')
                    ->formatStateUsing(fn($state) => $state ? '✅ Yes' : '—')
                    ->alignCenter(),

                TextColumn::make('final_total')
                    ->label('SALE TOTAL')
                    ->money('USD')
                    ->sortable()
                    ->color('gray'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'completed'          => 'success',
                        'pending'            => 'warning',
                        'partially_refunded' => 'warning',
                        default              => 'gray',
                    })
                    ->formatStateUsing(fn($state) => ucfirst(str_replace('_', ' ', $state))),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchable()
            ->striped()
            ->paginated([15, 25, 50])
            ->emptyStateHeading('No warranty sales in this period')
            ->emptyStateDescription('Try adjusting the date range, period, or staff filter.');
    }
}