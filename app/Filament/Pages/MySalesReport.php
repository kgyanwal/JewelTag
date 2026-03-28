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

    protected static ?string $navigationIcon  = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationGroup = 'Analytics & Reports';
    protected static ?string $navigationLabel = 'My Sales Report';
    protected static ?string $title           = 'Sales Performance Report';
    protected static string  $view            = 'filament.pages.my-sales-report';

    public function table(Table $table): Table
    {
        $isPrivileged = auth()->user()->hasAnyRole(['Superadmin', 'Administration']);
        $tz           = Store::first()?->timezone ?? config('app.timezone', 'UTC');

        return $table
            ->query(
                Sale::query()
                    ->with('customer')
                    ->where('status', 'completed')
                    ->where('balance_due', 0)
                    ->when(!$isPrivileged, function (Builder $query) {
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

                TextColumn::make('customer_name_display')
    ->label('Customer')
    ->getStateUsing(fn($record) =>
        $record->customer
            ? trim($record->customer->name . ' ' . ($record->customer->last_name ?? ''))
            : '—'
    )
    ->searchable(
        query: function (Builder $query, string $search): Builder {
            return $query->whereHas('customer', fn($q) =>
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(name, ' ', last_name) LIKE ?", ["%{$search}%"])
            );
        }
    )
    ->sortable(false),

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
                            : ($record->sales_person_list ?? []);
                        $count     = max(1, is_array($staffList) ? count($staffList) : 1);
                        $perPerson = floatval($state) / $count;

                        $html = "<div class='font-bold text-gray-900 text-lg'>$"
                              . number_format($perPerson, 2) . "</div>";

                        if ($count > 1) {
                            $html .= "<div class='text-xs text-gray-400'>Invoice Total: $"
                                   . number_format($state, 2) . "</div>";
                            $html .= "<div class='text-[10px] text-primary-600 font-medium uppercase tracking-wider'>"
                                   . "Split {$count} ways</div>";
                        } else {
                            $html .= "<div class='text-[10px] text-gray-400 font-medium uppercase tracking-wider'>"
                                   . "Full Sale Amount</div>";
                        }

                        return new HtmlString($html);
                    })
                    ->summarize(
                        Summarizer::make()
                            ->label('My Total Share')
                            ->using(function ($query) {
                                $rows       = $query->get(['subtotal', 'sales_person_list']);
                                $totalShare = 0;
                                foreach ($rows as $sale) {
                                    $staffList  = is_string($sale->sales_person_list)
                                        ? json_decode($sale->sales_person_list, true)
                                        : ($sale->sales_person_list ?? []);
                                    $count      = max(1, is_array($staffList) ? count($staffList) : 1);
                                    $totalShare += ($sale->subtotal / $count);
                                }
                                return '$' . number_format($totalShare, 2);
                            })
                    ),
            ])
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from')
                            ->label('Start Date')
                            ->default(now()->startOfMonth()),
                        DatePicker::make('until')
                            ->label('End Date')
                            ->default(now()),
                    ])
                    ->query(function (Builder $query, array $data) use ($tz): Builder {
                        return $query
                            ->when($data['from'], fn($q) =>
                                $q->where('created_at', '>=',
                                    Carbon::parse($data['from'], $tz)->startOfDay()->utc())
                            )
                            ->when($data['until'], fn($q) =>
                                $q->where('created_at', '<=',
                                    Carbon::parse($data['until'], $tz)->endOfDay()->utc())
                            );
                    }),

                Filter::make('staff_filter')
                    ->label('Associate')
                    ->form([
                        Select::make('associate')
                            ->options(
                                $isPrivileged
                                    ? User::orderBy('name')->pluck('name', 'name')
                                    : []
                            )
                            ->placeholder('All Associates'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!auth()->user()->hasAnyRole(['Superadmin', 'Administration'])) {
                            return $query;
                        }
                        return $query->when(
                            $data['associate'] ?? null,
                            fn(Builder $q, $state) =>
                                $q->whereJsonContains('sales_person_list', $state)
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

        // Use $this->tableFilters (real Livewire property) — getTableFilters() does not exist
        $filters       = $this->tableFilters ?? [];
        $filteredAssoc = $isPrivileged ? ($filters['staff_filter']['associate'] ?? null) : null;

        // non-privileged → always themselves
        // admin + filter → selected associate
        // admin no filter → themselves
        $viewingUser = $filteredAssoc ?? $currentUser;

        $applyDates = function (Builder $q) use ($filters, $tz): Builder {
            if (!empty($filters['created_at']['from'])) {
                $q->where('created_at', '>=',
                    Carbon::parse($filters['created_at']['from'], $tz)->startOfDay()->utc());
            }
            if (!empty($filters['created_at']['until'])) {
                $q->where('created_at', '<=',
                    Carbon::parse($filters['created_at']['until'], $tz)->endOfDay()->utc());
            }
            return $q;
        };

        $personalQuery = Sale::query()
            ->where('status', 'completed')
            ->where('balance_due', 0)
            ->whereJsonContains('sales_person_list', $viewingUser);
        $applyDates($personalQuery);
        $sales = $personalQuery->get(['subtotal', 'tax_amount', 'sales_person_list']);

        $netShare = 0;
        foreach ($sales as $sale) {
            $staffList = is_string($sale->sales_person_list)
                ? json_decode($sale->sales_person_list, true)
                : ($sale->sales_person_list ?? []);
            $count     = max(1, is_array($staffList) ? count($staffList) : 1);
            $netShare += ($sale->subtotal / $count);
        }

        $storeTotal = null;
        if ($isPrivileged) {
            $storeQuery = Sale::query()
                ->where('status', 'completed')
                ->where('balance_due', 0);
            $applyDates($storeQuery);
            $storeTotal = $storeQuery->sum('subtotal');
        }

        return [
            'count'          => $sales->count(),
            'net_share'      => $netShare,
            'tax'            => $sales->sum('tax_amount'),
            'store_total'    => $storeTotal,
            'is_privileged'  => $isPrivileged,
            'user_name'      => $currentUser,
            'viewing_user'   => $viewingUser,
            'filtered_assoc' => $filteredAssoc,
        ];
    }
}