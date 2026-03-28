<?php

namespace App\Filament\Pages;

use App\Models\Sale;
use App\Models\User;
use App\Models\Store;
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
        $isPrivileged = auth()->user()->hasAnyRole(['Superadmin', 'Administration']);
        $tz           = Store::first()?->timezone ?? config('app.timezone', 'UTC');

        return $table
            ->query(
                Sale::query()
                    ->where('status', 'completed')
                    ->where('balance_due', 0)
                    ->when(!$isPrivileged, function (Builder $query) {
                        // Non-privileged: always locked to their own sales only
                        $query->whereJsonContains('sales_person_list', auth()->user()->name);
                    })
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y')
                    ->timezone($tz)
                    ->sortable(),

                TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->weight('bold')
                    ->color('primary')
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
                    ->label('Subtotal (My Share)')
                    ->alignRight()
                    ->formatStateUsing(function ($state, $record) {
                        $staffList = is_string($record->sales_person_list)
                            ? json_decode($record->sales_person_list, true)
                            : $record->sales_person_list;
                        $count     = is_array($staffList) ? count($staffList) : 1;
                        $perPerson = $state / $count;

                        $html = "<div class='font-bold text-gray-900 text-lg'>$" . number_format($perPerson, 2) . "</div>";

                        if ($count > 1) {
                            $html .= "<div class='text-xs text-gray-400'>Total Invoice: $" . number_format($state, 2) . "</div>";
                            $html .= "<div class='text-[10px] text-primary-600 font-medium uppercase tracking-wider'>Your Share (1/{$count})</div>";
                        } else {
                            $html .= "<div class='text-[10px] text-gray-400 font-medium uppercase tracking-wider'>Full Sale Amount</div>";
                        }

                        return new HtmlString($html);
                    })
                    ->summarize(
                        Summarizer::make()
                            ->label('My Total Share')
                            ->using(function ($query) {
                                $totalShare = 0;
                                foreach ($query->get() as $sale) {
                                    $staffList = is_string($sale->sales_person_list)
                                        ? json_decode($sale->sales_person_list, true)
                                        : $sale->sales_person_list;
                                    $count      = is_array($staffList) ? count($staffList) : 1;
                                    $totalShare += ($sale->subtotal / $count);
                                }
                                return '$' . number_format($totalShare, 2);
                            })
                    ),
            ])
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from')->label('Start Date')->default(now()->startOfMonth()),
                        DatePicker::make('until')->label('End Date')->default(now()),
                    ])
                    ->query(function (Builder $query, array $data) use ($tz): Builder {
                        return $query
                            ->when($data['from'], fn($q) =>
                                $q->where('created_at', '>=', Carbon::parse($data['from'], $tz)->startOfDay()->utc())
                            )
                            ->when($data['until'], fn($q) =>
                                $q->where('created_at', '<=', Carbon::parse($data['until'], $tz)->endOfDay()->utc())
                            );
                    }),

                // Only admins see the associate filter to view other staff sales
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
                    ->hidden(!$isPrivileged),
            ])
            ->defaultSort('created_at', 'desc')
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->filtersTriggerAction(
                fn($action) => $action->label('Filter / Date Range')->icon('heroicon-o-funnel')
            );
    }

    public function getStats(): array
    {
        $isPrivileged = auth()->user()->hasAnyRole(['Superadmin', 'Administration']);
        $tz           = Store::first()?->timezone ?? config('app.timezone', 'UTC');
        $currentUser  = auth()->user()->name;

        $query = Sale::query()
            ->where('status', 'completed')
            ->where('balance_due', 0)
            ->when(!$isPrivileged, fn($q) =>
                // Non-privileged: always their own sales only
                $q->whereJsonContains('sales_person_list', $currentUser)
            );

        // Apply date filters if set
        $filters = $this->getTableFilters();

        if ($dateFilter = ($filters['created_at'] ?? null)) {
            if (!empty($dateFilter['from'])) {
                $query->where('created_at', '>=', Carbon::parse($dateFilter['from'], $tz)->startOfDay()->utc());
            }
            if (!empty($dateFilter['until'])) {
                $query->where('created_at', '<=', Carbon::parse($dateFilter['until'], $tz)->endOfDay()->utc());
            }
        }

        // If admin filtered by a specific associate, apply that too
        if ($isPrivileged && !empty($filters['staff_filter']['associate'])) {
            $query->whereJsonContains('sales_person_list', $filters['staff_filter']['associate']);
        }

        $sales    = $query->get();
        $netShare = 0;

        foreach ($sales as $sale) {
            $staffList = is_string($sale->sales_person_list)
                ? json_decode($sale->sales_person_list, true)
                : $sale->sales_person_list;
            $count     = is_array($staffList) ? count($staffList) : 1;
            $netShare += ($sale->subtotal / $count);
        }

        // For admins — also compute store total (unfiltered by staff)
        $storeQuery = Sale::query()
            ->where('status', 'completed')
            ->where('balance_due', 0);

        if ($dateFilter = ($filters['created_at'] ?? null)) {
            if (!empty($dateFilter['from'])) {
                $storeQuery->where('created_at', '>=', Carbon::parse($dateFilter['from'], $tz)->startOfDay()->utc());
            }
            if (!empty($dateFilter['until'])) {
                $storeQuery->where('created_at', '<=', Carbon::parse($dateFilter['until'], $tz)->endOfDay()->utc());
            }
        }

        return [
            'count'        => $sales->count(),
            'net_share'    => $netShare,
            'tax'          => $sales->sum('tax_amount'),
            'store_total'  => $isPrivileged ? $storeQuery->sum('subtotal') : null,
            'is_privileged'=> $isPrivileged,
            'user_name'    => $currentUser,
        ];
    }
}