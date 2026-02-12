<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\ToggleButtons;
use Illuminate\Support\Str;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel = 'Vendors';
    protected static ?string $navigationGroup = 'Vendors';
    protected static ?string $modelLabel = 'Vendors';
    protected static ?string $pluralModelLabel = 'Vendors';
    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Supplier Details')
                    ->tabs([
                        Tabs\Tab::make('Basic Information')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Section::make('Vendors Identity')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('company_name')
                                                ->label('Company Name')
                                                ->required()
                                                ->maxLength(255),
                                            
                                            Forms\Components\TextInput::make('supplier_code')
                                                ->label('Supplier Code')
                                                ->required()
                                                ->unique(ignoreRecord: true)
                                                ->default(fn () => 'SUP-' . strtoupper(Str::random(6)))
                                                ->maxLength(50),
                                        ]),
                                        
                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('contact_person')
                                                ->label('Contact Person')
                                                ->maxLength(255),
                                            
                                            Forms\Components\Select::make('type')
                                                ->label('Supplier Type')
                                                ->options([
                                                    'manufacturer' => 'Manufacturer',
                                                    'wholesaler' => 'Wholesaler',
                                                    'distributor' => 'Distributor',
                                                    'artisan' => 'Artisan',
                                                    'other' => 'Other',
                                                ])
                                                ->default('wholesaler'),
                                        ]),
                                        
                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('tax_number')
                                                ->label('Tax Number')
                                                ->maxLength(50),
                                            
                                            Forms\Components\TextInput::make('lead_time_days')
                                                ->label('Lead Time (Days)')
                                                ->numeric()
                                                ->default(7),
                                        ]),
                                    ]),
                            ]),
                        
                        Tabs\Tab::make('Contact Details')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                Section::make('Communication')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('phone')
                                                ->label('Phone')
                                                ->tel()
                                                ->maxLength(20),
                                            
                                            Forms\Components\TextInput::make('mobile')
                                                ->label('Mobile')
                                                ->tel()
                                                ->maxLength(20),
                                        ]),
                                        
                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('email')
                                                ->label('Email')
                                                ->email()
                                                ->maxLength(255),
                                            
                                            Forms\Components\TextInput::make('fax')
                                                ->label('Fax')
                                                ->maxLength(20),
                                        ]),
                                        
                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('website')
                                                ->label('Website')
                                                ->url()
                                                ->maxLength(255),
                                        ]),
                                    ]),
                            ]),
                        
                        Tabs\Tab::make('Address')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Section::make('Physical Address')
                                    ->schema([
                                        Forms\Components\TextInput::make('physical_street')
                                            ->label('Street Address')
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        
                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('physical_suburb')
                                                ->label('Suburb/City')
                                                ->maxLength(100),
                                            
                                            Forms\Components\TextInput::make('physical_city')
                                                ->label('City')
                                                ->maxLength(100),
                                        ]),
                                        
                                        Grid::make(3)->schema([
                                            Forms\Components\TextInput::make('physical_state')
                                                ->label('State')
                                                ->maxLength(100),
                                            
                                            Forms\Components\Select::make('physical_country')
                                                ->label('Country')
                                                ->options([
                                                    'Australia' => 'Australia',
                                                    'United States' => 'United States',
                                                    'United Kingdom' => 'United Kingdom',
                                                    'India' => 'India',
                                                    'China' => 'China',
                                                ])
                                                ->default('Australia'),
                                            
                                            Forms\Components\TextInput::make('physical_postcode')
                                                ->label('Postcode')
                                                ->maxLength(20),
                                        ]),
                                    ]),
                            ]),
                        
                        Tabs\Tab::make('Terms & Status')
                            ->icon('heroicon-o-cog')
                            ->schema([
                                Section::make('Order Terms')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('payment_terms_days')
                                                ->label('Payment Terms (Days)')
                                                ->numeric()
                                                ->default(30),
                                            
                                            Forms\Components\TextInput::make('discount_percentage')
                                                ->label('Discount %')
                                                ->numeric()
                                                ->minValue(0)
                                                ->maxValue(100)
                                                ->default(0),
                                        ]),
                                    ]),
                                
                                Section::make('Status')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            ToggleButtons::make('is_active')
                                                ->label('Active')
                                                ->boolean()
                                                ->grouped()
                                                ->default(true),
                                            
                                            ToggleButtons::make('is_preferred')
                                                ->label('Preferred')
                                                ->boolean()
                                                ->grouped()
                                                ->default(false),
                                        ]),
                                        
                                        Forms\Components\Textarea::make('notes')
                                            ->label('Notes')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('supplier_code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('contact_person')
                    ->label('Contact')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->color(fn ($state) => match($state) {
                        'manufacturer' => 'success',
                        'wholesaler' => 'primary',
                        'distributor' => 'info',
                        default => 'gray',
                    }),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                
                Tables\Columns\IconColumn::make('is_preferred')
                    ->label('Preferred')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'manufacturer' => 'Manufacturer',
                        'wholesaler' => 'Wholesaler',
                        'distributor' => 'Distributor',
                        'artisan' => 'Artisan',
                        'other' => 'Other',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                
                Tables\Filters\TernaryFilter::make('is_preferred')
                    ->label('Preferred Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                // ⚠️ COMMENTED OUT TO FIX CRASH ⚠️
                // Uncomment this only after ProductItemResource is working and registered
                /*
                Tables\Actions\Action::make('viewProducts')
                    ->label('Products')
                    ->icon('heroicon-o-shopping-bag')
                    ->url(fn ($record) => 
                        \App\Filament\Resources\ProductItemResource::getUrl('index', [
                            'tableFilters' => [
                                'supplier_id' => [
                                    'value' => $record->id,
                                ],
                            ],
                        ])
                    ),
                */
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}