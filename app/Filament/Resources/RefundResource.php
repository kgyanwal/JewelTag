<?php

namespace App\Filament\Resources;

use App\Models\Refund;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Restock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class RefundResource extends Resource
{
    protected static ?string $model = Refund::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationGroup = 'Sales';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Partial Refund Selection')
                ->schema([
                    // 🔹 FIX: Automatically generate Refund # and send to DB
                    Forms\Components\TextInput::make('refund_no')
                        ->label('Refund Receipt #')
                        ->default(fn() => 'RFD-' . strtoupper(bin2hex(random_bytes(4))))
                        ->readOnly()
                        ->dehydrated()
                        ->required(),

                    Forms\Components\Select::make('sale_id')
                        ->label('Original Sale / Invoice')
                        ->relationship(
                            'sale',
                            'invoice_number',
                            fn(Builder $query) => $query->where('status', 'completed')
                        )
                        ->searchable()->preload()->required()->live()
                        ->afterStateUpdated(function (Set $set, $state) {
                            $set('refunded_items', []);
                            $set('refund_amount', 0);

                            // Auto-link customer from the sale
                            $sale = Sale::find($state);
                            if ($sale) {
                                $set('customer_id', $sale->customer_id);
                            }
                        }),

                    // 🔹 Dynamic Checkbox List for Item Selection
                    Forms\Components\CheckboxList::make('refunded_items')
    ->label('Select Items to Refund')
    ->options(function (Get $get, ?Refund $record) {
        $saleId = $get('sale_id');
        if (!$saleId) return [];
        
        $sale = Sale::find($saleId);
        if (!$sale) return [];

        // 1. Get IDs of items already refunded for this sale
        $alreadyRefundedIds = Refund::where('sale_id', $saleId)
            ->where('status', 'approved')
            // Exclude the current record if we are in Edit mode
            ->when($record, fn ($query) => $query->where('id', '!=', $record->id))
            ->get()
            ->pluck('refunded_items')
            ->flatten()
            ->toArray();

        // 2. Filter the options to only show items NOT yet refunded
        return $sale->items
            ->whereNotIn('id', $alreadyRefundedIds)
            ->mapWithKeys(fn ($item) => [
                $item->id => ($item->productItem->barcode ?? 'Stock') . " | " . $item->custom_description . " ($" . number_format($item->sold_price, 2) . ")"
            ]);
    })
    ->visible(fn (Get $get) => $get('sale_id'))
    ->live()
    ->columns(2),

                    Forms\Components\Select::make('quality_check')
                        ->options([
                            'excellent' => 'Excellent (Resalable)',
                            'good' => 'Good',
                            'damaged' => 'Damaged',
                        ])->required(),

                    Forms\Components\Toggle::make('should_restock')
                        ->label('Return checked items to Stock?')
                        ->default(true),

                    Forms\Components\TextInput::make('refund_amount')
                        ->numeric()->prefix('$')->required(),

                    Forms\Components\Textarea::make('remarks')->columnSpanFull(),

                    Forms\Components\Hidden::make('processed_by')->default(auth()->id()),
                    Forms\Components\Hidden::make('customer_id'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('refund_no')->label('Refund #')->searchable(),
            Tables\Columns\TextColumn::make('sale.invoice_number')->label('Invoice'),
            Tables\Columns\TextColumn::make('status')->badge()
                ->color(fn($state) => match ($state) {
                    'approved' => 'success',
                    'pending' => 'warning',
                    default => 'gray'
                }),
            Tables\Columns\TextColumn::make('refund_amount')->money('USD'),
        ])
            ->actions([
                Tables\Actions\Action::make('approveRefund')
                    ->label('Approve')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn($record) => $record->status === 'pending' && auth()->user()->hasAnyRole(['Superadmin', 'Administration']))
                    ->action(function (Refund $record) {
                        DB::transaction(function () use ($record) {
                            if ($record->should_restock && !empty($record->refunded_items)) {
                                $items = SaleItem::whereIn('id', $record->refunded_items)->get();
                                foreach ($items as $item) {
                                    if ($item->productItem) {
                                        // 🔹 CHANGE THIS: Set to 'in_stock' instead of 'refunded'
                                        $item->productItem->update(['status' => 'in_stock']);

                                        // Optional: Still create the Restock record for history/audit
                                        Restock::create([
                                            'refund_id' => $record->id,
                                            'product_item_id' => $item->product_item_id,
                                            'stock_no' => $item->productItem->barcode,
                                            'salesperson_name' => auth()->user()->name,
                                            'status' => 'completed', // Mark as completed immediately
                                        ]);
                                    }
                                }
                            }
                            $record->update(['status' => 'approved', 'approved_by' => auth()->id()]);
                        });
                        Notification::make()->title('Refund Approved & Item is now In Stock.')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => RefundResource\Pages\ListRefunds::route('/'),
            'create' => RefundResource\Pages\CreateRefund::route('/create'),
            'edit' => RefundResource\Pages\EditRefund::route('/{record}/edit'),
        ];
    }
}
