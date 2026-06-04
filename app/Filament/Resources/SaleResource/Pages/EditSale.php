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

    public function getSubheading(): ?\Illuminate\Support\HtmlString
    {
        if ($this->record->status === 'cancelled') {
            return new \Illuminate\Support\HtmlString("
                <span style='color: #dc2626; font-weight: 800; background-color: #fef2f2; padding: 4px 12px; border-radius: 6px; border: 1px solid #fee2e2; display: inline-block; margin-top: 4px;'>
                    ⚠️ THIS SALE HAS BEEN CANCELLED — Invoice #{$this->record->invoice_number} is deactivated and items are returned to stock registers.
                </span>
            ");
        }
        return null;
    }

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
                ->visible(
                    fn() =>
                    Staff::user()?->hasRole('Superadmin') ||
                        $this->record->status !== 'completed'
                ),

            Actions\Action::make('complete_sale')
                ->label('Finalize Sale (Today)')
                ->color('success')
                ->icon('heroicon-o-check')
                ->visible(
                    fn() =>
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

        // Load ALL payments from both tables
        $allDbPayments = $record->payments()->orderBy('paid_at')->get();
        $salePayments  = $record->salePayments()->orderBy('payment_date')->get();

        // Set default payment method from first payment
        $first = $allDbPayments->first();
        $data['payment_method'] = strtoupper($first?->method ?? ($record->payment_method ?: 'CASH'));
        $data['payment_target'] = ($first && $first->custom_order_id) ? 'custom' : 'regular';

        if ($allDbPayments->count() > 0 || $salePayments->count() > 0) {
            $splits = [];

            foreach ($allDbPayments as $p) {
    $splits[] = [
        'method'           => strtoupper(trim($p->method)),
        'amount'           => floatval($p->amount),
        'payment_target'   => $p->custom_order_id ? 'custom' : 'regular',
        'is_prior_deposit' => (bool) $p->custom_order_id,
    ];
}

            foreach ($salePayments as $p) {
                $splits[] = [
                    'method'         => strtoupper(trim($p->payment_method ?? 'CASH')),
                    'amount'         => floatval($p->amount),
                    'payment_target' => 'regular',
                ];
            }

            $data['is_split_payment'] = true;
            $data['split_payments']   = $splits;
            $data['amount_paid']      = 0;
        } else {
            $data['is_split_payment'] = false;
            $data['split_payments']   = [];
            $data['amount_paid']      = 0;
        }

        // Hydrate items
        $record->loadMissing(['items.customOrder', 'items.productItem', 'items.repair']);

        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $key => $item) {

                if (!empty($item['custom_order_id'])) {
                    $customOrder = \App\Models\CustomOrder::find($item['custom_order_id']);
                    if ($customOrder) {
                        $data['items'][$key]['is_new_custom_order'] = false;
                        $data['items'][$key]['stock_no_display']    = 'CUSTOM #' . $customOrder->order_no;
                        $data['items'][$key]['custom_description']  = $item['custom_description'] ?? "CUSTOM Order: {$customOrder->product_name}\nMetal: {$customOrder->metal_type}";
                        $data['items'][$key]['sold_price']          = $customOrder->quoted_price;
                        $data['items'][$key]['sale_price_override'] = $item['sale_price_override'] ?? $customOrder->quoted_price;
                        $data['items'][$key]['qty']                 = 1;
                        
                        // 🚀 SYSTEM PERSISTENCE FIX
                        $data['items'][$key]['is_tax_free'] = (bool) $customOrder->is_tax_free;
                        
                        if ((bool) $customOrder->is_tax_free) {
                            $data['items'][$key]['tax_amount'] = 0;
                        }
                    }
                } elseif (!empty($item['product_item_id'])) {
                    $productItem = \App\Models\ProductItem::find($item['product_item_id']);
                    if ($productItem) {
                        $data['items'][$key]['stock_no_display']    = $productItem->barcode;
                        $data['items'][$key]['custom_description']  = $item['custom_description'] ?? $productItem->custom_description ?? $productItem->barcode;
                        $data['items'][$key]['sold_price']          = $item['sold_price'] ?? $productItem->retail_price;
                        $data['items'][$key]['sale_price_override'] = $item['sale_price_override'] ?? (($item['sold_price'] * ($item['qty'] ?? 1)) - ($item['discount_amount'] ?? 0));
                    }
                } elseif (!empty($item['repair_id'])) {
                    $repair = \App\Models\Repair::find($item['repair_id']);
                    if ($repair) {
                        $data['items'][$key]['stock_no_display']   = 'REPAIR #' . $repair->repair_no;
                        $data['items'][$key]['custom_description'] = $item['custom_description'] ?? 'Repair Service';
                    }
                } else {
                    $data['items'][$key]['stock_no_display'] = 'NON-TAG';
                }
            }
        }

        // 🚀 CRITICAL FIX: Explicitly lock final values out of the original parent parameters on load
        $data['final_total']   = number_format(floatval($record->final_total), 2, '.', '');
        $data['subtotal']      = number_format(floatval($record->subtotal), 2, '.', '');
        $data['tax_amount']    = number_format(floatval($record->tax_amount), 2, '.', '');
        $data['balance_due']   = number_format(floatval($record->balance_due), 2, '.', '');

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!$this->canEditFreely()) {
            Notification::make()->title('Day Locked')->body('Cannot save — EOD closed.')->danger()->send();
            $this->halt();
        }

        $record = $this->record;

        // Filter payments correctly by target to prevent layout inversion loops
        $allDbPaid = $record->payments()->whereNull('custom_order_id')->sum('amount')
            + $record->salePayments()->sum('amount');

        if (!empty($data['is_split_payment'])) {
            $formTotal = collect($data['split_payments'] ?? [])
                ->where('payment_target', '!=', 'custom') // 🚀 CRITICAL FIX: Ignore custom order deposits
                ->sum(fn($p) => (float) ($p['amount'] ?? 0));
            $newIntended = max(0, round($formTotal - $allDbPaid, 2));
        } else {
            $newIntended = floatval($data['amount_paid'] ?? 0);
        }

        // Explicitly mapping inline columns to prevent front-end form wipe outs
        $customDeposit = collect($data['split_payments'] ?? [])->firstWhere('payment_target', 'custom');
        if ($customDeposit) {
            $data['payment_method_1'] = $customDeposit['method'] ?? 'CASH';
            $data['payment_amount_1'] = floatval($customDeposit['amount'] ?? 0);
        }

        $totalPaid  = round($allDbPaid + $newIntended, 2);
        $finalTotal = floatval($record->final_total);

        // Add custom deposit values back into base validations
        $baseDeposit = collect($data['split_payments'] ?? [])
    ->where('payment_target', 'custom')
    ->filter(fn($p) => empty($p['is_prior_deposit']))
    ->sum(fn($p) => (float)($p['amount'] ?? 0));

        $balance = round($finalTotal - ($totalPaid + $baseDeposit), 2);

        if ($balance <= 0) {
            $data['status']      = 'completed';
            $data['amount_paid'] = $totalPaid + $baseDeposit;
            $data['balance_due'] = 0;
        } else {
            $data['status']       = 'pending';
            $data['amount_paid']  = $totalPaid + $baseDeposit;
            $data['balance_due']  = max(0, $balance);
        }

        return $data;
    }

  protected function afterSave(): void
    {
        $sale = $this->record->fresh();

        DB::transaction(function () use ($sale) {
            $sale->load('items');
            $data = $this->form->getState();

            $customOrderId = $sale->items->pluck('custom_order_id')->filter()->first();
            $customOrder   = $customOrderId ? \App\Models\CustomOrder::find($customOrderId) : null;

            // 1. Existing POS payments in DB
            // 🚀 CRITICAL FIX: Removed `where('paid_at', '>=')` clause. 
            // We MUST fetch historical custom deposits so we don't accidentally duplicate them!
            $existingDirectPayments = $sale->payments()->get();
            $existingSalePayments   = $sale->salePayments()->get();

            // Total already in DB across both tables
            $totalAlreadyInDb = round($existingDirectPayments->sum('amount') + $existingSalePayments->sum('amount'), 2);

            // 2. Form total — includes existing rows + NEW rows staff added
            $formPayments = !empty($data['is_split_payment'])
                ? ($data['split_payments'] ?? [])
                : [[
                    'amount'         => $data['amount_paid'] ?? 0,
                    'method'         => $data['payment_method'] ?? 'CASH',
                    'payment_target' => $data['payment_target'] ?? 'regular',
                ]];

            $formTotal = round(collect($formPayments)->sum(fn($p) => floatval($p['amount'] ?? 0)), 2);

            // 3. Delta = truly NEW money only
            $delta = round($formTotal - $totalAlreadyInDb, 2);

            if ($delta > 0) {
                // Build lookup of ALL existing payments (both tables)
                $existingByMethod = [];
                foreach ($existingDirectPayments as $ep) {
                    $key = strtoupper(trim($ep->method));
                    $existingByMethod[$key] = ($existingByMethod[$key] ?? 0) + floatval($ep->amount);
                }
                foreach ($existingSalePayments as $sp) {
                    $key = strtoupper(trim($sp->payment_method ?? 'CASH'));
                    $existingByMethod[$key] = ($existingByMethod[$key] ?? 0) + floatval($sp->amount);
                }

                foreach ($formPayments as $p) {
                    $amt    = floatval($p['amount'] ?? 0);
                    $method = strtoupper(trim($p['method'] ?? 'CASH'));

                    if ($amt <= 0) continue;

                    $alreadyRecorded = $existingByMethod[$method] ?? 0;

                    if ($alreadyRecorded >= $amt) {
                        $existingByMethod[$method] -= $amt;
                        continue;
                    }

                    $newAmt = $amt - $alreadyRecorded;
                    $existingByMethod[$method] = 0;

                    $target   = $p['payment_target'] ?? 'regular';
                    $isCustom = ($target === 'custom' && $customOrder);

                    Payment::create([
                        'sale_id'         => $sale->id,
                        'custom_order_id' => $isCustom ? $customOrder->id : null,
                        'amount'          => round($newAmt, 2),
                        'method'          => $method,
                        'paid_at'         => now(),
                        'store_id'        => $sale->store_id,
                    ]);
                }

            } elseif ($delta < 0) {
                // Deletes the most recent payment if they reduced the overall total
                $sale->payments()
                    ->latest('paid_at')
                    ->first()
                    ?->delete();
            }

            // Sync the databases with correct sums
            if ($customOrder) {
                $allCustomPaid = Payment::where('custom_order_id', $customOrder->id)->sum('amount');
                $dbTax         = DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? 7.63;
                $taxRate       = $customOrder->is_tax_free ? 0 : floatval($dbTax) / 100;
                $orderTotal    = floatval($customOrder->quoted_price) * (1 + $taxRate);

               $newCoBal = round(max(0, $orderTotal - $allCustomPaid), 2);

$customOrder->update([
    'amount_paid' => round($allCustomPaid, 2),
    'balance_due' => $newCoBal,
    'status'      => $newCoBal <= 0.01 ? 'completed' : $customOrder->status,
    'sale_id'     => $sale->id,
]);

if ($newCoBal <= 0.01 && $customOrder->status !== 'completed') {
    Notification::make()
        ->title('Custom Order Completed')
        ->body("Order #{$customOrder->order_no} is now fully paid.")
        ->success()
        ->send();
}
            }

            $finalTotalPaid = $sale->payments()->sum('amount');
            // ── Sync linked Laybuy if exists ──────────────────────────────────
$laybuy = \App\Models\Laybuy::where('sale_id', $sale->id)->first();
if ($laybuy) {
    $totalSalePaid = Payment::where('sale_id', $sale->id)->sum('amount');
    $laybuyBal     = round(max(0, floatval($laybuy->total_amount) - $totalSalePaid), 2);

    $laybuy->update([
        'amount_paid'    => round($totalSalePaid, 2),
        'balance_due'    => $laybuyBal,
        'status'         => $laybuyBal <= 0.01 ? 'completed' : 'in_progress',
        'last_paid_date' => now(),
    ]);

    // Also create a LaybuyPayment ledger entry for the new payment
    $newPayments = Payment::where('sale_id', $sale->id)
        ->where('paid_at', '>=', now()->subSeconds(30))
        ->get();

    foreach ($newPayments as $np) {
        $alreadyLogged = \App\Models\LaybuyPayment::where('laybuy_id', $laybuy->id)
            ->where('amount', $np->amount)
            ->where('created_at', '>=', now()->subSeconds(30))
            ->exists();

        if (!$alreadyLogged) {
            \App\Models\LaybuyPayment::create([
                'laybuy_id'      => $laybuy->id,
                'amount'         => $np->amount,
                'payment_method' => $np->method,
                'created_at'     => $np->paid_at,
                'updated_at'     => $np->paid_at,
            ]);
        }
    }
}
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
