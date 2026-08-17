<?php

namespace App\Filament\Resources\RepairResource\Pages;

use App\Filament\Resources\RepairResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class CreateRepair extends CreateRecord
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

   protected function afterCreate(): void
{
    $record = $this->record;

    // 🚀 FIX — $this->data is the raw Livewire property and is not guaranteed to
    // contain the latest state of dehydrated(false) fields (like split_payments)
    // at this exact point in the lifecycle. getRawState() is Filament's official
    // way to read ALL current field values, including non-dehydrated ones.
    $data = $this->form->getRawState();

    \Illuminate\Support\Facades\Log::info('REPAIR PAYMENT DEBUG — afterCreate data', [
        'repair_id'        => $record->id,
        'is_split_payment' => $data['is_split_payment'] ?? 'MISSING',
        'split_payments'   => $data['split_payments'] ?? 'MISSING',
        'amount_paid'      => $data['amount_paid'] ?? 'MISSING',
        'payment_method'   => $data['payment_method'] ?? 'MISSING',
    ]);

    try {
        DB::transaction(function () use ($record, $data) {
            $payments = !empty($data['is_split_payment'])
                ? ($data['split_payments'] ?? [])
                : [['amount' => $data['amount_paid'] ?? 0, 'method' => $data['payment_method'] ?? 'CASH']];

            // 🚀 FIX — guard now scopes by row position too, so two legitimate split
            // rows with the same amount+method (e.g. two $50 CASH payments) don't get
            // wrongly treated as duplicates of each other.
            $seenThisRequest = [];

            foreach ($payments as $rowKey => $p) {
                $amt = round((float) ($p['amount'] ?? 0), 2);
                if ($amt <= 0) continue;

                $method = strtoupper(trim($p['method'] ?? 'CASH'));
                $dedupeKey = $rowKey . '|' . $method . '|' . $amt;

                if (isset($seenThisRequest[$dedupeKey])) continue;
                $seenThisRequest[$dedupeKey] = true;

                $exists = \App\Models\Payment::where('repair_id', $record->id)
                    ->where('amount', $amt)
                    ->where('method', $method)
                    ->where('paid_at', '>=', now()->subSeconds(10))
                    ->exists();

                if ($exists) continue;

                \App\Models\Payment::create([
                    'repair_id' => $record->id,
                    'amount'    => $amt,
                    'method'    => $method,
                    'paid_at'   => now(),
                    'store_id'  => $record->store_id ?? auth()->user()->store_id ?? 1,
                ]);
            }

            $calc = RepairResource::calculateRepairTotal($record->fresh());
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
                $sale = RepairResource::createSaleFromRepair($record->fresh());
                \Filament\Notifications\Notification::make()
                    ->title("✅ Fully Paid — Sale #{$sale->invoice_number} Created")
                    ->body('This repair now shows up in your Sales Report.')
                    ->success()
                    ->persistent()
                    ->send();
            }
        });
    } catch (\Throwable $e) {
        // 🚀 If ANYTHING in the block above fails (e.g. missing column), surface it
        // loudly instead of letting a Payment row silently end up orphaned.
        \Illuminate\Support\Facades\Log::error('Repair payment save failed', [
            'repair_id' => $record->id,
            'error'     => $e->getMessage(),
        ]);

        \Filament\Notifications\Notification::make()
            ->title('⚠️ Payment Save Incomplete')
            ->body('The repair was created, but recording the payment failed: ' . $e->getMessage() . '. Please use "Add Deposit" on the repair list to record it manually.')
            ->danger()
            ->persistent()
            ->send();
    }

    if ($this->data['auto_print'] ?? false) {
        $printUrl = route('repair.print', $this->record);
        $this->js("window.open('{$printUrl}', '_blank');");
    }
}

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

   protected function mutateFormDataBeforeCreate(array $data): array
{
    // 🔍 TEMP DEBUG — remove after we find the bug
    \Illuminate\Support\Facades\Log::info('WEBCAM DEBUG — raw $this->data[items]', [
        'this_data_items' => $this->data['items'] ?? 'MISSING',
    ]);
    \Illuminate\Support\Facades\Log::info('WEBCAM DEBUG — incoming $data[items]', [
        'data_items' => $data['items'] ?? 'MISSING',
    ]);

    // Fix captured_photos per repeater item — same UUID→numeric mismatch fix
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

    unset($data['captured_photos']);

        // Resolve the correct user ID from the visible Select
        $resolvedId = null;

        if (!empty($data['sales_person_list']) && is_array($data['sales_person_list'])) {
            $candidate = (int) $data['sales_person_list'][0];
            // Only accept if it's a positive integer (valid user ID)
            if ($candidate > 0) {
                $resolvedId = $candidate;
            }
        }

        if (!$resolvedId) {
            $activeName = Session::get('active_staff_name');
            if ($activeName) {
                $user = User::where('name', 'LIKE', "%{$activeName}%")->first();
                if ($user) $resolvedId = $user->id;
            }
        }

        if (!$resolvedId) {
            $resolvedId = auth()->id();
        }

         if ($resolvedId) {
            $data['staff_id']        = $resolvedId;
            $data['sales_person_id'] = $resolvedId;
        } else {
            $data['staff_id']        = null;
            $data['sales_person_id'] = null;
        }

        $data['sales_person_list'] = $resolvedId ? [$resolvedId] : [];

        return $data;
    }
}