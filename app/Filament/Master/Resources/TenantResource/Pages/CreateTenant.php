<?php

namespace App\Filament\Master\Resources\TenantResource\Pages;

use App\Filament\Master\Resources\TenantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    public string $domainUrl = '';

    // 1. Grab the domain before Filament tries to save the Tenant
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->domainUrl = $data['domain'];
        unset($data['domain']); // Remove it so it doesn't crash the tenant save
        
        return $data;
    }

    // 2. After the Tenant database is created, attach the domain!
    protected function afterCreate(): void
    {
        $this->record->domains()->create([
            'domain' => $this->domainUrl
        ]);
    }
}