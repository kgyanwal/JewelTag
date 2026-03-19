<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Laybuy;
use Filament\Resources\Pages\CreateRecord;
use App\Models\ProductItem;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Helpers\Staff;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;
    protected bool $hasUnsavedChangesAlert = true;

    /**
     * 🚀 DRAFT PERSISTENCE: 
     * To actually save the data between refreshes in Filament v3:
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // This is where you would load session data if you wanted manual persistence
        return $data;
    }
    public function form(Form $form): Form
    {
        return parent::form($form)
            ->statePath('data');
    }
    public function mount(): void
    {
        parent::mount();

        if (session()->has('sale_draft')) {
            $this->data = session('sale_draft');
        }

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

        // --- HANDLE CUSTOM ORDER ---
        if ($customOrderId) {
            $customOrder = \App\Models\CustomOrder::find($customOrderId);
            if ($customOrder) {
                $this->data['customer_id'] = $customOrder->customer_id;
                $this->data['items'] = [
                    [
                        'product_item_id' => null,
                        'repair_id' => null,
                        'custom_order_id' => $customOrder->id,
                        'stock_no_display' => 'CUSTOM #' . $customOrder->order_no,
                        'custom_description' => "Custom Piece: {$customOrder->metal_type} - " . ($customOrder->design_notes ?? ''),
                        'qty' => 1,
                        'sold_price' => $customOrder->quoted_price ?? 0,
                        'discount_percent' => 0,
                    ],
                ];
            }
        }

        // 🚀 THE FIX: Force Recalculate Totals after injecting repair/custom data
        if ($repairId || $customOrderId) {
            $this->recalculateFinancials();
        }
    }

    protected function recalculateFinancials(): void
    {
        // Simulate the $get and $set functions required by SaleResource::updateTotals
        $set = function ($path, $value) {
            data_set($this->data, $path, $value);
        };

        $get = function ($path) {
            return data_get($this->data, $path);
        };

        // Call the calculation logic from your Resource
        SaleResource::updateTotals($get, $set);
    }
    protected function getFormActions(): array
    {
        return [
            Action::make('complete_sale')
                ->label('Complete Sale')
                ->color('success')
                ->icon('heroicon-o-check')
                ->extraAttributes([
                    'class' => 'w-full md:w-auto',
                    'wire:loading.attr' => 'disabled', // Disables button while server is busy
                    'wire:target' => 'complete_sale',  // Specifically targets this action
                ])

                // 1. POPUP CONFIGURATION
                ->requiresConfirmation()
                ->modalHeading('Staff Verification')
                ->modalDescription('Please enter your PIN to certify and finalize this sale.')
                ->modalSubmitActionLabel('Verify & Save')
                ->modalSubmitAction(fn($action) => $action->extraAttributes(['wire:loading.attr' => 'disabled']))

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
                    $formState = $this->data;

                    $actualStaff = \App\Models\User::where('pin_code', $data['verification_pin'])
                        ->where('is_active', true)
                        ->first();

                    if (!$actualStaff) {
                        Notification::make()->title('Invalid PIN')->danger()->send();
                        return;
                    }

                    // Recalculate
                    $get = fn($path) => data_get($formState, $path);
                    $set = function ($path, $value) use (&$formState) {
                        data_set($formState, $path, $value);
                    };
                    SaleResource::updateTotals($get, $set);

                    if ($formState['is_split_payment'] ?? false) {
                        $formState['payment_method'] = 'split';
                    }

                    $this->data = $formState;

                    // ADD THIS: Prevent any chance of double-fire
                    if ($this->record) {
                        return; // Already saved, bail out
                    }

                    $this->create();
                }),

            // Optional: Keep the "Cancel" button
            parent::getCancelFormAction(),
        ];
    }

  protected function beforeCreate(): void
{
    // 🛡️ DUPLICATE GUARD
    $existingDuplicate = \App\Models\Sale::where('customer_id', $this->data['customer_id'])
        ->where('final_total', $this->data['final_total'])
        ->where('created_at', '>=', now()->subSeconds(30))
        ->exists();

    if ($existingDuplicate) {
        Notification::make()
            ->title('Duplicate Sale Blocked')
            ->body('This sale was already saved. Please refresh the page.')
            ->danger()
            ->send();
        $this->halt();
        return;
    }

    $items = $this->data['items'] ?? [];

    foreach ($items as $item) {
        if (
            empty($item['product_item_id']) &&
            empty($item['repair_id']) &&
            empty($item['custom_order_id'])
        ) {
            continue;
        }

        if (!empty($item['product_item_id'])) {
            $productItem = ProductItem::lockForUpdate()->find($item['product_item_id']);

            if (!$productItem) {
                Notification::make()
                    ->title('Product Not Found')
                    ->body('A product in your cart was not found in inventory.')
                    ->danger()
                    ->send();
                $this->halt();
                return;
            }

            if ($productItem->qty < $item['qty']) {
                Notification::make()
                    ->title('Insufficient Stock')
                    ->body("Only {$productItem->qty} left for {$productItem->barcode}.")
                    ->danger()
                    ->send();
                $this->halt();
                return;
            }

            if ($productItem->status === 'sold') {
                Notification::make()
                    ->title('Item Already Sold')
                    ->body("{$productItem->barcode} is no longer available.")
                    ->danger()
                    ->send();
                $this->halt();
                return;
            }
        }
    }
}

    protected function afterCreate(): void
    {
        DB::transaction(function () {
            $sale = $this->record;
            $isLaybuy = $sale->payment_method === 'laybuy';
if ($sale->is_split_payment) {
            foreach ($sale->split_payments as $payment) {
                \App\Models\Payment::create([
                    'sale_id' => $sale->id,
                    'amount' => $payment['amount'],
                    'method' => $payment['method'],
                    'paid_at' => now(),
                ]);
            }
        } else {
            \App\Models\Payment::create([
                'sale_id' => $sale->id,
                'amount' => $sale->final_total,
                'method' => $sale->payment_method,
                'paid_at' => now(),
            ]);
        }
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
                $sale->update([
                    'status' => 'completed',
                    'completed_at' => now(), // This captures TODAY'S date for the report
                ]);

                \Filament\Notifications\Notification::make()
                    ->title('Stock Reserved')
                    ->body('Items are now ON HOLD. Initial agreement ready.')
                    ->warning()
                    ->send();
            }
        });
        session()->forget('sale_draft');
        $this->data = [];
    }


    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    public function updated($property): void
    {
        // Save the entire form state in session
        session(['sale_draft' => $this->data]);
    }
}
