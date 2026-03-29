<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;
use Filament\Notifications\Notification;
use App\Helpers\Staff;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn() => Staff::user()?->hasRole('Superadmin') || $this->record->status !== 'completed'),

            Actions\Action::make('complete_sale')
                ->label('Finalize Sale (Today)')
                ->color('success')
                ->icon('heroicon-o-check')
                ->visible(fn() => $this->record->status !== 'completed' && Staff::user()?->hasAnyRole(['Superadmin', 'Administration']))
                ->requiresConfirmation()
                ->action(function () {
                    $sale = $this->record;

                    // 1. Calculate how much is still owed
                    $totalPaid = $sale->payments()->sum('amount');
                    $remaining = round($sale->final_total - $totalPaid, 2);

                    // 2. If money is still owed, log it as paid today
                    if ($remaining > 0) {
                        \App\Models\Payment::create([
                            'sale_id' => $sale->id,
                            'amount'  => $remaining,
                            'method'  => strtoupper(trim($sale->payment_method ?? 'CASH')),
                            'paid_at' => now(),
                        ]);

                        $sale->update(['amount_paid' => $sale->final_total]);
                    }

                    // 3. Mark as completed
                    $sale->update([
                        'status'       => 'completed',
                        'completed_at' => now(),
                    ]);

                    Notification::make()->title('Sale Finalized & Paid In Full')->success()->send();

                    return redirect(request()->header('Referer'));
                }),
        ];
    }

    /**
     * Populate amount_paid correctly when loading the edit form.
     * Logic is based on split vs non-split — no hardcoded method name checks.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record    = $this->record;
        $totalPaid = $record->payments->sum('amount');

        if ($record->is_split_payment) {
            // Split payment — show total paid across all methods
            $data['amount_paid'] = $totalPaid > 0
                ? $totalPaid
                : $record->final_total;
        } else {
            // ✅ FIXED: amount_paid column first — it stores what cashier physically entered
            // payments sum second — may equal final_total for completed sales (not what was handed over)
            if (floatval($record->amount_paid) > 0) {
                $data['amount_paid'] = $record->amount_paid;
            } elseif ($totalPaid > 0) {
                $data['amount_paid'] = $totalPaid;
            } else {
                $data['amount_paid'] = $record->final_total;
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->record;

        // ── AUTO-CORRECT STATUS BASED ON ACTUAL PAYMENTS ──────────────────────
        // Get total already paid from payments table
        $alreadyPaid = $record->payments()->sum('amount');

        // Add any new payment being entered now
        if ($record->is_split_payment) {
            $splits      = $data['split_payments'] ?? [];
            $newIntended = collect($splits)->sum(fn($p) => (float)($p['amount'] ?? 0));
        } else {
            $newIntended = floatval($data['amount_paid'] ?? 0);
        }

        // Use the higher of already paid vs newly entered
        $totalPaid  = max($alreadyPaid, $newIntended);
        $finalTotal = floatval($record->final_total);
        $balance    = round($finalTotal - $totalPaid, 2);

        if ($balance <= 0) {
            // Fully paid — force completed
            $data['status']      = 'completed';
            $data['balance_due'] = 0;
            if (!$record->completed_at) {
                $data['completed_at'] = now();
            }
        } else {
            // Still has balance — force pending
            $data['status']       = 'pending';
            $data['balance_due']  = $balance;
            $data['completed_at'] = null; // clear completed_at if it was set
        }

        // ── ACTIVITY LOG for completed sale edits ─────────────────────────────
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

    /**
     * After saving, sync payments correctly per method.
     *
     * For split payments: compare intended amount per method vs already paid per method
     * and only record the delta per method — never a single lump sum under wrong method.
     *
     * For non-split: record only the new delta as a single payment.
     */
    protected function afterSave(): void
    {
        $sale = $this->record->fresh();

        DB::transaction(function () use ($sale) {
            $sale->load('items');

            if ($sale->is_split_payment) {
                // ── SPLIT PAYMENT: per-method delta sync ──────────────────────
                $splits = is_string($sale->split_payments)
                    ? json_decode($sale->split_payments, true)
                    : $sale->split_payments;

                if (!is_array($splits) || empty($splits)) {
                    return;
                }

                // What the split_payments JSON intends per method (normalized to uppercase)
                $intendedByMethod = collect($splits)
                    ->groupBy(fn($s) => strtoupper(trim($s['method'])))
                    ->map(fn($g) => round($g->sum(fn($s) => (float) $s['amount']), 2));

                // What has already been recorded in payments table per method
                $paidByMethod = $sale->payments()->get()
                    ->groupBy(fn($p) => strtoupper(trim($p->method)))
                    ->map(fn($g) => round($g->sum('amount'), 2));

                $anyChange = false;

                foreach ($intendedByMethod as $method => $intendedAmount) {
                    $alreadyPaid = $paidByMethod[$method] ?? 0;
                    $methodDelta = round($intendedAmount - $alreadyPaid, 2);

                    if ($methodDelta > 0) {
                        // More paid on this method — record the extra
                        Payment::create([
                            'sale_id' => $sale->id,
                            'amount'  => $methodDelta,
                            'method'  => $method,
                            'paid_at' => now(),
                        ]);
                        $anyChange = true;

                    } elseif ($methodDelta < 0) {
                        // Overpaid on this method — adjust latest payment for this method
                        // NOTE: no date restriction — allows adjusting payments from previous days
                        $latestPayment = $sale->payments()
                            ->whereRaw('UPPER(TRIM(method)) = ?', [$method])
                            ->latest()
                            ->first();

                        if ($latestPayment) {
                            $correctedAmount = round($latestPayment->amount + $methodDelta, 2);
                            if ($correctedAmount <= 0) {
                                $latestPayment->delete();
                            } else {
                                $latestPayment->update(['amount' => $correctedAmount]);
                            }
                            $anyChange = true;
                        }
                    }
                }

                // Sync amount_paid on sale to total of split_payments JSON
                $totalIntended = round($intendedByMethod->sum(), 2);
                $sale->update(['amount_paid' => $totalIntended]);

                if ($anyChange) {
                    Notification::make()
                        ->title('Payments Updated')
                        ->body('Split payment records synced. Total collected: $' . number_format($totalIntended, 2))
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Sale Updated')
                        ->body('No payment changes detected.')
                        ->info()
                        ->send();
                }

            } else {
                // ── NON-SPLIT: single delta payment ───────────────────────────
                $alreadyPaidTotal = $sale->payments()->sum('amount');

                $amountPaid       = floatval($sale->amount_paid ?? 0);
                $newIntendedTotal = min(
                    $amountPaid > 0 ? $amountPaid : (float) $sale->final_total,
                    (float) $sale->final_total
                );

                $delta = round($newIntendedTotal - $alreadyPaidTotal, 2);

                if ($delta > 0) {
                    Payment::create([
                        'sale_id' => $sale->id,
                        'amount'  => $delta,
                        'method'  => strtoupper(trim($sale->payment_method ?? 'CASH')),
                        'paid_at' => now(),
                    ]);

                    // Sync amount_paid
                    $sale->update(['amount_paid' => round($alreadyPaidTotal + $delta, 2)]);

                    Notification::make()
                        ->title('New Payment Recorded')
                        ->body('$' . number_format($delta, 2) . ' added to today\'s EOD.')
                        ->success()
                        ->send();

                } elseif ($delta < 0) {
                    // ── FIX: removed ->whereDate('paid_at', today()) so we can adjust
                    //    payments from previous days when a sale is edited to a lower amount
                    $latestPayment = $sale->payments()->latest()->first();

                    if ($latestPayment) {
                        $correctedAmount = round($latestPayment->amount + $delta, 2);

                        if ($correctedAmount <= 0) {
                            $latestPayment->delete();
                        } else {
                            $latestPayment->update(['amount' => $correctedAmount]);
                        }

                        // Sync amount_paid
                        $sale->update(['amount_paid' => round($newIntendedTotal, 2)]);

                        Notification::make()
                            ->title('Payment Adjusted')
                            ->body('Payment corrected by $' . number_format(abs($delta), 2) . '. Balance due: $' . number_format($sale->final_total - $newIntendedTotal, 2))
                            ->warning()
                            ->send();
                    }

                } else {
                    Notification::make()
                        ->title('Sale Updated')
                        ->body('No payment changes detected.')
                        ->info()
                        ->send();
                }
            }
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}