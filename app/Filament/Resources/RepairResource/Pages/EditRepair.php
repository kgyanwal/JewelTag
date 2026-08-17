<?php

namespace App\Filament\Resources\RepairResource\Pages;

use App\Filament\Resources\RepairResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Session;

class EditRepair extends EditRecord
{
    protected static string $resource = RepairResource::class;

    protected function getListeners(): array
    {
        return [
            'webcam-photo-added'   => 'onWebcamPhotoAdded',
            'webcam-photo-removed' => 'onWebcamPhotoRemoved',
        ];
    }

 public function onWebcamPhotoAdded(string $statePath, string $path): void
{
    $relativePath = str_starts_with($statePath, 'data.') ? substr($statePath, 5) : $statePath;

    $current   = data_get($this->data, $relativePath) ?? [];
    $current[] = $path;

    $dataCopy = $this->data;
    data_set($dataCopy, $relativePath, $current);
    $this->data = $dataCopy;

    $this->dispatch('webcam-photo-synced', statePath: $statePath, photos: $current);
}

public function onWebcamPhotoRemoved(string $statePath, string $path): void
{
    $relativePath = str_starts_with($statePath, 'data.') ? substr($statePath, 5) : $statePath;

    $current = data_get($this->data, $relativePath) ?? [];
    $current = array_values(array_filter($current, fn($p) => $p !== $path));

    $dataCopy = $this->data;
    data_set($dataCopy, $relativePath, $current);
    $this->data = $dataCopy;

    $this->dispatch('webcam-photo-synced', statePath: $statePath, photos: $current);
}
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

   protected function afterFill(): void
    {
        $this->dispatch('$refresh');
    }

    // 🚀 NEW — Hydrate the Payment & Status fields from existing repair_id-scoped
    // Payment rows, mirroring EditSale::mutateFormDataBeforeFill's payment-loading logic.
   protected function hydratePaymentFields(array $data): array
    {
        $record   = $this->record;
        $payments = \App\Models\Payment::where('repair_id', $record->id)->orderBy('paid_at')->get();

        if ($payments->count() > 1) {
            $data['is_split_payment'] = true;
            $data['split_payments']   = $payments->map(fn($p) => [
                'method' => strtoupper(trim($p->method)),
                'amount' => floatval($p->amount),
            ])->toArray();
            $data['amount_paid'] = 0;
        } elseif ($payments->count() === 1) {
            $data['is_split_payment'] = false;
            $data['split_payments']   = [];
            $data['payment_method']   = strtoupper($payments->first()->method);
            $data['amount_paid']      = floatval($payments->first()->amount);
        } else {
            $data['is_split_payment'] = false;
            $data['split_payments']   = [];
            $data['amount_paid']      = 0;
        }

        return $data;
    }

