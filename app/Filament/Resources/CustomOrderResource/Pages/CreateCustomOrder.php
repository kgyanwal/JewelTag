<?php

namespace App\Filament\Resources\CustomOrderResource\Pages;

use App\Filament\Resources\CustomOrderResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Payment;

class CreateCustomOrder extends CreateRecord
{
    protected static string $resource = CustomOrderResource::class;
    protected ?bool $hasUnsavedDataChangesAlert = true;

    // 🚀 1. Variable to hold the payment method during the save cycle
    public ?string $depositMethod = null;

    // 🚀 2. Catch the VISA/Mastercard selection BEFORE it hits the Custom Orders table
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Save the chosen method (or default to cash if something goes wrong)
        $this->depositMethod = $data['initial_payment_method'] ?? 'cash';
        
        // Unset it so MySQL doesn't crash looking for an 'initial_payment_method' column
        unset($data['initial_payment_method']);
        
        return $data;
    }

    // 🚀 3. Log the payment AFTER the order is successfully created
    protected function afterCreate(): void
    {
        $order = $this->record;

        if ($order->amount_paid > 0) {
            Payment::create([
                'custom_order_id' => $order->id, 
                'amount' => $order->amount_paid,
                'method' => $this->depositMethod, // 👈 This now correctly injects VISA!
                'paid_at' => now(),
            ]);
        }
    }
}