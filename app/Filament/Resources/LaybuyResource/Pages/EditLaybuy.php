<?php

namespace App\Filament\Resources\LaybuyResource\Pages;

use App\Filament\Resources\LaybuyResource;
use App\Models\LaybuyPayment;
use App\Models\SalePayment;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditLaybuy extends EditRecord
{
    protected static string $resource = LaybuyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // 🚀 THE NEW PAYMENT BUTTON
            Actions\Action::make('add_payment')
                ->label('Record Payment')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                // Hide the button if the balance is $0
                ->visible(fn ($record) => $record->balance_due > 0)
                ->form([
                    TextInput::make('amount')
                        ->label('Payment Amount')
                        ->numeric()
                        ->required()
                        ->prefix('$')
                        ->default(fn ($record) => $record->balance_due) // Defaults to paying it off completely
                        ->maxValue(fn ($record) => $record->balance_due),
                    
                    Select::make('payment_method')
                        ->label('Payment Method')
                        ->options(function () {
                            // Fetch dynamic methods from your settings table
                            $settings = DB::table('site_settings')->where('key', 'payment_methods')->value('value');
                            $methods = $settings ? json_decode($settings, true) : ['CASH', 'VISA'];
                            
                            $options = [];
                            foreach ($methods as $method) {
                                // Creates the ['cash' => 'CASH'] format Filament expects
                                $cleanMethod = trim($method);
                                $options[strtolower($cleanMethod)] = strtoupper($cleanMethod);
                            }
                            return $options;
                        })
                        ->default('cash')
                        ->required(),
                    
                    DatePicker::make('payment_date')
                        ->label('Date Received')
                        ->default(now())
                        ->required(),
                ])
               ->action(function (array $data, $record) {
                    DB::transaction(function () use ($data, $record) {
                        $amount = (float) $data['amount'];
                        $method = strtoupper(trim($data['payment_method']));

                        // 1. Create the Layby Payment Record (Updates the Layby Ledger)
                        LaybuyPayment::create([
                            'laybuy_id'      => $record->id,
                            'amount'         => $amount,
                            'payment_method' => $method,
                            'created_at'     => $data['payment_date'],
                            'updated_at'     => $data['payment_date'],
                        ]);

                        // 2. Create the Main Payment Record (Syncs with End of Day)
                        if ($record->sale_id) {
                            \App\Models\Payment::create([
                                'sale_id' => $record->sale_id,
                                'amount'  => $amount,
                                'method'  => $method,
                                'paid_at' => $data['payment_date'],
                            ]);
                        }

                        // 3. Recalculate Balances
                        $newPaid    = $record->amount_paid + $amount;
                        $newBalance = max(0, $record->total_amount - $newPaid);
                        $status     = $newBalance <= 0 ? 'completed' : 'in_progress';

                        // 4. Update the Lay-by Status
                        $record->update([
                            'amount_paid'    => $newPaid,
                            'balance_due'    => $newBalance,
                            'status'         => $status,
                            'last_paid_date' => $data['payment_date'],
                        ]);

                        // 5. Update the attached Sale Status
                        if ($record->sale_id) {
                            $sale = $record->sale;
                            $sale->update([
                                'amount_paid' => $sale->amount_paid + $amount,
                                'balance_due' => max(0, $sale->final_total - ($sale->amount_paid + $amount)),
                                'status'      => $newBalance <= 0 ? 'completed' : $sale->status,
                            ]);
                        }
                    });

                    Notification::make()
                        ->title('Payment Recorded Successfully')
                        ->success()
                        ->send();

                    // Refresh the page so the user sees the new balance instantly
                    redirect(request()->header('Referer'));
                }),

            Actions\DeleteAction::make(),
        ];
    }

    // This populates the UI with the stock items when you click "Edit"
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $laybuy = $this->getRecord();

        // ✅ ADD THESE THREE LINES — populate financial fields from DB
        $data['total_amount'] = $laybuy->total_amount;
        $data['amount_paid']  = $laybuy->amount_paid;
        $data['balance_due']  = $laybuy->balance_due;

        // Your existing items logic — unchanged
        if ($laybuy->sale && $laybuy->sale->items->isNotEmpty()) {
            $data['layby_items'] = $laybuy->sale->items->map(function ($item) {
                return [
                    'product_item_id' => $item->product_item_id,
                    'barcode'         => $item->productItem?->barcode ?? 'N/A',
                    'description'     => $item->custom_description ?? $item->productItem?->custom_description ?? 'Custom Item',
                    'price'           => $item->sold_price,
                ];
            })->toArray();
        } else {
            $data['layby_items'] = [];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['creation_mode'])) {
            $data['is_trade_in'] = ($data['creation_mode'] === 'trade_in');
        }
        return $data;
    }
}