<?php

namespace App\Filament\Resources\LaybuyResource\Pages;

use App\Filament\Resources\LaybuyResource;
use App\Models\LaybuyPayment;
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
                        ->numeric()->required()->prefix('$')
                        ->default(fn($record) => $record->balance_due)
                        ->maxValue(fn($record) => $record->balance_due),

                    Select::make('payment_method')
                        ->label('Payment Method')
                        ->options(function () {
                            $settings = DB::table('site_settings')->where('key', 'payment_methods')->value('value');
                            $methods  = $settings ? json_decode($settings, true) : ['CASH', 'VISA'];
                            $options  = [];
                            foreach ($methods as $method) {
                                $clean = strtoupper(trim($method));
                                if ($clean !== 'LAYBUY') $options[$clean] = $clean;
                            }
                            return $options;
                        })
                        ->default('CASH')->required(),

                    DatePicker::make('payment_date')
                        ->label('Date Received')
                        ->default(now())->required(),
                ])
                ->action(function (array $data, $record) {
                    DB::transaction(function () use ($data, $record) {
                        $amount = (float) $data['amount'];
                        $method = strtoupper(trim($data['payment_method']));
                        $tz     = \App\Models\Store::first()?->timezone ?? 'America/Denver';
                        $paidAt = Carbon::parse($data['payment_date'], $tz)->setTimezone('UTC');

                        // 1. Laybuy ledger entry
                        LaybuyPayment::create([
                            'laybuy_id'      => $record->id,
                            'amount'         => $amount,
                            'payment_method' => $method,
                            'created_at'     => $paidAt,
                            'updated_at'     => $paidAt,
                        ]);

                        // 2. EOD payment entry
                        \App\Models\Payment::create([
                            'sale_id'  => $record->sale_id ?? null,
                            'amount'   => $amount,
                            'method'   => $method,
                            'paid_at'  => $paidAt,
                            'store_id' => auth()->user()->store_id ?? 1,
                        ]);

                        // 3. Recalculate Laybuy balances
                        $newPaid    = $record->amount_paid + $amount;
                        $newBalance = max(0, $record->total_amount - $newPaid);
                        $status     = $newBalance <= 0 ? 'completed' : 'in_progress';
                        $record->update([
                            'amount_paid'    => $newPaid,
                            'balance_due'    => $newBalance,
                            'status'         => $status,
                            'last_paid_date' => Carbon::parse($data['payment_date'], $tz)->toDateString(),
                        ]);

                        // 4. Sync linked Sale
                        if ($record->sale_id) {
                            $sale          = $record->sale;
                            $totalSalePaid = \App\Models\Payment::where('sale_id', $sale->id)->sum('amount');
                            $saleBalance   = max(0, $sale->final_total - $totalSalePaid);
                            $isFullyPaid   = $saleBalance <= 0.01;
                            $sale->update([
                                'amount_paid'  => $totalSalePaid,
                                'balance_due'  => $saleBalance,
                                'status'       => $isFullyPaid ? 'completed' : $sale->status,
                                'completed_at' => $isFullyPaid ? now() : $sale->completed_at,
                            ]);
                            // Release items to sold if fully paid
                            if ($isFullyPaid) {
                                foreach ($sale->items as $saleItem) {
                                    if ($saleItem->product_item_id) {
                                        \App\Models\ProductItem::where('id', $saleItem->product_item_id)
                                            ->where('status', 'on_hold')
                                            ->update(['status' => 'sold', 'hold_reason' => null, 'held_by_sale_id' => null]);
                                    }
                                }
                            }
                        }
                    });

                    Notification::make()->title('Payment Recorded Successfully')->success()->send();
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
                            ->filter(fn($p) => Carbon::parse($p->created_at)->setTimezone($tz)->toDateString() === $today)
                            ->map(fn($p) => [
                                'id'             => $p->id,
                                'created_at'     => Carbon::parse($p->created_at)->setTimezone($tz)->format('Y-m-d'),
                                'payment_method' => $p->payment_method,
                                'amount'         => $p->amount,
                            ])->values()->toArray(),
                    ];
                })
                ->form([
                    \Filament\Forms\Components\Repeater::make('ledger')
                        ->label('')
                        ->schema([
                            \Filament\Forms\Components\DatePicker::make('created_at')->label('Date')->required(),
                            \Filament\Forms\Components\Select::make('payment_method')
                                ->label('Method')
                                ->options(function () {
                                    $settings = DB::table('site_settings')->where('key', 'payment_methods')->value('value');
                                    $methods  = $settings ? json_decode($settings, true) : ['CASH', 'VISA'];
                                    $options  = [];
                                    foreach ($methods as $m) {
                                        $clean = strtoupper(trim($m));
                                        if ($clean !== 'LAYBUY') $options[$clean] = $clean;
                                    }
                                    return $options;
                                })->required(),
                            \Filament\Forms\Components\TextInput::make('amount')->label('Amount')->numeric()->prefix('$')->required(),
                            \Filament\Forms\Components\Hidden::make('id'),
                        ])
                        ->columns(3)->addable(false)->deletable(true)->reorderable(false)
                        ->itemLabel(fn(array $state): ?string => ($state['payment_method'] ?? '') . ' — $' . number_format($state['amount'] ?? 0, 2)),
                ])
                ->action(function (array $data, $record) {
                    DB::transaction(function () use ($data, $record) {
                        $tz         = \App\Models\Store::first()?->timezone ?? 'America/Denver';
                        $ledgerData = collect($data['ledger'] ?? []);
                        $keptIds    = $ledgerData->pluck('id')->filter()->toArray();
                        $today      = now()->setTimezone($tz)->toDateString();

                        // Delete removed payments
                        $paymentsToDelete = $record->laybuyPayments()
                            ->whereNotIn('id', $keptIds)->get()
                            ->filter(fn($p) => Carbon::parse($p->created_at)->setTimezone($tz)->toDateString() === $today);

                        foreach ($paymentsToDelete as $payment) {
                            \App\Models\Payment::where(function ($q) use ($record) {
                                $record->sale_id ? $q->where('sale_id', $record->sale_id) : $q->whereNull('sale_id');
                            })->where('amount', $payment->amount)->where('method', $payment->payment_method)
                                ->whereBetween('paid_at', [Carbon::parse($payment->created_at)->subMinute(), Carbon::parse($payment->created_at)->addMinute()])
                                ->delete();
                            $payment->delete();
                        }

                        // Update changed payments
                        foreach ($ledgerData as $row) {
                            if (empty($row['id'])) continue;
                            $payment = LaybuyPayment::find($row['id']);
                            if (!$payment) continue;
                            $newAmount = (float) $row['amount'];
                            $newMethod = strtoupper(trim($row['payment_method']));
                            $newPaidAt = Carbon::parse($row['created_at'], $tz)->setTimezone('UTC');
                            if ($payment->amount != $newAmount || $payment->payment_method !== $newMethod) {
                                \App\Models\Payment::where(function ($q) use ($record) {
                                    $record->sale_id ? $q->where('sale_id', $record->sale_id) : $q->whereNull('sale_id');
                                })->where('amount', $payment->amount)->where('method', $payment->payment_method)
                                    ->whereBetween('paid_at', [Carbon::parse($payment->created_at)->subMinute(), Carbon::parse($payment->created_at)->addMinute()])
                                    ->update(['amount' => $newAmount, 'method' => $newMethod, 'paid_at' => $newPaidAt]);
                                $payment->update(['amount' => $newAmount, 'payment_method' => $newMethod, 'created_at' => $newPaidAt, 'updated_at' => now()]);
                            }
                        }

                        // Recalculate totals
                        $newTotalPaid = $record->laybuyPayments()->sum('amount');
                        $trueBalance  = max(0, floatval($record->total_amount) - floatval($newTotalPaid));
                        $newStatus    = $trueBalance <= 0 ? 'completed' : 'in_progress';
                        $record->update(['amount_paid' => $newTotalPaid, 'balance_due' => $trueBalance, 'status' => $newStatus]);

                        if ($record->sale_id) {
                            $sale          = \App\Models\Sale::find($record->sale_id);
                            $saleTotalPaid = \App\Models\Payment::where('sale_id', $sale->id)->sum('amount');
                            $saleBalance   = max(0, $sale->final_total - $saleTotalPaid);
                            $sale->update([
                                'amount_paid'  => $saleTotalPaid,
                                'balance_due'  => $saleBalance,
                                'status'       => $newStatus === 'completed' ? 'completed' : 'pending',
                                'completed_at' => $newStatus === 'completed' ? now() : ($saleTotalPaid < $sale->final_total ? null : $sale->completed_at),
                            ]);
                        }
                    });

                    Notification::make()->title('Ledger Updated')->body('Payments and balances have been recalculated.')->success()->send();
                    redirect(request()->header('Referer'));
                }),

            Actions\DeleteAction::make(),
        ];
    }

    // ── Populate form on edit — maps all new field names ──
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $laybuy = $this->getRecord();

        $data['total_amount'] = $laybuy->total_amount;
        $data['amount_paid']  = $laybuy->amount_paid;
        $data['balance_due']  = $laybuy->balance_due;

        if ($laybuy->sale && $laybuy->sale->items->isNotEmpty()) {
            $data['layby_items'] = $laybuy->sale->items->map(function ($item) {
                $unitPrice = floatval($item->sold_price ?? 0);
                $salePrice = floatval($item->sale_price_override ?? $unitPrice);
                $discPct   = floatval($item->discount_percent ?? 0);
                $discAmt   = floatval($item->discount_amount ?? 0);

                if ($salePrice <= 0) $salePrice = $unitPrice;
                if ($discAmt <= 0 && $discPct > 0) {
                    $discAmt = round($unitPrice * $discPct / 100, 2);
                }

                return [
                    'product_item_id'  => $item->product_item_id,
                    'barcode'          => $item->productItem?->barcode ?? 'N/A',
                    'description'      => $item->custom_description ?? $item->productItem?->custom_description ?? 'Item',
                    'unit_price'       => $unitPrice,
                    'sale_price'       => $salePrice,
                    'discount_percent' => $discPct,
                    'discount_amount'  => $discAmt,
                    'is_tax_free'      => (bool) ($item->is_tax_free ?? false),
                    'qty'              => intval($item->qty ?? 1),
                ];
            })->toArray();
        } else {
            $data['layby_items'] = [];
        }
        if (!empty($laybuy->sales_person)) {
            $data['sales_person_list'] = array_values(
                array_filter(array_map('trim', explode(',', $laybuy->sales_person)))
            );
        } elseif (empty($data['sales_person_list'])) {
            $data['sales_person_list'] = [];
        }
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
{
    // Sync sales_person_list → sales_person VARCHAR before saving
    if (!empty($data['sales_person_list']) && is_array($data['sales_person_list'])) {
        $data['sales_person'] = implode(', ', $data['sales_person_list']);
    }

    unset(
        $data['layby_items'],
        $data['item_search'],
        $data['totals_summary'],
        $data['totals_display'],
        $data['imported_items_list'],
        $data['payment_history'],
        // sales_person_list is NOT unset here — Laybuy model needs it
        $data['progress_bar']
    );
    return $data;
}
}
