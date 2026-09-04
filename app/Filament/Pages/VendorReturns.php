<?php

namespace App\Filament\Pages;

use App\Models\VendorReturn;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms;
use Filament\Notifications\Notification;

class VendorReturns extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';
    protected static ?string $navigationLabel = 'Vendor Returns';
    protected static ?string $navigationGroup = 'Inventory';
    protected static string $view = 'filament.pages.vendor-returns';

    public function table(Table $table): Table
    {
        return $table
            ->query(VendorReturn::query()->with(['productItem', 'supplier', 'refund']))
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock_no')
                    ->label('Stock #')
                    ->weight('bold')
                    ->copyable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('productItem.custom_description')
                    ->label('Item')
                    ->limit(40)
                    ->wrap(),

                Tables\Columns\TextColumn::make('supplier.company_name')
                    ->label('Vendor')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                Tables\Columns\TextColumn::make('cost_price')
                    ->label('Cost')
                    ->money('USD'),

                Tables\Columns\TextColumn::make('return_credit_expected')
                    ->label('Credit Expected')
                    ->money('USD')
                    ->color('success')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'pending'  => 'gray',
                        'shipped'  => 'warning',
                        'credited' => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn($state) => match ($state) {
                        'pending'  => '⏳ Pending Pickup/Ship',
                        'shipped'  => '📮 Shipped to Vendor',
                        'credited' => '✅ Credit Received',
                        'rejected' => '❌ Rejected by Vendor',
                        default    => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('rma_number')
                    ->label('RMA #')
                    ->placeholder('—')
                    ->copyable(),

                Tables\Columns\TextColumn::make('refund.refund_no')
                    ->label('Refund #')
                    ->url(fn($record) => $record->refund_id
                        ? \App\Filament\Resources\RefundResource::getUrl('edit', ['record' => $record->refund_id])
                        : null)
                    ->color('info'),

                Tables\Columns\TextColumn::make('processedBy.name')
                    ->label('Processed By')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'  => 'Pending Pickup/Ship',
                        'shipped'  => 'Shipped to Vendor',
                        'credited' => 'Credit Received',
                        'rejected' => 'Rejected by Vendor',
                    ]),
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Vendor')
                    ->relationship('supplier', 'company_name')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\Action::make('mark_shipped')
                    ->label('Mark Shipped')
                    ->icon('heroicon-o-truck')
                    ->color('warning')
                    ->visible(fn(VendorReturn $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\TextInput::make('rma_number')
                            ->label('RMA / Tracking Number')
                            ->placeholder('e.g. RMA-2026-0091'),
                    ])
                    ->action(function (VendorReturn $record, array $data) {
                        $record->update([
                            'status'     => 'shipped',
                            'rma_number' => $data['rma_number'] ?? null,
                            'shipped_at' => now(),
                        ]);
                        Notification::make()->title('Marked as Shipped')->success()->send();
                    }),

                Tables\Actions\Action::make('mark_credited')
                    ->label('Confirm Credit')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn(VendorReturn $record) => $record->status === 'shipped')
                    ->form([
                        Forms\Components\TextInput::make('return_credit_expected')
                            ->label('Actual Credit Received')
                            ->numeric()
                            ->prefix('$')
                            ->default(fn(VendorReturn $record) => $record->return_credit_expected)
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->action(function (VendorReturn $record, array $data) {
                        $record->update([
                            'status'                 => 'credited',
                            'return_credit_expected' => $data['return_credit_expected'],
                            'credited_at'            => now(),
                        ]);
                        Notification::make()->title('Vendor Credit Confirmed')->success()->send();
                    }),

                Tables\Actions\Action::make('mark_rejected')
                    ->label('Mark Rejected')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(VendorReturn $record) => in_array($record->status, ['pending', 'shipped']))
                    ->requiresConfirmation()
                    ->modalDescription('Vendor declined this return — the item stays out of your sellable stock. You may need to intervene manually.')
                    ->action(fn(VendorReturn $record) => $record->update(['status' => 'rejected'])),
            ])
            ->defaultSort('created_at', 'desc');
    }
}