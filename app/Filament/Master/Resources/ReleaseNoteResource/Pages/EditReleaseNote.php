<?php

namespace App\Filament\Master\Resources\ReleaseNoteResource\Pages;

use App\Filament\Master\Resources\ReleaseNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReleaseNote extends EditRecord
{
    protected static string $resource = ReleaseNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}