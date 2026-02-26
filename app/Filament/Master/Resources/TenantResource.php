<?php

namespace App\Filament\Master\Resources;

use App\Filament\Master\Resources\TenantResource\Pages;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Store Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Store Infrastructure')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('id')
                            ->label('Database ID (Tenant ID)')
                            ->required()
                            ->disabled(fn ($record) => $record !== null)
                            ->placeholder('e.g. lxdiamond')
                            ->unique(ignoreRecord: true),
                            
                        Forms\Components\TextInput::make('domain')
                            ->label('Primary Domain')
                            ->required()
                            ->placeholder('lxdiamond.localhost')
                            ->helperText('This creates the web address for the store.'),
                    ]),

                // 🚀 Initial Admin Section (Only visible on Create)
                Forms\Components\Section::make('Initial Superadmin Account')
                    ->description('This user will be created automatically inside the new tenant database.')
                    ->visible(fn ($record) => $record === null) 
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('admin_name')
                            ->label('Full Name')
                            ->required()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('admin_email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('admin_password')
                            ->label('Password')
                            ->password()
                            ->required()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('admin_pin')
                            ->label('Access PIN')
                            ->required()
                            ->maxLength(4)
                            ->default('1234')
                            ->dehydrated(false),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Store ID')->searchable()->weight('bold'),

                TextColumn::make('domains.domain')
                    ->label('Web Address')
                    ->badge()
                    ->color('success')
                    ->url(function ($record) {
                        $domain = $record->domains->first()?->domain;
                        $protocol = app()->isLocal() ? 'http' : 'https';
                        $port = app()->isLocal() ? ':8001' : '';
                        return "{$protocol}://{$domain}{$port}/admin";
                    }, true),

                TextColumn::make('users_count')
                    ->label('Staff')
                    ->getStateUsing(fn (Tenant $record) => $record->run(fn () => \App\Models\User::count()))
                    ->badge(),

                TextColumn::make('support_info')
                    ->label('Admin Login')
                    ->description(fn (Tenant $record) => $record->run(function() {
                        $admin = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'Superadmin'))->first();
                        return $admin ? "User: {$admin->username} | PIN: {$admin->pin_code}" : 'No Admin Found';
                    }))
                    ->getStateUsing(fn (Tenant $record) => $record->run(function() {
                        return \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'Superadmin'))->value('email') ?? 'N/A';
                    }))
                    ->icon('heroicon-m-finger-print')
                    ->color('info'),

                TextColumn::make('created_at')->label('Launched')->dateTime()->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('emergency_reset')
                    ->label('Force Reset')
                    ->icon('heroicon-o-lifebuoy')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Tenant $record) {
                        $record->run(function () {
                            $admin = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'Superadmin'))->first();
                            if ($admin) {
                                $admin->update(['password' => Hash::make('jeweltag123'), 'pin_code' => '1234']);
                            }
                        });
                        Notification::make()->title('Reset Successful')->success()->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}