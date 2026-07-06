<?php

namespace App\Filament\Pages;

use App\Models\Sale;
use App\Models\Store;
use App\Models\Customer;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB;

class WarrantyExpiryReminders extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'Warranty Reminders';
    protected static ?string $title           = 'Warranty Expiry Reminders';
    protected static string  $view            = 'filament.pages.warranty-expiry-reminders';

    // Filter: how many days ahead to look
    public string $window = '60'; // 30 | 60 | 90 | expired

    public ?array $data = [];

    public function mount(): void
    {
        $this->data = ['window' => $this->window];
        $this->form->fill($this->data);
    }

    public function updated($property): void
    {
        if (str_starts_with($property, 'data.')) {
            $this->window = $this->data['window'] ?? '60';
            $this->resetTable();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                \Filament\Forms\Components\Select::make('window')
                    ->label('Show warranties expiring')
                    ->options([
                        '30'      => 'Within 30 days',
                        '60'      => 'Within 60 days',
                        '90'      => 'Within 90 days',
                        'expired' => 'Already expired (overdue)',
                    ])
                    ->default('60')
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(fn() => $this->resetTable()),
            ]);
    }

    // ── Parse warranty_period to number of months ──────────────────────────
    // Handles: "12 months", "1 year", "2 years", "6 months", null → default 12
    private static function warrantyMonths(?string $period = null): int
    {
        if (!$period) return 12;
        $period = strtolower(trim($period));
        if (preg_match('/(\d+)\s*year/', $period, $m)) return intval($m[1]) * 12;
        if (preg_match('/(\d+)\s*month/', $period, $m)) return intval($m[1]);
        if (is_numeric($period)) return intval($period);
        return 12;
    }

    // ── Compute expiry date expression in SQL ─────────────────────────────
    // warranty_period stored as "12 months", "1 year", etc.
    // We extract the month count and add to completed_at in SQL.
    // Fallback: 12 months if warranty_period is null/empty.
private static function expiryExpr(): string
{
    // Simpler expression: always add 12 months as SQL default,
    // then fix in PHP display. The real filtering uses PHP below.
    return "DATE_ADD(COALESCE(completed_at, created_at), INTERVAL 12 MONTH)";
}

