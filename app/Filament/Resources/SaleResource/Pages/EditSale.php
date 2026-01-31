<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;
use Filament\Notifications\Notification;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    /**
     * Runs when page loads
     */
    public function mount($record): void
    {
        parent::mount($record);

        // If sale already completed → block editing
        if ($this->record->status === 'completed') {

            Notification::make()
                ->title('Sale Locked')
                ->body('This sale is already completed and cannot be edited.')
                ->danger()
                ->persistent() // stays visible
                ->send();

            // Redirect back to list page
            $this->redirect(SaleResource::getUrl('index'));
        }
    }

    /**
     * Disable form fields if completed
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->status === 'completed') {
            $this->form->disabled();
        }

        return $data;
    }

    /**
     * Header actions (Delete button)
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status !== 'completed'),
        ];
    }
}
