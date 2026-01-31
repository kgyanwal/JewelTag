<?php

namespace App\Filament\Resources\LaybuyResource\Pages;

use App\Filament\Resources\LaybuyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLaybuy extends EditRecord
{
    protected static string $resource = LaybuyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function mutateFormDataBeforeSave(array $data): array
{
    if (isset($data['creation_mode'])) {
        $data['is_trade_in'] = ($data['creation_mode'] === 'trade_in');
    }

    return $data;
}

}
