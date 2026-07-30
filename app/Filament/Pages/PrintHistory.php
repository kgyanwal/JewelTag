<?php

namespace App\Filament\Pages;

use App\Models\PrintLog;
use App\Services\ZebraPrinterService;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;

class PrintHistory extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-printer';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?string $navigationLabel = 'Print History';
    protected static string $view = 'filament.pages.print-history';

    public function table(Table $table): Table
    {
        return $table
            ->query(PrintLog::query()->latest())
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Printed At')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->description(fn(PrintLog $record) => $record->created_at->diffForHumans()),

                Tables\Columns\TextColumn::make('barcode')
                    ->label('Stock No.')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('rfid_code')
                    ->label('RFID Code')
                    ->fontFamily('mono')
                    ->limit(16)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('print_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'rfid'  => 'warning',
                        'bulk'  => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state) => $state === 'success' ? 'success' : 'danger')
                    ->formatStateUsing(fn(string $state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('user_name')
                    ->label('Printed By')
                    ->default('System'),
            ])
            ->filters([
                SelectFilter::make('print_type')->options([
                    'barcode' => 'Barcode', 'rfid' => 'RFID', 'bulk' => 'Bulk',
                ]),
                SelectFilter::make('status')->options([
                    'success' => 'Success', 'failed' => 'Failed',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('reprint')
                    ->label('Reprint')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn(PrintLog $record) => $record->productItem !== null)
                    ->requiresConfirmation()
                    ->action(function (PrintLog $record, ZebraPrinterService $service) {
                        $item = $record->productItem;
                        if (!$item) {
                            Notification::make()->title('Original item no longer exists')->danger()->send();
                            return;
                        }
                        $success = $service->printJewelryTag($item, $record->print_type === 'rfid');
                        Notification::make()
                            ->title($success ? 'Reprint sent' : 'Reprint failed')
                            ->{$success ? 'success' : 'danger'}()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }
}