<?php

namespace App\Filament\Master\Resources;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\SupportCannedResponse;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;
    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';
    protected static ?string $navigationLabel = 'Support Helpdesk';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\Select::make('status')
                    ->options([
                        'open' => 'Open', 'in_progress' => 'In Progress',
                        'resolved' => 'Resolved', 'closed' => 'Closed',
                    ])->required(),
                Forms\Components\Select::make('priority')
                    ->options(['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent']),
                Forms\Components\Select::make('assigned_to')
                    ->label('Assign To')
                    ->options(User::pluck('name', 'id'))
                    ->searchable(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#'),
                Tables\Columns\TextColumn::make('store_name')->label('Store')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('subject')->limit(35)->searchable(),
                Tables\Columns\TextColumn::make('category')->badge(),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'urgent' => 'danger', 'high' => 'warning', 'low' => 'gray', default => 'info',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'open' => 'danger', 'in_progress' => 'warning', 'resolved' => 'success', 'closed' => 'gray', default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('assignedAdmin.name')->label('Assigned')->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('created_at')->dateTime('M d, h:i A')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tenant_id')->label('Store')
                    ->options(fn() => SupportTicket::distinct()->pluck('tenant_id', 'tenant_id')),
                Tables\Filters\SelectFilter::make('status')
                    ->options(['open' => 'Open', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed']),
                Tables\Filters\SelectFilter::make('priority')
                    ->options(['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent']),
            ])
            ->actions([
                Tables\Actions\Action::make('manage')
                    ->label('Open Ticket')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->modalHeading(fn(SupportTicket $record) => "#{$record->id} — {$record->subject} ({$record->store_name})")
                    ->modalWidth('4xl')
                    ->modalSubmitActionLabel('Send')
                    ->form([
                        Forms\Components\Placeholder::make('thread')
                            ->hiddenLabel()
                            ->content(function (SupportTicket $record) {
                                $msgs = $record->messages()->get();
                                $html = '';
                                foreach ($msgs as $m) {
                                    $isAdmin = $m->sender_type === 'admin';
                                    $isNote  = $m->is_internal_note;
                                    $bg = $isNote ? '#fef9c3' : ($isAdmin ? '#eff6ff' : '#f8fafc');
                                    $label = $isNote ? '🔒 Internal Note — ' . $m->sender_name : ($isAdmin ? 'You (Support)' : $m->sender_name);
                                    $atts = '';
                                    if (!empty($m->attachments)) {
                                        foreach ($m->attachments as $a) {
                                            $url = \Illuminate\Support\Facades\Storage::disk('public')->url($a);
                                            $atts .= "<a href='{$url}' target='_blank' style='display:inline-block;margin-top:6px;margin-right:6px;'><img src='{$url}' style='width:60px;height:60px;border-radius:6px;object-fit:cover;border:1px solid #e2e8f0;'></a>";
                                        }
                                    }
                                    $html .= "<div style='background:{$bg};border-radius:8px;padding:10px 14px;margin-bottom:8px;'>
                                        <div style='font-size:11px;font-weight:700;color:#475569;'>{$label} • {$m->created_at->format('M d, h:i A')}</div>
                                        <div style='font-size:13px;margin-top:4px;'>" . nl2br(e($m->message)) . "</div>
                                        {$atts}
                                    </div>";
                                }
                                return new HtmlString($html ?: "<p class='text-sm text-gray-400 italic'>No messages.</p>");
                            }),

                        Forms\Components\Select::make('canned_response_id')
                            ->label('Insert Canned Response')
                            ->options(SupportCannedResponse::pluck('title', 'id'))
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $canned = SupportCannedResponse::find($state);
                                if ($canned) $set('reply_message', $canned->body);
                            }),

                        Forms\Components\Textarea::make('reply_message')
                            ->label('Reply (visible to store)')
                            ->rows(3),

                        Forms\Components\Toggle::make('is_internal_note')
                            ->label('Mark as internal note (hidden from store)'),
                    ])
                    ->action(function (SupportTicket $record, array $data) {
                        if (!empty($data['reply_message'])) {
                            SupportTicketMessage::create([
                                'ticket_id'         => $record->id,
                                'sender_type'       => 'admin',
                                'sender_name'       => auth()->user()->name,
                                'message'           => $data['reply_message'],
                                'is_internal_note'  => $data['is_internal_note'] ?? false,
                            ]);
                            if (empty($data['is_internal_note'])) {
                                $record->update(['last_reply_at' => now()]);
                            }
                        }
                    }),

                Tables\Actions\EditAction::make()->label('Status / Assign'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Master\Resources\SupportTicketResource\Pages\ListSupportTickets::route('/'),
        ];
    }
    
}