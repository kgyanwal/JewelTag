<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomOrderResource\Pages;
use App\Models\CustomOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\{Section, Grid, Select, TextInput, Textarea, FileUpload, DatePicker, Toggle, Placeholder, DateTimePicker};
use Filament\Notifications\Notification;
use Filament\Forms\Get;
use Filament\Forms\Set;

class CustomOrderResource extends Resource
{
    protected static ?string $model = CustomOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Sales';

    public static function form(Form $form): Form
    {
        return $form->schema([
            // 👈 LEFT COLUMN (Main Details)
            Forms\Components\Group::make()->schema([
                
                Section::make('Customer & Design')->schema([
                    Select::make('customer_id')
                        ->label('Select Customer')
                        ->relationship('customer', 'name')
                        ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} {$record->last_name}")
                        ->searchable(['name', 'phone'])
                        ->preload()
                        ->required(),
                    
                    Textarea::make('design_notes')
                        ->label('Design Description')
                        ->rows(4)
                        ->placeholder('Describe the custom piece details...'),
                        
                    FileUpload::make('reference_image')
                        ->image()
                        ->directory('custom-orders'),
                ]),

                Section::make('Vendor & Scheduling') // 🆕 Added Vendor & Dates
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('vendor_name')
                                ->label('Vendor / Artisan Name')
                                ->placeholder('Who is making this?'),
                            
                            TextInput::make('vendor_info')
                                ->label('Vendor Contact/Info'),
                        ]),

                        Grid::make(3)->schema([
                            DatePicker::make('due_date')
                                ->label('Production Due Date')
                                ->native(false),
                                
                            DatePicker::make('expected_delivery_date')
                                ->label('Cust. Delivery Date')
                                ->native(false),

                            DatePicker::make('follow_up_date')
                                ->label('Follow Up By')
                                ->native(false),
                        ]),
                    ]),

            ])->columnSpan(8),

            // 👉 RIGHT COLUMN (Specs & Status)
            Forms\Components\Group::make()->schema([
                
                Section::make('Specs')->schema([
                    Select::make('metal_type')
                        ->options([
                            '10k' => '10k Gold',
                            '14k' => '14k Gold',
                            '18k' => '18k Gold',
                            'platinum' => 'Platinum',
                            'silver' => 'Silver',
                        ])->required(),
                    
                    Grid::make(2)->schema([
                        TextInput::make('metal_weight')->numeric()->suffix('g'),
                        TextInput::make('diamond_weight')->numeric()->suffix('ct'),
                    ]),
                    
                    TextInput::make('size')->placeholder('Ring Size / Length'),
                ]),

                Section::make('Financials & Status')->schema([
                    TextInput::make('budget')->numeric()->prefix('$'),
                    TextInput::make('quoted_price')->numeric()->prefix('$')->required(),
                    
                    Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'quoted' => 'Quoted',
                            'approved' => 'Approved',
                            'in_production' => 'In Production',
                            'received' => 'Received from Vendor',
                            'completed' => 'Picked Up / Completed',
                        ])
                        ->default('draft')
                        ->live(), 
                    
                    // 🆕 Notification Logic
                    Section::make('Customer Notification')
                        ->schema([
                            Placeholder::make('notify_help')
                                ->label('')
                                ->content('Item received? Notify customer now:'),

                            Toggle::make('is_customer_notified')
                                ->label('Notify Customer (Email/SMS)')
                                ->onColor('success')
                                ->offColor('danger')
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set, $state, $record) {
                                    if ($state && $record) {
                                        // 📧 EMAIL LOGIC HERE:
                                        // Mail::to($record->customer->email)->send(new CustomOrderReady($record));
                                        
                                        $set('notified_at', now());
                                        
                                        Notification::make()
                                            ->title('Notification Sent')
                                            ->body("Customer {$record->customer->name} has been notified.")
                                            ->success()
                                            ->send();
                                    }
                                }),
                                
                            DateTimePicker::make('notified_at')
                                ->label('Sent At')
                                ->readOnly(),
                        ])
                        ->visible(fn (Get $get) => in_array($get('status'), ['received', 'completed'])) 
                        ->compact(),
                ]),

            ])->columnSpan(4),
        ])->columns(12);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_no')->label('#')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer.name')->label('Customer')->searchable(),
                Tables\Columns\TextColumn::make('vendor_name')->label('Vendor')->toggleable(),
                Tables\Columns\TextColumn::make('due_date')->date('M d')->label('Due')->sortable()->color('danger'),
                Tables\Columns\TextColumn::make('quoted_price')->money('USD')->weight('bold'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'quoted' => 'info',
                        'approved' => 'primary',
                        'in_production' => 'warning',
                        'received' => 'success',
                        'completed' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_customer_notified')
                    ->label('Notified')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                // 🔹 Send to POS Action
                Tables\Actions\Action::make('convertToSale')
                    ->label('To Sale')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (CustomOrder $record) => $record->status === 'completed' || $record->status === 'received')
                    ->action(function (CustomOrder $record) {
                        return redirect()->route('filament.admin.resources.sales.create', [
                            'customer_id' => $record->customer_id,
                            'custom_order_id' => $record->id,
                        ]);
                    }),
            ])
            ->defaultSort('created_at', 'desc');
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