<?php

namespace App\Filament\Resources\CustomOrderResource\Pages;

use App\Filament\Resources\CustomOrderResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Payment;

class CreateCustomOrder extends CreateRecord
{
    protected static string $resource = CustomOrderResource::class;
    protected ?bool $hasUnsavedDataChangesAlert = true;
public ?string $depositMethod = null;
public bool $isSplitDeposit = false;
public array $splitDepositPayments = [];

    // 🚀 2. Catch the VISA/Mastercard selection BEFORE it hits the Custom Orders table
   protected function mutateFormDataBeforeCreate(array $data): array
{
    $this->depositMethod        = $data['initial_payment_method'] ?? 'cash';
    $this->isSplitDeposit       = (bool) ($data['is_split_deposit'] ?? false);
    $this->splitDepositPayments = $data['split_deposit_payments'] ?? [];

    unset($data['initial_payment_method'], $data['is_split_deposit'], $data['split_deposit_payments']);

    return $data;
}

protected function afterCreate(): void
{
    $order = $this->record;

    if ($order->amount_paid > 0) {
        if ($this->isSplitDeposit && !empty($this->splitDepositPayments)) {
            foreach ($this->splitDepositPayments as $payment) {
                Payment::create([
                    'custom_order_id' => $order->id,
                    'amount'          => (float) $payment['amount'],
                    'method'          => strtolower($payment['method']),
                    'paid_at'         => now(),
                ]);
            }
        } else {
            Payment::create([
                'custom_order_id' => $order->id,
                'amount'          => $order->amount_paid,
                'method'          => $this->depositMethod,
                'paid_at'         => now(),
            ]);
        }
    }
}
}