<?php

namespace App\Filament\Resources\ExchangeResource\Pages;

use App\Filament\Resources\ExchangeResource;
use App\Models\Exchange;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use App\Models\User;

class CreateExchange extends CreateRecord
{
    protected static string $resource = ExchangeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['exchange_no']  = Exchange::generateExchangeNo();
        $data['requested_by'] = auth()->id();
        $data['store_id']     = auth()->user()->store_id ?? \App\Models\Store::first()?->id ?? 1;
        $data['status']       = 'pending_approval';

        // Returned items
        $formItems = $data['returned_items_form'] ?? [];
        $returning = array_filter($formItems, fn($i) => !empty($i['returning']));
        $data['returned_items'] = array_values($returning);
        $data['total_credit']   = collect($returning)->sum('credit_amount');

        // New items
        $data['new_items'] = $data['new_items_form'] ?? [];

        $dbTax   = \Illuminate\Support\Facades\DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? 7.63;
        $taxRate = floatval($dbTax) / 100;

        $subtotal = 0;
        $tax = 0;
        foreach ($data['new_items'] as $item) {
            $qty  = intval($item['qty'] ?? 1);
            $unit = floatval($item['sale_price'] ?? $item['price'] ?? 0);
            $line = ($item['selection_type'] ?? null) === 'custom_order' ? $unit : ($unit * $qty);
            $subtotal += $line;
            if (empty($item['is_tax_free'])) {
                $tax += $line * $taxRate;
            }
        }

        $data['new_items_subtotal'] = round($subtotal, 2);
        $data['new_items_tax']      = round($tax, 2);
        $data['new_sale_amount']    = round($subtotal + $tax, 2);
        $data['difference_amount']  = round($data['new_sale_amount'] - $data['total_credit'], 2);

        $diff = floatval($data['difference_amount']);
        if ($diff > 0.009)       $data['exchange_type'] = 'upgrade';
        elseif ($diff < -0.009)  $data['exchange_type'] = 'downgrade';
        else                     $data['exchange_type'] = 'same_value';

        if (!empty($data['is_split_payment'])) {
            $data['split_payments'] = array_values(array_filter($data['split_payments'] ?? [], fn($p) => floatval($p['amount'] ?? 0) > 0));
        }

        unset($data['returned_items_form'], $data['new_items_form']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $managers = User::role(['Superadmin', 'Administration', 'Manager'])->get();
        foreach ($managers as $manager) {
            Notification::make()
                ->title('Exchange Request — Approval Needed 🔄')
                ->body(
                    auth()->user()->name . ' submitted exchange ' . $this->record->exchange_no .
                    ' for ' . ($this->record->customer?->name ?? 'customer') .
                    ' | Credit: $' . number_format($this->record->total_credit, 2) .
                    ' | Difference: $' . number_format($this->record->difference_amount, 2) .
                    '. Go to Sales → Exchanges to approve.'
                )
                ->warning()
                ->sendToDatabase($manager);
        }

        Notification::make()
            ->title('Exchange Submitted ✅')
            ->body('Exchange ' . $this->record->exchange_no . ' has been submitted for manager approval.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}