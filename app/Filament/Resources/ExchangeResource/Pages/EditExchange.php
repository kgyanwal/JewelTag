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
            Actions\DeleteAction::make()->visible(fn($record) => $record->status === 'pending_approval'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['returned_items_form'] = $data['returned_items'] ?? [];
        $data['new_items_form']      = $data['new_items'] ?? [];
        // Re-hydrate stock_no_display from persist field
        foreach ($data['new_items_form'] as $k => &$item) {
            if (empty($item['stock_no_display']) && !empty($item['stock_no_display_persist'])) {
                $item['stock_no_display'] = $item['stock_no_display_persist'];
            }
        }
        unset($item);
        // Sales person list
        if (!empty($data['sales_person_list']) && is_string($data['sales_person_list'])) {
            $data['sales_person_list'] = explode(',', $data['sales_person_list']);
        }
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $formItems = $data['returned_items_form'] ?? [];
        $returning = array_values(array_filter($formItems, fn($i) => !empty($i['returning'])));
        $data['returned_items'] = $returning;
        $data['total_credit']   = round(collect($returning)->sum(fn($i) => floatval($i['credit_amount'] ?? 0)), 2);

        $data['new_items'] = array_values($data['new_items_form'] ?? []);

        $dbTax   = DB::table('site_settings')->where('key','tax_rate')->value('value') ?? 7.63;
        $taxRate = floatval($dbTax) / 100;
        $sub = 0; $tax = 0;
        foreach ($data['new_items'] as $item) {
            $qty  = intval($item['qty'] ?? 1);
            $line = floatval($item['sale_price_override'] ?? 0);
            if ($line <= 0) $line = floatval($item['sold_price'] ?? 0) * $qty;
            $sub += $line;
            if (empty($item['is_tax_free'])) $tax += $line * $taxRate;
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

        unset($data['returned_items_form'], $data['new_items_form'],
              $data['current_item_search'], $data['current_item_qty'],
              $data['new_items_subtotal_display'], $data['new_items_tax_display']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}