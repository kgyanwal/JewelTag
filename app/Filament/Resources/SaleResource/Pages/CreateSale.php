<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Laybuy;
use Filament\Resources\Pages\CreateRecord;
use App\Models\ProductItem;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;
protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['invoice_number'] = 'INV-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(3)));
        
        return $data;
    }
protected function beforeCreate(): void
{
    $items = $this->data['items'] ?? [];

    foreach ($items as $item) {
        // 🔹 FIX: Skip empty entries added by the Repeater
        if (empty($item['product_item_id'])) {
            continue;
        }

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
        $isLaybuy = $sale->payment_method === 'laybuy';

        foreach ($sale->items as $saleItem) {
            if (!$saleItem->product_item_id) continue;

            $productItem = \App\Models\ProductItem::lockForUpdate()->find($saleItem->product_item_id);
            if (!$productItem) continue;
            $productItem = \App\Models\ProductItem::lockForUpdate()->find($saleItem->product_item_id);
            if (!$productItem) continue;

            $qtySold = (int) ($saleItem->qty ?? 1);
            $newQty = max(0, $productItem->qty - $qtySold);

            // 🔹 FIX: If it's a Laybuy, set status to 'on_hold'. 
            // If it's a normal sale, set to 'sold' if qty is 0.
            $productItem->update([
                'qty' => $newQty,
                'status' => $isLaybuy ? 'on_hold' : ($newQty === 0 ? 'sold' : 'in_stock'),
                'hold_reason' => $isLaybuy ? "Laybuy: {$sale->invoice_number}" : null,
                'held_by_sale_id' => $isLaybuy ? $sale->id : null,
            ]);
        }

        if ($isLaybuy) {
            \App\Models\Laybuy::create([
                'laybuy_no' => 'LB-' . date('Ymd-His'),
                'customer_id' => $sale->customer_id,
                'sale_id' => $sale->id,
                'sales_person' => $sale->sales_person_list,
                'total_amount' => $sale->final_total,
                'amount_paid' => 0,
                'balance_due' => $sale->final_total,
                'status' => 'in_progress', // 🔹 Set the plan status
                'start_date' => now(),
                'due_date' => now()->addDays(30),
            ]);
            
            // 🔹 FIX: Update the SALE status to 'inprogress' or 'pending' 
            // so the receipt button remains hidden.
            $sale->update(['status' => 'pending']);
            
            \Filament\Notifications\Notification::make()
                ->title('Stock Reserved')
                ->body('Items are now ON HOLD. Initial agreement ready.')
                ->warning()
                ->send();
        }
    });
}

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}