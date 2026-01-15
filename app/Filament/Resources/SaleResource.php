<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Models\Sale;
use App\Models\ProductItem;
use App\Models\SalesAssistant;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\{Section, Grid, Group, Repeater, Select, TextInput, Placeholder, Hidden};
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
     protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'Quick Sale';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(12)->schema([
                    Group::make()->columnSpan(8)->schema([
                        Section::make('Stock Selection')->schema([
                            Grid::make(4)->schema([
                                Select::make('current_item_search')
    ->label('Select Stock #')
    ->searchable()
    ->preload()
    ->options(function () {
        // 🔹 ADVANCED: Show items that have actual quantity left
        return ProductItem::where('qty', '>', 0)
            ->get()
            ->mapWithKeys(fn ($item) => [
                $item->id => "{$item->barcode} - {$item->qty} left (\${$item->retail_price})"
            ]);
    })
                                    ->live()
                                    ->dehydrated(false)
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        $item = ProductItem::find($state);
                                        if ($item) {
                                            $set('current_price', $item->retail_price);
                                            $set('current_desc', $item->productTemplate?->name ?? $item->barcode);
                                            $set('current_qty', 1);
                                        }
                                    })
                                    ->columnSpan(3),

                                TextInput::make('current_qty')->label('Qty')->numeric()->default(1)->dehydrated(false),
                            ]),

                            Forms\Components\Actions::make([
                                Action::make('add_item')
                                    ->label('ADD TO BILL')
                                    ->color('primary')
                                    ->button()
                                    ->extraAttributes(['class' => 'w-full'])
                                    ->action(function (Get $get, Set $set) {
                                        $item = ProductItem::find($get('current_item_search'));
                                        if (!$item) return;

                                        $currentItems = $get('items') ?? [];
                                        $currentItems[] = [
                                            'product_item_id' => $item->id,
                                            'stock_no_display' => $item->barcode,
                                            'custom_description' => $get('current_desc'),
                                            'qty' => $get('current_qty') ?? 1,
                                            'original_price' => $item->retail_price, 
                                            'sold_price' => $get('current_price'),
                                            'discount' => 0,
                                        ];
                                        
                                        $set('items', $currentItems);
                                        $set('current_item_search', null);
                                        self::updateTotals($get, $set);
                                    }),
                            ]),
                        ]),

                        Section::make('Current Bill')->schema([
                            Repeater::make('items')
                                ->relationship()
                                ->columns(6)
                                ->addable(false) 
                                ->live() 
                                ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotals($get, $set))
                                ->schema([
                                    TextInput::make('stock_no_display')->label('Stock #')->readOnly()->dehydrated(false), 
                                    TextInput::make('custom_description')->label('Description'),
                                    TextInput::make('qty')->numeric()->live()->required()->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotals($get, $set)),
                                    TextInput::make('original_price')->label('Price')->prefix('$')->readOnly()->dehydrated(false),
                                    TextInput::make('sold_price')->label('Sold At')->numeric()->live()->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotals($get, $set)),
                                    TextInput::make('discount')->label('Disc ($)')->numeric()->live()->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotals($get, $set)),
                                    Hidden::make('product_item_id'),
                                ]),
                        ]),
                    ]),

                    Group::make()->columnSpan(4)->schema([
                        Section::make('Checkout Details')->schema([
                           Select::make('customer_id')
    ->label('Customer')
    ->relationship(name: 'customer', titleAttribute: 'id') 
    ->getOptionLabelFromRecordUsing(fn (Customer $record) => "{$record->first_name} {$record->last_name}")
    ->searchable(['first_name', 'last_name', 'phone']) // 🔹 Fixed search columns
    ->preload()
    ->required(),

                            Select::make('sales_person_list')
                                ->options(SalesAssistant::pluck('name', 'name'))
                                ->multiple()
                                ->required(),

                            self::totalRow('SUBTOTAL', 'subtotal'),
                            self::totalRow('TAX (8.25%)', 'tax_amount'),
                            
                            Placeholder::make('total_display')
                                ->label('BALANCE DUE')
                                ->content(fn(Get $get) => '$' . (number_format((float)($get('final_total') ?? 0), 2)))
                                ->extraAttributes(['class' => 'text-red-600 font-bold text-3xl']),

                            Forms\Components\Actions::make([
                                Action::make('save_sale')
                                    ->label('COMPLETE & SAVE')
                                    ->color('success')
                                    ->button()
                                    ->extraAttributes(['class' => 'w-full'])
                                    ->action(fn ($livewire) => $livewire->create()),
                                
                                Action::make('print_receipt')
                                    ->label('PRINT RECEIPT')
                                    ->icon('heroicon-o-printer')
                                    ->color('gray')
                                    ->button()
                                    ->extraAttributes(['class' => 'w-full'])
                                    ->url(fn ($record) => $record ? route('sales.receipt', $record) : '#')
                                    ->openUrlInNewTab()
                                    ->visible(fn ($record) => $record !== null),
                            ]),
                        ]),
                    ]),
                ]),
                Hidden::make('final_total'),
                Hidden::make('subtotal'),
                Hidden::make('tax_amount'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')->label('Invoice #')->searchable(),
                // 🔹 FIX: Reaches into the relationship to show name in the List
                TextColumn::make('customer.first_name')
                    ->label('Customer Name')
                    ->formatStateUsing(fn ($record) => $record->customer 
                        ? "{$record->customer->first_name} {$record->customer->last_name}" 
                        : 'Guest')
                    ->sortable(),
                TextColumn::make('final_total')->label('Total')->money('USD'),
                TextColumn::make('created_at')->label('Date')->dateTime(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('print')
                    ->icon('heroicon-o-printer')
                    ->url(fn (Sale $record) => route('sales.receipt', $record))
                    ->openUrlInNewTab(),
            ]);
    }

    public static function updateTotals(Get $get, Set $set)
    {
        $items = $get('items') ?? [];
        $subtotal = 0;
        foreach ($items as $item) {
            $price = floatval($item['sold_price'] ?? 0);
            $qty = intval($item['qty'] ?? 1);
            $disc = floatval($item['discount'] ?? 0);
            $subtotal += ($price - $disc) * $qty;
        }
        $tax = $subtotal * 0.0825;
        $total = $subtotal + $tax;
        $set('subtotal', number_format($subtotal, 2, '.', ''));
        $set('tax_amount', number_format($tax, 2, '.', ''));
        $set('final_total', number_format($total, 2, '.', ''));
    }

    protected static function totalRow($label, $field)
    {
        return TextInput::make($field)->label($label)->prefix('$')->readOnly()
            ->extraInputAttributes(['class' => 'text-right']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }
}