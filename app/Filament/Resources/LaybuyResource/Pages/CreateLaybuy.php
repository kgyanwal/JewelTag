<?php

namespace App\Filament\Resources\LaybuyResource\Pages;

use App\Filament\Resources\LaybuyResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use App\Models\ProductItem;

class CreateLaybuy extends CreateRecord
{
    protected static string $resource = LaybuyResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {
            // 🚀 FIX: Ensure numeric values are never null
            $totalAmount = floatval($data['total_amount'] ?? 0);
            $amountPaid = floatval($data['amount_paid'] ?? 0);
            $balanceDue = $totalAmount - $amountPaid;

            // 1. Create the Pending Sale
            $sale = Sale::create([
                'customer_id' => $data['customer_id'],
                'invoice_number' => 'INV-' . strtoupper(bin2hex(random_bytes(3))),
                'status' => 'pending',
                'payment_method' => 'laybuy',
                'subtotal' => $totalAmount,
                'final_total' => $totalAmount,
                'tax_amount' => 0,
                'store_id' => auth()->user()->store_id ?? 1,
            ]);

            // 2. Add items to Sale and reserve Stock
            if (isset($data['layby_items'])) {
                foreach ($data['layby_items'] as $item) {
                    $sale->items()->create([
                        'product_item_id' => $item['product_item_id'],
                        'custom_description' => $item['description'],
                        'qty' => 1,
                        'sold_price' => $item['price'],
                    ]);

                    ProductItem::where('id', $item['product_item_id'])
                        ->update(['status' => 'on_laybuy']);
                }
            }

            // 3. Prepare final Laybuy data
            $data['sale_id'] = $sale->id;
            $data['amount_paid'] = $amountPaid; // 🚀 Ensure it is passed back as 0 if empty
            $data['balance_due'] = $balanceDue;
            
            unset($data['layby_items']); // Remove temporary repeater data

            return parent::handleRecordCreation($data);
        });
    }
}