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
                ->visible(fn () => Staff::user()?->hasRole('Superadmin') || $this->record->status !== 'completed'),

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

                    // 2. If money is still owed, automatically log it as paid today
                    if ($remaining > 0) {
                        \App\Models\Payment::create([
                            'sale_id' => $sale->id,
                            'amount'  => $remaining,
                            'method'  => $sale->payment_method ?? 'cash',
                            'paid_at' => now(), // Puts the money in TODAY'S End of Day
                        ]);
                        
                        // Update the internal amount_paid tracker
                        $sale->update(['amount_paid' => $sale->final_total]);
                    }

                    // 3. Mark the sale as officially completed
                    $sale->update([
                        'status'       => 'completed',
                        'completed_at' => now(),
                    ]);

                    Notification::make()->title('Sale Finalized & Paid In Full')->success()->send();
                    
                    // Force a hard refresh so the UI updates the Balance Due to $0.00
                    return redirect(request()->header('Referer'));
                }),
        ];
    }

    // 👇 ADD THIS — fixes amount_paid display on edit load
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record    = $this->record;
        $method    = strtolower($record->payment_method ?? '');
        $totalPaid = $record->payments->sum('amount');

        if (!$record->is_split_payment && !in_array($method, ['cash', 'laybuy', ''])) {
            // Non-cash (Visa, Katapult, etc.) — always show full total
            $data['amount_paid'] = $record->final_total;
        } elseif ($totalPaid > 0) {
            // Cash — show what was actually paid
            $data['amount_paid'] = $totalPaid;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['status'] ?? null) === 'completed' && !$this->record->completed_at) {
            $data['completed_at'] = now();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $sale = $this->record->fresh();

        DB::transaction(function () use ($sale) {
            $sale->load('items');

            $alreadyPaidTotal = $sale->payments()->sum('amount');

            $newIntendedTotal = 0;
            if ($sale->is_split_payment) {
                $splits = is_string($sale->split_payments)
                    ? json_decode($sale->split_payments, true)
                    : $sale->split_payments;
                if (is_array($splits)) {
                    $newIntendedTotal = collect($splits)->sum('amount');
                }
            } else {
                // 🚀 THE FIX: Calculate delta against actual amount_paid, not the final bill total
                $newIntendedTotal = min((float)$sale->amount_paid, (float)$sale->final_total);
            }

            $delta = round($newIntendedTotal - $alreadyPaidTotal, 2);

            if ($delta <= 0) {
                Notification::make()
                    ->title('Sale Updated')
                    ->body('No new payment detected. EOD records unchanged.')
                    ->info()
                    ->send();
                return;
            }

            $method = $sale->payment_method;
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