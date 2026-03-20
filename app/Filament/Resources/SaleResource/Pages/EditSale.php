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
        DB::transaction(function () {
            $sale = $this->record;
            
            // 1. Force the Sale model to fetch the new items and run its math hook
            $sale->load('items');
            $sale->save(); 

            // 2. Grab the original payment date. 
            // We do this so editing yesterday's sale doesn't move the money into today's drawer!
            $originalPaymentDate = $sale->payments()->min('paid_at') ?? now();

            // 3. Wipe the old, incorrect payment records
            $sale->payments()->delete();

            // 4. Re-create the payment records with the NEW amounts
            if ($sale->is_split_payment) {
                $splits = is_string($sale->split_payments) ? json_decode($sale->split_payments, true) : $sale->split_payments;
                
                if (is_array($splits)) {
                    foreach ($splits as $payment) {
                        Payment::create([
                            'sale_id' => $sale->id,
                            'amount'  => $payment['amount'],
                            'method'  => $payment['method'],
                            'paid_at' => $originalPaymentDate, 
                        ]);
                    }
                }
            } else {
                // If standard payment, use the freshly calculated final_total!
                Payment::create([
                    'sale_id' => $sale->id,
                    'amount'  => $sale->final_total, 
                    'method'  => $sale->payment_method,
                    'paid_at' => $originalPaymentDate, 
                ]);
            }
        });

        Notification::make()
            ->title('Sale & Payments Synchronized')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}