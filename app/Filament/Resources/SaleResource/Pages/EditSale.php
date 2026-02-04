<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\SaleEditRequest;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => \App\Helpers\Staff::user()?->hasRole('Superadmin') || $this->record->status !== 'completed'),
        ];
    }

    /**
     * 🔹 Use handleRecordUpdate to intercept the save process
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $staff = \App\Helpers\Staff::user();

        // If NOT Admin/Superadmin, intercept and create a request
        if (!$staff?->hasAnyRole(['Superadmin', 'Administration'])) {
            
            SaleEditRequest::create([
                'sale_id' => $record->id,
                'user_id' => $staff->id,
                'proposed_changes' => $data, // The validated form data
                'status' => 'pending',
            ]);

            Notification::make()
                ->title('Edit Request Submitted')
                ->body('Changes are pending administrative approval.')
                ->warning()
                ->send();

            $this->redirect($this->getResource()::getUrl('index'));

            // Return the original record without changes to stop the update
            return $record;
        }

        // If Admin, proceed with the standard update
        $record->update($data);

        return $record;
    }
}