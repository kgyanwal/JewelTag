<?php

namespace App\Filament\Pages;

use App\Models\ProductItem;
use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class FindStock extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static ?string $navigationLabel = 'Find Stock';
    protected static ?string $navigationGroup = 'Inventory';
    protected static string $view = 'filament.pages.find-stock';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
{
    return $form
        ->schema([
            Section::make('Find stock at Your Store')
                ->description('Use the filters below to locate specific inventory items.')
                ->schema([
                    Grid::make(6)
                        ->schema([
                            TextInput::make('stock_number')->label('Stock Number'),
                            TextInput::make('description')->label('Description'),
                            Select::make('department')
                                ->options(['GOLD' => 'Gold', 'DIAMOND' => 'Diamond'])
                                ->placeholder('Any'),
                            Select::make('category')
                                ->options(['RINGS' => 'Rings', 'CHAINS' => 'Chains'])
                                ->placeholder('Any'),
                            Select::make('collection')->placeholder('Any'),
                            Select::make('core_range')->placeholder('Any'),
                            
                            Select::make('location')
                                ->options(['in_stock' => 'In Stock', 'sold' => 'Sold'])
                                ->placeholder('Any'),
                            Grid::make(2)->schema([
                                TextInput::make('price_from')->placeholder('Min'),
                                TextInput::make('price_to')->placeholder('Max'),
                            ])->label('Sale Price'),

                            // ✅ FIX IS HERE: We added ->model(ProductItem::class)
                            Select::make('supplier_id')
    ->label('Supplier')
    ->options(\App\Models\Supplier::pluck('company_name', 'id')) // Manual pull
    ->searchable()
    ->placeholder('Any'),

                            TextInput::make('supplier_code'),
                            TextInput::make('serial_number'),
                            Select::make('is_web_item')
                                ->options(['1' => 'Yes', '0' => 'No'])
                                ->placeholder('Any'),
                        ]),
                ])->collapsible(),
        ])
        ->statePath('data');
}

    public function table(Table $table): Table
    {
        return $table
            ->query(ProductItem::query())
            ->columns([
                TextColumn::make('barcode')->label('STOCK NUMBER')->searchable()->sortable(),
                TextColumn::make('qty')->label('QTY')->alignCenter(),
                TextColumn::make('barcode')->label('DESCRIPTION'), // Mapping barcode to desc for now
                TextColumn::make('cost_price')->label('COST PRICE')->money('USD'),
                TextColumn::make('retail_price')->label('SALES PRICE')->money('USD')->color('primary'),
                TextColumn::make('department')->label('DEPARTMENT'),
                TextColumn::make('category')->label('CATEGORY'),
                TextColumn::make('supplier.company_name')->label('SUPPLIER'),
                TextColumn::make('status')->label('LOCATION')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in_stock' => 'success',
                        'sold' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([]) // Filters are handled by the top form
            ->actions([]);
    }
}