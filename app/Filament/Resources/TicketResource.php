<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\{Section, TextInput, Select, Repeater, Textarea, FileUpload, Placeholder, Hidden, Group};
use Illuminate\Database\Eloquent\Builder;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'Support';
public static function form(Form $form): Form
{
    return $form->schema([
        Section::make('Ticket Information')->schema([
            TextInput::make('subject')
                ->required()
                ->columnSpanFull()
                ->disabled(fn ($record) => $record !== null),
            Select::make('category')
        ->options([
            'sales' => 'Sales',
            'technical' => 'Technical',
            'billing' => 'Billing',
        ])
        ->required()
        ->default('technical'),
            // Initial Message Field: Only visible during CREATE
            Textarea::make('initial_message')
                ->label('Description of Issue')
                ->required()
                ->visible(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord)
                ->dehydrated(false) // We handle saving this manually in the Page class
                ->columnSpanFull(),

            Select::make('status')
                ->options(['open' => 'Open', 'pending' => 'Pending', 'closed' => 'Closed'])
                ->required()
                ->default('open')
                ->visible(fn () => auth()->user()->hasRole('Superadmin')), // Only Developer sees this

            Select::make('priority')
                ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'])
                ->default('medium')
                ->required(),

            Hidden::make('user_id')
                ->default(auth()->id()),
        ])->columns(2),

        // Conversation Thread: Only visible during EDIT
        Section::make('Conversation Thread')
            ->visible(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\EditRecord)
            ->schema([
                Repeater::make('messages')
                    ->relationship('messages')
                    ->schema([
                        Group::make([
                            Placeholder::make('author')
                                ->label('')
                                ->content(function ($record) {
                                    $name = $record?->user?->name ?? auth()->user()->name;
                                    $role = $record?->user?->hasRole('Superadmin') ? '🛠️ Developer' : '👤 User';
                                    return new \Illuminate\Support\HtmlString("<strong>{$role}: {$name}</strong>");
                                }),
                        ])->columns(1),

                        Textarea::make('content')
                            ->label('Message')
                            ->required()
                            ->rows(3),
                        
                        Hidden::make('user_id')->default(auth()->id()),
                    ])
                    ->reorderable(false)
                    ->addActionLabel('Send Reply')
            ]),
    ]);
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('Ticket ID')->sortable(),
                Tables\Columns\TextColumn::make('subject')->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('Customer')->visible(fn() => auth()->user()->hasRole('Superadmin')),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'open',
                        'warning' => 'pending',
                        'gray' => 'closed',
                    ]),
                Tables\Columns\TextColumn::make('priority')->badge(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // 🔹 Advanced "OneSwim" Logic: Sales Assistants only see their own tickets
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (!auth()->user()->hasRole('Superadmin')) {
            return $query->where('user_id', auth()->id());
        }
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}