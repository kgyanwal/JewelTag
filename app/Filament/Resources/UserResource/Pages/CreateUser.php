<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function beforeCreate(): void
    {
        if (!function_exists('tenant') || !tenant()) return;

        // tenant() IS the Tenant model, resolved on the central connection —
        // don't re-query it against the tenant DB.
        $tenantModel = tenant();
        $tenantModel->loadMissing('plan');

        if (!$tenantModel->plan) return;

        $current = \App\Models\User::count();

        if (!$tenantModel->withinLimit('max_users', $current)) {
            Notification::make()
                ->title('User Limit Reached')
                ->body("Your {$tenantModel->plan->name} plan allows up to {$tenantModel->plan->max_users} users ({$current}/{$tenantModel->plan->max_users} used). Upgrade to add more staff.")
                ->danger()
                ->persistent()
                ->actions([
                    NotificationAction::make('upgrade')
                        ->label('Upgrade Plan')
                        ->url('/contact')
                        ->button(),
                ])
                ->send();

            $this->halt();
        }
    }
}