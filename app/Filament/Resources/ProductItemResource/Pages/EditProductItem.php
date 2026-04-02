<?php

namespace App\Filament\Resources\ProductItemResource\Pages;

use App\Filament\Resources\ProductItemResource;
use App\Services\ZebraPrinterService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction;

class EditProductItem extends EditRecord
{
    protected static string $resource = ProductItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * 🚀 This triggers the popup notification after the "Save Changes" button is clicked.
     */
    protected function afterSave(): void
    {
        $record = $this->record;

        Notification::make()
            ->success()
            ->title('Stock Updated Successfully')
            ->body("Stock No: **{$record->barcode}** has been updated.")
            ->persistent() 
            ->actions([
                // 1. PRINT OPTION (Triggers the Zebra Printer)
                NotificationAction::make('print_tag')
                    ->label('Print Tag Now')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->button()
                    ->dispatch('zebra-print', [
                        'zpl' => app(ZebraPrinterService::class)->getZplCode($record, false)
                    ]),

                // 2. ADD ANOTHER (Redirects to the Create page)
                NotificationAction::make('new_item')
                    ->label('Add Another')
                    ->icon('heroicon-o-plus-circle')
                    ->color('gray')
                    ->url(ProductItemResource::getUrl('create')),
            ])
            ->send();
    }

    /**
     * Optional: Disable the default small top-right notification 
     * to avoid having two notifications on screen at once.
     */
    protected function getSavedNotificationTitle(): ?string
    {
        return null; 
    }
}