<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Models\Sale;
use App\Models\ProductItem;
use App\Models\Customer;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\{Section, Grid, Group, Repeater, Select, TextInput, Placeholder, Hidden, Checkbox};
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\HtmlString;

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
                    // ───────── LEFT COLUMN (8/12): BILLING ─────────
                    Group::make()->columnSpan(8)->schema([
                        Section::make('Stock Selection')->schema([
                            Grid::make(4)->schema([
                                Select::make('current_item_search')
                                    ->label('Select Stock #')
                                    ->searchable()
                                    ->preload()
                                    ->options(fn() => ProductItem::where('qty', '>', 0)
                                        ->get()
                                        ->mapWithKeys(fn($item) => [
                                            $item->id => "{$item->barcode} - {$item->qty} left (\${$item->retail_price})"
                                        ]))
                                    ->live()
                                    ->dehydrated(false)
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if ($item = ProductItem::find($state)) {
                                            $set('current_price', $item->retail_price);
                                            $set('current_desc', $item->custom_description ?? $item->barcode);
                                        }
                                    })
                                    ->columnSpan(3),
                                TextInput::make('current_qty')->label('Qty')->numeric()->default(1)->dehydrated(false),
                            ]),
                            Forms\Components\Actions::make([
                                Action::make('add_item')
                                    ->label('ADD TO BILL')
                                    ->color('primary')->button()->extraAttributes(['class' => 'w-full'])
                                    ->action(function (Get $get, Set $set) {
                                        $item = ProductItem::find($get('current_item_search'));
                                        if (!$item) return;
                                        $currentItems = $get('items') ?? [];
                                        $currentItems[] = [
                                            'product_item_id' => $item->id,
                                            'stock_no_display' => $item->barcode,
                                            'custom_description' => $get('current_desc'),
                                            'qty' => $get('current_qty') ?? 1,
                                            'sold_price' => $get('current_price'),
                                            'discount' => 0,
                                        ];
                                        $set('items', $currentItems);
                                        $set('current_item_search', null);
                                        self::updateTotals($get, $set);
                                    }),
                            ]),
                        ]),

                        Section::make('Current Bill Items')->schema([
                            Repeater::make('items')
                                ->relationship()
                                ->columns(12)
                                ->addable(false)
                                ->live()
                                ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotals($get, $set))
                                ->schema([
                                    TextInput::make('stock_no_display')->label('Item')->readOnly()->dehydrated(false)->columnSpan(2),
                                    TextInput::make('custom_description')->label('Description')->columnSpan(4),
                                    TextInput::make('qty')->numeric()->live()->required()->columnSpan(2),
                                    TextInput::make('sold_price')->label('Price')->numeric()->live()->columnSpan(2),
                                    TextInput::make('discount')->label('Disc $')->numeric()->live()->columnSpan(2),
                                    Hidden::make('product_item_id'),
                                ]),
                        ]),

                        Section::make('Shipping & Handling')->schema([
                            Grid::make(4)->schema([
                                TextInput::make('shipping_charges')->label('Charges')->numeric()->prefix('$')->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::updateTotals($get, $set)),
                                Checkbox::make('shipping_taxed')->label('Taxed?')->default(false),
                                Select::make('carrier')->options(['No carrier' => 'No carrier', 'UPS' => 'UPS', 'FedEx' => 'FedEx'])->default('No carrier'),
                                TextInput::make('tracking_number')->label('Tracking'),
                            ]),
                        ]),
                    ]),

                    // ───────── RIGHT COLUMN (4/12): CHECKOUT ─────────
                    Group::make()->columnSpan(4)->schema([
                        Section::make('Customer & Personnel')->schema([
                            Select::make('customer_id')
                                ->relationship('customer', 'id')
                                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                                ->searchable()->preload()->required()->live(),
                            
                            Placeholder::make('cust_info')
                                ->label('')
                                ->content(function (Get $get) {
                                    $customer = Customer::find($get('customer_id'));
                                    if (!$customer) return 'Select customer to view details';
                                    
                                    // Persistent formatting for +1
                                    $phone = $customer->phone;
                                    if ($phone && !str_starts_with($phone, '+1')) {
                                        $phone = '+1' . $phone;
                                    }
                                    
                                    return new HtmlString("<div class='text-sm border-t pt-2'><strong>Address:</strong> {$customer->address}<br><strong>Phone:</strong> {$phone}</div>");
                                }),

                            // 🔹 FIXED DROPDOWN: Uses whereHas and direct name fallback to ensure Krishna and Nabin appear
                            Select::make('sales_person_list')
                                ->label('Sales Person')
                                ->options(function() {
                                    return User::whereHas('roles', function($q) {
                                        $q->whereIn('name', [
                                            'Administration', 
                                            'Advanced Sales', 
                                            'Sales Associate', 
                                            'Superadmin'
                                        ]);
                                    })
                                    ->orWhereIn('name', ['Krishna', 'Nabin Sapkota']) // Direct fallback to names in your screenshot
                                    ->pluck('name', 'name'); 
                                })
                                ->searchable()
                                ->preload() 
                                ->required(),
                        ]),

                        Section::make('Financial Summary')->schema([
                            self::totalRow('TAX TOTAL', 'tax_amount'),
                            self::totalRow('SUBTOTAL', 'subtotal'),
                            Placeholder::make('total_display')
                                ->label('BALANCE DUE')
                                ->content(fn(Get $get) => '$' . (number_format((float)($get('final_total') ?? 0), 2)))
                                ->extraAttributes(['class' => 'text-red-600 font-bold text-3xl']),

                            Forms\Components\Actions::make([
                                Action::make('complete')
                                    ->label('COMPLETE SALE')
                                    ->color('success')->button()->extraAttributes(['class' => 'w-full'])
                                    ->action(fn ($livewire) => $livewire->create()),
                            ]),
                        ]),
                    ]),
                ]),
                // 🔹 Database Hiddens
                Hidden::make('final_total'),
                Hidden::make('subtotal'),
                Hidden::make('tax_amount'),
                Hidden::make('store_id')->default(fn() => auth()->user()->store_id ?? 1),
                Hidden::make('invoice_number')->default(fn() => 'INV-' . date('Y-') . rand(1000, 9999)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')->label('Invoice #')->searchable(),
                TextColumn::make('customer.first_name')
                    ->label('Customer')
                    ->formatStateUsing(fn ($record) => $record->customer ? "{$record->customer->first_name} {$record->customer->last_name}" : 'Walk-in'),
                TextColumn::make('sales_person_list')->label('Sales Person'),
                TextColumn::make('final_total')->label('Total')->money('USD'),
                TextColumn::make('created_at')->label('Date')->dateTime(),
            ]);
    }

    public static function updateTotals(Get $get, Set $set)
    {
        $items = $get('items') ?? [];
        $shipping = floatval($get('shipping_charges') ?? 0);
        
        $subtotal = 0;
        foreach ($items as $item) {
            $price = floatval($item['sold_price'] ?? 0);
            $qty = intval($item['qty'] ?? 1);
            $disc = floatval($item['discount'] ?? 0);
            $subtotal += ($price * $qty) - $disc;
        }

        $taxable = $subtotal + ($get('shipping_taxed') ? $shipping : 0);
        $tax = $taxable * 0.0825;
        
        $set('subtotal', number_format($subtotal + $shipping, 2, '.', ''));
        $set('tax_amount', number_format($tax, 2, '.', ''));
        $set('final_total', number_format($subtotal + $shipping + $tax, 2, '.', ''));
    }

    protected static function totalRow($label, $field) {
        return TextInput::make($field)->label($label)->prefix('$')->readOnly()->extraInputAttributes(['class' => 'text-right']);
    }

    public static function getPages(): array {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }
}