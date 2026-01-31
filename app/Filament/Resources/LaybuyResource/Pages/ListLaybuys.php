<?php

namespace App\Filament\Resources\LaybuyResource\Pages;

use App\Filament\Resources\LaybuyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLaybuys extends ListRecords
{
    protected static string $resource = LaybuyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
