<?php

namespace App\Filament\Master\Resources;

use App\Filament\Master\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use App\Forms\Components\CustomDatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Billing & Legal';
    protected static ?string $navigationLabel = 'Subscriptions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)->schema([
                    // ── COLUMN 1 & 2: Plan Details ──
                    Grid::make(1)->columnSpan(2)->schema([
                        Section::make('Subscription Details')
                            ->icon('heroicon-o-currency-dollar')
                            ->columns(2)
                            ->schema([
                                Select::make('tenant_id')
                                    ->label('Store / Tenant')
                                    ->options(Tenant::pluck('id', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->disabled(fn($record) => $record !== null), // Can't change tenant after creation

                                Select::make('plan_tier')
                                    ->label('Plan Tier')
                                    ->options([
                                        'starter' => 'Starter ($99/mo)',
                                        'professional' => 'Professional ($199/mo)',
                                        'enterprise' => 'Enterprise (Custom)',
                                    ])
                                    ->required()
                                    ->live(),

                                Select::make('billing_cycle')
                                    ->options([
                                        'monthly' => 'Monthly',
                                        'annually' => 'Annually (Save 20%)',
                                    ])
                                    ->required()
                                    ->default('monthly'),

                                Select::make('status')
                                    ->label('Account Status')
                                    ->options([
                                        'trialing' => 'Trialing',
                                        'active' => 'Active',
                                        'past_due' => 'Past Due (Warning)',
                                        'canceled' => 'Canceled',
                                        'unpaid' => 'Unpaid (Suspended)',
                                    ])
                                    ->required()
                                    ->default('active'),
                            ]),

                        Section::make('Billing Cycle Dates')
                            ->icon('heroicon-o-calendar-days')
                            ->columns(2)
                            ->schema([
                                CustomDatePicker::make('current_period_start')
                                    ->label('Cycle Start Date')
                                    ->required(),
                                CustomDatePicker::make('current_period_end')
                                    ->label('Next Renewal / Due Date')
                                    ->required(),
                                CustomDatePicker::make('trial_ends_at')
                                    ->label('Trial Ends At (Optional)'),
                                CustomDatePicker::make('canceled_at')
                                    ->label('Canceled At (Read Only)')
                                    ->disabled(),
                            ]),
                    ]),

                    // ── COLUMN 3: Legal & Paperwork ──
                    Grid::make(1)->columnSpan(1)->schema([
                        Section::make('Legal & Paperwork')
                            ->icon('heroicon-o-document-text')
                            ->description('Master Subscription Agreement details.')
                            ->schema([
                                TextInput::make('msa_version')
                                    ->label('MSA Version Agreed To')
                                    ->default('v1.0-2026')
                                    ->required(),
                                
                                TextInput::make('msa_agreed_ip')
                                    ->label('IP Address at Signing')
                                    ->disabled()
                                    ->helperText('Logged automatically during onboarding.'),

                                CustomDatePicker::make('msa_agreed_at')
                                    ->label('Date Signed')
                                    ->default(now())
                                    ->required(),

                                FileUpload::make('contract_pdf_path')
                                    ->label('Custom Contract Upload')
                                    ->directory('contracts')
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->helperText('Upload signed physical contracts here (for Enterprise accounts).'),
                            ]),
                    ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant_id')
                    ->label('Store')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('plan_tier')
                    ->label('Plan')
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->badge()
                    ->color('info'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trialing' => 'info',
                        'past_due' => 'warning',
                        'canceled', 'unpaid' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucfirst($state))),

                TextColumn::make('current_period_end')
                    ->label('Next Invoice')
                    ->date()
                    ->sortable()
                    ->description(function ($record) {
                        if ($record->status === 'past_due') {
                            return new HtmlString("<span class='text-danger-600 font-bold'>Payment Overdue!</span>");
                        }
                        return ucfirst($record->billing_cycle);
                    }),

                TextColumn::make('msa_version')
                    ->label('Legal')
                    ->icon('heroicon-s-check-badge')
                    ->color('success')
                    ->tooltip('Contract Signed')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'past_due' => 'Past Due',
                        'canceled' => 'Canceled',
                        'unpaid' => 'Unpaid',
                    ]),
                Tables\Filters\SelectFilter::make('plan_tier')
                    ->options([
                        'starter' => 'Starter',
                        'professional' => 'Professional',
                        'enterprise' => 'Enterprise',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                // Action to view the signed agreement terms
                Tables\Actions\Action::make('view_contract')
                    ->label('View Contract')
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->color('gray')
                    ->url(fn ($record) => $record->contract_pdf_path ? asset('storage/' . $record->contract_pdf_path) : null)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->contract_pdf_path !== null),

                // Industry Standard: "Cancel at Period End" Action
                Tables\Actions\Action::make('cancel_subscription')
                    ->label('Cancel Subs')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Subscription')
                    ->modalDescription('Are you sure? This will mark the subscription to cancel at the end of the current billing cycle.')
                    ->visible(fn ($record) => in_array($record->status, ['active', 'past_due']))
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'canceled',
                            'canceled_at' => now(),
                        ]);
                        Notification::make()->title('Subscription Canceled')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}