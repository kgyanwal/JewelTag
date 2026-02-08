<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Models\Sale;
use App\Models\ProductItem;
use App\Models\Customer;
use App\Models\User;
use App\Models\Store;
use App\Models\Repair;
use App\Models\CustomOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\{Section, Grid, Group, Repeater, Select, TextInput, Placeholder, Hidden, Checkbox, Toggle};
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Support\Facades\DB;
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
                    Group::make()->columnSpan(8)->schema([
                        Section::make('Stock Selection')->schema([
                            Grid::make(4)->schema([
                                Select::make('current_item_search')
                                    ->label('Select Stock #')
                                    ->searchable()
                                    ->preload()
                                    ->options(
                                        fn() => ProductItem::where('qty', '>', 0)
                                            ->where('status', 'in_stock')
                                            ->get()
                                            ->mapWithKeys(fn($item) => [
                                                $item->id => "{$item->barcode} - {$item->qty} left (\${$item->retail_price})"
                                            ])
                                    )
                                    ->live()
                                    ->dehydrated(false)
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if ($item = ProductItem::find($state)) {
                                            $set('current_price', $item->retail_price);
                                            $set('current_desc', $item->custom_description ?? $item->barcode);
                                        }
                                    })->columnSpan(3),
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

                                        $currentItems = collect($get('items') ?? [])
                                            ->filter(fn($row) => !empty($row['product_item_id']) || !empty($row['repair_id']))
                                            ->toArray();

                                        $currentItems[] = [
                                            'product_item_id' => $item->id,
                                            'repair_id' => null,
                                            'stock_no_display' => $item->barcode,
                                            'custom_description' => $get('current_desc'),
                                            'qty' => $get('current_qty') ?? 1,
                                            'sold_price' => $get('current_price'),
                                            'discount_percent' => 0,
                                        ];

                                        $set('items', $currentItems);
                                        $set('current_item_search', null);
                                        $set('current_qty', 1);
                                        self::updateTotals($get, $set);
                                    }),
                            ]),
                        ]),
                        Section::make('Trade-In Details')
                            ->schema([
                                Select::make('has_trade_in')
                                    ->label('Is there a Trade-In?')
                                    ->options([1 => 'Yes', 0 => 'No'])
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotals($get, $set)),

                                Grid::make(2)
                                    ->visible(fn(Get $get) => $get('has_trade_in') == 1)
                                    ->schema([
                                        TextInput::make('trade_in_value')
                                            ->label('Trade-In Value (Deduction)')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required(fn(Get $get) => $get('has_trade_in') == 1)
                                            ->live()
                                            ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotals($get, $set)),

                                        TextInput::make('trade_in_receipt_no')
                                            ->label('Trade-In Tracking #')
                                            ->default(fn() => 'TRD-' . date('Ymd-His'))
                                            ->readOnly(),
                                    ]),
                            ]),
                        Section::make('Current Bill Items')->schema([
                            Repeater::make('items')
                                ->relationship('items')
                                ->schema([
                                    Hidden::make('product_item_id')->dehydrated(),
                                    Hidden::make('repair_id')->dehydrated(),
                                    Hidden::make('custom_order_id')->dehydrated(),
                                    TextInput::make('stock_no_display')->label('Item')->readOnly()->dehydrated(false)->columnSpan(2),
                                    TextInput::make('custom_description')->label('Description')->columnSpan(4),
                                    TextInput::make('qty')->numeric()->default(1)->required()->live()
                                        ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                            if ($pid = $get('product_item_id')) {
                                                $productItem = ProductItem::find($pid);
                                                if ($productItem && $state > $productItem->qty) {
                                                    $set('qty', $productItem->qty);
                                                }
                                            }
                                            self::updateTotals($get, $set);
                                        }),
                                    TextInput::make('sold_price')->label('Price')->numeric()->live()
                                        ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotals($get, $set))->columnSpan(2),
                                    TextInput::make('discount_percent')->label('Disc %')->numeric()->suffix('%')->live()
                                        ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotals($get, $set))->columnSpan(2),
                                ])
                                ->columns(12)
                                ->addable(false)
                                ->deletable(true)
                                ->reorderable(false)
                                ->default([])
                                ->live()
                                ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotals($get, $set))
                        ]),

                        Section::make('Shipping & Handling')->schema([
                            Grid::make(4)->schema([
                                TextInput::make('shipping_charges')
                                    ->label('Charges')->numeric()->prefix('$')->default(0)->required()->live()
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::updateTotals($get, $set)),
                                Checkbox::make('shipping_taxed')->label('Taxed?')->default(false),
                                Select::make('carrier')->options(['No carrier' => 'No carrier', 'UPS' => 'UPS', 'FedEx' => 'FedEx'])->default('No carrier'),
                                TextInput::make('tracking_number')->label('Tracking')->nullable(),
                            ]),
                        ]),
                    ]),

                    Group::make()->columnSpan(4)->schema([
                        Section::make('Customer & Personnel')->schema([
                            Select::make('customer_id')
                                ->label('Customer')
                                ->relationship('customer', 'name')
                                ->getOptionLabelFromRecordUsing(fn($record) => "{$record->name}")
                                ->searchable(['name', 'phone'])
                                ->preload()
                                ->required()
                                ->live(),

                            Placeholder::make('cust_info')
                                ->label('')
                                ->content(function (Get $get) {
                                    $customer = Customer::find($get('customer_id'));
                                    if (!$customer) return 'Select customer to view details';
                                    $phone = $customer->phone ?? 'N/A';
                                    return new HtmlString("<div class='text-sm border-t pt-2'><strong>Address:</strong> {$customer->address}<br><strong>Phone:</strong> {$phone}</div>");
                                }),

                            TextInput::make('sales_person_list')
                                ->label('Sales Person')
                                ->default(fn() => auth()->user()->name)
                                ->readOnly()
                                ->required()
                                ->dehydrated(),
                        ]),

                        Section::make('Payment & Status')->schema([
                            Select::make('payment_method')
                                ->label('Payment Method')
                                ->options([
                                    'cash' => 'CASH',
                                    'laybuy' => 'LAYBUY (Installment Plan)',
                                    'visa' => 'VISA',
                                    'comenity' => 'COMENITY',
                                    'debit_card' => 'DEBIT CARD',
                                    'american_express' => 'AMERICAN EXPRESS',
                                    'mastercard' => 'MASTERCARD',
                                    'synchrony' => 'SYNCHRONY',
                                    'affirm' => 'AFFIRM',
                                    'others' => 'OTHERS',
                                ])
                                ->default('cash')->required(),
                            Select::make('status')
                                ->label('Sale Status')
                                ->options(['completed' => 'Completed', 'pending' => 'Pending', 'inprogress' => 'In Progress'])
                                ->default('completed')->required(),
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
                                    ->action(fn($livewire) => $livewire->create()),
                            ]),
                        ]),
                    ]),
                ]),
                Hidden::make('final_total'),
                Hidden::make('subtotal'),
                Hidden::make('tax_amount'),
                Hidden::make('store_id')->default(fn() => auth()->user()->store_id ?? Store::first()?->id ?? 1),
                Hidden::make('invoice_number')->dehydrated(true),
               Hidden::make('custom_order_id')
    ->dehydrated(false), // 👈 Add this: tells Filament NOT to insert into 'sales' table

