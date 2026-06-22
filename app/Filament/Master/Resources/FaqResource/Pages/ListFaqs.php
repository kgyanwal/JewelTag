<?php

namespace App\Filament\Master\Resources\FaqResource\Pages;

use App\Filament\Master\Resources\FaqResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFaqs extends ListRecords
{
    protected static string $resource = FaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New FAQ'),
        ];
    }
}