<?php

namespace App\Filament\Resources\CustomOrderResource\Pages;

use App\Filament\Resources\CustomOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomOrder extends EditRecord
{
    protected static string $resource = CustomOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
