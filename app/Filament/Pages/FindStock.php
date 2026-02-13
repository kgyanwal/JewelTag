<?php

namespace App\Filament\Pages;

use App\Models\ProductItem;
use App\Models\Supplier;
use App\Models\InventorySetting;
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
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProductItemResource; 

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

    /**
     * 🔹 Top Filter Form with Live Updates
     */
    public function form(Form $form): Form
    {
        $settings = InventorySetting::whereIn('key', ['departments', 'sub_departments', 'categories', 'metal_types'])
            ->get()
            ->pluck('value', 'key');

        return $form
            ->schema([
                Section::make('Find stock at Your Store')
                    ->description('The list below updates automatically as you type.')
                    ->schema([
                        Grid::make(6)
                            ->schema([
                                TextInput::make('stock_number')
                                    ->label('Stock Number')
                                    ->live()
                                    ->debounce(500), // Waits 500ms after you stop typing
                                
                                TextInput::make('description')
                                    ->label('Description')
                                    ->live()
                                    ->debounce(500),

                                Select::make('department')
                                    ->options($settings['departments'] ?? [])
                                    ->searchable()
                                    ->live(),

                                Select::make('sub_department')
                                    ->options($settings['sub_departments'] ?? [])
                                    ->searchable()
                                    ->live(),

                                Select::make('category')
                                    ->options($settings['categories'] ?? [])
                                    ->searchable()
                                    ->live(),

                                Select::make('metal_type')
                                    ->options($settings['metal_types'] ?? [])
                                    ->searchable()
                                    ->live(),

                                Select::make('status')
                                    ->label('Location')
                                    ->options(['in_stock' => 'In Stock', 'sold' => 'Sold', 'on_hold' => 'On Hold'])
                                    ->live(),

                                Grid::make(2)->schema([
                                    TextInput::make('price_from')
                                        ->numeric()
                                        ->prefix('$')
                                        ->live()
                                        ->debounce(600),
                                    TextInput::make('price_to')
                                        ->numeric()
                                        ->prefix('$')
                                        ->live()
                                        ->debounce(600),
                                ])->columnSpan(2),

                                Select::make('supplier_id')
                                    ->label('Supplier')
                                    ->options(Supplier::pluck('company_name', 'id'))
                                    ->searchable()
                                    ->live(),

                                TextInput::make('supplier_code')
                                    ->label('Vendor Code')
                                    ->live()
                                    ->debounce(500),

                                TextInput::make('serial_number')
                                    ->label('Serial Number')
                                    ->live()
                                    ->debounce(500),

                                Select::make('is_web_item')
                                    ->options(['1' => 'Yes', '0' => 'No'])
                                    ->live(),
                            ]),
                    ])->collapsible(),
            ])
            ->statePath('data');
    }

    /**
     * 🔹 Simplified Header Actions
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('clear')
                ->label('Clear All Filters')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->action(function () {
                    $this->form->fill();
                }),
        ];
    }

    /**
     * 🔹 Filtered Table
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(ProductItem::query())
            ->modifyQueryUsing(function (Builder $query) {
                $formData = $this->data; // Access the live state array

                return $query
                    ->when($formData['stock_number'] ?? null, fn ($q, $v) => $q->where('barcode', 'like', "%{$v}%"))
                    ->when($formData['description'] ?? null, fn ($q, $v) => $q->where('custom_description', 'like', "%{$v}%"))
                    ->when($formData['department'] ?? null, fn ($q, $v) => $q->where('department', $v))
                    ->when($formData['sub_department'] ?? null, fn ($q, $v) => $q->where('sub_department', $v))
                    ->when($formData['category'] ?? null, fn ($q, $v) => $q->where('category', $v))
                    ->when($formData['metal_type'] ?? null, fn ($q, $v) => $q->where('metal_type', $v))
                    ->when($formData['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
                    ->when($formData['supplier_id'] ?? null, fn ($q, $v) => $q->where('supplier_id', $v))
                    ->when($formData['supplier_code'] ?? null, fn ($q, $v) => $q->where('supplier_code', 'like', "%{$v}%"))
                    ->when($formData['serial_number'] ?? null, fn ($q, $v) => $q->where('serial_number', 'like', "%{$v}%"))
                    ->when($formData['price_from'] ?? null, fn ($q, $v) => $q->where('retail_price', '>=', $v))
                    ->when($formData['price_to'] ?? null, fn ($q, $v) => $q->where('retail_price', '<=', $v))
                    ->when(filled($formData['is_web_item'] ?? null), function ($q) use ($formData) {
                        return $q->where('is_web_item', $formData['is_web_item'] === '1');
                    });
            })
            ->columns([
                TextColumn::make('barcode')->label('STOCK NUMBER')->sortable(),
                TextColumn::make('qty')->label('QTY')->alignCenter(),
                TextColumn::make('custom_description')->label('DESCRIPTION')->wrap()->limit(50),
                TextColumn::make('retail_price')->label('SALES PRICE')->money('USD')->color('primary'),
                TextColumn::make('department')->label('DEPARTMENT'),
                TextColumn::make('sub_department')->label('SUB DEPARTMENT'),
                TextColumn::make('category')->label('CATEGORY'),
                TextColumn::make('metal_type')->label('METAL'),
                TextColumn::make('supplier.company_name')->label('SUPPLIER'),
                TextColumn::make('status')->label('LOCATION')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in_stock' => 'success',
                        'sold' => 'danger',
                        'on_hold' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->actions([
              
          
                
                \Filament\Tables\Actions\EditAction::make()
                    ->url(fn (ProductItem $record): string => ProductItemResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }
}