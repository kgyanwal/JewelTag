<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryAuditResource\Pages;
use App\Models\InventoryAudit;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
// 🚀 Ensure these are here at the top
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\TextColumn;

class InventoryAuditResource extends Resource
{
    protected static ?string $model = InventoryAudit::class;

    // ... other methods like form()

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('session_name')->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->label('Date')->date(),

                Panel::make([
                    Split::make([
                        TextColumn::make('scans_count')
                            ->label('Total Scans')
                            ->default(fn($record) => $record->auditItems()->count() . " items found."),
                        
                        TextColumn::make('id')
                            ->label('View Full History')
                            ->formatStateUsing(fn() => 'View Details')
                            ->url(fn($record) => route('audit.history', $record->id))
                            ->color('primary'),
                    ]),
                ])
                ->collapsible()
                ->collapsed(true),
            ]);
    }
}