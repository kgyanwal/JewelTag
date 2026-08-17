<?php

namespace App\Filament\Pages;

use App\Models\Sale;
use App\Models\User;
use App\Models\Store;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\{Grid, Section, Select};
use App\Forms\Components\CustomDatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class StaffPerformanceDashboard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Analytics & Reports';
    protected static ?string $navigationLabel = 'Staff Performance';
    protected static ?string $title           = 'Staff Performance Dashboard';
    protected static string  $view            = 'filament.pages.staff-performance-dashboard';

    public ?string $period    = 'this_month';
    public ?string $date_from = null;
    public ?string $date_to   = null;
    public ?array  $data      = [];

    public function mount(): void
    {
        $this->data = [
            'period'    => 'this_month',
            'date_from' => null,
            'date_to'   => null,
        ];
        $this->form->fill($this->data);
    }

    public function updated($property): void
    {
        if (str_starts_with($property, 'data.')) {
            $this->period    = $this->data['period']    ?? 'this_month';
            $this->date_from = $this->data['date_from'] ?? null;
            $this->date_to   = $this->data['date_to']   ?? null;
        }
    }

    public function form(Form $form): Form
    {
        return $form->statePath('data')->schema([
            Section::make()->schema([
                Grid::make(4)->schema([
                    Select::make('period')
                        ->label('Period')
                        ->options([
                            'today'        => 'Today',
                            'yesterday'    => 'Yesterday',
                            'this_week'    => 'This Week',
                            'last_week'    => 'Last Week',
                            'this_month'   => 'This Month',
                            'last_month'   => 'Last Month',
                            'this_quarter' => 'This Quarter',
                            'this_year'    => 'This Year',
                            'custom'       => 'Custom Range',
                        ])
                        ->default('this_month')
                        ->live()
                        ->native(false)
                        ->afterStateUpdated(fn() => null),

                    CustomDatePicker::make('date_from')
                        ->label('From')
                        ->displayFormat('m/d/Y')
                        ->visible(fn(\Filament\Forms\Get $get) => $get('period') === 'custom')
                        ->live(),

                    CustomDatePicker::make('date_to')
                        ->label('To')
                        ->displayFormat('m/d/Y')
                        ->visible(fn(\Filament\Forms\Get $get) => $get('period') === 'custom')
                        ->live(),
                ]),
            ])->extraAttributes(['style' => 'z-index:40;position:relative;']),
        ]);
    }

    private function getDateRange(): array
    {
        $tz   = Store::first()?->timezone ?? config('app.timezone', 'UTC');
        $now  = Carbon::now($tz);
        $period = $this->data['period'] ?? $this->period ?? 'this_month';

        return match($period) {
            'today'        => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday'    => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'this_week'    => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'last_week'    => [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()],
            'this_month'   => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month'   => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            'this_quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()],
            'this_year'    => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'custom'       => [
                Carbon::parse($this->data['date_from'] ?? $now->format('Y-m-d'), $tz)->startOfDay(),
                Carbon::parse($this->data['date_to']   ?? $now->format('Y-m-d'), $tz)->endOfDay(),
            ],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    public function getDashboardData(): array
    {
        [$from, $to] = $this->getDateRange();

        // All completed sales in range
        $sales = Sale::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from->utc(), $to->utc()])
            ->get(['id','subtotal','tax_amount','final_total','sales_person_list',
                   'payment_method','is_split_payment','created_at','balance_due',
                   'shipping_charges','trade_in_value','warranty_charge']);

        // Build per-staff breakdown
        $staffData = [];
        $paymentBreakdown = [];
        $dailySales = [];

        foreach ($sales as $sale) {
            $staffList = is_string($sale->sales_person_list)
                ? json_decode($sale->sales_person_list, true)
                : ($sale->sales_person_list ?? []);
            if (!is_array($staffList)) $staffList = [];
            $count = max(1, count($staffList));

            $saleVolume = floatval($sale->subtotal)
                        + floatval($sale->shipping_charges ?? 0)
                        - floatval($sale->trade_in_value ?? 0);

            $share = $saleVolume / $count;

            foreach ($staffList as $name) {
                $name = trim($name);
                if (!$name) continue;
                if (!isset($staffData[$name])) {
                    $staffData[$name] = [
                        'name'         => $name,
                        'sales_count'  => 0,
                        'total'        => 0,
                        'tax'          => 0,
                        'avg_sale'     => 0,
                        'solo_count'   => 0,
                        'split_count'  => 0,
                        'methods'      => [],
                        'daily'        => [],
                    ];
                }
                $staffData[$name]['sales_count']++;
                $staffData[$name]['total']        += $share;
                $staffData[$name]['tax']          += floatval($sale->tax_amount) / $count;

                if ($count === 1) $staffData[$name]['solo_count']++;
                else              $staffData[$name]['split_count']++;

                $method = strtoupper($sale->payment_method ?? 'OTHER');
                $staffData[$name]['methods'][$method] = ($staffData[$name]['methods'][$method] ?? 0) + 1;

                // Daily tracking
                $day = Carbon::parse($sale->created_at)->format('Y-m-d');
                $staffData[$name]['daily'][$day] = ($staffData[$name]['daily'][$day] ?? 0) + $share;
            }

            // Payment method breakdown (store-wide)
            $method = strtoupper($sale->payment_method ?? 'OTHER');
            $paymentBreakdown[$method] = ($paymentBreakdown[$method] ?? 0) + 1;

            // Daily sales for trend chart
            $day = Carbon::parse($sale->created_at)->format('M d');
            $dailySales[$day] = ($dailySales[$day] ?? 0) + floatval($sale->final_total);
        }

        // Calculate averages
        foreach ($staffData as $name => &$d) {
            $d['avg_sale'] = $d['sales_count'] > 0
                ? round($d['total'] / $d['sales_count'], 2)
                : 0;
        }
        unset($d);

        // Sort by total descending
        uasort($staffData, fn($a, $b) => $b['total'] <=> $a['total']);

        // Previous period comparison
        $prevDiff = $to->diffInDays($from);
        $prevFrom = $from->copy()->subDays($prevDiff + 1)->startOfDay();
        $prevTo   = $from->copy()->subDay()->endOfDay();

        $prevSales = Sale::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$prevFrom->utc(), $prevTo->utc()])
            ->get(['subtotal','shipping_charges','trade_in_value','sales_person_list']);

        $prevStaffTotals = [];
        foreach ($prevSales as $sale) {
            $staffList = is_string($sale->sales_person_list)
                ? json_decode($sale->sales_person_list, true)
                : ($sale->sales_person_list ?? []);
            if (!is_array($staffList)) continue;
            $count = max(1, count($staffList));
            $vol   = (floatval($sale->subtotal) + floatval($sale->shipping_charges ?? 0) - floatval($sale->trade_in_value ?? 0)) / $count;
            foreach ($staffList as $name) {
                $name = trim($name);
                $prevStaffTotals[$name] = ($prevStaffTotals[$name] ?? 0) + $vol;
            }
        }

        // Add change% to each staff member
        foreach ($staffData as $name => &$d) {
            $prev = $prevStaffTotals[$name] ?? 0;
            $d['prev_total'] = $prev;
            $d['change_pct'] = $prev > 0
                ? round((($d['total'] - $prev) / $prev) * 100, 1)
                : ($d['total'] > 0 ? 100 : 0);
        }
        unset($d);

        // Build day labels for trend chart (last 30 days or date range)
        $trendLabels = [];
        $trendValues = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $label = $cursor->format('M d');
            $trendLabels[] = $label;
            $trendValues[] = round($dailySales[$label] ?? 0, 2);
            $cursor->addDay();
            if (count($trendLabels) > 60) break; // cap at 60 days
        }

        return [
            'staff'             => array_values($staffData),
            'total_sales'       => $sales->count(),
            'total_revenue'     => $sales->sum('final_total'),
            'avg_sale'          => $sales->count() > 0 ? round($sales->sum('final_total') / $sales->count(), 2) : 0,
            'payment_breakdown' => $paymentBreakdown,
            'trend_labels'      => $trendLabels,
            'trend_values'      => $trendValues,
            'from_label'        => $from->format('M d, Y'),
            'to_label'          => $to->format('M d, Y'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return $user?->hasAnyRole(['Superadmin', 'Administration', 'Manager']) ?? false;
    }
}