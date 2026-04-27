<?php

namespace App\Filament\Pages;

use App\Models\Payment;
use App\Models\Store;
use Filament\Forms\Components\{Grid, Section, Select, TextInput, Group};
use App\Forms\Components\CustomDatePicker; // Ensure this matches your component namespace
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
        if (str_starts_with($property, 'data. ')) {
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
    ->options(function () {
        $json = \Illuminate\Support\Facades\DB::table('site_settings')->where('key', 'payment_methods')->value('value');
        $methods = $json ? json_decode($json, true) : [];
        
        $options = [];
        if (is_array($methods)) {
            foreach ($methods as $method) {
                $options[strtoupper($method)] = strtoupper($method);
            }
        }
        
        return $options;
    })
    ->placeholder('Any')
    ->live()
    ->afterStateUpdated(fn() => $this->resetTable()),

                            CustomDatePicker::make('payment_date')
                                ->label('Payment Date')
                                ->displayFormat('m/d/Y')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),

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
                                        CustomDatePicker::make('date_from')
                                            ->label('From Date')
                                            ->displayFormat('m/d/Y')
                                            ->live()
                                            ->afterStateUpdated(fn() => $this->resetTable()),

                                        CustomDatePicker::make('date_to')
                                            ->label('To Date')
                                            ->displayFormat('m/d/Y')
                                            ->live()
                                            ->afterStateUpdated(fn() => $this->resetTable()),
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
                    ->when($f['job_number'] ?? null, fn($q, $v) =>
                    $q->whereHas('sale', fn($sq) => $sq->where('invoice_number', 'like', "%{$v}%")))
                    ->when($f['last_name'] ?? null, fn($q, $v) =>
                    $q->whereHas('sale.customer', fn($sq) => $sq->where('last_name', 'like', "%{$v}%")))
                    ->when($f['first_name'] ?? null, fn($q, $v) =>
                    $q->whereHas('sale.customer', fn($sq) => $sq->where('name', 'like', "%{$v}%")))
                    ->when($f['amount'] ?? null, function ($q, $v) {
                        $digits = str_replace(['$', ','], '', $v);
                        return $q->where('amount', 'like', "%{$digits}%");
                    })
                    ->when($f['payment_type'] ?? null, fn($q, $v) => $q->where('method', $v))

                    // Date Filters - All three now use CustomDatePicker (YYYY-MM-DD)
                    ->when($f['payment_date'] ?? null, function ($q, $v) {
                        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
                            return $q->whereDate('paid_at', $v);
                        }
                        return $q;
                    })
                    ->when($f['date_from'] ?? null, function ($q, $v) {
                        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
                            return $q->whereDate('paid_at', '>=', $v);
                        }
                        return $q;
                    })
                    ->when($f['date_to'] ?? null, function ($q, $v) {
                        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
                            return $q->whereDate('paid_at', '<=', $v);
                        }
                        return $q;
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

                TextColumn::make('paid_at')
                    ->label('DATE')
                    ->dateTime('m/d/Y')
                    ->sortable(),

                TextColumn::make('store.name')
                    ->label('STORE')
                    ->placeholder('N/A')
                    ->weight('medium'),
            ])
           ->actions([
                // 🚀 Added guard to ensure sale_id exists before generating URL
                \Filament\Tables\Actions\Action::make('view_sale')
                    ->label('View Sale')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn(Payment $record): bool => !empty($record->sale_id))
                    ->url(fn(Payment $record): string => \App\Filament\Resources\SaleResource::getUrl('view', ['record' => $record->sale_id])), // Changed from 'edit' to 'view' based on your prompt

                // 🚀 Fallback action if this is just a deposit for a custom order
                \Filament\Tables\Actions\Action::make('view_custom_order')
                    ->label('View Order')
                    ->icon('heroicon-o-sparkles')
                    ->color('warning')
                    ->visible(fn(Payment $record): bool => empty($record->sale_id) && !empty($record->custom_order_id))
                    ->url(fn(Payment $record): string => \App\Filament\Resources\CustomOrderResource::getUrl('edit', ['record' => $record->custom_order_id])),
            ]);
    }

    public function resetFilters(): void
    {
        $this->form->fill([
            'show_range' => false,
            'job_number' => null,
            'last_name' => null,
            'first_name' => null,
            'amount' => null,
            'payment_type' => null,
            'payment_date' => null,
            'date_from' => null,
            'date_to' => null,
            'store_id' => null,
        ]);
        $this->resetTable();
    }
}