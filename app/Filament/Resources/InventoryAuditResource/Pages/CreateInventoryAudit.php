<?php

namespace App\Filament\Resources\InventoryAuditResource\Pages;

use App\Filament\Resources\InventoryAuditResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInventoryAudit extends CreateRecord
{
    protected static string $resource = InventoryAuditResource::class;
    
    // Redirect back to the list after creating so you can click "Open Scanner"
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}