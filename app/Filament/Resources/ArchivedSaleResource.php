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

class ArchivedSaleResource extends Resource
{
    protected static ?string $model = Sale::class;
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Admin'; // Keep it separate from normal Sales
    protected static ?string $navigationLabel = 'Deleted Sales';
    protected static ?string $slug = 'archived-sales';

    // 🔒 Only Superadmin can see this menu
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasRole('Superadmin');
    }

    // 🔍 This is the Magic: Only show "Deleted" items
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
                TextColumn::make('invoice_number')->label('Inv #')->searchable(),
                TextColumn::make('customer.name')->label('Customer')->searchable(),
                TextColumn::make('final_total')->label('Total')->money('USD')->color('danger'),
                TextColumn::make('deleted_at')->label('Archived Date')->dateTime(),
            ])
            ->actions([
                // Restore Button (Brings sale back & marks items as Sold)
                Tables\Actions\RestoreAction::make()
                    ->label('Restore')
                    ->color('success'),
                
                // Permanent Delete (If you want to fully remove it)
                Tables\Actions\ForceDeleteAction::make(),
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