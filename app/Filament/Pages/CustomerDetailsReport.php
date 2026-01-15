<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Components\{Section, Grid, Select, DatePicker, Toggle, CheckboxList, TextInput};
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;

class CustomerDetailsReport extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

   protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    
    // 🔹 Level 1: The Main Group
    protected static ?string $navigationGroup = 'Reports'; 
    
    // 🔹 Level 2 & 3: Hierarchical Labeling
    // This makes it appear as "Customer > CR007 - Details"
    protected static ?string $navigationLabel = 'Customer > Details List';
    
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.customer-details-report';

    public ?array $filterData = [];
    public array $selectedFields = ['name', 'phone', 'email', 'city']; 

    public function mount(): void
    {
        $this->form->fill();
    }

    // 🔹 Logic for the Export Button
    public function exportReport()
    {
        // Add your CSV/Excel logic here
        $this->dispatch('notify', ['message' => 'Exporting Data...']);
    }

    // 🔹 Updated Form focusing only on 'name' and core filters
    protected function getFormSchema(): array
    {
        return [
            Section::make('Options')
                ->schema([
                    Grid::make(3)->schema([
                        DatePicker::make('birthday_from')->label('Birthdays From')->live(),
                        DatePicker::make('birthday_to')->label('To')->live(),
                        TextInput::make('postcode')->placeholder('Search Postcode')->live(),
                    ]),
                ]),
        ];
    }

    protected function getFieldsFormSchema(): array
    {
        return [
            CheckboxList::make('selectedFields')
                ->options([
                    'name' => 'Customer Name', // Updated to use single name field
                    'phone' => 'Phone',
                    'email' => 'Email',
                    'city' => 'City',
                    'loyalty_tier' => 'Loyalty Tier',
                    'gold_preference' => 'Gold Preference',
                ])
                ->reactive()
                ->afterStateUpdated(fn ($state) => $this->selectedFields = $state),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Customer::query())
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->when($this->filterData['birthday_from'] ?? null, fn($q, $d) => $q->whereDate('dob', '>=', $d))
                ->when($this->filterData['postcode'] ?? null, fn($q, $v) => $q->where('postcode', 'like', "%{$v}%"))
                ->latest()
            )
            ->columns([
                TextColumn::make('name')->label('NAME')->visible(fn() => in_array('name', $this->selectedFields)),
                TextColumn::make('phone')->label('PHONE')->visible(fn() => in_array('phone', $this->selectedFields)),
                TextColumn::make('email')->label('EMAIL')->visible(fn() => in_array('email', $this->selectedFields)),
                TextColumn::make('city')->label('CITY')->visible(fn() => in_array('city', $this->selectedFields)),
                TextColumn::make('gold_preference')->label('GOLD PREF')->visible(fn() => in_array('gold_preference', $this->selectedFields)),
            ])
            ->paginated([10, 25, 50]);
    }
}