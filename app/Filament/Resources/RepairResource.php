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
use Filament\Forms\Components\{DatePicker, Section, TextInput, Select, Toggle, Grid, Textarea, Placeholder, Repeater, Hidden, Group};
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

    public static function form(Form $form): Form
    {
        return $form->schema([

            // ── MAIN LAYOUT: 12-column grid ──────────────────────────
            Grid::make(12)->schema([

                // ── LEFT COLUMN: Customer + Items (8 cols) ───────────
                Group::make()->columnSpan(['lg' => 8])->schema([

                    Section::make('Repair Intake')
                        ->description('Record customer details and overall ticket status.')
                        ->icon('heroicon-o-user-circle')
                        ->schema([
                            Grid::make(2)->schema([
                                // Customer Selection
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
                                    ->hintAction(
                                        FormAction::make('Help')
                                            ->icon('heroicon-o-information-circle')
                                            ->tooltip('Click + to add a new customer')
                                    )
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
                                                        DatePicker::make('dob')->maxDate(now())->rule('before_or_equal:today')->label('Birth Date'),
                                                        DatePicker::make('wedding_anniversary')->label('Wedding Date'),
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

                                // Status
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

                    // ── JEWELRY ITEMS REPEATER ────────────────────────
                    Section::make('Jewelry Items to Repair')
                        ->description('Add all items brought in by the customer.')
                        ->icon('heroicon-o-sparkles')
                        ->schema([
                            Repeater::make('items')
                                ->label('')
                                ->schema([
                                    // Row 1: Toggles
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

                                    // Row 2: Store Stock Selector (Only visible if Toggle is ON)
                                    Select::make('original_product_id')
                                        ->label('Search Store Stock No.')
                                        ->placeholder('Search sold items...')
                                        ->relationship(
                                            name: 'originalProduct',
                                            titleAttribute: 'barcode',
                                            modifyQueryUsing: fn($query) => $query->where('status', 'sold')
                                        )
                                        ->getOptionLabelFromRecordUsing(fn($record) => "{$record->barcode} - {$record->custom_description}")
                                        ->searchable()
                                        ->preload()
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems() // 🚀 PREVENTS DUPLICATE SELECTION
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                            if ($item = ProductItem::find($state)) {
                                                $set('item_description', $item->custom_description ?? $item->barcode);
                                            }
                                        })
                                        ->visible(fn(Forms\Get $get) => $get('is_from_store_stock'))
                                        ->columnSpanFull(),

                                    // Row 3: Descriptions
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

                                    // Row 4: Pricing
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
                                ->cloneable() // 🚀 Allows cloning a row if customer brings 2 identical rings
                                ->reorderable(true),
                        ]),
                ]),

                // ── RIGHT COLUMN: Staff + Workflow (4 cols) ──────────
                Group::make()->columnSpan(['lg' => 4])->schema([

                    Section::make('Staff Assignment')
                        ->icon('heroicon-o-identification')
                        ->schema([
                            Select::make('sales_person_list')
                                ->label('Sales Staff')
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->options(\App\Models\User::pluck('name', 'id'))
                                ->default(fn() => [Session::get('active_staff_name') ?? auth()->user()->name])
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    $set('sales_person_id', !empty($state) ? $state[0] : null);
                                }),
                            
                            Hidden::make('sales_person_id'),
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('repair_no')
                    ->label('Repair #')
                    ->searchable()->sortable()->weight('bold'),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->description(function ($record) {
                        $items = $record->items ?? [];
                        $count = count($items);
                        if ($count === 0) return 'No items recorded';
                        if ($count === 1) return Str::limit($items[0]['item_description'] ?? 'Jewelry Item', 50);
                        return "{$count} Items (Multiple)";
                    }),

                Tables\Columns\TextColumn::make('salesPerson.name')
                    ->label('STAFF')->badge()->color('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'received'    => 'gray',
                        'in_progress' => 'info',
                        'ready'       => 'success',
                        'delivered'   => 'primary',
                        default       => 'gray',
                    }),

                Tables\Columns\TextColumn::make('notified_at')
                    ->label('Last Notified')
                    ->getStateUsing(fn($record) => $record->notified_at ? $record->notified_at->format('M d, y - h:i A') : 'Not Notified')
                    ->icon(fn($record) => $record->notified_at ? 'heroicon-s-check-circle' : 'heroicon-o-clock')
                    ->color(fn($record) => $record->notified_at ? 'success' : 'gray')
                    ->description(function ($record) {
                        if (empty($record->repair_history)) return null;
                        return new HtmlString("<span class='text-primary-600 font-bold underline cursor-pointer hover:text-primary-500'>View Full History</span>");
                    })
                    ->action(
                        Tables\Actions\Action::make('viewRepairLog')
                            ->modalHeading('Communication History')
                            ->modalWidth('lg')->slideOver()
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
                    ),

                Tables\Columns\TextColumn::make('estimated_total')
                    ->label('Total Quote')->money('USD')
                    ->getStateUsing(fn($record) => collect($record->items ?? [])->sum(fn($i) => (float)($i['estimated_cost'] ?? 0))),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['received' => 'Received', 'in_progress' => 'In Progress', 'ready' => 'Ready', 'delivered' => 'Delivered']),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('notifyDelay')
                        ->label('Delay Notification')->icon('heroicon-o-clock')->color('danger')
                        ->form([
                            Forms\Components\Select::make('notify_method')
                                ->options(['sms' => 'SMS', 'email' => 'Email', 'both' => 'Both'])
                                ->default('sms')->required(),
                            Forms\Components\Textarea::make('message')->label('Message Content')
                                ->default(fn($record) => "Hi {$record->customer->name}, we are experiencing a slight delay with your repair #{$record->repair_no}. We appreciate your patience!")
                                ->required(),
                        ])
                        ->action(fn($record, array $data) => self::handleRepairNotification($record, $data['notify_method'], $data['message'])),

                    Tables\Actions\Action::make('markReady')
                        ->label('Ready for Pickup')->icon('heroicon-o-check-circle')->color('success')
                        ->form([
                            Forms\Components\Select::make('notify_method')
                                ->options(['sms' => 'SMS', 'email' => 'Email', 'both' => 'Both', 'none' => 'None'])
                                ->default('both')->required(),
                            Forms\Components\Textarea::make('message')->label('Message Content')
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
                        ->label('Bill to POS')->icon('heroicon-o-currency-dollar')->color('success')
                        ->url(fn(Repair $record) => route('filament.admin.resources.sales.create', [
                            'repair_id'   => $record->id,
                            'customer_id' => $record->customer_id,
                        ])),

                    Tables\Actions\Action::make('printJobPacket')
                        ->label('Print Job Packet')->icon('heroicon-o-printer')->color('info')
                        ->url(fn(Repair $record): string => route('repair.print', $record))
                        ->openUrlInNewTab(),
                ])
            ])
            ->defaultSort('created_at', 'desc');
    }

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

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRepairs::route('/'),
            'create' => Pages\CreateRepair::route('/create'),
            'edit'   => Pages\EditRepair::route('/{record}/edit'),
        ];
    }
}