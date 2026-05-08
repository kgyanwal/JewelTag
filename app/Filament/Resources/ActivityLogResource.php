<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Helpers\Staff;
use App\Models\ActivityLog;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use App\Forms\Components\CustomDatePicker;
use Illuminate\Support\Facades\DB;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-finger-print';
    protected static ?string $navigationGroup = 'Admin';
    protected static ?string $label = 'User Access Log';

    public static function shouldRegisterNavigation(): bool
    {
        $staff = Staff::user();
        return $staff?->hasAnyRole(['Superadmin', 'Administration']) ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('DATE')
                    ->dateTime('M d, Y h:i:s A')
                    ->sortable()
                    ->timezone(fn () => \App\Models\Store::first()->timezone ?? 'America/Denver'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('STAFF NAME')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('url')
                    ->label('URI')
                    ->fontFamily('mono')
                    ->color('gray')
                    ->wrap()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('identifier')
                    ->label('REFERENCE #')
                    ->placeholder('Page Visit')
                    ->searchable(),

                Tables\Columns\TextColumn::make('action')
                    ->label('ACTION')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Created' => 'success',
                        'Updated' => 'warning',
                        'Deleted' => 'danger',
                        'View' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('module')
                    ->label('MODULE')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                // 🔹 Professional "Trash" button to clear old logs
                Tables\Actions\Action::make('clearLogs')
                    ->label('Clear All Logs')
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->action(fn () => ActivityLog::truncate())
                    ->visible(fn () => auth()->user()->hasRole('Superadmin')),
            ])
            ->filters([
                // ───────── TOP ROW FILTERS ─────────
                Tables\Filters\Filter::make('search_row')
                    ->form([
                        Forms\Components\Grid::make(4)->schema([
                            // 1. Username Select
                            Forms\Components\Select::make('user_id')
                                ->label('Staff Name')
                                ->relationship('user', 'name')
                                ->placeholder('Any')
                                ->preload(),

                            // 2. Exact Date
                            CustomDatePicker::make('exact_date')
                                ->label('Date'),

                            // 3. Hour (0-23)
                            Forms\Components\Select::make('hour')
                                ->label('Hour')
                                ->options(array_combine(range(0, 23), range(0, 23)))
                                ->placeholder('Any'),

                            // 4. Minute (0-59)
                            Forms\Components\Select::make('minute')
                                ->label('Minute')
                                ->options(array_combine(range(0, 59), range(0, 59)))
                                ->placeholder('Any'),
                        ]),
                        
                        Forms\Components\Grid::make(3)->schema([
                            // 5. URI Search
                            Forms\Components\TextInput::make('uri_path')
                                ->label('URI')
                                ->placeholder('e.g. /utilities'),

                            // 6. Identifier Search
                            Forms\Components\TextInput::make('ref_no')
                                ->label('Reference #')
                                ->placeholder('Job or Stock Number'),

                            // 7. Action Badge Filter
                            Forms\Components\Select::make('action_type')
                                ->label('Action')
                                ->options([
                                    'View' => 'View',
                                    'Created' => 'Created',
                                    'Updated' => 'Updated',
                                    'Deleted' => 'Deleted',
                                ])->placeholder('Any'),
                        ]),
                    ])
                 ->query(function (Builder $query, array $data): Builder {
    // 🚀 1. Get the store's timezone
    $storeTimezone = \App\Models\Store::first()->timezone ?? 'America/Denver';

    return $query
        ->when($data['user_id'], fn ($q, $v) => $q->where('user_id', $v))
        ->when($data['uri_path'], fn ($q, $v) => $q->where('url', 'like', "%{$v}%"))
        ->when($data['ref_no'], fn ($q, $v) => $q->where('identifier', 'like', "%{$v}%"))
        ->when($data['action_type'], fn ($q, $v) => $q->where('action', $v))
        
        // 🚀 2. Handle Timezone Conversion for Date Searches
        ->when($data['exact_date'], function ($q, $v) use ($storeTimezone) {
            // Convert the local start of day to UTC
            $startOfDayUTC = \Carbon\Carbon::parse($v, $storeTimezone)->startOfDay()->utc();
            // Convert the local end of day to UTC
            $endOfDayUTC = \Carbon\Carbon::parse($v, $storeTimezone)->endOfDay()->utc();
            
            // Search between those exact UTC boundaries
            $q->whereBetween('created_at', [$startOfDayUTC, $endOfDayUTC]);
        });
})
            ], layout: FiltersLayout::AboveContent) // 👈 This creates the "Search Panel" UI
            ->filtersFormColumns(1);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}