protected function getTableQuery(): Builder
{
    $window = $this->data['window'] ?? $this->window ?? '60';
    $today  = now()->toDateString();

    // Load all warranty sales then filter in PHP using the same
    // warrantyMonths() helper — avoids REGEXP_SUBSTR MySQL issues.
    $allIds = Sale::where('has_warranty', 1)
        ->where('status', 'completed')
        ->whereNull('deleted_at')
        ->whereNotNull('customer_id')
        ->get(['id', 'completed_at', 'created_at', 'warranty_period'])
        ->filter(function ($sale) use ($window, $today) {
            $months  = self::warrantyMonths($sale->warranty_period);
            $start   = $sale->completed_at ?? $sale->created_at;
            $expiry  = \Carbon\Carbon::parse($start)->addMonths($months)->toDateString();

            if ($window === 'expired') {
                return $expiry < $today;
            }

            $days = intval($window);
            $cutoff = now()->addDays($days)->toDateString();
            return $expiry >= $today && $expiry <= $cutoff;
        })
        ->pluck('id');

    return Sale::query()
        ->with(['customer', 'items'])
        ->whereIn('id', $allIds)
        ->whereNull('deleted_at')
        ->orderByRaw(self::expiryExpr() . ' ASC');
}

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('expiry_date')
                    ->label('Warranty Expires')
                    ->getStateUsing(function ($record) {
                        $months  = self::warrantyMonths($record->warranty_period);
                        $start   = $record->completed_at ?? $record->created_at;
                        $expiry  = Carbon::parse($start)->addMonths($months);
                        $daysLeft = now()->diffInDays($expiry, false);
                        $expired  = $daysLeft < 0;

                        $dateStr = $expiry->format('M d, Y');

                        if ($expired) {
                            $label = abs((int)$daysLeft) . 'd overdue';
                            $color = '#B8463F';
                            $bg    = '#fef2f2';
                        } elseif ($daysLeft <= 14) {
                            $label = (int)$daysLeft . 'd left';
                            $color = '#B8463F';
                            $bg    = '#fef2f2';
                        } elseif ($daysLeft <= 30) {
                            $label = (int)$daysLeft . 'd left';
                            $color = '#92400e';
                            $bg    = '#fffbeb';
                        } else {
                            $label = (int)$daysLeft . 'd left';
                            $color = '#065f46';
                            $bg    = '#ecfdf5';
                        }

                        return new HtmlString("
                            <div style='font-weight:700;color:var(--jt-ink);font-size:13px;'>{$dateStr}</div>
                            <span style='background:{$bg};color:{$color};font-weight:700;font-size:10px;padding:2px 8px;border-radius:999px;display:inline-block;margin-top:3px;'>{$label}</span>
                        ");
                    })
                    ->html()
                    ->sortable(query: fn(Builder $q, string $d) =>
                        $q->orderByRaw(self::expiryExpr() . " {$d}")
                    ),

                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->getStateUsing(fn($record) => $record->customer
                        ? trim($record->customer->name . ' ' . ($record->customer->last_name ?? ''))
                        : '—'
                    )
                    ->description(fn($record) => $record->customer?->phone ?? $record->customer?->email ?? null)
                    ->searchable(query: fn(Builder $q, string $s) =>
                        $q->whereHas('customer', fn($sq) =>
                            $sq->where('name', 'like', "%{$s}%")
                               ->orWhere('last_name', 'like', "%{$s}%")
                               ->orWhere('phone', 'like', "%{$s}%")
                        )
                    )
                    ->weight('bold'),

                TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->weight('bold')
                    ->color('primary')
                    ->url(fn($record) => \App\Filament\Resources\SaleResource::getUrl('edit', ['record' => $record]))
                    ->searchable(),

                TextColumn::make('warranty_info')
                    ->label('Warranty')
                    ->getStateUsing(function ($record) {
                        $period  = $record->warranty_period ?? '12 months';
                        $charge  = floatval($record->warranty_charge ?? 0);
                        return new HtmlString("
                            <div style='font-size:12px;font-weight:600;color:var(--jt-ink);'>{$period}</div>
                            <div style='font-size:11px;color:#64748b;margin-top:2px;'>Paid: $" . number_format($charge, 2) . "</div>
                        ");
                    })
                    ->html(),

                TextColumn::make('completed_at')
                    ->label('Sale Date')
                    ->date('M d, Y')
                    ->description(fn($record) => $record->invoice_number)
                    ->color('gray')
                    ->size('sm'),

                TextColumn::make('items_summary')
                    ->label('Items Sold')
                    ->getStateUsing(function ($record) {
                        $items = $record->items ?? collect();
                        if ($items->isEmpty()) return '—';
                        $first = $items->first();
                        $desc  = $first?->custom_description ?? $first?->stock_no_display ?? 'Item';
                        $count = $items->count();
                        $extra = $count > 1 ? " +".($count-1)." more" : '';
                        return new HtmlString(
                            "<span style='font-size:12px;color:var(--jt-ink);'>" .
                            \Illuminate\Support\Str::limit($desc, 40) . "</span>" .
                            ($extra ? "<span style='font-size:11px;color:#94a3b8;'>{$extra}</span>" : '')
                        );
                    })
                    ->html(),

                TextColumn::make('follow_up_status')
                    ->label('Follow-up')
                    ->getStateUsing(function ($record) {
                        if ($record->follow_up_date) {
                            $date = Carbon::parse($record->follow_up_date);
                            $past = $date->isPast();
                            $color = $past ? '#B8463F' : '#065f46';
                            $bg    = $past ? '#fef2f2' : '#ecfdf5';
                            $label = ($past ? 'Was due ' : 'Due ') . $date->format('M d');
                            return new HtmlString(
                                "<span style='background:{$bg};color:{$color};font-size:10px;font-weight:700;padding:2px 8px;border-radius:999px;'>{$label}</span>"
                            );
                        }
                        return new HtmlString(
                            "<span style='background:#f1f5f9;color:#64748b;font-size:10px;font-weight:600;padding:2px 8px;border-radius:999px;'>Not set</span>"
                        );
                    })
                    ->html(),
            ])
            ->actions([
                TableAction::make('set_followup')
                    ->label('Set Call Reminder')
                    ->icon('heroicon-o-phone')
                    ->color('primary')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('follow_up_date')
                            ->label('Reminder Date')
                            ->required()
                            ->default(now()->addDays(3)->format('Y-m-d'))
                            ->minDate(now()->toDateString()),
                        \Filament\Forms\Components\Textarea::make('notes')
                            ->label('Call Notes (optional)')
                            ->placeholder('e.g. Call to schedule inspection before warranty expires')
                            ->rows(2),
                    ])
                    ->modalHeading(fn($record) => 'Set Reminder — ' . ($record->customer?->name ?? 'Customer'))
                    ->modalDescription(fn($record) => 'This will set a follow-up date on invoice ' . $record->invoice_number . ' to remind you to call about the warranty inspection.')
                    ->action(function ($record, array $data) {
                        $record->update([
                            'follow_up_date' => $data['follow_up_date'],
                            'notes'          => $data['notes']
                                ? ($record->notes ? $record->notes . "\n---\n" . $data['notes'] : $data['notes'])
                                : $record->notes,
                        ]);
                        Notification::make()
                            ->title('Reminder Set')
                            ->body('Follow-up scheduled for ' . Carbon::parse($data['follow_up_date'])->format('M d, Y'))
                            ->success()
                            ->send();
                    }),

                TableAction::make('log_called')
                    ->label('Mark Called')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Called?')
                    ->modalDescription(fn($record) => 'This will set today as the second follow-up date on ' . $record->invoice_number . ', indicating the call was made.')
                    ->action(function ($record) {
                        $record->update([
                            'second_follow_up_date' => now()->toDateString(),
                        ]);
                        Notification::make()
                            ->title('Marked as Called')
                            ->body('Second follow-up date recorded as today.')
                            ->success()
                            ->send();
                    }),

                TableAction::make('view_sale')
                    ->label('Open Sale')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn($record) => \App\Filament\Resources\SaleResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort(fn(Builder $q) =>
                $q->orderByRaw(self::expiryExpr() . ' ASC')
            )
            ->searchable()
            ->striped()
            ->paginated([15, 25, 50])
            ->emptyStateIcon('heroicon-o-shield-check')
            ->emptyStateHeading('No warranties expiring in this window')
            ->emptyStateDescription('All good! Try a longer window or check \'Already expired\'.');
    }

    public function getStats(): array
    {
        $expr  = self::expiryExpr();
        $today = now()->toDateString();

        $base = Sale::where('has_warranty', 1)
            ->where('status', 'completed')
            ->whereNull('deleted_at')
            ->whereNotNull('customer_id');

        $expiredCount = (clone $base)
            ->whereRaw("DATE({$expr}) < ?", [$today])
            ->count();

        $within30 = (clone $base)
            ->whereRaw("DATE({$expr}) >= ?", [$today])
            ->whereRaw("DATE({$expr}) <= ?", [now()->addDays(30)->toDateString()])
            ->count();

        $within60 = (clone $base)
            ->whereRaw("DATE({$expr}) >= ?", [$today])
            ->whereRaw("DATE({$expr}) <= ?", [now()->addDays(60)->toDateString()])
            ->count();

        $within90 = (clone $base)
            ->whereRaw("DATE({$expr}) >= ?", [$today])
            ->whereRaw("DATE({$expr}) <= ?", [now()->addDays(90)->toDateString()])
            ->count();

        $withReminder = (clone $base)
            ->whereRaw("DATE({$expr}) >= ?", [$today])
            ->whereRaw("DATE({$expr}) <= ?", [now()->addDays(90)->toDateString()])
            ->whereNotNull('follow_up_date')
            ->count();

        $withoutReminder = (clone $base)
            ->whereRaw("DATE({$expr}) >= ?", [$today])
            ->whereRaw("DATE({$expr}) <= ?", [now()->addDays(90)->toDateString()])
            ->whereNull('follow_up_date')
            ->count();

        return compact(
            'expiredCount', 'within30', 'within60',
            'within90', 'withReminder', 'withoutReminder'
        );
    }
}