<?php

namespace App\Filament\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Sale;
use Filament\Forms\Components\{DatePicker, Grid, Section, Select, TextInput, Placeholder, Toggle, Group};
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Columns\{TextColumn, IconColumn};
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class FindSale extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon  = 'heroicon-o-magnifying-glass';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $title           = 'Find Sale';
    protected static string  $view            = 'filament.pages.find-sale';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    // ── Only reset the table when the user STOPS typing (debounced via onBlur)
    //    NOT on every keystroke — this is the #1 performance fix
    public function updated($property): void
    {
        if (str_starts_with($property, 'data.')) {
            $this->resetTable();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Section::make('Advanced Sale Search')
                    ->description('Filter by Customer details, Invoice #, or Job types')
                    ->aside()
                    ->schema([
                        Grid::make(4)->schema([
                            // ── Use live(onBlur: true) instead of ->live()
                            //    This fires only when the user tabs/clicks away,
                            //    not on every single keypress
                            TextInput::make('invoice_number')
                                ->label('Invoice / Job #')
                                ->live(onBlur: true),   // ← WAS: ->live()

                            TextInput::make('staff_name')
                                ->label('Sales Staff')
                                ->placeholder('e.g. Anthony')
                                ->live(onBlur: true),   // ← WAS: ->live()

                            TextInput::make('first_name')
                                ->label('First Name')
                                ->live(onBlur: true),   // ← WAS: ->live()

                            TextInput::make('last_name')
                                ->label('Last Name')
                                ->live(onBlur: true),   // ← WAS: ->live()

                            TextInput::make('phone')
                                ->label('Phone Number')
                                ->tel()
                                ->prefix('+1')
                                ->live(onBlur: true),   // ← WAS: ->live()

                            Select::make('payment_method')
                                ->options(fn() => \App\Filament\Resources\SaleResource::getPaymentOptions())
                                ->placeholder('All Methods')
                                ->live(),               // selects are fine as ->live() — no typing

                            DatePicker::make('date_from')
                                ->label('Date From')
                                ->live(),               // date pickers are fine too

                            Select::make('job_type')
                                ->label('Job Type')
                                ->options([
                                    'Resize'        => 'Resize',
                                    'Solder'        => 'Solder / Weld',
                                    'Bail Change'   => 'Bail Change',
                                    'Shorten'       => 'Shortening',
                                    'Stone Setting' => 'Stone Setting',
                                ])
                                ->placeholder('All Service Types')
                                ->live(),
                        ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Sale::query())
            ->modifyQueryUsing(
                fn(Builder $query) => $query
                    ->withoutTrashed()
                    // ── Eager load ALL relationships used in columns
                    //    This collapses N+1 queries into exactly 4 queries total
                    ->with([
                        'customer',           // used in customer name column
                        'items.productItem',  // used in items_summary column
                        'payments',           // used in payment_status_summary column
                    ])
                    ->when(
                        $this->data['invoice_number'] ?? null,
                        fn($q, $v) => $q->where('invoice_number', 'like', "%{$v}%")
                    )
                    ->when(
                        $this->data['staff_name'] ?? null,
                        fn($q, $v) => $q->where('sales_person_list', 'like', "%{$v}%")
                    )
                    ->when(
                        $this->data['first_name'] ?? null,
                        fn($q, $v) => $q->whereHas(
                            'customer',
                            fn($sq) => $sq->where('name', 'like', "%{$v}%")
                        )
                    )
                    ->when(
                        $this->data['last_name'] ?? null,
                        fn($q, $v) => $q->whereHas(
                            'customer',
                            fn($sq) => $sq->where('last_name', 'like', "%{$v}%")
                        )
                    )
                    ->when(
                        $this->data['payment_method'] ?? null,
                        fn($q, $v) => $q->where('payment_method', $v)
                    )
                    ->when(
                        $this->data['job_type'] ?? null,
                        fn($q, $v) => $q->where('job_type', $v)
                    )
                    ->when(
                        $this->data['date_from'] ?? null,
                        fn($q, $v) => $q->whereDate('created_at', '>=', $v)
                    )
                    ->latest()
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('DATE')
                    ->dateTime('M d, Y')
                    ->sortable(),

                TextColumn::make('invoice_number')
                    ->label('JOB #')
                    ->weight('bold')
                    ->color('primary')
                    ->copyable(),

                // ── customer relationship is now eager loaded so no extra query per row
                TextColumn::make('customer_name_display')
                    ->label('Customer')
                    ->getStateUsing(
                        fn($record) =>
                        $record->customer
                            ? trim($record->customer->name . ' ' . ($record->customer->last_name ?? ''))
                            : '—'
                    )
                    ->searchable(
                        query: function (Builder $query, string $search): Builder {
                            return $query->whereHas(
                                'customer',
                                fn($q) =>
                                $q->where('name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhereRaw("CONCAT(name, ' ', last_name) LIKE ?", ["%{$search}%"])
                            );
                        }
                    )
                    ->sortable(false),

                TextColumn::make('items_summary')
                    ->label('ITEMS / DESCRIPTION')
                    ->getStateUsing(function ($record) {
                        // items->productItem already eager loaded — no extra queries
                        return $record->items
                            ->take(3)   // ← cap at 3 lines per row to avoid huge cells
                            ->map(
                                fn($i) => ($i->productItem?->barcode ?? 'Item') . ': ' .
                                    Str::limit($i->custom_description ?? '', 30)
                            )
                            ->toArray();
                    })
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->color('gray')
                    ->size('xs'),

                TextColumn::make('sales_person_list')
                    ->label('SALES STAFF')
                    ->badge()
                    ->color('gray')
                    ->separator(',')
                    ->toggleable(),

                // ── payments already eager loaded — no extra query per row
            TextColumn::make('payment_status_summary')
    ->label('PAYMENT SUMMARY')
    ->getStateUsing(function ($record) {
        // 1. Identify if this is a Custom Order Conversion
        $isCustomDeposit = $record->has_trade_in
            && str_contains($record->trade_in_description ?? '', 'Prior Deposit');

        // 2. The Bill Total (Grand Total)
        // We add trade_in_value back because final_total is already "Net" in the DB
        $total = floatval($record->final_total);
        if ($isCustomDeposit) {
            $total += floatval($record->trade_in_value);
        }

        // 3. The actual money collected
        // We sum the payments table. 
        // IMPORTANT: Our CreateSale logic already linked custom order payments to this sale_id.
        $paid = floatval($record->payments->sum('amount'));

        // Fallback for legacy data not using the payments table
        if ($paid == 0 && floatval($record->amount_paid) > 0) {
            $paid = floatval($record->amount_paid);
        }

        $balance = max(0, $total - $paid);

        $html  = "<div class='text-xs text-gray-500'>Bill Total: $" . number_format($total, 2) . "</div>";
        $html .= "<div class='text-sm font-bold text-success-600'>Paid: $" . number_format($paid, 2) . "</div>";

        if ($balance > 0.01) {
            $html .= "<div class='text-sm font-bold text-danger-600'>Balance: $" . number_format($balance, 2) . "</div>";
        } else {
            $html .= "<div class='text-[10px] bg-success-100 text-success-700 px-1.5 py-0.5 rounded inline-block uppercase font-bold mt-1'>Fully Paid</div>";
        }

        return new HtmlString($html);
    }),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'completed'          => 'success',
                        'refunded'           => 'danger',
                        'partially_refunded' => 'warning',
                        'cancelled'          => 'gray',
                        default              => 'gray',
                    })
                    ->formatStateUsing(
                        fn(string $state) =>
                        strtoupper(str_replace('_', ' ', $state))
                    ),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->url(
                        fn(Sale $record): string =>
                        SaleResource::getUrl('edit', ['record' => $record])
                    ),

                Action::make('quick_view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->slideOver()
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->form(fn(Sale $record): array => [
                        Group::make()->schema([
                            Grid::make(3)->schema([
                                Section::make('Customer Info')
                                    ->columnSpan(2)
                                    ->columns(2)
                                    ->schema([
                                        Placeholder::make('c_name')
                                            ->label('Name')
                                            ->content(
                                                $record->customer
                                                    ? trim($record->customer->name . ' ' . ($record->customer->last_name ?? ''))
                                                    : 'Walk-in'
                                            ),
                                        Placeholder::make('c_phone')
                                            ->label('Phone')
                                            ->content($record->customer?->phone ?? '—'),
                                        Placeholder::make('c_email')
                                            ->label('Email')
                                            ->content($record->customer?->email ?? '—'),
                                        Placeholder::make('c_address')
                                            ->label('Address')
                                            ->content($record->customer?->street ?? '—'),
                                    ]),

                                Section::make('Quick Status')
                                    ->columnSpan(1)
                                    ->schema([
                                        Placeholder::make('s_invoice')
                                            ->label('Invoice #')
                                            ->content(new HtmlString(
                                                "<span class='font-mono font-bold text-lg text-primary-600'>{$record->invoice_number}</span>"
                                            )),
                                        Placeholder::make('s_status')
                                            ->label('Status')
                                            ->content(new HtmlString(
                                                "<span class='px-2 py-1 rounded text-xs font-bold uppercase " .
                                                    match ($record->status) {
                                                        'completed'          => 'bg-success-100 text-success-700',
                                                        'refunded'           => 'bg-danger-100 text-danger-700',
                                                        'partially_refunded' => 'bg-warning-100 text-warning-700',
                                                        default              => 'bg-gray-100 text-gray-700',
                                                    } . "'>{$record->status}</span>"
                                            )),
                                    ]),
                            ]),

                            Section::make('Bill Items')->schema([
                                Placeholder::make('items_html')
                                    ->label('')
                                    ->content(function () use ($record) {
                                        $html  = '<table class="w-full text-sm text-left border-collapse">';
                                        $html .= '<thead class="bg-gray-50 text-gray-600 uppercase text-[10px]"><tr>';
                                        $html .= '<th class="p-2">SKU</th><th class="p-2">Description</th>';
                                        $html .= '<th class="p-2 text-right">Price</th><th class="p-2 text-right">Disc</th><th class="p-2 text-right">Total</th>';
                                        $html .= '</tr></thead><tbody>';

                                        foreach ($record->items as $item) {
                                            $price    = floatval($item->sold_price);
                                            $disc     = floatval($item->discount_amount);
                                            $rowTotal = ($price * ($item->qty ?? 1)) - $disc;

                                            $html .= "<tr class='border-b border-gray-100'>";
                                            $html .= "<td class='p-2 font-mono text-primary-600'>"
                                                . ($item->productItem?->barcode ?? 'MANUAL') . "</td>";
                                            $html .= "<td class='p-2 text-gray-600'>"
                                                . e($item->custom_description) . "</td>";
                                            $html .= "<td class='p-2 text-right'>$" . number_format($price, 2) . "</td>";
                                            $html .= "<td class='p-2 text-right text-danger-600'>-$" . number_format($disc, 2) . "</td>";
                                            $html .= "<td class='p-2 text-right font-bold'>$" . number_format($rowTotal, 2) . "</td>";
                                            $html .= "</tr>";
                                        }

                                        $html .= '</tbody></table>';
                                        return new HtmlString($html);
                                    }),
                            ]),

                            Grid::make(2)->schema([
                                Section::make('Workshop Details')
                                    ->visible(fn() => !empty($record->job_type))
                                    ->columnSpan(1)
                                    ->schema([
                                        Placeholder::make('j_type')->label('Type')->content($record->job_type ?? '—'),
                                        Placeholder::make('j_metal')->label('Metal')->content($record->metal_type ?? '—'),
                                        Placeholder::make('j_size')->label('Sizing')
                                            ->content("{$record->current_size} ➔ {$record->target_size}"),
                                        Placeholder::make('j_notes')->label('Instructions')
                                            ->content($record->job_instructions ?? '—'),
                                    ]),

                                Section::make('Totals')
                                    ->columnSpan(fn() => !empty($record->job_type) ? 1 : 2)
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Placeholder::make('f_sub')
                                                ->label('Subtotal')
                                                ->content('$' . number_format($record->subtotal, 2)),
                                            Placeholder::make('f_tax')
                                                ->label('Tax')
                                                ->content('$' . number_format($record->tax_amount, 2)),
                                            Placeholder::make('f_trade')
                                                ->label('Trade-In')
                                                ->content('-$' . number_format($record->trade_in_value ?? 0, 2)),
                                            Placeholder::make('f_total')
                                                ->label('Grand Total')
                                                ->content(new HtmlString(
                                                    "<span class='text-xl font-black text-gray-900'>$"
                                                        . number_format($record->final_total, 2) . "</span>"
                                                )),
                                        ]),
                                       Placeholder::make('f_paid')
    ->label('Total Payments Received')
    ->content(function() use ($record) {
        // Source of truth: Sum of all payments linked to this Sale ID
        $amt = $record->payments->sum('amount');
        
        return new HtmlString(
            "<div class='p-2 bg-success-50 border border-success-200 rounded text-success-700 font-bold'>$" 
            . number_format($amt, 2) . "</div>"
        );
    }),
                                    ]),
                            ]),

                            Section::make('Internal Notes')->collapsed()->schema([
                                Placeholder::make('f_notes')
                                    ->label('')
                                    ->content($record->notes ?? 'No internal notes recorded.'),
                            ]),
                        ]),
                    ]),

                \Filament\Tables\Actions\ActionGroup::make([
                    \Filament\Tables\Actions\Action::make('printStandard')
                        ->label('Standard Receipt')
                        ->icon('heroicon-o-printer')
                        ->url(fn(Sale $record) => route('sales.receipt', ['record' => $record, 'type' => 'standard']))
                        ->openUrlInNewTab()
                        ->extraAttributes([
                            'onclick' => "let win = window.open(this.href, '_blank'); win.onload = function() { win.print(); }; return false;"
                        ]),

                    \Filament\Tables\Actions\Action::make('printGift')
                        ->label('Gift Receipt')
                        ->icon('heroicon-o-gift')
                        ->color('success')
                        ->url(fn(Sale $record) => route('sales.receipt', ['record' => $record, 'type' => 'gift']))
                        ->openUrlInNewTab(),

                    \Filament\Tables\Actions\Action::make('printJob')
                        ->label('Workshop / Job Card')
                        ->icon('heroicon-o-wrench')
                        ->color('warning')
                        ->url(fn(Sale $record) => route('sales.receipt', ['record' => $record, 'type' => 'job']))
                        ->openUrlInNewTab(),

                    Action::make('emailReceipt')
                        ->label('Email Receipt')
                        ->icon('heroicon-o-envelope')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function (Sale $record) {
                            if (!$record->customer || empty($record->customer->email)) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Email Missing')->body('Customer has no email address.')
                                    ->danger()->send();
                                return;
                            }
                            $mailable = new \App\Mail\CustomerReceipt($record);
                            $sent     = $mailable->sendDirectly();
                            if ($sent) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Receipt Sent')
                                    ->body("Successfully emailed to {$record->customer->email}")
                                    ->success()->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Email Error')->body('Check Laravel logs for SES details.')
                                    ->danger()->send();
                            }
                        }),

                    Action::make('smsReceipt')
                        ->label('SMS Receipt')
                        ->icon('heroicon-o-device-phone-mobile')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Send Receipt via SMS')
                        ->action(function (Sale $record) {
                            $phone = $record->customer->phone ?? null;
                            if (empty($phone)) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Error')->body('Customer has no phone number.')
                                    ->danger()->send();
                                return;
                            }
                            $digits = preg_replace('/[^0-9]/', '', $phone);
                            if (\Illuminate\Support\Str::startsWith($digits, '1') && strlen($digits) === 11) {
                                $digits = substr($digits, 1);
                            }
                            $formattedPhone = '+1' . $digits;
                            $store          = $record->store;
                            $baseUrl        = $store && !empty($store->domain_url)
                                ? rtrim($store->domain_url, '/')
                                : config('app.url');
                            $link      = $baseUrl . '/receipt/' . $record->id;
                            $storeName = $store->name ?? 'Diamond Square';
                            $message   = "Hi {$record->customer->name}, thanks for visiting {$storeName}! View your receipt here: {$link}";
                            try {
                                $settings = \Illuminate\Support\Facades\DB::table('site_settings')->pluck('value', 'key');
                                $sns      = new \Aws\Sns\SnsClient([
                                    'version'     => 'latest',
                                    'region'      => $settings['aws_sms_default_region'] ?? config('services.sns.region'),
                                    'credentials' => [
                                        'key'    => $settings['aws_sms_access_key_id'] ?? config('services.sns.key'),
                                        'secret' => $settings['aws_sms_secret_access_key'] ?? config('services.sns.secret'),
                                    ],
                                ]);
                                $sns->publish([
                                    'Message'     => $message,
                                    'PhoneNumber' => $formattedPhone,
                                    'MessageAttributes' => [
                                        'OriginationNumber' => [
                                            'DataType'    => 'String',
                                            'StringValue' => $settings['aws_sns_sms_from'] ?? config('services.sns.sms_from'),
                                        ],
                                    ],
                                ]);
                                \Filament\Notifications\Notification::make()->title('SMS Sent')->success()->send();
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('SMS Failed')->body($e->getMessage())->danger()->send();
                            }
                        }),
                ])
                    ->label('Print')
                    ->icon('heroicon-m-printer')
                    ->color('gray')
                    ->button()
                    ->outlined(),
            ])
            ->defaultPaginationPageOption(15)  // ← show 15 rows, not 25 (reduces render load)
            ->paginationPageOptions([10, 15, 25, 50]);
    }

    public function resetFilters(): void
    {
        $this->form->fill();
        $this->resetTable();
    }
}
