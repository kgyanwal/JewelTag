<?php

namespace App\Filament\Resources\CustomOrderResource\Pages;

use App\Filament\Resources\CustomOrderResource;
use App\Models\Payment;
use App\Models\Sale;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class EditCustomOrder extends EditRecord
{
    protected static string $resource = CustomOrderResource::class;

    public ?string $depositMethod        = null;
    public bool    $isSplitDeposit       = false;
    public array   $splitDepositPayments = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;

        $isTaxFree  = (bool)($record->is_tax_free ?? false);
        $dbTax      = DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? 7.63;
        $taxRate    = $isTaxFree ? 0 : floatval($dbTax) / 100;
        $grandTotal = floatval($record->quoted_price) * (1 + $taxRate);

        // ── SOURCE OF TRUTH: all payments linked to this custom order ──────
        // Includes payments made directly on the custom order AND
        // payments made via the linked sale (e.g. $500 paid at sale checkout)
        $directPayments = Payment::where('custom_order_id', $record->id)->sum('amount');

        // Also check sale payments if linked — don't double-count
        $salePayments = 0;
        if ($record->sale_id) {
            $salePayments = Payment::where('sale_id', $record->sale_id)
                ->whereNull('custom_order_id')
                ->sum('amount');
        }

        $actualPaid = $directPayments + $salePayments;

        // Fallback if no payments recorded at all
        if ($actualPaid == 0) {
            $actualPaid = floatval($record->amount_paid);
        }

        $trueBalance = round(max(0, $grandTotal - $actualPaid), 2);

        // ── SELF-HEAL: update DB if stale ─────────────────────────────────
        if (
            abs(floatval($record->balance_due) - $trueBalance) > 0.01 ||
            abs(floatval($record->amount_paid) - $actualPaid) > 0.01
        ) {
            $record->update([
                'balance_due' => $trueBalance,
                'amount_paid' => round($actualPaid, 2),
                'status'      => $trueBalance <= 0 ? 'completed' : $record->status,
            ]);
        }

        // Inject corrected values into form
        $data['balance_due'] = $trueBalance;
        $data['amount_paid'] = round($actualPaid, 2);

        return $data;
    }

   protected function mutateFormDataBeforeSave(array $data): array
    {
        // Capture dehydrated(false) fields
        $this->depositMethod        = $this->data['initial_payment_method'] ?? null;
        $this->isSplitDeposit       = (bool) ($this->data['is_split_deposit'] ?? false);
        $this->splitDepositPayments = $this->data['split_deposit_payments'] ?? [];

     // ── CALCULATE GRAND TOTAL ────────
        $quoted         = floatval($data['quoted_price'] ?? $this->record->quoted_price ?? 0); 
        $discountAmount = floatval($data['discount_amount'] ?? $this->record->discount_amount ?? 0);
        $afterDiscount  = max(0, $quoted - $discountAmount);
        
        $hasWarranty    = ($data['has_warranty'] ?? $this->record->has_warranty ?? 0) == 1;
        $warrantyCharge = $hasWarranty ? floatval($data['warranty_charge'] ?? $this->record->warranty_charge ?? 0) : 0;
        
        $isTaxFree      = (bool)($data['is_tax_free'] ?? $this->record->is_tax_free ?? false);
        $dbTax          = DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? 7.63;
        $taxRate        = $isTaxFree ? 0 : floatval($dbTax) / 100;
        
        $tax            = ($afterDiscount + $warrantyCharge) * $taxRate;
        $grandTotal     = $afterDiscount + $warrantyCharge + $tax;
        // 🚀 THE FIX: If an admin explicitly edited the amount_paid field, TRUST THE FORM DATA.
        // Otherwise, fall back to what is currently in the DB.
        $formAmountPaid = floatval($data['amount_paid'] ?? 0);

        if (\App\Helpers\Staff::user()?->hasAnyRole(['Superadmin', 'Administration', 'Manager'])) {
            $actualPaid = $formAmountPaid;
        } else {
            $directPayments = Payment::where('custom_order_id', $this->record->id)->sum('amount');
            $salePayments   = 0;
            if ($this->record->sale_id) {
                $salePayments = Payment::where('sale_id', $this->record->sale_id)->whereNull('custom_order_id')->sum('amount');
            }
            $actualPaid = $directPayments + $salePayments;
        }

        $data['amount_paid'] = round($actualPaid, 2);
        $data['balance_due'] = round(max(0, $grandTotal - $actualPaid), 2);

        return $data;
    }

    protected function afterSave(): void
    {
        $order = $this->record->fresh();

        // Only Superadmin, Administration or Manager can update payment records
        $canEdit = \App\Helpers\Staff::user()?->hasAnyRole(['Superadmin', 'Administration', 'Manager']);
        if (!$canEdit) {
            return;
        }

        // Nothing to update if no payment method was provided
        if (!$this->depositMethod && empty($this->splitDepositPayments)) {
            return;
        }

        DB::transaction(function () use ($order) {

            if ($this->isSplitDeposit && !empty($this->splitDepositPayments)) {
                // ── SPLIT: delete old payments and re-create ───────────────
                Payment::where('custom_order_id', $order->id)->delete();

                foreach ($this->splitDepositPayments as $payment) {
                    Payment::create([
                        'custom_order_id' => $order->id,
                        'sale_id'         => $order->sale_id ?? null,
                        'amount'          => round((float) $payment['amount'], 2),
                        'method'          => strtoupper(trim($payment['method'])),
                        'paid_at'         => now(),
                    ]);
                }

            } elseif ($this->depositMethod) {
                // ── SINGLE: get all payments for this custom order ─────────
                $existingPayments = Payment::where('custom_order_id', $order->id)
                    ->orderBy('paid_at', 'asc')
                    ->get();

                // Fallback: find orphaned payment if none linked yet
                if ($existingPayments->isEmpty()) {
                    $existingPayments = Payment::whereNull('custom_order_id')
                        ->whereNull('sale_id')
                        ->where('amount', round((float) $order->amount_paid, 2))
                        ->orderBy('paid_at', 'asc')
                        ->get();
                }

                if ($existingPayments->count() > 1) {
                    // Duplicates exist — keep first, delete the rest
                    $first = $existingPayments->first();
                    Payment::where('custom_order_id', $order->id)
                        ->where('id', '!=', $first->id)
                        ->delete();

                    $first->update([
                        'method'          => strtoupper(trim($this->depositMethod)),
                        'amount'          => round((float) $order->amount_paid, 2),
                        'custom_order_id' => $order->id,
                    ]);

                } elseif ($existingPayments->count() === 1) {
                    // Update existing payment
                    $existingPayments->first()->update([
                        'method'          => strtoupper(trim($this->depositMethod)),
                        'amount'          => round((float) $order->amount_paid, 2),
                        'custom_order_id' => $order->id,
                    ]);

                } else {
                    // No payment exists — create one
                    Payment::create([
                        'custom_order_id' => $order->id,
                        'sale_id'         => $order->sale_id ?? null,
                        'amount'          => round((float) $order->amount_paid, 2),
                        'method'          => strtoupper(trim($this->depositMethod)),
                        'paid_at'         => now(),
                    ]);
                }
            }

            // ── SYNC LINKED SALE ───────────────────────────────────────────
            if ($order->sale_id) {
                $sale = Sale::find($order->sale_id);
                if ($sale) {
                    $totalPaid = $sale->payments()->sum('amount');
                    $balance   = round(max(0, $sale->final_total - $totalPaid), 2);
                    $sale->update([
                        'amount_paid'  => $totalPaid,
                        'balance_due'  => $balance,
                        'status'       => $balance <= 0 ? 'completed' : 'pending',
                        'completed_at' => $balance <= 0 ? now() : null,
                    ]);
                }
            }
        });

        Notification::make()
            ->title('Payment Updated')
            ->body('Payment method and EOD records have been updated.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}