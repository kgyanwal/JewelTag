<?php

namespace App\Filament\Resources\SalesAssistantResource\Pages;

use App\Filament\Resources\SalesAssistantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalesAssistant extends EditRecord
{
    protected static string $resource = SalesAssistantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
