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
                        // 🚀 Auto-fill from URL ?sale_id= when coming from the Sales table Refund button
                        ->default(fn() => request('sale_id') ? (int) request('sale_id') : null)
                        ->afterStateHydrated(function (Set $set, $state) {
                            // When pre-filled from URL, also auto-link the customer
                            if ($state) {
                                $sale = Sale::find($state);
                                if ($sale) {
                                    $set('customer_id', $sale->customer_id);
                                }
                            }
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set, $state) {
                            $set('refunded_items', []);
                            $set('refund_amount', 0);

                            $sale = Sale::find($state);
                            if ($sale) {
                                $set('customer_id', $sale->customer_id);
                            }
                        }),

                    Forms\Components\CheckboxList::make('refunded_items')
                        ->label('Select Items to Refund')
                        ->options(function (Get $get, ?Refund $record) {
                            $saleId = $get('sale_id');
                            if (!$saleId) return [];

                            $sale = Sale::find($saleId);
                            if (!$sale) return [];

                            $alreadyRefundedIds = Refund::where('sale_id', $saleId)
                                ->where('status', 'approved')
                                ->when($record, fn($query) => $query->where('id', '!=', $record->id))
                                ->get()
                                ->pluck('refunded_items')
                                ->flatten()
                                ->toArray();

                           return $sale->items
                                ->whereNotIn('id', $alreadyRefundedIds)
                                ->mapWithKeys(fn($item) => [
                                    $item->id => ($item->productItem->barcode ?? ($item->custom_description ? 'NON-TAG' : 'Stock'))
                                        . " | " . \Illuminate\Support\Str::limit(strip_tags($item->custom_description ?? '—'), 50)
                                        . " — $" . number_format($item->sold_price, 2)
                                ]);
                        })
                        ->visible(fn(Get $get) => $get('sale_id'))
                        ->live()
                        ->columns(2),

                    Forms\Components\Select::make('quality_check')
                        ->options([
                            'excellent' => 'Excellent (Resalable)',
                            'good'      => 'Good',
                            'damaged'   => 'Damaged',
                        ])->required(),

                    Forms\Components\Toggle::make('should_restock')
                        ->label('Return checked items to Stock?')
                        ->default(true),

                    Forms\Components\TextInput::make('refund_amount')
                        ->numeric()->prefix('$')->required(),

                    Forms\Components\Textarea::make('remarks')->columnSpanFull(),

                Forms\Components\Hidden::make('processed_by')->default(auth()->id()),
                    Forms\Components\Hidden::make('customer_id')
                        ->default(function () {
                            $saleId = request('sale_id');
                            if ($saleId) {
                                return Sale::find($saleId)?->customer_id;
                            }
                            return null;
                        }),
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
                    'pending'  => 'warning',
                    default    => 'gray'
                }),
            Tables\Columns\TextColumn::make('refund_amount')->money('USD'),
        ])
            ->actions([
                Tables\Actions\Action::make('approveRefund')
                    ->label('Approve')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn($record) => $record->status === 'pending'
                        && auth()->user()->hasAnyRole(['Superadmin', 'Administration']))
                    ->action(function (Refund $record) {
                        DB::transaction(function () use ($record) {
                            if ($record->should_restock && !empty($record->refunded_items)) {
                                $items = SaleItem::whereIn('id', $record->refunded_items)->get();
                                foreach ($items as $item) {
                                   if ($item->productItem) {
                                        $item->productItem->update([
                                            'status' => 'in_stock',
                                            'qty'    => max(1, $item->productItem->qty + intval($item->qty ?? 1)),
                                        ]);

                                        Restock::create([
                                            'refund_id'       => $record->id,
                                            'product_item_id' => $item->product_item_id,
                                            'stock_no'        => $item->productItem->barcode,
                                            'salesperson_name'=> auth()->user()->name,
                                            'status'          => 'completed',
                                        ]);
                                    }
                                }
                            }

                            $sale = $record->sale;
                            if ($sale) {
                                $totalItemsInSale   = $sale->items()->count();
                                $totalItemsRefunded = collect($record->refunded_items)->count();
                                $newStatus = ($totalItemsRefunded >= $totalItemsInSale)
                                    ? 'refunded'
                                    : 'partially_refunded';
                                $sale->update(['status' => $newStatus]);
                            }

                            \App\Models\Payment::create([
                                'sale_id' => $record->sale_id,
                                'amount'  => -abs($record->refund_amount),
                                'method'  => $record->sale?->payment_method ?? 'cash',
                                'paid_at' => now(),
                            ]);

                            $record->update([
                                'status'      => 'approved',
                                'approved_by' => auth()->id(),
                            ]);
                        });

                        Notification::make()
                            ->title('Refund Approved & Item is now In Stock.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => RefundResource\Pages\ListRefunds::route('/'),
            'create' => RefundResource\Pages\CreateRefund::route('/create'),
            'edit'   => RefundResource\Pages\EditRefund::route('/{record}/edit'),
        ];
    }
}