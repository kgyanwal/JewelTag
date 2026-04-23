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
use Carbon\Carbon;

class EditLaybuy extends EditRecord
{
    protected static string $resource = LaybuyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ─── 1. RECORD PAYMENT ───
            Actions\Action::make('add_payment')
                ->label('Record Payment')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn($record) => $record->balance_due > 0)
                ->form([
                    TextInput::make('amount')
                        ->label('Payment Amount')
                        ->numeric()
                        ->required()
                        ->prefix('$')
                        ->default(fn($record) => $record->balance_due)
                        ->maxValue(fn($record) => $record->balance_due),

                    Select::make('payment_method')
                        ->label('Payment Method')
                        ->options(function () {
                            $settings = DB::table('site_settings')->where('key', 'payment_methods')->value('value');
                            $methods  = $settings ? json_decode($settings, true) : ['CASH', 'VISA'];
                            $options  = [];
                            foreach ($methods as $method) {
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
                        $tz     = \App\Models\Store::first()?->timezone ?? 'America/Denver';
                        $paidAt = Carbon::parse($data['payment_date'], $tz)->setTimezone('UTC');

                        // 1. Create the Laybuy Payment Record (ledger)
                        LaybuyPayment::create([
                            'laybuy_id'      => $record->id,
                            'amount'         => $amount,
                            'payment_method' => $method,
                            'created_at'     => $paidAt,
                            'updated_at'     => $paidAt,
                        ]);

                        // 2. Create the EOD Payment Record (always, even without sale_id)
                        \App\Models\Payment::create([
                            'sale_id'  => $record->sale_id ?? null,
                            'amount'   => $amount,
                            'method'   => $method,
                            'paid_at'  => $paidAt,
                            'store_id' => auth()->user()->store_id ?? 1,
                        ]);

                        // 3. Recalculate Laybuy Balances
                        $newPaid    = $record->amount_paid + $amount;
                        $newBalance = max(0, $record->total_amount - $newPaid);
                        $status     = $newBalance <= 0 ? 'completed' : 'in_progress';

                        $record->update([
                            'amount_paid'    => $newPaid,
                            'balance_due'    => $newBalance,
                            'status'         => $status,
                            'last_paid_date' => Carbon::parse($data['payment_date'], $tz)->toDateString(),
                        ]);

                        // 4. Update the attached Sale Status
                       if ($record->sale_id) {
                            $sale = $record->sale;
                            $sale->update([
                                'amount_paid'  => $sale->amount_paid + $amount,
                                'balance_due'  => max(0, $sale->final_total - ($sale->amount_paid + $amount)),
                                'status'       => $newBalance <= 0 ? 'completed' : $sale->status,
                                // 🚀 THE FIX: Trigger the EOD report when paid in full
                                'completed_at' => $newBalance <= 0 ? now() : $sale->completed_at,
                            ]);
                        }
                    });

                    Notification::make()
                        ->title('Payment Recorded Successfully')
                        ->success()
                        ->send();

                    redirect(request()->header('Referer'));
                }),

            // ─── 2. MANAGE LEDGER (TODAY'S PAYMENTS ONLY) ───
            Actions\Action::make('manage_payments')
                ->label('Manage Ledger')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('warning')
                ->visible(fn() => \App\Helpers\Staff::user()?->hasAnyRole(['Superadmin', 'Administration', 'Manager']) ?? true)
                ->modalHeading('Manage Layby Payments')
                ->modalDescription('You can only edit or delete payments made today. Contact admin for older adjustments.')
                ->modalSubmitActionLabel('Save Ledger')
                ->fillForm(function ($record) {
                    $tz    = \App\Models\Store::first()?->timezone ?? 'America/Denver';
                    $today = now()->setTimezone($tz)->toDateString();

                    return [
                        'ledger' => $record->laybuyPayments
                            ->filter(fn($p) => Carbon::parse($p->created_at)
                                ->setTimezone($tz)->toDateString() === $today)
                            ->map(fn($p) => [
                                'id'             => $p->id,
                                'created_at'     => Carbon::parse($p->created_at)
                                    ->setTimezone($tz)->format('Y-m-d'),
                                'payment_method' => $p->payment_method,
                                'amount'         => $p->amount,
                            ])->values()->toArray(),
                    ];
                })
                ->form([
                    \Filament\Forms\Components\Repeater::make('ledger')
                        ->label('')
                        ->schema([
                            \Filament\Forms\Components\DatePicker::make('created_at')
                                ->label('Date')
                                ->required(),
                            \Filament\Forms\Components\Select::make('payment_method')
                                ->label('Method')
                                ->options(function () {
                                    $settings = DB::table('site_settings')->where('key', 'payment_methods')->value('value');
                                    $methods  = $settings ? json_decode($settings, true) : ['CASH', 'VISA'];
                                    $options  = [];
                                    foreach ($methods as $method) {
                                        $cleanMethod = trim($method);
                                        $options[strtoupper($cleanMethod)] = strtoupper($cleanMethod);
                                    }
                                    return $options;
                                })
                                ->required(),
                            \Filament\Forms\Components\TextInput::make('amount')
                                ->label('Amount')
                                ->numeric()
                                ->prefix('$')
                                ->required(),
                            \Filament\Forms\Components\Hidden::make('id'),
                        ])
                        ->columns(3)
                        ->addable(false)
                        ->deletable(true)
                        ->reorderable(false)
                        ->itemLabel(fn(array $state): ?string =>
                            ($state['payment_method'] ?? '') . ' — $' . number_format($state['amount'] ?? 0, 2)
                        ),
                ])
                ->action(function (array $data, $record) {
                    DB::transaction(function () use ($data, $record) {
                        $tz         = \App\Models\Store::first()?->timezone ?? 'America/Denver';
                        $ledgerData = collect($data['ledger'] ?? []);
                        $keptIds    = $ledgerData->pluck('id')->filter()->toArray();

                        // 1. Find payments DELETED from the repeater (today's only)
                        $today = now()->setTimezone($tz)->toDateString();
                        $paymentsToDelete = $record->laybuyPayments()
                            ->whereNotIn('id', $keptIds)
                            ->get()
                            ->filter(fn($p) => Carbon::parse($p->created_at)
                                ->setTimezone($tz)->toDateString() === $today);

                        foreach ($paymentsToDelete as $payment) {
                            // Delete matching EOD payment record
                            \App\Models\Payment::where(function ($q) use ($record) {
                                    if ($record->sale_id) {
                                        $q->where('sale_id', $record->sale_id);
                                    } else {
                                        $q->whereNull('sale_id');
                                    }
                                })
                                ->where('amount', $payment->amount)
                                ->where('method', $payment->payment_method)
                                ->whereBetween('paid_at', [
                                    Carbon::parse($payment->created_at)->subMinute(),
                                    Carbon::parse($payment->created_at)->addMinute(),
                                ])
                                ->delete();

                            $payment->delete();
                        }

                        // 2. Update KEPT payments if amounts/methods changed
                        foreach ($ledgerData as $row) {
                            if (empty($row['id'])) continue;

                            $payment = LaybuyPayment::find($row['id']);
                            if (!$payment) continue;

                            $newAmount  = (float) $row['amount'];
                            $newMethod  = strtoupper($row['payment_method']);
                            $newPaidAt  = Carbon::parse($row['created_at'], $tz)->setTimezone('UTC');

                            $amountChanged = $payment->amount != $newAmount;
                            $methodChanged = $payment->payment_method !== $newMethod;
                            $dateChanged   = Carbon::parse($payment->created_at)->toDateString() !== $row['created_at'];

                            if ($amountChanged || $methodChanged || $dateChanged) {
                                // Sync the EOD payments table
                                \App\Models\Payment::where(function ($q) use ($record) {
                                        if ($record->sale_id) {
                                            $q->where('sale_id', $record->sale_id);
                                        } else {
                                            $q->whereNull('sale_id');
                                        }
                                    })
                                    ->where('amount', $payment->amount)
                                    ->where('method', $payment->payment_method)
                                    ->whereBetween('paid_at', [
                                        Carbon::parse($payment->created_at)->subMinute(),
                                        Carbon::parse($payment->created_at)->addMinute(),
                                    ])
                                    ->update([
                                        'amount'  => $newAmount,
                                        'method'  => $newMethod,
                                        'paid_at' => $newPaidAt,
                                    ]);

                                // Sync the laybuy_payments table
                                $payment->update([
                                    'amount'         => $newAmount,
                                    'payment_method' => $newMethod,
                                    'created_at'     => $newPaidAt,
                                    'updated_at'     => now(),
                                ]);
                            }
                        }

                        // 3. Recalculate Laybuy totals from full payment history
                        $newTotalPaid = $record->laybuyPayments()->sum('amount');
                        $trueBalance  = max(0, floatval($record->total_amount) - floatval($newTotalPaid));
                        $newStatus    = $trueBalance <= 0 ? 'completed' : 'in_progress';

                        $record->update([
                            'amount_paid' => $newTotalPaid,
                            'balance_due' => $trueBalance,
                            'status'      => $newStatus,
                        ]);

                        // 4. Update the parent Sale
                       if ($record->sale_id) {
                            $sale = \App\Models\Sale::find($record->sale_id);
                            if ($sale) {
                                $saleTotalPaid = \App\Models\Payment::where('sale_id', $sale->id)->sum('amount');
                                $saleBalance   = max(0, $sale->final_total - $saleTotalPaid);
                                
                                $sale->update([
                                    'amount_paid'  => $saleTotalPaid,
                                    'balance_due'  => $saleBalance,
                                    'status'       => $newStatus === 'completed' ? 'completed' : 'pending',
                                    // 🚀 THE FIX: Trigger the EOD report based on recalculated totals
                                    'completed_at' => $newStatus === 'completed' ? now() : ($saleTotalPaid < $sale->final_total ? null : $sale->completed_at),
                                ]);
                            }
                        }
                    });

                    Notification::make()
                        ->title('Ledger Updated')
                        ->body('Payments and balances have been recalculated.')
                        ->success()
                        ->send();

                    redirect(request()->header('Referer'));
                }),

            Actions\DeleteAction::make(),
        ];
    }

    // ─── POPULATE FORM ON EDIT ───
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $laybuy = $this->getRecord();

        $data['total_amount'] = $laybuy->total_amount;
        $data['amount_paid']  = $laybuy->amount_paid;
        $data['balance_due']  = $laybuy->balance_due;

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