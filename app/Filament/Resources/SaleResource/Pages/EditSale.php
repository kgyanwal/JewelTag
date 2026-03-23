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
                            'method'  => $sale->payment_method ?? 'cash',
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
        if (($data['status'] ?? null) === 'completed' && !$this->record->completed_at) {
            $data['completed_at'] = now();
        }

        // ✅ Log when a completed sale is edited — writes directly to activity_log table
        if ($this->record->status === 'completed') {
            \App\Models\ActivityLog::create([
                'user_id'    => auth()->id(),
                'action'     => 'Updated',
                'module'     => 'Sale',
                'identifier' => $this->record->invoice_number,
                'changes'    => json_encode([
                    'note'          => 'Completed sale was edited',
                    'edited_by'     => auth()->user()->name,
                    'ip'            => request()->ip(),
                    'final_total'   => $this->record->final_total,
                    'status'        => $this->record->status,
                ]),
                'url'        => '/' . request()->path(),
                'ip_address' => request()->ip(),
            ]);
        }

        return $data;
    }

    /**
     * After saving, record only the NEW delta payment — never delete existing payments.
     * This ensures past EOD records are never corrupted by edits.
     */
    protected function afterSave(): void
    {
        $sale = $this->record->fresh();

        DB::transaction(function () use ($sale) {
            $sale->load('items');

            $alreadyPaidTotal = $sale->payments()->sum('amount');

            if ($sale->is_split_payment) {
                $splits = is_string($sale->split_payments)
                    ? json_decode($sale->split_payments, true)
                    : $sale->split_payments;
                $newIntendedTotal = is_array($splits) ? collect($splits)->sum('amount') : 0;
            } else {
                $amountPaid       = floatval($sale->amount_paid ?? 0);
                $newIntendedTotal = min(
                    $amountPaid > 0 ? $amountPaid : (float) $sale->final_total,
                    (float) $sale->final_total
                );
            }

            $delta = round($newIntendedTotal - $alreadyPaidTotal, 2);

            // ✅ New payment needed
            if ($delta > 0) {
                $method = $sale->payment_method;
                if ($sale->is_split_payment) {
                    $splits = is_string($sale->split_payments)
                        ? json_decode($sale->split_payments, true)
                        : $sale->split_payments;
                    $method = collect($splits)->last()['method'] ?? $method;
                }

                Payment::create([
                    'sale_id' => $sale->id,
                    'amount'  => $delta,
                    'method'  => $method,
                    'paid_at' => now(),
                ]);

                Notification::make()
                    ->title('New Payment Recorded')
                    ->body('$' . number_format($delta, 2) . ' added to today\'s EOD.')
                    ->success()
                    ->send();
                return;
            }

            // ✅ Cashier corrected a lower amount — adjust the latest payment
            // Only adjust if the latest payment was made today (same day correction)
            if ($delta < 0) {
                $latestPayment = $sale->payments()->whereDate('paid_at', today())->latest()->first();

                if ($latestPayment) {
                    $correctedAmount = round($latestPayment->amount + $delta, 2);

                    if ($correctedAmount <= 0) {
                        // Delta wipes out entire latest payment — delete it
                        $latestPayment->delete();
                    } else {
                        // Reduce the latest payment by the overpaid amount
                        $latestPayment->update(['amount' => $correctedAmount]);
                    }

                    Notification::make()
                        ->title('Payment Adjusted')
                        ->body('Payment corrected by $' . number_format(abs($delta), 2) . '. Balance due: $' . number_format($sale->final_total - $newIntendedTotal, 2))
                        ->warning()
                        ->send();
                    return;
                }
            }

            // No change
            Notification::make()
                ->title('Sale Updated')
                ->body('No payment changes detected.')
                ->info()
                ->send();
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}