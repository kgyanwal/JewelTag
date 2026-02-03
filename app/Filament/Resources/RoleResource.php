<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Resources\RoleResource\RelationManagers\PermissionsRelationManager;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'User Roles';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?string $modelLabel = 'Role';
    protected static ?string $pluralModelLabel = 'Roles';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Role Information')
                    ->description('Define role name and basic permissions')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Role Name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('e.g., Administration, Manager, Sales')
                            ->afterStateUpdated(fn ($state, callable $set) => 
                                $set('guard_name', 'web')
                            ),
                        
                        Forms\Components\Select::make('guard_name')
                            ->label('Guard')
                            ->options([
                                'web' => 'Web',
                                'api' => 'API',
                            ])
                            ->default('web')
                            ->required(),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Role Description')
                            ->rows(2)
                            ->placeholder('Describe what this role can do...')
                            ->columnSpanFull(),
                    ])->columns(2),
                
                Section::make('Module Permissions')
                    ->description('Select what modules this role can access')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\CheckboxList::make('module_permissions')
                                ->label('Main Modules')
                                ->options([
                                    'dashboard' => 'Dashboard',
                                    'sales' => 'Sales (POS)',
                                    'customers' => 'Customers',
                                    'inventory' => 'Inventory',
                                    'suppliers' => 'Suppliers',
                                    'reports' => 'Reports',
                                    'staff' => 'Staff Management',
                                    'settings' => 'System Settings',
                                ])
                                ->columns(2)
                                ->bulkToggleable(),
                        ]),
                    ]),
                
                Section::make('Advanced Permissions')
                    ->description('Fine-grained permission control')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Select::make('permissions')
                            ->relationship('permissions', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->label('Select Permissions')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('guard_name')
                                    ->options(['web' => 'Web'])
                                    ->default('web'),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Role Name')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => match($record->name) {
                        'Superadmin' => 'danger',
                        'Administration' => 'primary',
                        'Manager' => 'success',
                        'Advanced Sales' => 'warning',
                        'Sales' => 'info',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(30)
                    ->tooltip(function ($record) {
                        return $record->description;
                    }),
                
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->sortable()
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions')
                    ->sortable()
                    ->alignCenter(),
                
                Tables\Columns\TextColumn::make('guard_name')
                    ->label('Guard')
                    ->badge()
                    ->color(fn ($state) => $state === 'web' ? 'success' : 'warning'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('guard_name')
                    ->options([
                        'web' => 'Web',
                        'api' => 'API',
                    ]),
                
                Tables\Filters\Filter::make('system_roles')
                    ->label('System Roles Only')
                    ->query(fn ($query) => 
                        $query->whereIn('name', ['Superadmin', 'Administration', 'Manager'])
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-o-eye'),
                    
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil'),
                    
                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplicate')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->action(function ($record) {
                            $newRole = $record->replicate();
                            $newRole->name = $record->name . ' Copy';
                            $newRole->save();
                            
                            // Copy permissions
                            $newRole->syncPermissions($record->permissions);
                        }),
                    
                    Tables\Actions\DeleteAction::make()
                        ->icon('heroicon-o-trash'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('assignPermissions')
                        ->label('Assign Permissions')
                        ->icon('heroicon-o-key')
                        ->form([
                            Select::make('permissions')
                                ->relationship('permissions', 'name')
                                ->multiple()
                                ->preload()
                                ->searchable(),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->syncPermissions($data['permissions']);
                            }
                        }),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [
            PermissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
            'view' => Pages\ViewRole::route('/{record}'),
        ];
    }
    public static function canViewAny(): bool
{
    // Only show this resource in the sidebar if the user can view users
    return auth()->user()->hasPermissionTo('view.users');
}
   public static function shouldRegisterNavigation(): bool
{
    // 🔹 Use your Staff helper to check the identity of the person who entered the PIN
    $staff = \App\Helpers\Staff::user();

    // Only allow specific roles to see the Administration menu
    return $staff?->hasAnyRole(['Superadmin', 'Administration']) ?? false;
}

    // TEMPORARY: Allow access to everyone to prevent lockout during setup
    
}