<?php

namespace App\Filament\Resources\DeletionRequestResource\Pages;

use App\Filament\Resources\DeletionRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDeletionRequest extends EditRecord
{
    protected static string $resource = DeletionRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
