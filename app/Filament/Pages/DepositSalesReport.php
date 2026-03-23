<?php

namespace App\Filament\Pages;

use App\Models\Laybuy;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\HeaderActionsPosition;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\HtmlString;

class DepositSalesReport extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationGroup = 'Analytics & Reports';
    protected static ?string $navigationLabel = 'Deposit Sales Report';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.deposit-sales-report';

    // This adds the "Big Numbers" stats at the top
    protected function getHeaderWidgets(): array
    {
        return [
            // If you have a separate Widget class, call it here. 
            // Otherwise, the table summaries handle the math below.
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Laybuy::query()->latest('start_date'))
            ->columns([
                TextColumn::make('customer.name')
                    ->label('Customer & Job')
                    ->weight(FontWeight::Bold)
                    ->description(fn (Laybuy $record) => "Job #: " . ($record->sale->invoice_number ?? 'Pending'))
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('customer', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                                     ->orWhereHas('sale', fn ($q) => $q->where('invoice_number', 'like', "%{$search}%"));
                    }),

                TextColumn::make('start_date')
                    ->label('Date Sold')
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Invoice Total')
                    ->money('USD')
                    ->alignment('right')
                    ->summarize(Sum::make()->label('Total Invoiced')->money('USD')),

                TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->money('USD')
                    ->color('success')
                    ->alignment('right')
                    ->summarize(Sum::make()->label('Total Collected')->money('USD')),

                TextColumn::make('balance_due')
                    ->label('Balance')
                    ->money('USD')
                    ->weight(FontWeight::Bold)
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray')
                    ->alignment('right')
                    ->summarize(Sum::make()->label('Total Owed')->money('USD')),

                TextColumn::make('staff_list')
                    ->label('Staff')
                    ->badge()
                    ->separator('/')
                    ->toggleable(),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state)))
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'in_progress' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'in_progress' => 'Active Deposits',
                        'completed' => 'Fully Paid',
                    ]),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')->label('From Date'),
                        DatePicker::make('to')->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('start_date', '>=', $data['from']))
                            ->when($data['to'], fn ($q) => $q->whereDate('start_date', '<=', $data['to']));
                    })
            ])
            ->headerActions([
                // 🚀 This adds the EXPORT button like OnSwim
                Action::make('export')
                    ->label('Download CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn () => $this->exportReport()),
            ])
            ->persistFiltersInSession()
            ->defaultSort('start_date', 'desc');
    }

    public function exportReport()
    {
        // Logic to generate CSV of current filtered results
        // You can use a package like Maatwebsite/Laravel-Excel or simple fputcsv
        Notification::make()->title('Exporting report...')->success()->send();
    }
}