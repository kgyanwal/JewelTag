<?php

namespace App\Filament\Master\Resources;

use App\Filament\Master\Resources\ReleaseNoteResource\Pages;
use App\Models\ReleaseNote;
use App\Models\Tenant;
use App\Models\User;
use App\Mail\ReleaseNoteMail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;

class ReleaseNoteResource extends Resource
{
    protected static ?string $model = ReleaseNote::class;
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationLabel = 'Release Notes';
    protected static ?string $navigationGroup = 'SaaS Management';

       public static function form(Form $form): Form
    {
        return $form->schema([

            // 🚀 NEW — quick-fill templates so staff aren't starting from a blank
            // page. Clicking one fills title/body with a ready-to-edit example
            // matching that release type.
            Forms\Components\Section::make('Quick Start')
                ->description('Not sure what to write? Pick a template to get started, then edit it.')
                ->schema([
                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('template_feature')
                            ->label('✨ New Feature Template')
                            ->color('success')->outlined()
                            ->action(function (Forms\Set $set) {
                                $set('type', 'feature');
                                $set('title', 'New: [Feature Name]');
                                $set('body', "We've just added [feature name] to help you [what it does for the store].\n\nHere's how to use it:\n1. Go to [location in the app]\n2. [Step]\n3. [Step]\n\nIf you have any questions, reach out to support anytime.");
                            }),
                        Forms\Components\Actions\Action::make('template_fix')
                            ->label('🔧 Bug Fix Template')
                            ->color('warning')->outlined()
                            ->action(function (Forms\Set $set) {
                                $set('type', 'fix');
                                $set('title', 'Fixed: [Issue Description]');
                                $set('body', "We identified and fixed an issue where [what was happening]. This is now resolved and no action is needed on your end.\n\nIf you were experiencing this issue and it's still occurring, please contact support.");
                            }),
                        Forms\Components\Actions\Action::make('template_improvement')
                            ->label('⚡ Improvement Template')
                            ->color('info')->outlined()
                            ->action(function (Forms\Set $set) {
                                $set('type', 'improvement');
                                $set('title', 'Improved: [Area of the App]');
                                $set('body', "We've made [what changed] faster/easier to use.\n\nWhat's different:\n- [Change 1]\n- [Change 2]\n\nThis update is already live — no action needed.");
                            }),
                        Forms\Components\Actions\Action::make('template_announcement')
                            ->label('📢 Announcement Template')
                            ->color('gray')->outlined()
                            ->action(function (Forms\Set $set) {
                                $set('type', 'announcement');
                                $set('title', '[Announcement Title]');
                                $set('body', "We wanted to let you know about [what you're announcing].\n\n[Details, dates, or anything staff need to know.]\n\nQuestions? Reach out anytime.");
                            }),
                    ]),
                ]),

            Forms\Components\Section::make('Release Details')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('version')
                        ->label('Version (optional)')
                        ->placeholder('e.g. v2.4.0'),

                    Forms\Components\Select::make('type')
                        ->label('Type')
                        ->options([
                            'feature'      => '✨ New Feature',
                            'fix'          => '🔧 Bug Fix',
                            'improvement'  => '⚡ Improvement',
                            'announcement' => '📢 Announcement',
                        ])
                        ->default('feature')
                        ->required()
                        ->native(false)
                        ->live(),

                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->placeholder('e.g. New: Split Payment for Repairs')
                        ->live(onBlur: true)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('body')
                        ->label('Details')
                        ->required()
                        ->rows(8)
                        ->placeholder("Write in plain, friendly language — this is exactly what store staff will read.\n\nExample:\nYou can now split a repair payment across multiple methods (e.g. part cash, part card). Just toggle \"Enable Split Payment\" when adding a deposit.")
                        ->columnSpanFull()
                        ->live(onBlur: true)
                        ->helperText('Plain text — shown in the in-app banner and included in the email/SMS sent to every store.'),

                    Forms\Components\DateTimePicker::make('expires_at')
                        ->label('Banner Expires')
                        ->helperText('Leave blank to show until manually removed.'),
                ]),

            // 🚀 NEW — live preview, exact same markup/colors as the real banner
            // rendered in the tenant Admin Panel, so you see precisely what
            // staff will see before you publish anything.
            Forms\Components\Section::make('Live Preview')
                ->description('This is exactly how it will appear inside every store.')
                ->schema([
                    Forms\Components\Placeholder::make('preview')
                        ->hiddenLabel()
                        ->live()
                        ->content(function (Forms\Get $get) {
                            $type  = $get('type') ?: 'feature';
                            $title = $get('title') ?: 'Your title will appear here';
                            $body  = $get('body') ?: 'Your message will appear here...';
                            $version = $get('version');

                            $styles = [
                                'feature'      => ['bg' => '#EAF6EF', 'border' => '#0F7A5C', 'icon' => '#0F7A5C', 'text' => '#0B3D3C', 'sub' => '#0F7A5C', 'label' => '✨ NEW FEATURE'],
                                'fix'          => ['bg' => '#FBF3E2', 'border' => '#C9A24B', 'icon' => '#C9A24B', 'text' => '#5A4419', 'sub' => '#8A6A22', 'label' => '🔧 BUG FIX'],
                                'improvement'  => ['bg' => '#EEF3F2', 'border' => '#3D6B63', 'icon' => '#3D6B63', 'text' => '#0B3D3C', 'sub' => '#264E48', 'label' => '⚡ IMPROVEMENT'],
                                'announcement' => ['bg' => '#F3F4F6', 'border' => '#6B7280', 'icon' => '#6B7280', 'text' => '#1F2937', 'sub' => '#4B5563', 'label' => '📢 ANNOUNCEMENT'],
                            ];
                            $s = $styles[$type] ?? $styles['feature'];
                            $versionHtml = $version ? '<span style="opacity:0.6;font-weight:600;">' . e($version) . ' — </span>' : '';

                            return new \Illuminate\Support\HtmlString('
                                <div style="background:' . $s['bg'] . ';border-left:4px solid ' . $s['border'] . ';border-radius:10px;padding:14px 18px;display:flex;align-items:flex-start;gap:12px;">
                                    <div style="background:' . $s['icon'] . ';border-radius:50%;width:30px;height:30px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                                        <span style="color:white;font-size:14px;">●</span>
                                    </div>
                                    <div style="flex:1;min-width:0;">
                                        <div style="font-size:10px;font-weight:800;color:' . $s['sub'] . ';text-transform:uppercase;letter-spacing:0.06em;margin-bottom:3px;">' . $s['label'] . '</div>
                                        <div style="font-size:13px;font-weight:800;color:' . $s['text'] . ';margin-bottom:3px;">' . $versionHtml . e($title) . '</div>
                                        <div style="font-size:13px;color:' . $s['sub'] . ';line-height:1.6;white-space:pre-wrap;">' . e($body) . '</div>
                                    </div>
                                </div>
                            ');
                        }),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('version')->badge()->color('gray')->placeholder('—'),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ReleaseNote::typeStyle($state)['label'])
                    ->color(fn (string $state) => ReleaseNote::typeStyle($state)['color']),

                Tables\Columns\TextColumn::make('title')->weight('bold')->searchable(),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),

                Tables\Columns\IconColumn::make('email_sent')
                    ->label('Email Sent')
                    ->boolean(),

                Tables\Columns\IconColumn::make('sms_sent')
                    ->label('SMS Sent')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')->label('Created')->dateTime('M j, Y g:i A')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                                Action::make('publish_notify')
                    ->label('Publish & Notify')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (ReleaseNote $record) => !$record->is_published)
                    ->form([
                        Forms\Components\CheckboxList::make('channels')
                            ->label('Notify via')
                            ->options([
                                'banner' => 'In-app banner (shown to every store on login)',
                                'email'  => 'Email every tenant admin (sent from info@jeweltag.us)',
                            ])
                            ->default(['banner', 'email'])
                            ->live()
                            ->required(),

                        // 🚀 NEW — pulls every active tenant's Superadmin email addresses
                        // right now, so you see exactly who's about to be emailed BEFORE
                        // confirming. No AWS/SES needed — this all goes through your
                        // DreamHost mailbox (info@jeweltag.us) configured in .env.
                        Forms\Components\Placeholder::make('recipient_preview')
                            ->label('Who will be emailed')
                            ->live()
                            ->visible(fn (Forms\Get $get) => in_array('email', $get('channels') ?? []))
                            ->content(function () {
                                $rows = self::collectRecipientEmails();

                                if ($rows->isEmpty()) {
                                    return new HtmlString('<div style="color:#dc2626;font-size:13px;font-weight:600;">⚠️ No active tenants with a Superadmin email found.</div>');
                                }

                                $html = '<div style="max-height:260px;overflow-y:auto;border:1px solid #e5e7eb;border-radius:8px;">';
                                foreach ($rows as $row) {
                                    $html .= "<div style='display:flex;justify-content:space-between;align-items:center;padding:8px 12px;border-bottom:1px solid #f1f5f9;font-size:12px;'>
                                        <span style='font-weight:700;color:#0B3D3C;'>{$row['tenant_id']}</span>
                                        <span style='background:#EAF6EF;color:#0F7A5C;padding:2px 10px;border-radius:99px;font-size:11px;font-weight:700;'>✉ {$row['email']}</span>
                                    </div>";
                                }
                                $html .= '</div>';
                                $html = '<div style="font-size:11px;color:#64748b;margin-bottom:6px;font-weight:600;">' . $rows->count() . ' email(s) will be sent:</div>' . $html;

                                return new HtmlString($html);
                            }),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading(fn (ReleaseNote $record) => "Publish \"{$record->title}\"?")
                    ->modalDescription('Review the recipient list above — sends go out immediately on confirm.')
                    ->modalSubmitActionLabel('Confirm & Send')
                    ->action(function (ReleaseNote $record, array $data) {
                        $channels = $data['channels'] ?? [];

                        $record->update([
                            'is_published' => true,
                            'published_at' => now(),
                        ]);

                        $emailCount = 0;
                        $failures   = [];

                        if (in_array('email', $channels)) {
                            $rows = self::collectRecipientEmails();

                            foreach ($rows as $row) {
                                try {
                                    Mail::to($row['email'])->send(new ReleaseNoteMail($record, $row['name']));
                                    $emailCount++;
                                } catch (\Exception $e) {
                                    $failures[] = $row['email'];
                                    \Illuminate\Support\Facades\Log::error("Release note email failed for {$row['email']}: " . $e->getMessage());
                                }
                            }

                            $record->update(['email_sent' => true]);
                        }

                        \App\Models\MasterAuditLog::record(
                            action: 'release_note_published',
                            fieldLabel: 'Release Note',
                            oldValue: null,
                            newValue: $record->title . ' (' . implode(', ', $channels) . ')',
                            severity: 'info',
                        );

                        $failureNote = !empty($failures) ? ' (' . count($failures) . ' failed — check logs)' : '';

                        Notification::make()
                            ->title('Release Note Published')
                            ->body("Sent to {$emailCount} email(s){$failureNote}. Banner is now live in every active store.")
                            ->success()
                            ->send();
                    }),

                Action::make('unpublish')
                    ->label('Unpublish')
                    ->icon('heroicon-o-eye-slash')
                    ->color('danger')
                    ->visible(fn (ReleaseNote $record) => $record->is_published)
                    ->requiresConfirmation()
                    ->action(fn (ReleaseNote $record) => $record->update(['is_published' => false])),

                Tables\Actions\DeleteAction::make(),
            ]);
    }

      // 🚀 UPDATED — pulls the STORE's own contact email (from each tenant's
    // Store model, e.g. "info@lxdiamond.com") rather than a staff/user's
    // personal login email. Falls back to the Superadmin's email only if
    // the store record has no email set, so a tenant is never silently
    // skipped just because they haven't filled in their store profile.
    protected static function collectRecipientEmails()
    {
        $rows = collect();

        $tenants = Tenant::where('is_active', true)->get();

        foreach ($tenants as $tenant) {
            try {
                               $tenant->run(function () use ($tenant, &$rows) {
                    // 🚀 Prefer the HQ store's contact email if this tenant has
                    // multiple Store rows (branch locations); fall back to the
                    // first store on record if no row is flagged as HQ.
                    $store = \App\Models\Store::where('is_hq', true)->first()
                        ?? \App\Models\Store::first();

                    $email = $store?->email ?? null;
                    $name  = $store?->name ?? $tenant->id;

                    // Fallback: if the store has no contact email on file,
                    // use the Superadmin user's email instead of skipping this tenant.
                    if (empty($email)) {
                        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'Superadmin'))
                            ->whereNotNull('email')
                            ->where('email', '!=', '')
                            ->first();
                        $email = $admin?->email;
                        $name  = $admin?->name ?? $name;
                    }

                    if (!empty($email)) {
                        $rows->push([
                            'tenant_id' => $tenant->id,
                            'name'      => $name,
                            'email'     => $email,
                        ]);
                    }
                });
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Could not fetch store email for tenant {$tenant->id}: " . $e->getMessage());
            }
        }

        return $rows;
    }
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListReleaseNotes::route('/'),
            'create' => Pages\CreateReleaseNote::route('/create'),
            'edit'   => Pages\EditReleaseNote::route('/{record}/edit'),
        ];
    }
}