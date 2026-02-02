<?php

namespace App\Filament\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Sale;
use App\Models\SalesAssistant;
use Filament\Forms\Components\{DatePicker, Grid, Section, Select, TextInput};
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FindSale extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $title = 'Find Sale';
    protected static string $view = 'filament.pages.find-sale';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    // 🔹 Automatically refreshes table when form data changes
    public function updated($property): void
    {
        if (str_starts_with($property, 'data.')) {
            $this->resetTable();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Section::make('Advanced Sale Search')
                    ->description('Filter through your jewelry sales records')
                    ->aside() 
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('invoice_number')
                                ->label('Job / Invoice #')
                                ->live() // 🔹 Real-time search
                                ->placeholder('e.g. INV-2026-0001'),
                            TextInput::make('customer_name')
                                ->label('Customer Name')
                                ->live(),
                            TextInput::make('phone')
                                ->label('Phone Number')
                                ->tel()
                                ->prefix('+1')
                                ->live(),
                            Select::make('payment_method')
                                ->label('Payment Method')
                                ->options([
                                    'cash' => 'CASH',
                                    'laybuy' => 'LAYBUY (Installment Plan)',
                                    'visa' => 'VISA',
                                    'comenity' => 'COMENITY',
                                ])
                                ->searchable()
                                ->live()
                                ->placeholder('All Methods'),
                            DatePicker::make('date_from')->label('Date From')->live(),
                            DatePicker::make('date_to')->label('Date To')->live(),
                        ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Sale::query())
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->when($this->data['invoice_number'] ?? null, fn ($q, $v) => $q->where('invoice_number', 'like', "%{$v}%"))
                // 🔹 FIXED: Uses 'name' column instead of first/last name
                ->when($this->data['customer_name'] ?? null, fn ($q, $v) => $q->whereHas('customer', fn($sq) => $sq->where('name', 'like', "%{$v}%")))
                ->when($this->data['phone'] ?? null, fn ($q, $v) => $q->whereHas('customer', fn($sq) => $sq->where('phone', 'like', "%{$v}%")))
                ->when($this->data['date_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
                ->when($this->data['date_to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
                ->when($this->data['payment_method'] ?? null, fn ($q, $v) => $q->where('payment_method', $v))
                ->latest()
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('DATE ENTERED')
                    ->dateTime('M d, Y')
                    ->sortable(),
                TextColumn::make('invoice_number')
                    ->label('JOB NUMBER')
                    ->weight('bold')
                    ->color('primary'),
                TextColumn::make('customer.name')
                    ->label('CUSTOMER')
                    ->description(fn($record) => $record->customer?->phone ?? 'Walk-in'),
                TextColumn::make('final_total')
                    ->label('TOTAL')
                    ->money('USD')
                    ->alignment('right')
                    ->weight('bold'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed', 'sold' => 'success',
                        'pending' => 'warning',
                        'refunded' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
                \Filament\Tables\Actions\Action::make('view')
                    ->label('Open Sale')
                    ->icon('heroicon-m-eye')
                    ->color('gray')
                    ->url(fn (Sale $record): string => SaleResource::getUrl('edit', ['record' => $record])),
            ]);
    }

    public function resetFilters(): void
    {
        $this->form->fill();
        $this->resetTable();
    }
}