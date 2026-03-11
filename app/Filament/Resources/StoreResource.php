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
use Illuminate\Support\Facades\Log;
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
                        ->placeholder('https://thediamondsq.com')
                        ->suffixIcon('heroicon-m-globe-alt')
                        ->columnSpanFull(),
                ])->columns(2),

             Section::make('Location Details')
    ->schema([
        GoogleAutocomplete::make('address_search')
            ->label('Search Address')
            ->autocompletePlaceholder('Start typing address...')
            ->countries(['US'])
            ->dehydrated(false)
            // 🚀 THE FIX: Pass the actual component objects, not strings
            ->withFields([
                TextInput::make('city')
                    ->label('City')
                    ->placeholder('City will auto-fill'),
                
                TextInput::make('state')
                    ->label('State')
                    ->placeholder('State will auto-fill'),
            ], 'locality', 'administrative_area_level_1'), 
            // The 2nd and 3rd arguments map the children to Google's locality/state keys
    ])->columns(1), // Columns(1) looks better when fields are nested
                Section::make('Timezone Settings')->schema([

                    Select::make('timezone')
                        ->label('Store Timezone')
                        ->options(
                            collect(\DateTimeZone::listIdentifiers())
                                ->mapWithKeys(fn($tz) => [$tz => $tz])
                                ->toArray()
                        )
                        ->searchable()
                        ->default('America/Denver')
                        ->required()
                        ->helperText('This timezone will control store time display'),

                ])->columns(1),

                Section::make('Contact Information')->schema([
                    TextInput::make('phone')
                        ->label('Phone Number')
                        ->tel()
                        ->prefix('+1')
                        ->mask('999-999-9999')
                        ->dehydrateStateUsing(
                            fn($state) => $state
                                ? '+1' . preg_replace('/\D/', '', $state)
                                : null
                        ),

                    TextInput::make('email')
                        ->label('Email Address')
                        ->email(),
                ])->columns(2),

                Section::make('Branding')->schema([
                    FileUpload::make('logo_path')
                        ->label('Store Logo')
                        ->image()
                        ->directory('store-logos')
                        ->visibility('public')
                        ->columnSpanFull(),
                ]),
                TextInput::make('crm_url')
                    ->label('CRM Portal Link')
                    ->url()
                    ->placeholder('https://crm.yourdomain.com')
                    ->suffixIcon('heroicon-m-link')
                    ->helperText('The dynamic link used for the dashboard CRM tile.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('city')->sortable(),
                Tables\Columns\TextColumn::make('state')->sortable(),
                Tables\Columns\TextColumn::make('timezone'),
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
        // 🔹 Use your Staff helper to check the identity of the person who entered the PIN
        $staff = \App\Helpers\Staff::user();

        // Only allow specific roles to see the Administration menu
        return $staff?->hasAnyRole(['Superadmin', 'Administration']) ?? false;
    }
}
