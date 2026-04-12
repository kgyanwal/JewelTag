<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;
use Filament\Notifications\Notification;
use App\Helpers\Staff;
use App\Models\Payment;
use App\Models\DailyClosing;
use App\Models\SaleEditRequest;
use Illuminate\Support\Facades\DB;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    protected function isDayClosed(): bool
    {
        $saleDate = $this->record->created_at->format('Y-m-d');
        return DailyClosing::whereDate('closing_date', $saleDate)->exists();
    }

    protected function isLaybuyOrCustom(): bool
    {
        $sale = $this->record;
        if ($sale->payment_method === 'laybuy') return true;
        $sale->loadMissing('items');
        return $sale->items->contains(fn($i) => !empty($i->custom_order_id));
    }

    protected function canEditFreely(): bool
    {
        $user = Staff::user();

        if ($user?->hasAnyRole(['Superadmin', 'Administration', 'Manager'])) return true;

        $sale = $this->record;

        if ($this->isLaybuyOrCustom()) return true;
        if ($sale->status === 'pending') return true;
        if ($sale->status === 'completed' && floatval($sale->balance_due) > 0) return true;
        if ($sale->status === 'completed' && floatval($sale->balance_due) <= 0 && !$this->isDayClosed()) return true;

        // Check approved edit request
        if ($this->isDayClosed()) {
            return SaleEditRequest::where('sale_id', $sale->id)
                ->where('user_id', auth()->id())
                ->where('status', 'approved')
                ->exists();
        }

        return false;
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if (!$this->canEditFreely()) {
            Notification::make()
                ->title('Edit Restricted')
                ->body('This day is EOD locked. Use "Request Edit" from the sales list.')
                ->danger()
                ->send();

            redirect($this->getResource()::getUrl('index'));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn() =>
                    Staff::user()?->hasRole('Superadmin') ||
                    $this->record->status !== 'completed'
                ),

            Actions\Action::make('complete_sale')
                ->label('Finalize Sale (Today)')
                ->color('success')
                ->icon('heroicon-o-check')
                ->visible(fn() =>
                    $this->record->status !== 'completed' &&
                    Staff::user()?->hasAnyRole(['Superadmin', 'Administration', 'Manager'])
                )
                ->requiresConfirmation()
                ->action(function () {
                    $sale      = $this->record;
                    $totalPaid = $sale->payments()->sum('amount');
                    $remaining = round($sale->final_total - $totalPaid, 2);

                    if ($remaining > 0) {
                        \App\Models\Payment::create([
                            'sale_id' => $sale->id,
                            'amount'  => $remaining,
                            'method'  => strtoupper(trim($sale->payment_method ?? 'CASH')),
                            'paid_at' => now(),
                        ]);
                        $sale->update(['amount_paid' => $sale->final_total]);
                    }

                    $sale->update(['status' => 'completed', 'completed_at' => now()]);
                    Notification::make()->title('Sale Finalized & Paid In Full')->success()->send();
                    return redirect(request()->header('Referer'));
                }),
        ];
    }

   protected function mutateFormDataBeforeFill(array $data): array
{
    $record    = $this->record;
    $totalPaid = $record->payments->sum('amount');

    if ($record->is_split_payment) {
        $data['amount_paid'] = $totalPaid > 0 ? $totalPaid : $record->final_total;
    } else {
        if (floatval($record->amount_paid) > 0) {
            $data['amount_paid'] = $record->amount_paid;
        } elseif ($totalPaid > 0) {
            $data['amount_paid'] = $totalPaid;
        } else {
            $data['amount_paid'] = $record->final_total;
        }
    }

    // ── FIX: Hydrate custom order data into repeater items ────────────
    $record->loadMissing('items.customOrder');

    if (isset($data['items']) && is_array($data['items'])) {
        foreach ($data['items'] as $key => $item) {
            $customOrderId = $item['custom_order_id'] ?? null;

            if ($customOrderId) {
                $customOrder = \App\Models\CustomOrder::find($customOrderId);
                if ($customOrder) {
                    // Rebuild the new_custom_data JSON so the repeater
                    // knows this is a custom order item and shows it correctly
                    $data['items'][$key]['is_new_custom_order']  = false; // it's existing, not new
                    $data['items'][$key]['stock_no_display']     = 'CUSTOM #' . $customOrder->order_no;
                    $data['items'][$key]['custom_description']   = $item['custom_description']
                        ?? "CUSTOM Order: {$customOrder->product_name}\nMetal: {$customOrder->metal_type}";
                    $data['items'][$key]['sold_price']           = $customOrder->quoted_price;
                    $data['items'][$key]['sale_price_override']  = $customOrder->quoted_price;
                    $data['items'][$key]['qty']                  = 1;
                    $data['items'][$key]['is_tax_free']          = (bool) $customOrder->is_tax_free;
                    $data['items'][$key]['discount_percent']     = 0;
                    $data['items'][$key]['discount_amount']      = 0;
                }
            }
        }
    }

    return $data;
}

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!$this->canEditFreely()) {
            Notification::make()->title('Day Locked')->body('Cannot save — EOD closed.')->danger()->send();
            $this->halt();
        }

        $record      = $this->record;
        $alreadyPaid = $record->payments()->sum('amount');

        if ($record->is_split_payment) {
            $splits      = $data['split_payments'] ?? [];
            $newIntended = collect($splits)->sum(fn($p) => (float)($p['amount'] ?? 0));
        } else {
            $newIntended = floatval($data['amount_paid'] ?? 0);
        }

        $totalPaid  = max($alreadyPaid, $newIntended);
        $finalTotal = floatval($record->final_total);
        $balance    = round($finalTotal - $totalPaid, 2);

        if ($balance <= 0) {
            $data['status']      = 'completed';
            $data['balance_due'] = 0;
            if (!$record->completed_at) $data['completed_at'] = now();
        } else {
            $data['status']       = 'pending';
            $data['balance_due']  = $balance;
            $data['completed_at'] = null;
        }

        if ($record->status === 'completed') {
            \App\Models\ActivityLog::create([
                'user_id'    => auth()->id(),
                'action'     => 'Updated',
                'module'     => 'Sale',
                'identifier' => $record->invoice_number,
                'changes'    => json_encode([
                    'note'        => 'Completed sale was edited',
                    'edited_by'   => auth()->user()->name,
                    'ip'          => request()->ip(),
                    'final_total' => $record->final_total,
                    'status'      => $record->status,
                ]),
                'url'        => '/' . request()->path(),
                'ip_address' => request()->ip(),
            ]);
        }

        return $data;
    }

   protected function afterSave(): void
    {
        $sale = $this->record->fresh();

        DB::transaction(function () use ($sale) {
            $sale->load('items');

            // 1. Find all payments attached to this sale that are NOT prior custom order deposits.
            // 🚀 THE FIX: Removed whereNull('laybuy_id') because that column doesn't exist on the payments table.
            $pointOfSalePayments = $sale->payments()
                ->whereNull('custom_order_id')
                ->get();

            // 2. Wipe the old POS payments clean to prevent double-counting or messy deltas.
            foreach ($pointOfSalePayments as $payment) {
                $payment->delete();
            }

            // 3. Determine how much money the user says they collected RIGHT NOW in the edit form.
            $data = $this->form->getState();
            $newPosTotalPaid = 0;

            if (!empty($data['is_split_payment'])) {
                $splits = $data['split_payments'] ?? [];
                if (is_array($splits)) {
                    foreach ($splits as $split) {
                        $amt = floatval($split['amount'] ?? 0);
                        if ($amt > 0) {
                            Payment::create([
                                'sale_id'  => $sale->id,
                                'amount'   => $amt,
                                'method'   => strtoupper(trim($split['method'])),
                                'paid_at'  => $sale->created_at, // Keep original sale time
                                'store_id' => $sale->store_id,
                            ]);
                            $newPosTotalPaid += $amt;
                        }
                    }
                }
            } else {
                $amt = floatval($data['amount_paid'] ?? 0);
                if ($amt > 0) {
                    Payment::create([
                        'sale_id'  => $sale->id,
                        'amount'   => $amt,
                        'method'   => strtoupper(trim($data['payment_method'] ?? 'CASH')),
                        'paid_at'  => $sale->created_at, // Keep original sale time
                        'store_id' => $sale->store_id,
                    ]);
                    $newPosTotalPaid += $amt;
                }
            }

            // 4. Calculate the TRUE total paid for this sale (New POS money + Any old deposits)
            // 🚀 THE FIX: Removed the laybuy_id check here as well.
            $totalPriorDeposits = $sale->payments()
                ->whereNotNull('custom_order_id')
                ->sum('amount');

            $trueTotalPaid = round($totalPriorDeposits + $newPosTotalPaid, 2);
            $finalTotal    = round(floatval($sale->final_total), 2);
            $balanceDue    = max(0, $finalTotal - $trueTotalPaid);

            // 5. Update the Sale record with the perfect math
            $sale->update([
                'amount_paid'  => $trueTotalPaid,
                'balance_due'  => $balanceDue,
                'status'       => $balanceDue <= 0 ? 'completed' : 'pending',
                'completed_at' => $balanceDue <= 0 ? ($sale->completed_at ?? now()) : null,
            ]);

            Notification::make()
                ->title('Sale Payments Updated')
                ->body('Ledger has been synced successfully.')
                ->success()
                ->send();
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}