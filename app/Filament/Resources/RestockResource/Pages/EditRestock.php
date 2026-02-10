<?php

namespace App\Filament\Resources\RestockResource\Pages;

use App\Filament\Resources\RestockResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRestock extends EditRecord
{
    protected static string $resource = RestockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
