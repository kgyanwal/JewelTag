<?php

namespace App\Filament\Pages;

use App\Models\Sale;
use App\Models\Customer;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class UpcomingFollowUps extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon  = 'heroicon-o-phone-arrow-up-right';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'Follow-Ups Due';
    protected static ?string $title           = 'Upcoming Customer Follow-Ups';
    protected static ?int    $navigationSort  = 3;

    protected static string $view = 'filament.pages.upcoming-follow-ups';

    // Stats displayed in header cards
    public int $overdueCount   = 0;
    public int $todayCount     = 0;
    public int $thisWeekCount  = 0;
    public int $upcomingCount  = 0;

    public function mount(): void
    {
        $today    = now()->startOfDay();
        $tomorrow = now()->addDay()->startOfDay();
        $weekEnd  = now()->addDays(7)->endOfDay();
        $future   = now()->addDays(14)->endOfDay();

        $base = Sale::query()->where('status', 'completed');

        $this->overdueCount = (clone $base)
            ->where(fn($q) => $q
                ->where('follow_up_date', '<', $today)
                ->orWhere('second_follow_up_date', '<', $today)
            )->count();

        $this->todayCount = (clone $base)
            ->where(fn($q) => $q
                ->whereDate('follow_up_date', today())
                ->orWhereDate('second_follow_up_date', today())
            )->count();

        $this->thisWeekCount = (clone $base)
            ->where(fn($q) => $q
                ->whereBetween('follow_up_date', [$tomorrow, $weekEnd])
                ->orWhereBetween('second_follow_up_date', [$tomorrow, $weekEnd])
            )->count();

        $this->upcomingCount = (clone $base)
            ->where(fn($q) => $q
                ->whereBetween('follow_up_date', [$weekEnd, $future])
                ->orWhereBetween('second_follow_up_date', [$weekEnd, $future])
            )->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Sale::query()
                    ->with(['customer', 'items'])
                    ->where('status', 'completed')
                    ->where(function ($query) {
                        $today  = now();
                        $future = now()->addDays(14);
                        $query->whereBetween('follow_up_date', [$today, $future])
                              ->orWhereBetween('second_follow_up_date', [$today, $future])
                              ->orWhere('follow_up_date', '<', $today)
                              ->orWhere('second_follow_up_date', '<', $today);
                    })
            )
            ->columns([
                // Customer info
                Tables\Columns\Layout\Stack::make([
                    TextColumn::make('customer.name')
                        ->label('Customer')
                        ->formatStateUsing(fn(Sale $record) =>
                            trim(($record->customer->name ?? '') . ' ' . ($record->customer->last_name ?? ''))
                        )
                        ->weight('bold')
                        ->size('sm')
                        ->searchable(),

                    TextColumn::make('customer.phone')
                        ->label('Phone')
                        ->icon('heroicon-m-phone')
                        ->size('xs')
                        ->color('gray'),
                ])->space(1),

                TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                TextColumn::make('follow_up_status')
                    ->label('Follow-Up Status')
                    ->getStateUsing(function (Sale $record) {
                        $today = now()->startOfDay();
                        $f1    = $record->follow_up_date;
                        $f2    = $record->second_follow_up_date;

                        if ($f1 && $f1 < $today) return 'overdue_1st';
                        if ($f1 && $f1->isToday())  return 'today_1st';
                        if ($f2 && $f2 < $today) return 'overdue_2nd';
                        if ($f2 && $f2->isToday())  return 'today_2nd';
                        if ($f1) return 'upcoming_1st';
                        return 'upcoming_2nd';
                    })
                    ->badge()
                    ->formatStateUsing(fn($state) => match($state) {
                        'overdue_1st'  => '🔴 Overdue — 1st',
                        'today_1st'    => '🟡 Today — 1st',
                        'overdue_2nd'  => '🟠 Overdue — 2nd',
                        'today_2nd'    => '🟡 Today — 2nd',
                        'upcoming_1st' => '🔵 Upcoming — 1st',
                        default        => '🔵 Upcoming — 2nd',
                    })
                    ->color(fn($state) => match($state) {
                        'overdue_1st', 'overdue_2nd' => 'danger',
                        'today_1st', 'today_2nd'     => 'warning',
                        default                       => 'info',
                    }),

                TextColumn::make('follow_up_date')
                    ->date('M d, Y')
                    ->label('1st Follow-Up')
                    ->color(fn($state) => $state && $state <= now() ? 'danger' : 'warning')
                    ->icon('heroicon-m-calendar')
                    ->sortable(),

                TextColumn::make('second_follow_up_date')
                    ->date('M d, Y')
                    ->label('2nd Follow-Up')
                    ->icon('heroicon-m-calendar-days')
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('final_total')
                    ->label('Sale Value')
                    ->money('USD')
                    ->color('success')
                    ->weight('bold'),

                TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items')
                    ->badge()
                    ->color('gray'),
            ])
            ->actions([
                Action::make('call')
                    ->label('Call')
                    ->icon('heroicon-o-phone')
                    ->color('success')
                    ->size('sm')
                    ->url(fn(Sale $record) => "tel:{$record->customer?->phone}")
                    ->openUrlInNewTab()
                    ->hidden(fn(Sale $record) => empty($record->customer?->phone)),

                Action::make('sms')
                    ->label('SMS')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('info')
                    ->size('sm')
                    ->form([
                        Textarea::make('message')
                            ->label('Message')
                            ->placeholder("Hi {customer}, just checking in on your recent purchase…")
                            ->default(fn(Sale $record) =>
                                "Hi " . ($record->customer->name ?? 'there') . ", this is a friendly follow-up from our store regarding your recent purchase (Invoice #{$record->invoice_number}). We hope you're loving it! Feel free to reach out anytime. 😊"
                            )
                            ->rows(4)
                            ->required(),
                    ])
                    ->action(function (Sale $record, array $data) {
                        $phone = $record->customer?->phone;
                        if (!$phone) {
                            Notification::make()->danger()->title('No phone number on file')->send();
                            return;
                        }
                        // Format for SMS URL
                        $phone   = preg_replace('/\D/', '', $phone);
                        $message = urlencode($data['message']);

                        // This opens the SMS app on mobile / desktop
                        $this->dispatch('open-sms', [
                            'url' => "sms:+1{$phone}?body={$message}",
                        ]);

                        Notification::make()->success()->title('SMS Ready')->body('Opening your messaging app…')->send();
                    })
                    ->hidden(fn(Sale $record) => empty($record->customer?->phone)),

                Action::make('mark_done')
                    ->label('Mark Done')
                    ->icon('heroicon-o-check-circle')
                    ->color('gray')
                    ->size('sm')
                    ->requiresConfirmation()
                    ->modalHeading('Mark Follow-Up Complete?')
                    ->modalDescription('This will clear the follow-up dates so this sale no longer appears here.')
                    ->action(function (Sale $record) {
                        $record->update([
                            'follow_up_date'        => null,
                            'second_follow_up_date' => null,
                        ]);
                        Notification::make()->success()->title('Follow-up marked complete')->send();
                    }),

                Action::make('view_sale')
                    ->label('View')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->size('sm')
                    ->url(fn(Sale $record) => \App\Filament\Resources\SaleResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('urgency')
                    ->label('Urgency')
                    ->options([
                        'overdue' => '🔴 Overdue',
                        'today'   => '🟡 Due Today',
                        'week'    => '🔵 This Week',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!$data['value']) return $query;
                        $today   = now()->startOfDay();
                        $weekEnd = now()->addDays(7)->endOfDay();
                        return match ($data['value']) {
                            'overdue' => $query->where(fn($q) => $q
                                ->where('follow_up_date', '<', $today)
                                ->orWhere('second_follow_up_date', '<', $today)),
                            'today'   => $query->where(fn($q) => $q
                                ->whereDate('follow_up_date', today())
                                ->orWhereDate('second_follow_up_date', today())),
                            'week'    => $query->where(fn($q) => $q
                                ->whereBetween('follow_up_date', [$today, $weekEnd])
                                ->orWhereBetween('second_follow_up_date', [$today, $weekEnd])),
                            default => $query,
                        };
                    }),
            ])
            ->defaultSort('follow_up_date', 'asc')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}