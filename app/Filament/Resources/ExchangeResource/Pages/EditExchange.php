<?php

namespace App\Filament\Resources\ExchangeResource\Pages;

use App\Filament\Resources\ExchangeResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditExchange extends EditRecord
{
    protected static string $resource = ExchangeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn($record) => $record->status === 'pending_approval'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['returned_items_form'] = $data['returned_items'] ?? [];
        $data['new_items_form']      = $data['new_items'] ?? [];
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $formItems = $data['returned_items_form'] ?? [];
        $returning = array_filter($formItems, fn($i) => !empty($i['returning']));
        $data['returned_items'] = array_values($returning);
        $data['total_credit']   = collect($returning)->sum('credit_amount');

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

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}