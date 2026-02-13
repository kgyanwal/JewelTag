<?php

namespace App\Filament\Resources\ArchivedSaleResource\Pages;

use App\Filament\Resources\ArchivedSaleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListArchivedSales extends ListRecords
{
    protected static string $resource = ArchivedSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
