<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Summarizer;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Customer;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use App\Forms\Components\CustomDatePicker;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action as PageAction;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Support\Enums\Alignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SoldItemsReport extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationGroup = 'Analytics & Reports';
    protected static ?string $navigationLabel = 'Sales Intelligence';
    protected static string $view = 'filament.pages.sold-items-report';

    public ?array $data = [];
    public bool $showTable = false;
    public array $intelligence = [];
    public array $breakdowns = [];

    public function mount(): void
    {
        $this->form->fill([
            'from_date' => now()->startOfMonth()->format('Y-m-d'),
            'to_date'   => now()->format('Y-m-d'),
            'fields'    => [
                'invoice', 'stock_no', 'description', 'customer', 'staff',
                'payment_method', 'sold_price', 'cost', 'profit', 'margin', 'date_sold',
            ],
        ]);

        $this->refreshIntelligence();
    }

    /**
     * Base query — real sale transactions (sale_items joined to sales),
     * not a product_items stock snapshot.
     */
    protected function baseQuery(): Builder
    {
        $fromDate = $this->data['from_date'] ?? now()->startOfMonth();
        $toDate   = $this->data['to_date'] ?? now();

        return SaleItem::query()
            ->whereHas('sale', function ($q) use ($fromDate, $toDate) {
                $q->whereNotIn('status', ['cancelled', 'void'])
                    ->whereBetween('created_at', [
                        Carbon::parse($fromDate)->startOfDay(),
                        Carbon::parse($toDate)->endOfDay(),
                    ]);
            })
            ->when($this->data['supplier_id'] ?? null, function ($q, $id) {
                $q->whereHas('productItem', fn($sub) => $sub->where('supplier_id', $id));
            })
            ->when($this->data['category'] ?? null, function ($q, $c) {
                $q->whereHas('productItem', fn($sub) => $sub->where('category', $c));
            })
            ->when($this->data['min_profit'] ?? null, function ($q, $min) {
                $q->whereRaw('(COALESCE(sale_price_override, sold_price * qty) - (cost_price * qty)) >= ?', [$min]);
            })
            ->when($this->data['search_stock'] ?? null, function ($q, $term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('stock_no_display', 'like', "%{$term}%")
                        ->orWhere('custom_description', 'like', "%{$term}%");
                });
            })
            ->when($this->data['sales_person'] ?? null, function ($q, $person) {
                $q->whereHas('sale', fn($sub) => $sub->where('sales_person_list', 'like', "%{$person}%"));
            })
            ->when($this->data['customer_id'] ?? null, function ($q, $customerId) {
                $q->whereHas('sale', fn($sub) => $sub->where('customer_id', $customerId));
            });
    }

      // 🚀 FIX — the summarizer's $query->get() can return plain stdClass rows
    // (not hydrated SaleItem models) depending on how Filament executes the
    // underlying table query. Accept object|SaleItem so both paths work.
    protected function lineTotal(object $item): float
    {
        return (float) ($item->sale_price_override ?: ($item->sold_price * $item->qty));
    }

    protected function lineCost(object $item): float
    {
        return (float) $item->cost_price * (int) $item->qty;
    }

    public function refreshIntelligence(): void
    {
        $items = (clone $this->baseQuery())->get();

        $revenue   = $items->sum(fn($i) => $this->lineTotal($i));
        $cost      = $items->sum(fn($i) => $this->lineCost($i));
        $profit    = $revenue - $cost;
        $itemCount = $items->count();

        $this->intelligence = [
            'revenue'    => number_format($revenue, 2),
            'cost'       => number_format($cost, 2),
            'profit'     => number_format($profit, 2),
            'count'      => number_format($itemCount),
            'margin'     => $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0,
            'avg_ticket' => $itemCount > 0 ? number_format($revenue / $itemCount, 2) : '0.00',
            'best_day'   => $this->getBestSellingDay(),
        ];

        $this->breakdowns = [
            'top_categories' => $this->getCategoryBreakdown(),
            'top_suppliers'  => $this->getSupplierBreakdown(),
        ];
    }

    protected function getBestSellingDay(): string
    {
        $items = (clone $this->baseQuery())->with('sale')->get();

        $byDay = $items->groupBy(fn($i) => optional($i->sale?->created_at)->format('Y-m-d'))
            ->map(fn($group) => $group->sum(fn($i) => $this->lineTotal($i)))
            ->sortDesc();

        $topDate = $byDay->keys()->first();

        return $topDate ? Carbon::parse($topDate)->format('l, M d') : 'N/A';
    }

    protected function getCategoryBreakdown(): array
    {
        $items = (clone $this->baseQuery())->with('productItem')->get();

        return $items->groupBy(fn($i) => $i->productItem?->category ?: 'Uncategorized')
            ->map(fn($group, $label) => [
                'label'   => $label,
                'revenue' => $group->sum(fn($i) => $this->lineTotal($i)),
                'qty'     => $group->count(),
            ])
            ->sortByDesc('revenue')
            ->take(5)
            ->values()
            ->toArray();
    }

    protected function getSupplierBreakdown(): array
    {
        $items = (clone $this->baseQuery())->with('productItem.supplier')->get();

        return $items->groupBy(fn($i) => $i->productItem?->supplier?->company_name ?: 'Unknown')
            ->map(fn($group, $label) => [
                'label'   => $label,
                'revenue' => $group->sum(fn($i) => $this->lineTotal($i)),
                'qty'     => $group->count(),
            ])
            ->sortByDesc('revenue')
            ->take(5)
            ->values()
            ->toArray();
    }

    public function applyFilters(): void
    {
        $this->showTable = true;
        $this->refreshIntelligence();
        $this->resetTable();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Date Range')
                ->description('Choose the time window for completed sales')
                ->icon('heroicon-o-calendar-days')
                ->iconColor('primary')
                ->schema([
                    Grid::make(2)->schema([
                        CustomDatePicker::make('from_date')
                            ->label('Start Date')
                            ->live()
                            ->afterStateUpdated(fn() => $this->refreshIntelligence()),

                        CustomDatePicker::make('to_date')
                            ->label('End Date')
                            ->live()
                            ->afterStateUpdated(fn() => $this->refreshIntelligence()),
                    ]),
                ]),

            Section::make('Filters')
                ->description('Narrow the dataset by vendor, category, staff, or customer')
                ->icon('heroicon-o-funnel')
                ->iconColor('primary')
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('supplier_id')
                            ->label('Vendor')
                            ->placeholder('All Vendors')
                            ->options(fn() => Supplier::pluck('company_name', 'id'))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn() => $this->refreshIntelligence()),

                        Select::make('category')
                            ->label('Category')
                            ->placeholder('All Categories')
                            ->options(fn() => \App\Models\ProductItem::query()
                                ->whereNotNull('category')
                                ->distinct()
                                ->pluck('category', 'category'))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn() => $this->refreshIntelligence()),

                        Select::make('sales_person')
                            ->label('Sales Staff')
                            ->placeholder('All Staff')
                            ->options(fn() => User::orderBy('name')->pluck('name', 'name'))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn() => $this->refreshIntelligence()),
                    ]),

                    Grid::make(3)->schema([
                        Select::make('customer_id')
                            ->label('Customer')
                            ->placeholder('All Customers')
                            ->options(fn() => Customer::query()
                                ->orderBy('name')
                                ->limit(200)
                                ->get()
                                ->mapWithKeys(fn($c) => [$c->id => trim($c->name . ' ' . ($c->last_name ?? ''))]))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn() => $this->refreshIntelligence()),

                        TextInput::make('min_profit')
                            ->label('Minimum Profit ($)')
                            ->numeric()
                            ->prefix('$')
                            ->live()
                            ->debounce(500)
                            ->afterStateUpdated(fn() => $this->refreshIntelligence()),

                        TextInput::make('search_stock')
                            ->label('Search Stock # or Description')
                            ->prefixIcon('heroicon-o-magnifying-glass')
                            ->live()
                            ->debounce(500)
                            ->afterStateUpdated(fn() => $this->refreshIntelligence()),
                    ]),
                ]),

            Section::make('Columns to Show')
                ->description('Choose which fields appear in the results table and exports')
                ->icon('heroicon-o-table-cells')
                ->iconColor('primary')
                ->schema([
                    CheckboxList::make('fields')
                        ->hiddenLabel()
                        ->options([
                            'invoice'        => 'Invoice #',
                            'stock_no'       => 'Stock #',
                            'description'    => 'Item Name',
                            'category'       => 'Category',
                            'supplier'       => 'Supplier',
                            'customer'       => 'Customer',
                            'staff'          => 'Sales Staff',
                            'payment_method' => 'Payment Method',
                            'qty'            => 'Quantity',
                            'sold_price'     => 'Sold Price',
                            'cost'           => 'Cost Basis',
                            'discount'       => 'Discount',
                            'profit'         => 'Net Profit',
                            'margin'         => 'Margin %',
                            'date_sold'      => 'Sale Date',
                        ])
                        ->columns(3)
                        ->live(),
                ]),
        ])->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn() => $this->baseQuery())
            ->columns([
                TextColumn::make('sale.invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary')
                    ->visible(fn() => in_array('invoice', $this->data['fields'] ?? [])),

                TextColumn::make('stock_no_display')
                    ->label('Stock #')
                    ->searchable()
                    ->visible(fn() => in_array('stock_no', $this->data['fields'] ?? [])),

                TextColumn::make('custom_description')
                    ->label('Description')
                    ->wrap()
                    ->limit(50)
                    ->searchable()
                    ->visible(fn() => in_array('description', $this->data['fields'] ?? [])),

                TextColumn::make('productItem.category')
                    ->label('Category')
                    ->badge()
                    ->color('info')
                    ->visible(fn() => in_array('category', $this->data['fields'] ?? [])),

                TextColumn::make('productItem.supplier.company_name')
                    ->label('Supplier')
                    ->color('gray')
                    ->toggleable()
                    ->visible(fn() => in_array('supplier', $this->data['fields'] ?? [])),

                TextColumn::make('sale.customer')
                    ->label('Customer')
                    ->getStateUsing(fn($record) => $record->sale?->customer
                        ? trim($record->sale->customer->name . ' ' . ($record->sale->customer->last_name ?? ''))
                        : '—')
                    ->searchable()
                    ->visible(fn() => in_array('customer', $this->data['fields'] ?? [])),

                TextColumn::make('sale.sales_person_list')
                    ->label('Sales Staff')
                    ->badge()
                    ->color('gray')
                    ->visible(fn() => in_array('staff', $this->data['fields'] ?? [])),

                TextColumn::make('sale.payment_method')
                    ->label('Payment')
                    ->formatStateUsing(fn($state, $record) => $record->sale?->is_split_payment ? 'SPLIT' : strtoupper($state ?? ''))
                    ->badge()
                    ->color('gray')
                    ->visible(fn() => in_array('payment_method', $this->data['fields'] ?? [])),

                TextColumn::make('qty')
                    ->label('Qty')
                    ->alignment(Alignment::Center)
                    ->visible(fn() => in_array('qty', $this->data['fields'] ?? [])),

                                TextColumn::make('line_total')
                    ->label('Sold Price')
                    ->state(fn($record) => $this->lineTotal($record))
                    ->money('USD')
                    ->alignment(Alignment::End)
                    ->weight('bold')
                    // 🚀 FIX — Sum::make() tries to SQL-sum a literal column
                    // named "line_total", which doesn't exist on sale_items.
                    // Use a closure-based summarizer instead, which sums the
                    // already-fetched records in PHP.
                                     ->summarize(Summarizer::make()
                        ->label('Gross Total')
                        ->using(fn($query) => '$' . number_format(
                            $query->get()->sum(fn($r) => $this->lineTotal($r)),
                            2
                        )))
                    ->visible(fn() => in_array('sold_price', $this->data['fields'] ?? [])),

                TextColumn::make('line_cost')
                    ->label('Cost Basis')
                    ->state(fn($record) => $this->lineCost($record))
                    ->money('USD')
                    ->alignment(Alignment::End)
                    ->color('gray')
                                        ->summarize(Summarizer::make()
                        ->label('Total Cost')
                        ->using(fn($query) => '$' . number_format(
                            $query->get()->sum(fn($r) => $this->lineCost($r)),
                            2
                        )))
                    ->visible(fn() => in_array('cost', $this->data['fields'] ?? [])),

                TextColumn::make('discount_amount')
                    ->label('Discount')
                    ->money('USD')
                    ->alignment(Alignment::End)
                    ->toggleable()
                    ->visible(fn() => in_array('discount', $this->data['fields'] ?? [])),

                TextColumn::make('line_profit')
                    ->label('Net Profit')
                    ->state(fn($record) => $this->lineTotal($record) - $this->lineCost($record))
                    ->money('USD')
                    ->alignment(Alignment::End)
                    ->weight('bold')
                    ->color(fn($state) => $state >= 0 ? 'success' : 'danger')
                    ->visible(fn() => in_array('profit', $this->data['fields'] ?? [])),

                TextColumn::make('line_margin')
                    ->label('Margin')
                    ->state(function ($record) {
                        $total = $this->lineTotal($record);
                        if ($total <= 0) return 0;
                        return round((($total - $this->lineCost($record)) / $total) * 100, 1);
                    })
                    ->formatStateUsing(fn($state) => $state . '%')
                    ->alignment(Alignment::Center)
                    ->badge()
                    ->color(fn($state) => match (true) {
                        (float) $state >= 40 => 'success',
                        (float) $state >= 20 => 'warning',
                        default              => 'danger',
                    })
                    ->visible(fn() => in_array('margin', $this->data['fields'] ?? [])),

                TextColumn::make('sale.created_at')
                    ->label('Sale Date')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->color('gray')
                    ->visible(fn() => in_array('date_sold', $this->data['fields'] ?? [])),
            ])
            ->headerActions([
                TableAction::make('export_excel')
                    ->label('Export Excel')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('success')
                    ->action(fn() => $this->exportExcel()),

                TableAction::make('export_pdf')
                    ->label('Export PDF')
                    ->icon('heroicon-m-printer')
                    ->color('danger')
                    ->action(fn() => $this->exportPdf()),
            ])
            ->defaultSort('id', 'desc')
            ->striped()
            ->paginated([25, 50, 100, 'all']);
    }

    public function exportExcel()
    {
        $rows = $this->baseQuery()->with(['sale.customer', 'productItem.supplier'])->get();

        $export = new class($rows, $this) implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize {
            protected Collection $rows;
            protected SoldItemsReport $page;

            public function __construct(Collection $rows, SoldItemsReport $page)
            {
                $this->rows = $rows;
                $this->page = $page;
            }

            public function collection(): Collection
            {
                return $this->rows;
            }

            public function headings(): array
            {
                return [
                    'Invoice #', 'Stock #', 'Description', 'Category', 'Supplier',
                    'Customer', 'Sales Staff', 'Payment Method', 'Qty',
                    'Sold Price', 'Cost Price', 'Discount', 'Net Profit', 'Margin %', 'Sale Date',
                ];
            }

            public function map($row): array
            {
                $total  = $this->page->lineTotalPublic($row);
                $cost   = $this->page->lineCostPublic($row);
                $profit = $total - $cost;
                $margin = $total > 0 ? round(($profit / $total) * 100, 1) : 0;

                $customer = $row->sale?->customer
                    ? trim($row->sale->customer->name . ' ' . ($row->sale->customer->last_name ?? ''))
                    : '—';

                return [
                    $row->sale?->invoice_number ?? '—',
                    $row->stock_no_display ?? '—',
                    strip_tags($row->custom_description ?? ''),
                    $row->productItem?->category ?? '—',
                    $row->productItem?->supplier?->company_name ?? '—',
                    $customer,
                    $row->sale?->sales_person_list ?? '—',
                    $row->sale?->is_split_payment ? 'SPLIT' : strtoupper($row->sale?->payment_method ?? ''),
                    $row->qty,
                    $total,
                    $cost,
                    (float) $row->discount_amount,
                    $profit,
                    $margin . '%',
                    optional($row->sale?->created_at)->format('M d, Y H:i'),
                ];
            }

            public function styles(Worksheet $sheet): ?array
            {
                return [
                    1 => [
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => [
                            'fillType'   => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '0B3D3C'],
                        ],
                    ],
                ];
            }
        };

        return Excel::download($export, 'Sales_Intelligence_' . now()->format('Y-m-d') . '.xlsx');
    }

    // Public wrappers so the anonymous export class can call the protected calc methods.
    public function lineTotalPublic(SaleItem $item): float
    {
        return $this->lineTotal($item);
    }

    public function lineCostPublic(SaleItem $item): float
    {
        return $this->lineCost($item);
    }

    public function exportPdf()
    {
        $data = [
            'intel'      => $this->intelligence,
            'breakdowns' => $this->breakdowns,
            'rows'       => $this->baseQuery()->with(['sale.customer', 'productItem.supplier'])->get(),
            'from_date'  => $this->data['from_date'] ?? null,
            'to_date'    => $this->data['to_date'] ?? null,
        ];

        $pdf = Pdf::loadHTML(Blade::render('filament.exports.sales-pdf', $data))->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn() => print($pdf->output()),
            'Sales_Intelligence_' . now()->format('Y-m-d') . '.pdf'
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            PageAction::make('recalc')
                ->label('Sync Stats')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->refreshIntelligence();
                    $this->resetTable();
                }),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $staff = \App\Helpers\Staff::user();
        return $staff?->hasAnyRole(['Superadmin', 'Administration']) ?? false;
    }
}