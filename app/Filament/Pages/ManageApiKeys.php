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

/**
 * ============================================================
 * ManageApiKeys — Filament Page
 * ============================================================
 *
 * Provides a self-service UI for store managers to:
 *  - Generate named Sanctum API tokens for CRM integrations
 *  - View all active tokens (masked)
 *  - Revoke (delete) tokens instantly
 *
 * The generated token is shown ONCE in a modal immediately
 * after creation. It is never retrievable again.
 *
 * ────────────────────────────────────────────────────────────
 * HOW IT WORKS
 * ────────────────────────────────────────────────────────────
 * 1. User clicks "Generate New API Key"
 * 2. Enters an integration name (e.g. "HubSpot CRM")
 * 3. Laravel Sanctum creates a hashed token in personal_access_tokens
 * 4. The plain-text token is stored in $plainTextToken (LiveWire property)
 * 5. An Alpine.js event opens the display modal
 * 6. User copies the token; it is never shown again
 *
 * ────────────────────────────────────────────────────────────
 * BLADE VIEW: resources/views/filament/pages/manage-api-keys.blade.php
 * ────────────────────────────────────────────────────────────
 */
class ManageApiKeys extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon  = 'heroicon-o-key';
    protected static ?string $navigationGroup = 'Admin';
    protected static ?string $title           = 'API Integrations';
    protected static string  $view            = 'filament.pages.manage-api-keys';

    /**
     * Holds the plain-text token after creation.
     * Livewire syncs this to the Blade view automatically.
     * Set to null after user closes the modal via clearToken().
     */
    public ?string $plainTextToken = null;

    // ────────────────────────────────────────────────────────────────────────
    // Header Actions
    // ────────────────────────────────────────────────────────────────────────

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
                    // Create a Sanctum token scoped to the currently authenticated user.
                    // This ensures tokens are always tenant-isolated.
                    $newToken = auth()->user()->createToken($data['name']);

                    // Store the plain-text token in the Livewire property so the
                    // Blade view can render it inside the confirmation modal.
                    $this->plainTextToken = $newToken->plainTextToken;
                })
                ->after(function (): void {
                    // Dispatch an Alpine/Livewire event to open the token modal.
                    // Only fires if a token was actually generated.
                    if ($this->plainTextToken) {
                        $this->dispatch('open-token-modal');
                    }
                })
                ->modalHeading('Generate API Key')
                ->modalSubmitActionLabel('Generate'),
        ];
    }

    // ────────────────────────────────────────────────────────────────────────
    // Table
    // ────────────────────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            // Scope strictly to the logged-in user's tokens only.
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

    // ────────────────────────────────────────────────────────────────────────
    // Livewire Actions
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Called by the Blade modal's Close button via wire:click.
     * Clears the plain-text token from memory so it cannot be
     * recovered by inspecting the Livewire state after closing.
     */
    public function clearToken(): void
    {
        $this->plainTextToken = null;
    }
}