<?php

namespace App\Filament\Pages;

use App\Models\EodAmendmentRequest;
use App\Models\DailyClosing;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class EodAmendmentRequests extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Admin';
    protected static ?string $navigationLabel = 'EOD Amendment Requests';
    protected static ?string $title           = 'EOD Amendment Requests';
    protected static string  $view            = 'filament.pages.eod-amendment-requests';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('Superadmin') ?? false;
    }

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole('Superadmin'), 403);
    }

    protected function getTableQuery(): Builder
    {
        return EodAmendmentRequest::query()
            ->with(['requester', 'reviewer'])
            ->latest();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('status')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn($state) => ucfirst($state))
                    ->sortable(),

                TextColumn::make('eod_date')
                    ->label('EOD Date')
                    ->date('M d, Y')
                    ->sortable()
                    ->description(fn($record) =>
                        $record->eod_date->isToday()
                            ? 'Today'
                            : $record->eod_date->diffForHumans()
                    ),

                TextColumn::make('requester.name')
                    ->label('Requested By')
                    ->weight('bold'),

                TextColumn::make('invoice_number')
                    ->label('Invoice')
                    ->color('primary')
                    ->default('—'),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn($state) => $state ? '$' . number_format($state, 2) : '—')
                    ->color('success'),

                TextColumn::make('reason')
                    ->label('Reason')
                    ->limit(60)
                    ->tooltip(fn($record) => $record->reason)
                    ->wrap(),

                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->color('gray')
                    ->size('sm'),

                TextColumn::make('review_info')
                    ->label('Review')
                    ->getStateUsing(function ($record) {
                        if ($record->status === 'pending') {
                            return new HtmlString('<span style="color:#94a3b8;font-size:11px;font-style:italic;">Awaiting review</span>');
                        }
                        $by    = $record->reviewer?->name ?? 'Unknown';
                        $at    = $record->reviewed_at?->format('M d, h:i A') ?? '';
                        $note  = $record->review_notes
                            ? '<br><span style="color:#64748b;font-style:italic;">' . e($record->review_notes) . '</span>'
                            : '';
                        $color = $record->status === 'approved' ? '#059669' : '#B8463F';
                        return new HtmlString(
                            "<span style='font-size:11px;color:{$color};font-weight:600;'>{$by}</span>" .
                            "<br><span style='font-size:10px;color:#94a3b8;'>{$at}</span>{$note}"
                        );
                    })
                    ->html(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending'  => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                TableAction::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('review_notes')
                            ->label('Approval Notes (optional)')
                            ->placeholder('e.g. Confirmed late sale — will unlock EOD')
                            ->rows(2),
                    ])
                    ->modalHeading(fn($record) => 'Approve Request — ' . $record->eod_date->format('M d, Y'))
                    ->modalDescription(fn($record) =>
                        'Submitted by ' . ($record->requester?->name ?? '?') .
                        ' for EOD ' . $record->eod_date->format('M d, Y') .
                        ($record->invoice_number ? ' — Invoice: ' . $record->invoice_number : '') .
                        '. Staff will be notified. You can then unlock the EOD using the "Unlock EOD" button.'
                    )
                    ->modalSubmitActionLabel('Approve & Notify Staff')
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status'       => 'approved',
                            'reviewed_by'  => auth()->id(),
                            'reviewed_at'  => now(),
                            'review_notes' => $data['review_notes'] ?? null,
                        ]);

                        $eodDate = $record->eod_date->format('M d, Y');

                        Notification::make()
                            ->title('EOD Amendment Approved ✅')
                            ->body("Your request to amend EOD for {$eodDate} has been approved. The EOD will be unlocked shortly — please add the missing payment and re-post.")
                            ->success()
                            ->sendToDatabase($record->requester);

                        Notification::make()
                            ->title('Request Approved')
                            ->body("Approved {$record->requester?->name}'s EOD amendment for {$eodDate}. Use 'Unlock EOD' button to delete the daily_closings record.")
                            ->success()
                            ->send();
                    }),

                TableAction::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('review_notes')
                            ->label('Rejection Reason')
                            ->placeholder('e.g. Payment should be recorded in tomorrow\'s EOD')
                            ->required()
                            ->rows(2),
                    ])
                    ->modalHeading(fn($record) => 'Reject Request — ' . $record->eod_date->format('M d, Y'))
                    ->modalSubmitActionLabel('Reject Request')
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status'       => 'rejected',
                            'reviewed_by'  => auth()->id(),
                            'reviewed_at'  => now(),
                            'review_notes' => $data['review_notes'],
                        ]);

                        $eodDate = $record->eod_date->format('M d, Y');

                        Notification::make()
                            ->title('EOD Amendment Request Rejected')
                            ->body("Your amendment request for {$eodDate} was not approved. Reason: {$data['review_notes']}")
                            ->danger()
                            ->sendToDatabase($record->requester);

                        Notification::make()
                            ->title('Request Rejected')
                            ->body("Rejected {$record->requester?->name}'s EOD amendment request for {$eodDate}.")
                            ->warning()
                            ->send();
                    }),

                TableAction::make('unlock_eod')
                    ->label('Unlock EOD')
                    ->icon('heroicon-o-lock-open')
                    ->color('warning')
                    ->visible(fn($record) => $record->status === 'approved')
                    ->requiresConfirmation()
                    ->modalHeading(fn($record) => 'Unlock EOD for ' . $record->eod_date->format('M d, Y') . '?')
                    ->modalDescription(fn($record) =>
                        'This will delete the daily_closings record for ' . $record->eod_date->format('M d, Y') .
                        ', allowing staff to add the missing payment and re-post. This cannot be undone.'
                    )
                    ->modalSubmitActionLabel('Yes, Unlock EOD')
                    ->action(function ($record) {
                        $deleted = DailyClosing::whereDate('closing_date', $record->eod_date)->delete();

                        if ($deleted) {
                            $record->update(['status' => 'approved']); // keep approved

                            Notification::make()
                                ->title('EOD Unlocked ✅')
                                ->body('EOD for ' . $record->eod_date->format('M d, Y') . ' has been unlocked successfully.')
                                ->success()
                                ->send();

                            Notification::make()
                                ->title('EOD Unlocked — Action Required')
                                ->body('The EOD for ' . $record->eod_date->format('M d, Y') . ' has been unlocked. Please go to End of Day, add the missing payment, and re-post the closing.')
                                ->success()
                                ->sendToDatabase($record->requester);
                        } else {
                            Notification::make()
                                ->title('Already Unlocked')
                                ->body('No daily_closings record found for ' . $record->eod_date->format('M d, Y') . ' — it may already be unlocked.')
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([15, 25])
            ->emptyStateIcon('heroicon-o-clipboard-document-check')
            ->emptyStateHeading('No amendment requests yet')
            ->emptyStateDescription('When staff submit EOD amendment requests, they will appear here for your review.');
    }
}