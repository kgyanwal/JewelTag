<?php

namespace App\Filament\Resources\ExchangeResource\Pages;

use App\Filament\Resources\ExchangeResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;
use Illuminate\Support\Facades\DB;

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

        foreach ($data['new_items_form'] as $k => &$item) {
            if (empty($item['stock_no_display']) && !empty($item['stock_no_display_persist'])) {
                $item['stock_no_display'] = $item['stock_no_display_persist'];
            }
            $item['is_tax_free'] = filter_var($item['is_tax_free'] ?? false, FILTER_VALIDATE_BOOLEAN);
        }
        unset($item);

        if (!empty($data['sales_person_list']) && is_string($data['sales_person_list'])) {
            $data['sales_person_list'] = explode(',', $data['sales_person_list']);
        }

        $data['from_custom_order']     = filter_var($data['from_custom_order'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $data['co_item_in_production'] = filter_var($data['co_item_in_production'] ?? true,  FILTER_VALIDATE_BOOLEAN);
        $data['co_item_arrived']       = filter_var($data['co_item_arrived']       ?? false, FILTER_VALIDATE_BOOLEAN);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['original_sale_id']      = !empty($data['original_sale_id']) ? $data['original_sale_id'] : (\App\Models\Sale::latest()->value('id') ?? 1);
        $data['from_custom_order']     = filter_var($data['from_custom_order'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $data['custom_order_id']       = !empty($data['custom_order_id']) ? $data['custom_order_id'] : null;
        $data['co_item_in_production'] = filter_var($data['co_item_in_production'] ?? true,  FILTER_VALIDATE_BOOLEAN);
        $data['co_item_arrived']       = filter_var($data['co_item_arrived']       ?? false, FILTER_VALIDATE_BOOLEAN);

        // THE FIX: If from a custom order, we bypass the hidden repeater and pull credit directly from DB
        if ($data['from_custom_order'] && $data['custom_order_id']) {
            $co = \App\Models\CustomOrder::find($data['custom_order_id']);
            $paid = \App\Models\Payment::where('custom_order_id', $co->id)->sum('amount');
            if ($paid <= 0) $paid = floatval($co->amount_paid ?? 0);
            
            $data['total_credit'] = round($paid, 2);
            $data['returned_items'] = [[
                'item_source'     => 'custom_order',
                'custom_order_id' => $co->id,
                'description'     => "Custom Order #{$co->order_no} — {$co->product_name}",
                'stock_no'        => 'CUSTOM',
                'credit_amount'   => $data['total_credit'],
                'returning'       => true,
            ]];
        } else {
            $formItems = $data['returned_items_form'] ?? [];
            $returning = array_values(array_filter($formItems, fn($i) => !empty($i['returning'])));
            $data['returned_items'] = $returning;
            $data['total_credit']   = round(collect($returning)->sum(fn($i) => floatval($i['credit_amount'] ?? 0)), 2);
        }

        $data['new_items'] = array_values($data['new_items_form'] ?? []);

        $dbTax   = DB::table('site_settings')->where('key','tax_rate')->value('value') ?? 7.63;
        $taxRate = floatval($dbTax) / 100;
        $sub = 0; $tax = 0;

        foreach ($data['new_items'] as $item) {
            $qty      = intval($item['qty'] ?? 1);
            $rowTotal = (isset($item['sale_price_override']) && floatval($item['sale_price_override']) > 0)
                ? floatval($item['sale_price_override'])
                : (floatval($item['sold_price'] ?? 0) * $qty) - floatval($item['discount_amount'] ?? 0);
            $sub += $rowTotal;
            $isTaxFree = filter_var($item['is_tax_free'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if (!$isTaxFree) $tax += $rowTotal * $taxRate;
        }

        $data['new_items_subtotal'] = round($sub, 2);
        $data['new_items_tax']      = round($tax, 2);
        $data['new_sale_amount']    = round($sub + $tax, 2);
        $data['difference_amount']  = round($data['new_sale_amount'] - $data['total_credit'], 2);

        $diff = floatval($data['difference_amount']);
        $data['exchange_type'] = $diff > 0.009 ? 'upgrade' : ($diff < -0.009 ? 'downgrade' : 'same_value');

        if (!empty($data['split_payments'])) {
            $data['split_payments'] = array_values(array_filter($data['split_payments'] ?? [], fn($p) => floatval($p['amount'] ?? 0) > 0));
        }

        if (!empty($data['sales_person_list']) && is_array($data['sales_person_list'])) {
            $data['sales_person_list'] = implode(',', $data['sales_person_list']);
        }

        unset(
            $data['returned_items_form'], $data['new_items_form'],
            $data['current_item_search'], $data['current_item_qty'],
            $data['new_items_subtotal_display'], $data['new_items_tax_display'],
            $data['co_grand_total'], $data['co_total_paid'], $data['co_balance_due'],
            $data['co_product_name'], $data['co_status']
        );

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}