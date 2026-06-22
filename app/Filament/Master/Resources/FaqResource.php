<?php

namespace App\Filament\Master\Resources;

use App\Models\Faq;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;
    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';
    protected static ?string $navigationLabel = 'FAQ Manager';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $recordTitleAttribute = 'question';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('FAQ Entry')
                ->description('This will be visible to every store on the platform.')
                ->icon('heroicon-o-question-mark-circle')
                ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\Select::make('category')
                            ->label('Category')
                            ->native(false)
                            ->options(Faq::getCategoryOptions())
                            ->default('general')
                            ->required()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Display Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers show first')
                            ->columnSpan(1),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Published')
                            ->default(true)
                            ->onColor('success')
                            ->inline(false)
                            ->columnSpan(1),
                    ]),

                    Forms\Components\TextInput::make('question')
                        ->label('Question')
                        ->placeholder('e.g. How do I push an item to Shopify?')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\RichEditor::make('answer')
                        ->label('Answer')
                        ->placeholder('Write a clear, step-by-step answer...')
                        ->toolbarButtons([
                            'bold', 'italic', 'underline', 'bulletList', 'orderedList', 'link', 'h2', 'h3',
                        ])
                        ->required()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->color('gray')
                    ->grow(false),

                Tables\Columns\TextColumn::make('category')
                    ->label('CATEGORY')
                    ->badge()
                    ->formatStateUsing(fn($state) => Faq::getCategoryOptions()[$state] ?? ucfirst($state))
                    ->color('info'),

                Tables\Columns\TextColumn::make('question')
                    ->label('QUESTION')
                    ->searchable()
                    ->wrap()
                    ->weight('semibold')
                    ->limit(70),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('PUBLISHED')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('view_count')
                    ->label('VIEWS')
                    ->numeric()
                    ->sortable()
                    ->color('gray')
                    ->grow(false),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('UPDATED')
                    ->since()
                    ->color('gray')
                    ->size('xs'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options(Faq::getCategoryOptions()),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Published Status'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('toggle_published')
                        ->label(fn(Faq $record) => $record->is_active ? 'Unpublish' : 'Publish')
                        ->icon(fn(Faq $record) => $record->is_active ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                        ->color(fn(Faq $record) => $record->is_active ? 'gray' : 'success')
                        ->action(fn(Faq $record) => $record->update(['is_active' => !$record->is_active])),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order', 'asc')
            ->emptyStateHeading('No FAQs yet')
            ->emptyStateDescription('Add your first FAQ — it will be visible to every store.')
            ->emptyStateIcon('heroicon-o-question-mark-circle');
    }

  public static function getPages(): array
{
    return [
        'index'  => \App\Filament\Master\Resources\FaqResource\Pages\ListFaqs::route('/'),
        'create' => \App\Filament\Master\Resources\FaqResource\Pages\CreateFaq::route('/create'),
        'edit'   => \App\Filament\Master\Resources\FaqResource\Pages\EditFaq::route('/{record}/edit'),
    ];
}
}