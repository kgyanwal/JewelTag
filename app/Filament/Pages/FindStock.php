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
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Actions\Action as HeaderAction;
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
     * Auto refresh table when filters change
     */
    public function updated($property): void
    {
        if (str_starts_with($property, 'data.')) {
            $this->resetTable();
        }
    }

    /**
     * FIX: add missing method so Blade buttons won't break
     */
    public function applyFilters(): void
    {
        $this->resetTable();
    }

    public function form(Form $form): Form
    {
        $settings = InventorySetting::all()->pluck('value', 'key');

        return $form
            ->statePath('data')
            ->schema([
                Section::make('Find Stock')
                    ->description('Filter results update automatically as you type.')
                    ->schema([
                        Grid::make(4)->schema([

                            TextInput::make('stock_number')
                                ->label('Stock #')
                                ->live()
                                ->debounce(500),

                            TextInput::make('description')
                                ->label('Description')
                                ->live()
                                ->debounce(500),

                            Select::make('status')
                                ->label('Location')
                                ->options([
                                    'in_stock' => 'In Stock',
                                    'sold' => 'Sold',
                                    'on_hold' => 'On Hold',
                                ])
                                ->live(),

                            Select::make('department')
                                ->options(fn() => collect($settings['departments'] ?? [])
                                    ->pluck('name', 'name'))
                                ->searchable()
                                ->live(),

                            Select::make('sub_department')
                                ->options(fn() => collect($settings['sub_departments'] ?? [])
                                    ->mapWithKeys(fn($i) => [$i => $i]))
                                ->searchable()
                                ->live(),

                            Select::make('category')
                                ->options(fn() => collect($settings['categories'] ?? [])
                                    ->mapWithKeys(fn($i) => [$i => $i]))
                                ->searchable()
                                ->live(),

                            Select::make('metal_type')
                                ->options(fn() => collect($settings['metal_types'] ?? [])
                                    ->mapWithKeys(fn($i) => [$i => $i]))
                                ->searchable()
                                ->live(),

                            Select::make('supplier_id')
                                ->label('Supplier')
                                ->options(Supplier::pluck('company_name', 'id'))
                                ->searchable()
                                ->live(),

                            TextInput::make('price_from')
                                ->numeric()
                                ->prefix('$')
                                ->live()
                                ->debounce(800),

                            TextInput::make('price_to')
                                ->numeric()
                                ->prefix('$')
                                ->live()
                                ->debounce(800),

                            TextInput::make('supplier_code')
                                ->label('Vendor Code')
                                ->live()
                                ->debounce(500),

                            TextInput::make('serial_number')
                                ->label('Serial #')
                                ->live()
                                ->debounce(500),

                        ]),
                    ])
                    ->collapsible(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            HeaderAction::make('clear')
                ->label('Clear All')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->action(function () {
                    $this->form->fill();
                    $this->resetTable();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ProductItem::query())
            ->modifyQueryUsing(function (Builder $query) {

                $f = $this->data;

                return $query
                    ->when($f['stock_number'] ?? null,
                        fn ($q, $v) => $q->where('barcode', 'like', "%{$v}%"))

                    ->when($f['description'] ?? null,
                        fn ($q, $v) => $q->where('custom_description', 'like', "%{$v}%"))

                    ->when($f['department'] ?? null,
                        fn ($q, $v) => $q->where('department', $v))

                    ->when($f['sub_department'] ?? null,
                        fn ($q, $v) => $q->where('sub_department', $v))

                    ->when($f['category'] ?? null,
                        fn ($q, $v) => $q->where('category', $v))

                    ->when($f['metal_type'] ?? null,
                        fn ($q, $v) => $q->where('metal_type', $v))

                    ->when($f['status'] ?? null,
                        fn ($q, $v) => $q->where('status', $v))

                    ->when($f['supplier_id'] ?? null,
                        fn ($q, $v) => $q->where('supplier_id', $v))

                    ->when($f['supplier_code'] ?? null,
                        fn ($q, $v) => $q->where('supplier_code', 'like', "%{$v}%"))

                    ->when($f['serial_number'] ?? null,
                        fn ($q, $v) => $q->where('serial_number', 'like', "%{$v}%"))

                    ->when($f['price_from'] ?? null,
                        fn ($q, $v) => $q->where('retail_price', '>=', $v))

                    ->when($f['price_to'] ?? null,
                        fn ($q, $v) => $q->where('retail_price', '<=', $v));
            })
            ->columns([

                TextColumn::make('barcode')
                    ->label('STOCK #')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('qty')
                    ->label('QTY')
                    ->alignCenter(),

                TextColumn::make('custom_description')
                    ->label('DESCRIPTION')
                    ->wrap()
                    ->limit(40),

                TextColumn::make('retail_price')
                    ->label('PRICE')
                    ->money('USD')
                    ->sortable()
                    ->color('success'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'in_stock' => 'success',
                        'sold' => 'danger',
                        'on_hold' => 'warning',
                        default => 'gray',
                    }),

            ])
            ->actions([
                EditAction::make()
                    ->url(fn ($record) =>
                        ProductItemResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc');
    }
}