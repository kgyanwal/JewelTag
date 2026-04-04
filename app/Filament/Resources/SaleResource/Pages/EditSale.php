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

        if ($user?->hasAnyRole(['Superadmin', 'Administration'])) return true;

        $sale = $this->record;

        if ($this->isLaybuyOrCustom()) return true;
        if ($sale->status === 'pending') return true;
        if ($sale->status === 'completed' && floatval($sale->balance_due) > 0) return true;
        if ($sale->status === 'completed' && floatval($sale->balance_due) <= 0 && !$this->isDayClosed()) return true;

        // Check approved edit request
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
                ->visible(fn() =>
                    Staff::user()?->hasRole('Superadmin') ||
                    $this->record->status !== 'completed'
                ),

            Actions\Action::make('complete_sale')
                ->label('Finalize Sale (Today)')
                ->color('success')
                ->icon('heroicon-o-check')
                ->visible(fn() =>
                    $this->record->status !== 'completed' &&
                    Staff::user()?->hasAnyRole(['Superadmin', 'Administration'])
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
        $record    = $this->record;
        $totalPaid = $record->payments->sum('amount');

        if ($record->is_split_payment) {
            $data['amount_paid'] = $totalPaid > 0 ? $totalPaid : $record->final_total;
        } else {
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
        if (!$this->canEditFreely()) {
            Notification::make()->title('Day Locked')->body('Cannot save — EOD closed.')->danger()->send();
            $this->halt();
        }

        $record      = $this->record;
        $alreadyPaid = $record->payments()->sum('amount');

        if ($record->is_split_payment) {
            $splits      = $data['split_payments'] ?? [];
            $newIntended = collect($splits)->sum(fn($p) => (float)($p['amount'] ?? 0));
        } else {
            $newIntended = floatval($data['amount_paid'] ?? 0);
        }

        $totalPaid  = max($alreadyPaid, $newIntended);
        $finalTotal = floatval($record->final_total);
        $balance    = round($finalTotal - $totalPaid, 2);

        if ($balance <= 0) {
            $data['status']      = 'completed';
            $data['balance_due'] = 0;
            if (!$record->completed_at) $data['completed_at'] = now();
        } else {
            $data['status']       = 'pending';
            $data['balance_due']  = $balance;
            $data['completed_at'] = null;
        }

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

    protected function afterSave(): void
    {
        $sale = $this->record->fresh();

        DB::transaction(function () use ($sale) {
            $sale->load('items');

            if ($sale->is_split_payment) {
                $splits = is_string($sale->split_payments)
                    ? json_decode($sale->split_payments, true)
                    : $sale->split_payments;

                if (!is_array($splits) || empty($splits)) return;

                $intendedByMethod = collect($splits)
                    ->groupBy(fn($s) => strtoupper(trim($s['method'])))
                    ->map(fn($g) => round($g->sum(fn($s) => (float) $s['amount']), 2));

                $paidByMethod = $sale->payments()->get()
                    ->groupBy(fn($p) => strtoupper(trim($p->method)))
                    ->map(fn($g) => round($g->sum('amount'), 2));

                $anyChange = false;

                foreach ($intendedByMethod as $method => $intendedAmount) {
                    $alreadyPaid = $paidByMethod[$method] ?? 0;
                    $methodDelta = round($intendedAmount - $alreadyPaid, 2);

                    if ($methodDelta > 0) {
                        Payment::create(['sale_id' => $sale->id, 'amount' => $methodDelta, 'method' => $method, 'paid_at' => now()]);
                        $anyChange = true;
                    } elseif ($methodDelta < 0) {
                        $latest = $sale->payments()->whereRaw('UPPER(TRIM(method)) = ?', [$method])->latest()->first();
                        if ($latest) {
                            $corrected = round($latest->amount + $methodDelta, 2);
                            $corrected <= 0 ? $latest->delete() : $latest->update(['amount' => $corrected]);
                            $anyChange = true;
                        }
                    }
                }

                $totalIntended = round($intendedByMethod->sum(), 2);
                $sale->update(['amount_paid' => $totalIntended]);
                Notification::make()->title($anyChange ? 'Payments Updated' : 'Sale Updated')
                    ->body($anyChange ? 'Split synced: $' . number_format($totalIntended, 2) : 'No payment changes.')
                    ->success()->send();

            } else {
                $alreadyPaidTotal = $sale->payments()->sum('amount');
                $amountPaid       = floatval($sale->amount_paid ?? 0);
                $newIntendedTotal = min($amountPaid > 0 ? $amountPaid : (float)$sale->final_total, (float)$sale->final_total);
                $delta            = round($newIntendedTotal - $alreadyPaidTotal, 2);
                $currentMethod    = strtoupper(trim($sale->payment_method ?? 'CASH'));

                if ($delta > 0) {
                    Payment::create(['sale_id' => $sale->id, 'amount' => $delta, 'method' => $currentMethod, 'paid_at' => now()]);
                    $sale->update(['amount_paid' => round($alreadyPaidTotal + $delta, 2)]);
                    Notification::make()->title('New Payment Recorded')->body('$' . number_format($delta, 2) . ' added.')->success()->send();
                } elseif ($delta < 0) {
                    $latest = $sale->payments()->latest()->first();
                    if ($latest) {
                        $corrected = round($latest->amount + $delta, 2);
                        $corrected <= 0 ? $latest->delete() : $latest->update(['amount' => $corrected]);
                        $sale->update(['amount_paid' => round($newIntendedTotal, 2)]);
                        Notification::make()->title('Payment Adjusted')->body('Corrected by $' . number_format(abs($delta), 2))->warning()->send();
                    }
                } else {
                    $latest = $sale->payments()->latest()->first();
                    if ($latest && strtoupper(trim($latest->method)) !== $currentMethod) {
                        $latest->update(['method' => $currentMethod]);
                        Notification::make()->title('Payment Method Updated')->info()->send();
                    } else {
                        Notification::make()->title('Sale Updated')->body('No payment changes.')->info()->send();
                    }
                }
            }
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}