<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductTemplateResource\Pages;
use App\Models\ProductTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductTemplateResource extends Resource
{
    protected static ?string $model = ProductTemplate::class;
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Inventory';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Design Details')->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->label('Design Name'),
                    
                    Forms\Components\TextInput::make('sku_prefix')
                        ->required()
                        ->label('SKU Prefix')
                        ->placeholder('e.g. RNG-SOL'),
                    
                    Forms\Components\Select::make('category')
                        ->options([
                            'Ring' => 'Ring',
                            'Necklace' => 'Necklace',
                            'Earring' => 'Earring',
                            'Bracelet' => 'Bracelet',
                            'Watch' => 'Watch',
                            'Loose Stone' => 'Loose Stone',
                        ])
                        ->required(),
                    
                    Forms\Components\FileUpload::make('image_path')
                        ->image()
                        ->directory('designs'),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path'),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('sku_prefix'),
                Tables\Columns\TextColumn::make('category'),
            ])
            ->filters([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductTemplates::route('/'),
            'create' => Pages\CreateProductTemplate::route('/create'),
            'edit' => Pages\EditProductTemplate::route('/{record}/edit'),
        ];
    }
}