<?php

namespace App\Filament\Resources;

use App\Models\SaleEditRequest;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class SaleEditRequestResource extends Resource
{
    protected static ?string $model = SaleEditRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';
    protected static ?string $navigationGroup = 'Administration';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sale.invoice_number')
                    ->label('Inv #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Requested By')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
                // 🔹 VIEW THE CHANGES
                Tables\Actions\ViewAction::make()
                    ->infolist(fn ($record) => [
                        \Filament\Infolists\Components\Section::make('Proposed Changes')
                            ->schema([
                                \Filament\Infolists\Components\KeyValueEntry::make('proposed_changes')
                                    ->label('Form Data Snapshot')
                            ])
                    ]),

                // 🔹 APPROVE ACTION
                Tables\Actions\Action::make('approve')
                    ->label('Approve & Update')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'pending' && \App\Helpers\Staff::user()?->hasRole('Superadmin'))
                    ->action(function ($record) {
                        $sale = $record->sale;
                        
                        // Apply the captured JSON changes to the real Sale
                        $sale->update($record->proposed_changes);

                        $record->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                        ]);

                        Notification::make()->title('Sale Updated Successfully')->success()->send();
                    }),

                // 🔹 REJECT ACTION
                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(fn ($record) => $record->update(['status' => 'rejected'])),
            ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Helpers\Staff::user()?->hasAnyRole(['Superadmin', 'Administration']) ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => SaleEditRequestResource\Pages\ListSaleEditRequests::route('/'),
        ];
    }
}