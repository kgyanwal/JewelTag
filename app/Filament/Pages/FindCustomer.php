<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\User;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;

class FindCustomer extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static ?string $navigationGroup = 'Customer';
    protected static ?string $title = 'Find Customer';
    protected static string $view = 'filament.pages.find-customer';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->schema([
                        Grid::make(4)->schema([
                            TextInput::make('customer_no')
                                ->label('Customer ID')
                                ->live() // 🚀 Makes search real-time
                                ->afterStateUpdated(fn() => $this->resetTable()),
                            TextInput::make('last_name')
                                ->label('Last Name')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),
                            TextInput::make('name')
                                ->label('First Name')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),
                            TextInput::make('company')
                                ->label('Company')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),
                        ]),
                        Grid::make(4)->schema([
                            TextInput::make('phone')
                                ->label('Mobile')
                                ->prefix('+1')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),
                            TextInput::make('email')
                                ->label('Email')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),
                            TextInput::make('city')
                                ->label('City')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),
                            Select::make('sales_person')
                                ->label('Sales Person')
                                // 🚀 FETCH DYNAMICALLY FROM USERS
                                ->options(User::pluck('name', 'name'))
                                ->placeholder('Any')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),
                        ]),
                    ])->compact(),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            // 🚀 The query now correctly pulls from the Livewire data property
            ->query(
                Customer::query()
                    ->when($this->data['customer_no'] ?? null, fn($q, $val) => $q->where('customer_no', 'like', "%$val%"))
                    ->when($this->data['name'] ?? null, fn($q, $val) => $q->where('name', 'like', "%$val%"))
                    ->when($this->data['last_name'] ?? null, fn($q, $val) => $q->where('last_name', 'like', "%$val%"))
                    ->when($this->data['phone'] ?? null, fn($q, $val) => $q->where('phone', 'like', "%$val%"))
                    ->when($this->data['email'] ?? null, fn($q, $val) => $q->where('email', 'like', "%$val%"))
                    ->when($this->data['city'] ?? null, fn($q, $val) => $q->where('city', 'like', "%$val%"))
                    ->when($this->data['sales_person'] ?? null, fn($q, $val) => $q->where('sales_person', $val))
            )
            ->columns([
                ImageColumn::make('image')->circular()->label(''),
                TextColumn::make('customer_no')->label('ID')->sortable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->getStateUsing(fn($record) => "{$record->name} {$record->last_name}"),
                TextColumn::make('phone')->label('Mobile'),
                TextColumn::make('city')->label('City'),
                TextColumn::make('loyalty_tier')->badge(),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make()
                    ->url(fn (Customer $record): string => "/admin/customers/{$record->id}/edit"),
                \Filament\Tables\Actions\Action::make('new_sale')
                    ->label('New Sale')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('success')
                    ->url(fn (Customer $record): string => \App\Filament\Resources\SaleResource::getUrl('create', ['customer_id' => $record->id])),    
            ]);
    }
}