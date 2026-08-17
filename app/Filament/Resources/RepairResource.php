<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RepairResource\Pages;
use App\Models\Repair;
use App\Models\ProductItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\{Section, TextInput, Select, Toggle, Grid, Textarea, Placeholder, Repeater, Hidden, Group};
use App\Forms\Components\CustomDatePicker;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Aws\Sns\SnsClient;
use Tapp\FilamentGoogleAutocomplete\Forms\Components\GoogleAutocomplete;
use Illuminate\Support\Facades\Session;

class RepairResource extends Resource
{
    protected static ?string $model = Repair::class;
    protected static ?string $navigationIcon = 'heroicon-o-wrench';
    protected static ?string $navigationGroup = 'Sales';

    // 🚀 NEW — Sums every service's charge across every item on this repair ticket.
    // Prefers final_cost (what was actually charged) over estimated_cost (the quote),
    // and skips warranty items entirely (always $0), matching the table's own display logic.
    public static function calculateRepairTotal(Repair $record): array
    {
        $subtotal        = 0;
        $taxableSubtotal = 0; // 🚀 NEW — only non-tax-free items contribute to the taxable base

        foreach ($record->items ?? [] as $item) {
            $isWarranty = !empty($item['is_warranty']);
            $isTaxFree  = !empty($item['is_tax_free']); // 🚀 NEW
            if ($isWarranty) continue;

            $itemTotal = 0;
            foreach ($item['services'] ?? [] as $svc) {
                $itemTotal += (isset($svc['final_cost']) && $svc['final_cost'] !== '' && $svc['final_cost'] !== null)
                    ? floatval($svc['final_cost'])
                    : floatval($svc['estimated_cost'] ?? 0);
            }

            $subtotal += $itemTotal;
            if (!$isTaxFree) {
                $taxableSubtotal += $itemTotal;
            }
        }

        $dbTax   = \Illuminate\Support\Facades\DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? 7.63;
        $taxRate = floatval($dbTax) / 100;
        $tax     = round($taxableSubtotal * $taxRate, 2); // 🚀 CHANGED — tax only on taxable portion
        $total   = round($subtotal + $tax, 2);

        $paid    = round(\App\Models\Payment::where('repair_id', $record->id)->sum('amount'), 2);
        $balance = max(0, round($total - $paid, 2));

        return compact('subtotal', 'tax', 'total', 'paid', 'balance');
    }

