<?php

namespace App\Filament\Resources\RepairResource\Pages;

use App\Filament\Resources\RepairResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRepairs extends ListRecords
{
    protected static string $resource = RepairResource::class;

    // ⬇️ UPDATE THIS LINE TO MATCH YOUR ACTUAL FILE LOCATION ⬇️
    // Path: resources/views/filament/pages/list-repairs.blade.php
    protected static string $view = 'filament.pages.list-repairs';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}