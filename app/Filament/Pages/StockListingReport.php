<?php

namespace App\Filament\Pages;

use App\Models\ProductItem;
use App\Models\Supplier;
use App\Models\InventorySetting;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Components\{Section, Grid, CheckboxList, Select, TextInput, Group, Toggle};
use App\Forms\Components\CustomDatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB;

class StockListingReport extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon  = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Analytics & Reports';
    protected static ?string $navigationLabel = 'Stock Listing Report';
    protected static string  $view            = 'filament.pages.stock-listing-report';

    public ?array $filterData = [];

    public array $selectedFields = [
        'barcode', 'custom_description', 'cost_price', 'retail_price',
        'status', 'department', 'category', 'metal_type', 'date_in',
    ];

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getColumnOptions(): array
    {
        return [
            'barcode'            => 'Stock No.',
            'custom_description' => 'Description',
            'cost_price'         => 'Cost Price',
            'retail_price'       => 'Sale Price',
            'web_price'          => 'Web Price',
            'discount_percent'   => 'Discount %',
            'markup'             => 'Markup',
            'qty'                => 'Qty',
            'status'             => 'Location / Status',
            'date_in'            => 'Date In',
            'department'         => 'Department',
            'sub_department'     => 'Sub-Department',
            'category'           => 'Category',
            'metal_type'         => 'Metal Type',
            'metal_weight'       => 'Metal Weight',
            'diamond_weight'     => 'Diamond Weight',
            'size'               => 'Size',
            'shape'              => 'Shape',
            'color'              => 'Colour',
            'clarity'            => 'Clarity',
            'cut'                => 'Cut',
            'polish'             => 'Polish',
            'symmetry'           => 'Symmetry',
            'fluorescence'       => 'Fluorescence',
            'measurements'       => 'Measurements',
            'certificate_number' => 'Cert. Number',
            'certificate_agency' => 'Cert. Agency',
            'is_lab_grown'       => 'Lab Grown',
            'serial_number'      => 'Serial No.',
            'supplier_code'      => 'Supplier Code',
            'supplier_name'      => 'Supplier',
            'rfid_code'          => 'RFID Code',
            'is_memo'            => 'Memo Item',
            'is_trade_in'        => 'Trade-In',
            'created_at'         => 'Date Entered',
        ];
    }

    public function form(Form $form): Form
    {
        $settings = InventorySetting::all()->pluck('value', 'key');

        return $form->schema([
            Section::make('Stock Report Filters')
                ->description('Filter your inventory and choose which columns to include in the report.')
                ->schema([
                    // ── ROW 1: Date + Status + Supplier ──────────────────────
                    Grid::make(4)->schema([
                        CustomDatePicker::make('date_from')
                            ->label('Date In From')
                            ->live(),

                        CustomDatePicker::make('date_to')
                            ->label('Date In To')
                            ->live(),

                        Select::make('status')
                            ->label('Status / Location')
                            ->options([
                                'in_stock' => 'In Stock',
                                'sold'     => 'Sold',
                                'on_hold'  => 'On Hold',
                                'memo'     => 'On Memo',
                            ])
                            ->placeholder('All Statuses')
                            ->live(),

                        Select::make('supplier_id')
                            ->label('Supplier / Vendor')
                            ->options(Supplier::pluck('company_name', 'id'))
                            ->searchable()
                            ->placeholder('All Suppliers')
                            ->live(),
                    ]),

                    // ── ROW 2: Department + Category + Metal + Price Range ───
                    Grid::make(4)->schema([
                        Select::make('department')
                            ->label('Department')
                            ->options(fn() => collect($settings['departments'] ?? [])
                                ->filter(fn($item) => !empty($item['name']))
                                ->pluck('name', 'name'))
                            ->searchable()
                            ->placeholder('All Departments')
                            ->live(),

                        Select::make('category')
                            ->label('Category')
                            ->options(fn() => collect($settings['categories'] ?? [])
                                ->filter()
                                ->mapWithKeys(fn($i) => [$i => $i]))
                            ->searchable()
                            ->placeholder('All Categories')
                            ->live(),

                        Select::make('metal_type')
                            ->label('Metal Type')
                            ->options(fn() => collect($settings['metal_types'] ?? [])
                                ->filter()
                                ->mapWithKeys(fn($i) => [$i => $i]))
                            ->searchable()
                            ->placeholder('All Metals')
                            ->live(),

                        TextInput::make('barcode_search')
                            ->label('Stock # / Barcode')
                            ->placeholder('G7302...')
                            ->live()
                            ->debounce(400),
                    ]),

                    // ── ROW 3: Price Range + Trade-In + Memo ─────────────────
                    Grid::make(4)->schema([
                        TextInput::make('price_from')
                            ->label('Retail Price From')
                            ->numeric()
                            ->prefix('$')
                            ->live(onBlur: true),

                        TextInput::make('price_to')
                            ->label('Retail Price To')
                            ->numeric()
                            ->prefix('$')
                            ->live(onBlur: true),

                        Select::make('is_memo')
                            ->label('Memo / Owned')
                            ->options(['1' => 'Memo / Consignment', '0' => 'Store Owned'])
                            ->placeholder('All')
                            ->live(),

                        Select::make('is_trade_in')
                            ->label('Source')
                            ->options(['1' => 'Trade-In Only', '0' => 'Purchased Stock'])
                            ->placeholder('All')
                            ->live(),
                    ]),

                    // ── COLUMN SELECTOR ───────────────────────────────────────
                    Group::make()->schema([
                        CheckboxList::make('selectedFields')
                            ->label('Columns to Include in Report')
                            ->options($this->getColumnOptions())
                            ->columns(5)
                            ->bulkToggleable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn($state) => $this->selectedFields = $state),
                    ])->extraAttributes(['class' => 'mt-4']),
                ]),
        ])->statePath('filterData');
    }

    protected function applyFilters(Builder $query): Builder
    {
        $f = $this->filterData;

        return $query
            ->when($f['date_from'] ?? null,      fn($q, $v) => $q->whereDate('date_in', '>=', $v))
            ->when($f['date_to'] ?? null,        fn($q, $v) => $q->whereDate('date_in', '<=', $v))
            ->when($f['status'] ?? null,         fn($q, $v) => $q->where('status', $v))
            ->when($f['supplier_id'] ?? null,    fn($q, $v) => $q->where('supplier_id', $v))
            ->when($f['department'] ?? null,     fn($q, $v) => $q->where('department', $v))
            ->when($f['category'] ?? null,       fn($q, $v) => $q->where('category', $v))
            ->when($f['metal_type'] ?? null,     fn($q, $v) => $q->where('metal_type', $v))
            ->when($f['barcode_search'] ?? null, fn($q, $v) => $q->where('barcode', 'like', "%{$v}%"))
            ->when($f['price_from'] ?? null,     fn($q, $v) => $q->where('retail_price', '>=', $v))
            ->when($f['price_to'] ?? null,       fn($q, $v) => $q->where('retail_price', '<=', $v))
            ->when($f['is_memo'] !== null && $f['is_memo'] !== '',     fn($q) => $q->where('is_memo', $f['is_memo']))
            ->when($f['is_trade_in'] !== null && $f['is_trade_in'] !== '', fn($q) => $q->where('is_trade_in', $f['is_trade_in']));
    }

    public function table(Table $table): Table
    {
        $allPossible = $this->getColumnOptions();
        $columns     = [];

        foreach ($allPossible as $key => $label) {
            $visible = fn() => in_array($key, $this->selectedFields);

            if ($key === 'supplier_name') {
                $columns[] = TextColumn::make('supplier.company_name')
                    ->label('SUPPLIER')
                    ->visible($visible)
                    ->sortable();

            } elseif (in_array($key, ['cost_price', 'retail_price', 'web_price'])) {
                $columns[] = TextColumn::make($key)
                    ->label(strtoupper($label))
                    ->money('USD')
                    ->visible($visible)
                    ->sortable();

            } elseif ($key === 'is_memo') {
                $columns[] = TextColumn::make($key)
                    ->label('MEMO')
                    ->formatStateUsing(fn($state) => $state ? 'Consignment' : 'Owned')
                    ->badge()
                    ->color(fn($state) => $state ? 'warning' : 'gray')
                    ->visible($visible);

            } elseif ($key === 'is_trade_in') {
                $columns[] = TextColumn::make($key)
                    ->label('SOURCE')
                    ->formatStateUsing(fn($state) => $state ? 'Trade-In' : 'Purchased')
                    ->badge()
                    ->color(fn($state) => $state ? 'info' : 'gray')
                    ->visible($visible);

            } elseif ($key === 'is_lab_grown') {
                $columns[] = IconColumn::make($key)
                    ->label('LAB GROWN')
                    ->boolean()
                    ->visible($visible);

            } elseif ($key === 'status') {
                $columns[] = TextColumn::make($key)
                    ->label('STATUS')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'in_stock' => 'success',
                        'sold'     => 'danger',
                        'on_hold'  => 'warning',
                        'memo'     => 'info',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn($state) => ucwords(str_replace('_', ' ', $state)))
                    ->visible($visible);

            } elseif (in_array($key, ['date_in', 'created_at'])) {
                $columns[] = TextColumn::make($key)
                    ->label(strtoupper($label))
                    ->date('M d, Y')
                    ->sortable()
                    ->visible($visible);

            } elseif ($key === 'discount_percent') {
                $columns[] = TextColumn::make($key)
                    ->label('DISC %')
                    ->suffix('%')
                    ->visible($visible);

            } elseif ($key === 'barcode') {
                $columns[] = TextColumn::make($key)
                    ->label('STOCK #')
                    ->searchable()
                    ->weight('bold')
                    ->fontFamily('mono')
                    ->copyable()
                    ->visible($visible);

            } elseif ($key === 'custom_description') {
                $columns[] = TextColumn::make($key)
                    ->label('DESCRIPTION')
                    ->limit(50)
                    ->wrap()
                    ->visible($visible);

            } else {
                $columns[] = TextColumn::make($key)
                    ->label(strtoupper($label))
                    ->sortable()
                    ->visible($visible);
            }
        }

        return $table
            ->query(ProductItem::query()->with('supplier'))
            ->modifyQueryUsing(fn(Builder $query) => $this->applyFilters($query))
            ->columns($columns)
            ->defaultSort('date_in', 'desc')
            ->paginated([25, 50, 100, 'all'])
            ->emptyStateHeading('No stock matches your filters');
    }

    public function exportReport(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $allPossible = $this->getColumnOptions();
        $fields      = $this->selectedFields;

        $query = ProductItem::with('supplier')
            ->tap(fn($q) => $this->applyFilters($q))
            ->orderBy('date_in', 'desc');

        $filename = 'stock-listing-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($query, $fields, $allPossible) {
            $handle = fopen('php://output', 'w');

            // ── Headers ──────────────────────────────────────────────────────
            $headers = array_map(fn($f) => $allPossible[$f] ?? $f, $fields);
            fputcsv($handle, $headers);

            // ── Data Rows ─────────────────────────────────────────────────────
            $query->chunk(500, function ($items) use ($handle, $fields) {
                foreach ($items as $item) {
                    $row = [];
                    foreach ($fields as $field) {
                        $row[] = match ($field) {
                            'supplier_name'  => $item->supplier?->company_name ?? '',
                            'is_memo'        => $item->is_memo ? 'Consignment' : 'Owned',
                            'is_trade_in'    => $item->is_trade_in ? 'Trade-In' : 'Purchased',
                            'is_lab_grown'   => $item->is_lab_grown ? 'Yes' : 'No',
                            'status'         => ucwords(str_replace('_', ' ', $item->status ?? '')),
                            'date_in'        => $item->date_in?->format('m/d/Y') ?? '',
                            'created_at'     => $item->created_at?->format('m/d/Y') ?? '',
                            'cost_price',
                            'retail_price',
                            'web_price'      => '$' . number_format(floatval($item->$field ?? 0), 2),
                            default          => $item->$field ?? '',
                        };
                    }
                    fputcsv($handle, $row);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function getStockSummary(): array
    {
        $query = ProductItem::query()->tap(fn($q) => $this->applyFilters($q));

        return [
            'total'      => (clone $query)->count(),
            'in_stock'   => (clone $query)->where('status', 'in_stock')->count(),
            'sold'       => (clone $query)->where('status', 'sold')->count(),
            'on_hold'    => (clone $query)->where('status', 'on_hold')->count(),
            'retail_val' => (clone $query)->sum('retail_price'),
            'cost_val'   => (clone $query)->sum('cost_price'),
        ];
    }
}