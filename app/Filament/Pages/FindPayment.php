<?php

namespace App\Filament\Pages;

use App\Models\Payment;
use App\Models\Store;
use Filament\Forms\Components\{Grid, Section, Select, TextInput, Group};
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class FindPayment extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon  = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $title           = 'Find Payments';
    protected static string $view             = 'filament.pages.find-payment';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(['show_range' => false]);
    }

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
                Section::make('Find payments')
                    ->schema([
                        Grid::make(7)->schema([
                            TextInput::make('job_number')
                                ->label('Job Number')
                                ->placeholder('e.g. D5000')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),

                            TextInput::make('last_name')
                                ->label('Last Name')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),

                            TextInput::make('first_name')
                                ->label('First Name')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),

                            TextInput::make('amount')
                                ->label('Payment Amount')
                                ->prefix('$')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),

                            Select::make('payment_type')
                                ->label('Payment Type')
                                ->options([
                                    'CASH' => 'Cash',
                                    'VISA' => 'Visa',
                                    'MASTERCARD' => 'Mastercard',
                                    'AMEX' => 'Amex',
                                    'LAYBUY' => 'Laybuy',
                                ])
                                ->placeholder('Any')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),

                            // --- PURE TEXT DATE INPUT ---
                            TextInput::make('payment_date')
                                ->label('Payment Date')
                                ->placeholder('mm/dd/yyyy')
                                ->mask('99/99/9999')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable())
                                ->extraInputAttributes(['style' => 'cursor: text !important;']),

                            Select::make('store_id')
                                ->label('Store')
                                ->options(Store::pluck('name', 'id'))
                                ->placeholder('All stores')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),
                        ]),

                        Group::make()
                            ->schema([
                                \Filament\Forms\Components\Actions::make([
                                    \Filament\Forms\Components\Actions\Action::make('toggle_range')
                                        ->label(fn(Get $get) => $get('show_range') ? 'Hide Date Range' : 'Find By date Range ↓')
                                        ->color('gray')
                                        ->size('sm')
                                        ->link()
                                        ->action(fn(Get $get, $set) => $set('show_range', !$get('show_range'))),
                                ]),

                                Grid::make(4)
                                    ->schema([
                                        // --- PURE TEXT FROM DATE ---
                                        TextInput::make('date_from')
                                            ->label('From Date')
                                            ->placeholder('mm/dd/yyyy')
                                            ->mask('99/99/9999')
                                            ->live()
                                            ->afterStateUpdated(fn() => $this->resetTable())
                                            ->extraInputAttributes(['style' => 'cursor: text !important;']),

                                        // --- PURE TEXT TO DATE ---
                                        TextInput::make('date_to')
                                            ->label('To Date')
                                            ->placeholder('mm/dd/yyyy')
                                            ->mask('99/99/9999')
                                            ->live()
                                            ->afterStateUpdated(fn() => $this->resetTable())
                                            ->extraInputAttributes(['style' => 'cursor: text !important;']),
                                    ])
                                    ->visible(fn(Get $get) => $get('show_range')),
                            ])
                            ->extraAttributes(['class' => 'mt-2 pt-2 border-t border-gray-100']),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Payment::query())
            ->modifyQueryUsing(function (Builder $query) {
                $f = $this->data;

                return $query->with(['sale.customer', 'store'])
                    ->when(
                        $f['job_number'] ?? null,
                        fn($q, $v) =>
                        $q->whereHas('sale', fn($sq) => $sq->where('invoice_number', 'like', "%{$v}%"))
                    )
                    ->when(
                        $f['last_name'] ?? null,
                        fn($q, $v) =>
                        $q->whereHas('sale.customer', fn($sq) => $sq->where('last_name', 'like', "%{$v}%"))
                    )
                    ->when(
                        $f['first_name'] ?? null,
                        fn($q, $v) =>
                        $q->whereHas('sale.customer', fn($sq) => $sq->where('name', 'like', "%{$v}%"))
                    )
                    ->when($f['amount'] ?? null, function ($q, $v) {
                        $digits = str_replace(['$', ','], '', $v);
                        return $q->where('amount', 'like', "%{$digits}%");
                    })
                    ->when($f['payment_type'] ?? null, fn($q, $v) => $q->where('method', $v))

                    ->when($f['payment_date'] ?? null, function ($q, $v) {
                        try {
                            return $q->whereDate('created_at', \Carbon\Carbon::createFromFormat('m/d/Y', $v)->format('Y-m-d'));
                        } catch (\Exception $e) {
                            return $q;
                        }
                    })
                    ->when($f['date_from'] ?? null, function ($q, $v) {
                        try {
                            return $q->whereDate('created_at', '>=', \Carbon\Carbon::createFromFormat('m/d/Y', $v)->format('Y-m-d'));
                        } catch (\Exception $e) {
                            return $q;
                        }
                    })
                    ->when($f['date_to'] ?? null, function ($q, $v) {
                        try {
                            return $q->whereDate('created_at', '<=', \Carbon\Carbon::createFromFormat('m/d/Y', $v)->format('Y-m-d'));
                        } catch (\Exception $e) {
                            return $q;
                        }
                    })

                    ->when($f['store_id'] ?? null, fn($q, $v) => $q->where('store_id', $v))
                    ->latest();
            })
            ->columns([
                TextColumn::make('sale.invoice_number')
                    ->label('JOB NUMBER')
                    ->weight('bold')
                    ->color('primary')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('sale.customer.last_name')
                    ->label('LAST NAME')
                    ->default('—'),

                TextColumn::make('sale.customer.name')
                    ->label('FIRST NAME')
                    ->default('—'),

                TextColumn::make('amount')
                    ->label('PAYMENT AMOUNT')
                    ->money('USD')
                    ->weight('bold'),

                TextColumn::make('method')
                    ->label('PAYMENT TYPE')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn($state) => Str::upper($state)),

                TextColumn::make('created_at')
                    ->label('DATE')
                    ->dateTime('m/d/Y')
                    ->sortable(),

                TextColumn::make('store.name')
                    ->label('STORE')
                    ->placeholder('N/A')
                    ->weight('medium'),
            ])
            ->actions([
                \Filament\Tables\Actions\Action::make('view_sale')
                    ->label('View Sale')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn(Payment $record): string => \App\Filament\Resources\SaleResource::getUrl('edit', ['record' => $record->sale_id])),
            ]);
    }

    public function resetFilters(): void
    {
        $this->form->fill(['show_range' => false]);
        $this->resetTable();
    }
}
