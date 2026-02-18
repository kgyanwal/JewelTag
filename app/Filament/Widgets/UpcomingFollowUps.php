<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UpcomingFollowUps extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full'; // Full width on dashboard

    public function table(Table $table): Table
    {
        return $table
            ->heading('📅 Upcoming Customer Follow-Ups (Next 14 Days)')
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
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->description(fn (Sale $record) => $record->customer->phone),
                
                Tables\Columns\TextColumn::make('invoice_number')->label('Inv #'),
                
                Tables\Columns\TextColumn::make('follow_up_date')
                    ->date('M d, Y')
                    ->label('1st Follow Up')
                    ->color(fn ($state) => $state <= now() ? 'danger' : 'warning'),

                Tables\Columns\TextColumn::make('second_follow_up_date')
                    ->date('M d, Y')
                    ->label('2nd Follow Up'),

                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items Sold'),
            ])
            ->actions([
                // Quick Action to View Sale or Call
                Tables\Actions\Action::make('call')
                    ->label('Call')
                    ->icon('heroicon-o-phone')
                    ->url(fn (Sale $record) => "tel:{$record->customer->phone}"),
            ]);
    }
}