<?php

namespace App\Filament\Resources\RefundResource\Pages;

use App\Filament\Resources\RefundResource;
use App\Models\Sale;
use Filament\Resources\Pages\CreateRecord;

class CreateRefund extends CreateRecord
{
    protected static string $resource = RefundResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['customer_id']) && !empty($data['sale_id'])) {
            $data['customer_id'] = Sale::find($data['sale_id'])?->customer_id;
        }
        return $data;
    }
}