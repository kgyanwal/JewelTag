<?php

namespace App\Filament\Master\Resources\SupportCannedResponseResource\Pages;

use App\Filament\Master\Resources\SupportCannedResponseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSupportCannedResponses extends ListRecords
{
    protected static string $resource = SupportCannedResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}