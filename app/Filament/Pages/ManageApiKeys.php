<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Laravel\Sanctum\PersonalAccessToken;

class ManageApiKeys extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon  = 'heroicon-o-key';
    protected static ?string $navigationGroup = 'Admin';
    protected static ?string $title           = 'API Integrations';
    protected static string  $view            = 'filament.pages.manage-api-keys';

    public ?string $plainTextToken = null;

    // ── NEW: hide from nav entirely if plan doesn't include API access ──
    public static function shouldRegisterNavigation(): bool
    {
        return self::tenantHasApiAccess();
    }

    // ── NEW: block direct URL access too, not just the nav link ──
    public static function canAccess(): bool
    {
        return self::tenantHasApiAccess();
    }

    // ── NEW: shared plan-check helper ──
    protected static function tenantHasApiAccess(): bool
    {
        if (!function_exists('tenant') || !tenant()) {
            return true; // central/master context — don't block
        }

        $tenantModel = tenant();
        $tenantModel->loadMissing('plan');

        // No plan attached — fail safe by denying (matches your existing
        // "no plan = restricted" pattern elsewhere in the app)
        if (!$tenantModel->plan) {
            return false;
        }

        return (bool) $tenantModel->plan->hasFeature('api');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createToken')
                ->label('Generate New API Key')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->form([
                    TextInput::make('name')
                        ->label('Integration Name')
                        ->placeholder('e.g., HubSpot CRM, Zoho, Custom ERP…')
                        ->helperText('Give this key a descriptive name so you know which system uses it.')
                        ->required()
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    $newToken = auth()->user()->createToken($data['name']);
                    $this->plainTextToken = $newToken->plainTextToken;
                })
                ->after(function (): void {
                    if ($this->plainTextToken) {
                        $this->dispatch('open-token-modal');
                    }
                })
                ->modalHeading('Generate API Key')
                ->modalSubmitActionLabel('Generate'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PersonalAccessToken::query()
                    ->where('tokenable_id', auth()->id())
                    ->where('tokenable_type', get_class(auth()->user()))
                    ->latest()
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Integration Name')
                    ->searchable()
                    ->weight('bold')
                    ->icon('heroicon-m-key'),

                TextColumn::make('token')
                    ->label('Token (masked)')
                    ->formatStateUsing(fn () => '•••• •••• •••• ••••')
                    ->color('gray')
                    ->fontFamily('mono')
                    ->tooltip('The full token is never shown again after creation.'),

                TextColumn::make('last_used_at')
                    ->label('Last Used')
                    ->dateTime('M d, Y — h:i A')
                    ->placeholder('Never used')
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->actions([
                DeleteAction::make()
                    ->label('Revoke')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->modalHeading('Revoke API Key')
                    ->modalDescription('Are you sure? Any CRM or integration using this key will be instantly disconnected and cannot reconnect until a new key is issued.')
                    ->modalSubmitActionLabel('Yes, revoke it')
                    ->successNotificationTitle('API Key Revoked')
                    ->after(fn () => Notification::make()
                        ->warning()
                        ->title('Key Revoked')
                        ->body('The API key has been permanently deleted. Update your CRM to avoid downtime.')
                        ->send()
                    ),
            ])
            ->emptyStateIcon('heroicon-o-key')
            ->emptyStateHeading('No API Keys Yet')
            ->emptyStateDescription('Generate a key above to connect your CRM or third-party integration.');
    }

    public function clearToken(): void
    {
        $this->plainTextToken = null;
    }
}