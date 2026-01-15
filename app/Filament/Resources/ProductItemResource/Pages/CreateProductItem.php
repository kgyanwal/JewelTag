<?php

namespace App\Filament\Resources\ProductItemResource\Pages;

use App\Filament\Resources\ProductItemResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProductItem extends CreateRecord
{
    protected static string $resource = ProductItemResource::class;
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
{
    $qty = (int) ($data['qty'] ?? 1);
    unset($data['qty'], $data['options'], $data['creation_mode']);

    $firstRecord = null;

    for ($i = 0; $i < $qty; $i++) {
        $itemData = $data;
        
        // 🔹 FIX: Assign the Store ID
        $itemData['store_id'] = 1; 

        // 🔹 Auto-generate Stock Number
        $lastId = \App\Models\ProductItem::max('id') ?? 0;
        $itemData['barcode'] = 'G' . (1000 + $lastId + $i + 1);
        
        $record = static::getModel()::create($itemData);
        if ($i === 0) $firstRecord = $record;
    }

    return $firstRecord;
}
}
