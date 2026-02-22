<?php

namespace App\Filament\Master\Resources\TenantResource\Pages;

use App\Filament\Master\Resources\TenantResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Store domain in a temporary property so we can use it in afterCreate
        $this->recordData['domain_to_create'] = $data['domain'];
        
        // Remove 'domain' so it doesn't try to save into the 'tenants' table
        unset($data['domain']); 
        
        return $data;
    }

    protected function afterCreate(): void
    {
        $domain = $this->recordData['domain_to_create'];

        // Automatically create the domain record for the new tenant database
        $this->record->domains()->create([
            'domain' => $domain,
        ]);
    }
}