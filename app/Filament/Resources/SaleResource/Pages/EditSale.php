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
        $record = $this->record;
        
        // Grab POS payments (those created exactly with the sale, ignoring prior custom order deposits)
        $posPayments = $record->payments()->where('paid_at', '>=', $record->created_at)->get();
        
        // ── Hydrate the "payment_target" dropdowns for the UI ────────────
        if ($record->is_split_payment || $posPayments->count() > 1) {
            $data['is_split_payment'] = true;
            $data['split_payments'] = $posPayments->map(function ($p) {
                return [
                    'method'         => $p->method,
                    'amount'         => $p->amount,
                    'payment_target' => $p->custom_order_id ? 'custom' : 'regular',
                ];
            })->toArray();
        } else {
            $data['is_split_payment'] = false;
            $first = $posPayments->first();
            $data['amount_paid']    = $first?->amount ?? floatval($record->amount_paid);
            $data['payment_method'] = $first?->method ?? ($record->payment_method ?: 'CASH');
            $data['payment_target'] = ($first && $first->custom_order_id) ? 'custom' : 'regular';
        }

        // ── FIX: Hydrate ALL items data into repeater items ────────────
        $record->loadMissing(['items.customOrder', 'items.productItem', 'items.repair']);

        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $key => $item) {
                
                // 1. Handle Custom Orders
                if (!empty($item['custom_order_id'])) {
                    $customOrder = \App\Models\CustomOrder::find($item['custom_order_id']);
                    if ($customOrder) {
                        $data['items'][$key]['is_new_custom_order']  = false; 
                        $data['items'][$key]['stock_no_display']     = 'CUSTOM #' . $customOrder->order_no;
                        $data['items'][$key]['custom_description']   = $item['custom_description'] ?? "CUSTOM Order: {$customOrder->product_name}\nMetal: {$customOrder->metal_type}";
                        $data['items'][$key]['sold_price']           = $customOrder->quoted_price;
                        $data['items'][$key]['sale_price_override']  = $item['sale_price_override'] ?? $customOrder->quoted_price;
                        $data['items'][$key]['qty']                  = 1;
                        $data['items'][$key]['is_tax_free']          = (bool) $customOrder->is_tax_free;
                    }
                } 
                // 2. Handle Normal Inventory Items
                elseif (!empty($item['product_item_id'])) {
                    $productItem = \App\Models\ProductItem::find($item['product_item_id']);
                    if ($productItem) {
                        $data['items'][$key]['stock_no_display']    = $productItem->barcode;
                        $data['items'][$key]['custom_description']  = $item['custom_description'] ?? $productItem->custom_description ?? $productItem->barcode;
                        $data['items'][$key]['sold_price']          = $item['sold_price'] ?? $productItem->retail_price;
                        $data['items'][$key]['sale_price_override'] = $item['sale_price_override'] ?? ($productItem->retail_price * ($item['qty'] ?? 1));
                    }
                }
                // 3. Handle Repair Items
                elseif (!empty($item['repair_id'])) {
                    $repair = \App\Models\Repair::find($item['repair_id']);
                    if ($repair) {
                        $data['items'][$key]['stock_no_display']    = 'REPAIR #' . $repair->repair_no;
                        $data['items'][$key]['custom_description']  = $item['custom_description'] ?? 'Repair Service';
                    }
                }
                // 4. Handle Non-Tag Items
                else {
                     $data['items'][$key]['stock_no_display'] = 'NON-TAG';
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

        $record = $this->record;

        if (!empty($data['is_split_payment'])) {
            $newIntended = collect($data['split_payments'] ?? [])->sum(fn($p) => (float)($p['amount'] ?? 0));
        } else {
            $newIntended = floatval($data['amount_paid'] ?? 0);
        }

        // Add any deposits made BEFORE the sale was created
        $priorDeposits = $record->payments()->where('paid_at', '<', $record->created_at)->sum('amount');
        $totalPaid     = round($priorDeposits + $newIntended, 2);
        
        $finalTotal = floatval($record->final_total);
        $balance    = round($finalTotal - $totalPaid, 2);

        if ($balance <= 0) {
            $data['status']      = 'completed';
            $data['balance_due'] = 0;
            if (!$record->completed_at) $data['completed_at'] = now();
        } else {
            $data['status']       = 'pending';
            $data['balance_due']  = max(0, $balance);
            $data['completed_at'] = null;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $sale = $this->record->fresh();

        DB::transaction(function () use ($sale) {
            $sale->load('items');
            $data = $this->form->getState();

            // Find linked custom order
            $customOrderId = $sale->items->pluck('custom_order_id')->filter()->first();
            $customOrder = $customOrderId ? \App\Models\CustomOrder::find($customOrderId) : null;

            // 1. Wipe current POS payments only (keep prior deposits safe)
            $sale->payments()->where('paid_at', '>=', $sale->created_at)->delete();

            // 2. Re-create payments with correct targets
            $newPosTotalPaid = 0;
            $payments = !empty($data['is_split_payment']) 
                ? ($data['split_payments'] ?? []) 
                : [['amount' => $data['amount_paid'], 'method' => $data['payment_method'], 'payment_target' => $data['payment_target'] ?? 'regular']];

            foreach ($payments as $p) {
                $amt = floatval($p['amount'] ?? 0);
                if ($amt <= 0) continue;

                $target = $p['payment_target'] ?? 'regular';
                $isCustom = ($target === 'custom' && $customOrder);

                Payment::create([
                    'sale_id'         => $sale->id,
                    'custom_order_id' => $isCustom ? $customOrder->id : null,
                    'amount'          => $amt,
                    'method'          => strtoupper(trim($p['method'] ?? 'CASH')),
                    'paid_at'         => $sale->created_at,
                    'store_id'        => $sale->store_id,
                ]);
                $newPosTotalPaid += $amt;
            }

            // 3. Recalculate Custom Order totals if applicable
            if ($customOrder) {
                $allCustomPaid = Payment::where('custom_order_id', $customOrder->id)->sum('amount');
                $dbTax         = DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? 7.63;
                $taxRate       = $customOrder->is_tax_free ? 0 : floatval($dbTax) / 100;
                $orderTotal    = floatval($customOrder->quoted_price) * (1 + $taxRate);

                $customOrder->update([
                    'amount_paid' => round($allCustomPaid, 2),
                    'balance_due' => round(max(0, $orderTotal - $allCustomPaid), 2),
                ]);
            }

            // 4. Update the umbrella Sale record
            $finalTotalPaid = $sale->payments()->sum('amount');
            $sale->update(['amount_paid' => $finalTotalPaid]);

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