<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-finger-print';
    protected static ?string $navigationGroup = 'Administration';

    public static function canViewAny(): bool
    {
        // 🔹 Only allow high-level roles
        return auth()->user()->hasAnyRole(['Superadmin', 'Admin']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Time')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('User')->searchable(),
                Tables\Columns\TextColumn::make('action')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Created' => 'success',
                        'Updated' => 'warning',
                        'Deleted' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('module')->label('Module'),
                Tables\Columns\TextColumn::make('identifier')->label('Reference #')->searchable(),
                Tables\Columns\TextColumn::make('ip_address')->label('IP Address'),
            ])
            ->defaultSort('created_at', 'desc');
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}