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
use App\Models\ProductItem; // Assuming this model holds sale data
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\CheckboxList;

class SoldItemsReport extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?string $navigationLabel = 'SALES: Items Sold';
    protected static string $view = 'filament.pages.sold-items-report';

    public ?array $data = [];
    public bool $showTable = false;

    public function mount(): void
    {
        $this->form->fill([
            'from_date' => now()->startOfMonth()->format('Y-m-d'),
            'to_date' => now()->format('Y-m-d'),
            'fields' => ['stock_no', 'description', 'sold_price'],
        ]);
    }

    // 🔹 This button now triggers the data visibility
    public function runAction(string $action)
    {
        if ($action === 'preview') {
            $this->showTable = true;
        }
        // Logic for Print/Export would go here
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // 🔹 Filtering logic based on your Form Data
                ProductItem::query()
                    ->when($this->data['from_date'], fn($q) => $q->whereDate('created_at', '>=', $this->data['from_date']))
                    ->when($this->data['to_date'], fn($q) => $q->whereDate('created_at', '<=', $this->data['to_date']))
                    ->where('status', 'sold')
            )
            ->columns([
                TextColumn::make('barcode')->label('Stock No.')->visible(fn() => in_array('stock_no', $this->data['fields'] ?? [])),
                TextColumn::make('productTemplate.name')->label('Description')->visible(fn() => in_array('description', $this->data['fields'] ?? [])),
                TextColumn::make('retail_price')->label('Sold Price')->money('USD')->visible(fn() => in_array('sold_price', $this->data['fields'] ?? [])),
                TextColumn::make('created_at')->label('Date Sold')->date()->visible(fn() => in_array('date_in', $this->data['fields'] ?? [])),
            ])
            ->paginated([10, 25, 50]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(12)->schema([
                Section::make('Options:')->columnSpan(9)->schema([
                    Grid::make(4)->schema([
                        DatePicker::make('from_date')->label('From Date:')->live(),
                        DatePicker::make('to_date')->label('To Date:')->live(),
                        // ... other filters here
                    ]),
                ]),
                Section::make('Fields:')->columnSpan(3)->schema([
                    CheckboxList::make('fields')
                        ->options([
                            'stock_no' => 'Stock/Item No.',
                            'description' => 'Description',
                            'sold_price' => 'Sold Price',
                            'date_in' => 'Date In',
                        ])->live() // 🔹 .live() allows the table to update instantly
                ]),
            ])
        ])->statePath('data');
    }
}