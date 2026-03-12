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
use Filament\Forms\Components\{Section, TextInput, Select, Toggle, Grid, Textarea, DateTimePicker};
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Aws\Sns\SnsClient;

class RepairResource extends Resource
{
    protected static ?string $model = Repair::class;
    protected static ?string $navigationIcon = 'heroicon-o-wrench';
    protected static ?string $navigationGroup = 'Sales';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Repair Intake')
                ->description('Record details for customer jewelry service.')
                ->schema([
                    Select::make('customer_id')
                        ->relationship('customer', 'name')
                        ->getOptionLabelFromRecordUsing(fn($record) => "{$record->name} {$record->last_name}")
                        ->searchable(['name', 'last_name', 'phone'])
                        ->preload()
                        ->hintAction(
                            FormAction::make('Help')
                                ->icon('heroicon-o-information-circle')
                                ->tooltip('Click + to add a new customer')
                        )
                        ->required()
                        ->columnSpan(1)
                        ->createOptionModalHeading('Create New Customer')
                        ->createOptionForm([
                            Forms\Components\Tabs::make('New Customer')
                                ->tabs([
                                    Forms\Components\Tabs\Tab::make('Contact')
                                        ->icon('heroicon-o-user')
                                        ->schema([
                                            Forms\Components\Grid::make(2)->schema([
                                                Forms\Components\TextInput::make('name')
                                                    ->label('First Name')
                                                    ->required(),
                                                Forms\Components\TextInput::make('last_name')
                                                    ->label('Last Name'),
                                            ]),

                                            Forms\Components\Grid::make(2)->schema([
                                                Forms\Components\TextInput::make('phone')
                                                    ->label('Mobile Phone')
                                                    ->tel()
                                                    ->prefix('+1')
                                                    ->mask('(999) 999-9999')
                                                    ->stripCharacters(['(', ')', '-', ' '])
                                                    ->required()
                                                    ->unique('customers', 'phone'),

                                                Forms\Components\TextInput::make('email')
                                                    ->label('Email')
                                                    ->email(),
                                            ]),

                                            Forms\Components\Textarea::make('address')
                                                ->label('Address')
                                                ->rows(2)
                                                ->columnSpanFull(),
                                        ]),

                                    Forms\Components\Tabs\Tab::make('Profile')
                                        ->icon('heroicon-o-camera')
                                        ->schema([
                                            Forms\Components\FileUpload::make('image')
                                                ->label('Customer Photo')
                                                ->image()
                                                ->avatar()
                                                ->directory('customer-photos')
                                                ->visibility('public')
                                                ->columnSpanFull(),

                                            Forms\Components\Grid::make(2)->schema([
                                                Forms\Components\DatePicker::make('dob')
                                                    ->label('Birthday')
                                                    ->native(false),

                                                Forms\Components\DatePicker::make('wedding_anniversary')
                                                    ->label('Anniversary')
                                                    ->native(false),
                                            ]),
                                        ]),
                                ]),

                            Forms\Components\Hidden::make('customer_no')
                                ->default(fn() => 'CUST-' . strtoupper(bin2hex(random_bytes(3)))),
                        ])
                        ->createOptionUsing(function (array $data) {
                            return \App\Models\Customer::create($data)->id;
                        }),

                    Select::make('status')
                        ->options([
                            'received' => 'Received',
                            'in_progress' => 'In Progress',
                            'ready' => 'Ready for Pickup',
                            'delivered' => 'Delivered',
                        ])
                        ->default('received')
                        ->required()
                        ->columnSpan(1),

                    Grid::make(2)->schema([
                        Toggle::make('is_from_store_stock')
                            ->label('Was this bought from our store?')
                            ->live()
                            ->columnSpanFull(),

                        Select::make('original_product_id')
                            ->label('Search Stock No.')
                            ->placeholder('Search sold items...')
                            ->relationship(
                                name: 'originalProduct',
                                titleAttribute: 'barcode',
                                modifyQueryUsing: fn($query) => $query->where('status', 'sold')
                            )
                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->barcode} - {$record->custom_description}")
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($item = ProductItem::find($state)) {
                                    $set('item_description', $item->custom_description ?? $item->barcode);
                                }
                            })
                            ->visible(fn(Forms\Get $get) => $get('is_from_store_stock'))
                            ->columnSpanFull(),
                    ]),

                    Textarea::make('item_description')
                        ->label('Item Name/Description')
                        ->placeholder('e.g., Diamond Engagement Ring')
                        ->required()
                        ->maxLength(3000)
                        ->rows(3)
                        ->live()
                        ->helperText(fn($state) => strlen($state ?? '') . ' / 3000 characters')
                        ->columnSpanFull(),

                    Textarea::make('reported_issue')
                        ->label('Issue Reported by Customer')
                        ->placeholder('Describe what needs to be fixed...')
                        ->required()
                        ->columnSpanFull(),

                    TextInput::make('estimated_cost')
                        ->label('Estimated Cost')
                        ->numeric()
                        ->prefix('$')
                        ->default(0),

                    TextInput::make('final_cost')
                        ->label('Final Cost')
                        ->numeric()
                        ->prefix('$')
                        ->helperText('Leave blank until repair is finished.'),
                ])->columns(2)
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('repair_no')
                    ->label('Repair #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->description(fn ($record) => Str::limit($record->item_description, 50)),

                Tables\Columns\IconColumn::make('is_from_store_stock')
                    ->label('Store Stock')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'received' => 'gray',
                        'in_progress' => 'info',
                        'ready' => 'success',
                        'delivered' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('notified_at')
                    ->label('Last Notified')
                    ->getStateUsing(fn ($record) => $record->notified_at ? $record->notified_at->format('M d, y - h:i A') : 'Not Notified')
                    ->icon(fn ($record) => $record->notified_at ? 'heroicon-s-check-circle' : 'heroicon-o-clock')
                    ->color(fn ($record) => $record->notified_at ? 'success' : 'gray')
                    ->description(function ($record) {
                        if (!$record->notified_at) return null;
                        return new HtmlString("<span class='text-primary-600 font-bold underline cursor-pointer hover:text-primary-500'>View Message Log</span>");
                    })
                    ->action(
                        Tables\Actions\Action::make('viewRepairLog')
                            ->modalHeading('Communication History')
                            ->modalWidth('lg')
                            ->slideOver()
                            ->modalSubmitAction(false)
                            ->form([
                                Forms\Components\Placeholder::make('msg_content')
                                    ->label('Sent Content')
                                    ->content(fn ($record) => new HtmlString("
                                        <div class='p-4 bg-gray-50 border border-gray-200 rounded-lg shadow-sm'>
                                            <div class='flex justify-between items-center mb-2'>
                                                <span class='text-xs font-bold text-primary-600 uppercase'>Message Sent</span>
                                                <span class='text-[10px] text-gray-400'>".$record->notified_at?->format('M d, Y h:i A')."</span>
                                            </div>
                                            <p class='text-sm text-gray-700 italic leading-relaxed'>\"{$record->last_message}\"</p>
                                        </div>
                                    "))
                            ])
                    ),

                Tables\Columns\TextColumn::make('estimated_cost')
                    ->money('USD')
                    ->label('Quote')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['received' => 'Received', 'in_progress' => 'In Progress', 'ready' => 'Ready', 'delivered' => 'Delivered']),
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
                                ->default('sms')->required(),
                            Forms\Components\Textarea::make('message')
                                ->label('Message Content')
                                ->default(fn($record) => "Hi {$record->customer->name}, we are experiencing a slight delay with your repair #{$record->repair_no}. We appreciate your patience!")
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            self::handleRepairNotification($record, $data['notify_method'], $data['message']);
                        }),

                    Tables\Actions\Action::make('markReady')
                        ->label('Ready for Pickup')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->form([
                            Forms\Components\Select::make('notify_method')
                                ->options(['sms' => 'SMS', 'email' => 'Email', 'both' => 'Both', 'none' => 'None'])
                                ->default('both')->required(),
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
                        ->url(fn(Repair $record) => route('filament.admin.resources.sales.create', ['repair_id' => $record->id, 'customer_id' => $record->customer_id])),
                ])
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function handleRepairNotification(Repair $record, string $method, string $message)
    {
        $record->update([
            'last_message' => $message,
            'notified_at' => now(),
        ]);

        if (in_array($method, ['sms', 'both']) && !empty($record->customer->phone)) {
            try {
                $digits = preg_replace('/[^0-9]/', '', $record->customer->phone);
                $formattedPhone = '+1' . (Str::startsWith($digits, '1') ? substr($digits, 1) : $digits);
                
                $sns = new SnsClient([
                    'version' => 'latest',
                    'region'  => config('services.sns.region'),
                    'credentials' => ['key' => config('services.sns.key'), 'secret' => config('services.sns.secret')],
                ]);

                $sns->publish(['Message' => $message, 'PhoneNumber' => $formattedPhone]);
            } catch (\Exception $e) {
                Notification::make()->title('SMS Error')->body($e->getMessage())->danger()->send();
            }
        }

        if (in_array($method, ['email', 'both']) && !empty($record->customer->email)) {
            try {
                Mail::raw($message, function ($mail) use ($record) {
                    $mail->to($record->customer->email)->subject("Update: Jewelry Repair #{$record->repair_no}");
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
            'index' => Pages\ListRepairs::route('/'),
            'create' => Pages\CreateRepair::route('/create'),
            'edit' => Pages\EditRepair::route('/{record}/edit'),
        ];
    }
}