<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use Filament\Forms\Components\Actions\Action;
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
    protected static ?string $navigationGroup = 'CRM';
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
                            TextInput::make('customer_no')->label('Customer ID'),
                            TextInput::make('last_name')->label('Last Name'),
                            TextInput::make('name')->label('First Name'),
                            TextInput::make('company')->label('Company'),
                        ]),
                        Grid::make(4)->schema([
                            TextInput::make('street')->label('Street'),
                            TextInput::make('suburb')->label('Suburb'),
                            TextInput::make('city')->label('City/Province'),
                            TextInput::make('state')->label('State'),
                        ]),
                        Grid::make(4)->schema([
                            TextInput::make('postcode')->label('Postcode'),
                            TextInput::make('home_phone')->label('Home Phone'),
                            TextInput::make('phone')->label('Mobile')->prefix('+1'),
                            TextInput::make('email')->label('Email'),
                        ]),
                        Grid::make(4)->schema([
                            Select::make('sales_person')
                                ->options([
                                    'Aaron' => 'Aaron',
                                    'Ben' => 'Ben',
                                    'Simon' => 'Simon',
                                ])->placeholder('Any'),
                        ]),
                    ])->compact(),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Customer::query())
            ->columns([
                ImageColumn::make('image')->circular()->label(''),
                TextColumn::make('customer_no')->label('ID')->sortable(),
                TextColumn::make('name')->label('Name')->formatStateUsing(fn($record) => "$record->name $record->last_name"),
                TextColumn::make('phone')->label('Mobile'),
                TextColumn::make('city')->label('City'),
                TextColumn::make('loyalty_tier')->badge(),
            ])
            ->filters([]) // We use the form above for filtering
            ->actions([
                \Filament\Tables\Actions\EditAction::make()
                    ->url(fn (Customer $record): string => "/admin/customers/{$record->id}/edit"),
            ]);
    }
protected function getTableQuery(): Builder
{
    $query = Customer::query();
    $formData = $this->form->getState();

    return $query
        ->when($formData['customer_no'], fn($q, $val) => $q->where('customer_no', 'like', "%$val%"))
        ->when($formData['name'], fn($q, $val) => $q->where('name', 'like', "%$val%"))
        ->when($formData['last_name'], fn($q, $val) => $q->where('last_name', 'like', "%$val%"))
        ->when($formData['phone'], fn($q, $val) => $q->where('phone', 'like', "%$val%"))
        ->when($formData['email'], fn($q, $val) => $q->where('email', 'like', "%$val%"))
        ->when($formData['sales_person'], fn($q, $val) => $q->where('sales_person', $val));
}
    public function applyFilters(): void
    {
        // This forces the table to refresh using the form data
        $this->resetTable();
    }

    public function clearFilters(): void
    {
        $this->form->fill();
        $this->applyFilters();
    }
}