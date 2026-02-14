<?php

namespace App\Filament\Resources\InventoryAuditResource\Pages;

use App\Filament\Resources\InventoryAuditResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInventoryAudits extends ListRecords
{
    protected static string $resource = InventoryAuditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // 🚀 THIS CREATES THE "NEW" BUTTON IN THE UI
            Actions\CreateAction::make(),
        ];
    }
}