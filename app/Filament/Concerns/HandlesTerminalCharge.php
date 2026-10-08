<?php

namespace App\Filament\Concerns;

use App\Filament\Resources\SaleResource;
use App\Services\Payments\ValorGateway;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

trait HandlesTerminalCharge
{
    /** Called by wire:poll.3s in the "waiting" panel. */
    public function checkDeviceChargeStatus(): void
    {
        $reqTxnId = $this->data['pending_device_request_id'] ?? null;
        if (!$reqTxnId) return;

        try {
            $status = app(ValorGateway::class)->checkStatus($reqTxnId);
        } catch (\Throwable $e) {
            return;
        }

        $state = $status['state'] ?? 'pending';

        if (in_array($state, ['pending', 'error'], true)) return;

        if ($state === 'approved') {
            $this->recordApprovedTerminalCharge($status);
        } else {
            Notification::make()
                ->title('Card Declined')
                ->body($status['message'] ?? 'The terminal reported a decline. Nothing was charged.')
                ->danger()
                ->persistent()
                ->send();
        }

        $this->clearPendingTerminalCharge();
        $this->syncTerminalTotals();
    }

    protected function recordApprovedTerminalCharge(array $status): void
    {
        $splits = $this->data['split_payments'] ?? [];

        foreach ($splits as $row) {
            if (!empty($status['txn_id']) && ($row['gateway_txn_id'] ?? null) === $status['txn_id']) {
                return;
            }
        }

        if (empty($this->data['is_split_payment'])) {
            $typed = round((float) ($this->data['amount_paid'] ?? 0), 2);
            if ($typed > 0 && !empty($this->data['payment_method'])) {
                $splits[(string) Str::uuid()] = [
                    'method'         => strtoupper((string) $this->data['payment_method']),
                    'amount'         => number_format($typed, 2, '.', ''),
                    'payment_target' => $this->data['payment_target'] ?? 'regular',
                ];
            }
        }

        $amount = round((float) ($this->data['pending_device_amount'] ?? 0), 2);
        $method = $this->resolveTerminalMethod($status['card_brand'] ?? null);

        $splits[(string) Str::uuid()] = [
            'method'         => $method['key'],
            'amount'         => number_format($amount, 2, '.', ''),
            'payment_target' => 'regular',
            'gateway'        => 'valor',
            'gateway_txn_id' => $status['txn_id'] ?? null,
            'auth_code'      => $status['auth_code'] ?? null,
            'card_last4'     => $status['card_last4'] ?? null,
            'card_brand'     => $status['card_brand'] ?? null,
        ];

        $this->data['split_payments']   = $splits;
        $this->data['is_split_payment'] = true;
        $this->data['amount_paid']      = 0;

        $total     = round((float) ($this->data['final_total'] ?? 0), 2);
        $collected = round(collect($splits)->sum(fn($r) => (float) ($r['amount'] ?? 0)), 2);
        $remaining = max(0, round($total - $collected, 2));

        Notification::make()
            ->title('Card Approved ✅  $' . number_format($amount, 2))
            ->body(
                ($status['card_brand'] ?? 'CARD') . ' ••' . ($status['card_last4'] ?? '----')
                . ' · Auth ' . ($status['auth_code'] ?? '-')
                . ($remaining > 0
                    ? ' · Still to collect: $' . number_format($remaining, 2)
                    : ' · Invoice fully paid')
            )
            ->success()
            ->send();

        if (!$method['matched']) {
            Notification::make()
                ->title('Add "' . $method['key'] . '" to Payment Methods')
                ->body('This card type is not in Settings → Sales → Payment Methods, so End-of-Day may not group it. Add it there.')
                ->warning()
                ->persistent()
                ->send();
        }
    }

    protected function resolveTerminalMethod(?string $brand): array
    {
        $brand = strtoupper(trim((string) $brand));
        if ($brand === '') return ['key' => 'CARD', 'matched' => false];

        $aliases = [
            'MC' => 'MASTERCARD', 'MASTER CARD' => 'MASTERCARD', 'MASTERCARD' => 'MASTERCARD',
            'AMERICAN EXPRESS' => 'AMEX', 'AMEX' => 'AMEX',
            'DISC' => 'DISCOVER',
        ];
        $brand = $aliases[$brand] ?? $brand;

        foreach (array_keys(SaleResource::getPaymentOptions()) as $key) {
            if (strtoupper(trim((string) $key)) === $brand) {
                return ['key' => strtoupper(trim((string) $key)), 'matched' => true];
            }
        }
        return ['key' => $brand, 'matched' => false];
    }

    protected function clearPendingTerminalCharge(): void
    {
        $this->data['pending_device_request_id'] = null;
        $this->data['pending_device_amount']     = null;
        $this->data['pending_device_started_at'] = null;
        $this->data['terminal_amount']           = null;
    }

    protected function syncTerminalTotals(): void
    {
        $get = fn($path) => data_get($this->data, $path);
        $set = function ($path, $value) { data_set($this->data, $path, $value); };
        SaleResource::updateTotals($get, $set);

        if (property_exists($this, 'draftId') && $this->draftId) {
            session(["sale_draft_{$this->draftId}" => $this->data]);
        }
    }
}