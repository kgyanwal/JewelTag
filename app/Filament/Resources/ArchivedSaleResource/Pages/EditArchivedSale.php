<?php

namespace App\Filament\Resources\ArchivedSaleResource\Pages;

use App\Filament\Resources\ArchivedSaleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArchivedSale extends EditRecord
{
    protected static string $resource = ArchivedSaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
