<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Laybuy;
use Filament\Resources\Pages\CreateRecord;
use App\Models\ProductItem;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Helpers\Staff;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Illuminate\Support\Str;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;
    protected bool $hasUnsavedChangesAlert = true;
    public ?string $draftId = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $data;
    }
protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['is_warranty_taxed'] = (bool) ($data['is_warranty_taxed'] ?? false);
        $data['shipping_taxed']    = (bool) ($data['shipping_taxed'] ?? false);
        $data['has_warranty']      = (int)  ($data['has_warranty'] ?? 0);
        $data['has_trade_in']      = (int)  ($data['has_trade_in'] ?? 0);
        $data['warranty_charge']   = (float)($data['warranty_charge'] ?? 0);
        unset($data['payment_target']);
        return $data;
    }
    public function form(Form $form): Form
    {
        return parent::form($form)->statePath('data');
    }

    public function mount(): void
    {
        parent::mount();

        // ── TAB-ISOLATED DRAFT ────────────────────────────────────────────────
        $this->draftId = request()->query('draft_id', Str::uuid()->toString());

        if (!request()->has('draft_id')) {
            $this->redirect(request()->fullUrlWithQuery(['draft_id' => $this->draftId]));
            return;
        }

        $sessionKey = "sale_draft_{$this->draftId}";

        $repairId      = request()->get('repair_id');
        $customOrderId = request()->get('custom_order_id');

        if ($repairId || $customOrderId) {
            session()->forget($sessionKey);
            $this->data = [];
        } elseif (session()->has($sessionKey)) {
            $this->data = session($sessionKey);
        }

        // ── HANDLE REPAIR ────────────────────────────────────────────────────
        if ($repairId) {
            $repair = \App\Models\Repair::find($repairId);
            if ($repair) {
                $this->data['customer_id'] = $repair->customer_id;

                $cartItems   = [];
                $repairItems = $repair->items ?? [];

                if (empty($repairItems) && !empty($repair->item_description)) {
                    $repairItems = [[
                        'item_description' => $repair->item_description,
                        'reported_issue'   => $repair->reported_issue,
                        'final_cost'       => $repair->final_cost,
                        'estimated_cost'   => $repair->estimated_cost,
                    ]];
                }

                foreach ($repairItems as $index => $rItem) {
                    $cost        = floatval($rItem['final_cost'] ?? $rItem['estimated_cost'] ?? 0);
                    $cartItems[] = [
                        'product_item_id'     => null,
                        'repair_id'           => $repair->id,
                        'stock_no_display'    => 'REPAIR #' . $repair->repair_no . '-' . ($index + 1),
                        'custom_description'  => ($rItem['item_description'] ?? 'Repair') . ' — ' . ($rItem['reported_issue'] ?? ''),
                        'qty'                 => 1,
                        'sold_price'          => $cost,
                        'sale_price_override' => $cost,
                        'discount_percent'    => 0,
                        'discount_amount'     => 0,
                        'is_tax_free'         => false,
                    ];
                }

                $this->data['items'] = $cartItems;
            }
        }

        // ── HANDLE CUSTOM ORDER ───────────────────────────────────────────────
        if ($customOrderId) {
            $customOrder = \App\Models\CustomOrder::find($customOrderId);
            if ($customOrder) {
                $this->data['customer_id'] = $customOrder->customer_id;

                $this->data['items'] = [[
                    'custom_order_id'     => $customOrder->id,
                    'stock_no_display'    => 'CUSTOM #' . $customOrder->order_no,
                    'custom_description'  => "Custom {$customOrder->product_name}",
                    'qty'                 => 1,
                    'sold_price'          => $customOrder->quoted_price,
                    'sale_price_override' => $customOrder->quoted_price,
                    'is_tax_free'         => true,
                ]];

                if ($customOrder->has_trade_in) {
                    $this->data['has_trade_in']         = 1;
                    $this->data['trade_in_value']       = $customOrder->trade_in_value;
                    $this->data['trade_in_description'] = $customOrder->trade_in_description;
                }

                $priorPayments  = \App\Models\Payment::where('custom_order_id', $customOrder->id)->get();
                $totalPriorPaid = $priorPayments->sum('amount');

                $this->data['is_split_payment'] = true;
                $this->data['split_payments']   = $priorPayments->map(fn($p) => [
                    'method'           => $p->method,
                    'amount'           => $p->amount,
                    'is_prior_deposit' => true,
                ])->toArray();

                $netBill  = floatval($customOrder->quoted_price) - floatval($customOrder->trade_in_value ?? 0);
                $dueToday = max(0, $netBill - $totalPriorPaid);

                if ($dueToday > 0) {
                    $this->data['split_payments'][] = [
                        'method' => 'CASH',
                        'amount' => $dueToday,
                    ];
                }

                $this->recalculateFinancials();
            }
        }

        if ($repairId || $customOrderId) {
            $this->data['has_trade_in']          = $this->data['has_trade_in'] ?? 0;
            $this->data['shipping_charges']      = $this->data['shipping_charges'] ?? 0;
            $this->data['carrier']               = $this->data['carrier'] ?? 'No carrier';
            $this->data['has_warranty']          = $this->data['has_warranty'] ?? 0;
            $this->data['shipping_taxed']        = $this->data['shipping_taxed'] ?? false;
            $this->data['follow_up_date']        = $this->data['follow_up_date'] ?? now()->addWeeks(2)->format('Y-m-d');
            $this->data['second_follow_up_date'] = $this->data['second_follow_up_date'] ?? now()->addMonths(6)->format('Y-m-d');
            $this->data['is_split_payment']      = $this->data['is_split_payment'] ?? false;
            $this->data['payment_method']        = $this->data['payment_method'] ?? 'cash';
            $this->data['status']                = $this->data['status'] ?? 'inprogress';
            $this->data['amount_paid']           = $this->data['amount_paid'] ?? 0;
            if (empty($this->data['sales_person_list'])) {
                $this->data['sales_person_list'] = [
                    \Illuminate\Support\Facades\Session::get('active_staff_name') ?? auth()->user()->name
                ];
            }
            $this->recalculateFinancials();
        }
    }

    protected function recalculateFinancials(): void
    {
        $set = function ($path, $value) {
            data_set($this->data, $path, $value);
        };

        $get = function ($path) {
            return data_get($this->data, $path);
        };

        SaleResource::updateTotals($get, $set);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('complete_sale')
                ->label('Complete Sale')
                ->color('success')
                ->icon('heroicon-o-check')
                ->disabled(function () {
                    $hasCustomer = !empty($this->data['customer_id']);
                    $hasItems    = !empty($this->data['items']);
                    $hasPayment  = !empty($this->data['payment_method']) || !empty($this->data['is_split_payment']);
                    return !$hasCustomer || !$hasItems || !$hasPayment;
                })
                ->extraAttributes([
                    'class'             => 'w-full md:w-auto',
                    'wire:loading.attr' => 'disabled',
                    'wire:target'       => 'complete_sale',
                ])
                ->requiresConfirmation()
                ->modalHeading('Staff Verification')
                ->modalDescription('Please enter your PIN to certify and finalize this sale.')
                ->modalSubmitActionLabel('Verify & Save')
                ->modalSubmitAction(fn($action) => $action->extraAttributes(['wire:loading.attr' => 'disabled']))
                ->form([
                    TextInput::make('verification_pin')
                        ->label('Enter PIN')
                        ->password()
                        ->revealable()
                        ->required()
                        ->numeric()
                        ->autofocus(),
                ])
                ->action(function (array $data) {
                    $formState = $this->data;

                    $actualStaff = \App\Models\User::where('pin_code', $data['verification_pin'])
                        ->where('is_active', true)
                        ->first();

                    if (!$actualStaff) {
                        Notification::make()->title('Invalid PIN')->danger()->send();
                        return;
                    }

                    $existingStaff   = $formState['sales_person_list'] ?? [];
                    $terminalDefault = Session::get('active_staff_name') ?? auth()->user()->name;

                    $existingStaff = array_values(array_filter(
                        $existingStaff,
                        fn($s) => $s !== $terminalDefault
                    ));

                    if (!in_array($actualStaff->name, $existingStaff)) {
                        $existingStaff[] = $actualStaff->name;
                    }

                    $formState['sales_person_list'] = !empty($existingStaff)
                        ? array_values($existingStaff)
                        : [$actualStaff->name];

                    $get = fn($path) => data_get($formState, $path);
                    $set = function ($path, $value) use (&$formState) {
                        data_set($formState, $path, $value);
                    };

                    SaleResource::updateTotals($get, $set);

                    if ($formState['is_split_payment'] ?? false) {
                        $formState['payment_method'] = 'split';
                    }

                    $this->data = $formState;

                    if ($this->record) {
                        return;
                    }

                    $this->create();
                }),

            parent::getCancelFormAction(),
        ];
    }

    protected function beforeCreate(): void
    {
        // 🛡️ DUPLICATE GUARD
        $existingDuplicate = \App\Models\Sale::where('customer_id', $this->data['customer_id'])
            ->where('final_total', $this->data['final_total'])
            ->where('created_at', '>=', now()->subSeconds(30))
            ->exists();

        if ($existingDuplicate) {
            Notification::make()
                ->title('Duplicate Sale Blocked')
                ->body('This sale was already saved. Please refresh the page.')
                ->danger()
                ->send();
            $this->halt();
            return;
        }

        $items = $this->data['items'] ?? [];

        foreach ($items as $item) {
            if (
                empty($item['product_item_id']) &&
                empty($item['repair_id']) &&
                empty($item['custom_order_id']) &&
                empty($item['is_new_custom_order'])
            ) {
                continue;
            }

            if (!empty($item['product_item_id'])) {
                $productItem = ProductItem::lockForUpdate()->find($item['product_item_id']);

                if (!$productItem) {
                    Notification::make()->title('Product Not Found')->body('A product in your cart was not found in inventory.')->danger()->send();
                    $this->halt();
                    return;
                }

                if ($productItem->qty < $item['qty']) {
                    Notification::make()->title('Insufficient Stock')->body("Only {$productItem->qty} left for {$productItem->barcode}.")->danger()->send();
                    $this->halt();
                    return;
                }

                if ($productItem->status === 'sold') {
                    Notification::make()->title('Item Already Sold')->body("{$productItem->barcode} is no longer available.")->danger()->send();
                    $this->halt();
                    return;
                }
            }
        }
    }

    protected function afterCreate(): void
    {
        DB::transaction(function () {
            $sale     = $this->record;
            $sale->load('items'); 
            $specialJobs = $sale->special_jobs ?? [];
            $isLaybuy = $sale->payment_method === 'laybuy';

            // Find the custom order if one exists in this cart
            $customOrderId = $sale->items->pluck('custom_order_id')->filter()->first();
            $customOrder = $customOrderId ? \App\Models\CustomOrder::find($customOrderId) : null;

            // 🚀 CRITICAL FIX 1: Link historical Custom Order payments to this new Sale record immediately
            if ($customOrder) {
                \App\Models\Payment::where('custom_order_id', $customOrder->id)
                    ->update(['sale_id' => $sale->id]);
            }

            // Gather payments safely
            $paymentsToProcess = [];
            if ($this->data['is_split_payment'] ?? false) {
                foreach ($this->data['split_payments'] ?? [] as $p) {
                    // 🚀 CRITICAL FIX 2: Do NOT create new payment records for prior custom deposits!
                    if (!empty($p['is_prior_deposit'])) {
                        continue;
                    }
                    $paymentsToProcess[] = [
                        'amount' => round((float) ($p['amount'] ?? 0), 2),
                        'method' => strtoupper(trim($p['method'] ?? 'CASH')),
                        'target' => $p['payment_target'] ?? 'regular'
                    ];
                }
            } else {
                $paymentsToProcess[] = [
                    'amount' => round((float) ($this->data['amount_paid'] ?? 0), 2),
                    'method' => strtoupper(trim($this->data['payment_method'] ?? 'CASH')),
                    'target' => $this->data['payment_target'] ?? 'regular'
                ];
            }

            // Loop and insert only TRULY NEW money collected today
            foreach ($paymentsToProcess as $p) {
                if ($p['amount'] <= 0) continue;

                $target = $p['target'] ?? 'regular';
                $isCustom = ($target === 'custom' && $customOrder);
                
                \App\Models\Payment::create([
                    'sale_id'         => $sale->id,
                    'custom_order_id' => $isCustom ? $customOrder->id : null,
                    'amount'          => $p['amount'],
                    'method'          => $p['method'],
                    'paid_at'         => now(),
                    'store_id'        => $sale->store_id ?? 1,
                ]);
            }

            // 🚀 Update Sale total directly from DB so it accounts for old AND new money
            $totalSalePaid = \App\Models\Payment::where('sale_id', $sale->id)->sum('amount');
            $sale->update([
                'amount_paid' => round($totalSalePaid, 2)
            ]);

            // Update the nested Custom Order parameters instantly
            if ($customOrder) {
                $allCustomPaid = \App\Models\Payment::where('custom_order_id', $customOrder->id)->sum('amount');
                $isTaxFree   = (bool)($customOrder->is_tax_free);
                $dbTax       = \Illuminate\Support\Facades\DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? 7.63;
                $taxRate     = $isTaxFree ? 0 : floatval($dbTax) / 100;
                $grandTotal  = floatval($customOrder->quoted_price) * (1 + $taxRate);

                $customOrder->update([
                    'amount_paid' => round($allCustomPaid, 2),
                    'balance_due' => round(max(0, $grandTotal - $allCustomPaid), 2),
                    'sale_id'     => $sale->id,
                    'status'      => 'in_production',
                ]);
            }

            // Update Inventory
            foreach ($sale->items as $saleItem) {
                if (!$saleItem->product_item_id) continue;

                $productItem = \App\Models\ProductItem::lockForUpdate()->find($saleItem->product_item_id);
                if (!$productItem) continue;

                $qtySold = (int) ($saleItem->qty ?? 1);
                $newQty  = max(0, $productItem->qty - $qtySold);

                $productItem->update([
                    'qty'             => $newQty,
                    'status'          => $isLaybuy ? 'on_hold' : ($newQty === 0 ? 'sold' : 'in_stock'),
                    'hold_reason'     => $isLaybuy ? "Laybuy: {$sale->invoice_number}" : null,
                    'held_by_sale_id' => $isLaybuy ? $sale->id : null,
                ]);
            }

            // Handle Laybuy logic
            if ($isLaybuy) {
                $salesPersonString = is_array($sale->sales_person_list)
                    ? implode(', ', $sale->sales_person_list)
                    : $sale->sales_person_list;

                \App\Models\Laybuy::create([
                    'laybuy_no'    => 'LB-' . date('Ymd-His'),
                    'customer_id'  => $sale->customer_id,
                    'sale_id'      => $sale->id,
                    'sales_person' => $salesPersonString,
                    'total_amount' => $sale->final_total,
                    'amount_paid'  => 0,
                    'balance_due'  => $sale->final_total,
                    'status'       => 'in_progress',
                    'start_date'   => now(),
                    'due_date'     => now()->addDays(30),
                ]);

                $sale->update([
                    'status'       => 'completed',
                    'completed_at' => now(),
                ]);

                \Filament\Notifications\Notification::make()
                    ->title('Stock Reserved')
                    ->body('Items are now ON HOLD. Initial agreement ready.')
                    ->warning()
                    ->send();
            }

      
if (!empty($specialJobs) && is_array($specialJobs)) {

    $saleItemsArray = $sale->items->values();

    foreach ($specialJobs as $job) {
        if (empty($job['job_type'])) continue;

        // ── Get selected item indexes (or default to first item) ──
        $selectedIndexes = $job['applicable_item_indexes'] ?? [0];
        if (empty($selectedIndexes)) {
            $selectedIndexes = [0];
        }

        // ── Build description from only the selected items ────────
        $selectedItems = collect($selectedIndexes)->map(function ($idx) use ($saleItemsArray) {
            return $saleItemsArray->get((int)$idx);
        })->filter();

        $itemDescription = $selectedItems->map(function ($item) {
            if ($item->productItem) {
                return $item->productItem->barcode . ' — ' . ($item->productItem->custom_description ?? '');
            }
            return $item->custom_description ?? 'Item';
        })->filter()->implode(', ');

        if (empty($itemDescription)) {
            $itemDescription = 'Item from Sale #' . $sale->invoice_number;
        }

        // ── Generate repair number ────────────────────────────────
        $datePrefix = now()->format('ymd');
        $sequence   = \App\Models\Repair::whereDate('created_at', today())->count() + 1;
        while (\App\Models\Repair::where('repair_no', $datePrefix . '-' . $sequence)->exists()) {
            $sequence++;
        }
        $repairNo = $datePrefix . '-' . $sequence;

        \App\Models\Repair::create([
            'sale_id'              => $sale->id,
            'repair_no'            => $repairNo,
            'customer_id'          => $sale->customer_id,
            'store_id'             => $sale->store_id,
            'staff_id'             => auth()->id(),
            'sales_person_list'    => is_array($sale->sales_person_list)
                                        ? $sale->sales_person_list
                                        : [$sale->sales_person_list],
            'status'               => 'received',
            'item_description'     => $itemDescription,
            'reported_issue'       => $job['job_type']
                                        . (!empty($job['current_size']) ? ' | Current: ' . $job['current_size'] : '')
                                        . (!empty($job['target_size'])  ? ' → Target: '  . $job['target_size']  : '')
                                        . (!empty($job['metal_type'])   ? ' | Metal: '   . $job['metal_type']   : ''),
            'repair_notes'         => $job['job_instructions'] ?? null,
            'customer_pickup_date' => $job['date_required'] ?? null,
            'estimated_cost'       => 0,
            'final_cost'           => null,
            'items'                => [[
                'item_description' => $itemDescription,
                'reported_issue'   => $job['job_type'],
                'job_type'         => $job['job_type'],
                'metal_type'       => $job['metal_type'] ?? null,
                'current_size'     => $job['current_size'] ?? null,
                'target_size'      => $job['target_size'] ?? null,
                'job_instructions' => $job['job_instructions'] ?? null,
                'date_required'    => $job['date_required'] ?? null,
                'estimated_cost'   => 0,
                'final_cost'       => null,
            ]],
        ]);
    }
}
        });

        session()->forget("sale_draft_{$this->draftId}");
        $this->data = [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function updated($property): void
    {
        if ($this->draftId) {
            session(["sale_draft_{$this->draftId}" => $this->data]);
        }
    }
}