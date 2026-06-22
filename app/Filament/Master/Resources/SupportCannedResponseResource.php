<?php

namespace App\Filament\Master\Resources;

use App\Models\SupportCannedResponse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupportCannedResponseResource extends Resource
{
    protected static ?string $model = SupportCannedResponse::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';
    protected static ?string $navigationLabel = 'Canned Responses';
    protected static ?string $navigationGroup = 'Support';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->required(),
            Forms\Components\TextInput::make('category'),
            Forms\Components\Textarea::make('body')->rows(5)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title'),
            Tables\Columns\TextColumn::make('category')->badge(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => \App\Filament\Master\Resources\SupportCannedResponseResource\Pages\ListSupportCannedResponses::route('/'),
            'create' => \App\Filament\Master\Resources\SupportCannedResponseResource\Pages\CreateSupportCannedResponse::route('/create'),
            'edit'   => \App\Filament\Master\Resources\SupportCannedResponseResource\Pages\EditSupportCannedResponse::route('/{record}/edit'),
        ];
    }
}