<?php

namespace App\Filament\Resources\ArchivedStockResource\Pages;

use App\Filament\Resources\ArchivedStockResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateArchivedStock extends CreateRecord
{
    protected static string $resource = ArchivedStockResource::class;
}
