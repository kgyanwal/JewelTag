<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationLabel = 'Permissions';
    protected static ?string $navigationGroup = 'Admin';
    protected static ?string $modelLabel = 'Permission';
    protected static ?string $pluralModelLabel = 'Permissions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Permission Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Permission Name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('e.g., view.dashboard, create.sale'),
                        
                        Forms\Components\Select::make('guard_name')
                            ->label('Guard')
                            ->options([
                                'web' => 'Web',
                                'api' => 'API',
                            ])
                            ->default('web')
                            ->required(),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->placeholder('What does this permission allow?')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Permission')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('guard_name')
                    ->label('Guard')
                    ->badge(),
                
                Tables\Columns\TextColumn::make('roles_count')
                    ->label('Roles')
                    ->counts('roles')
                    ->sortable()
                    ->alignCenter(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }
    
    // TEMPORARY: Allow access to everyone
     public static function shouldRegisterNavigation(): bool
{
    // 🔹 Use your Staff helper to check the identity of the person who entered the PIN
    $staff = \App\Helpers\Staff::user();

    // Only allow specific roles to see the Administration menu
    return $staff?->hasAnyRole(['Superadmin']) ?? false;
}

}