<?php

namespace App\Filament\Pages;

use App\Models\Sale;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Illuminate\Support\HtmlString;

class MySalesReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationGroup = 'Analytics & Reports';
    protected static ?string $navigationLabel = 'My Sales Report';
    protected static ?string $title = 'Sales Performance Report';
    protected static string $view = 'filament.pages.my-sales-report';

    public function table(Table $table): Table
    {
        // 🚀 CRITICAL: Define privilege check once for reuse
        $isPrivileged = auth()->user()->hasAnyRole(['Superadmin', 'Administration']);

        return $table
            ->query(
                Sale::query()
                    ->where('status', 'completed')
                    // 🔒 SECURITY: Strictly filter by the logged-in user if not an Admin
                    ->when(!$isPrivileged, function (Builder $query) {
                        $query->whereJsonContains('sales_person_list', auth()->user()->name);
                    })
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y')
                    ->sortable(),

                TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->searchable(),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->formatStateUsing(fn($record) => "{$record->customer->name} {$record->customer->last_name}")
                    ->searchable(),

                TextColumn::make('sales_person_list')
                    ->label('Associates')
                    ->badge()
                    ->separator(','),

                TextColumn::make('subtotal')
                    ->label('Subtotal (Ex. Tax)')
                    ->alignRight()
                    ->formatStateUsing(function ($state, $record) {
                        $count = is_array($record->sales_person_list) ? count($record->sales_person_list) : 1;
                        $perPerson = $state / $count;
                        
                        $html = "<div class='font-bold text-gray-900'>$" . number_format($state, 2) . "</div>";
                        if ($count > 1) {
                            $html .= "<div class='text-xs text-primary-600 italic'>$" . number_format($perPerson, 2) . " total sold amount</div>";
                        }
                        return new HtmlString($html);
                    })
                    ->summarize(
                        Summarizer::make()
                            ->label('Individual Split Total')
                            ->using(function ($query) {
                                $sales = $query->get();
                                $totalIndividualShare = 0;

                                foreach ($sales as $sale) {
                                    $staffList = is_string($sale->sales_person_list) 
                                        ? json_decode($sale->sales_person_list, true) 
                                        : $sale->sales_person_list;
                                        
                                    $count = is_array($staffList) ? count($staffList) : 1;
                                    $totalIndividualShare += ($sale->subtotal / $count);
                                }
                                return "$" . number_format($totalIndividualShare, 2);
                            })
                    ),
            ])
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from')->label('Start Date')->default(now()->startOfMonth()),
                        DatePicker::make('until')->label('End Date')->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn($q) => $q->whereDate('created_at', '<=', $data['until']));
                    }),

                // 👥 ROLE-BASED ASSOCIATE FILTER FIX
                Filter::make('staff_filter')
                    ->label('Associate')
                    ->form([
                        Select::make('associate')
                            ->options(User::pluck('name', 'name'))
                            ->placeholder('All Associates'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['associate'],
                            fn(Builder $q, $state) => $q->whereJsonContains('sales_person_list', $state)
                        );
                    })
                    // 🚀 HIDDEN: This prevents Rabin from seeing the "Associate" dropdown entirely
                    ->hidden(!$isPrivileged),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function getViewData(): array
    {
        $isPrivileged = auth()->user()->hasAnyRole(['Superadmin', 'Administration']);

        $query = Sale::query()
            ->where('status', 'completed')
            ->when(!$isPrivileged, function ($q) {
                // 🔒 Security: Only query Rabin's sales if logged in as Rabin (Sales role)
                $q->whereJsonContains('sales_person_list', auth()->user()->name);
            });

        $filters = $this->getTableFilters();
        
        if ($dateFilter = ($filters['created_at'] ?? null)) {
            $query->when($dateFilter['from'], fn($q) => $q->whereDate('created_at', '>=', $dateFilter['from']));
            $query->when($dateFilter['until'], fn($q) => $q->whereDate('created_at', '<=', $dateFilter['until']));
        }
        
        // Final Individual Share Calculation
        $sales = $query->get();
        $individualSubtotal = 0;
        foreach ($sales as $sale) {
            $staffList = is_string($sale->sales_person_list) 
                ? json_decode($sale->sales_person_list, true) 
                : $sale->sales_person_list;

            $count = is_array($staffList) ? count($staffList) : 1;
            $individualSubtotal += ($sale->subtotal / $count);
        }

        return [
            'isPrivileged' => $isPrivileged,
            'totalSalesCount' => $sales->count(),
            'aggregateSubtotal' => $individualSubtotal,
            'totalTax' => $query->sum('tax_amount'),
        ];
    }
}