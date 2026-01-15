<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockTransferResource\Pages;
use App\Models\StockTransfer;
use App\Models\ProductItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class StockTransferResource extends Resource
{
    protected static ?string $model = StockTransfer::class;
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Stock Transfers';
    protected static ?string $navigationGroup = 'Inventory';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Shipping Details')->schema([
                    TextInput::make('transfer_number')
                        ->default('TRF-' . date('Ymd') . '-' . rand(100,999))
                        ->required()
                        ->readOnly(),
                    
                    Select::make('from_store_id')
                        ->relationship('fromStore', 'name')
                        ->label('From (Source)')
                        ->required()
                        ->reactive(), 

                    Select::make('to_store_id')
                        ->relationship('toStore', 'name')
                        ->label('To (Destination)')
                        ->required()
                        ->different('from_store_id'),
                        
                    Select::make('status')
                        ->options([
                            'pending' => 'Draft / Packing',
                            'in_transit' => 'In Transit (Sent)',
                            'completed' => 'Completed (Received)',
                        ])
                        ->default('pending')
                        ->disabled(),
                ])->columns(2),

                Section::make('Items to Move')->schema([
                    Repeater::make('items')
                        ->relationship()
                        ->schema([
                            Select::make('product_item_id')
                                ->label('Scan Item (Barcode)')
                                ->searchable()
                                ->required()
                                // Only show items that are currently in the SOURCE store
                                ->options(function (Forms\Get $get) {
                                    $fromStoreId = $get('../../from_store_id');
                                    if (!$fromStoreId) return [];
                                    
                                    return ProductItem::where('store_id', $fromStoreId)
                                        ->where('status', 'in_stock')
                                        ->pluck('barcode', 'id');
                                })
                        ])
                        ->columns(1)
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transfer_number')->searchable(),
                Tables\Columns\TextColumn::make('fromStore.name')->label('From'),
                Tables\Columns\TextColumn::make('toStore.name')->label('To'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'in_transit' => 'warning',
                        'completed' => 'success',
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                // Button to Ship
                Tables\Actions\Action::make('ship')
                    ->label('Ship Items')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (StockTransfer $record) => $record->status === 'pending')
                    ->action(fn (StockTransfer $record) => $record->markAsSent()),

                // Button to Receive
                Tables\Actions\Action::make('receive')
                    ->label('Receive Items')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (StockTransfer $record) => $record->status === 'in_transit')
                    ->action(fn (StockTransfer $record) => $record->markAsReceived()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockTransfers::route('/'),
            'create' => Pages\CreateStockTransfer::route('/create'),
            'edit' => Pages\EditStockTransfer::route('/{record}/edit'),
        ];
    }
}