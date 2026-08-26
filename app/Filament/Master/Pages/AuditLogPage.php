<?php

namespace App\Filament\Master\Pages;

use Filament\Forms;
use App\Models\MasterAuditLog;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class AuditLogPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Audit Log';
    protected static ?string $navigationGroup = 'SaaS Management';
    protected static ?int $navigationSort = 90;
    protected static string $view = 'filament.master.pages.audit-log-page';

    public function table(Table $table): Table
    {
        return $table
            ->query(MasterAuditLog::query()->latest())
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->description(fn (MasterAuditLog $record) => $record->created_at->diffForHumans()),

                Tables\Columns\TextColumn::make('actor_name')
                    ->label('Staff Member')
                    ->weight('bold')
                    ->searchable()
                    ->icon('heroicon-m-user-circle'),

                Tables\Columns\TextColumn::make('tenant_name')
                    ->label('Tenant')
                    ->formatStateUsing(fn (MasterAuditLog $record) => $record->tenant_name ?? $record->tenant_id ?? '—')
                    ->searchable(['tenant_name', 'tenant_id'])
                    ->url(fn (MasterAuditLog $record) => $record->tenant_id
                        ? \App\Filament\Master\Resources\TenantResource::getUrl('edit', ['record' => $record->tenant_id])
                        : null)
                    ->openUrlInNewTab()
                    ->color('info'),

                Tables\Columns\TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'impersonated'          => '👤 Impersonated',
                        'plan_changed'          => '🔄 Plan Changed',
                        'system_status_toggled' => '⚡ System Status Toggled',
                        'billing_edited'        => '💳 Billing Edited',
                        'tenant_created'        => '✨ Tenant Created',
                        'tenant_suspended'      => '🚫 Tenant Suspended',
                        'tenant_reactivated'    => '✅ Tenant Reactivated',
                        'trial_extended'        => '⏳ Trial Extended',
                        'emergency_reset'       => '🆘 Force Reset',
                        'tenant_archived'       => '📦 Tenant Archived',
                        default                 => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->color(fn (MasterAuditLog $record) => match ($record->severity) {
                        'critical' => 'danger',
                        'warning'  => 'warning',
                        default    => 'gray',
                    }),

                Tables\Columns\TextColumn::make('change')
                    ->label('Change')
                    ->html()
                    ->getStateUsing(function (MasterAuditLog $record) {
                        if ($record->old_value === null && $record->new_value === null) {
                            return $record->field_label ?? '—';
                        }
                        $label = $record->field_label ? e($record->field_label) . ': ' : '';
                        $old = e($record->old_value ?? '—');
                        $new = e($record->new_value ?? '—');
                        return "<span style='font-size:12px;'>{$label}<span style='background:#fee2e2;color:#b91c1c;padding:2px 8px;border-radius:6px;font-weight:700;text-decoration:line-through;'>{$old}</span> <span style='color:#94a3b8;'>→</span> <span style='background:#dcfce7;color:#15803d;padding:2px 8px;border-radius:6px;font-weight:700;'>{$new}</span></span>";
                    }),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->color('gray')
                    ->size('xs')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label('Tenant')
                    ->options(fn () => \App\Models\Tenant::pluck('id', 'id')),

                Tables\Filters\SelectFilter::make('actor_id')
                    ->label('Staff Member')
                    ->options(fn () => \App\Models\User::pluck('name', 'id')),

                Tables\Filters\SelectFilter::make('action')
                    ->label('Action Type')
                    ->options([
                        'impersonated'          => 'Impersonated',
                        'plan_changed'          => 'Plan Changed',
                        'system_status_toggled' => 'System Status Toggled',
                        'billing_edited'        => 'Billing Edited',
                        'tenant_created'        => 'Tenant Created',
                        'tenant_suspended'      => 'Tenant Suspended',
                        'tenant_reactivated'    => 'Tenant Reactivated',
                        'trial_extended'        => 'Trial Extended',
                        'emergency_reset'       => 'Force Reset',
                        'tenant_archived'       => 'Tenant Archived',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s'); // near-live without hammering the DB
    }
}