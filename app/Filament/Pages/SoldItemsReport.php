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
use Filament\Tables\Columns\Summarizers\Sum;
use App\Models\ProductItem;
use App\Models\InventorySetting;
use App\Models\Supplier;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
// Aliasing to prevent "Method does not exist" errors
use Filament\Actions\Action as PageAction;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Blade;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SoldItemsExport;

/**
 * SoldItemsReport Class
 * * An enterprise-grade intelligence report for sales tracking.
 * This version resolves the SQL 'Unknown Column' error by using calculated summaries.
 * * @property array $data
 */
class SoldItemsReport extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationGroup = 'Analytics & Reports';
    protected static ?string $navigationLabel = '📊 Sales Intelligence';
    protected static string $view = 'filament.pages.sold-items-report';

    public ?array $data = [];
    public bool $showTable = true; 
    public array $intelligence = [];

    /**
     * Component Lifecycle: Initial state setup
     */
    public function mount(): void
    {
        $this->form->fill([
            'from_date' => now()->startOfMonth()->format('Y-m-d'),
            'to_date' => now()->format('Y-m-d'),
            'fields' => ['stock_no', 'description', 'sold_price', 'cost', 'profit', 'date_sold'],
            'status_filter' => 'sold',
        ]);

        $this->refreshIntelligence();
    }

    /**
     * Core Logic: Calculate Google-Sheets style intelligence cards
     */
    public function refreshIntelligence(): void
    {
        $fromDate = $this->data['from_date'] ?? now()->startOfMonth();
        $toDate = $this->data['to_date'] ?? now();

        $query = ProductItem::query()
            ->where('status', 'sold')
            ->whereBetween('updated_at', [
                Carbon::parse($fromDate)->startOfDay(), 
                Carbon::parse($toDate)->endOfDay()
            ]);

        $revenue = (float) $query->sum('retail_price');
        $cost = (float) $query->sum('cost_price');
        $profit = $revenue - $cost;
        $itemCount = $query->count();

        $this->intelligence = [
            'revenue' => number_format($revenue, 2),
            'cost' => number_format($cost, 2),
            'profit' => number_format($profit, 2),
            'count' => number_format($itemCount),
            'margin' => $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0,
            'avg_ticket' => $itemCount > 0 ? number_format($revenue / $itemCount, 2) : '0.00',
            'best_day' => $this->getBestSellingDay($fromDate, $toDate),
        ];
    }

    /**
     * Complex DB Query for daily volume analysis
     */
    protected function getBestSellingDay($from, $to): string
    {
        $day = DB::table('product_items')
            ->select(DB::raw('DATE(updated_at) as date'), DB::raw('SUM(retail_price) as total'))
            ->where('status', 'sold')
            ->whereNull('deleted_at')
            ->whereBetween('updated_at', [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()])
            ->groupBy('date')
            ->orderByDesc('total')
            ->first();

        return $day ? Carbon::parse($day->date)->format('l, M d') : 'N/A';
    }

    /**
     * Trigger Action for Preview
     */
    public function runAction(string $action): void
    {
        if ($action === 'preview') {
            $this->showTable = true;
            $this->refreshIntelligence();
        }
    }

    /**
     * Form Schema with extensive filtering options
     */
    public function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(12)->schema([
                Section::make('Intelligence Parameters')
                    ->columnSpan(8)
                    ->collapsible()
                    ->schema([
                        Grid::make(3)->schema([
                            DatePicker::make('from_date')
                                ->label('Start Date')
                                ->live()
                                ->afterStateUpdated(fn() => $this->refreshIntelligence()),
                            
                            DatePicker::make('to_date')
                                ->label('End Date')
                                ->live()
                                ->afterStateUpdated(fn() => $this->refreshIntelligence()),

                            Select::make('status_filter')
                                ->label('Status')
                                ->options(['sold' => 'Sold Only', 'on_hold' => 'On Hold'])
                                ->default('sold')
                                ->live(),
                        ]),
                        
                        Grid::make(2)->schema([
                            Select::make('supplier_id')
                                ->label('Vendor Filter')
                                ->options(Supplier::pluck('company_name', 'id'))
                                ->searchable()
                                ->live(),
                            
                            TextInput::make('min_profit')
                                ->label('Minimum Profit Threshold')
                                ->numeric()
                                ->prefix('$')
                                ->live()
                                ->debounce(500),
                        ]),
                    ]),

                Section::make('Display Mapping')
                    ->columnSpan(4)
                    ->schema([
                        CheckboxList::make('fields')
                            ->options([
                                'stock_no' => 'Stock #',
                                'description' => 'Item Name',
                                'sold_price' => 'Sold Price',
                                'cost' => 'Cost Basis',
                                'profit' => 'Net Profit',
                                'margin' => 'Margin %',
                                'date_sold' => 'Timestamp',
                            ])
                            ->columns(2)
                            ->live(),
                    ]),
            ])
        ])->statePath('data');
    }

    /**
     * Table Configuration with Calculated Summaries
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                ProductItem::query()
                    ->where('status', 'sold')
                    ->when($this->data['from_date'], fn($q, $d) => $q->whereDate('updated_at', '>=', $d))
                    ->when($this->data['to_date'], fn($q, $d) => $q->whereDate('updated_at', '<=', $d))
                    ->when($this->data['supplier_id'], fn($q, $id) => $q->where('supplier_id', $id))
                    ->when($this->data['min_profit'], function($q, $min) {
                        return $q->whereRaw('(retail_price - cost_price) >= ?', [$min]);
                    })
            )
            ->columns([
                TextColumn::make('barcode')
                    ->label('ID')
                    ->searchable()
                    ->visible(fn() => in_array('stock_no', $this->data['fields'] ?? [])),

                TextColumn::make('custom_description')
                    ->label('Description')
                    ->wrap()
                    ->visible(fn() => in_array('description', $this->data['fields'] ?? [])),

                TextColumn::make('retail_price')
                    ->label('Sold Price')
                    ->money('USD')
                    ->alignment(Alignment::End)
                    ->summarize(Sum::make()->label('Gross Total'))
                    ->visible(fn() => in_array('sold_price', $this->data['fields'] ?? [])),

                TextColumn::make('cost_price')
                    ->label('Cost')
                    ->money('USD')
                    ->alignment(Alignment::End)
                    ->summarize(Sum::make()->label('Total Cost'))
                    ->visible(fn() => in_array('cost', $this->data['fields'] ?? [])),

                /**
                 * 🔹 PROFIT COLUMN FIX: 
                 * We use ->state() for display and REMOVE ->summarize(Sum::make()) 
                 * to avoid the "Unknown column product_items.profit" error.
                 */
                TextColumn::make('profit_calc')
                    ->label('Profit')
                    ->state(fn($record) => $record->retail_price - $record->cost_price)
                    ->money('USD')
                    ->alignment(Alignment::End)
                    ->color(fn($state) => $state >= 0 ? 'success' : 'danger')
                    ->visible(fn() => in_array('profit', $this->data['fields'] ?? [])),

                TextColumn::make('margin_pct')
                    ->label('Margin')
                    ->state(fn($record) => $record->retail_price > 0 ? round((($record->retail_price - $record->cost_price) / $record->retail_price) * 100, 1) . '%' : '0%')
                    ->alignment(Alignment::Center)
                    ->badge()
                    ->color(fn($state) => (float) $state > 25 ? 'success' : 'warning')
                    ->visible(fn() => in_array('margin', $this->data['fields'] ?? [])),

                TextColumn::make('updated_at')
                    ->label('Timestamp')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->visible(fn() => in_array('date_sold', $this->data['fields'] ?? [])),
            ])
            ->headerActions([
                TableAction::make('export_excel')
                    ->label('Excel Export')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('success')
                    ->action(fn() => $this->exportExcel()),
                
                TableAction::make('export_pdf')
                    ->label('PDF Print')
                    ->icon('heroicon-m-printer')
                    ->color('danger')
                    ->action(fn() => $this->exportPdf()),
            ])
            ->defaultSort('updated_at', 'desc')
            ->striped();
    }

    /**
     * Download Logic: Excel
     */
    public function exportExcel()
    {
        return Excel::download(new SoldItemsExport($this->getTableQuery()->get()), 'Sales_Intelligence.xlsx');
    }

    /**
     * Download Logic: PDF
     */
    public function exportPdf()
    {
        $data = ['intel' => $this->intelligence, 'rows' => $this->getTableQuery()->get()];
        $pdf = Pdf::loadHTML(Blade::render('filament.exports.sales-pdf', $data));
        return response()->streamDownload(fn() => print($pdf->output()), "Report.pdf");
    }

    /**
     * Global Actions
     */
    protected function getHeaderActions(): array
    {
        return [
            PageAction::make('recalc')
                ->label('Sync Stats')
                ->icon('heroicon-o-arrow-path')
                ->action(fn() => $this->refreshIntelligence()),
        ];
    }
}