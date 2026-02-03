<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StoreResource\Pages;
use App\Models\Store;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\ImageColumn;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationGroup = 'Administration';

  public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Section::make('Store Details')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Store Name')
                    ->required()
                    ->placeholder('e.g. Head Office')
                    ->maxLength(255),

                // CHANGED: 'address' -> 'location' (Matches your database)
                Forms\Components\TextInput::make('location') 
                    ->label('Address / Location')
                    ->placeholder('123 Main St'),
                
                Forms\Components\TextInput::make('phone')
                    ->label('Phone Number'),

                    FileUpload::make('logo_path')
    ->label('Store Logo/Image')
    ->image()
    ->directory('store-logos')
    ->visibility('public')
    ->disk('public')
    ->columnSpanFull(),
            ])
        ]);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            ImageColumn::make('logo_path')
                ->label('Logo')
                ->disk('public') // IMPORTANT
                ->height(50)
                ->width(50)
                ->circular()
                ->defaultImageUrl(asset('images/store-placeholder.png')),

            Tables\Columns\TextColumn::make('name')
                ->label('Store Name')
                ->sortable()
                ->searchable(),

            Tables\Columns\TextColumn::make('location')
                ->label('Address')
                ->searchable(),

            Tables\Columns\TextColumn::make('phone')
                ->label('Phone'),
        ])
        ->filters([])
        ->actions([
            Tables\Actions\EditAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
}


public static function canAccess(): bool
{
    // 🔹 Only allow users with the 'super_admin' role to access Store management
    return auth()->user()->hasRole('Superadmin');
}
   public static function shouldRegisterNavigation(): bool
{
    // 🔹 Use your Staff helper to check the identity of the person who entered the PIN
    $staff = \App\Helpers\Staff::user();

    // Only allow specific roles to see the Administration menu
    return $staff?->hasAnyRole(['Superadmin', 'Administration']) ?? false;
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