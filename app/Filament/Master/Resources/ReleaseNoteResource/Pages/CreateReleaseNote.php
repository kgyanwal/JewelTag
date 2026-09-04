<?php

namespace App\Filament\Master\Resources\ReleaseNoteResource\Pages;

use App\Filament\Master\Resources\ReleaseNoteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReleaseNote extends CreateRecord
{
    protected static string $resource = ReleaseNoteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }
}