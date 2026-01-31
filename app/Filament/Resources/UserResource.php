<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Users';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?string $modelLabel = 'User';
    protected static ?string $pluralModelLabel = 'Users';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('User Configuration')
                    ->tabs([
                        Tabs\Tab::make('Personal Details')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Section::make('Account Information')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Full Name')
                                            ->required()
                                            ->maxLength(255),
                                        
                                        Forms\Components\TextInput::make('email')
                                            ->label('Email Address')
                                            ->email()
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255),
                                        
                                        Forms\Components\TextInput::make('employee_code')
                                            ->label('Employee ID')
                                            ->unique(ignoreRecord: true)
                                            ->prefix('EMP-')
                                            ->maxLength(50),
                                        
                                        Forms\Components\TextInput::make('phone')
                                            ->label('Phone Number')
                                            ->tel()
                                            ->maxLength(20),
                                    ])->columns(2),
                                
                                Section::make('Security')
                                    ->schema([
                                        Forms\Components\TextInput::make('password')
                                            ->label('Password')
                                            ->password()
                                            ->revealable()
                                            ->required(fn ($operation) => $operation === 'create')
                                            ->rule(Password::default())
                                            ->dehydrated(fn ($state) => filled($state))
                                            ->dehydrateStateUsing(fn ($state) => 
                                                Hash::make($state)
                                            ),
                                        
                                        Forms\Components\TextInput::make('pin_code')
                                            ->label('POS PIN Code')
                                            ->password()
                                            ->revealable()
                                            ->numeric()
                                            ->length(4)
                                            ->helperText('4-digit code for POS access'),
                                    ])->columns(2),
                            ]),
                        
                        Tabs\Tab::make('Role & Permissions')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Section::make('Role Assignment')
                                    ->schema([
                                        Forms\Components\Select::make('roles')
                                            ->relationship('roles', 'name')
                                            ->multiple()
                                            ->preload()
                                            ->searchable()
                                            ->label('Assign Roles')
                                            ->helperText('Select one or more roles for this user'),
                                        
                                        // Removed Store relation if Store model doesn't exist yet
                                        // If you have a Store model, uncomment below:
                                        /* Forms\Components\Select::make('store_id')
                                            ->relationship('store', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->label('Assigned Store'),
                                        */
                                    ]),
                                
                                Section::make('Permissions')
                                    ->description('Direct permission assignment (overrides roles)')
                                    ->collapsible()
                                    ->schema([
                                        Forms\Components\Select::make('permissions')
                                            ->relationship('permissions', 'name')
                                            ->multiple()
                                            ->preload()
                                            ->searchable()
                                            ->label('Additional Permissions'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->email),
                
                Tables\Columns\TextColumn::make('employee_code')
                    ->label('Employee ID')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->separator(',')
                    ->colors([
                        'primary', 'success', 'warning', 'danger', 'info'
                    ])
                    ->limitList(2)
                    ->expandableLimitedList(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->label('Filter by Role'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-o-eye'),
                    
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil'),
                    
                    Tables\Actions\DeleteAction::make()
                        ->icon('heroicon-o-trash'),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            
        ];
    }
public static function shouldRegisterNavigation(): bool
{
    // 🔹 Only allow Superadmins to see this in the menu
    return auth()->user()->hasRole('Superadmin');
}
    // TEMPORARY: Allow access to everyone
    
}