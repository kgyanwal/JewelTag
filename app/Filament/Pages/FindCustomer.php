<?php

namespace App\Filament\Pages;

use App\Filament\Resources\CustomerResource;
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
            ->query(Customer::query())
            ->modifyQueryUsing(function (Builder $query) {
                $f = $this->data;

                return $query
                    // 1. Filter by Customer ID
                    ->when($f['customer_no'] ?? null, fn ($q, $v) => $q->where('customer_no', 'like', "%{$v}%"))
                    
                    // 2. Filter by Name (Search both name and last_name)
                    ->when($f['full_name'] ?? null, function ($q, $v) {
                        $q->where(fn ($sub) => $sub->where('name', 'like', "%{$v}%")
                            ->orWhere('last_name', 'like', "%{$v}%")
                            ->orWhereRaw("CONCAT(name, ' ', last_name) LIKE ?", ["%{$v}%"])
                        );
                    })

                    // 3. Filter by Phone
                    ->when($f['phone'] ?? null, fn ($q, $v) => $q->where('phone', 'like', "%{$v}%"))

                    // 4. Filter by Email
                    ->when($f['email'] ?? null, fn ($q, $v) => $q->where('email', 'like', "%{$v}%"))

                    // 5. Filter by Address (Search across street, city, and postcode)
                    ->when($f['address'] ?? null, function ($q, $v) {
                        $q->where(fn ($sub) => $sub->where('street', 'like', "%{$v}%")
                            ->orWhere('city', 'like', "%{$v}%")
                            ->orWhere('postcode', 'like', "%{$v}%")
                        );
                    })
                    ->latest();
            })
            ->columns([
                TextColumn::make('customer_no')
                    ->label('ID')
                    ->copyable()
                    ->sortable()
                    ->fontFamily('mono'),

                TextColumn::make('name')
                    ->label('FULL NAME')
                    ->weight('bold')
                    ->formatStateUsing(fn ($record) => "{$record->name} {$record->last_name}")
                    ->searchable(['name', 'last_name']),

                TextColumn::make('phone')
                    ->label('MOBILE')
                    ->icon('heroicon-m-phone')
                    ->copyable(),

                TextColumn::make('email')
                    ->label('EMAIL')
                    ->icon('heroicon-m-envelope')
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('full_address')
                    ->label('ADDRESS')
                    ->getStateUsing(fn ($record) => trim("{$record->street} {$record->city}, {$record->state} {$record->postcode}"))
                    ->wrap()
                    ->limit(50),
            ])
            ->actions([
                \Filament\Tables\Actions\Action::make('edit')
                    ->label('Edit Profile')
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn ($record) => CustomerResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function resetFilters(): void
    {
        $this->form->fill();
        $this->resetTable();
    }
}