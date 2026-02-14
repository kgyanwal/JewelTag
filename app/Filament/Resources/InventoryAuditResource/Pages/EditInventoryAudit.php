<?php

namespace App\Filament\Resources\InventoryAuditResource\Pages;

use App\Filament\Resources\InventoryAuditResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInventoryAudit extends EditRecord
{
    protected static string $resource = InventoryAuditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
