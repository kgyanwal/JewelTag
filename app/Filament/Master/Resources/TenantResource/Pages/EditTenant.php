<?php

namespace App\Filament\Master\Resources\TenantResource\Pages;

use App\Filament\Master\Resources\TenantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Pull the primary domain from the related domains table
     * so the form field is pre-populated on edit.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['domain'] = $this->record->domains()->first()?->domain ?? '';

        return $data;
    }

    /**
     * When saving, update (or create) the domain record in the
     * domains table rather than trying to write it onto the tenant row.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Stash the domain value so afterSave() can use it,
        // then remove it from $data so Filament doesn't try
        // to write a non-existent column on the tenants table.
        $this->domainToSave = $data['domain'] ?? null;
        unset($data['domain']);

        return $data;
    }

    protected string|null $domainToSave = null;

    protected function afterSave(): void
    {
        if (blank($this->domainToSave)) {
            return;
        }

        $existing = $this->record->domains()->first();

        if ($existing) {
            $existing->update(['domain' => $this->domainToSave]);
        } else {
            $this->record->domains()->create(['domain' => $this->domainToSave]);
        }
    }
}