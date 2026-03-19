<?php

namespace App\Filament\Pages;

use App\Models\Sale;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables; 
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Enums\FiltersLayout;
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
        $isPrivileged = auth()->user()->hasAnyRole(['Superadmin', 'Administration']);

        return $table
            ->query(
                Sale::query()
                    // 🎯 FIX: Only show sales that are 100% completed
                    ->where('status', 'completed') 
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
                    ->formatStateUsing(fn($record) => "{$record->customer?->name} {$record->customer?->last_name}")
                    ->searchable(),

                TextColumn::make('sales_person_list')
                    ->label('Associates')
                    ->badge()
                    ->separator(','),

                TextColumn::make('subtotal')
                    ->label('Subtotal (Individual Share)')
                    ->alignRight()
                    ->formatStateUsing(function ($state, $record) {
                        $staff = $record->sales_person_list;
                        if (is_string($staff)) $staff = json_decode($staff, true) ?? [$staff];
                        $count = (is_array($staff) && count($staff) > 0) ? count($staff) : 1;
                        
                        $perPerson = $state / $count;
                        
                        $html = "<div class='font-bold text-gray-900 text-lg'>$" . number_format($perPerson, 2) . "</div>";
                        
                        if ($count > 1) {
                            $html .= "<div class='text-xs text-gray-400'>Total Invoice: $" . number_format($state, 2) . "</div>";
                            $html .= "<div class='text-[10px] text-primary-600 font-medium uppercase'>Split 1/{$count}</div>";
                        }
                        
                        return new HtmlString($html);
                    })
                    ->summarize(
                        Summarizer::make()
                            ->label('Total Share')
                            ->using(function ($query) {
                                return "$" . number_format($query->get()->reduce(function ($carry, $sale) {
                                    $staff = $sale->sales_person_list;
                                    if (is_string($staff)) $staff = json_decode($staff, true) ?? [$staff];
                                    $count = (is_array($staff) && count($staff) > 0) ? count($staff) : 1;
                                    return $carry + ($sale->subtotal / $count);
                                }, 0), 2);
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

                Filter::make('staff_filter')
                    ->label('Associate')
                    ->form([
                        Select::make('associate')
                            ->options(User::pluck('name', 'name'))
                            ->placeholder('All Associates'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['associate'], fn($q, $s) => $q->whereJsonContains('sales_person_list', $s));
                    })
                    ->hidden(!$isPrivileged),
            ])
            ->filtersLayout(FiltersLayout::AboveContent) 
            ->defaultSort('created_at', 'desc');
    }

    public function getStats(): array
    {
        $isPrivileged = auth()->user()->hasAnyRole(['Superadmin', 'Administration']);
        
        // 🎯 FIX: Only calculate stats for fully completed sales
        $query = Sale::query()->where('status', 'completed')
            ->when(!$isPrivileged, fn($q) => $q->whereJsonContains('sales_person_list', auth()->user()->name));

        $tableFilters = $this->tableFilters;

        if (!empty($tableFilters['created_at']['from'])) {
            $query->whereDate('created_at', '>=', $tableFilters['created_at']['from']);
        }
        if (!empty($tableFilters['created_at']['until'])) {
            $query->whereDate('created_at', '<=', $tableFilters['created_at']['until']);
        }
        if ($isPrivileged && !empty($tableFilters['staff_filter']['associate'])) {
            $query->whereJsonContains('sales_person_list', $tableFilters['staff_filter']['associate']);
        }

        $sales = $query->get();
        
        $netShare = $sales->reduce(function ($carry, $sale) {
            $staff = $sale->sales_person_list;
            if (is_string($staff)) $staff = json_decode($staff, true) ?? [$staff];
            $count = (is_array($staff) && count($staff) > 0) ? count($staff) : 1;
            return $carry + ($sale->subtotal / $count);
        }, 0);

        return [
            'count' => $sales->count(),
            'net_share' => $netShare,
            'tax' => $sales->sum('tax_amount'),
        ];
    }
}