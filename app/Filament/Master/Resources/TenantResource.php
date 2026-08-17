<?php

namespace App\Filament\Master\Resources;

use App\Filament\Master\Resources\TenantResource\Pages;
use App\Models\Tenant;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
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
                            ->disabled(fn($record) => $record !== null)
                            ->placeholder('e.g. lxdiamond')
                            ->minLength(3)
                            ->regex('/^[a-z0-9]+$/')
                            ->validationMessages([
                                'regex' => 'The Store ID can only contain lowercase letters and numbers (no spaces or symbols allowed).',
                                'min' => 'The Store ID must be at least 3 characters long to prevent accidental typos.',
                            ])
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('domain')
                            ->label('Primary Domain')
                            ->required()
                            ->placeholder('lxdiamond.localhost')
                            ->helperText('This creates the web address for the store.'),
                    ]),

                Forms\Components\Section::make('Initial Superadmin Account')
                    ->description('This user will be created automatically inside the new tenant database.')
                    ->visible(fn($record) => $record === null)
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
                    ]),

                Forms\Components\Section::make('Plan & Subscription')
                    ->icon('heroicon-o-credit-card')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('plan_id')
                            ->label('Assigned Plan')
                            ->options(fn () => Plan::where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'))
                            ->required()
                            ->native(false)
                            ->live()
                            ->default(fn () => Plan::where('slug', 'pro')->value('id')),

                        Forms\Components\Select::make('plan_status')
                            ->label('Subscription Status')
                            ->options([
                                'trial'     => '⏳ Trial',
                                'active'    => '✅ Active',
                                'suspended' => '🚫 Suspended',
                                'cancelled' => '❌ Cancelled',
                            ])
                            ->default('trial')
                            ->required()
                            ->native(false)
                            ->live(),

                        Forms\Components\DatePicker::make('trial_ends_at')
                            ->label('Trial Ends')
                            ->visible(fn (Get $get) => $get('plan_status') === 'trial')
                            ->default(now()->addDays(3)),

                        Forms\Components\DatePicker::make('plan_expires_at')
                            ->label('Plan Expiry Date')
                            ->visible(fn (Get $get) => in_array($get('plan_status'), ['active', 'suspended']))
                            ->helperText('Leave blank for no expiry'),

                        Forms\Components\Textarea::make('suspension_reason')
                            ->label('Suspension Reason')
                            ->visible(fn (Get $get) => $get('plan_status') === 'suspended')
                            ->columnSpanFull()
                            ->rows(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Store ID')->searchable()->weight('bold'),

                TextColumn::make('plan.name')
                    ->label('Plan')
                    ->badge()
                    ->color(fn ($record) => match ($record->plan?->slug) {
                        'basic'      => 'warning',
                        'pro'        => 'success',
                        'enterprise' => 'info',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state ?? 'No Plan'),

                TextColumn::make('plan_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active'    => 'success',
                        'trial'     => 'warning',
                        'suspended' => 'danger',
                        'cancelled' => 'gray',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'active'    => '✅ Active',
                        'trial'     => '⏳ Trial',
                        'suspended' => '🚫 Suspended',
                        'cancelled' => '❌ Cancelled',
                        default     => ucfirst($state ?? '—'),
                    }),

                TextColumn::make('trial_ends_at')
                    ->label('Trial Ends')
                    ->visible(fn ($record) => $record?->plan_status === 'trial')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '—';
                        $daysLeft = now()->diffInDays($state, false);
                        if ($daysLeft < 0) return '⚠️ Expired';
                        if ($daysLeft === 0) return '🔴 Today';
                        return "{$daysLeft} day(s) left";
                    })
                    ->badge()
                    ->color(fn ($state) => $state && now()->diffInDays($state, false) <= 1 ? 'danger' : 'warning'),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('System Status')
                    ->onColor('success')
                    ->offColor('danger')
                    ->afterStateUpdated(function ($record, $state) {
                        $status = $state ? 'activated' : 'suspended';
                        Notification::make()
                            ->title("Store {$status}")
                            ->body("Store {$record->id} has been {$status}.")
                            ->success()
                            ->send();
                    }),

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
                    ->getStateUsing(fn(Tenant $record) => $record->run(fn() => \App\Models\User::count()))
                    ->badge(),

                TextColumn::make('support_info')
                    ->label('Admin Login')
                    ->description(fn(Tenant $record) => $record->run(function () {
                        $admin = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'Superadmin'))->first();
                        return $admin ? "User: {$admin->username} | PIN: {$admin->pin_code}" : 'No Admin Found';
                    }))
                    ->getStateUsing(fn(Tenant $record) => $record->run(function () {
                        return \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'Superadmin'))->value('email') ?? 'N/A';
                    }))
                    ->icon('heroicon-m-finger-print')
                    ->color('info'),

                TextColumn::make('db_size')
                    ->label('Database Size')
                    ->getStateUsing(function (Tenant $record) {
                        try {
                            $dbName = $record->tenancy_db_name;
                            $result = \Illuminate\Support\Facades\DB::connection('mysql')->select("
                                SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'size' 
                                FROM information_schema.tables 
                                WHERE table_schema = ?
                            ", [$dbName]);
                            $size = $result[0]->size ?? 0;
                            return $size > 0 ? "{$size} MB" : 'N/A';
                        } catch (\Exception $e) {
                            return 'Error';
                        }
                    })
                    ->badge()
                    ->color(function (Tenant $record) {
                        try {
                            $dbName = $record->tenancy_db_name;
                            $result = \Illuminate\Support\Facades\DB::connection('mysql')->select("
                                SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'size' 
                                FROM information_schema.tables WHERE table_schema = ?
                            ", [$dbName]);
                            $size = $result[0]->size ?? 0;
                            return $size > 500 ? 'danger' : 'info';
                        } catch (\Exception $e) { return 'gray'; }
                    })
                    ->icon('heroicon-o-circle-stack'),

                TextColumn::make('storage_size')
                    ->label('File Storage')
                    ->getStateUsing(function (Tenant $record) {
                        try {
                            $sizeInBytes = 0;
                            $prefix = config('tenancy.filesystem.suffix_base', 'tenant') . $record->id;
                            $possiblePaths = [
                                storage_path($prefix),
                                storage_path("app/public/{$prefix}"),
                                storage_path("app/{$prefix}"),
                                public_path($prefix),
                            ];
                            foreach ($possiblePaths as $path) {
                                if (\Illuminate\Support\Facades\File::isDirectory($path)) {
                                    $files = \Illuminate\Support\Facades\File::allFiles($path);
                                    foreach ($files as $file) {
                                        $sizeInBytes += $file->getSize();
                                    }
                                }
                            }
                            if ($sizeInBytes === 0) {
                                return '0 MB';
                            }
                            $sizeInMb = round($sizeInBytes / 1024 / 1024, 2);
                            if ($sizeInMb > 1000) {
                                return round($sizeInMb / 1024, 2) . ' GB';
                            }
                            return "{$sizeInMb} MB";
                        } catch (\Exception $e) {
                            return 'Error';
                        }
                    })
                    ->badge()
                    ->color(fn ($state) => $state === '0 MB' ? 'gray' : 'warning')
                    ->icon('heroicon-o-folder-open'),

                TextColumn::make('created_at')->label('Launched')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan_id')
                    ->label('Plan')
                    ->options(fn () => Plan::where('is_active', true)->orderBy('sort_order')->pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('plan_status')
                    ->label('Status')
                    ->options(['trial' => 'Trial', 'active' => 'Active', 'suspended' => 'Suspended', 'cancelled' => 'Cancelled']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Action::make('change_plan')
                    ->label('Change Plan')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('plan_id')
                            ->label('New Plan')
                            ->options(fn () => Plan::where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'))
                            ->required()->native(false),
                        Forms\Components\Select::make('plan_status')
                            ->label('Status')
                            ->options(['trial' => 'Trial', 'active' => 'Active', 'suspended' => 'Suspended'])
                            ->default('active')->required()->native(false),
                    ])
                    ->fillForm(fn (Tenant $record) => [
                        'plan_id'     => $record->plan_id,
                        'plan_status' => $record->plan_status,
                    ])
                    ->action(function (Tenant $record, array $data) {
                        $record->update([
                            'plan_id'     => $data['plan_id'],
                            'plan_status' => $data['plan_status'],
                        ]);
                        Notification::make()->title('Plan Updated')->success()->send();
                    }),

                // ── NEW: Extend Trial ──────────────────────────────────
                Action::make('extend_trial')
                    ->label('Extend Trial')
                    ->icon('heroicon-o-clock')
                    ->color('info')
                    ->visible(fn (Tenant $record) => $record->plan_status === 'trial' || $record->plan_status === 'suspended')
                    ->form([
                        Forms\Components\Select::make('extend_days')
                            ->label('Extend By')
                            ->options([
                                1  => '+1 day',
                                3  => '+3 days',
                                7  => '+7 days',
                                14 => '+14 days',
                                30 => '+30 days',
                            ])
                            ->default(3)
                            ->required()
                            ->native(false),
                    ])
                    ->action(function (Tenant $record, array $data) {
                        $base = $record->trial_ends_at && $record->trial_ends_at->isFuture()
                            ? $record->trial_ends_at
                            : now();

                        $record->update([
                            'trial_ends_at'      => $base->addDays((int) $data['extend_days']),
                            'plan_status'        => 'trial',
                            'suspended_at'       => null,
                            'suspension_reason'  => null,
                        ]);

                        Notification::make()
                            ->title('Trial Extended')
                            ->body("New trial end date: {$record->fresh()->trial_ends_at->format('M j, Y')}")
                            ->success()
                            ->send();
                    }),

                Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn (Tenant $record) => $record->plan_status !== 'suspended')
                    ->form([
                        Forms\Components\Textarea::make('reason')->label('Reason')->required()->rows(2),
                    ])
                    ->requiresConfirmation()
                    ->action(function (Tenant $record, array $data) {
                        $record->update([
                            'plan_status'       => 'suspended',
                            'suspended_at'      => now(),
                            'suspension_reason' => $data['reason'],
                        ]);
                        Notification::make()->title('Tenant Suspended')->warning()->send();
                    }),

                Action::make('reactivate')
                    ->label('Reactivate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Tenant $record) => $record->plan_status === 'suspended')
                    ->requiresConfirmation()
                    ->action(function (Tenant $record) {
                        $record->update(['plan_status' => 'active', 'suspended_at' => null, 'suspension_reason' => null]);
                        Notification::make()->title('Tenant Reactivated')->success()->send();
                    }),

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

                Tables\Actions\DeleteAction::make()
                    ->label('Archive Store')
                    ->icon('heroicon-o-archive-box')
                    ->color('danger')
                    ->modalHeading('Archive Store?')
                    ->modalDescription('Are you sure you want to archive this store? The store will be hidden and disabled, but the database and all its records will remain safely intact. You can restore it later.')
                    ->modalSubmitActionLabel('Yes, archive it')
                    ->successNotificationTitle('Store successfully archived'),
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