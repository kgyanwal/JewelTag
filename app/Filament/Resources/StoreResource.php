<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StoreResource\Pages;
use App\Models\Store;
use App\Helpers\LocationHelper;
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
                Section::make('Store Identity')->schema([
                    TextInput::make('name')
                        ->label('Store Name')
                        ->required()
                        ->maxLength(255),
                    
                    TextInput::make('domain_url')
                        ->label('Website Domain')
                        ->url()
                        ->suffixIcon('heroicon-m-globe-alt')
                        ->columnSpanFull(),
                ])->columns(2),

                Section::make('Location Details')->schema([
                    // 1. THE SEARCH TRIGGER
                    GoogleAutocomplete::make('address_search')
    ->label('Search Address')
    ->autocompletePlaceholder('Start typing...')
    ->countries(['US'])
  
    ->withFields([
        TextInput::make('address_line_1')
            ->extraInputAttributes(['data-google-field' => '{street_number} {route}']),
        TextInput::make('city')
            ->extraInputAttributes(['data-google-field' => 'locality']),
        Select::make('state')
            ->options(LocationHelper::getUsStates())
            ->extraInputAttributes(['data-google-field' => 'administrative_area_level_1_short']),
        TextInput::make('zip_code')
            ->extraInputAttributes(['data-google-field' => 'postal_code']),
    ])
                ])->columns(2),

                Section::make('Contact Information')->schema([
                    TextInput::make('phone')
                        ->label('Phone Number')
                        ->tel()
                        ->prefix('+1')
                        ->mask('999-999-9999')
                        // Persistent +1 logic
                        ->dehydrateStateUsing(fn ($state) => $state ? '+1' . preg_replace('/\D/', '', $state) : null),
                        
                    TextInput::make('email')
                        ->label('Email Address')
                        ->email(),
                ])->columns(2),

                Section::make('Branding')->schema([
                    FileUpload::make('logo_path')
                        ->label('Store Logo')
                        ->image()
                        ->directory('store-logos')
                        ->columnSpanFull(),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('city')->sortable(),
                Tables\Columns\TextColumn::make('state')->sortable(),
                Tables\Columns\TextColumn::make('phone'),
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStores::route('/'),
            'create' => Pages\CreateStore::route('/create'),
            'edit' => Pages\EditStore::route('/{record}/edit'),
        ];
    }
}