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
            // ✅ Split payment — show total paid across all methods
            $data['amount_paid'] = $totalPaid > 0
                ? $totalPaid
                : $record->final_total;
        } else {
            // ✅ Single payment — show actual paid amount in priority order
            if ($totalPaid > 0) {
                // Most accurate: from actual payment records
                $data['amount_paid'] = $totalPaid;
            } elseif (floatval($record->amount_paid) > 0) {
                // Fallback: saved amount_paid column
                $data['amount_paid'] = $record->amount_paid;
            } else {
                // No records at all — default to full total as safe starting point
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
            DB::table('activity_log')->insert([
                'log_name'     => 'sale_edits',
                'description'  => 'Completed sale edited',
                'subject_type' => \App\Models\Sale::class,
                'subject_id'   => $this->record->id,
                'causer_type'  => \App\Models\User::class,
                'causer_id'    => auth()->id(),
                'properties'   => json_encode([
                    'invoice_number' => $this->record->invoice_number,
                    'edited_by'      => auth()->user()->name,
                    'ip'             => request()->ip(),
                    'final_total'    => $this->record->final_total,
                    'status_at_edit' => $this->record->status,
                ]),
                'created_at'   => now(),
                'updated_at'   => now(),
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

            // What has already been recorded in the payments table
            $alreadyPaidTotal = $sale->payments()->sum('amount');

            if ($sale->is_split_payment) {
                // Split: sum all split entries
                $splits = is_string($sale->split_payments)
                    ? json_decode($sale->split_payments, true)
                    : $sale->split_payments;
                $newIntendedTotal = is_array($splits) ? collect($splits)->sum('amount') : 0;
            } else {
                // Standard: use amount_paid (what cashier entered), capped at final_total
                // If amount_paid is 0, treat as full payment (non-cash methods auto-fill)
                $amountPaid       = floatval($sale->amount_paid ?? 0);
                $newIntendedTotal = min(
                    $amountPaid > 0 ? $amountPaid : (float) $sale->final_total,
                    (float) $sale->final_total
                );
            }

            // ✅ Only record the positive difference — never re-record old payments
            $delta = round($newIntendedTotal - $alreadyPaidTotal, 2);

            if ($delta <= 0) {
                Notification::make()
                    ->title('Sale Updated')
                    ->body('No new payment detected. EOD records unchanged.')
                    ->info()
                    ->send();
                return;
            }

            // Determine which method to record the delta under
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
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}