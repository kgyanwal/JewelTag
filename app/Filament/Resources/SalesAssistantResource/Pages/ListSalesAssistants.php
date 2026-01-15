<?php

namespace App\Filament\Resources\SalesAssistantResource\Pages;

use App\Filament\Resources\SalesAssistantResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalesAssistants extends ListRecords
{
    protected static string $resource = SalesAssistantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
