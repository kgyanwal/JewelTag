<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\SaleEditRequest;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\Staff;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Standard Delete Action
            Actions\DeleteAction::make()
                ->visible(fn () => Staff::user()?->hasRole('Superadmin') || $this->record->status !== 'completed'),
            
            // 🚀 Keep the Manual Completion Action for Admins
            Actions\Action::make('complete_sale')
                ->label('Finalize Sale (Today)')
                ->color('success')
                ->icon('heroicon-o-check')
                ->visible(fn() => $this->record->status !== 'completed' && Staff::user()?->hasAnyRole(['Superadmin', 'Administration']))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status' => 'completed',
                        'completed_at' => now(), 
                    ]);

                    Notification::make()->title('Sale Finalized')->success()->send();
                    $this->refreshFormData(['status', 'completed_at']);
                }),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $staff = Staff::user();

        // 1. NON-ADMIN LOGIC: Create Request and Halt Update
        if (!$staff?->hasAnyRole(['Superadmin', 'Administration'])) {
            
            SaleEditRequest::create([
                'sale_id' => $record->id,
                'user_id' => $staff->id,
                'proposed_changes' => $data, 
                'status' => 'pending',
            ]);

            Notification::make()
                ->title('Request Sent')
                ->body('Your changes have been sent to Administration for approval.')
                ->warning()
                ->persistent() // Keeps it visible so they read it
                ->send();

            // Halt the process and go back to list
            $this->halt(); 
            return $record;
        }

        // 2. ADMIN LOGIC: Standard Update + Ensure completed_at stays accurate
        if ($data['status'] === 'completed' && !$record->completed_at) {
            $data['completed_at'] = now();
        }

        $record->update($data);

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}