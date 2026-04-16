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
use Laravel\Sanctum\PersonalAccessToken;

class ManageApiKeys extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = 'Admin';
    protected static ?string $title = 'API Integrations';
    protected static string $view = 'filament.pages.manage-api-keys';

    public ?string $plainTextToken = null;

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
                        ->placeholder('e.g., HubSpot CRM')
                        ->required()
                        ->maxLength(255),
                ])
                ->action(function (array $data) {
                    // Generate token tied strictly to this logged-in store manager
                    $token = auth()->user()->createToken($data['name']);
                    $this->plainTextToken = $token->plainTextToken;
                })
                ->modalHeading('Generate API Key')
                ->after(function () {
                    if ($this->plainTextToken) {
                        $this->dispatch('open-token-modal');
                    }
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(PersonalAccessToken::query()->where('tokenable_id', auth()->id()))
            ->columns([
                TextColumn::make('name')->label('Key Name')->searchable()->weight('bold'),
                TextColumn::make('token')->label('Token Mask')->formatStateUsing(fn () => '••••••••••••••••••••••••')->color('gray')->fontFamily('mono'),
                TextColumn::make('last_used_at')->label('Last Used')->dateTime('M d, Y h:i A')->placeholder('Never used'),
                TextColumn::make('created_at')->label('Created')->dateTime('M d, Y')->sortable(),
            ])
            ->actions([
                DeleteAction::make()
                    ->label('Revoke')
                    ->modalHeading('Revoke API Key')
                    ->modalDescription('Are you sure? Any CRM using this key will be instantly disconnected.')
                    ->successNotificationTitle('API Key Revoked'),
            ])
            ->emptyStateHeading('No API Keys')
            ->emptyStateDescription('Create a key to connect your CRM.');
    }
}