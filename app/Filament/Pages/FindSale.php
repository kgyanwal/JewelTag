<?php

namespace App\Filament\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Sale;
use App\Models\SalesAssistant;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
    protected static ?string $navigationGroup = 'Sales'; // 🔹 Moved under Sales
    protected static ?string $title = 'Find Sale';
    protected static string $view = 'filament.pages.find-sale';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
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
                                ->placeholder('e.g. INV-2026-0001'),
                            TextInput::make('customer_name')
                                ->label('Customer Name'),
                            TextInput::make('phone')
                                ->label('Phone Number')
                                ->tel()
                                ->prefix('+1'), // 🔹 Persistent +1
                            
                            Select::make('sales_person')
                                ->options(SalesAssistant::pluck('name', 'name'))
                                ->searchable(),
                            DatePicker::make('date_from')->label('Date From'),
                            DatePicker::make('date_to')->label('Date To'),
                        ]),
                    ])->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Sale::query())
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->when($this->data['invoice_number'] ?? null, fn ($q, $v) => $q->where('invoice_number', 'like', "%{$v}%"))
                ->when($this->data['customer_name'] ?? null, fn ($q, $v) => $q->whereHas('customer', fn($sq) => $sq->where('first_name', 'like', "%{$v}%")->orWhere('last_name', 'like', "%{$v}%")))
                ->when($this->data['phone'] ?? null, fn ($q, $v) => $q->whereHas('customer', fn($sq) => $sq->where('phone', 'like', "%{$v}%")))
                ->when($this->data['date_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
                ->when($this->data['date_to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
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
                    ->color('primary')
                    ->searchable(),
                TextColumn::make('customer.first_name')
                    ->label('CUSTOMER')
                    ->formatStateUsing(fn($record) => "{$record->customer?->first_name} {$record->customer?->last_name}")
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
                    // 🔹 Redirects to the Edit page of the SaleResource
                    ->url(fn (Sale $record): string => SaleResource::getUrl('edit', ['record' => $record])),
            ]);
    }

    public function updatedData(): void
    {
        $this->resetTable();
    }
}