    // 🚀 NEW — Mirrors CustomOrderResource::createSaleDirectly(). Fires automatically once
    // a repair's balance hits $0 via the "Add Deposit" action below, so the finished repair
    // shows up in Sales Reports exactly like a completed Custom Order does.
    public static function createSaleFromRepair(Repair $record): \App\Models\Sale
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($record) {
            $calc = self::calculateRepairTotal($record);

            $staffIds = $record->sales_person_list;
            if (is_string($staffIds)) {
                $decoded  = json_decode($staffIds, true);
                $staffIds = is_array($decoded) ? $decoded : [];
            }
            if (empty($staffIds) || !is_array($staffIds)) {
                $staffIds = $record->staff_id ? [$record->staff_id] : [];
            }
            $staffNames = \App\Models\User::whereIn('id', $staffIds)->pluck('name')->toArray();
            if (empty($staffNames)) {
                $staffNames = [auth()->user()->name ?? 'Unknown'];
            }

            $description = collect($record->items ?? [])
                ->pluck('item_description')
                ->filter()
                ->implode(', ') ?: ('Repair #' . $record->repair_no);

            $invoiceNumber = 'R' . str_pad((string) (\App\Models\Sale::max('id') + 1), 4, '0', STR_PAD_LEFT);

            $sale = \App\Models\Sale::create([
                'invoice_number'    => $invoiceNumber,
                'customer_id'       => $record->customer_id,
                'store_id'          => $record->store_id ?? auth()->user()->store_id ?? 1,
                'sales_person_list' => $staffNames,
                'subtotal'          => round($calc['subtotal'], 2),
                'tax_amount'        => $calc['tax'],
                'final_total'       => $calc['total'],
                'amount_paid'       => $calc['paid'],
                'balance_due'       => max(0, round($calc['total'] - $calc['paid'], 2)),
                'payment_method'    => 'split',
                'is_split_payment'  => true,
                'status'            => 'completed',
                'completed_at'      => now(),
                'notes'             => 'Created from Repair #' . $record->repair_no,
            ]);

            $allItemsTaxFree = collect($record->items ?? [])
                ->filter(fn($item) => empty($item['is_warranty']))
                ->every(fn($item) => !empty($item['is_tax_free']));

            $sale->items()->create([
                'repair_id'           => $record->id,
                'stock_no_display'    => 'REPAIR #' . $record->repair_no,
                'custom_description'  => $description,
                'qty'                 => 1,
                'sold_price'          => round($calc['subtotal'], 2),
                'sale_price_override' => round($calc['subtotal'], 2),
                'is_tax_free'         => $allItemsTaxFree,
                'is_non_stock'        => true,
            ]);

            // Re-point every deposit payment already collected on this repair to the new Sale
            \App\Models\Payment::where('repair_id', $record->id)->update(['sale_id' => $sale->id]);

            $record->update([
                'sale_id' => $sale->id,
                'status'  => 'delivered',
            ]);

            return $sale;
        });
    }

    public static function updateRepairTotals(callable|Forms\Get $get, callable|Forms\Set $set): void
    {
        $items           = $get('items') ?? [];
        $subtotal        = 0;
        $taxableSubtotal = 0; // 🚀 NEW

        foreach ($items as $item) {
            if (!empty($item['is_warranty'])) continue;

            $itemTotal = 0;
            foreach ($item['services'] ?? [] as $svc) {
                $itemTotal += (isset($svc['final_cost']) && $svc['final_cost'] !== '' && $svc['final_cost'] !== null)
                    ? floatval($svc['final_cost'])
                    : floatval($svc['estimated_cost'] ?? 0);
            }

            $subtotal += $itemTotal;
            if (empty($item['is_tax_free'])) {
                $taxableSubtotal += $itemTotal;
            }
        }

        $dbTax   = \Illuminate\Support\Facades\DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? 7.63;
        $taxRate = floatval($dbTax) / 100;
        $tax     = round($taxableSubtotal * $taxRate, 2); // 🚀 CHANGED
        $total   = round($subtotal + $tax, 2);

        $set('repair_subtotal', number_format($subtotal, 2, '.', ''));
        $set('repair_tax',      number_format($tax, 2, '.', ''));
        $set('repair_total',    number_format($total, 2, '.', ''));

        $isSplit = $get('is_split_payment');
        if ($isSplit) {
            $payments       = $get('split_payments') ?? [];
            $totalCollected = collect($payments)->sum(fn($p) => (float) ($p['amount'] ?? 0));
        } else {
            $totalCollected = floatval($get('amount_paid') ?? 0);
        }

        $changeGiven = max(0, $totalCollected - $total);
        $balanceDue  = max(0, $total - $totalCollected);

        $set('change_given', number_format($changeGiven, 2, '.', ''));
        $set('balance_due',  number_format($balanceDue, 2, '.', ''));
    }

    private static function staffBadge(?string $name): string
    {
        if (!$name) return '<span style="color:#9ca3af;font-size:11px;">—</span>';
        $palette = [
            ['#DBEAFE', '#1D4ED8'],
            ['#EDE9FE', '#6D28D9'],
            ['#CCFBF1', '#0F766E'],
            ['#FEF3C7', '#B45309'],
            ['#DCFCE7', '#15803D'],
            ['#FCE7F3', '#BE185D'],
            ['#FEE2E2', '#B91C1C'],
            ['#F3E8FF', '#7E22CE'],
            ['#CFFAFE', '#0E7490'],
            ['#FFEDD5', '#C2410C'],
            ['#E0E7FF', '#4338CA'],
            ['#ECFCCB', '#4D7C0F'],
        ];
        $index = abs(crc32(strtolower(trim($name)))) % count($palette);
        [$bg, $text] = $palette[$index];
        $short = strtoupper(substr(trim($name), 0, 6));
        return "<span style='background:{$bg};color:{$text};padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;letter-spacing:0.5px;white-space:nowrap;display:inline-block;border:1px solid {$text}33;'>{$short}</span>";
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(12)->schema([

                // ── LEFT ──────────────────────────────────────────────
                Group::make()->columnSpan(['lg' => 8])->schema([

                    Section::make('Repair Intake')
                        ->description('Record customer details and overall ticket status.')
                        ->icon('heroicon-o-user-circle')
                        ->schema([
                            Grid::make(2)->schema([
                                Select::make('customer_id')
                                    ->label('Select Customer')
                                    ->relationship('customer', 'name')
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->name} {$record->last_name} | {$record->phone} (#{$record->customer_no})")
                                    ->searchable()
                                    ->getSearchResultsUsing(function (string $search) {
                                        return \App\Models\Customer::query()
                                            ->where(function ($q) use ($search) {
                                                $q->whereRaw("CONCAT(name, ' ', last_name) LIKE ?", ["%{$search}%"])
                                                    ->orWhereRaw("CONCAT(last_name, ' ', name) LIKE ?", ["%{$search}%"])
                                                    ->orWhere('phone', 'like', "%{$search}%")
                                                    ->orWhere('customer_no', 'like', "%{$search}%");
                                            })
                                            ->limit(50)->get()
                                            ->mapWithKeys(fn($c) => [$c->id => "{$c->name} {$c->last_name} | {$c->phone} (#{$c->customer_no})"]);
                                    })
                                    ->preload()
                                    ->hintActions([
                                        FormAction::make('view_customer_details')
                                            ->label('View Profile')->icon('heroicon-o-user-circle')->color('info')
                                            ->visible(fn(Forms\Get $get) => $get('customer_id') !== null)
                                            ->modalHeading('Customer Profile Details')
                                            ->modalSubmitAction(false)->modalCancelActionLabel('Close')->slideOver()
                                            ->form(function (Forms\Get $get) {
                                                $customer = \App\Models\Customer::find($get('customer_id'));
                                                if (!$customer) return [];
                                                return [
                                                    Grid::make(2)->schema([
                                                        Placeholder::make('img')->label('')
                                                            ->content(new HtmlString("
                                                                <div class='flex items-center gap-4 p-4 bg-gray-50 rounded-xl border border-gray-200'>
                                                                    <img src='" . ($customer->image ? asset('storage/' . $customer->image) : asset('jeweltaglogo.png')) . "' class='w-20 h-20 rounded-full object-cover shadow-sm'>
                                                                    <div>
                                                                        <h3 class='text-lg font-bold text-gray-900'>{$customer->name} {$customer->last_name}</h3>
                                                                        <p class='text-sm text-gray-500'>ID: {$customer->customer_no}</p>
                                                                        <span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-800 mt-1'>Balance: $" . number_format($customer->credit_balance ?? 0, 2) . "</span>
                                                                    </div>
                                                                </div>
                                                            "))->columnSpanFull(),
                                                        TextInput::make('p')->label('Phone')->default($customer->phone)->readOnly(),
                                                        TextInput::make('e')->label('Email')->default($customer->email)->readOnly(),
                                                        TextInput::make('addr')->label('Full Address')
                                                            ->default(trim("{$customer->street} {$customer->suburb} {$customer->city} {$customer->state} {$customer->postcode}"))
                                                            ->readOnly()->columnSpanFull(),
                                                    ]),
                                                ];
                                            }),
                                        FormAction::make('Help')->icon('heroicon-o-information-circle')->tooltip('Click + to add a new customer'),
                                    ])
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\Tabs::make('New Customer')->tabs([
                                            Forms\Components\Tabs\Tab::make('Contact')->icon('heroicon-o-user')->schema([
                                                Grid::make(2)->schema([
                                                    TextInput::make('name')->label('First Name')->required(),
                                                    TextInput::make('last_name')->label('Last Name'),
                                                ]),
                                                Grid::make(2)->schema([
                                                    TextInput::make('phone')->label('Mobile Phone')->tel()->prefix('+1')
                                                        ->mask('(999) 999-9999')->placeholder('(555) 555-5555')
                                                        ->stripCharacters(['(', ')', '-', ' '])->rule('regex:/^[0-9]{10}$/')
                                                        ->afterStateHydrated(function ($component, $state) {
                                                            if ($state && preg_match('/^[0-9]{10}$/', $state))
                                                                $component->state('(' . substr($state, 0, 3) . ') ' . substr($state, 3, 3) . '-' . substr($state, 6));
                                                        }),
                                                    TextInput::make('email')->label('Email')->email(),
                                                ]),
                                                Grid::make(2)->schema([
                                                    CustomDatePicker::make('dob')->rule('before_or_equal:today')->label('Birth Date'),
                                                    CustomDatePicker::make('wedding_anniversary')->label('Wedding Date'),
                                                ]),
                                                Section::make('Customer Address')->columns(2)->collapsible()->schema([
                                                    GoogleAutocomplete::make('address_search')->label('Search Address')
                                                        ->autocompletePlaceholder('Start typing address...')->countries(['US'])->columnSpanFull()
                                                        ->withFields([
                                                            TextInput::make('street')->label('Street Address')->extraInputAttributes(['data-google-field' => '{street_number} {route}']),
                                                            TextInput::make('address_line_2')->label('Address 2')->extraInputAttributes(['data-google-field' => 'subpremise']),
                                                            TextInput::make('city')->label('City')->extraInputAttributes(['data-google-field' => 'locality', 'data-google-value' => 'short_name']),
                                                            TextInput::make('state')->label('State')->extraInputAttributes(['data-google-field' => 'administrative_area_level_1']),
                                                            TextInput::make('postcode')->label('Zip Code')->extraInputAttributes(['data-google-field' => 'postal_code']),
                                                        ]),
                                                    Select::make('country')->label('Country')->default('United States')->searchable(),
                                                ]),
                                            ]),
                                        ]),
                                        Hidden::make('customer_no')->default(fn() => 'CUST-' . strtoupper(Str::random(6))),
                                    ])
                                    ->createOptionUsing(fn(array $data) => \App\Models\Customer::create($data)->id),

                                Select::make('status')
                                    ->options(['received' => 'Received', 'in_progress' => 'In Progress', 'ready' => 'Ready for Pickup', 'delivered' => 'Delivered'])
                                    ->default('received')->required(),
                            ]),
                        ]),

                    Section::make('Jewelry Items to Repair')
                        ->description('Add all items brought in. Each item can have its own photos, issues, and services with individual pricing.')
                        ->icon('heroicon-o-sparkles')
                        ->schema([
                            Repeater::make('items')->label('')
                                ->schema([

                                    Section::make('Item Origin')
                                        ->description('Where did this piece come from?')
                                        ->icon('heroicon-o-tag')
                                        ->schema([
                                            Grid::make(3)->schema([
                                                Toggle::make('is_warranty')
                                                    ->label('Covered Under Warranty?')
                                                    ->helperText('Automatically sets all service costs to $0.00')
                                                    ->onColor('success')->inline(false)->live()
                                                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                                        if ($state) {
                                                            // Zero out all service costs via the services array
                                                            $set('services', collect($get('services') ?? [])->map(function ($svc) {
                                                                $svc['estimated_cost'] = 0;
                                                                $svc['final_cost']     = 0;
                                                                return $svc;
                                                            })->toArray());
                                                        }
                                                    }),
                                                Toggle::make('is_from_store_stock')->label('Was this bought from our store?')->inline(false)->live(),
                                                // 🚀 Tax-free flag per item, same role as is_tax_free on Sale/CustomOrder items
                                                Toggle::make('is_tax_free')
                                                    ->label('Tax Free?')
                                                    ->helperText('Exempt this item\'s services from tax')
                                                    ->onColor('warning')->inline(false)->live(),
                                            ]),
                                            Select::make('original_product_id')
                                                ->label('Search Store Stock No.')
                                                ->placeholder('Search by stock number...')
                                                ->options(fn() => \App\Models\ProductItem::query()->whereNotNull('barcode')->orderBy('barcode')->limit(200)->get()
                                                    ->mapWithKeys(fn($item) => [$item->id => "{$item->barcode} — " . Str::limit($item->custom_description ?? '', 40) . " [{$item->status}]"]))
                                                ->getSearchResultsUsing(fn(string $search) => \App\Models\ProductItem::query()
                                                    ->where(fn($q) => $q->where('barcode', 'like', "%{$search}%")->orWhere('custom_description', 'like', "%{$search}%"))
                                                    ->limit(50)->get()
                                                    ->mapWithKeys(fn($item) => [$item->id => "{$item->barcode} — " . Str::limit($item->custom_description ?? '', 40) . " [{$item->status}]"]))
                                                ->getOptionLabelUsing(fn($v) => \App\Models\ProductItem::find($v)?->barcode . ' — ' . \App\Models\ProductItem::find($v)?->custom_description)
                                                ->searchable()->live()
                                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                    if ($item = \App\Models\ProductItem::find($state))
                                                        $set('item_description', $item->custom_description ?? $item->barcode);
                                                })
                                                ->visible(fn(Forms\Get $get) => $get('is_from_store_stock'))
                                                ->columnSpanFull(),
                                        ]),

                                    // ── PHOTO DOCUMENTATION ───────────────────────────
                                    Section::make('Photo Documentation')
                                        ->description('Capture the item\'s intake condition.')
                                        ->icon('heroicon-o-camera')->collapsible()
                                        ->schema([
                                            Forms\Components\FileUpload::make('item_photo')
                                                ->label('Item Photo (Intake Condition)')
                                                ->image()->disk('public')->directory('repair-intake-photos')
                                                ->visibility('public')->imageEditor()
                                                ->imageEditorAspectRatios(['1:1', '4:3', null])
                                                ->maxSize(5120)->columnSpanFull()
                                                ->extraInputAttributes(['capture' => 'environment']),
                                            Forms\Components\Hidden::make('captured_photos')->default([])->dehydrated(true),
                                            Placeholder::make('webcam_view')->label('')->live()
                                                ->content(function (Forms\Get $get, $component) {
                                                    $photos   = $get('captured_photos') ?? [];
                                                    if (!is_array($photos)) $photos = [];
                                                    $statePath = $component->getStatePath();
                                                    $fieldPath = str_replace('webcam_view', 'captured_photos', $statePath);
                                                    $key = md5($fieldPath . count($photos));
                                                    return new HtmlString(\Illuminate\Support\Facades\Blade::render(
                                                        '<livewire:repair-webcam-capture :statePath="$statePath" :photos="$photos" :key="$key" />',
                                                        ['statePath' => $fieldPath, 'photos' => $photos, 'key' => $key]
                                                    ));
                                                }),
                                        ]),

                                    // ── DESCRIPTION & ISSUE ──────────────────────────
                                    Section::make('Description & Issue')->icon('heroicon-o-document-text')->schema([
                                        Grid::make(2)->schema([
                                            Textarea::make('item_description')
                                                ->label('Item Name / Description')
                                                ->placeholder('e.g., 14k White Gold Diamond Engagement Ring')
                                                ->required()->maxLength(3000)->rows(3),
                                            Textarea::make('reported_issue')
                                                ->label('Issue Reported / Instructions')
                                                ->placeholder('e.g., Needs sizing to 6.5, missing side stone...')
                                                ->required()->rows(3),
                                        ]),
                                    ]),

                                    // ── SERVICES WITH PRICING ─────────────────────────
                                    Section::make('Services & Pricing')
                                        ->description('Add each service required. Set the quote and final cost per service so customers see exactly what they\'re paying for.')
                                        ->icon('heroicon-o-wrench-screwdriver')
                                        ->schema([
                                            Repeater::make('services')->label('')
                                                ->addActionLabel('+ Add Another Service')
                                                ->defaultItems(1)->collapsible()->reorderable(false)
                                                ->itemLabel(function (array $state): string {
                                                    $label = $state['job_type'] ?? 'New Service';
                                                    $final = $state['final_cost'] ?? null;
                                                    $est   = floatval($state['estimated_cost'] ?? 0);
                                                    if ($final !== null && $final !== '' && floatval($final) > 0) {
                                                        $label .= '  ·  $' . number_format(floatval($final), 2) . ' charged';
                                                    } elseif ($est > 0) {
                                                        $label .= '  ·  $' . number_format($est, 2) . ' quoted';
                                                    }
                                                    return $label;
                                                })
                                                ->schema([

                                                    // Row 1: Service type + Metal + Date
                                                    Grid::make(12)->schema([
                                                        Select::make('job_type')
                                                            ->label('Service Type')
                                                            ->options(fn() => \App\Filament\Resources\SaleResource::getServiceTypeOptions())
                                                            ->placeholder('Select service...')
                                                            ->nullable()->live()->columnSpan(4),

                                                        Select::make('metal_type')
                                                            ->label('Metal')
                                                            ->options(['10k' => '10k Gold', '14k' => '14k Gold', '18k' => '18k Gold', 'Platinum' => 'Platinum', 'Silver' => 'Sterling Silver', 'Other' => 'Other'])
                                                            ->placeholder('Select metal...')->nullable()->columnSpan(3),

                                                        CustomDatePicker::make('date_required')
                                                            ->label('Due Date')->displayFormat('M d, Y')->nullable()->columnSpan(3),

                                                        Select::make('send_to')
                                                            ->label('Send To')
                                                            ->options(function () {
                                                                $locations = \App\Models\InventorySetting::where('key', 'repair_locations')->first()?->value ?? [];
                                                                return collect($locations)->mapWithKeys(fn($l) => [$l => $l])->toArray();
                                                            })
                                                            ->createOptionForm([
                                                                Forms\Components\TextInput::make('name')->label('New Location Name')->required(),
                                                            ])
                                                            ->createOptionUsing(function (array $data) {
                                                                $setting = \App\Models\InventorySetting::firstOrCreate(['key' => 'repair_locations'], ['value' => []]);
                                                                $current = $setting->value ?? [];
                                                                $current[] = $data['name'];
                                                                $setting->update(['value' => array_values(array_unique($current))]);
                                                                return $data['name'];
                                                            })
                                                            ->searchable()->native(false)
                                                            ->placeholder('Location...')->columnSpan(2),
                                                    ]),

                                                    // Resize fields
                                                    Grid::make(2)->schema([
                                                        TextInput::make('current_size')->label('Current Size')->placeholder('e.g. 7')->nullable(),
                                                        TextInput::make('target_size')->label('Target Size')->placeholder('e.g. 8.5')->nullable(),
                                                    ])->visible(fn(Forms\Get $get) => $get('job_type') === 'Resize'),

                                                    // Bench notes
                                                    Textarea::make('job_instructions')
                                                        ->label('Bench Notes / Instructions')
                                                        ->placeholder('Specific instructions for the bench jeweler...')
                                                        ->columnSpanFull()->rows(2),

                                                    // ── PRICING ROW ─────────────────────────────────
                                                    Placeholder::make('pricing_divider')->label('')->hiddenLabel()
                                                        ->content(new HtmlString("
                                                            <div style='border-top:1.5px dashed #e2e8f0;margin:8px 0 4px;position:relative;'>
                                                                <span style='position:absolute;top:-9px;left:12px;background:#fff;padding:0 8px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;'>Pricing</span>
                                                            </div>
                                                        "))->columnSpanFull(),

                                                    Grid::make(3)->schema([
                                                        TextInput::make('estimated_cost')
                                                            ->label('Quoted to Customer')
                                                            ->numeric()->prefix('$')->default(0)->nullable()
                                                            ->extraInputAttributes(['style' => 'background:#fffbeb;border-color:#f59e0b;font-weight:700;font-size:1rem;'])
                                                            ->helperText('What you told the customer'),

                                                        TextInput::make('final_cost')
                                                            ->label('Final Charged')
                                                            ->numeric()->prefix('$')->nullable()
                                                            ->extraInputAttributes(['style' => 'background:#f0fdf4;border-color:#22c55e;font-weight:700;font-size:1rem;'])
                                                            ->helperText('Fill when service is complete'),

                                                        Placeholder::make('service_status')
                                                            ->label('Status')
                                                            ->live()
                                                            ->content(function (Forms\Get $get) {
                                                                $est   = floatval($get('estimated_cost') ?? 0);
                                                                $final = $get('final_cost');
                                                                $isWarranty = $get('../../is_warranty');

                                                                if ($isWarranty) {
                                                                    return new HtmlString("<span style='display:inline-flex;align-items:center;gap:5px;background:#dcfce7;color:#166534;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:700;border:1px solid #bbf7d0;'>🛡️ Warranty — No Charge</span>");
                                                                }
                                                                if ($final === null || $final === '') {
                                                                    if ($est > 0) {
                                                                        return new HtmlString("<span style='display:inline-flex;align-items:center;gap:5px;background:#fffbeb;color:#b45309;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:700;border:1px solid #fde68a;'>⏳ Quoted \$" . number_format($est, 2) . "</span>");
                                                                    }
                                                                    return new HtmlString("<span style='display:inline-flex;align-items:center;gap:5px;background:#f1f5f9;color:#64748b;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:700;border:1px solid #e2e8f0;'>⏳ Pending Quote</span>");
                                                                }
                                                                $f = floatval($final);
                                                                if ($f > 0) {
                                                                    return new HtmlString("<span style='display:inline-flex;align-items:center;gap:5px;background:#dcfce7;color:#166534;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:700;border:1px solid #bbf7d0;'>✅ \$" . number_format($f, 2) . " charged</span>");
                                                                }
                                                                return new HtmlString("<span style='display:inline-flex;align-items:center;gap:5px;background:#f0fdf4;color:#16a34a;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:700;border:1px solid #bbf7d0;'>✅ No Charge</span>");
                                                            }),
                                                    ])->columnSpanFull(),
                                                ])
                                                ->columnSpanFull(),

                                            // ── PER-ITEM TOTAL SUMMARY ────────────────────
                                            Placeholder::make('item_total_summary')
                                                ->label('')->hiddenLabel()->live()
                                                ->content(function (Forms\Get $get) {
                                                    $services = $get('services') ?? [];
                                                    $totalEst   = 0;
                                                    $totalFinal = 0;
                                                    $anyFinal   = false;

                                                    foreach ($services as $svc) {
                                                        $totalEst += floatval($svc['estimated_cost'] ?? 0);
                                                        if (isset($svc['final_cost']) && $svc['final_cost'] !== '') {
                                                            $totalFinal += floatval($svc['final_cost']);
                                                            $anyFinal = true;
                                                        }
                                                    }

                                                    if ($totalEst == 0 && !$anyFinal) return '';

                                                    $taxFreeBadge = $get('is_tax_free')
                                                        ? "<span style='background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:99px;font-size:9px;font-weight:800;margin-left:8px;'>TAX FREE</span>"
                                                        : '';

                                                    $html = "<div style='background:linear-gradient(135deg,#0f172a,#1e3a5f);border-radius:12px;padding:14px 18px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;'>";
                                                    $html .= "<div style='font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:rgba(255,255,255,.5);'>Item Total{$taxFreeBadge}</div>";
                                                    $html .= "<div style='display:flex;gap:20px;align-items:center;'>";

                                                    if ($totalEst > 0) {
                                                        $html .= "<div style='text-align:center;'><div style='font-size:9px;color:rgba(255,255,255,.4);font-weight:600;text-transform:uppercase;'>Quoted</div><div style='font-size:1.4rem;font-weight:900;color:#fcd34d;'>$" . number_format($totalEst, 2) . "</div></div>";
                                                    }
                                                    if ($anyFinal) {
                                                        $html .= "<div style='text-align:center;'><div style='font-size:9px;color:rgba(255,255,255,.4);font-weight:600;text-transform:uppercase;'>Final</div><div style='font-size:1.4rem;font-weight:900;color:#6ee7b7;'>$" . number_format($totalFinal, 2) . "</div></div>";
                                                    }

                                                    $html .= "</div></div>";
                                                    return new HtmlString($html);
                                                })
                                                ->columnSpanFull(),
                                        ]),
                                ])
                                ->defaultItems(1)
                                ->addActionLabel('+ Add Another Jewelry Item')
                                ->itemLabel(fn(array $state): ?string => $state['item_description'] ?? 'New Item')
                                ->collapsible()->cloneable()->reorderable(true)
                                ->live()
                                ->afterStateUpdated(fn(Forms\Get $get, Forms\Set $set) => self::updateRepairTotals($get, $set)),
                        ]),
                ]),

                // ── RIGHT ─────────────────────────────────────────────
                Group::make()->columnSpan(['lg' => 4])->schema([

                    Section::make('Staff Assignment')
                        ->description('Who is handling this ticket?')
                        ->icon('heroicon-o-identification')
                        ->schema([
                            Hidden::make('sales_person_id'),
                            Select::make('sales_person_list')
                                ->label('Sales Staff')->multiple()->searchable()->preload()
                                ->options(\App\Models\User::pluck('name', 'id'))
                                ->default(function () {
                                    $activeName = Session::get('active_staff_name');
                                    if ($activeName) {
                                        $user = \App\Models\User::where('name', 'LIKE', "%{$activeName}%")->first();
                                        if ($user) return [$user->id];
                                    }
                                    return auth()->id() ? [auth()->id()] : [];
                                })
                                ->required()->live()
                                ->afterStateUpdated(fn($state, Forms\Set $set) => $set('sales_person_id', !empty($state) ? (int)$state[0] : null)),
                        ]),

                    Section::make('Repair Tracking')
                        ->description('Drop-off, pickup, and repair location details.')
                        ->icon('heroicon-o-map-pin')->collapsible()
                        ->schema([
                            Grid::make(2)->schema([
                                Select::make('dropped_by')->label('Dropped By')
                                    ->options(fn() => \App\Models\User::pluck('name', 'name')->toArray())
                                    ->searchable()->preload(),
                                CustomDatePicker::make('date_dropped')->label('Date Dropped')->displayFormat('M d, Y'),
                            ]),
                            Grid::make(2)->schema([
                                Select::make('picked_up_by')->label('Picked Up By')
                                    ->options(fn() => \App\Models\User::pluck('name', 'name')->toArray())
                                    ->searchable()->preload(),
                                CustomDatePicker::make('date_picked_up')->label('Date Picked/Received')->displayFormat('M d, Y'),
                            ]),
                            Grid::make(2)->schema([
                                GoogleAutocomplete::make('repair_location_search')
                                    ->label('Repair Location')
                                    ->autocompletePlaceholder('Search address or place...')
                                    ->countries(['US'])->columnSpanFull()
                                    ->withFields([
                                        TextInput::make('repair_location')
                                            ->label('Location Name / Address')
                                            ->placeholder('e.g. Javier Workshop, Texas Vendor')
                                            ->extraInputAttributes(['data-google-field' => '{establishment} {route}']),
                                    ]),
                                CustomDatePicker::make('customer_pickup_date')->label('Customer Pickup Date')->displayFormat('M d, Y'),
                            ]),
                            Textarea::make('repair_notes')->label('Notes')->rows(2)
                                ->placeholder('e.g. Should be sent to Texas, Javier could not do it...'),
                        ]),

                    // ── TICKET SUMMARY (right rail) ───────────────────
                    Section::make('Ticket Summary')
                        ->icon('heroicon-o-calculator')
                        ->schema([
                            Placeholder::make('grand_total_summary')->label('')->hiddenLabel()->live()
                                ->content(function (Forms\Get $get) {
                                    $items = $get('items') ?? [];
                                    $grandEst   = 0;
                                    $grandFinal = 0;
                                    $anyFinal   = false;
                                    $rows = '';

                                    $itemNumber = 0;
                                    foreach ($items as $item) {
                                        $itemNumber++;
                                        $desc     = Str::limit($item['item_description'] ?? ('Item ' . $itemNumber), 28);
                                        $services = $item['services'] ?? [];
                                        $itemEst  = 0;
                                        $itemFin  = 0;
                                        $hasF     = false;

                                        foreach ($services as $svc) {
                                            $itemEst += floatval($svc['estimated_cost'] ?? 0);
                                            if (isset($svc['final_cost']) && $svc['final_cost'] !== '') {
                                                $itemFin += floatval($svc['final_cost']);
                                                $hasF = true;
                                                $anyFinal = true;
                                            }
                                        }

                                        $grandEst   += $itemEst;
                                        $grandFinal += $itemFin;

                                        $priceHtml = $hasF
                                            ? "<span style='font-weight:800;color:#059669;'>$" . number_format($itemFin, 2) . "</span>"
                                            : ($itemEst > 0 ? "<span style='font-weight:700;color:#b45309;'>~$" . number_format($itemEst, 2) . "</span>" : "<span style='color:#94a3b8;'>TBD</span>");

                                        $rows .= "<div style='display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid #f1f5f9;font-size:12px;'>
                                            <span style='color:#374151;'>" . e($desc) . "</span>
                                            {$priceHtml}
                                        </div>";
                                    }

                                    if (!$rows) {
                                        return new HtmlString("<div style='text-align:center;color:#94a3b8;font-size:12px;padding:12px;'>No items added yet</div>");
                                    }

                                    $totalColor  = $anyFinal ? '#059669' : '#b45309';
                                    $totalLabel  = $anyFinal ? 'Final Total' : 'Quoted Total';
                                    $totalAmount = $anyFinal ? $grandFinal : $grandEst;

                                    return new HtmlString("
                                        <div>{$rows}</div>
                                        <div style='display:flex;justify-content:space-between;align-items:center;margin-top:10px;padding-top:10px;border-top:2px solid #0f172a;'>
                                            <span style='font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:#0f172a;'>{$totalLabel}</span>
                                            <span style='font-size:1.5rem;font-weight:900;color:{$totalColor};'>$" . number_format($totalAmount, 2) . "</span>
                                        </div>
                                    ");
                                }),
                        ]),

                    // 🚀 NEW — Payment & Status, mirrors SaleResource's section exactly:
                    // split toggle, method select, amount received w/ "Collect Remaining
                    // Balance", change given, split payments repeater w/ "Fill Remaining".
                    Section::make('Payment & Status')
                        ->icon('heroicon-o-banknotes')
                        ->schema([

                            Toggle::make('is_split_payment')
                                ->label('Enable Split Payment')
                                ->onColor('warning')
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                    if ($state) {
                                        $existingAmount = floatval($get('amount_paid') ?? 0);
                                        $existingMethod = $get('payment_method') ?? 'CASH';
                                        $currentSplits  = $get('split_payments') ?? [];
                                        if ($existingAmount > 0 && empty($currentSplits)) {
                                            $set('split_payments', [
                                                (string) Str::uuid() => ['method' => $existingMethod, 'amount' => $existingAmount],
                                            ]);
                                        }
                                    } else {
                                        $currentSplits = $get('split_payments') ?? [];
                                        if (!empty($currentSplits)) {
                                            $totalSplit  = collect($currentSplits)->sum(fn($p) => (float) ($p['amount'] ?? 0));
                                            $firstMethod = collect($currentSplits)->first()['method'] ?? 'CASH';
                                            $set('amount_paid', number_format($totalSplit, 2, '.', ''));
                                            $set('payment_method', $firstMethod);
                                        }
                                    }
                                    self::updateRepairTotals($get, $set);
                                })
                                ->columnSpanFull(),

                            Select::make('payment_method')
                                ->label('Payment Method')
                                ->options(fn() => \App\Filament\Resources\SaleResource::getPaymentOptions())
                                ->placeholder('Select Payment Method')
                                ->required(fn(Forms\Get $get) => !$get('is_split_payment'))
                                ->visible(fn(Forms\Get $get) => !$get('is_split_payment'))
                                ->live()
                                ->afterStateUpdated(fn(Forms\Get $get, Forms\Set $set) => self::updateRepairTotals($get, $set)),

                            Placeholder::make('display_total_due')
                                ->label('Total Amount to Collect')
                                ->visible(fn(Forms\Get $get) => !$get('is_split_payment'))
                                ->live()
                                ->content(function (Forms\Get $get, ?Repair $record) {
                                    $total     = floatval($get('repair_total') ?? 0);
                                    $dbPaid    = $record ? \App\Models\Payment::where('repair_id', $record->id)->sum('amount') : 0;
                                    $remaining = max(0, $total - $dbPaid);
                                    $method    = strtoupper($get('payment_method') ?? '');
                                    $badge     = $method
                                        ? "<span style='background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;border-radius:99px;padding:2px 10px;font-size:11px;font-weight:700;margin-left:8px;'>{$method}</span>"
                                        : '';
                                    $note = $dbPaid > 0
                                        ? "<div style='font-size:11px;color:#64748b;margin-top:4px;'>Already paid: <strong style='color:#16a34a;'>\$" . number_format($dbPaid, 2) . "</strong> &nbsp;·&nbsp; Repair total: \$" . number_format($total, 2) . "</div>"
                                        : '';
                                    return new HtmlString("
                                        <div style='background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:12px 16px;'>
                                            <div style='display:flex;align-items:center;'>
                                                <span style='font-size:1.75rem;font-weight:900;color:#0284c7;'>\$" . number_format($remaining, 2) . "</span>
                                                {$badge}
                                            </div>
                                            {$note}
                                        </div>
                                    ");
                                }),

                            TextInput::make('amount_paid')
                                ->label('Amount Received')
                                ->numeric()->prefix('$')->default(0)
                                ->live(onBlur: true)
                                ->visible(fn(Forms\Get $get) => !$get('is_split_payment'))
                                ->afterStateUpdated(fn(Forms\Get $get, Forms\Set $set) => self::updateRepairTotals($get, $set))
                                ->helperText(function (Forms\Get $get, ?Repair $record) {
                                    if (!$record) return 'What the customer is paying right now';
                                    $dbPaid    = \App\Models\Payment::where('repair_id', $record->id)->sum('amount');
                                    $total     = floatval($get('repair_total') ?? 0);
                                    $remaining = max(0, $total - $dbPaid);
                                    $color     = $remaining <= 0 ? '#16a34a' : '#dc2626';
                                    $label     = $remaining <= 0 ? '✅ $0.00' : '$' . number_format($remaining, 2);
                                    return new HtmlString("<strong style='color:{$color};'>Remaining Balance Due: {$label}</strong>");
                                })
                                ->hintAction(
                                    FormAction::make('fill_full_amount')
                                        ->label('Collect Remaining Balance')
                                        ->icon('heroicon-o-banknotes')
                                        ->color('success')
                                        ->action(function (Forms\Get $get, Forms\Set $set, ?Repair $record) {
                                            $total     = floatval($get('repair_total') ?? 0);
                                            $dbPaid    = $record ? \App\Models\Payment::where('repair_id', $record->id)->sum('amount') : 0;
                                            $remaining = max(0, round($total - $dbPaid, 2));
                                            $set('amount_paid', number_format($remaining, 2, '.', ''));
                                            self::updateRepairTotals($get, $set);
                                        })
                                ),

                            Placeholder::make('change_given_display')
                                ->label('Change Given')
                                ->live()
                                ->visible(fn(Forms\Get $get) => !$get('is_split_payment'))
                                ->content(function (Forms\Get $get) {
                                    $change = max(0, floatval($get('amount_paid') ?? 0) - floatval($get('repair_total') ?? 0));
                                    if ($change > 0.009) {
                                        return new HtmlString("<span style='font-size:1.2rem;font-weight:900;color:#2563eb;'>\$" . number_format($change, 2) . "</span>");
                                    }
                                    return new HtmlString("<span style='color:#94a3b8;'>$0.00</span>");
                                }),

                            Repeater::make('split_payments')
                                ->label('Payment Breakdown')
                                ->dehydrated(false)
                                ->schema([
                                    Grid::make(2)->schema([
                                        Select::make('method')
                                            ->options(fn() => \App\Filament\Resources\SaleResource::getPaymentOptions())
                                            ->required()
                                            ->live()
                                            // 🚀 FIX — recalc directly via the top-level Livewire data array,
                                            // same pattern SaleResource uses for its nested discount fields,
                                            // instead of relying solely on the parent Repeater's afterStateUpdated
                                            // to propagate up (which can lag on nested/blur updates).
                                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, \Filament\Forms\Contracts\HasForms $livewire) {
                                                self::updateRepairTotals(
                                                    fn($p) => data_get($livewire->data, $p),
                                                    fn($p, $v) => data_set($livewire->data, $p, $v)
                                                );
                                            }),
                                        TextInput::make('amount')
                                            ->numeric()->prefix('$')->required()
                                            ->live(onBlur: true)
                                            // 🚀 FIX — same direct recalculation on the amount field itself
                                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, \Filament\Forms\Contracts\HasForms $livewire) {
                                                self::updateRepairTotals(
                                                    fn($p) => data_get($livewire->data, $p),
                                                    fn($p, $v) => data_set($livewire->data, $p, $v)
                                                );
                                            })
                                            ->hintAction(
                                                FormAction::make('fill_remaining')
                                                    ->label('Fill Remaining')
                                                    ->icon('heroicon-o-banknotes')
                                                    ->color('success')
                                                    ->visible(fn(Forms\Get $get) => floatval($get('amount') ?? 0) == 0)
                                                    ->action(function (Forms\Get $get, Forms\Set $set) {
                                                        $total = floatval($get('../../repair_total') ?? 0);
                                                        $sum   = collect($get('../../split_payments') ?? [])->sum(fn($p) => floatval($p['amount'] ?? 0));
                                                        $set('amount', number_format(max(0, $total - $sum), 2, '.', ''));
                                                        self::updateRepairTotals($get, $set);
                                                    })
                                            ),
                                    ]),
                                ])
                                ->visible(fn(Forms\Get $get) => $get('is_split_payment'))
                                ->addActionLabel('+ Add Payment Method')
                                ->reorderable(false)
                                ->defaultItems(0)
                                ->live()
                                ->afterStateUpdated(fn(Forms\Get $get, Forms\Set $set) => self::updateRepairTotals($get, $set))
                                ->columnSpanFull(),

                            // 🚀 NEW — standalone "+ Collect Remaining Balance" button, same as
                            // SaleResource's smart_collect_remaining, sitting outside the repeater
                            // so it always works regardless of nested-field propagation timing.
                            Forms\Components\Actions::make([
                                FormAction::make('repair_smart_collect_remaining')
                                    ->label('+ Collect Remaining Balance')
                                    ->icon('heroicon-o-banknotes')
                                    ->color('success')
                                    ->outlined()
                                    ->visible(function (Forms\Get $get) {
                                        $total = floatval($get('repair_total') ?? 0);
                                        $sum   = collect($get('split_payments') ?? [])->sum(fn($p) => floatval($p['amount'] ?? 0));
                                        return round($total - $sum, 2) > 0.01;
                                    })
                                    ->action(function (Forms\Get $get, Forms\Set $set) {
                                        $existingSplits = $get('split_payments') ?? [];
                                        $currentSum     = collect($existingSplits)->sum(fn($p) => (float) ($p['amount'] ?? 0));
                                        $total          = floatval($get('repair_total') ?? 0);
                                        $remaining      = max(0, round($total - $currentSum, 2));

                                        if ($remaining <= 0.01) return;

                                        $defaultMethod = collect($existingSplits)
                                            ->filter(fn($p) => !empty($p['method']))
                                            ->pluck('method')
                                            ->first() ?? $get('payment_method') ?? 'CASH';

                                        $existingSplits[(string) Str::uuid()] = [
                                            'method' => $defaultMethod,
                                            'amount' => number_format($remaining, 2, '.', ''),
                                        ];

                                        $set('split_payments', $existingSplits);
                                        self::updateRepairTotals($get, $set);
                                    }),
                            ])->visible(fn(Forms\Get $get) => $get('is_split_payment')),

                            Placeholder::make('split_remaining')
                                ->label('Remaining Balance')
                                ->live()
                                ->visible(fn(Forms\Get $get) => $get('is_split_payment'))
                                ->content(function (Forms\Get $get) {
                                    $total = floatval($get('repair_total') ?? 0);
                                    $sum   = collect($get('split_payments') ?? [])->sum(fn($p) => floatval($p['amount'] ?? 0));
                                    $rem   = $total - $sum;
                                    $color = abs($rem) < 0.009 ? '#16a34a' : '#0284c7';
                                    return new HtmlString("<span style='font-size:1.1rem;font-weight:900;color:{$color};'>\$" . number_format(max(0, $rem), 2) . "</span>");
                                }),

                            Hidden::make('repair_subtotal'),
                            Hidden::make('repair_tax'),
                            Hidden::make('repair_total')
                                ->afterStateHydrated(fn(Forms\Get $get, Forms\Set $set) => self::updateRepairTotals($get, $set)),
                            Hidden::make('balance_due'),
                            Hidden::make('change_given')->dehydrated(false),
                        ]),

                    Section::make('Customer Contact')
                        ->description('Track whether the customer has been reached')
                        ->icon('heroicon-o-phone')
                        ->schema([
                            Grid::make(2)->schema([
                                Toggle::make('customer_called')
                                    ->label('📞 Called — Customer Answered')
                                    ->helperText('Check if customer picked up the phone')
                                    ->onColor('success')->inline(false)->live(),

                                Toggle::make('customer_texted')
                                    ->label('💬 Texted — No Answer')
                                    ->helperText('Check if you had to send a text instead')
                                    ->onColor('info')->inline(false)->live(),
                            ]),
                        ]),

                    Section::make('Workflow')
                        ->description('Printing and Notifications')
                        ->icon('heroicon-o-printer')
                        ->schema([
                            Toggle::make('auto_print')
                                ->label('Print Job Packet after saving?')
                                ->default(true)->dehydrated(false)->inline(false)->live(),
                        ]),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->columnToggleFormColumns(2)
            ->headerActions([
                // 🚀 NEW — single click expands/collapses all the secondary
                // tracking columns (Dropped, Drop By, Pick By, Picked, Ready?,
                // Location, Pickup) at once, instead of toggling them one by
                // one through the column-picker dropdown. State lives in
                // session so it persists across page reloads for this user.
            Tables\Actions\Action::make('toggle_expand_columns')
                    ->label(fn() => Session::get('repair_columns_expanded', false) ? '← Collapse Columns' : 'Expand Columns →')
                    ->icon(fn() => Session::get('repair_columns_expanded', false) ? 'heroicon-o-chevron-double-left' : 'heroicon-o-chevron-double-right')
                    ->color('danger')
                    ->extraAttributes([
                        'style' => 'font-weight:800;box-shadow:0 2px 8px rgba(0,0,0,0.15);',
                    ])
                    ->action(function () {
                        Session::put('repair_columns_expanded', ! Session::get('repair_columns_expanded', false));
                    }),
            ])
            ->modifyQueryUsing(function ($query) {
                $query->with(['customer', 'sale']);
                $keyword  = request('keyword');
                $customer = request('customer');
                $staff    = request('staff');
                $status   = request('status');
                $location = request('location');
                $from     = request('from');
                $to       = request('to');

                if ($keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where('repair_no', 'like', "%{$keyword}%")
                            ->orWhere('repair_notes', 'like', "%{$keyword}%")
                            ->orWhereRaw("JSON_SEARCH(items,'all',?,null,'\$[*].item_description') IS NOT NULL", ["%{$keyword}%"])
                            ->orWhereRaw("JSON_SEARCH(items,'all',?,null,'\$[*].reported_issue') IS NOT NULL", ["%{$keyword}%"]);
                    });
                }
                if ($customer) {
                    $query->whereHas(
                        'customer',
                        fn($q) =>
                        $q->where('name', 'like', "%{$customer}%")->orWhere('last_name', 'like', "%{$customer}%")
                            ->orWhereRaw("CONCAT(name,' ',last_name) LIKE ?", ["%{$customer}%"])->orWhere('phone', 'like', "%{$customer}%")
                    );
                }
                if ($staff) {
                    $query->where(
                        fn($q) =>
                        $q->whereHas('salesPerson', fn($sq) => $sq->where('name', 'like', "%{$staff}%"))
                            ->orWhere('sales_person_list', 'like', "%{$staff}%")
                    );
                }
                if ($status)   $query->where('status', $status);
                if ($location) $query->where('repair_location', $location);
                if ($from) {
                    try {
                        $query->whereDate('created_at', '>=', (new \Carbon\Carbon($from))->format('Y-m-d'));
                    } catch (\Exception $e) {
                    }
                }
                if ($to) {
                    try {
                        $query->whereDate('created_at', '<=', (new \Carbon\Carbon($to))->format('Y-m-d'));
                    } catch (\Exception $e) {
                    }
                }

                return $query;
            })
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('DATE')->date('m/d/y')->sortable()->size('sm')->grow(false)->toggleable(),

                Tables\Columns\TextColumn::make('repair_no')
                    ->label('JOB #')->searchable()
                    ->weight('bold')->copyable()->grow(false)->toggleable(),

                Tables\Columns\TextColumn::make('customer.last_name')
                    ->label('CUSTOMER')->searchable(['last_name', 'name'])
                    ->formatStateUsing(fn($record) => strtoupper($record->customer?->last_name ?? 'WALK-IN'))
                    ->weight('bold')->grow(false)->toggleable(),

                Tables\Columns\TextColumn::make('salesPerson.name')
                    ->label('SALES STAFF')->html()
                    ->getStateUsing(function ($record) {
                        $list = $record->sales_person_list;
                        if (is_string($list)) {
                            $decoded = json_decode($list, true);
                            $list = is_array($decoded) ? $decoded : [$list];
                        }
                        if (empty($list) || !is_array($list)) return $record->salesPerson?->name ?? '—';
                        $firstItem = $list[0] ?? null;
                        if (is_numeric($firstItem)) {
                            $names = \App\Models\User::whereIn('id', $list)->pluck('name')->toArray();
                            return !empty($names) ? implode('||', $names) : ($record->salesPerson?->name ?? '—');
                        }
                        $names = array_filter($list, fn($n) => !empty(trim($n ?? '')));
                        return !empty($names) ? implode('||', $names) : ($record->salesPerson?->name ?? '—');
                    })
                    ->formatStateUsing(function ($state) {
                        if (!$state || $state === '—') return '<span style="color:#9ca3af;font-size:11px;">—</span>';
                        return collect(explode('||', $state))->map(fn($name) => self::staffBadge(trim($name)))->implode(' ');
                    })
                    ->grow(false)->toggleable(),

                Tables\Columns\TextColumn::make('date_dropped')
                    ->label('DROPPED')->date('m/d/y')->placeholder('—')->size('sm')->grow(false)
                    ->visible(fn() => Session::get('repair_columns_expanded', false)),

                Tables\Columns\SelectColumn::make('dropped_by')->label('DROP BY')
                    ->options(fn() => \App\Models\User::pluck('name', 'name')->toArray())
                    ->selectablePlaceholder(true)->placeholder('—')->searchable()->grow(false)
                    ->extraAttributes(['style' => 'min-width:110px;'])
                    ->visible(fn() => Session::get('repair_columns_expanded', false)),

                Tables\Columns\SelectColumn::make('picked_up_by')->label('PICK BY')
                    ->options(fn() => \App\Models\User::pluck('name', 'name')->toArray())
                    ->selectablePlaceholder(true)->placeholder('—')->searchable()->grow(false)
                    ->extraAttributes(['style' => 'min-width:110px;'])
                    ->visible(fn() => Session::get('repair_columns_expanded', false)),

                Tables\Columns\TextColumn::make('date_picked_up')
                    ->label('PICKED')->date('m/d/y')->placeholder('—')->size('sm')->grow(false)
                    ->visible(fn() => Session::get('repair_columns_expanded', false)),

                Tables\Columns\SelectColumn::make('status')->label('STATUS')
                    ->options(['received' => 'RCVD', 'in_progress' => 'IN PROG', 'ready' => 'DONE', 'delivered' => 'DELIVERED'])
                    ->selectablePlaceholder(false)->grow(false)
                    ->extraAttributes(fn($record): array => match ($record->status) {
                        'received'    => ['style' => 'background:#F3F4F6;color:#374151;font-weight:700;border-radius:4px;min-width:90px;'],
                        'in_progress' => ['style' => 'background:#FEE2E2;color:#B91C1C;font-weight:700;border-radius:4px;min-width:90px;'],
                        'ready'       => ['style' => 'background:#DCFCE7;color:#15803D;font-weight:700;border-radius:4px;min-width:90px;'],
                        'delivered'   => ['style' => 'background:#DBEAFE;color:#1D4ED8;font-weight:700;border-radius:4px;min-width:90px;'],
                        default       => ['style' => 'min-width:90px;'],
                    })->toggleable(),

                Tables\Columns\ToggleColumn::make('is_ready_toggle')->label('READY?')
                    ->getStateUsing(fn($record) => in_array($record->status, ['ready', 'delivered']))
                    ->onColor('success')->offColor('gray')
                    ->updateStateUsing(fn($record, $state) => $record->update(['status' => $state ? 'ready' : 'received']))
                    ->grow(false)
                    ->visible(fn() => Session::get('repair_columns_expanded', false)),

                Tables\Columns\SelectColumn::make('repair_location')->label('LOCATION')
                    ->options(fn() => \App\Models\Repair::query()->whereNotNull('repair_location')->where('repair_location', '!=', '')->distinct()->pluck('repair_location', 'repair_location')->toArray())
                    ->selectablePlaceholder(true)->placeholder('—')->searchable()->grow(false)
                    ->extraAttributes(['style' => 'min-width:150px;'])
                    ->visible(fn() => Session::get('repair_columns_expanded', false)),

                Tables\Columns\TextColumn::make('customer_pickup_date')->label('PICKUP')->placeholder('—')
                    ->formatStateUsing(
                        fn($state) => $state
                            ? new HtmlString("<span style='background:#dcfce7;color:#166534;padding:2px 6px;border-radius:4px;font-weight:700;font-size:11px;white-space:nowrap;'>" . (\Carbon\Carbon::parse($state)->format('m/d/y')) . "</span>")
                            : '<span style="color:#9ca3af;">—</span>'
                    )->grow(false)
                    ->visible(fn() => Session::get('repair_columns_expanded', false)),

                Tables\Columns\TextColumn::make('origin')->label('ORIGIN')->html()
                    ->getStateUsing(function ($record) {
                        if (empty($record->sale_id)) return 'DIRECT';
                        $saleData  = \Illuminate\Support\Facades\DB::table('sales')->where('id', $record->sale_id)->select('notes')->first();
                        $saleNotes = strtolower($saleData?->notes ?? '');
                        $hasExchangeItem = \Illuminate\Support\Facades\DB::table('sale_items')->where('sale_id', $record->sale_id)
                            ->where(fn($q) => $q->where('custom_description', 'like', '%exchange%')->orWhere('custom_description', 'like', '%return%'))->exists();
                        if ($hasExchangeItem || str_contains($saleNotes, 'exchange')) return 'EXCHANGE';
                        return 'POS SALE';
                    })
                    ->formatStateUsing(function (string $state, $record) {
                        $badgeClass = match ($state) {
                            'POS SALE' => 'bg-blue-50 text-blue-700 border-blue-200',
                            'EXCHANGE' => 'bg-amber-50 text-amber-700 border-amber-200',
                            default    => 'bg-gray-50 text-gray-700 border-gray-200',
                        };
                        $badgeLabel = match ($state) {
                            'POS SALE' => '🛒 POS SALE',
                            'EXCHANGE' => '🔄 EXCHANGE',
                            default => '🛠️ DIRECT'
                        };
                        $html = "<div><span class='inline-flex items-center gap-x-1 rounded-md px-2 py-0.5 text-xs font-medium border {$badgeClass}'>{$badgeLabel}</span></div>";
                        if (!empty($record->sale_id)) {
                            $inv = $record->sale?->invoice_number ?? \Illuminate\Support\Facades\DB::table('sales')->where('id', $record->sale_id)->value('invoice_number');
                            if ($inv) $html .= "<div class='text-[11px] text-gray-400 font-mono mt-1'>Inv #{$inv}</div>";
                        }
                        return new HtmlString($html);
                    })->grow(false)->toggleable(),

                Tables\Columns\TextColumn::make('sale.invoice_number')->label('SALE')->placeholder('—')
                    ->formatStateUsing(
                        fn($state) => $state
                            ? new HtmlString("<span style='background:#dcfce7;color:#166534;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;'>#{$state}</span>")
                            : '—'
                    )
                    ->url(fn($record) => $record->sale_id ? \App\Filament\Resources\SaleResource::getUrl('edit', ['record' => $record->sale_id]) : null)
                    ->openUrlInNewTab()->grow(false)->toggleable(),

                Tables\Columns\TextColumn::make('repair_notes')->label('NOTES')->limit(25)
                    ->tooltip(fn($record) => $record->repair_notes)
                    ->placeholder('—')->size('sm')->color('warning')->grow(false)->toggleable(),

                Tables\Columns\TextColumn::make('notified_at')->label('NOTIFIED')
                    ->getStateUsing(fn($record) => $record->notified_at ? $record->notified_at->format('m/d/y') : '—')
                    ->icon(fn($record) => $record->notified_at ? 'heroicon-s-check-circle' : 'heroicon-o-clock')
                    ->color(fn($record) => $record->notified_at ? 'success' : 'gray')
                    ->description(fn($record) => !empty($record->repair_history) ? new HtmlString("<span class='text-primary-600 text-[10px] font-bold underline cursor-pointer'>History</span>") : null)
                    ->action(
                        Tables\Actions\Action::make('viewRepairLog')
                            ->modalHeading('Communication History')->modalWidth('lg')->slideOver()
                            ->modalSubmitAction(false)
                            ->form([
                                Forms\Components\Placeholder::make('history_display')->label('')
                                    ->content(function ($record) {
                                        if (empty($record->repair_history)) return 'No history available.';
                                        $html = '<div class="space-y-4">';
                                        foreach (array_reverse($record->repair_history) as $log) {
                                            $date  = \Carbon\Carbon::parse($log['sent_at'])->format('M d, Y h:i A');
                                            $method = strtoupper($log['method'] ?? 'N/A');
                                            $staff  = $log['staff_name'] ?? 'System';
                                            $html .= "<div class='p-4 bg-gray-50 border border-gray-200 rounded-lg shadow-sm'>
                                                <div class='flex justify-between items-center mb-2'>
                                                    <span class='text-xs font-bold text-primary-600 uppercase'>{$method}</span>
                                                    <span class='text-[10px] text-gray-400'>{$date}</span>
                                                </div>
                                                <p class='text-sm text-gray-700 italic leading-relaxed'>\"{$log['message']}\"</p>
                                                <div class='mt-2 text-[10px] text-gray-500'>Sent by: {$staff}</div>
                                            </div>";
                                        }
                                        $html .= '</div>';
                                        return new HtmlString($html);
                                    }),
                            ])
                    )->grow(false)->toggleable(),

                // ── CALL / TEXT contact tracking ──────────────────────────────
                Tables\Columns\ToggleColumn::make('customer_called')
                    ->label('📞 CALL')
                    ->onColor('success')->offColor('gray')
                    ->tooltip(fn($record) => $record->customer_called ? 'Customer answered ✓' : 'Not called yet'),

                Tables\Columns\ToggleColumn::make('customer_texted')
                    ->label('💬 TEXT')
                    ->onColor('info')->offColor('gray')
                    ->tooltip(fn($record) => $record->customer_texted ? 'Customer texted ✓' : 'Not texted yet'),

                // ── QUOTE TOTAL column — sums all services across all items ──
                Tables\Columns\TextColumn::make('estimated_total')->label('QUOTED')->money('USD')
                    ->getStateUsing(
                        fn($record) =>
                        collect($record->items ?? [])->sum(
                            fn($item) =>
                            collect($item['services'] ?? [])->sum(fn($svc) => floatval($svc['estimated_cost'] ?? 0))
                        )
                    )->grow(false)->toggleable(),

                // ── FINAL TOTAL column ────────────────────────────────────────
                Tables\Columns\TextColumn::make('final_total_col')->label('CHARGED')->html()
                    ->getStateUsing(
                        fn($record) =>
                        collect($record->items ?? [])->sum(
                            fn($item) =>
                            collect($item['services'] ?? [])->sum(
                                fn($svc) =>
                                isset($svc['final_cost']) && $svc['final_cost'] !== '' ? floatval($svc['final_cost']) : 0
                            )
                        )
                    )
                    ->formatStateUsing(function ($state) {
                        if ($state <= 0) return '<span style="color:#94a3b8;">—</span>';
                        return new HtmlString("<span style='font-weight:800;color:#059669;'>$" . number_format($state, 2) . "</span>");
                    })->grow(false)->toggleable(),

                // 🚀 NEW — mirrors CustomOrderResource's PAID/BALANCE columns
                Tables\Columns\TextColumn::make('repair_paid')
                    ->label('PAID')
                    ->getStateUsing(fn(Repair $record) => self::calculateRepairTotal($record)['paid'])
                    ->money('USD')->color('info')->grow(false)->toggleable(),

                Tables\Columns\TextColumn::make('repair_balance')
                    ->label('BALANCE')
                    ->getStateUsing(fn(Repair $record) => self::calculateRepairTotal($record)['balance'])
                    ->money('USD')
                    ->color(fn($state) => $state > 0 ? 'danger' : 'success')
                    ->weight('bold')
                    ->grow(false)->toggleable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('notifyDelay')
                        ->label('Delay Notification')->icon('heroicon-o-clock')->color('danger')
                        ->form([
                            Forms\Components\Select::make('notify_method')
                                ->options(['sms' => 'SMS', 'email' => 'Email', 'both' => 'Both'])->default('sms')->required(),
                            Forms\Components\Textarea::make('message')->label('Message Content')
                                ->default(fn($record) => "Hi {$record->customer->name}, we are experiencing a slight delay with your repair #{$record->repair_no}. We appreciate your patience!")
                                ->required(),
                        ])
                        ->action(fn($record, array $data) => self::handleRepairNotification($record, $data['notify_method'], $data['message'])),

                    Tables\Actions\Action::make('markReady')
                        ->label('Ready for Pickup')->icon('heroicon-o-check-circle')->color('success')
                        ->form([
                            Forms\Components\Select::make('notify_method')
                                ->options(['sms' => 'SMS', 'email' => 'Email', 'both' => 'Both', 'none' => 'None'])->default('both')->required(),
                            Forms\Components\Textarea::make('message')->label('Message Content')
                                ->default(fn($record) => "Hi {$record->customer->name}, great news! Your repair #{$record->repair_no} is ready for pickup.")
                                ->visible(fn($get) => $get('notify_method') !== 'none'),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update(['status' => 'ready']);
                            if (($data['notify_method'] ?? 'none') !== 'none')
                                self::handleRepairNotification($record, $data['notify_method'], $data['message']);
                        }),

                    Tables\Actions\Action::make('billRepair')
                        ->label('Bill to POS')->icon('heroicon-o-currency-dollar')->color('success')
                        ->visible(fn(Repair $record) => !$record->sale_id)
                        ->url(fn(Repair $record) => route('filament.admin.resources.sales.create', ['repair_id' => $record->id, 'customer_id' => $record->customer_id])),

                    // 🚀 NEW — Add Deposit, mirrors CustomOrderResource::recordPayment.
                    // Hidden if this repair is already linked to a Sale — in that case
                    // payment collection happens on the Sale side, not here, exactly like
                    // you asked: "if from sales, do transaction from sales."
                    Tables\Actions\Action::make('recordRepairPayment')
                        ->label('Add Deposit')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->visible(function (Repair $record) {
                            // 🚀 Always visible for POS-linked repairs regardless of current
                            // balance, since staff may need to add a charge + payment after
                            // the fact. Direct (non-POS) repairs keep the balance>0 gate.
                            if ($record->status === 'delivered') return false;
                            if ($record->sale_id) return true;
                            return self::calculateRepairTotal($record)['balance'] > 0.01;
                        })
                        ->modalHeading(fn(Repair $record) => "Add Payment — Repair #{$record->repair_no}")
                        ->modalSubmitActionLabel('✓ Record Payment')
                        ->modalWidth('lg')
                        ->form(function (Repair $record) {
                            $calc = self::calculateRepairTotal($record);
                            $payments = \App\Models\Payment::where('repair_id', $record->id)->orderBy('paid_at')->get();

                            $rows = '';
                            if ($payments->isEmpty()) {
                                $rows = "<p style='font-size:11px;color:#9ca3af;font-style:italic;padding:8px 0;'>No payments recorded yet.</p>";
                            } else {
                                $running = 0;
                                foreach ($payments as $p) {
                                    $running += floatval($p->amount);
                                    $runningBal = max(0, $calc['total'] - $running);
                                    $rows .= "<div style='display:flex;justify-content:space-between;align-items:center;font-size:11px;padding:6px 0;border-bottom:1px dashed #e5e7eb;'>
                                        <div>
                                            <span style='color:#374151;font-weight:600;'>" . \Carbon\Carbon::parse($p->paid_at)->format('M d, Y') . "</span>
                                            <span style='display:inline-block;margin-left:8px;background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;border-radius:99px;padding:1px 8px;font-size:10px;font-weight:700;'>" . strtoupper($p->method) . "</span>
                                        </div>
                                        <div style='text-align:right;'>
                                            <span style='color:#10b981;font-weight:700;'>+\$" . number_format($p->amount, 2) . "</span>
                                            <span style='color:#9ca3af;font-size:10px;margin-left:8px;'>bal: \$" . number_format($runningBal, 2) . "</span>
                                        </div>
                                    </div>";
                                }
                            }

                            $balColor = $calc['balance'] <= 0 ? '#10b981' : '#ef4444';
                            $summaryHtml = "
                                <div style='background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:14px;margin-bottom:2px;'>
                                    <div style='font-size:10px;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;'>📋 Payment History</div>
                                    {$rows}
                                    <div style='margin-top:10px;padding-top:10px;border-top:2px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;'>
                                        <div>
                                            <div style='font-size:10px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;'>Repair Total</div>
                                            <div style='font-size:14px;font-weight:700;color:#374151;'>\$" . number_format($calc['total'], 2) . "</div>
                                        </div>
                                        <div style='text-align:center;'>
                                            <div style='font-size:10px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;'>Total Paid</div>
                                            <div style='font-size:14px;font-weight:700;color:#10b981;'>\$" . number_format($calc['paid'], 2) . "</div>
                                        </div>
                                        <div style='text-align:right;'>
                                            <div style='font-size:10px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;'>Remaining</div>
                                            <div style='font-size:20px;font-weight:900;color:{$balColor};'>\$" . number_format($calc['balance'], 2) . "</div>
                                        </div>
                                    </div>
                                </div>
                            ";

                            return [
                                Placeholder::make('payment_history')->label('')->content(new HtmlString($summaryHtml)),
                                TextInput::make('amount')
                                    ->label('Amount to Collect Now')
                                    ->numeric()->required()->prefix('$')
                                    ->default(number_format($calc['balance'], 2, '.', ''))
                                    // 🚀 FIX — hard-cap collection at the actual remaining balance.
                                    // Previously staff could type any amount (e.g. $20,000 against
                                    // a $10,000 repair), silently overpaying with no validation.
                                    ->maxValue($calc['balance'])
                                    ->helperText('Cannot exceed the remaining balance of $' . number_format($calc['balance'], 2, '.', ','))
                                    ->extraInputAttributes(['style' => 'font-size:1.4rem;font-weight:900;height:3rem;border:2px solid #10b981;background:#f0fdf4;color:#15803d;']),
                                Select::make('payment_method')
                                    ->label('Payment Method')
                                    ->options(fn() => \App\Filament\Resources\SaleResource::getPaymentOptions())
                                    ->default('CASH')->required()->native(false),
                            ];
                        })
                        ->action(function (Repair $record, array $data) {
                            \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data) {
                                // 🚀 FIX — if this repair is already linked to a Sale (POS-origin
                                // repair), stamp sale_id on the new Payment too, so it shows up in
                                // that Sale's own payment log / totals, not just the repair's.
                                \App\Models\Payment::create([
                                    'repair_id' => $record->id,
                                    'sale_id'   => $record->sale_id,
                                    'amount'    => round((float) $data['amount'], 2),
                                    'method'    => strtoupper(trim($data['payment_method'])),
                                    'paid_at'   => now(),
                                    'store_id'  => $record->store_id ?? auth()->user()->store_id ?? 1,
                                ]);
                            });

                            $calc = self::calculateRepairTotal($record->fresh());
                            $record->update([
                                'amount_paid' => $calc['paid'],
                                'balance_due' => $calc['balance'],
                            ]);

                            // 🚀 FIX — resync the parent Sale's amount_paid/balance_due immediately
                            // from the DB, exactly like CreateSale/EditSale do after their own
                            // payment loops. Without this, the Sale record kept showing stale
                            // totals even though a new Payment was just linked to it.
                            if ($record->sale_id) {
                                $sale = \App\Models\Sale::find($record->sale_id);
                                if ($sale) {
                                    $totalSalePaid = \App\Models\Payment::where('sale_id', $sale->id)->sum('amount');
                                    $newBalance    = max(0, round(floatval($sale->final_total) - $totalSalePaid, 2));
                                    $sale->update([
                                        'amount_paid' => round($totalSalePaid, 2),
                                        'balance_due' => $newBalance,
                                        'status'      => $newBalance <= 0.01 ? 'completed' : $sale->status,
                                    ]);
                                }
                            }

                            if ($calc['balance'] <= 0.01 && !$record->sale_id) {
                                $sale = self::createSaleFromRepair($record->fresh());
                                Notification::make()
                                    ->title("✅ Fully Paid — Sale #{$sale->invoice_number} Created")
                                    ->body('This repair now shows up in your Sales Report.')
                                    ->success()
                                    ->persistent()
                                    ->send();
                            } else {
                                Notification::make()->title('✅ Payment Recorded')->body('Balance updated successfully.')->success()->send();
                            }
                        }),
                    Tables\Actions\Action::make('printJobPacket')
                        ->label('Print Job Packet')->icon('heroicon-o-printer')->color('info')
                        ->url(fn(Repair $record): string => route('repair.print', $record))->openUrlInNewTab(),
                ])
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function handleRepairNotification(Repair $record, string $method, string $message)
    {
        $history   = $record->repair_history ?? [];
        $history[] = ['sent_at' => now()->toDateTimeString(), 'method' => $method, 'message' => $message, 'staff_name' => auth()->user()->name ?? 'System'];
        $record->update(['last_message' => $message, 'notified_at' => now(), 'repair_history' => $history]);

        if (in_array($method, ['sms', 'both']) && !empty($record->customer->phone)) {
            try {
                $digits         = preg_replace('/[^0-9]/', '', $record->customer->phone);
                $formattedPhone = '+1' . (Str::startsWith($digits, '1') ? substr($digits, 1) : $digits);
                $settings       = \Illuminate\Support\Facades\DB::table('site_settings')->pluck('value', 'key');
                $sns = new SnsClient(['version' => 'latest', 'region' => $settings['aws_sms_default_region'] ?? config('services.sns.region', 'us-east-2'), 'credentials' => ['key' => $settings['aws_sms_access_key_id'] ?? config('services.sns.key'), 'secret' => $settings['aws_sms_secret_access_key'] ?? config('services.sns.secret')]]);
                $sns->publish(['Message' => $message, 'PhoneNumber' => $formattedPhone, 'MessageAttributes' => ['OriginationNumber' => ['DataType' => 'String', 'StringValue' => $settings['aws_sns_sms_from'] ?? config('services.sns.sms_from')]]]);
            } catch (\Exception $e) {
                Notification::make()->title('SMS Error')->body($e->getMessage())->danger()->send();
            }
        }

        if (in_array($method, ['email', 'both']) && !empty($record->customer->email)) {
            try {
                $settings = \Illuminate\Support\Facades\DB::table('site_settings')->pluck('value', 'key');
                config(['services.ses.key' => $settings['aws_access_key_id'] ?? config('services.ses.key'), 'services.ses.secret' => $settings['aws_secret_access_key'] ?? config('services.ses.secret'), 'services.ses.region' => $settings['aws_default_region'] ?? config('services.ses.region')]);
                Mail::raw($message, fn($mail) => $mail->to($record->customer->email)->subject("Update: Jewelry Repair #{$record->repair_no}"));
            } catch (\Exception $e) {
                Notification::make()->title('Email Error')->body('SMTP check failed.')->danger()->send();
            }
        }

        Notification::make()->title('Customer Notified')->success()->send();
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRepairs::route('/'),
            'create' => Pages\CreateRepair::route('/create'),
            'edit'   => Pages\EditRepair::route('/{record}/edit'),
        ];
    }
}
