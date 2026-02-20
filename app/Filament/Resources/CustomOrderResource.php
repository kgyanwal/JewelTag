<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomOrderResource\Pages;
use App\Models\CustomOrder;
use App\Models\User; 
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\{Section, Grid, Select, TextInput, Textarea, FileUpload, DatePicker, Toggle, Placeholder, DateTimePicker};
use Filament\Notifications\Notification;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Aws\Sns\SnsClient; 
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class CustomOrderResource extends Resource
{
    protected static ?string $model = CustomOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'Custom Orders';

    public static function form(Form $form): Form
    {
        return $form->schema([
            // 👈 LEFT COLUMN (Main Details)
            Forms\Components\Group::make()->schema([
                
                Section::make('Customer & Assignment')
                    ->description('Assign this custom piece to a customer and sales rep.')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('customer_id')
                                ->label('Select Customer')
                                ->relationship('customer', 'name')
                                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} {$record->last_name} ({$record->phone})")
                                ->searchable(['name', 'last_name', 'phone'])
                                ->preload()
                                ->prefixIcon('heroicon-o-user')
                                ->required(),

                            Select::make('staff_id')
                                ->label('Sales Person')
                                ->options(User::pluck('name', 'id'))
                                ->default(fn() => auth()->id())
                                ->searchable()
                                ->prefixIcon('heroicon-o-identification')
                                ->required(),
                        ]),
                    ]),

                Section::make('Design & Manufacturing')
                    ->description('Upload references and describe the piece.')
                    ->icon('heroicon-o-paint-brush')
                    ->schema([
                        // DYNAMIC PRODUCT NAME
                        Select::make('product_name')
                            ->label('Product / Jewelry Type')
                            ->placeholder('e.g. Diamond Tennis Bracelet')
                            ->options(function() {
                                return CustomOrder::distinct()->whereNotNull('product_name')->pluck('product_name', 'product_name');
                            })
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('new_product_name')->required()->label('New Product Name')
                            ])
                            ->createOptionUsing(fn (array $data) => $data['new_product_name'])
                            ->prefixIcon('heroicon-o-tag')
                            ->required(),

                        Textarea::make('design_notes')
                            ->label('Detailed Design Notes')
                            ->rows(4)
                            ->placeholder('Describe stone placements, engraving, specific styles...'),
                            
                        FileUpload::make('reference_image')
                            ->label('Reference Sketch / Photo')
                            ->image()
                            ->imageEditor()
                            ->directory('custom-orders'),
                    ]),

                Section::make('Vendor & Scheduling')
                    ->description('Track external production and deadlines.')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('vendor_name')
                                ->label('Vendor Name')
                                ->prefixIcon('heroicon-o-building-storefront')
                                ->placeholder('Who is making this?'),
                            
                            TextInput::make('vendor_info')
                                ->label('Vendor Contact/Info')
                                ->prefixIcon('heroicon-o-phone'),
                        ]),

                        Grid::make(3)->schema([
                            DatePicker::make('due_date')->label('Vendor Due Date')->native(false),
                            DatePicker::make('expected_delivery_date')->label('Cust. Delivery Date')->native(false),
                            DatePicker::make('follow_up_date')->label('Follow Up Date')->native(false),
                        ]),
                    ]),

            ])->columnSpan(['lg' => 8]),

            // 👉 RIGHT COLUMN (Specs & Status)
            Forms\Components\Group::make()->schema([
                
                Section::make('Material Specs')
                    ->icon('heroicon-o-beaker')
                    ->schema([
                        Select::make('metal_type')
                            ->label('Metal Karat/Type')
                            ->options([
                                '10k' => '10k Gold',
                                '14k' => '14k Gold',
                                '18k' => '18k Gold',
                                'platinum' => 'Platinum',
                                'silver' => 'Silver',
                            ])->required(),
                        
                        Grid::make(2)->schema([
                            TextInput::make('metal_weight')->label('Metal Wt.')->numeric()->suffix('g'),
                            TextInput::make('diamond_weight')->label('Diamond Wt.')->numeric()->suffix('ct'),
                        ]),
                        
                        TextInput::make('size')->label('Size / Length')->placeholder('e.g. Size 7, 18"'),
                    ]),

                Section::make('Financials & Status')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        TextInput::make('budget')->label('Customer Budget')->numeric()->prefix('$'),
                        
                        TextInput::make('quoted_price')
                            ->label('Final Quoted Price')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->extraInputAttributes(['class' => 'font-bold text-green-600 bg-green-50']),
                        
                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'quoted' => 'Quoted',
                                'approved' => 'Approved',
                                'in_production' => 'In Production',
                                'received' => 'Ready for Pickup', 
                                'completed' => 'Picked Up / Completed',
                            ])
                            ->default('draft')
                            ->live()
                            ->extraAttributes(['class' => 'font-bold']), 
                        
                        Section::make('Communication Log')
                            ->schema([
                                Toggle::make('is_customer_notified')
                                    ->label('Mark as Notified')
                                    ->onColor('success')
                                    ->offColor('gray'),
                                DateTimePicker::make('notified_at')
                                    ->label('Last Notified Timestamp')
                                    ->readOnly(),
                            ])
                            ->visible(fn (Get $get) => in_array($get('status'), ['received', 'completed'])) 
                            ->compact(),
                    ]),

            ])->columnSpan(['lg' => 4]),
        ])->columns(12);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_no')->label('ORDER #')->searchable()->sortable()->weight('bold'),
                
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('CUSTOMER')
                    ->searchable()
                    ->description(fn($record) => $record->product_name ?? 'Custom Piece'),
                    
                Tables\Columns\TextColumn::make('staff.name')
                    ->label('SALES REP')
                    ->toggleable()
                    ->color('gray'), 

                Tables\Columns\TextColumn::make('due_date')
                    ->date('M d, Y')
                    ->label('DUE DATE')
                    ->sortable()
                    ->color(fn ($state) => $state <= now() ? 'danger' : 'warning'),
                
                Tables\Columns\SelectColumn::make('status')
                    ->label('STATUS')
                    ->options([
                        'draft' => 'Draft',
                        'quoted' => 'Quoted',
                        'approved' => 'Approved',
                        'in_production' => 'In Production',
                        'received' => 'Ready for Pickup',
                        'completed' => 'Completed',
                    ])
                    ->selectablePlaceholder(false),

                // 🆕 TIMESTAMP COLUMN WITH TICK & TRANSPARENCY
                Tables\Columns\TextColumn::make('notified_at')
                    ->label('TIMESTAMP')
                    ->getStateUsing(fn ($record) => $record->is_customer_notified && $record->notified_at ? $record->notified_at->format('M d, y - h:i A') : 'Not Notified')
                    ->icon(fn ($state) => $state !== 'Not Notified' ? 'heroicon-s-check-circle' : 'heroicon-o-clock')
                    ->color(fn ($state) => $state !== 'Not Notified' ? 'success' : 'gray')
                    // Tailwind class to make the text slightly transparent
                    ->extraAttributes(fn ($state) => $state !== 'Not Notified' ? ['class' => 'opacity-70'] : []),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton(),
                
                // 🔴 DELAY ACTION (Dynamic SMS/Email)
                Tables\Actions\Action::make('notifyDelay')
                    ->label('Delay')
                    ->icon('heroicon-o-clock')
                    ->color('danger') 
                    ->modalHeading('Notify Customer of Delay')
                    ->form([
                        Select::make('notify_method')
                            ->label('Notification Method')
                            ->options([
                                'sms' => 'SMS Only',
                                'email' => 'Email Only',
                                'both' => 'Both (SMS & Email)'
                            ])
                            ->default('sms')
                            ->required(),
                        Textarea::make('message')
                            ->label('Message Content')
                            ->default(fn($record) => "Hi {$record->customer->name}, your custom order #{$record->order_no} is taking a little longer than expected. We apologize for the delay and will update you soon.")
                            ->rows(3)
                            ->required()
                    ])
                    ->action(function (CustomOrder $record, array $data) {
                        self::handleNotification($record, $data['notify_method'], $data['message']);
                    }),

                // 🟢 READY ACTION (Dynamic SMS/Email)
                Tables\Actions\Action::make('markReady')
                    ->label('Ready')
                    ->icon('heroicon-o-check-circle')
                    ->color('success') 
                    ->modalHeading('Mark Ready & Notify')
                    ->form([
                        Select::make('notify_method')
                            ->label('Notification Method')
                            ->options([
                                'sms' => 'SMS Only',
                                'email' => 'Email Only',
                                'both' => 'Both (SMS & Email)',
                                'none' => 'Do not notify (Just update status)'
                            ])
                            ->default('both')
                            ->required(),
                        Textarea::make('message')
                            ->label('Message Content')
                            ->default(fn($record) => "Hi {$record->customer->name}, Great news! Your custom order #{$record->order_no} is READY for pickup at Diamond Square.")
                            ->rows(3)
                            ->required(fn(Get $get) => $get('notify_method') !== 'none')
                            ->visible(fn(Get $get) => $get('notify_method') !== 'none')
                    ])
                    ->action(function (CustomOrder $record, array $data) {
                        // 1. Update Status
                        $record->update([
                            'status' => 'received', 
                            'is_customer_notified' => $data['notify_method'] !== 'none', 
                            'notified_at' => $data['notify_method'] !== 'none' ? now() : null
                        ]);
                        
                        // 2. Send Notifications
                        if ($data['notify_method'] !== 'none') {
                            self::handleNotification($record, $data['notify_method'], $data['message']);
                        } else {
                            Notification::make()->title('Status updated. No notifications sent.')->success()->send();
                        }
                    }),

                // 🔹 CONVERT TO SALE
                Tables\Actions\Action::make('convertToSale')
                    ->label('To Sale')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('info')
                    ->visible(fn (CustomOrder $record) => $record->status === 'received')
                    ->action(function (CustomOrder $record) {
                        return redirect()->route('filament.admin.resources.sales.create', [
                            'customer_id' => $record->customer_id,
                            'custom_order_id' => $record->id,
                        ]);
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * 📩 HELPER: Handles Both SMS and Email Routing
     */
    public static function handleNotification(CustomOrder $record, string $method, string $message)
    {
        $smsSuccess = false;
        $emailSuccess = false;

        // --- SEND SMS ---
        if (in_array($method, ['sms', 'both'])) {
            if (empty($record->customer->phone)) {
                Notification::make()->title('SMS Failed')->body('Customer has no phone number.')->danger()->send();
            } else {
                try {
                    $digits = preg_replace('/[^0-9]/', '', $record->customer->phone);
                    if (Str::startsWith($digits, '1') && strlen($digits) === 11) $digits = substr($digits, 1);
                    $formattedPhone = '+1' . $digits;

                    $sns = new SnsClient([
                        'version' => 'latest',
                        'region'  => config('services.sns.region'),
                        'credentials' => [
                            'key'    => config('services.sns.key'),
                            'secret' => config('services.sns.secret'),
                        ],
                    ]);

                    $sns->publish([
                        'Message' => $message,
                        'PhoneNumber' => $formattedPhone,
                        'MessageAttributes' => [
                            'OriginationNumber' => ['DataType' => 'String', 'StringValue' => config('services.sns.sms_from')],
                        ],
                    ]);
                    $smsSuccess = true;
                } catch (\Exception $e) {
                    Notification::make()->title('SMS Error')->body($e->getMessage())->danger()->send();
                }
            }
        }

        // --- SEND EMAIL ---
        if (in_array($method, ['email', 'both'])) {
            if (empty($record->customer->email)) {
                Notification::make()->title('Email Failed')->body('Customer has no email address.')->danger()->send();
            } else {
                try {
                    // Send basic text email using Laravel Mail Facade
                    Mail::raw($message, function ($mail) use ($record) {
                        $mail->to($record->customer->email)
                             ->subject("Update on your Custom Order #{$record->order_no}");
                    });
                    $emailSuccess = true;
                } catch (\Exception $e) {
                    Notification::make()->title('Email Error')->body('Failed to send email. Check SMTP settings.')->danger()->send();
                }
            }
        }

        // --- FINAL NOTIFICATION UI ---
        if ($method === 'both' && $smsSuccess && $emailSuccess) {
            Notification::make()->title('Success')->body('SMS & Email sent successfully.')->success()->send();
        } elseif ($method === 'sms' && $smsSuccess) {
            Notification::make()->title('Success')->body('SMS sent successfully.')->success()->send();
        } elseif ($method === 'email' && $emailSuccess) {
            Notification::make()->title('Success')->body('Email sent successfully.')->success()->send();
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomOrders::route('/'),
            'create' => Pages\CreateCustomOrder::route('/create'),
            'edit' => Pages\EditCustomOrder::route('/{record}/edit'),
        ];
    }
}