<?php

namespace App\Filament\Resources;

use App\Models\ProductItem;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ArchivedStockResource extends Resource
{
    protected static ?string $model = ProductItem::class;
    protected static ?string $navigationIcon = 'heroicon-o-archive-box-x-mark';
    protected static ?string $navigationGroup = 'Admin';
    protected static ?string $navigationLabel = 'Deleted Stocks';

    /**
     * Restriction: Only Superadmin can see this page
     */
    public static function shouldRegisterNavigation(): bool
    {
        // 🔹 Use your Staff helper to check the identity of the person who entered the PIN
        $staff = \App\Helpers\Staff::user();

        // Only allow specific roles to see the Administration menu
        return $staff?->hasAnyRole(['Superadmin']) ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            /** * 🔹 The Magic: Only show items that have been "deleted"
             * This separates the backup from your active "in_stock" items.
             */
            ->modifyQueryUsing(fn (Builder $query) => $query->onlyTrashed())
            ->columns([
                Tables\Columns\TextColumn::make('barcode')->label('Stock #')->searchable(),
                Tables\Columns\TextColumn::make('custom_description')->label('Description')->limit(40),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted Date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->color('gray'),
            ])
            ->actions([
                /** * 🔹 Restore: This puts the item back into active inventory
                 */
                Tables\Actions\RestoreAction::make()
                    ->label('Restore to Stock')
                    ->color('success'),
                
                Tables\Actions\ForceDeleteAction::make()
                    ->label('Delete Permanently')
            ]);
    }

    /**
     * Necessary for SoftDeletes to work correctly in the table
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\ArchivedStockResource\Pages\ListArchivedStocks::route('/'),
        ];
    }
   
}