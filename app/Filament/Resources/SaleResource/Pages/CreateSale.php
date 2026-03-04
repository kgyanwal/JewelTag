<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Laybuy;
use Filament\Resources\Pages\CreateRecord;
use App\Models\ProductItem;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use App\Helpers\Staff;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;
protected function getFormActions(): array
    {
        return [
            Action::make('complete_sale')
                ->label('Complete Sale')
                ->color('success')
                ->icon('heroicon-o-check')
                ->extraAttributes(['class' => 'w-full md:w-auto'])
                
                // 1. POPUP CONFIGURATION
                ->requiresConfirmation()
                ->modalHeading('Staff Verification')
                ->modalDescription('Please enter your PIN to certify and finalize this sale.')
                ->modalSubmitActionLabel('Verify & Save')
                
                // 2. PIN FORM
                ->form([
                    TextInput::make('verification_pin')
                        ->label('Enter PIN')
                        ->password()
                        ->revealable()
                        ->required()
                        ->numeric()
                        ->autofocus(),
                ])
                
                // 3. THE LOGIC
                ->action(function (array $data) {
                    // A. Get Active Staff
                    $formState = $this->data;
                    // If split is enabled, set the primary payment_method to "Split" for the table view
    if ($formState['is_split_payment'] ?? false) {
        $formState['payment_method'] = 'split';
    }
                    $staff = Staff::user() ?? auth()->user();

                    if (!$staff) {
                        Notification::make()->title('Error')->body('No active staff session found.')->danger()->send();
                        return;
                    }

                    // B. Verify PIN
                    if ($staff->pin_code !== $data['verification_pin']) {
                        Notification::make()
                            ->title('Invalid PIN')
                            ->body('Verification failed. Sale was not created.')
                            ->danger()
                            ->send();
                        return; // 🛑 Stop execution
                    }

                    // C. Submit the Main Form
                    // This creates the record using the data currently in the main form
                    $this->data = $formState;
                    $this->create(); 
                }),
                
            // Optional: Keep the "Cancel" button
            parent::getCancelFormAction(),
        ];
    }
    
protected function beforeCreate(): void
{
    $items = $this->data['items'] ?? [];

    foreach ($items as $item) {
        // 🔹 FIX: Skip empty entries added by the Repeater
       if (
            empty($item['product_item_id']) &&
            empty($item['repair_id']) &&
            empty($item['custom_order_id'])
        ) {
            continue;
        }

        // 2. ONLY run this block if there is a physical product ID
        if (!empty($item['product_item_id'])) {
            $productItem = ProductItem::lockForUpdate()->find($item['product_item_id']);

            if (!$productItem) {
                throw new \Exception('Product not found in inventory.');
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
    // 🚀 THE FIX: Convert the array of sales persons into a string
    $salesPersonString = is_array($sale->sales_person_list) 
        ? implode(', ', $sale->sales_person_list) 
        : $sale->sales_person_list;

    \App\Models\Laybuy::create([
        'laybuy_no' => 'LB-' . date('Ymd-His'),
        'customer_id' => $sale->customer_id,
        'sale_id' => $sale->id,
        'sales_person' => $salesPersonString, // 👈 Use the flattened string here
        'total_amount' => $sale->final_total,
        'amount_paid' => 0,
        'balance_due' => $sale->final_total,
        'status' => 'in_progress',
        'start_date' => now(),
        'due_date' => now()->addDays(30),
    ]);
            // 🔹 FIX: Update the SALE status to 'inprogress' or 'pending' 
            // so the receipt button remains hidden.
            $sale->update(['status' => 'pending',]);
            
            \Filament\Notifications\Notification::make()
                ->title('Stock Reserved')
                ->body('Items are now ON HOLD. Initial agreement ready.')
                ->warning()
                ->send();
        }
        
    });
}
public function mount(): void
{
    parent::mount();

    // 1. Check for Repair or Custom Order in the URL
    $repairId = request()->get('repair_id');
    $customOrderId = request()->get('custom_order_id');

    // --- HANDLE REPAIR ---
    if ($repairId) {
        $repair = \App\Models\Repair::find($repairId);
        if ($repair) {
            $this->data['customer_id'] = $repair->customer_id;
            $this->data['items'] = [
                [
                    'product_item_id' => null,
                    'repair_id' => $repair->id,
                    'stock_no_display' => 'REPAIR #' . $repair->repair_no,
                    'custom_description' => $repair->item_description . ' — ' . $repair->reported_issue,
                    'qty' => 1,
                    'sold_price' => $repair->final_cost ?? $repair->estimated_cost ?? 0,
                    'discount_percent' => 0,
                ],
            ];
        }
    }

    // --- HANDLE CUSTOM ORDER (NEW LOGIC) ---
    if ($customOrderId) {
        $customOrder = \App\Models\CustomOrder::find($customOrderId);
        if ($customOrder) {
            // Auto-select the customer
            $this->data['customer_id'] = $customOrder->customer_id;

            // Inject the custom order into the POS bill
          $this->data['items'] = [
    [
        'product_item_id' => null,
        'repair_id' => null,
        'custom_order_id' => $customOrder->id, // 🔥 THIS LINE
        'stock_no_display' => 'CUSTOM #' . $customOrder->order_no,
        'custom_description' => "Custom Piece: {$customOrder->metal_type} - " . ($customOrder->design_notes ?? ''),
        'qty' => 1,
        'sold_price' => $customOrder->quoted_price ?? 0,
        'discount_percent' => 0,
    ],
];

        }
    }
}
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}