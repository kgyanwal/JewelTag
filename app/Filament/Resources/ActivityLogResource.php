<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Helpers\Staff;
use App\Models\ActivityLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-finger-print';
    protected static ?string $navigationGroup = 'Admin';

   public static function shouldRegisterNavigation(): bool
    {
        // Use your Staff helper to check the identity of the person who entered the PIN
        $staff = Staff::user();

        // Only allow specific roles to see the Administration menu
        return $staff?->hasAnyRole(['Superadmin', 'Administration']) ?? false;
    }

    public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('created_at')
                ->label('Time')
                ->dateTime('M d, Y h:i:s A') // 12-hour format like screenshot
                ->sortable(),

            Tables\Columns\TextColumn::make('user.name')
                ->label('Username')
                ->searchable(),

            Tables\Columns\TextColumn::make('url') // 🔹 NEW COLUMN
                ->label('URI') 
                ->fontFamily('mono')
                ->color('gray')
                ->searchable()
                ->toggleable(),

            Tables\Columns\TextColumn::make('action')
                ->badge()
                ->color(fn ($state) => match ($state) {
                    'Created' => 'success',
                    'Updated' => 'warning',
                    'Deleted' => 'danger',
                    'View' => 'info', // 🔹 Added View color
                    default => 'gray',
                }),

            Tables\Columns\TextColumn::make('module')->label('Module'),
            
            Tables\Columns\TextColumn::make('identifier')
                ->label('Reference #')
                ->searchable(),
        ])
        ->defaultSort('created_at', 'desc')
        ->poll('10s'); // Auto-refresh the log table
}
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}