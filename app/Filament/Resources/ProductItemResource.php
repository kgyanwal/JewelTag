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
                        Radio::make('creation_mode')
                            ->label('')
                            ->options(['new' => 'Assemble New Stock Item', 'copy' => 'Copy Existing Item'])
                            ->default('new')->inline()->columnSpan(12)->dehydrated(false),

                        Select::make('supplier_id')
                            ->relationship('supplier', 'company_name')
                            ->searchable()->preload()->required()->columnSpan(4),

                        CheckboxList::make('options')
                            ->options(['details' => 'More Details', 'print' => 'Print Tag'])
                            ->columns(2)->columnSpan(8)->dehydrated(false), 

                        TextInput::make('supplier_code')->label('Supplier Code')->columnSpan(3),
                        
                        Select::make('form_type')
                            ->options(['Ring' => 'Ring', 'Watch' => 'Watch', 'Earring' => 'Earring', 'General' => 'General'])
                            ->default('General')->columnSpan(3),

                        Select::make('department')
                            ->options(fn () => collect(Cache::get('inventory_departments', []))->mapWithKeys(fn ($val) => [$val => $val]))
                            ->searchable()->columnSpan(2),

                        Select::make('category')
                            ->options(fn () => collect(Cache::get('inventory_categories', []))->mapWithKeys(fn ($val) => [$val => $val]))
                            ->searchable()->columnSpan(2),

                        Select::make('metal_type')
                            ->options(fn () => collect(Cache::get('inventory_metal_types', []))->mapWithKeys(fn ($val) => [$val => $val]))
                            ->searchable()->columnSpan(2),

                        TextInput::make('size')->columnSpan(6),
                        TextInput::make('metal_weight')->numeric()->columnSpan(6),

                        TextInput::make('qty')->numeric()->default(1)->columnSpan(2)->extraInputAttributes(['class' => 'bg-yellow-50 text-center font-bold']),
                        
                        TextInput::make('cost_price')->prefix('$')->numeric()->columnSpan(2),
                        TextInput::make('retail_price')->prefix('$')->numeric()->columnSpan(3)->extraInputAttributes(['class' => 'bg-green-50 font-bold text-green-700']),
                        TextInput::make('web_price')->prefix('$')->numeric()->columnSpan(3),
                        TextInput::make('discount_percent')->suffix('%')->numeric()->columnSpan(2),

                        Textarea::make('custom_description')->columnSpan(12)->rows(3),

                        TextInput::make('barcode')->label('Stock Number')->placeholder('G-Auto Generate')->disabled()->columnSpan(4),
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
                Tables\Columns\TextColumn::make('status')->badge()->color(fn ($state) => $state === 'in_stock' ? 'success' : 'gray'),
            ])
            ->actions([
               ActionGroup::make([
                EditAction::make(),
                ViewAction::make()->color('info'),
                Action::make('print_tag')
                    ->label('Print RFID Tag')->icon('heroicon-o-printer')->color('info')
                    ->action(function ($record, ZebraPrinterService $service) {
                        if ($service->printJewelryTag($record)) {
                            Notification::make()->title('Success')->body('Tag sent to printer.')->success()->send();
                        } else {
                            Notification::make()->title('Printer Error')->danger()->send();
                        }
                    }),
            ]),
            ]);
    }

    public static function getPages(): array {
        return [
            'index' => Pages\ListProductItems::route('/'),
            'create' => Pages\CreateProductItem::route('/create'),
            'edit' => Pages\EditProductItem::route('/{record}/edit'),
        ];
    }
}