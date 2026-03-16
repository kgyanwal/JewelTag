<?php

namespace App\Filament\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\User;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

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
                Section::make('Search Customers')
                    ->schema([
                        Grid::make(4)->schema([
                            TextInput::make('customer_no')
                                ->label('Customer ID')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),
                            TextInput::make('name')
                                ->label('First Name')
                                ->live()
                                ->afterStateUpdated(fn() => $this->resetTable()),
                            TextInput::make('last_name')
                                ->label('Last Name')
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
                    ->when($f['customer_no'] ?? null, fn ($q, $v) => $q->where('customer_no', 'like', "%{$v}%"))
                    ->when($f['name'] ?? null, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
                    ->when($f['last_name'] ?? null, fn ($q, $v) => $q->where('last_name', 'like', "%{$v}%"))
                    ->when($f['company'] ?? null, fn ($q, $v) => $q->where('company', 'like', "%{$v}%"))
                    ->when($f['phone'] ?? null, fn ($q, $v) => $q->where('phone', 'like', "%{$v}%"))
                    ->when($f['email'] ?? null, fn ($q, $v) => $q->where('email', 'like', "%{$v}%"))
                    ->when($f['city'] ?? null, fn ($q, $v) => $q->where('city', 'like', "%{$v}%"))
                    ->when($f['sales_person'] ?? null, fn ($q, $v) => $q->where('sales_person', $v))
                    ->latest();
            })
            ->columns([
                TextColumn::make('customer_no')
                    ->label('ID')
                    ->sortable()
                    ->copyable(),

                TextColumn::make('full_name')
                    ->label('FULL NAME')
                    ->weight('bold')
                    ->getStateUsing(fn ($record) => "{$record->name} {$record->last_name}"),

                TextColumn::make('phone')
                    ->label('MOBILE')
                    ->copyable(),

                TextColumn::make('email')
                    ->label('EMAIL')
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('address')
                    ->label('ADDRESS')
                    ->getStateUsing(fn ($record) => trim("{$record->street} {$record->city}, {$record->state} {$record->postcode}"))
                    ->wrap(),
            ])
            ->actions([
                // 🚀 VIEW DETAILS POPUP (Slide-over)
                Action::make('view')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->slideOver()
                    ->modalSubmitAction(false)
                    ->form(fn ($record) => [
                        Section::make('Customer Profile')
                            ->schema([
                                Grid::make(2)->schema([
                                    Placeholder::make('id')->label('Customer ID')->content($record->customer_no),
                                    Placeholder::make('created')->label('Member Since')->content($record->created_at->format('M d, Y')),
                                    Placeholder::make('name')->label('Name')->content("{$record->name} {$record->last_name}"),
                                    Placeholder::make('phone')->label('Phone')->content($record->phone),
                                    Placeholder::make('email')->label('Email')->content($record->email ?? 'N/A'),
                                    Placeholder::make('tier')->label('Loyalty Tier')->content(strtoupper($record->loyalty_tier ?? 'Standard')),
                                ]),
                            ]),
                        Section::make('Mailing Address')
                            ->schema([
                                Placeholder::make('full_address')
                                    ->label('')
                                    ->content(new HtmlString("
                                        <div class='text-sm text-gray-600'>
                                            {$record->street}<br>
                                            {$record->city}, {$record->state} {$record->postcode}<br>
                                            <strong>Country:</strong> {$record->country}
                                        </div>
                                    ")),
                            ]),
                    ]),

                \Filament\Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn ($record) => CustomerResource::getUrl('edit', ['record' => $record])),
            ])
            ->headerActions([
                \Filament\Tables\Actions\Action::make('reset')
                    ->label('Clear Filters')
                    ->color('gray')
                    ->action(fn() => $this->resetFilters()),
            ]);
    }

    public function resetFilters(): void
    {
        $this->form->fill();
        $this->resetTable();
    }
}