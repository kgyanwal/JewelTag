<?php

namespace App\Filament\Resources;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Filament\Notifications\Notification;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;
    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';
    protected static ?string $navigationLabel = 'Support';
    protected static bool $shouldRegisterNavigation = false; // moved to user menu instead

    // Scope every query to this tenant only
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('tenant_id', tenant('id'));
    }

    // ── PILL HELPERS ─────────────────────────────────────────────
    private static function categoryMeta(string $cat): array
    {
        return match ($cat) {
            'bug'             => ['🐞', 'Bug / Error',     '#fef2f2', '#dc2626', '#fecaca'],
            'billing'         => ['💳', 'Billing',         '#fffbeb', '#b45309', '#fde68a'],
            'feature_request' => ['✨', 'Feature Request', '#f5f3ff', '#7c3aed', '#ddd6fe'],
            'training'        => ['🎓', 'Training / How-To','#eff6ff', '#1d4ed8', '#bfdbfe'],
            default           => ['💬', 'Other',           '#f8fafc', '#475569', '#e2e8f0'],
        };
    }

    private static function priorityMeta(string $p): array
    {
        return match ($p) {
            'urgent' => ['🔥', 'Urgent', '#fef2f2', '#dc2626', '#fecaca'],
            'high'   => ['⚠️', 'High',   '#fff7ed', '#c2410c', '#fed7aa'],
            'low'    => ['🟢', 'Low',    '#f0fdf4', '#15803d', '#bbf7d0'],
            default  => ['🔵', 'Normal', '#eff6ff', '#1d4ed8', '#bfdbfe'],
        };
    }

    private static function statusMeta(string $s): array
    {
        return match ($s) {
            'open'        => ['● ', 'Open',        '#fef2f2', '#dc2626', '#fecaca'],
            'in_progress' => ['◐ ', 'In Progress',  '#fffbeb', '#b45309', '#fde68a'],
            'resolved'    => ['✓ ', 'Resolved',     '#f0fdf4', '#15803d', '#bbf7d0'],
            'closed'      => ['○ ', 'Closed',       '#f8fafc', '#64748b', '#e2e8f0'],
            default       => ['• ', ucfirst($s),    '#f8fafc', '#475569', '#e2e8f0'],
        };
    }

    private static function pill(string $icon, string $label, string $bg, string $text, string $border): string
    {
        return "<span style='display:inline-flex;align-items:center;gap:4px;background:{$bg};color:{$text};border:1px solid {$border};border-radius:999px;padding:3px 10px;font-size:11px;font-weight:700;white-space:nowrap;'>{$icon} {$label}</span>";
    }

    // ── FORM (Create Ticket) ─────────────────────────────────────
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('New Support Ticket')
                ->description("Tell us what's going on — our team typically replies within a few hours.")
                ->icon('heroicon-o-lifebuoy')
                ->schema([
                    Forms\Components\TextInput::make('subject')
                        ->label('Subject')
                        ->placeholder('Briefly summarize the issue...')
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('category')
                            ->label('Category')
                            ->native(false)
                            ->options([
                                'bug'             => '🐞 Bug / Error',
                                'billing'         => '💳 Billing',
                                'feature_request' => '✨ Feature Request',
                                'training'        => '🎓 Training / How-To',
                                'other'           => '💬 Other',
                            ])
                            ->default('other')
                            ->required(),

                        Forms\Components\Select::make('priority')
                            ->label('Priority')
                            ->native(false)
                            ->options([
                                'low'    => '🟢 Low',
                                'normal' => '🔵 Normal',
                                'high'   => '⚠️ High',
                                'urgent' => '🔥 Urgent',
                            ])
                            ->default('normal')
                            ->required(),
                    ]),

                    Forms\Components\Textarea::make('description')
                        ->label('Describe the issue')
                        ->placeholder("What happened? What did you expect to happen? Steps to reproduce help us fix it faster.")
                        ->rows(5)
                        ->required()
                        ->dehydrated(false)
                        ->columnSpanFull(),

                    Forms\Components\FileUpload::make('attachments')
                        ->label('Screenshots / Files')
                        ->helperText('Up to 5 images — a screenshot is often the fastest way to explain a bug.')
                        ->multiple()
                        ->image()
                        ->panelLayout('grid')
                        ->maxFiles(5)
                        ->directory('support-attachments')
                        ->disk('public')
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Hidden::make('tenant_id')->default(fn() => tenant('id')),
            Forms\Components\Hidden::make('store_name')->default(fn() => tenant('name') ?? tenant('id')),
            Forms\Components\Hidden::make('created_by_name')->default(fn() => auth()->user()->name),
            Forms\Components\Hidden::make('created_by_user_id')->default(fn() => auth()->id()),
            Forms\Components\Hidden::make('status')->default('open'),
        ]);
    }

    // ── TABLE (Ticket List) ───────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('TICKET')
                    ->formatStateUsing(fn($state) => '#' . str_pad($state, 4, '0', STR_PAD_LEFT))
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->color('gray')
                    ->grow(false),

                Tables\Columns\TextColumn::make('subject')
                    ->label('SUBJECT')
                    ->searchable()
                    ->limit(45)
                    ->weight('semibold')
                    ->description(function (SupportTicket $record) {
                        $count = $record->messages()->where('is_internal_note', false)->count();
                        return $count > 0
                            ? "{$count} " . ($count === 1 ? 'reply' : 'replies')
                            : 'No replies yet';
                    }),

                Tables\Columns\TextColumn::make('category')
                    ->label('CATEGORY')
                    ->html()
                    ->getStateUsing(function (SupportTicket $record) {
                        [$icon, $label, $bg, $text, $border] = self::categoryMeta($record->category);
                        return self::pill($icon, $label, $bg, $text, $border);
                    }),

                Tables\Columns\TextColumn::make('priority')
                    ->label('PRIORITY')
                    ->html()
                    ->getStateUsing(function (SupportTicket $record) {
                        [$icon, $label, $bg, $text, $border] = self::priorityMeta($record->priority);
                        return self::pill($icon, $label, $bg, $text, $border);
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('STATUS')
                    ->html()
                    ->getStateUsing(function (SupportTicket $record) {
                        [$icon, $label, $bg, $text, $border] = self::statusMeta($record->status);
                        return self::pill($icon, $label, $bg, $text, $border);
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('OPENED')
                    ->date('M d, Y')
                    ->description(fn($record) => $record->created_at?->format('h:i A'))
                    ->sortable()
                    ->color('gray')
                    ->size('xs'),

                Tables\Columns\TextColumn::make('last_reply_at')
                    ->label('LAST ACTIVITY')
                    ->getStateUsing(fn($record) => $record->last_reply_at
                        ? $record->last_reply_at->diffForHumans()
                        : $record->created_at->diffForHumans())
                    ->color('gray')
                    ->size('xs'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open'        => 'Open',
                        'in_progress' => 'In Progress',
                        'resolved'    => 'Resolved',
                        'closed'      => 'Closed',
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'urgent' => 'Urgent',
                        'high'   => 'High',
                        'normal' => 'Normal',
                        'low'    => 'Low',
                    ]),
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'bug'             => 'Bug / Error',
                        'billing'         => 'Billing',
                        'feature_request' => 'Feature Request',
                        'training'        => 'Training / How-To',
                        'other'           => 'Other',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view_thread')
                    ->label('Open')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('primary')
                    ->button()
                    ->size('sm')
                    ->modalHeading(fn(SupportTicket $record) => "Ticket #" . str_pad($record->id, 4, '0', STR_PAD_LEFT))
                    ->modalDescription(fn(SupportTicket $record) => $record->subject)
                    ->modalWidth('2xl')
                    ->modalSubmitActionLabel('Send Reply')
                    ->modalIcon('heroicon-o-chat-bubble-left-right')
                    ->fillForm(fn() => ['reply_message' => ''])
                    ->form([
                        Forms\Components\Placeholder::make('meta_row')
                            ->hiddenLabel()
                            ->content(function (SupportTicket $record) {
                                [$cIcon, $cLabel, $cBg, $cText, $cBorder] = self::categoryMeta($record->category);
                                [$pIcon, $pLabel, $pBg, $pText, $pBorder] = self::priorityMeta($record->priority);
                                [$sIcon, $sLabel, $sBg, $sText, $sBorder] = self::statusMeta($record->status);

                                $catPill    = self::pill($cIcon, $cLabel, $cBg, $cText, $cBorder);
                                $prioPill   = self::pill($pIcon, $pLabel, $pBg, $pText, $pBorder);
                                $statusPill = self::pill($sIcon, $sLabel, $sBg, $sText, $sBorder);

                                $opened = $record->created_at->format('M d, Y \a\t h:i A');

                                return new HtmlString("
                                    <div style='display:flex;flex-wrap:wrap;gap:8px;align-items:center;padding:10px 0 14px;border-bottom:1px solid #f1f5f9;margin-bottom:14px;'>
                                        {$catPill} {$prioPill} {$statusPill}
                                        <span style='margin-left:auto;font-size:11px;color:#94a3b8;'>Opened {$opened}</span>
                                    </div>
                                ");
                            }),

                        Forms\Components\Placeholder::make('thread')
                            ->hiddenLabel()
                            ->content(function (SupportTicket $record) {
                                $msgs = $record->messages()->where('is_internal_note', false)->orderBy('created_at')->get();

                                if ($msgs->isEmpty()) {
                                    return new HtmlString("
                                        <div style='text-align:center;padding:28px 12px;color:#94a3b8;'>
                                            <div style='font-size:28px;margin-bottom:6px;'>💬</div>
                                            <div style='font-size:13px;'>No replies yet — our team will respond here soon.</div>
                                        </div>
                                    ");
                                }

                                $html = "<div style='max-height:360px;overflow-y:auto;padding-right:6px;display:flex;flex-direction:column;gap:10px;'>";

                                foreach ($msgs as $m) {
                                    $isAdmin = $m->sender_type === 'admin';
                                    $align   = $isAdmin ? 'flex-start' : 'flex-end';
                                    $bg      = $isAdmin ? '#f0f9ff' : '#f0fdf4';
                                    $border  = $isAdmin ? '#bae6fd' : '#bbf7d0';
                                    $badge   = $isAdmin
                                        ? "<span style='font-size:10px;font-weight:700;color:#0369a1;'>🎧 Support Team</span>"
                                        : "<span style='font-size:10px;font-weight:700;color:#15803d;'>👤 " . e($m->sender_name ?? 'You') . "</span>";

                                    $html .= "
                                        <div style='display:flex;flex-direction:column;align-items:{$align};'>
                                            <div style='background:{$bg};border:1px solid {$border};border-radius:12px;padding:10px 14px;max-width:80%;'>
                                                <div style='display:flex;justify-content:space-between;gap:12px;margin-bottom:4px;'>
                                                    {$badge}
                                                    <span style='font-size:10px;color:#94a3b8;'>{$m->created_at->format('M d, h:i A')}</span>
                                                </div>
                                                <div style='font-size:13px;color:#1e293b;line-height:1.5;'>" . nl2br(e($m->message)) . "</div>
                                            </div>
                                        </div>
                                    ";
                                }

                                $html .= "</div>";
                                return new HtmlString($html);
                            }),

                        Forms\Components\Textarea::make('reply_message')
                            ->label('Your reply')
                            ->placeholder('Type a message to our support team...')
                            ->rows(3)
                            ->extraInputAttributes(['style' => 'border-radius:10px;']),
                    ])
                   ->action(function (SupportTicket $record, array $data) {
    if (!empty($data['reply_message'])) {
        SupportTicketMessage::create([
            'ticket_id'   => $record->id,
            'sender_type' => 'tenant',
            'sender_name' => auth()->user()->name,
            'message'     => $data['reply_message'],
        ]);
        $record->update(['last_reply_at' => now()]);

        $ticketNo = str_pad($record->id, 4, '0', STR_PAD_LEFT);

        \Illuminate\Support\Facades\Mail::raw(
            "{$record->store_name} replied to ticket #{$ticketNo}\n\n" .
            "Subject: {$record->subject}\n\n" .
            "Message:\n{$data['reply_message']}",
            function ($message) use ($ticketNo) {
                $message->to('info@jeweltag.us')
                    ->subject("Reply on Ticket #{$ticketNo}");
            }
        );

        Notification::make()
            ->title('Reply sent')
            ->success()
            ->send();
    }
}),
            ])
            ->emptyStateHeading('No support tickets yet')
            ->emptyStateDescription('Need help with something? Click "New Ticket" to get started.')
            ->emptyStateIcon('heroicon-o-lifebuoy')
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => \App\Filament\Resources\SupportTicketResource\Pages\ListSupportTickets::route('/'),
            'create' => \App\Filament\Resources\SupportTicketResource\Pages\CreateSupportTicket::route('/create'),
            'edit'   => \App\Filament\Resources\SupportTicketResource\Pages\EditSupportTicket::route('/{record}/edit'),
        ];
    }
}