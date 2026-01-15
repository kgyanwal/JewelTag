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
use Filament\Forms\Components\{Section, Grid, Select, TextInput, CheckboxList, Hidden, Textarea, Radio};
use Filament\Tables\Actions\{Action, EditAction, ViewAction, ActionGroup};
use Filament\Notifications\Notification;

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
                        // ROW 1: CREATION MODE
                        Radio::make('creation_mode')
                            ->label('')
                            ->options(['new' => 'Assemble New Stock Item', 'copy' => 'Copy Existing Item'])
                            ->default('new')
                            ->inline()
                            ->columnSpan(12)
                            ->dehydrated(false),

                        // ROW 2: SUPPLIER & OPTIONS
                        Select::make('supplier_id')
                            ->label('Supplier')
                            ->relationship('supplier', 'company_name') // 🔹 Dynamic Relationship
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(4),

                        CheckboxList::make('options')
                            ->options(['details' => 'More Details', 'print' => 'Print Tag'])
                            ->columns(2)
                            ->columnSpan(8)
                            ->extraAttributes(['class' => 'justify-end flex'])
                            ->dehydrated(false), 

                        // ROW 3: CODES & SPECS
                        TextInput::make('supplier_code')->label('Supplier Code')->columnSpan(3),
                        
                        Select::make('form_type')
                            ->options(['Ring' => 'Ring', 'Pearl' => 'Pearl', 'Watch' => 'Watch', 'Earring' => 'Earring', 'General' => 'General'])
                            ->default('General')
                            ->columnSpan(2),

                        Select::make('department')
                            ->options([
                                'CLEARANCE' => 'CLEARANCE', 
                                'DIA BRIDAL DEPT 1' => 'DIA BRIDAL DEPT 1', 
                                'GOLD RING DEPT 41' => 'GOLD RING DEPT 41',
                                'WATCH DEPT 35' => 'WATCH DEPT 35'
                            ])->searchable()->columnSpan(3),

                        Select::make('category')
                            ->options([
                                'ANIMAL PENDANT' => 'ANIMAL PENDANT', 
                                'BRIDAL ENGAGEMENT' => 'BRIDAL ENGAGEMENT', 
                                'MEN\'S WEDDING BAND' => 'MEN\'S WEDDING BAND'
                            ])->searchable()->columnSpan(2),

                        Select::make('metal_type')
                            ->options([
                                '10K YG' => '10K YG', 
                                '14K WG' => '14K WG', 
                                '18K Gold' => '18K Gold',
                                'Platinum' => 'Platinum'
                            ])->columnSpan(2),

                        // ROW 4: NEWLY MIGRATED FIELDS
                        TextInput::make('size')->label('Item Size')->columnSpan(6),
                        TextInput::make('metal_weight')->label('Metal Weight (g)')->numeric()->columnSpan(6),

                        // ROW 5: PRICING
                        TextInput::make('qty')
                            ->label('Qty')
                            ->numeric()
                            ->default(1)
                            ->columnSpan(2)
                            ->extraInputAttributes(['class' => 'bg-yellow-50 text-center font-bold']),
                        
                        TextInput::make('cost_price')->label('Cost')->prefix('$')->numeric()->columnSpan(2),
                        TextInput::make('retail_price')->label('Sale Price')->prefix('$')->numeric()->columnSpan(3)
                            ->extraInputAttributes(['class' => 'bg-green-50 font-bold text-green-700']),
                        TextInput::make('web_price')->label('Web Price')->prefix('$')->numeric()->columnSpan(3),
                        TextInput::make('discount_percent')->label('Discount')->suffix('%')->numeric()->columnSpan(2),

                        // ROW 6: STANDALONE DESCRIPTION
                        Textarea::make('custom_description')
                            ->label('Item Description')
                            ->columnSpan(12)
                            ->rows(3),

                        // ROW 7: SYSTEM CODES
                        TextInput::make('barcode')
                            ->label('Stock Number (RFID)')
                            ->placeholder('G-Auto Generate')
                            ->disabled() 
                            ->columnSpan(4),
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
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in_stock' => 'success',
                        'sold' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
               Tables\Actions\ActionGroup::make([
                
                // 1. Edit Function (Added back)
                Tables\Actions\EditAction::make(),

                // 2. View Function (Matching your "Swim" software request)
                Tables\Actions\ViewAction::make()
                    ->color('info'),

                // 3. Print RFID Tag
                Action::make('print_tag')
                    ->label('Print RFID Tag')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->action(function ($record, ZebraPrinterService $service) {
                        $printed = $service->printJewelryTag($record);
                        
                        if ($printed) {
                            Notification::make()
                                ->title('Success')
                                ->body('Tag sent to Zebra printer successfully.')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Printer Error')
                                ->body('Could not connect to the printer at 192.168.1.50.')
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
            ]),
            ])
            ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
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