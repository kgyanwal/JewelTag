<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryAuditResource\Pages;
use App\Models\InventoryAudit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;

class InventoryAuditResource extends Resource
{
    protected static ?string $model = InventoryAudit::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $slug = 'inventory-audits';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?int $navigationSort = 10;




    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Audit Details')
                    ->schema([
                        Forms\Components\TextInput::make('session_name')
                            ->required()
                            ->default('Audit - ' . now()->format('M d, Y')),
                        Forms\Components\Select::make('status')
                            ->options(['open' => 'Open', 'completed' => 'Completed'])
                            ->default('open'),
                        Forms\Components\Hidden::make('user_id')
                            ->default(auth()->id()),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('session_name')->searchable(),
                Tables\Columns\TextColumn::make('status')->badge()->color('success'),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->actions([
                // 🚀 THE BUTTON TO OPEN THE SCANNER
                Action::make('scan')
                    ->label('Open Scanner')
                    ->icon('heroicon-o-qr-code')
                    ->color('success')
                    ->url(fn (InventoryAudit $record): string => route('inventory.audit', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryAudits::route('/'),
            'create' => Pages\CreateInventoryAudit::route('/create'),
            'edit' => Pages\EditInventoryAudit::route('/{record}/edit'),
        ];
    }
}