<?php

namespace App\Filament\Master\Resources\AnnouncementResource\Pages;

use App\Filament\Master\Resources\AnnouncementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAnnouncements extends ListRecords
{
    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
