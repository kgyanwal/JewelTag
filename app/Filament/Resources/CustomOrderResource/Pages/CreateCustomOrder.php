<?php

namespace App\Filament\Resources\CustomOrderResource\Pages;

use App\Filament\Resources\CustomOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomOrder extends CreateRecord
{
    protected static string $resource = CustomOrderResource::class;
}
