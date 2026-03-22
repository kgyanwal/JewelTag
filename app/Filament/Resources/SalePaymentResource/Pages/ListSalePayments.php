<?php

namespace App\Filament\Resources\SalePaymentResource\Pages;

use App\Filament\Resources\SalePaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalePayments extends ListRecords
{
    protected static string $resource = SalePaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
