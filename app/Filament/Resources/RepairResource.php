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

    // ── DYNAMIC STAFF BADGE ──────────────────────────────────────────
    private static function staffBadge(?string $name): string
    {
        if (!$name) return '<span style="color:#9ca3af;font-size:11px;">—</span>';

        $palette = [
            ['#DBEAFE', '#1D4ED8'], // blue
            ['#EDE9FE', '#6D28D9'], // purple
            ['#CCFBF1', '#0F766E'], // teal
            ['#FEF3C7', '#B45309'], // amber
            ['#DCFCE7', '#15803D'], // green
            ['#FCE7F3', '#BE185D'], // pink
            ['#FEE2E2', '#B91C1C'], // red
            ['#F3E8FF', '#7E22CE'], // violet
            ['#CFFAFE', '#0E7490'], // cyan
            ['#FFEDD5', '#C2410C'], // orange
            ['#E0E7FF', '#4338CA'], // indigo
            ['#ECFCCB', '#4D7C0F'], // lime
        ];

        $index       = abs(crc32(strtolower(trim($name)))) % count($palette);
        [$bg, $text] = $palette[$index];
        $short       = strtoupper(substr(trim($name), 0, 6));

        return "<span style='background:{$bg};color:{$text};padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700;letter-spacing:0.5px;white-space:nowrap;display:inline-block;border:1px solid {$text}33;'>{$short}</span>";
    }

    // ── FORM ─────────────────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(12)->schema([

                // ── LEFT: Customer + Items ────────────────────────────
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
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(fn($c) => [
                                                $c->id => "{$c->name} {$c->last_name} | {$c->phone} (#{$c->customer_no})"
                                            ]);
                                    })
                                    ->preload()
                                    // 🚀 THE FIX: Use hintActions (plural) to have both buttons!
                                    ->hintActions([
                                        FormAction::make('view_customer_details')
                                            ->label('View Profile')
                                            ->icon('heroicon-o-user-circle')
                                            ->color('info')
                                            ->visible(fn(Forms\Get $get) => $get('customer_id') !== null)
                                            ->modalHeading('Customer Profile Details')
                                            ->modalSubmitAction(false)
                                            ->modalCancelActionLabel('Close')
                                            ->slideOver()
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
                                                                    <span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-800 mt-1'>
                                                                        Balance: $" . number_format($customer->credit_balance ?? 0, 2) . "
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        "))->columnSpanFull(),
                                                        TextInput::make('p')->label('Phone')->default($customer->phone)->readOnly(),
                                                        TextInput::make('e')->label('Email')->default($customer->email)->readOnly(),
                                                        TextInput::make('addr')->label('Full Address')->default(trim("{$customer->street} {$customer->suburb} {$customer->city} {$customer->state} {$customer->postcode}"))->readOnly()->columnSpanFull(),
                                                    ]),
                                                ];
                                            }),
                                        FormAction::make('Help')
                                            ->icon('heroicon-o-information-circle')
                                            ->tooltip('Click + to add a new customer')
                                    ])
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\Tabs::make('New Customer')->tabs([
                                            Forms\Components\Tabs\Tab::make('Contact')
                                                ->icon('heroicon-o-user')
                                                ->schema([
                                                    Grid::make(2)->schema([
                                                        TextInput::make('name')->label('First Name')->required(),
                                                        TextInput::make('last_name')->label('Last Name'),
                                                    ]),
                                                    Grid::make(2)->schema([
                                                        TextInput::make('phone')
                                                            ->label('Mobile Phone')->tel()->prefix('+1')
                                                            ->mask('(999) 999-9999')->placeholder('(555) 555-5555')
                                                            ->stripCharacters(['(', ')', '-', ' '])
                                                            ->rule('regex:/^[0-9]{10}$/')
                                                            ->afterStateHydrated(function ($component, $state) {
                                                                if ($state && preg_match('/^[0-9]{10}$/', $state)) {
                                                                    $component->state('(' . substr($state, 0, 3) . ') ' . substr($state, 3, 3) . '-' . substr($state, 6));
                                                                }
                                                            }),
                                                        TextInput::make('email')->label('Email')->email(),
                                                    ]),
                                                    Grid::make(2)->schema([
                                                        CustomDatePicker::make('dob')->rule('before_or_equal:today')->label('Birth Date'),
                                                        CustomDatePicker::make('wedding_anniversary')->label('Wedding Date'),
                                                    ]),
                                                    Section::make('Customer Address')
                                                        ->columns(2)->collapsible()
                                                        ->schema([
                                                            GoogleAutocomplete::make('address_search')
                                                                ->label('Search Address')
                                                                ->autocompletePlaceholder('Start typing address...')
                                                                ->countries(['US'])->columnSpanFull()
                                                                ->withFields([
                                                                    TextInput::make('street')->label('Street Address')
                                                                        ->extraInputAttributes(['data-google-field' => '{street_number} {route}']),
                                                                    TextInput::make('address_line_2')->label('Address 2')
                                                                        ->extraInputAttributes(['data-google-field' => 'subpremise']),
                                                                    TextInput::make('city')->label('City')
                                                                        ->extraInputAttributes(['data-google-field' => 'locality', 'data-google-value' => 'short_name']),
                                                                    TextInput::make('state')->label('State')->columnSpan(1)
                                                                        ->extraInputAttributes(['data-google-field' => 'administrative_area_level_1']),
                                                                    TextInput::make('postcode')->label('Zip Code')->columnSpan(1)
                                                                        ->extraInputAttributes(['data-google-field' => 'postal_code']),
                                                                ]),
                                                            Select::make('country')->label('Country')->default('United States')->searchable(),
                                                        ]),
                                                ]),
                                        ]),
                                        Hidden::make('customer_no')->default(fn() => 'CUST-' . strtoupper(Str::random(6))),
                                    ])
                                    ->createOptionUsing(fn(array $data) => \App\Models\Customer::create($data)->id),

                                Select::make('status')
                                    ->options([
                                        'received'    => 'Received',
                                        'in_progress' => 'In Progress',
                                        'ready'       => 'Ready for Pickup',
                                        'delivered'   => 'Delivered',
                                    ])
                                    ->default('received')
                                    ->required(),
                            ]),
                        ]),

                    Section::make('Jewelry Items to Repair')
                        ->description('Add all items brought in by the customer.')
                        ->icon('heroicon-o-sparkles')
                        ->schema([
                            Repeater::make('items')
                                ->label('')
                                ->schema([
                                    Grid::make(2)->schema([
                                        Toggle::make('is_warranty')
                                            ->label('Covered Under Warranty?')
                                            ->helperText('Automatically sets costs to $0.00')
                                            ->onColor('success')
                                            ->inline(false)
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                if ($state) {
                                                    $set('estimated_cost', 0);
                                                    $set('final_cost', 0);
                                                }
                                            }),

                                        Toggle::make('is_from_store_stock')
                                            ->label('Was this bought from our store?')
                                            ->inline(false)
                                            ->live(),
                                    ])->columnSpanFull(),

                                    Select::make('original_product_id')
                                        ->label('Search Store Stock No.')
                                        ->placeholder('Search by stock number (G1234, R1001, N...)')
                                        ->options(function () {
                                            // ✅ Show ALL stock regardless of status — sold, in_stock, on_hold
                                            return \App\Models\ProductItem::query()
                                                ->whereNotNull('barcode')
                                                ->orderBy('barcode')
                                                ->limit(200)
                                                ->get()
                                                ->mapWithKeys(fn($item) => [
                                                    $item->id => "{$item->barcode} — " . \Illuminate\Support\Str::limit($item->custom_description ?? '', 40)
                                                        . " [{$item->status}]"
                                                ]);
                                        })
                                        ->getSearchResultsUsing(function (string $search) {
                                            // ✅ Search by barcode OR description, no status filter
                                            return \App\Models\ProductItem::query()
                                                ->where(function ($q) use ($search) {
                                                    $q->where('barcode', 'like', "%{$search}%")
                                                        ->orWhere('custom_description', 'like', "%{$search}%");
                                                })
                                                ->limit(50)
                                                ->get()
                                                ->mapWithKeys(fn($item) => [
                                                    $item->id => "{$item->barcode} — " . \Illuminate\Support\Str::limit($item->custom_description ?? '', 40)
                                                        . " [{$item->status}]"
                                                ]);
                                        })
                                        ->getOptionLabelUsing(fn($value) => \App\Models\ProductItem::find($value)?->barcode . ' — ' . \App\Models\ProductItem::find($value)?->custom_description)
                                        ->searchable()
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                            if ($item = \App\Models\ProductItem::find($state)) {
                                                $set('item_description', $item->custom_description ?? $item->barcode);
                                            }
                                        })
                                        ->visible(fn(Forms\Get $get) => $get('is_from_store_stock'))
                                        ->columnSpanFull(),
                                    Grid::make(2)->schema([
                                        Textarea::make('item_description')
                                            ->label('Item Name / Description')
                                            ->placeholder('e.g., 14k White Gold Diamond Engagement Ring')
                                            ->required()
                                            ->maxLength(3000)
                                            ->rows(3),

                                        Textarea::make('reported_issue')
                                            ->label('Issue Reported / Instructions')
                                            ->placeholder('e.g., Needs sizing to 6.5, missing side stone...')
                                            ->required()
                                            ->rows(3),
                                    ])->columnSpanFull(),

                                    Grid::make(2)->schema([
                                        TextInput::make('estimated_cost')
                                            ->label('Estimated Cost')
                                            ->numeric()
                                            ->prefix('$')
                                            ->default(0)
                                            ->nullable(),

                                        TextInput::make('final_cost')
                                            ->label('Final Cost')
                                            ->numeric()
                                            ->prefix('$')
                                            ->helperText('Leave blank until repair is finished.'),
                                    ])->columnSpanFull(),
                                ])
                                ->defaultItems(1)
                                ->addActionLabel('+ Add Another Jewelry Item')
                                ->itemLabel(fn(array $state): ?string => $state['item_description'] ?? 'New Item')
                                ->collapsible()
                                ->cloneable()
                                ->reorderable(true),
                        ]),
                ]),

                // ── RIGHT: Staff + Tracking + Workflow ────────────────
                Group::make()->columnSpan(['lg' => 4])->schema([

                    Section::make('Staff Assignment')
                        ->icon('heroicon-o-identification')
                        ->schema([

                            // ✅ NO default() here — mutateFormDataBeforeCreate owns this value.
                            // The hidden field just ensures sales_person_id is included in form data.
                            Hidden::make('sales_person_id'),

                            Select::make('sales_person_list')
                                ->label('Sales Staff')
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->options(\App\Models\User::pluck('name', 'id'))
                                ->default(function () {
                                    // This correctly resolves "Rabin" from the session.
                                    $activeName = \Illuminate\Support\Facades\Session::get('active_staff_name');

                                    if ($activeName) {
                                        $user = \App\Models\User::where('name', 'LIKE', "%{$activeName}%")->first();
                                        if ($user) return [$user->id];
                                    }

                                    return auth()->id() ? [auth()->id()] : [];
                                })
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    // Sync the hidden field when user manually changes selection
                                    $set('sales_person_id', !empty($state) ? (int) $state[0] : null);
                                }),
                        ]),

                    Section::make('Repair Tracking')
                        ->icon('heroicon-o-map-pin')
                        ->collapsible()
                        ->schema([
                            Grid::make(2)->schema([
                                Select::make('dropped_by')
                                    ->label('Dropped By')
                                    ->options(fn() => \App\Models\User::pluck('name', 'name')->toArray())
                                    ->searchable()
                                    ->preload(),
                                CustomDatePicker::make('date_dropped')
                                    ->label('Date Dropped')
                                    ->displayFormat('M d, Y'),
                            ]),

                            Grid::make(2)->schema([
                                Select::make('picked_up_by')
                                    ->label('Picked Up By')
                                    ->options(fn() => \App\Models\User::pluck('name', 'name')->toArray())
                                    ->searchable()
                                    ->preload(),


                                CustomDatePicker::make('date_picked_up')
                                    ->label('Date Picked/Received')
                                    ->displayFormat('M d, Y'),
                            ]),

                            Grid::make(2)->schema([
                                GoogleAutocomplete::make('repair_location_search')
                                    ->label('Repair Location')
                                    ->autocompletePlaceholder('Search address or place...')
                                    ->countries(['US'])
                                    ->columnSpanFull()
                                    ->withFields([
                                        TextInput::make('repair_location')
                                            ->label('Location Name / Address')
                                            ->placeholder('e.g. Javier Workshop, Texas Vendor')
                                            ->extraInputAttributes(['data-google-field' => '{establishment} {route}']),
                                    ]),

                                CustomDatePicker::make('customer_pickup_date')
                                    ->label('Customer Pickup Date')
                                    ->displayFormat('M d, Y'),
                            ]),

                            Textarea::make('repair_notes')
                                ->label('Notes')
                                ->rows(2)
                                ->placeholder('e.g. Should be sent to Texas, Javier could not do it...'),
                        ]),

                    Section::make('Workflow')
                        ->description('Printing and Notifications')
                        ->icon('heroicon-o-printer')
                        ->schema([
                            Toggle::make('auto_print')
                                ->label('Print Job Packet after saving?')
                                ->default(true)
                                ->dehydrated(false)
                                ->inline(false)
                                ->live(),
                        ]),
                ]),
            ]),
        ]);
    }

    // ── TABLE ─────────────────────────────────────────────────────────
  public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([

                Tables\Columns\TextColumn::make('created_at')
                    ->label('DATE')
                    ->date('m/d/y')
                    ->sortable()
                    ->size('sm')
                    ->grow(false),

                Tables\Columns\TextColumn::make('repair_no')
                    ->label('JOB #')
                    ->searchable()
                    ->formatStateUsing(fn($state) => '#' . substr(preg_replace('/[^0-9]/', '', $state), -4))
                    ->weight('bold')
                    ->copyable()
                    ->grow(false),

                Tables\Columns\TextColumn::make('customer.last_name')
                    ->label('CUSTOMER')
                    ->searchable(['last_name', 'name'])
                    ->formatStateUsing(fn($record) => strtoupper($record->customer?->last_name ?? 'WALK-IN'))
                    ->weight('bold')
                    ->grow(false),

                Tables\Columns\TextColumn::make('salesPerson.name')
                    ->label('SALES STAFF')
                    ->html()
                    ->getStateUsing(function ($record) {
                        $list = $record->sales_person_list;
                        if (is_string($list)) {
                            $list = json_decode($list, true);
                        }
                        if (!empty($list) && is_array($list)) {
                            return \App\Models\User::whereIn('id', $list)
                                ->pluck('name')
                                ->implode('||'); 
                        }
                        if ($record->salesPerson) {
                            return $record->salesPerson->name;
                        }
                        return '—';
                    })
                    ->formatStateUsing(function ($state) {
                        if (!$state || $state === '—') {
                            return '<span style="color:#9ca3af;font-size:11px;">—</span>';
                        }
                        $names = explode('||', $state);
                        return collect($names)
                            ->map(fn($name) => self::staffBadge(trim($name)))
                            ->implode(' ');
                    })
                    ->grow(false),

                Tables\Columns\TextColumn::make('date_dropped')
                    ->label('DROPPED')
                    ->date('m/d/y')
                    ->placeholder('—')
                    ->size('sm')
                    ->grow(false),

                // 🚀 THE FIX: Converted to SelectColumn
                Tables\Columns\SelectColumn::make('dropped_by')
                    ->label('DROP BY')
                    ->options(fn() => \App\Models\User::pluck('name', 'name')->toArray())
                    ->selectablePlaceholder(true)
                    ->placeholder('—')
                    ->grow(false)
                    ->extraAttributes(['style' => 'min-width:110px;']),

                // 🚀 THE FIX: Converted to SelectColumn
                Tables\Columns\SelectColumn::make('picked_up_by')
                    ->label('PICK BY')
                    ->options(fn() => \App\Models\User::pluck('name', 'name')->toArray())
                    ->selectablePlaceholder(true)
                    ->placeholder('—')
                    ->grow(false)
                    ->extraAttributes(['style' => 'min-width:110px;']),

                Tables\Columns\TextColumn::make('date_picked_up')
                    ->label('PICKED')
                    ->date('m/d/y')
                    ->placeholder('—')
                    ->size('sm')
                    ->grow(false),

                Tables\Columns\SelectColumn::make('status')
                    ->label('STATUS')
                    ->options([
                        'received'    => 'RCVD',
                        'in_progress' => 'IN PROG',
                        'ready'       => 'DONE',
                        'delivered'   => 'DELIVERED',
                    ])
                    ->selectablePlaceholder(false)
                    ->grow(false)
                    ->extraAttributes(fn($record): array => match ($record->status) {
                        'received'    => ['style' => 'background:#F3F4F6;color:#374151;font-weight:700;border-radius:4px;min-width:90px;'],
                        'in_progress' => ['style' => 'background:#FEE2E2;color:#B91C1C;font-weight:700;border-radius:4px;min-width:90px;'],
                        'ready'       => ['style' => 'background:#DCFCE7;color:#15803D;font-weight:700;border-radius:4px;min-width:90px;'],
                        'delivered'   => ['style' => 'background:#DBEAFE;color:#1D4ED8;font-weight:700;border-radius:4px;min-width:90px;'],
                        default       => ['style' => 'min-width:90px;'],
                    }),

                // 🚀 THE FIX: Converted to SelectColumn
                Tables\Columns\SelectColumn::make('repair_location')
                    ->label('LOCATION')
                    ->options(function ($record) {
                        // Get staff names as options
                        $options = \App\Models\User::pluck('name', 'name')->toArray();
                        // If the record has a custom location (like "Javier Workshop") that isn't a staff name,
                        // ensure it appears in the dropdown so it doesn't look blank.
                        if ($record->repair_location && !array_key_exists($record->repair_location, $options)) {
                            $options[$record->repair_location] = $record->repair_location;
                        }
                        return $options;
                    })
                    ->selectablePlaceholder(true)
                    ->placeholder('—')
                    ->grow(false)
                    ->extraAttributes(['style' => 'min-width:130px;']),

                Tables\Columns\TextColumn::make('customer_pickup_date')
                    ->label('PICKUP')
                    ->placeholder('—')
                    ->formatStateUsing(
                        fn($state) => $state
                            ? "<span style='background:#dcfce7;color:#166534;padding:2px 6px;border-radius:4px;font-weight:700;font-size:11px;white-space:nowrap;'>"
                            . \Carbon\Carbon::parse($state)->format('m/d/y')
                            . "</span>"
                            : '<span style="color:#9ca3af;">—</span>'
                    )
                    ->html()
                    ->grow(false),

                Tables\Columns\TextColumn::make('repair_notes')
                    ->label('NOTES')
                    ->limit(25)
                    ->tooltip(fn($record) => $record->repair_notes)
                    ->placeholder('—')
                    ->size('sm')
                    ->color('warning')
                    ->grow(false),

                Tables\Columns\TextColumn::make('notified_at')
                    ->label('NOTIFIED')
                    ->getStateUsing(
                        fn($record) => $record->notified_at
                            ? $record->notified_at->format('m/d/y')
                            : '—'
                    )
                    ->icon(fn($record) => $record->notified_at ? 'heroicon-s-check-circle' : 'heroicon-o-clock')
                    ->color(fn($record) => $record->notified_at ? 'success' : 'gray')
                    ->description(function ($record) {
                        if (empty($record->repair_history)) return null;
                        return new HtmlString("<span class='text-primary-600 text-[10px] font-bold underline cursor-pointer'>History</span>");
                    })
                    ->action(
                        Tables\Actions\Action::make('viewRepairLog')
                            ->modalHeading('Communication History')
                            ->modalWidth('lg')
                            ->slideOver()
                            ->modalSubmitAction(false)
                            ->form([
                                Forms\Components\Placeholder::make('history_display')
                                    ->label('')
                                    ->content(function ($record) {
                                        if (empty($record->repair_history)) return 'No history available.';
                                        $html = '<div class="space-y-4">';
                                        foreach (array_reverse($record->repair_history) as $log) {
                                            $date   = \Carbon\Carbon::parse($log['sent_at'])->format('M d, Y h:i A');
                                            $method = strtoupper($log['method'] ?? 'N/A');
                                            $staff  = $log['staff_name'] ?? 'System';
                                            $html  .= "
                                                <div class='p-4 bg-gray-50 border border-gray-200 rounded-lg shadow-sm'>
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
                                    })
                            ])
                    )
                    ->grow(false),

                Tables\Columns\TextColumn::make('estimated_total')
                    ->label('QUOTE')
                    ->money('USD')
                    ->getStateUsing(
                        fn($record) => collect($record->items ?? [])
                            ->sum(fn($i) => (float)($i['estimated_cost'] ?? 0))
                    )
                    ->grow(false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'received'    => 'Received',
                        'in_progress' => 'In Progress',
                        'ready'       => 'Job Complete',
                        'delivered'   => 'Done',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('notifyDelay')
                        ->label('Delay Notification')
                        ->icon('heroicon-o-clock')
                        ->color('danger')
                        ->form([
                            Forms\Components\Select::make('notify_method')
                                ->options(['sms' => 'SMS', 'email' => 'Email', 'both' => 'Both'])
                                ->default('sms')
                                ->required(),
                            Forms\Components\Textarea::make('message')
                                ->label('Message Content')
                                ->default(fn($record) => "Hi {$record->customer->name}, we are experiencing a slight delay with your repair #{$record->repair_no}. We appreciate your patience!")
                                ->required(),
                        ])
                        ->action(fn($record, array $data) => self::handleRepairNotification(
                            $record,
                            $data['notify_method'],
                            $data['message']
                        )),

                    Tables\Actions\Action::make('markReady')
                        ->label('Ready for Pickup')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('notify_method')
                                ->options(['sms' => 'SMS', 'email' => 'Email', 'both' => 'Both', 'none' => 'None'])
                                ->default('both')
                                ->required(),
                            Forms\Components\Textarea::make('message')
                                ->label('Message Content')
                                ->default(fn($record) => "Hi {$record->customer->name}, great news! Your repair #{$record->repair_no} is ready for pickup.")
                                ->visible(fn($get) => $get('notify_method') !== 'none'),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update(['status' => 'ready']);
                            if (($data['notify_method'] ?? 'none') !== 'none') {
                                self::handleRepairNotification($record, $data['notify_method'], $data['message']);
                            }
                        }),

                    Tables\Actions\Action::make('billRepair')
                        ->label('Bill to POS')
                        ->icon('heroicon-o-currency-dollar')
                        ->color('success')
                        ->url(fn(Repair $record) => route('filament.admin.resources.sales.create', [
                            'repair_id'   => $record->id,
                            'customer_id' => $record->customer_id,
                        ])),

                    Tables\Actions\Action::make('printJobPacket')
                        ->label('Print Job Packet')
                        ->icon('heroicon-o-printer')
                        ->color('info')
                        ->url(fn(Repair $record): string => route('repair.print', $record))
                        ->openUrlInNewTab(),
                ])
            ])
            ->defaultSort('created_at', 'desc');
    }

    // ── NOTIFICATIONS ─────────────────────────────────────────────────
    public static function handleRepairNotification(Repair $record, string $method, string $message)
    {
        $history   = $record->repair_history ?? [];
        $history[] = [
            'sent_at'    => now()->toDateTimeString(),
            'method'     => $method,
            'message'    => $message,
            'staff_name' => auth()->user()->name ?? 'System',
        ];

        $record->update([
            'last_message'   => $message,
            'notified_at'    => now(),
            'repair_history' => $history,
        ]);

        if (in_array($method, ['sms', 'both']) && !empty($record->customer->phone)) {
            try {
                $digits         = preg_replace('/[^0-9]/', '', $record->customer->phone);
                $formattedPhone = '+1' . (Str::startsWith($digits, '1') ? substr($digits, 1) : $digits);

                $settings = \Illuminate\Support\Facades\DB::table('site_settings')->pluck('value', 'key');

                $sns = new SnsClient([
                    'version'     => 'latest',
                    'region'      => $settings['aws_sms_default_region'] ?? config('services.sns.region', 'us-east-2'),
                    'credentials' => [
                        'key'    => $settings['aws_sms_access_key_id'] ?? config('services.sns.key'),
                        'secret' => $settings['aws_sms_secret_access_key'] ?? config('services.sns.secret'),
                    ],
                ]);

                $sns->publish([
                    'Message'           => $message,
                    'PhoneNumber'       => $formattedPhone,
                    'MessageAttributes' => [
                        'OriginationNumber' => [
                            'DataType'    => 'String',
                            'StringValue' => $settings['aws_sns_sms_from'] ?? config('services.sns.sms_from'),
                        ],
                    ],
                ]);
            } catch (\Exception $e) {
                Notification::make()->title('SMS Error')->body($e->getMessage())->danger()->send();
            }
        }

        if (in_array($method, ['email', 'both']) && !empty($record->customer->email)) {
            try {
                $settings = \Illuminate\Support\Facades\DB::table('site_settings')->pluck('value', 'key');
                config([
                    'services.ses.key'    => $settings['aws_access_key_id'] ?? config('services.ses.key'),
                    'services.ses.secret' => $settings['aws_secret_access_key'] ?? config('services.ses.secret'),
                    'services.ses.region' => $settings['aws_default_region'] ?? config('services.ses.region'),
                ]);

                Mail::raw($message, function ($mail) use ($record) {
                    $mail->to($record->customer->email)
                        ->subject("Update: Jewelry Repair #{$record->repair_no}");
                });
            } catch (\Exception $e) {
                Notification::make()->title('Email Error')->body('SMTP check failed.')->danger()->send();
            }
        }

        Notification::make()->title('Customer Notified')->success()->send();
    }

    // ── PAGES ─────────────────────────────────────────────────────────
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRepairs::route('/'),
            'create' => Pages\CreateRepair::route('/create'),
            'edit'   => Pages\EditRepair::route('/{record}/edit'),
        ];
    }
}
