<?php

namespace App\Filament\Resources\ArchivedStockResource\Pages;

use App\Filament\Resources\ArchivedStockResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListArchivedStocks extends ListRecords
{
    protected static string $resource = ArchivedStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
