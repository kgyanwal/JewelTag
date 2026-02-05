<?php

namespace App\Filament\Resources\CustomOrderResource\Pages;

use App\Filament\Resources\CustomOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomOrders extends ListRecords
{
    protected static string $resource = CustomOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
