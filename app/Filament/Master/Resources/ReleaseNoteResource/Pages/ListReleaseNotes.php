<?php

namespace App\Filament\Master\Resources\ReleaseNoteResource\Pages;

use App\Filament\Master\Resources\ReleaseNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReleaseNotes extends ListRecords
{
    protected static string $resource = ReleaseNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}