<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductItemResource\Pages;
use App\Models\ProductItem;
use App\Services\ZebraPrinterService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\{Section, Grid, Select, TextInput, CheckboxList, Hidden, Textarea, Radio, Toggle};
use Filament\Tables\Actions\{Action, EditAction, ViewAction, ActionGroup};
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class ProductItemResource extends Resource
{
    protected static ?string $model = ProductItem::class;
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Assemble Stock';
    protected static ?string $navigationGroup = 'Inventory';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Assemble New Stock Item')
                    ->columns(12)
                    ->schema([
                        Forms\Components\Select::make('store_id')
                            ->label('Target Store')
                            ->relationship('store', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->user()->store_id ?? \App\Models\Store::first()?->id)
                            ->disabled(fn () => !auth()->user()->hasRole('super_admin'))
                            ->dehydrated() 
                            ->columnSpan(4),

                        Radio::make('creation_mode')
                            ->label('')
                            ->options(['new' => 'Assemble New Stock Item', 'copy' => 'Copy Existing Item'])
                            ->default('new')->inline()->columnSpan(12)->dehydrated(false),

                        Select::make('supplier_id')
                            ->relationship('supplier', 'company_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (callable $set, $state) {
                                if ($state) {
                                    $latestCode = \App\Models\ProductItem::where('supplier_id', $state)
                                        ->latest()
                                        ->value('supplier_code');
                                    $set('supplier_code', $latestCode);
                                }
                            })
                            ->columnSpan(4),

                        CheckboxList::make('print_options')
                            ->options([
                                'print_tag' => 'Print Barcode Tag',
                                'print_rfid' => 'Print RFID Tag',
                                'encode_rfid' => 'Encode RFID Data'
                            ])
                            ->columns(3)
                            ->columnSpan(12), // Logic: Now persists to DB

                        Toggle::make('enable_rfid_tracking')
                            ->label('Enable RFID Tracking')
                            ->onColor('success')
                            ->offColor('gray')
                            ->inline(false)
                            ->columnSpan(6), // Logic: Now persists to DB

                        TextInput::make('rfid_code')
                            ->label('RFID EPC Code')
                            ->placeholder('Auto-generated')
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(6),

                        TextInput::make('supplier_code')
                            ->label('Supplier Code')
                            ->placeholder('Auto-fills from last invoice')
                            ->columnSpan(3),

                        Select::make('form_type')
                            ->options(['Ring' => 'Ring', 'Watch' => 'Watch', 'Earring' => 'Earring', 'General' => 'General'])
                            ->default('General')->columnSpan(3),

                        Select::make('department')
                            ->options(fn() => collect(Cache::get('inventory_departments', []))->mapWithKeys(fn($val) => [$val => $val]))
                            ->searchable()->columnSpan(2),

                        Select::make('category')
                            ->options(fn() => collect(Cache::get('inventory_categories', []))->mapWithKeys(fn($val) => [$val => $val]))
                            ->searchable()->columnSpan(2),

                        Select::make('metal_type')
                            ->options(fn() => collect(Cache::get('inventory_metal_types', []))->mapWithKeys(fn($val) => [$val => $val]))
                            ->searchable()->columnSpan(2),

                        TextInput::make('size')->columnSpan(6),
                        TextInput::make('metal_weight')->numeric()->columnSpan(6),

                        TextInput::make('qty')->numeric()->default(1)->columnSpan(2)->extraInputAttributes(['class' => 'bg-yellow-50 text-center font-bold']),

                        TextInput::make('cost_price')->prefix('$')->numeric()->columnSpan(2),
                        TextInput::make('retail_price')->prefix('$')->numeric()->columnSpan(3)->extraInputAttributes(['class' => 'bg-green-50 font-bold text-green-700']),
                        TextInput::make('web_price')->prefix('$')->numeric()->columnSpan(3),
                        TextInput::make('discount_percent')->suffix('%')->numeric()->columnSpan(2),

                        Textarea::make('custom_description')->columnSpan(12)->rows(3),

                        TextInput::make('barcode')->label('Stock Number')->placeholder('G-Auto Generate')->disabled()->dehydrated()->columnSpan(4),
                        TextInput::make('serial_number')->columnSpan(4),
                        TextInput::make('component_qty')->numeric()->default(1)->columnSpan(4),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('barcode')->label('STOCK NO.')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('custom_description')->label('DESCRIPTION')->limit(40),
                Tables\Columns\TextColumn::make('metal_weight')->label('WEIGHT')->suffix('g'),
                Tables\Columns\TextColumn::make('retail_price')->label('PRICE')->money('USD')->color('success'),
                Tables\Columns\TextColumn::make('rfid_code')
                ->label('RFID NUMBER')
                ->fontFamily('mono')
                ->placeholder('Not Printed')
                ->searchable()
                ->copyable(),

            Tables\Columns\BadgeColumn::make('has_rfid')
                ->label('RFID STATUS')
                ->getStateUsing(fn ($record) => !empty($record->rfid_code) ? 'Yes' : 'No')
                ->colors(['success' => 'Yes', 'gray' => 'No']),
                Tables\Columns\TextColumn::make('status')
    ->badge()
    ->color(fn (string $state): string => match ($state) {
        'in_stock' => 'success',
        'sold' => 'danger',
        'out_of_stock' => 'warning',
        default => 'gray',
    }),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    ViewAction::make()->color('info'),
                    
                    Action::make('print_tag')
                        ->label('Print Barcode Tag')
                        ->icon('heroicon-o-printer')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(fn ($record, ZebraPrinterService $service) => $service->printJewelryTag($record, false)),
                    
                    Action::make('print_rfid_tag')
                        ->label('Print RFID Tag')
                        ->icon('heroicon-o-identification')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn ($record, ZebraPrinterService $service) => $service->printJewelryTag($record, true)),
                    
                    Action::make('test_rfid_printer')
                        ->label('Check Printer Status')
                        ->icon('heroicon-o-wrench')
                        ->color('gray')
                        ->action(function (ZebraPrinterService $service) {
                            $status = $service->checkRFIDPrinterStatus();
                            Notification::make()
                                ->title($status['connected'] ? 'Printer Online' : 'Printer Offline')
                                ->status($status['connected'] ? 'success' : 'danger')
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductItems::route('/'),
            'create' => Pages\CreateProductItem::route('/create'),
            'edit' => Pages\EditProductItem::route('/{record}/edit'),
        ];
    }
}