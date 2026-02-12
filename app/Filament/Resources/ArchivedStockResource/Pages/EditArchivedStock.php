<?php

namespace App\Filament\Resources\ArchivedStockResource\Pages;

use App\Filament\Resources\ArchivedStockResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArchivedStock extends EditRecord
{
    protected static string $resource = ArchivedStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
