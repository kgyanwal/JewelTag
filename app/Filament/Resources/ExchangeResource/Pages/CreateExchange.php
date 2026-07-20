<?php

namespace App\Filament\Resources\ExchangeResource\Pages;

use App\Filament\Resources\ExchangeResource;
use App\Models\Exchange;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class CreateExchange extends CreateRecord
{
    protected static string $resource = ExchangeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['exchange_no']  = Exchange::generateExchangeNo();
        $data['requested_by'] = auth()->id();
        $data['store_id']     = auth()->user()->store_id ?? \App\Models\Store::first()?->id ?? 1;
        $data['status']       = 'pending_approval';

        // Fix for SQL Integrity constraint 1048
        $data['original_sale_id']      = !empty($data['original_sale_id']) ? $data['original_sale_id'] : (\App\Models\Sale::latest()->value('id') ?? 1);
        $data['from_custom_order']     = filter_var($data['from_custom_order'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $data['custom_order_id']       = !empty($data['custom_order_id']) ? $data['custom_order_id'] : null;
        $data['co_item_in_production'] = filter_var($data['co_item_in_production'] ?? true,  FILTER_VALIDATE_BOOLEAN);
        $data['co_item_arrived']       = filter_var($data['co_item_arrived']       ?? false, FILTER_VALIDATE_BOOLEAN);

        // ── Returned items & Total Credit ─────────────────────────────
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

        // ── New items — save repeater rows as-is ──────────────────────
        $data['new_items'] = array_values($data['new_items_form'] ?? []);

        // ── Recalculate totals with proper tax handling ────────────────
        $dbTax   = DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? 7.63;
        $taxRate = floatval($dbTax) / 100;
        $sub = 0;
        $tax = 0;

        foreach ($data['new_items'] as $item) {
            $qty  = intval($item['qty'] ?? 1);

            // sale_price_override = pre-tax line total (after discount)
            $linePreTax = floatval($item['sale_price_override'] ?? 0);
            if ($linePreTax <= 0) {
                $linePreTax = (floatval($item['sold_price'] ?? 0) * $qty) - floatval($item['discount_amount'] ?? 0);
            }

            $sub += $linePreTax;

            // Use filter_var to safely cast any truthy value
            $isTaxFree = filter_var($item['is_tax_free'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if (!$isTaxFree) {
                $tax += $linePreTax * $taxRate;
            }
        }

        $data['new_items_subtotal'] = round($sub, 2);
        $data['new_items_tax']      = round($tax, 2);
        $data['new_sale_amount']    = round($sub + $tax, 2);
        $data['difference_amount']  = round($data['new_sale_amount'] - $data['total_credit'], 2);

        $diff = floatval($data['difference_amount']);
        $data['exchange_type'] = $diff > 0.009 ? 'upgrade' : ($diff < -0.009 ? 'downgrade' : 'same_value');

        if (!empty($data['is_split_payment'])) {
            $data['split_payments'] = array_values(array_filter(
                $data['split_payments'] ?? [],
                fn($p) => floatval($p['amount'] ?? 0) > 0
            ));
        }

        if (!empty($data['sales_person_list']) && is_array($data['sales_person_list'])) {
            $data['sales_person_list'] = implode(',', $data['sales_person_list']);
        }

        unset(
            $data['returned_items_form'],
            $data['new_items_form'],
            $data['current_item_search'],
            $data['current_item_qty'],
            $data['new_items_subtotal_display'],
            $data['new_items_tax_display'],
            $data['co_grand_total'],
            $data['co_total_paid'],
            $data['co_balance_due'],
            $data['co_product_name'],
            $data['co_status']
        );

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
                    ' | Diff: $' . number_format($this->record->difference_amount, 2)
                )
                ->warning()->sendToDatabase($manager);
        }
        Notification::make()
            ->title('Exchange Submitted ✅')
            ->body('Exchange ' . $this->record->exchange_no . ' submitted for approval.')
            ->success()->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}