<?php

namespace App\Filament\Master\Resources;

use App\Filament\Master\Resources\TenantResource\Pages;
use App\Models\Tenant;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use App\Filament\Master\Resources\SubscriptionResource;
class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Store Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ── STORE INFRASTRUCTURE ─────────────────────────────────────
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
                                'regex' => 'The Store ID can only contain lowercase letters and numbers.',
                                'min'   => 'The Store ID must be at least 3 characters long.',
                            ])
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('domain')
                            ->label('Primary Domain')
                            ->required()
                            ->placeholder('lxdiamond.localhost')
                            ->helperText('This creates the web address for the store.'),
                    ]),

                // ── INITIAL SUPERADMIN ────────────────────────────────────────
                Forms\Components\Section::make('Initial Superadmin Account')
                    ->description('This user will be created automatically inside the new tenant database.')
                    ->visible(fn($record) => $record === null)
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('admin_name')
                            ->label('Full Name')->required()->dehydrated(false),
                        Forms\Components\TextInput::make('admin_email')
                            ->label('Email Address')->email()->required()->dehydrated(false),
                        Forms\Components\TextInput::make('admin_password')
                            ->label('Password')->password()->required()->dehydrated(false),
                        Forms\Components\TextInput::make('admin_pin')
                            ->label('Access PIN')->required()->maxLength(4)->default('1234')->dehydrated(false),
                    ]),

                                 // ── PLAN & SUBSCRIPTION ───────────────────────────────────────
                Forms\Components\Section::make('Plan & Subscription')
                    ->icon('heroicon-o-credit-card')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('plan_id')
                            ->label('Assigned Plan')
                            ->options(fn() => Plan::where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'))
                            ->required()->native(false)->live()
                            ->default(fn() => Plan::where('slug', 'pro')->value('id')),

                        Forms\Components\Select::make('plan_status')
                            ->label('Subscription Status')
                            ->options([
                                'trial'     => '⏳ Trial',
                                'active'    => '✅ Active',
                                'suspended' => '🚫 Suspended',
                                'cancelled' => '❌ Cancelled',
                            ])
                            ->default('trial')->required()->native(false)->live(),

                        Forms\Components\DatePicker::make('trial_ends_at')
                            ->label('Trial Ends')
                            ->visible(fn(Get $get) => $get('plan_status') === 'trial')
                            ->default(now()->addDays(3)),

                        Forms\Components\DatePicker::make('plan_expires_at')
                            ->label('Plan Expiry Date')
                            ->visible(fn(Get $get) => in_array($get('plan_status'), ['active', 'suspended']))
                            ->helperText('Leave blank for no expiry'),

                        Forms\Components\Textarea::make('suspension_reason')
                            ->label('Suspension Reason')
                            ->visible(fn(Get $get) => $get('plan_status') === 'suspended')
                            ->columnSpanFull()->rows(2),

                        // 🚀 NEW — billing cycle for the auto-created Subscription record.
                        Forms\Components\Select::make('billing_cycle')
                            ->label('Billing Cycle')
                            ->options(['monthly' => 'Monthly', 'annually' => 'Annually (Save 20%)'])
                            ->default('monthly')
                            ->required()
                            ->visible(fn($record) => $record === null)
                            ->native(false),
                    ]),

                // 🚀 NEW — every new tenant now MUST have a License and a signed
                // Subscription Agreement (with digital signature: name + title)
                // before it can be created.
                Forms\Components\Section::make('License & Legal Agreement')
                    ->icon('heroicon-o-key')
                    ->description('Required for every new store. A License record and a signed Master Subscription Agreement are created automatically alongside this tenant.')
                    ->visible(fn($record) => $record === null)
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('license_plan')
                            ->label('License Tier')
                            ->options([
                                'essential'    => 'Essential',
                                'professional' => 'Professional',
                                'enterprise'   => 'Enterprise',
                            ])
                            ->default('professional')
                            ->required()
                            ->native(false),

                        Forms\Components\DateTimePicker::make('license_expires_at')
                            ->label('License Valid Until')
                            ->default(now()->addYear())
                            ->required()
                            ->native(false),

                        Forms\Components\Actions::make([
                            \Filament\Forms\Components\Actions\Action::make('view_and_sign_agreement')
                                ->label(fn(Get $get) => $get('msa_agreed_at') ? '📄 Agreement Signed' : '📄 View & Sign Subscription Agreement')
                                ->color(fn(Get $get) => $get('msa_agreed_at') ? 'success' : 'danger')
                                ->button()
                                ->extraAttributes(['style' => 'width:100%;'])
                                ->modalHeading('JewelTag Software License and Subscription Agreement')
                                ->modalWidth('4xl')
                                ->modalSubmitActionLabel('I Agree — Sign Agreement')
                                ->modalCancelActionLabel('Close')
                                ->fillForm(fn(Get $get) => [
                                    'agreement_checkbox' => (bool) $get('msa_agreed_at'),
                                    'signer_name'         => $get('msa_signer_name'),
                                    'signer_title'        => $get('msa_signer_title'),
                                ])
                                ->form([
                                    Forms\Components\Placeholder::make('agreement_scroll_hint')
                                        ->hiddenLabel()
                                        ->content(new \Illuminate\Support\HtmlString("
                                            <div style='display:flex;align-items:center;justify-content:center;gap:6px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:8px 12px;margin-bottom:8px;font-size:12px;color:#92400e;font-weight:700;'>
                                                ⬇ Scroll down to read the full agreement before signing ⬇
                                            </div>
                                        ")),
                                    Forms\Components\Placeholder::make('agreement_full_text')
                                        ->hiddenLabel()
                                        ->content(fn() => new \Illuminate\Support\HtmlString(SubscriptionResource::getAgreementHtml())),
                                    Forms\Components\Checkbox::make('agreement_checkbox')
                                        ->label('I have read and agree to the terms of this Software License and Subscription Agreement, including all Exhibits, on behalf of this store.')
                                        ->required()
                                        ->accepted()
                                        ->live()
                                        ->extraAttributes(['style' => 'margin-top:12px;']),

                                    // 🚀 NEW — digital signature capture
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('signer_name')
                                                ->label('Full Legal Name')
                                                ->placeholder('e.g. Jane Smith')
                                                ->required(),
                                            Forms\Components\TextInput::make('signer_title')
                                                ->label('Title / Position')
                                                ->placeholder('e.g. Owner, General Manager')
                                                ->required(),
                                        ])
                                        ->columnSpanFull(),
                                ])
                                ->action(function (array $data, \Filament\Forms\Set $set) {
                                    if (empty($data['agreement_checkbox'])) return;
                                    $set('msa_agreed_at', now()->toDateTimeString());
                                    $set('msa_agreed_ip', request()->ip());
                                    $set('msa_signer_name', $data['signer_name']);
                                    $set('msa_signer_title', $data['signer_title']);
                                    Notification::make()
                                        ->title('Agreement Signed')
                                        ->body("Signed by {$data['signer_name']} ({$data['signer_title']}). Create the store to finalize the Subscription and License records.")
                                        ->success()
                                        ->send();
                                }),
                        ])->columnSpanFull(),

                        // 🚀 NEW — shows the actual signed signature block once signed
                        Forms\Components\Placeholder::make('signature_block_display')
                            ->hiddenLabel()
                            ->live()
                            ->visible(fn(Get $get) => (bool) $get('msa_agreed_at') && $get('msa_signer_name'))
                            ->content(function (Get $get) {
                                return new \Illuminate\Support\HtmlString(
                                    SubscriptionResource::getSignedSignatureHtml(
                                        $get('msa_signer_name'),
                                        $get('msa_signer_title'),
                                        $get('msa_agreed_at')
                                    )
                                );
                            }),

                        Forms\Components\Hidden::make('msa_agreed_at')->dehydrated(),
                        Forms\Components\Hidden::make('msa_agreed_ip')->dehydrated(),
                        Forms\Components\Hidden::make('msa_signer_name')->dehydrated(),
                        Forms\Components\Hidden::make('msa_signer_title')->dehydrated(),

                        Forms\Components\Placeholder::make('agreement_required_warning')
                            ->hiddenLabel()
                            ->visible(fn(Get $get) => empty($get('msa_agreed_at')))
                            ->content(new \Illuminate\Support\HtmlString("
                                <div style='background:#fef2f2;border:1.5px solid #fca5a5;border-radius:8px;padding:10px 14px;font-size:12px;color:#991b1b;font-weight:700;'>
                                    ⚠️ This store cannot be created until the Subscription Agreement is signed above.
                                </div>
                            "))
                            ->columnSpanFull(),
                    ]),
                // ── SECURITY SETTINGS — top level, NOT nested ─────────────────
                Forms\Components\Section::make('Security Settings')
                    ->icon('heroicon-o-shield-check')
                    ->description('Control two-factor authentication and backup code access for this store.')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('two_factor_enabled')
                            ->label('Two-Factor Authentication (2FA)')
                            ->helperText('When ON — all staff must verify via Auth App or SMS after login.')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger')
                            ->inline(false)
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('backup_codes_enabled')
                            ->label('Allow Store Backup Codes')
                            ->helperText('When ON — any staff can use store backup codes if locked out.')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger')
                            ->inline(false)
                            ->live()
                            ->columnSpan(1),

                        Forms\Components\DateTimePicker::make('backup_codes_expires_at')
                            ->label('Backup Codes Expire At')
                            ->helperText('After this date backup codes stop working. Leave blank for no expiry.')
                            ->nullable()
                            ->native(false)
                            ->visible(fn(Get $get) => (bool) $get('backup_codes_enabled'))
                            ->columnSpan(1),

                        Forms\Components\Placeholder::make('backup_codes_status')
                            ->label('Current Backup Codes')
                            ->content(function ($record) {
                                if (!$record) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<span style="color:#94a3b8;font-size:13px;">Save first, then use the Backup Codes action to generate codes.</span>'
                                    );
                                }

                                $stored  = json_decode($record->two_factor_backup_codes ?? '[]', true);
                                $count   = count($stored);
                                $expires = $record->backup_codes_expires_at;
                                $expired = $expires && now()->isAfter($expires);

                                if ($count === 0) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<div style="background:#fef3c7;border:1px solid #fbbf24;border-radius:8px;padding:10px 14px;color:#92400e;font-size:13px;font-weight:600;">
                                            ⚠️ No backup codes generated. Use the <strong>Backup Codes</strong> action in the table.
                                        </div>'
                                    );
                                }

                                $expireText = $expires
                                    ? ($expired
                                        ? '<span style="color:#dc2626;font-weight:700;">⚠️ EXPIRED</span>'
                                        : '<span style="color:#166534;">Expires: ' . \Carbon\Carbon::parse($expires)->format('M d, Y H:i') . '</span>')
                                    : '<span style="color:#64748b;">No expiry set</span>';

                                return new \Illuminate\Support\HtmlString("
                                    <div style='display:flex;align-items:center;gap:12px;flex-wrap:wrap;'>
                                        <div style='background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:8px 14px;'>
                                            <span style='font-size:14px;font-weight:700;color:#166534;'>🔑 {$count} code(s) available</span>
                                        </div>
                                        <div style='font-size:13px;'>{$expireText}</div>
                                    </div>
                                ");
                            })
                            ->visible(fn(Get $get) => (bool) $get('backup_codes_enabled'))
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Store ID')->searchable()->weight('bold'),

                TextColumn::make('plan.name')
                    ->label('Plan')->badge()
                    ->color(fn($record) => match ($record->plan?->slug) {
                        'basic'      => 'warning',
                        'pro'        => 'success',
                        'enterprise' => 'info',
                        default      => 'gray',
                    })
                    ->formatStateUsing(fn($state) => $state ?? 'No Plan'),

                TextColumn::make('plan_status')
                    ->label('Status')->badge()
                    ->color(fn($state) => match ($state) {
                        'active'    => 'success',
                        'trial'     => 'warning',
                        'suspended' => 'danger',
                        'cancelled' => 'gray',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'active'    => '✅ Active',
                        'trial'     => '⏳ Trial',
                        'suspended' => '🚫 Suspended',
                        'cancelled' => '❌ Cancelled',
                        default     => ucfirst($state ?? '—'),
                    }),

                TextColumn::make('trial_ends_at')
                    ->label('Trial Ends')
                    ->visible(fn($record) => $record?->plan_status === 'trial')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '—';
                        $daysLeft = now()->diffInDays($state, false);
                        if ($daysLeft < 0) return '⚠️ Expired';
                        if ($daysLeft === 0) return '🔴 Today';
                        return "{$daysLeft} day(s) left";
                    })
                    ->badge()
                    ->color(fn($state) => $state && now()->diffInDays($state, false) <= 1 ? 'danger' : 'warning'),

                // ── System active status ─────────────────────────────────────
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('System Status')
                    ->onColor('success')->offColor('danger')
                    ->afterStateUpdated(function ($record, $state) {
                        $status = $state ? 'activated' : 'suspended';
                        \App\Models\MasterAuditLog::record(
                            action: 'system_status_toggled',
                            fieldLabel: 'System Status',
                            oldValue: $state ? 'Suspended' : 'Active',
                            newValue: $state ? 'Active' : 'Suspended',
                            tenantId: $record->id,
                            tenantName: $record->id,
                            severity: $state ? 'info' : 'critical',
                        );
                        Notification::make()
                            ->title("Store {$status}")
                            ->body("Store {$record->id} has been {$status}.")
                            ->success()->send();
                    }),

                // ── 2FA enabled toggle ────────────────────────────────────────
                Tables\Columns\ToggleColumn::make('two_factor_enabled')
                    ->label('2FA')
                    ->onColor('success')->offColor('danger')
                    ->afterStateUpdated(function ($record, $state) {
                        \App\Models\MasterAuditLog::record(
                            action: '2fa_toggled',
                            fieldLabel: 'Two-Factor Authentication',
                            oldValue: $state ? 'Disabled' : 'Enabled',
                            newValue: $state ? 'Enabled' : 'Disabled',
                            tenantId: $record->id,
                            tenantName: $record->id,
                            severity: $state ? 'info' : 'warning',
                        );
                        Notification::make()
                            ->title($state ? '2FA Enabled' : '2FA Disabled')
                            ->body("2FA " . ($state ? 'enabled' : 'disabled') . " for {$record->id}.")
                            ->success()->send();
                    }),

                // ── Backup codes toggle ───────────────────────────────────────
                Tables\Columns\ToggleColumn::make('backup_codes_enabled')
                    ->label('Backup Codes')
                    ->onColor('success')->offColor('danger')
                    ->afterStateUpdated(function ($record, $state) {
                        \App\Models\MasterAuditLog::record(
                            action: 'backup_codes_toggled',
                            fieldLabel: 'Backup Codes',
                            oldValue: $state ? 'Disabled' : 'Enabled',
                            newValue: $state ? 'Enabled' : 'Disabled',
                            tenantId: $record->id,
                            tenantName: $record->id,
                            severity: 'info',
                        );
                        Notification::make()
                            ->title($state ? 'Backup Codes Enabled' : 'Backup Codes Disabled')
                            ->success()->send();
                    }),

                TextColumn::make('domains.domain')
                    ->label('Web Address')->badge()->color('success')
                    ->url(function ($record) {
                        $domain   = $record->domains->first()?->domain;
                        $protocol = app()->isLocal() ? 'http' : 'https';
                        $port     = app()->isLocal() ? ':8001' : '';
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
                    ->icon('heroicon-m-finger-print')->color('info'),

                TextColumn::make('db_size')
                    ->label('Database Size')
                    ->getStateUsing(function (Tenant $record) {
                        try {
                            $result = \Illuminate\Support\Facades\DB::connection('mysql')->select(
                                "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size
                                 FROM information_schema.tables WHERE table_schema = ?",
                                [$record->tenancy_db_name]
                            );
                            $size = $result[0]->size ?? 0;
                            return $size > 0 ? "{$size} MB" : 'N/A';
                        } catch (\Exception $e) {
                            return 'Error';
                        }
                    })
                    ->badge()
                    ->color(function (Tenant $record) {
                        try {
                            $result = \Illuminate\Support\Facades\DB::connection('mysql')->select(
                                "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size
                                 FROM information_schema.tables WHERE table_schema = ?",
                                [$record->tenancy_db_name]
                            );
                            return ($result[0]->size ?? 0) > 500 ? 'danger' : 'info';
                        } catch (\Exception $e) {
                            return 'gray';
                        }
                    })
                    ->icon('heroicon-o-circle-stack'),

                TextColumn::make('storage_size')
                    ->label('File Storage')
                    ->getStateUsing(function (Tenant $record) {
                        try {
                            $sizeInBytes = 0;
                            $prefix = config('tenancy.filesystem.suffix_base', 'tenant') . $record->id;
                            foreach (
                                [
                                    storage_path($prefix),
                                    storage_path("app/public/{$prefix}"),
                                    storage_path("app/{$prefix}"),
                                    public_path($prefix),
                                ] as $path
                            ) {
                                if (\Illuminate\Support\Facades\File::isDirectory($path)) {
                                    foreach (\Illuminate\Support\Facades\File::allFiles($path) as $file) {
                                        $sizeInBytes += $file->getSize();
                                    }
                                }
                            }
                            if ($sizeInBytes === 0) return '0 MB';
                            $mb = round($sizeInBytes / 1024 / 1024, 2);
                            return $mb > 1000 ? round($mb / 1024, 2) . ' GB' : "{$mb} MB";
                        } catch (\Exception $e) {
                            return 'Error';
                        }
                    })
                    ->badge()
                    ->color(fn($state) => $state === '0 MB' ? 'gray' : 'warning')
                    ->icon('heroicon-o-folder-open'),

                TextColumn::make('created_at')->label('Launched')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan_id')
                    ->label('Plan')
                    ->options(fn() => Plan::where('is_active', true)->orderBy('sort_order')->pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('plan_status')
                    ->label('Status')
                    ->options(['trial' => 'Trial', 'active' => 'Active', 'suspended' => 'Suspended', 'cancelled' => 'Cancelled']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Action::make('change_plan')
                    ->label('Change Plan')->icon('heroicon-o-arrows-right-left')->color('warning')
                    ->form([
                        Forms\Components\Select::make('plan_id')->label('New Plan')
                            ->options(fn() => Plan::where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'))
                            ->required()->native(false),
                        Forms\Components\Select::make('plan_status')->label('Status')
                            ->options(['trial' => 'Trial', 'active' => 'Active', 'suspended' => 'Suspended'])
                            ->default('active')->required()->native(false),
                    ])
                    ->fillForm(fn(Tenant $record) => ['plan_id' => $record->plan_id, 'plan_status' => $record->plan_status])
                    ->action(function (Tenant $record, array $data) {
                        $oldPlan = $record->plan?->name ?? 'No Plan';
                        $record->update(['plan_id' => $data['plan_id'], 'plan_status' => $data['plan_status']]);
                        $newPlan = \App\Models\Plan::find($data['plan_id'])?->name ?? 'No Plan';
                        \App\Models\MasterAuditLog::record(
                            action: 'plan_changed',
                            fieldLabel: 'Plan',
                            oldValue: "{$oldPlan} ({$record->plan_status})",
                            newValue: "{$newPlan} ({$data['plan_status']})",
                            tenantId: $record->id,
                            tenantName: $record->id,
                            severity: 'warning',
                        );
                        Notification::make()->title('Plan Updated')->success()->send();
                    }),

                Action::make('extend_trial')
                    ->label('Extend Trial')->icon('heroicon-o-clock')->color('info')
                    ->visible(fn(Tenant $record) => in_array($record->plan_status, ['trial', 'suspended']))
                    ->form([
                        Forms\Components\Select::make('extend_days')->label('Extend By')
                            ->options([1 => '+1 day', 3 => '+3 days', 7 => '+7 days', 14 => '+14 days', 30 => '+30 days'])
                            ->default(3)->required()->native(false),
                    ])
                    ->action(function (Tenant $record, array $data) {
                        $base = $record->trial_ends_at && $record->trial_ends_at->isFuture()
                            ? $record->trial_ends_at : now();
                        $record->update([
                            'trial_ends_at'     => $base->addDays((int) $data['extend_days']),
                            'plan_status'       => 'trial',
                            'suspended_at'      => null,
                            'suspension_reason' => null,
                        ]);
                        \App\Models\MasterAuditLog::record(
                            action: 'trial_extended',
                            tenantId: $record->id,
                            tenantName: $record->id,
                        );
                        Notification::make()->title('Trial Extended')
                            ->body("New end date: " . $record->fresh()->trial_ends_at->format('M j, Y'))
                            ->success()->send();
                    }),

                Action::make('suspend')
                    ->label('Suspend')->icon('heroicon-o-no-symbol')->color('danger')
                    ->visible(fn(Tenant $record) => $record->plan_status !== 'suspended')
                    ->form([Forms\Components\Textarea::make('reason')->label('Reason')->required()->rows(2)])
                    ->requiresConfirmation()
                    ->action(function (Tenant $record, array $data) {
                        $record->update(['plan_status' => 'suspended', 'suspended_at' => now(), 'suspension_reason' => $data['reason']]);
                        \App\Models\MasterAuditLog::record(
                            action: 'tenant_suspended',
                            fieldLabel: 'Reason',
                            newValue: $data['reason'],
                            tenantId: $record->id,
                            tenantName: $record->id,
                            severity: 'critical',
                        );
                        Notification::make()->title('Tenant Suspended')->warning()->send();
                    }),

                Action::make('reactivate')
                    ->label('Reactivate')->icon('heroicon-o-check-circle')->color('success')
                    ->visible(fn(Tenant $record) => $record->plan_status === 'suspended')
                    ->requiresConfirmation()
                    ->action(function (Tenant $record) {
                        $record->update(['plan_status' => 'active', 'suspended_at' => null, 'suspension_reason' => null]);
                        \App\Models\MasterAuditLog::record(
                            action: 'tenant_reactivated',
                            tenantId: $record->id,
                            tenantName: $record->id,
                            severity: 'warning',
                        );
                        Notification::make()->title('Tenant Reactivated')->success()->send();
                    }),

                Action::make('emergency_reset')
                    ->label('Force Reset')->icon('heroicon-o-lifebuoy')->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Tenant $record) {
                        $record->run(function () {
                            $admin = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'Superadmin'))->first();
                            if ($admin) $admin->update(['password' => Hash::make('jeweltag123'), 'pin_code' => '1234']);
                        });
                        \App\Models\MasterAuditLog::record(
                            action: 'emergency_reset',
                            fieldLabel: 'Superadmin Credentials',
                            newValue: 'Password + PIN reset to default',
                            tenantId: $record->id,
                            tenantName: $record->id,
                            severity: 'critical',
                        );
                        Notification::make()->title('Reset Successful')->success()->send();
                    }),

                // ── BACKUP CODES MANAGEMENT ───────────────────────────────────
                Action::make('manage_backup_codes')
                    ->label('Backup Codes')
                    ->icon('heroicon-o-key')
                    ->color('warning')
                    ->modalHeading(fn(Tenant $record) => '🔑 Store Backup Codes — ' . strtoupper($record->id))
                    ->modalWidth('xl')
                    ->slideOver()
                    ->modalCancelActionLabel('Close')
                    ->modalSubmitActionLabel('💾 Save Backup Codes')
                    ->form(function (Tenant $record) {
                        $codesEnabled = (bool) ($record->backup_codes_enabled ?? true);
                        $stored       = json_decode($record->two_factor_backup_codes ?? '[]', true);
                        $count        = count($stored);
                        $expires      = $record->backup_codes_expires_at;
                        $expired      = $expires && now()->isAfter($expires);

                        if (!$codesEnabled) {
                            $html = "<div style='background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:16px;color:#dc2626;font-weight:600;text-align:center;'>
                                🚫 Backup codes are disabled for this store.<br>
                                <small style='font-weight:400;'>Enable them in Security Settings first.</small>
                            </div>";
                        } elseif ($count === 0) {
                            $html = "<div style='background:#fef3c7;border:1px solid #fbbf24;border-radius:10px;padding:16px;color:#92400e;text-align:center;font-weight:600;'>
                                ⚠️ No backup codes generated yet.<br>
                                <small style='font-weight:400;'>Set expiry below and click Generate New Codes.</small>
                            </div>";
                        } else {
                            $expireHtml = $expires
                                ? ($expired
                                    ? "<span style='color:#dc2626;font-weight:700;'>⚠️ EXPIRED — " . \Carbon\Carbon::parse($expires)->format('M d, Y H:i') . "</span>"
                                    : "<span style='color:#166534;'>✅ Expires: " . \Carbon\Carbon::parse($expires)->format('M d, Y H:i') . " (" . \Carbon\Carbon::parse($expires)->diffForHumans() . ")</span>")
                                : "<span style='color:#64748b;'>No expiry — codes never expire</span>";

                            $html = "
                                <div style='background:" . ($expired ? '#fef2f2' : '#f0fdf4') . ";border:1px solid " . ($expired ? '#fecaca' : '#bbf7d0') . ";border-radius:10px;padding:14px 16px;margin-bottom:12px;'>
                                    <div style='font-size:14px;font-weight:700;color:#0B3D3C;margin-bottom:6px;'>🔑 {$count} backup code(s) stored</div>
                                    <div style='font-size:13px;'>{$expireHtml}</div>
                                </div>
                                <div style='background:#fef3c7;border:1px solid #fbbf24;border-radius:8px;padding:10px 14px;font-size:12px;color:#92400e;margin-bottom:12px;'>
                                    ⚠️ Existing codes are NOT shown here for security. Generate new codes to replace them.
                                </div>
                                <div style='background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:10px 14px;font-size:12px;color:#1e40af;'>
                                    💡 Any staff member at <strong>" . strtoupper($record->id) . "</strong> can use these codes.
                                </div>";
                        }

                        return [
                            Forms\Components\Placeholder::make('codes_status')
                                ->label('')
                                ->content(new \Illuminate\Support\HtmlString($html)),

                            // ── Custom backup codes — enter your own ─────────────────────────
                            Forms\Components\Repeater::make('backup_codes')
                                ->label('Backup Codes')
                                ->helperText('Enter your own backup codes. Staff enter these exactly as typed when locked out.')
                                ->schema([
                                    Forms\Components\TextInput::make('code')
                                        ->label('Code')
                                        ->required()
                                        ->placeholder('e.g. 123456 or STORE2026')
                                        ->maxLength(20),
                                ])
                                ->addActionLabel('+ Add Code')
                                ->minItems(1)
                                ->maxItems(20)
                                ->grid(2)
                                ->default([])
                                ->columnSpanFull()
                                ->visible($codesEnabled),

                            // ── Expiry date ───────────────────────────────────────────────────
                            Forms\Components\DateTimePicker::make('backup_codes_expires_at')
                                ->label('Codes Expire At (optional)')
                                ->helperText('After this date all backup codes stop working. Leave blank for no expiry.')
                                ->nullable()
                                ->native(false)
                                ->default($record->backup_codes_expires_at)
                                ->visible($codesEnabled),
                        ];
                    })
                    ->action(function (Tenant $record, array $data) {
                         \Log::info('Backup codes action fired', [
        'tenant' => $record->id,
        'data'   => $data,
    ]);
                        if (!($record->backup_codes_enabled ?? true)) {
                            Notification::make()->title('Backup codes are disabled')->danger()->send();
                            return;
                        }

                        // Collect custom codes entered by master admin
                        $rawCodes = collect($data['backup_codes'] ?? [])
                            ->pluck('code')
                            ->map(fn($c) => trim($c))
                            ->filter()
                            ->unique()
                            ->values()
                            ->toArray();

                        if (empty($rawCodes)) {
                            Notification::make()
                                ->title('No codes entered')
                                ->body('Please add at least one backup code.')
                                ->danger()->send();
                            return;
                        }

                        // Hash each code for secure storage
                        $hashed = array_map(
                            fn($c) => \Illuminate\Support\Facades\Hash::make($c),
                            $rawCodes
                        );

                        \Illuminate\Support\Facades\DB::connection('mysql')
                            ->table('tenants')
                            ->where('id', $record->id)
                            ->update([
                                'two_factor_backup_codes' => json_encode($hashed),
                                'backup_codes_expires_at' => $data['backup_codes_expires_at'] ?? null,
                            ]);

                        \App\Models\MasterAuditLog::record(
                            action: 'backup_codes_set',
                            fieldLabel: 'Backup Codes',
                            newValue: count($rawCodes) . ' custom codes set' .
                                ($data['backup_codes_expires_at']
                                    ? ', expires ' . $data['backup_codes_expires_at']
                                    : ', no expiry'),
                            tenantId: $record->id,
                            tenantName: $record->id,
                            severity: 'warning',
                        );

                        $expireNote = $data['backup_codes_expires_at']
                            ? "\nExpires: " . \Carbon\Carbon::parse($data['backup_codes_expires_at'])->format('M d, Y H:i')
                            : "\nNo expiry set";

                        Notification::make()
                            ->title('✅ Backup Codes Saved — ' . strtoupper($record->id))
                            ->body(count($rawCodes) . ' code(s) saved successfully.' . $expireNote)
                            ->success()->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->label('Archive Store')->icon('heroicon-o-archive-box')->color('danger')
                    ->modalHeading('Archive Store?')
                    ->modalDescription('The store will be hidden but all data remains intact.')
                    ->modalSubmitActionLabel('Yes, archive it')
                    ->successNotificationTitle('Store successfully archived')
                    ->before(function (Tenant $record) {
                        \App\Models\MasterAuditLog::record(
                            action: 'tenant_archived',
                            tenantId: $record->id,
                            tenantName: $record->id,
                            severity: 'critical',
                        );
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit'   => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
