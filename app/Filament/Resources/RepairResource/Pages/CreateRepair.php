<?php

namespace App\Filament\Resources\RepairResource\Pages;

use App\Filament\Resources\RepairResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRepair extends CreateRecord
{
    protected static string $resource = RepairResource::class;
    protected function afterCreate(): void
    {
        // Check if the "auto_print" toggle was active in the form data
        if ($this->data['auto_print'] ?? false) {
            $record = $this->record;
            
            // Generate the URL for the print route
            $printUrl = route('repair.print', $record);

            // This injects a small piece of JS to open the new tab automatically
            $this->js("window.open('{$printUrl}', '_blank');");
        }
    }

    protected function getRedirectUrl(): string
    {
        // After creating, stay on the list page or go to view
        return $this->getResource()::getUrl('index');
    }
}
