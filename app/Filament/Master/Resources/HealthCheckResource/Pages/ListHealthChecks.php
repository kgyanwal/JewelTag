<?php

namespace App\Filament\Master\Resources\HealthCheckResource\Pages;

use App\Filament\Master\Resources\HealthCheckResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHealthChecks extends ListRecords
{
    protected static string $resource = HealthCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
