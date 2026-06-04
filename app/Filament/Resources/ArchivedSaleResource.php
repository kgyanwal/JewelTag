<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArchivedSaleResource\Pages;
use App\Models\Sale;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class ArchivedSaleResource extends Resource
{
    protected static ?string $model = Sale::class;
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Admin';
    protected static ?string $navigationLabel = 'Deleted Sales';
    protected static ?string $slug = 'archived-sales';

    public static function shouldRegisterNavigation(): bool
    {
        $staff = \App\Helpers\Staff::user();
        return $staff?->hasAnyRole(['Superadmin']) ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->onlyTrashed()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Inv #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->fontFamily('mono'),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->formatStateUsing(fn($record) => trim(($record->customer?->name ?? '') . ' ' . ($record->customer?->last_name ?? '')))
                    ->searchable()
                    ->description(fn($record) => $record->customer?->phone),

                TextColumn::make('final_total')
                    ->label('Total')
                    ->money('USD')
                    ->color('danger')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn(string $state) => strtoupper(str_replace('_', ' ', $state))),

                TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('deleted_at')
                    ->label('Deleted On')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->color('danger'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve_deletion')
                    ->label('Approve & Remove')
                    ->icon('heroicon-o-check-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Permanent Deletion?')
                    ->modalDescription('This will return all items to stock, remove all payments from EOD reports, and permanently delete this sale. This cannot be undone.')
                    ->modalSubmitActionLabel('Yes, Approve & Remove')
                    ->visible(fn() => \App\Helpers\Staff::user()?->hasRole('Superadmin') ?? false)
                    ->action(function (Sale $record) {
                        DB::transaction(function () use ($record) {

                            // 1. Return all physical items back to in_stock
                            $record->loadMissing('items');
                            foreach ($record->items as $saleItem) {
                                if (!$saleItem->product_item_id) continue;

                                $productItem = \App\Models\ProductItem::find($saleItem->product_item_id);
                                if ($productItem) {
                                    $productItem->update([
                                        'status'          => 'in_stock',
                                        'qty'             => max(1, $productItem->qty + intval($saleItem->qty ?? 1)),
                                        'hold_reason'     => null,
                                        'held_by_sale_id' => null,
                                    ]);
                                }
                            }

                            // 2. Cancel linked Laybuy plan if exists
                            $laybuy = \App\Models\Laybuy::where('sale_id', $record->id)->first();
                            if ($laybuy) {
                                $laybuy->update(['status' => 'cancelled']);
                            }

                            // 3. Revert linked Custom Orders back to in_production
                            $customOrderIds = $record->items
                                ->pluck('custom_order_id')
                                ->filter();

                            foreach ($customOrderIds as $coId) {
                                \App\Models\CustomOrder::where('id', $coId)->update([
                                    'status'  => 'in_production',
                                    'sale_id' => null,
                                ]);
                            }

                            // 4. Delete all payments so they don't appear in EOD
                            \App\Models\Payment::where('sale_id', $record->id)->delete();
                            DB::table('sale_payments')->where('sale_id', $record->id)->delete();

                            // 5. Permanently delete the sale
                            $record->forceDelete();
                        });

                        Notification::make()
                            ->title('Sale Permanently Removed')
                            ->body('Items returned to stock, payments removed from EOD, sale deleted.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\RestoreAction::make()
                    ->label('Restore Sale')
                    ->color('success')
                    ->visible(fn() => \App\Helpers\Staff::user()?->hasRole('Superadmin') ?? false),
            ])
            ->defaultSort('deleted_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArchivedSales::route('/'),
        ];
    }
}