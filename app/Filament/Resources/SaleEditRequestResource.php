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
    protected static ?string $navigationGroup = 'Sales';

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
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    }),
            ])
            ->actions([
                // 🔹 APPROVE ACTION (Unlocks the sale)
                Tables\Actions\Action::make('approve')
                    ->label('Approve & Unlock')
                    ->icon('heroicon-o-unlock')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(function ($record) {
                        
                        // 🚀 FIX: We just mark it approved so the cashier can now click the Edit button!
                        $record->update([
                            'status'      => 'approved',
                            'approved_by' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('Sale Unlocked')
                            ->body('The cashier can now edit this sale.')
                            ->success()
                            ->send();
                    }),

                // 🔹 REJECT ACTION
                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(function ($record) {
                        $record->update(['status' => 'rejected']);
                        Notification::make()->title('Request Rejected')->danger()->send();
                    }),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return \App\Helpers\Staff::user()?->hasAnyRole(['Superadmin', 'Administration']) || auth()->user()->hasRole('Superadmin');
    }

    public static function getPages(): array
    {
        return [
            'index' => SaleEditRequestResource\Pages\ListSaleEditRequests::route('/'),
        ];
    }
}