<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RestockResource\Pages;
use App\Models\Restock;
use App\Models\ProductItem;
use App\Services\ZebraPrinterService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RestockResource extends Resource
{
    protected static ?string $model = Restock::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $navigationLabel = 'Restock Queue';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Restock Verification')
                ->description('Perform final quality check before returning to sales floor.')
                ->schema([
                    Forms\Components\TextInput::make('stock_no')
                        ->label('Original Stock Number')
                        ->readOnly(),
                    
                    Forms\Components\TextInput::make('salesperson_name')
                        ->label('Salesperson Handling Restock')
                        ->required(),

                    Forms\Components\Select::make('quality_check')
                        ->options([
                            'excellent' => 'Excellent (As New)',
                            'good' => 'Good (Minor Polish Needed)',
                            'damaged' => 'Damaged (Repair Required)',
                        ])
                        ->required(),

                    Forms\Components\TextInput::make('restock_fee')
                        ->label('Restocking Fee Charged')
                        ->numeric()
                        ->prefix('$'),

                    Forms\Components\Textarea::make('notes')
                        ->label('Condition Notes')
                        ->placeholder('Describe any scratches or issues found...')
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // 🔹 Only show items waiting to be restocked
            ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')) 
            ->columns([
                TextColumn::make('stock_no')
                    ->label('Stock #')
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make('refund.refund_no')
                    ->label('From Refund #')
                    ->color('info'),

                BadgeColumn::make('quality_check')
                    ->colors([
                        'success' => 'excellent',
                        'warning' => 'good',
                        'danger' => 'damaged',
                    ]),

                TextColumn::make('created_at')
                    ->label('Request Date')
                    ->dateTime('M d, Y'),
            ])
            ->actions([
                // 🔹 FINAL AUTHENTICATION & RESTOCK ACTION
                Tables\Actions\Action::make('finalizeRestock')
                    ->label('Complete & Print')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Finalize Restock?')
                    ->modalDescription('This will officially put the item back in the POS stock list.')
                    ->action(function (Restock $record, ZebraPrinterService $service, $livewire) {
                        DB::transaction(function () use ($record) {
                            // 1. Update the actual Product Item to in_stock
                            $record->productItem->update([
                                'status' => 'in_stock'
                            ]);

                            // 2. Mark the restock record as finished
                            $record->update([
                                'status' => 'completed',
                                'finalized_by' => auth()->id(),
                                'finalized_at' => now(),
                            ]);
                        });

                        // 3. Trigger Zebra Print for the new tag
                        if ($record->productItem) {
                            $zpl = $service->getZplCode($record->productItem, false);
                            $livewire->dispatch('zebra-print', zpl: $zpl);
                        }

                        Notification::make()
                            ->title('Item Restocked')
                            ->body("Stock #{$record->stock_no} is now live in POS.")
                            ->success()
                            ->send();
                    }),
                
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRestocks::route('/'),
            'create' => Pages\CreateRestock::route('/create'),
            'edit' => Pages\EditRestock::route('/{record}/edit'),
        ];
    }
}