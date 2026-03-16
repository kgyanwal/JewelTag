<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StoreResource\Pages;
use App\Models\Store;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Tapp\FilamentGoogleAutocomplete\Forms\Components\GoogleAutocomplete;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Admin';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Store Identity')
                    ->description('This information appears at the top of receipts.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Brand Name (Header)')
                            ->placeholder('e.g., CONCHO')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('legal_name')
                            ->label('Legal Business Name')
                            ->placeholder('e.g., Finest Gem LLC'),


                        TextInput::make('tagline')
                            ->label('Store Tagline')
                            ->placeholder('e.g., Albuquerque\'s Finest Custom Jeweler')
                            ->columnSpanFull(),

                        TextInput::make('domain_url')
                            ->label('Website URL')
                            ->url()
                            ->placeholder('https://thediamondsq.com')
                            ->suffixIcon('heroicon-m-globe-alt')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Location Details')
    ->description('Search for an address to automatically fill the fields below.')
    ->schema([
        GoogleAutocomplete::make('address_search')
            ->label('Search Address')
            ->autocompletePlaceholder('Start typing address...')
            ->countries(['US'])
            ->columnSpanFull()
            ->withFields([
                TextInput::make('street')
                    ->label('Street Address')
                    ->extraInputAttributes(['data-google-field' => '{street_number} {route}']),

                TextInput::make('address_line_2')
                    ->label('Address 2 / Apt / Suite')
                    ->extraInputAttributes(['data-google-field' => 'subpremise']),

               TextInput::make('city')
                    ->label('City')
                    ->extraInputAttributes([
                        'data-google-field' => 'locality',  // ✅ Single field only
                        'data-google-value' => 'short_name'  // ✅ Gets "NYC" instead of "New York City"
                    ]),

                TextInput::make('state')
                    ->label('State')
                    ->extraInputAttributes(['data-google-field' => 'administrative_area_level_1'])
                    ->columnSpan(1),

                TextInput::make('postcode')
                    ->label('Zip Code')
                    ->extraInputAttributes(['data-google-field' => 'postal_code'])
                    ->columnSpan(1),

                TextInput::make('country')
                    ->label('Country')
                    ->extraInputAttributes(['data-google-field' => 'country'])
                    ->default('United States'),
            ]),
    ]),


                Section::make('Contact Information & Settings')->schema([
                    TextInput::make('phone')
                        ->label('Phone Number')
                        ->tel()
                        ->prefix('+1')
                        ->mask('999-999-9999')
                        ->dehydrateStateUsing(fn($state) => $state ? '+1' . preg_replace('/\D/', '', $state) : null),

                    TextInput::make('email')
                        ->label('Email Address')
                        ->email(),

                    Select::make('timezone')
                        ->label('Store Timezone')
                        ->options(collect(\DateTimeZone::listIdentifiers())->mapWithKeys(fn($tz) => [$tz => $tz])->toArray())
                        ->searchable()
                        ->default('America/Denver')
                        ->required(),
                ])->columns(2),

                Section::make('Branding & System')->schema([
                    FileUpload::make('logo_path')
                        ->label('Store Logo')
                        ->image()
                        ->directory('store-logos')
                        ->visibility('public'),

                    TextInput::make('crm_url')
                        ->label('CRM Portal Link')
                        ->url()
                        ->placeholder('https://crm.yourdomain.com')
                        ->suffixIcon('heroicon-m-link'),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Brand')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('legal_name')
                    ->label('Legal Entity')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('city')->sortable(),
                Tables\Columns\TextColumn::make('state')->sortable(),
                Tables\Columns\TextColumn::make('phone'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStores::route('/'),
            'create' => Pages\CreateStore::route('/create'),
            'edit' => Pages\EditStore::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $staff = \App\Helpers\Staff::user();
        return $staff?->hasAnyRole(['Superadmin', 'Administration']) ?? false;
    }
}
