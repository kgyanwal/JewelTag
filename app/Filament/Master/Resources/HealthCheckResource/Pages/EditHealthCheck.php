<?php

namespace App\Filament\Master\Resources\HealthCheckResource\Pages;

use App\Filament\Master\Resources\HealthCheckResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHealthCheck extends EditRecord
{
    protected static string $resource = HealthCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
