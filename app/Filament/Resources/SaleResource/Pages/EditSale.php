<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    /**
     * 🔹 mount() is now cleaned to allow entry.
     */
    public function mount($record): void
    {
        parent::mount($record);

        // We only show a warning now instead of redirecting, 
        // or you can remove this block entirely to stop the notification.
        if ($this->record->status === 'completed' && !Auth::user()->hasRole('Superadmin')) {
            Notification::make()
                ->title('Editing Completed Sale')
                ->body('Warning: You are editing a sale that is already marked as completed.')
                ->warning()
                ->send();
        }
    }

    /**
     * 🔹 mutateFormDataBeforeFill() updated to keep fields enabled.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // By removing $this->form->disabled(), all fields remain interactive.
        return $data;
    }

    /**
     * Header actions (Delete button)
     */
    protected function getHeaderActions(): array
    {
        return [
            // Delete is now visible to everyone, or keep the Superadmin restriction if preferred.
            Actions\DeleteAction::make()
                ->visible(fn () => Auth::user()->hasRole('Superadmin') || $this->record->status !== 'completed'),
        ];
    }
}