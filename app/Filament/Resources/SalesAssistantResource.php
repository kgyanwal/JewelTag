<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesAssistantResource\Pages;
use App\Models\SalesAssistant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SalesAssistantResource extends Resource
{
    protected static ?string $model = SalesAssistant::class;
    
    // ✅ Changed Icon to a "Badge" style icon
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationLabel = 'Staff Management';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ✅ Modern Layout: Card with Description
                Forms\Components\Section::make('Staff Profile')
                    ->description('Manage access and credentials for sales associates.')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->label('Full Name')
                                ->placeholder('e.g. Prince')
                                ->columnSpan(1),
                            
                            Forms\Components\TextInput::make('employee_code')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->label('Employee ID')
                                ->prefix('EMP-') // Adds a professional prefix look
                                ->columnSpan(1),
                        ]),

                        Forms\Components\Select::make('store_id')
                            ->relationship('store', 'name')
                            ->required()
                            ->searchable() // Easier for many stores
                            ->preload()
                            ->label('Assigned Store'),
                        
                        Forms\Components\Group::make()->schema([
                             Forms\Components\TextInput::make('pin_code')
                                ->label('POS Security PIN')
                                ->password()
                                ->revealable()
                                ->numeric()
                                ->length(4)
                                ->required()
                                ->helperText('4-digit code for POS access.'),
                            
                            Forms\Components\Toggle::make('is_active')
                                ->default(true)
                                ->label('Active Status')
                                ->onColor('success')
                                ->offColor('danger')
                                ->inline(false),
                        ])->columns(2),
                        
                    ])->columns(1), // Main section column count
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ✅ 1. Avatar Column (Generated from Name)
                Tables\Columns\TextColumn::make('name')
                    ->label('Staff Member')
                    ->formatStateUsing(fn ($state) => $state)
                    ->description(fn (SalesAssistant $record): string => $record->employee_code) // Shows ID under name
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('store.name')
                    ->label('Store Location')
                    ->icon('heroicon-m-building-storefront')
                    ->sortable()
                    ->color('gray'),

                // ✅ 2. Modern Status Badge
                Tables\Columns\TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive')
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->date()
                    ->label('Joined')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // ✅ Simple Filter for Active/Inactive
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Staff Status')
                    ->placeholder('All Staff')
                    ->trueLabel('Active Staff')
                    ->falseLabel('Inactive Staff'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton(), // Cleaner UI (just the icon)
                Tables\Actions\DeleteAction::make()->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesAssistants::route('/'),
            'create' => Pages\CreateSalesAssistant::route('/create'),
            'edit' => Pages\EditSalesAssistant::route('/{record}/edit'),
        ];
    }
}