    // 🚀 NEW — Reconciles the form's payment fields against what's already in the DB,
    // inserting only the delta (truly new money), same pattern as EditSale::afterSave().
    // Auto-fires createSaleFromRepair() the moment balance hits $0.
    protected function afterSave(): void
    {
        $record = $this->record->fresh();

        $data = $this->form->getRawState();

        \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data) {
            $existingPayments = \App\Models\Payment::where('repair_id', $record->id)->get();
            $totalAlreadyInDb = round($existingPayments->sum('amount'), 2);

            $formPayments = !empty($data['is_split_payment'])
                ? ($data['split_payments'] ?? [])
                : [['amount' => $data['amount_paid'] ?? 0, 'method' => $data['payment_method'] ?? 'CASH']];

            $formTotal = round(collect($formPayments)->sum(fn($p) => floatval($p['amount'] ?? 0)), 2);
            $delta     = round($formTotal - $totalAlreadyInDb, 2);

            if ($delta > 0) {
                $existingByMethod = [];
                foreach ($existingPayments as $ep) {
                    $key = strtoupper(trim($ep->method));
                    $existingByMethod[$key] = ($existingByMethod[$key] ?? 0) + floatval($ep->amount);
                }

                foreach ($formPayments as $p) {
                    $amt    = floatval($p['amount'] ?? 0);
                    $method = strtoupper(trim($p['method'] ?? 'CASH'));
                    if ($amt <= 0) continue;

                    $alreadyRecorded = $existingByMethod[$method] ?? 0;
                    if ($alreadyRecorded >= $amt) {
                        $existingByMethod[$method] -= $amt;
                        continue;
                    }

                    $newAmt = $amt - $alreadyRecorded;
                    $existingByMethod[$method] = 0;

                    \App\Models\Payment::create([
                        'repair_id' => $record->id,
                        'amount'    => round($newAmt, 2),
                        'method'    => $method,
                        'paid_at'   => now(),
                        'store_id'  => $record->store_id ?? auth()->user()->store_id ?? 1,
                    ]);
                }
            } elseif ($delta < 0) {
                \App\Models\Payment::where('repair_id', $record->id)
                    ->latest('paid_at')
                    ->first()
                    ?->delete();
            }

            $calc = \App\Filament\Resources\RepairResource::calculateRepairTotal($record->fresh());
            $record->update([
                'amount_paid'      => $calc['paid'],
                'balance_due'      => $calc['balance'],
                'repair_subtotal'  => $calc['subtotal'],
                'repair_tax'       => $calc['tax'],
                'repair_total'     => $calc['total'],
                'is_split_payment' => (bool) ($data['is_split_payment'] ?? false),
                'payment_method'   => !empty($data['is_split_payment'])
                    ? 'split'
                    : strtoupper(trim($data['payment_method'] ?? 'CASH')),
            ]);

            if ($calc['balance'] <= 0.01 && !$record->sale_id) {
                $sale = \App\Filament\Resources\RepairResource::createSaleFromRepair($record->fresh());
                \Filament\Notifications\Notification::make()
                    ->title("✅ Fully Paid — Sale #{$sale->invoice_number} Created")
                    ->body('This repair now shows up in your Sales Report.')
                    ->success()
                    ->persistent()
                    ->send();
            } else {
                \Filament\Notifications\Notification::make()
                    ->title('✅ Repair Payments Updated')
                    ->success()
                    ->send();
            }
        });
    }

   protected function mutateFormDataBeforeFill(array $data): array
    {
        // 🚀 FIX — capture the return value and merge it into $data, since that's
        // what Filament actually uses to fill the form.
        $data = $this->hydratePaymentFields($data);

        $list = $data['sales_person_list'] ?? null;

        if (!empty($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $index => &$item) {
                if (isset($item['captured_photos']) && is_string($item['captured_photos'])) {
                    $item['captured_photos'] = json_decode($item['captured_photos'], true) ?? [];
                }
                $item['captured_photos'] = array_values(
                    array_filter($item['captured_photos'] ?? [], fn($p) => !empty($p))
                );
            }
            unset($item);
            $data['items'] = $data['items'];
        }

        if (is_string($list)) {
            $list = json_decode($list, true);
        }

     if (!empty($list) && is_array($list)) {
            $candidate = (int) $list[0];
            if ($candidate > 0) {
                $data['sales_person_list'] = $list;
                $data['staff_id']          = $candidate;
                $data['sales_person_id']   = $candidate;
            } else {
                $data['staff_id']          = null;
                $data['sales_person_id']   = null;
                $data['sales_person_list'] = [];
            }
            return $data;
        }

        if (!empty($data['staff_id']) && (int) $data['staff_id'] > 0) {
            $data['sales_person_list'] = [$data['staff_id']];
            $data['sales_person_id']   = (int) $data['staff_id'];
        } else {
            $data['staff_id']          = null;
            $data['sales_person_id']   = null;
            $data['sales_person_list'] = [];
        }

        return $data;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // $this->data['items'] uses Filament UUID keys e.g. items.{uuid}.captured_photos
        // $data['items'] uses numeric keys 0,1,2...
        // We re-index $this->data items to match numeric order
        $livewireItems = array_values($this->data['items'] ?? []);

        if (!empty($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $index => &$item) {
                $livewirePhotos = $livewireItems[$index]['captured_photos'] ?? [];
                if (!is_array($livewirePhotos)) {
                    $livewirePhotos = [];
                }
                $item['captured_photos'] = array_values(array_filter($livewirePhotos));
            }
            unset($item);
        }

        // Remove wrongly-placed root-level captured_photos if present
        unset($data['captured_photos']);

        $list = $data['sales_person_list'] ?? null;

        if (is_string($list)) {
            $list = json_decode($list, true);
        }

        if (!empty($list) && is_array($list)) {
            $candidate = (int) $list[0];
            $data['sales_person_list'] = $list;
            $data['sales_person_id']   = $candidate > 0 ? $candidate : null;
            return $data;
        }

        if (!empty($data['staff_id']) && (int) $data['staff_id'] > 0) {
            $data['sales_person_list'] = [$data['staff_id']];
            $data['sales_person_id']   = (int) $data['staff_id'];
        }

        return $data;
    }
}