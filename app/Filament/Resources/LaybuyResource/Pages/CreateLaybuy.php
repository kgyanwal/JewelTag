<?php

namespace App\Filament\Resources\LaybuyResource\Pages;

use App\Filament\Resources\LaybuyResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Models\Sale;
use App\Models\ProductItem;

class CreateLaybuy extends CreateRecord
{
    protected static string $resource = LaybuyResource::class;

    // ── Override Save with PIN verification — same as CreateSale ──
    protected function getFormActions(): array
    {
        return [
            Action::make('save_with_pin')
                ->label('Save Layby Plan')
                ->color('success')
                ->icon('heroicon-o-check')
                ->requiresConfirmation()
                ->modalHeading('Staff Verification')
                ->modalDescription('Enter your PIN to confirm and save this layby plan.')
                ->modalSubmitActionLabel('Verify & Save')
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
                    // 1. Verify PIN
                    $actualStaff = \App\Models\User::where('pin_code', $data['verification_pin'])
                        ->where('is_active', true)
                        ->first();

                    if (!$actualStaff) {
                        Notification::make()->title('Invalid PIN')->danger()->send();
                        return;
                    }

                    // 2. Replace terminal default with PIN-verified staff name
                    $existingStaff   = $this->data['sales_person_list'] ?? [];
                    $terminalDefault = Session::get('active_staff_name') ?? auth()->user()->name;

                    $existingStaff = array_values(array_filter($existingStaff, fn($s) => $s !== $terminalDefault));
                    if (!in_array($actualStaff->name, $existingStaff)) {
                        $existingStaff[] = $actualStaff->name;
                    }

                    $this->data['sales_person_list'] = !empty($existingStaff)
                        ? array_values($existingStaff)
                        : [$actualStaff->name];

                    // 3. Set sales_person varchar for laybuys table
                    $this->data['sales_person'] = implode(', ', $this->data['sales_person_list']);

                    $this->create();
                }),

            parent::getCancelFormAction(),
        ];
    }

    // ── This runs BEFORE handleRecordCreation — do NOT strip layby_items here ──
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Only strip UI-only fields that would cause mass-assignment errors on the Laybuy model
        // DO NOT remove layby_items here — handleRecordCreation needs them
        unset($data['item_search'], $data['totals_summary'], $data['totals_display']);
        return $data;
    }

    // ── Main creation logic — builds Sale + SaleItems + Laybuy + Payments ──
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($data) {

            // Pull everything we need before stripping
            $items         = $data['layby_items'] ?? [];
            $paymentMethod = $data['initial_payment_method'] ?? 'CASH';
            $amountPaid    = floatval($data['amount_paid'] ?? 0);
            $totalAmount   = floatval($data['total_amount'] ?? 0);
            $balanceDue    = max(0, $totalAmount - $amountPaid);
            $salesPerson   = $data['sales_person'] ?? auth()->user()->name;
            $staffList     = $data['sales_person_list'] ?? [$salesPerson];

            // 1. Create the umbrella Sale record
            $sale = Sale::create([
                'customer_id'       => $data['customer_id'],
                'invoice_number'    => 'INV-' . strtoupper(bin2hex(random_bytes(3))),
                'status'            => $balanceDue <= 0 ? 'completed' : 'pending',
                'sales_person_list' => $staffList,
                'payment_method'    => 'laybuy',
                'subtotal'          => $totalAmount,
                'final_total'       => $totalAmount,
                'amount_paid'       => $amountPaid,
                'balance_due'       => $balanceDue,
                'tax_amount'        => 0,
                'store_id'          => auth()->user()->store_id ?? 1,
            ]);

            // 2. Add items to Sale and reserve stock
            foreach ($items as $item) {
                $unitPrice = floatval($item['unit_price'] ?? $item['price'] ?? 0);
                $salePrice = floatval($item['sale_price'] ?? $unitPrice);
                $discPct   = floatval($item['discount_percent'] ?? 0);
                $discAmt   = floatval($item['discount_amount'] ?? 0);
                $isTaxFree = !empty($item['is_tax_free']);

                $sale->items()->create([
                    'product_item_id'     => $item['product_item_id'],
                    'custom_description'  => $item['description'],
                    'qty'                 => 1,
                    'sold_price'          => $unitPrice,
                    'sale_price_override' => $salePrice,
                    'discount_percent'    => $discPct,
                    'discount_amount'     => $discAmt,
                    'is_tax_free'         => $isTaxFree,
                ]);

                ProductItem::where('id', $item['product_item_id'])->update([
                    'status'          => 'on_hold',
                    'hold_reason'     => "Laybuy: {$sale->invoice_number}",
                    'held_by_sale_id' => $sale->id,
                ]);
            }

            // 3. Strip form-only fields before creating Laybuy record
            unset(
                $data['layby_items'],
                $data['initial_payment_method'],
                $data['sales_person_list'],
                $data['item_search'],
                $data['totals_summary'],
                $data['totals_display'],
                $data['imported_items_list'],
                $data['payment_history'],
                $data['progress_bar']
            );

            // 4. Set correct Laybuy fields
            $data['sale_id']      = $sale->id;
            $data['amount_paid']  = $amountPaid;
            $data['balance_due']  = $balanceDue;
            $data['status']       = $balanceDue <= 0 ? 'completed' : 'in_progress';
            $data['sales_person'] = $salesPerson;
            $data['start_date']   = now()->toDateString();

            // 5. Create the Laybuy record via parent (handles fillable etc.)
            $laybuy = parent::handleRecordCreation($data);

            // 6. Record initial deposit into both ledgers
            if ($amountPaid > 0) {
                \App\Models\LaybuyPayment::create([
                    'laybuy_id'      => $laybuy->id,
                    'amount'         => $amountPaid,
                    'payment_method' => $paymentMethod,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);

                \App\Models\Payment::create([
                    'sale_id'  => $sale->id,
                    'amount'   => $amountPaid,
                    'method'   => $paymentMethod,
                    'paid_at'  => now(),
                    'store_id' => auth()->user()->store_id ?? 1,
                ]);
            }

            return $laybuy;
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}