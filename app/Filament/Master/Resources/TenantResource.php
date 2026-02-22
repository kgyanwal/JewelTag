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
                    ->description('Manage the core database and domain settings for this store.')
                    ->schema([
                        Forms\Components\TextInput::make('id')
                            ->label('Database ID (Tenant ID)')
                            ->required()
                            ->disabled(fn ($record) => $record !== null)
                            ->unique(ignoreRecord: true),
                            
                        Forms\Components\TextInput::make('domain')
                            ->label('Primary Domain')
                            ->required()
                            ->placeholder('storename.localhost')
                            ->helperText('Changing this here will update the associated domain record.'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Store ID')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('domains.domain')
                    ->label('Web Address')
                    ->badge()
                    ->color('success')
                    ->url(fn ($record) => "http://" . $record->domains->first()?->domain . ":8001/admin", true),

                // 🚀 Total Staff Count
                TextColumn::make('users_count')
                    ->label('Staff')
                    ->getStateUsing(fn (Tenant $record) => $record->run(fn () => \App\Models\User::count()))
                    ->badge(),

                // 🚀 Support Data: Superadmin Credentials
                TextColumn::make('support_info')
                    ->label('Admin Login (Support Only)')
                    ->description(fn (Tenant $record) => $record->run(function() {
                        $admin = \App\Models\User::role('Superadmin')->first();
                        return $admin ? "User: {$admin->username} | PIN: " . ($admin->pin_code ?? 'N/A') : 'No Admin Found';
                    }))
                    ->getStateUsing(fn (Tenant $record) => $record->run(fn () => \App\Models\User::role('Superadmin')->first()?->email))
                    ->icon('heroicon-m-finger-print')
                    ->color('info'),

                TextColumn::make('created_at')
                    ->label('Launched')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                // 🚀 Emergency Support Action
                Action::make('emergency_reset')
                    ->label('Force Reset')
                    ->icon('heroicon-o-lifebuoy')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reset Client Credentials')
                    ->modalDescription('This will reset the Superadmin password to "jeweltag123" and PIN to "1234". Use this only for support requests.')
                    ->action(function (Tenant $record) {
                        $record->run(function () {
                            $admin = \App\Models\User::role('Superadmin')->first();
                            if ($admin) {
                                $admin->update([
                                    'password' => bcrypt('jeweltag123'),
                                    'pin_code' => '1234'
                                ]);
                            }
                        });
                        Notification::make()->title('Credentials Reset Successfully')->success()->send();
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