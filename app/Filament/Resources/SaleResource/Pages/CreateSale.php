<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\ProductItem;
use Illuminate\Support\Facades\DB;
class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected function beforeCreate(): void
{
    $items = $this->data['items'] ?? [];

    foreach ($items as $item) {
        $productItem = ProductItem::lockForUpdate()->find($item['product_item_id']);

        if (! $productItem) {
            throw new \Exception('Product not found.');
        }

        if ($productItem->qty < $item['qty']) {
            throw new \Exception(
                "Insufficient stock for {$productItem->barcode}. Available: {$productItem->qty}"
            );
        }

        if ($productItem->status === 'sold') {
            throw new \Exception(
                "Item {$productItem->barcode} is already sold."
            );
        }
    }
}

  protected function afterCreate(): void
{
    DB::transaction(function () {
        $sale = $this->record;

        foreach ($sale->items as $saleItem) {
            $qtySold = (int) ($saleItem->qty ?? 1);

            $productItem = ProductItem::lockForUpdate()
                ->find($saleItem->product_item_id);

            if (! $productItem) {
                continue;
            }

            $newQty = max(0, $productItem->qty - $qtySold);

            $productItem->update([
                'qty' => $newQty,
                'status' => $newQty === 0 ? 'sold' : 'in_stock',
            ]);
        }
    });
}



    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}