<?php

namespace App\Filament\Pages;

use App\Models\ProductItem;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockAgingReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Analytics & Reports';
    protected static ?string $navigationLabel = '🕰️ Stock Aging';
    protected static string $view = 'filament.pages.stock-aging-report';

    public ?array $data = [];
    public array $reportData = [];

    public function mount(): void
    {
        $this->form->fill([
            'as_of_date' => now()->format('Y-m-d'),
        ]);
        $this->generateReport();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Report Filters')->schema([
                Grid::make(3)->schema([
                    DatePicker::make('as_of_date')
                        ->label('To Date')
                        ->default(now())
                        ->live()
                        ->afterStateUpdated(fn() => $this->generateReport()),
                    
                    Select::make('department')
                        ->options(function() {
                            // 🔹 FIX: Filter out nulls and map values to strings to prevent TypeError
                            return ProductItem::query()
                                ->whereNotNull('department')
                                ->where('department', '!=', '')
                                ->distinct()
                                ->pluck('department')
                                ->mapWithKeys(fn($dept) => [$dept => (string) $dept])
                                ->toArray();
                        })
                        ->searchable()
                        ->placeholder('All Departments')
                        ->live()
                        ->afterStateUpdated(fn() => $this->generateReport()),
                ]),
            ])
        ])->statePath('data');
    }

    public function generateReport()
    {
        $asOfDate = $this->data['as_of_date'] ?? now();
        $deptFilter = $this->data['department'] ?? null;

        // Aging Buckets in Months
        $buckets = [
            '0_3' => [0, 3],
            '3_6' => [3, 6],
            '6_9' => [6, 9],
            '9_12' => [9, 12],
            '12_18' => [12, 18],
            '18_24' => [18, 24],
            '24_plus' => [24, null],
        ];

        $query = DB::table('product_items')
            ->select('department')
            ->where('status', 'in_stock')
            ->whereNull('deleted_at');

        if ($deptFilter) {
            $query->where('department', $deptFilter);
        }

        foreach ($buckets as $key => $range) {
            $start = $range[0];
            $end = $range[1];

            // Calculate date thresholds relative to the selected as_of_date
            $dateEnd = Carbon::parse($asOfDate)->subMonths($start)->endOfDay();
            $dateStart = $end ? Carbon::parse($asOfDate)->subMonths($end)->startOfDay() : null;

            // Build conditional SQL logic
            $condition = "created_at <= '{$dateEnd}'";
            if ($dateStart) {
                $condition .= " AND created_at >= '{$dateStart}'";
            } else {
                // For 24+ months (oldest stock)
                $condition = "created_at < '{$dateEnd}'";
            }

            // Aggregate Qty, Cost, and Retail value per bucket
            $query->addSelect(DB::raw("SUM(CASE WHEN {$condition} THEN qty ELSE 0 END) as qty_{$key}"));
            $query->addSelect(DB::raw("SUM(CASE WHEN {$condition} THEN (cost_price * qty) ELSE 0 END) as cost_{$key}"));
            $query->addSelect(DB::raw("SUM(CASE WHEN {$condition} THEN (retail_price * qty) ELSE 0 END) as retail_{$key}"));
        }

        $this->reportData = $query->groupBy('department')->get()->toArray();
    }
    public static function shouldRegisterNavigation(): bool
    {
        // 🔹 Use your Staff helper to check the identity of the person who entered the PIN
        $staff = \App\Helpers\Staff::user();

        // Only allow specific roles to see the Administration menu
        return $staff?->hasAnyRole(['Superadmin', 'Administration']) ?? false;
    }
}