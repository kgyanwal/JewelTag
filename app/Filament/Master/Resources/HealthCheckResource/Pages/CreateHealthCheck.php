<?php

namespace App\Filament\Master\Resources\HealthCheckResource\Pages;

use App\Filament\Master\Resources\HealthCheckResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateHealthCheck extends CreateRecord
{
    protected static string $resource = HealthCheckResource::class;
}
