<?php

namespace App\Filament\Resources\SaleEditRequestResource\Pages;

use App\Filament\Resources\SaleEditRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSaleEditRequest extends EditRecord
{
    protected static string $resource = SaleEditRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
