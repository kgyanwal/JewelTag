<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Administration';

    public static function form(Form $form): Form
    {
        return $form->schema([
           Forms\Components\Section::make('Staff Profile')
    ->description('Identity is mapped via PIN. Universal Login is managed by the Superadmin.')
    ->schema([
        Forms\Components\TextInput::make('name')
            ->label('Staff Full Name')
            ->required()
            ->maxLength(255),
            
        Forms\Components\TextInput::make('pin_code')
            ->label('Terminal PIN')
            ->password()
            ->revealable()
            ->numeric()
            ->length(4)
            ->required()
            ->unique(ignoreRecord: true)
            ->helperText('4-digit code to identify this user after universal login.'),
            
        Forms\Components\TextInput::make('password')
            ->label('Account Password')
            ->password()
            ->revealable()
            ->placeholder('Leave blank to keep current password')
            ->helperText('Enter new password to change, leave blank to keep current')
            ->dehydrated(fn ($state) => filled($state))
            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
            ->rule('nullable')
            ->maxLength(255),
            
        Forms\Components\Select::make('roles')
            ->relationship('roles', 'name')
            ->multiple()
            ->preload()
            ->label('Assign Access Role'),
            
        Forms\Components\Toggle::make('is_active')
            ->label('Account Enabled')
            ->default(true),
    ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('pin_code')->label('PIN')->badge()->color('warning'),
            Tables\Columns\TextColumn::make('roles.name')->badge()->label('Role Name'),
            Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
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
    // 🔹 Use your Staff helper to check the identity of the person who entered the PIN
    $staff = \App\Helpers\Staff::user();

    // Only allow specific roles to see the Administration menu
    return $staff?->hasAnyRole(['Superadmin', 'Administration']) ?? false;
}

    // 🔹 Master credentials action
    public static function getHeaderActions(): array
    {
        return [
            Action::make('addMasterCredentials')
                ->label('Master Credentials')
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->visible(fn () => auth()->user()->hasRole('Superadmin'))
                ->modalHeading('Universal Terminal Credentials')
                ->modalDescription('Update the universal email/password used for browser login.')
                ->form([
                    Forms\Components\TextInput::make('email')->label('Universal Login Email')->email()->required(),
                    Forms\Components\TextInput::make('password')->label('Universal Login Password')->password()->revealable()->required(),
                ])
                ->fillForm(fn () => ['email' => auth()->user()->email])
                ->action(function ($data) {
                    $master = auth()->user();
                    $master->update([
                        'email' => $data['email'],
                        'password' => Hash::make($data['password']),
                    ]);

                    Notification::make()->title('Universal Credentials Updated')->success()->send();
                }),
        ];
    }
}
