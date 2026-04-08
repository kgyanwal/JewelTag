<?php

namespace App\Filament\Resources\CustomOrderResource\Pages;

use App\Filament\Resources\CustomOrderResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class CreateCustomOrder extends CreateRecord
{
    protected static string $resource = CustomOrderResource::class;
    protected ?bool $hasUnsavedDataChangesAlert = true;

    public ?string $depositMethod        = null;
    public bool    $isSplitDeposit       = false;
    public array   $splitDepositPayments = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // FIX: dehydrated(false) fields are stripped from $data but still
        // available in $this->data (Livewire component state)
        $this->depositMethod        = $this->data['initial_payment_method'] ?? 'CASH';
        $this->isSplitDeposit       = (bool) ($this->data['is_split_deposit'] ?? false);
        $this->splitDepositPayments = $this->data['split_deposit_payments'] ?? [];

        unset(
            $data['initial_payment_method'],
            $data['is_split_deposit'],
            $data['split_deposit_payments']
        );

        // Auto-calculate quoted_price and balance_due from items (with tax)
        $items = $data['items'] ?? [];
        if (!empty($items)) {
$subtotal  = collect($items)->sum(fn($i) => (float)($i['quoted_price'] ?? 0));
$deposit   = (float)($data['amount_paid'] ?? 0);
$discPct   = min(100, max(0, floatval($data['discount_percent'] ?? 0)));
$discAmt   = $subtotal * $discPct / 100;
$afterDisc = $subtotal - $discAmt;

$isTaxFree = (bool)($data['is_tax_free'] ?? false);
$dbTax     = DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? 7.63;
$taxRate   = $isTaxFree ? 0 : floatval($dbTax) / 100;
$tax       = $afterDisc * $taxRate;
$total     = $afterDisc + $tax;

$data['quoted_price']    = round($subtotal, 2);
$data['discount_amount'] = round($discAmt, 2);
$data['balance_due']     = round(max(0, $total - $deposit), 2);
        }

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
                        'sale_id'         => $order->sale_id ?? null, // FIX: link to sale for EOD
                        'amount'          => round((float) $payment['amount'], 2),
                        'method'          => strtoupper(trim($payment['method'])), // FIX: uppercase
                        'paid_at'         => now(),
                    ]);
                }
            } else {
                Payment::create([
                    'custom_order_id' => $order->id,
                    'sale_id'         => $order->sale_id ?? null, // FIX: link to sale for EOD
                    'amount'          => round((float) $order->amount_paid, 2),
                    'method'          => strtoupper(trim($this->depositMethod)), // FIX: uppercase
                    'paid_at'         => now(),
                ]);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}