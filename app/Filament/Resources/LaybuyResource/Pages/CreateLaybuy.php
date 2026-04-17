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
            $totalAmount = floatval($data['total_amount'] ?? 0);
            $amountPaid = floatval($data['amount_paid'] ?? 0);
            $balanceDue = max(0, $totalAmount - $amountPaid);
            
            $paymentMethod = $data['initial_payment_method'] ?? 'CASH';
            
            // 🚀 THE FIX: Grab the sales person from the form
            $salesPerson = $data['sales_person'] ?? auth()->user()->name;
            // Sales table expects an array/JSON for the staff list
            $staffList = [$salesPerson];

            // 1. Create the Pending Sale umbrella
            $sale = Sale::create([
                'customer_id'       => $data['customer_id'],
                'invoice_number'    => 'INV-' . strtoupper(bin2hex(random_bytes(3))),
                'status'            => $balanceDue <= 0 ? 'completed' : 'pending',
                // 🚀 THE FIX: Pass the staff name to the main sale record!
                'sales_person_list' => $staffList, 
                'payment_method'    => 'laybuy',
                'subtotal'          => $totalAmount,
                'final_total'       => $totalAmount,
                'amount_paid'       => $amountPaid,
                'balance_due'       => $balanceDue,
                'tax_amount'        => 0,
                'store_id'          => auth()->user()->store_id ?? 1,
            ]);

            // 2. Add items to Sale and reserve Stock
            if (isset($data['layby_items'])) {
                foreach ($data['layby_items'] as $item) {
                    $sale->items()->create([
                        'product_item_id'    => $item['product_item_id'],
                        'custom_description' => $item['description'],
                        'qty'                => 1,
                        'sold_price'         => $item['price'],
                    ]);

                    ProductItem::where('id', $item['product_item_id'])->update([
                        'status' => 'on_hold',
                        'hold_reason' => 'Laybuy'
                    ]);
                }
            }

            // 3. Prepare final Laybuy data
            $data['sale_id']     = $sale->id;
            $data['amount_paid'] = $amountPaid;
            $data['balance_due'] = $balanceDue;
            $data['status']      = $balanceDue <= 0 ? 'completed' : 'in_progress';
            
            unset($data['layby_items']); 
            unset($data['initial_payment_method']); 

            $laybuy = parent::handleRecordCreation($data);

            // 4. Log the initial deposit into the Ledger
            if ($amountPaid > 0) {
                \App\Models\Payment::create([
                    'sale_id'  => $sale->id,
                    'amount'   => $amountPaid,
                    'method'   => $paymentMethod,
                    'paid_at'  => now(),
                    'store_id' => auth()->user()->store_id ?? 1,
                ]);
            }

            return $laybuy;
        });
    }
}