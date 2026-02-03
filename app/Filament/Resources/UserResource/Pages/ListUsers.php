<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Hash;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // 🔹 Standard "New Staff" button
            Actions\CreateAction::make()
                ->label('New Staff'),

            // 🔹 Superadmin-only Master Credentials button
            Actions\Action::make('addMasterCredentials')
                ->label('Add Master Credentials')
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->visible(fn () => auth()->user()->hasRole('Superadmin'))
                ->modalHeading('Universal Terminal Credentials')
                ->modalDescription('Update the universal email and password used for browser login.')
                ->form([
                    Forms\Components\TextInput::make('email')
                        ->label('Universal Login Email')
                        ->email()
                        ->required(),

                    Forms\Components\TextInput::make('password')
                        ->label('Universal Login Password')
                        ->password()
                        ->revealable()
                        ->required(),
                ])
                ->fillForm(fn () => [
                    'email' => auth()->user()->email, // show current email
                ])
                ->action(function (array $data) {
                    /** @var User $master */
                    $master = auth()->user();

                    $master->update([
                        'email' => $data['email'],
                        'password' => Hash::make($data['password']),
                    ]);

                    Notification::make()
                        ->title('Universal Credentials Updated')
                        ->success()
                        ->send();
                }),
        ];
    }
}
