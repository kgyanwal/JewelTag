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
            // Standard Delete Action
            Actions\DeleteAction::make()
                ->visible(fn () => Staff::user()?->hasRole('Superadmin') || $this->record->status !== 'completed'),
            
            // Manual Completion Action for Admins
            Actions\Action::make('complete_sale')
                ->label('Finalize Sale (Today)')
                ->color('success')
                ->icon('heroicon-o-check')
                ->visible(fn() => $this->record->status !== 'completed' && Staff::user()?->hasAnyRole(['Superadmin', 'Administration']))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status' => 'completed',
                        'completed_at' => now(), 
                    ]);

                    Notification::make()->title('Sale Finalized')->success()->send();
                    $this->refreshFormData(['status', 'completed_at']);
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure completed_at is set if status is being changed to completed
        if (($data['status'] ?? null) === 'completed' && !$this->record->completed_at) {
            $data['completed_at'] = now();
        }

        return $data;
    }

    /**
     * 🚀 TRIGGER AFTER SAVE: Sync the Payments table!
     */
    protected function afterSave(): void
    {
        $sale = $this->record->fresh(); // Get latest DB state

        DB::transaction(function () use ($sale) {
            $sale->load('items');

            // 1. Sum what's already been paid (existing payment records — never touch these)
            $alreadyPaidTotal = $sale->payments()->sum('amount');

            // 2. Determine the new intended total
            $newIntendedTotal = 0;

            if ($sale->is_split_payment) {
                $splits = is_string($sale->split_payments)
                    ? json_decode($sale->split_payments, true)
                    : $sale->split_payments;

                if (is_array($splits)) {
                    $newIntendedTotal = collect($splits)->sum('amount');
                }
            } else {
                $newIntendedTotal = (float) $sale->final_total;
            }

            // 3. Only the positive difference is a NEW payment made today
            $delta = round($newIntendedTotal - $alreadyPaidTotal, 2);

            if ($delta <= 0) {
                // ✅ No new money added — nothing to record in EOD
                Notification::make()
                    ->title('Sale Updated')
                    ->body('No new payment detected. EOD records unchanged.')
                    ->info()
                    ->send();
                return;
            }

            // 4. Record ONLY the new delta as today's payment
            $method = $sale->payment_method;

            // For split payments, try to find the method of the latest split entry
            if ($sale->is_split_payment) {
                $splits = is_string($sale->split_payments)
                    ? json_decode($sale->split_payments, true)
                    : $sale->split_payments;
                $lastSplit = collect($splits)->last();
                $method = $lastSplit['method'] ?? $method;
            }

            Payment::create([
                'sale_id' => $sale->id,
                'amount'  => $delta,
                'method'  => $method,
                'paid_at' => now(), // ✅ Dated today — shows in today's EOD only
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