Hidden::make('repair_id')
    ->dehydrated(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')->label('Inv #')->searchable()->sortable()->grow(false),
                TextColumn::make('customer.name')->label('Customer')->searchable(['name', 'phone'])->sortable(),
                TextColumn::make('items')
                    ->label('Sold Items')
                    ->listWithLineBreaks()
                    ->getStateUsing(function ($record) {
                        return $record->items->map(function ($item) {
                            if ($item->productItem) return $item->productItem->barcode . ' — ' . ($item->productItem->custom_description ?? 'Item');
                            if ($item->repair_id) return "REPAIR: #" . ($item->repair->repair_no ?? 'SVC');
                            return $item->custom_description;
                        })->toArray();
                    })
                    ->limitList(1)->expandableLimitedList()->size('xs')->color('gray'),
                TextColumn::make('payment_method')->label('Method')->badge()->color('gray')->grow(false),
                TextColumn::make('final_total')->label('Total')->money('USD')->weight('bold')->color('success')->alignRight(),
                TextColumn::make('created_at')->label('Date')->dateTime('M d, y')->sortable()->grow(false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')->label('Payment Type')
                    ->options(['cash' => 'CASH', 'laybuy' => 'LAYBUY', 'visa' => 'VISA']),
                Tables\Filters\TernaryFilter::make('has_trade_in')->label('Trade-Ins')
            ])
            ->actions([
                Tables\Actions\EditAction::make()->visible(fn ($record) => $record->status !== 'completed' || auth()->user()->hasRole('Superadmin')),
                Tables\Actions\ViewAction::make()->label('View')->icon('heroicon-o-eye')->color('info'),
                Tables\Actions\Action::make('printReceipt')->label('Receipt')->icon('heroicon-o-printer')->color('info')
                    ->url(fn(Sale $record): string => route('sales.receipt', $record))->openUrlInNewTab(),
                    // Inside SaleResource.php table actions
Tables\Actions\Action::make('emailReceipt')
    ->label('Email Receipt')
    ->icon('heroicon-o-envelope')
    ->color('success')
    ->requiresConfirmation()
    ->action(function (Sale $record) {
        if (!$record->customer || empty($record->customer->email)) {
            \Filament\Notifications\Notification::make()
                ->title('Email Missing')
                ->body('Customer has no email address.')
                ->danger()
                ->send();
            return;
        }

        // Initialize mailable and call the direct send method
        $mailable = new \App\Mail\CustomerReceipt($record);
        $sent = $mailable->sendDirectly();

        if ($sent) {
            \Filament\Notifications\Notification::make()
                ->title('Receipt Sent')
                ->body("Successfully emailed to {$record->customer->email}")
                ->success()
                ->send();
        } else {
            \Filament\Notifications\Notification::make()
                ->title('Email Error')
                ->body('Check Laravel logs for SES details.')
                ->danger()
                ->send();
        }
    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function updateTotals(Get $get, Set $set)
    {
        $items = $get('items') ?? [];
        $shipping = floatval($get('shipping_charges') ?? 0);
        $subtotal = 0;

        foreach ($items as $item) {
            $price = floatval($item['sold_price'] ?? 0);
            $qty = intval($item['qty'] ?? 1);
            $percent = floatval($item['discount_percent'] ?? 0); 
            $rowTotal = $price * $qty;
            $subtotal += ($rowTotal - ($rowTotal * ($percent / 100)));
        }

        $dbTax = DB::table('site_settings')->where('key', 'tax_rate')->value('value') ?? 7.63;
        $tax = ($subtotal + ($get('shipping_taxed') ? $shipping : 0)) * (floatval($dbTax) / 100);
        $tradeIn = ($get('has_trade_in') == 1) ? floatval($get('trade_in_value') ?? 0) : 0;

        $set('subtotal', number_format($subtotal + $shipping, 2, '.', ''));
        $set('tax_amount', number_format($tax, 2, '.', ''));
        $set('final_total', number_format(($subtotal + $shipping + $tax) - $tradeIn, 2, '.', ''));
    }

    protected static function totalRow($label, $field)
    {
        return TextInput::make($field)->label($label)->prefix('$')->readOnly()->extraInputAttributes(['class' => 'text-right']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
            'view' => Pages\ViewSale::route('/{record}'),
        ];
    }
}