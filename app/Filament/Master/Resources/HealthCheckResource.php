<?php

namespace App\Filament\Master\Resources;

use App\Filament\Master\Resources\HealthCheckResource\Pages;
// 🚨 CHANGE THIS: Import the Tenant model instead
use App\Models\Tenant; 
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class HealthCheckResource extends Resource
{
    // 🚨 CHANGE THIS: Point to Tenant::class
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'System Health';
    protected static ?string $navigationGroup = 'Infrastructure';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Store ID')
                    ->weight('bold'),

                // 🚀 Dynamic Database Connection Check
                Tables\Columns\IconColumn::make('db_status')
                    ->label('DB Status')
                    ->getStateUsing(fn ($record) => $record->run(fn() => true)) 
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                // 🚀 Check for pending migrations
                Tables\Columns\TextColumn::make('migration_status')
                    ->label('Migrations')
                    ->getStateUsing(function ($record) {
                        return $record->run(function() {
                            $total = DB::table('migrations')->count();
                            return "{$total} Migrations Run";
                        });
                    })
                    ->badge()
                    ->color('info'),
            ])
           ->actions([
                Tables\Actions\Action::make('migrate')
                    ->label('Fix DB')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->color('warning')
                    ->requiresConfirmation()
                    // 🚨 THE FIX: Change '--tenant' to '--tenants' and wrap the ID in []
                    ->action(function ($record) {
                        Artisan::call('tenants:migrate', [
                            '--tenants' => [$record->id] 
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Migrations Completed')
                            ->body("Database for {$record->id} is now up to date.")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHealthChecks::route('/'),
        ];
    }
}