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
use Filament\Forms\Components\{Section, TextInput, Select, Toggle, Grid, Textarea};
use Filament\Forms\Components\Actions\Action as FormAction;
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
                        // 🚀 START: COPY-PASTED CUSTOMER CREATION LOGIC
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
                        // 🚀 END: CUSTOMER CREATION LOGIC
                    
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
                                modifyQueryUsing: fn ($query) => $query->where('status', 'sold') 
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->barcode} - {$record->custom_description}")
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($item = ProductItem::find($state)) {
                                    $set('item_description', $item->custom_description ?? $item->barcode);
                                }
                            })
                            ->visible(fn (Forms\Get $get) => $get('is_from_store_stock'))
                            ->columnSpanFull(),
                    ]),

                    TextInput::make('item_description')
                        ->label('Item Name/Description')
                        ->placeholder('e.g., Diamond Engagement Ring')
                        ->required()
                        ->columnSpanFull(),

                    Textarea::make('reported_issue')
                        ->label('Issue Reported by Customer')
                        ->placeholder('Describe what needs to be fixed...')
                        ->required() // 🔹 Mandatory per your DB error
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
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_from_store_stock')
                    ->label('Store Stock')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'received' => 'gray',
                        'in_progress' => 'info',
                        'ready' => 'success',
                        'delivered' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('estimated_cost')
                    ->money('USD')
                    ->label('Quote'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'received' => 'Received',
                        'in_progress' => 'In Progress',
                        'ready' => 'Ready',
                        'delivered' => 'Delivered',
                    ]),
                Tables\Filters\TernaryFilter::make('is_from_store_stock')
                    ->label('Item Origin')
                    ->trueLabel('Our Store')
                    ->falseLabel('Outside Item'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                // 🔹 Send to POS link
                Tables\Actions\Action::make('billRepair')
    ->label('Bill to POS')
    ->icon('heroicon-o-currency-dollar')
    ->color('success')
    ->requiresConfirmation()
    ->modalHeading('Transfer to Quick Sale?')
    ->modalDescription('This will create a new sale record with this repair service included.')
    ->url(fn (Repair $record) => route('filament.admin.resources.sales.create', [
        'repair_id' => $record->id,
        'customer_id' => $record->customer_id,
    ])),

            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
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