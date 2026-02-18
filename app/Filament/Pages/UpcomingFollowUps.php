<?php

namespace App\Filament\Pages;

use App\Models\Sale;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;

class UpcomingFollowUps extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Sales'; // 👈 Puts it in Sales Menu
    protected static ?string $navigationLabel = 'Follow-Ups Due';
    protected static ?string $title = 'Upcoming Customer Follow-Ups';
    protected static ?int $navigationSort = 3; // Adjust order
    
    protected static string $view = 'filament.pages.upcoming-follow-ups';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Sale::query()
                    ->where('status', 'completed')
                    ->where(function ($query) {
                        // Check both follow-up dates
                        $today = now();
                        $future = now()->addDays(14); // Look 2 weeks ahead

                        $query->whereBetween('follow_up_date', [$today, $future])
                              ->orWhereBetween('second_follow_up_date', [$today, $future]);
                    })
            )
            ->columns([
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->description(fn (Sale $record) => $record->customer->phone ?? 'No Phone'),
                
                TextColumn::make('invoice_number')
                    ->label('Inv #')
                    ->searchable(),
                
                TextColumn::make('follow_up_date')
                    ->date('M d, Y')
                    ->label('1st Follow Up')
                    ->color(fn ($state) => $state <= now() ? 'danger' : 'warning')
                    ->sortable(),

                TextColumn::make('second_follow_up_date')
                    ->date('M d, Y')
                    ->label('2nd Follow Up')
                    ->sortable(),

                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items Sold'),
            ])
            ->actions([
                // Call Button
                Action::make('call')
                    ->label('Call')
                    ->icon('heroicon-o-phone')
                    ->url(fn (Sale $record) => "tel:{$record->customer->phone}")
                    ->hidden(fn (Sale $record) => empty($record->customer->phone)),

                // Link to the actual sale to see details
                Action::make('view_sale')
                    ->label('View Invoice')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Sale $record) => \App\Filament\Resources\SaleResource::getUrl('edit', ['record' => $record])),
            ])
            ->defaultSort('follow_up_date', 'asc'); // Show closest dates first
    }
}